<?php
/**
 * Crawler - Extracts content from WordPress posts/pages.
 *
 * @package WPAICB\Content
 */

namespace WPAICB\Content;

use WPAICB\Admin\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Crawler {

    /**
     * Get all publishable content for indexing.
     *
     * @param array $content_types Post types to crawl.
     * @param array $excluded_posts Post IDs to exclude.
     * @return array Array of post objects.
     */
    public function get_indexable_content( $content_types = array(), $excluded_posts = array() ) {
        if ( empty( $content_types ) ) {
            $content_types = Admin::get_settings( 'content_types', array( 'post', 'page' ) );
        }

        if ( empty( $excluded_posts ) ) {
            $excluded_posts = Admin::get_settings( 'excluded_posts', array() );
        }

        $args = array(
            'post_type'      => $content_types,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'fields'         => 'ids',
        );

        if ( ! empty( $excluded_posts ) ) {
            $args['post__not_in'] = array_map( 'absint', $excluded_posts );
        }

        return get_posts( $args );
    }

    /**
     * Extract clean text content from a post.
     *
     * @param int $post_id Post ID.
     * @return array|null Array with 'title', 'content', 'type', 'url', 'excerpt' or null if invalid.
     */
    public function extract_post_content( $post_id ) {
        $post = get_post( $post_id );

        if ( ! $post || 'publish' !== $post->post_status ) {
            return null;
        }

        // Get the content and strip it clean
        $content = $post->post_content;

        // Apply content filters to process shortcodes, blocks, etc.
        $content = apply_filters( 'the_content', $content );

        // Strip HTML tags but keep paragraph structure
        $content = $this->html_to_text( $content );

        // Clean up whitespace
        $content = $this->clean_text( $content );

        if ( empty( trim( $content ) ) ) {
            return null;
        }

        // Get excerpt
        $excerpt = $post->post_excerpt;
        if ( empty( $excerpt ) ) {
            $excerpt = wp_trim_words( $content, 55 );
        }

        return array(
            'post_id' => $post_id,
            'title'   => $post->post_title,
            'content' => $content,
            'type'    => $post->post_type,
            'url'     => get_permalink( $post_id ),
            'excerpt' => $excerpt,
        );
    }

    /**
     * Convert HTML to clean text preserving paragraph structure.
     *
     * @param string $html HTML content.
     * @return string Clean text.
     */
    private function html_to_text( $html ) {
        // Remove script and style elements
        $html = preg_replace( '/<script[^>]*>.*?<\/script>/si', '', $html );
        $html = preg_replace( '/<style[^>]*>.*?<\/style>/si', '', $html );

        // Convert headers to text with newlines
        $html = preg_replace( '/<h[1-6][^>]*>(.*?)<\/h[1-6]>/si', "\n\n$1\n\n", $html );

        // Convert paragraphs and divs to newlines
        $html = preg_replace( '/<\/(p|div|article|section|li)>/i', "\n", $html );
        $html = preg_replace( '/<br\s*\/?>/i', "\n", $html );

        // Convert list items
        $html = preg_replace( '/<li[^>]*>/i', '- ', $html );

        // Strip remaining HTML tags
        $html = wp_strip_all_tags( $html );

        // Decode HTML entities
        $html = html_entity_decode( $html, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

        return $html;
    }

    /**
     * Clean up text by normalizing whitespace.
     *
     * @param string $text Raw text.
     * @return string Cleaned text.
     */
    private function clean_text( $text ) {
        // Normalize line endings
        $text = str_replace( array( "\r\n", "\r" ), "\n", $text );

        // Remove excessive blank lines (more than 2)
        $text = preg_replace( '/\n{3,}/', "\n\n", $text );

        // Remove excessive spaces
        $text = preg_replace( '/[ \t]+/', ' ', $text );

        // Trim lines
        $lines = explode( "\n", $text );
        $lines = array_map( 'trim', $lines );
        $text  = implode( "\n", $lines );

        return trim( $text );
    }

    /**
     * Get posts that have been modified since last index.
     *
     * @param string $since DateTime string.
     * @return array Array of post IDs.
     */
    public function get_modified_since( $since ) {
        $content_types = Admin::get_settings( 'content_types', array( 'post', 'page' ) );

        $args = array(
            'post_type'      => $content_types,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'date_query'     => array(
                array(
                    'column' => 'post_modified',
                    'after'  => $since,
                ),
            ),
            'fields' => 'ids',
        );

        return get_posts( $args );
    }

    /**
     * Extract metadata from a post for keyword generation.
     *
     * @param int $post_id Post ID.
     * @return array Metadata including categories, tags, etc.
     */
    public function get_post_metadata( $post_id ) {
        $metadata = array();

        // Get categories
        $categories = get_the_category( $post_id );
        if ( $categories ) {
            foreach ( $categories as $cat ) {
                $metadata[] = $cat->name;
            }
        }

        // Get tags
        $tags = get_the_tags( $post_id );
        if ( $tags ) {
            foreach ( $tags as $tag ) {
                $metadata[] = $tag->name;
            }
        }

        // Get custom taxonomies
        $post_type   = get_post_type( $post_id );
        $taxonomies  = get_object_taxonomies( $post_type, 'names' );
        $skip_taxs   = array( 'category', 'post_tag', 'post_format' );

        foreach ( $taxonomies as $taxonomy ) {
            if ( in_array( $taxonomy, $skip_taxs, true ) ) {
                continue;
            }
            $terms = get_the_terms( $post_id, $taxonomy );
            if ( $terms && ! is_wp_error( $terms ) ) {
                foreach ( $terms as $term ) {
                    $metadata[] = $term->name;
                }
            }
        }

        return array_unique( $metadata );
    }
}
