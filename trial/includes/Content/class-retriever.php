<?php
/**
 * Retriever - Finds relevant content chunks for a given query.
 *
 * @package WPAICB\Content
 */

namespace WPAICB\Content;

use WPAICB\Admin\Admin;
use WPAICB\Database\Content_Model;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Retriever {

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
        $this->content_model = new Content_Model();
    }

    /**
     * Retrieve relevant content chunks for a user query.
     *
     * @param string $query User's question.
     * @param int    $max_chunks Maximum number of chunks to return.
     * @return array Array of content chunk objects.
     */
    public function get_relevant_context( $query, $max_chunks = 0 ) {
        if ( empty( $max_chunks ) ) {
            $max_chunks = (int) Admin::get_settings( 'max_context_chunks', 5 );
        }

        // First check custom Q&A pairs for an exact or near match
        $qa_match = $this->check_custom_qa( $query );
        if ( $qa_match ) {
            return array( $qa_match );
        }

        // Search content chunks by keyword relevance
        $chunks = $this->content_model->search( $query, $max_chunks );

        if ( empty( $chunks ) ) {
            return array();
        }

        return $chunks;
    }

    /**
     * Check custom Q&A pairs for a matching question.
     *
     * @param string $query User's question.
     * @return object|null Matching Q&A object or null.
     */
    private function check_custom_qa( $query ) {
        global $wpdb;

        $table = $wpdb->prefix . 'aicb_custom_qa';

        // Get all active Q&A pairs
        $qa_pairs = $wpdb->get_results(
            "SELECT * FROM `{$table}` WHERE status = 'active' ORDER BY priority DESC" // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        );

        if ( empty( $qa_pairs ) ) {
            return null;
        }

        $query_lower    = strtolower( trim( $query ) );
        $query_keywords = $this->extract_query_keywords( $query_lower );

        $best_match = null;
        $best_score = 0;

        foreach ( $qa_pairs as $qa ) {
            $question_lower    = strtolower( trim( $qa->question ) );
            $question_keywords = $this->extract_query_keywords( $question_lower );

            // Calculate similarity score
            $score = $this->calculate_similarity( $query_keywords, $question_keywords );

            // Also check for substring match
            if ( strpos( $question_lower, $query_lower ) !== false || strpos( $query_lower, $question_lower ) !== false ) {
                $score = max( $score, 0.8 );
            }

            if ( $score > $best_score && $score >= 0.5 ) {
                $best_score = $score;
                $best_match = $qa;
            }
        }

        if ( $best_match ) {
            // Return as a content-like object
            return (object) array(
                'id'              => $best_match->id,
                'post_id'         => null,
                'content_type'    => 'custom_qa',
                'title'           => $best_match->question,
                'chunk_text'      => $best_match->answer,
                'chunk_index'     => 0,
                'word_count'      => str_word_count( $best_match->answer ),
                'keywords'        => '',
                'status'          => 'active',
                'relevance_score' => $best_score * 10,
            );
        }

        return null;
    }

    /**
     * Calculate similarity between two sets of keywords.
     *
     * @param array $keywords1 First set of keywords.
     * @param array $keywords2 Second set of keywords.
     * @return float Similarity score between 0 and 1.
     */
    private function calculate_similarity( $keywords1, $keywords2 ) {
        if ( empty( $keywords1 ) || empty( $keywords2 ) ) {
            return 0;
        }

        $intersection = array_intersect( $keywords1, $keywords2 );
        $union        = array_unique( array_merge( $keywords1, $keywords2 ) );

        if ( empty( $union ) ) {
            return 0;
        }

        // Jaccard similarity
        return count( $intersection ) / count( $union );
    }

    /**
     * Extract keywords from a query string.
     *
     * @param string $query The query string.
     * @return array Array of keywords.
     */
    private function extract_query_keywords( $query ) {
        $stop_words = array(
            'a', 'an', 'the', 'is', 'it', 'to', 'in', 'for', 'on', 'with',
            'at', 'by', 'from', 'as', 'of', 'and', 'or', 'not', 'be', 'are',
            'was', 'were', 'have', 'has', 'had', 'do', 'does', 'did', 'will',
            'would', 'could', 'should', 'may', 'might', 'can', 'this', 'that',
            'what', 'which', 'who', 'when', 'where', 'why', 'how', 'your',
            'you', 'we', 'they', 'i', 'me', 'my',
        );

        $query    = preg_replace( '/[^\w\s]/', ' ', $query );
        $words    = preg_split( '/\s+/', $query );
        $keywords = array();

        foreach ( $words as $word ) {
            $word = trim( $word );
            if ( strlen( $word ) >= 3 && ! in_array( $word, $stop_words, true ) ) {
                $keywords[] = $word;
            }
        }

        return array_unique( $keywords );
    }

    /**
     * Get context formatted as a string for AI prompt.
     *
     * @param array $chunks Array of chunk objects.
     * @return string Formatted context string.
     */
    public function format_context( $chunks ) {
        if ( empty( $chunks ) ) {
            return '';
        }

        $context = '';
        foreach ( $chunks as $index => $chunk ) {
            $title = isset( $chunk->title ) ? $chunk->title : '';
            $text  = isset( $chunk->chunk_text ) ? $chunk->chunk_text : '';

            $context .= sprintf( "[Source %d: %s]\n%s\n\n", $index + 1, $title, $text );
        }

        return trim( $context );
    }
}
