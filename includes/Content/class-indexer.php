<?php
/**
 * Indexer - Orchestrates content crawling, chunking, and storage.
 *
 * @package WPAICB\Content
 */

namespace WPAICB\Content;

use WPAICB\Admin\Admin;
use WPAICB\Database\Content_Model;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Indexer {

    /**
     * Crawler instance.
     *
     * @var Crawler
     */
    private $crawler;

    /**
     * Chunker instance.
     *
     * @var Chunker
     */
    private $chunker;

    /**
     * Content model instance.
     *
     * @var Content_Model
     */
    private $content_model;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->crawler       = new Crawler();
        $this->chunker       = new Chunker();
        $this->content_model = new Content_Model();
    }

    /**
     * Index all site content.
     *
     * @param bool $clear_existing Whether to clear existing content first.
     * @return array Result with 'posts_indexed', 'chunks_created', 'errors'.
     */
    public function index_all( $clear_existing = true ) {
        $result = array(
            'posts_indexed'  => 0,
            'chunks_created' => 0,
            'errors'         => array(),
        );

        // Clear existing content if requested
        if ( $clear_existing ) {
            $this->content_model->clear_all();
        }

        // Get all indexable post IDs
        $post_ids = $this->crawler->get_indexable_content();

        if ( empty( $post_ids ) ) {
            $result['errors'][] = __( 'No content found to index.', 'wp-ai-chatbot' );
            return $result;
        }

        foreach ( $post_ids as $post_id ) {
            $indexed = $this->index_post( $post_id );

            if ( $indexed > 0 ) {
                $result['posts_indexed']++;
                $result['chunks_created'] += $indexed;
            } else {
                $result['errors'][] = sprintf(
                    /* translators: %d: post ID */
                    __( 'Failed to index post ID: %d', 'wp-ai-chatbot' ),
                    $post_id
                );
            }
        }

        return $result;
    }

    /**
     * Index a single post.
     *
     * @param int $post_id Post ID to index.
     * @return int Number of chunks created, 0 on failure.
     */
    public function index_post( $post_id ) {
        // Extract content
        $post_data = $this->crawler->extract_post_content( $post_id );

        if ( null === $post_data ) {
            return 0;
        }

        // Remove existing chunks for this post
        $this->content_model->delete_by_post( $post_id );

        // Get metadata for keywords
        $metadata = $this->crawler->get_post_metadata( $post_id );

        // Chunk the content
        $chunks = $this->chunker->chunk( $post_data['content'], $post_data['title'] );

        if ( empty( $chunks ) ) {
            return 0;
        }

        // Store chunks
        $stored = 0;
        foreach ( $chunks as $chunk ) {
            $keywords = $this->chunker->extract_keywords( $chunk['text'], $metadata );

            $result = $this->content_model->create( array(
                'post_id'      => $post_id,
                'content_type' => $post_data['type'],
                'title'        => $post_data['title'],
                'chunk_text'   => $chunk['text'],
                'chunk_index'  => $chunk['index'],
                'word_count'   => $chunk['word_count'],
                'keywords'     => $keywords,
                'status'       => 'active',
            ) );

            if ( false !== $result ) {
                $stored++;
            }
        }

        return $stored;
    }

    /**
     * Handle post save - re-index when content changes.
     *
     * @param int      $post_id Post ID.
     * @param \WP_Post $post Post object.
     * @param bool     $update Whether this is an update.
     */
    public function handle_post_save( $post_id, $post, $update ) {
        // Skip autosaves and revisions
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }

        // Check if auto-indexing is enabled
        if ( ! Admin::get_settings( 'auto_index', true ) ) {
            return;
        }

        // Check if this post type is configured for indexing
        $content_types = Admin::get_settings( 'content_types', array( 'post', 'page' ) );
        if ( ! in_array( $post->post_type, $content_types, true ) ) {
            return;
        }

        // Check excluded posts
        $excluded = Admin::get_settings( 'excluded_posts', array() );
        if ( in_array( $post_id, $excluded, true ) ) {
            return;
        }

        // If post is published, index it. If unpublished, remove it.
        if ( 'publish' === $post->post_status ) {
            $this->index_post( $post_id );
        } else {
            $this->content_model->delete_by_post( $post_id );
        }
    }

    /**
     * Handle post deletion - remove from index.
     *
     * @param int $post_id Post ID being deleted.
     */
    public function handle_post_delete( $post_id ) {
        $this->content_model->delete_by_post( $post_id );
    }

    /**
     * Process the index queue (called by cron).
     * Re-indexes content that has been modified since last run.
     */
    public function process_index_queue() {
        if ( ! Admin::get_settings( 'auto_index', true ) ) {
            return;
        }

        $last_run = get_option( 'wpaicb_last_index_run', '' );

        if ( empty( $last_run ) ) {
            // First run - index everything
            $this->index_all();
        } else {
            // Index only modified content
            $modified_posts = $this->crawler->get_modified_since( $last_run );

            foreach ( $modified_posts as $post_id ) {
                $this->index_post( $post_id );
            }
        }

        update_option( 'wpaicb_last_index_run', current_time( 'mysql' ) );
    }

    /**
     * Get indexing status information.
     *
     * @return array
     */
    public function get_status() {
        $content_types = Admin::get_settings( 'content_types', array( 'post', 'page' ) );
        $stats         = $this->content_model->get_stats();

        // Count total available posts
        $total_available = 0;
        foreach ( $content_types as $type ) {
            $count = wp_count_posts( $type );
            if ( $count ) {
                $total_available += (int) $count->publish;
            }
        }

        return array(
            'indexed_posts'   => $stats['total_posts'],
            'total_available' => $total_available,
            'total_chunks'    => $stats['total_chunks'],
            'total_words'     => $stats['total_words'],
            'last_indexed'    => $stats['last_indexed'],
            'content_types'   => $content_types,
            'is_complete'     => $stats['total_posts'] >= $total_available,
        );
    }
}
