<?php
/**
 * Content Model - Data access for training content table.
 *
 * @package WPAICB\Database
 */

namespace WPAICB\Database;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Content_Model {

    /**
     * Table name.
     *
     * @var string
     */
    private $table;

    /**
     * Constructor.
     */
    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'aicb_training_content';
    }

    /**
     * Insert a content chunk.
     *
     * @param array $data Content data.
     * @return int|false Insert ID or false on failure.
     */
    public function create( $data ) {
        global $wpdb;

        $defaults = array(
            'post_id'      => null,
            'content_type' => 'post',
            'title'        => '',
            'chunk_text'   => '',
            'chunk_index'  => 0,
            'word_count'   => 0,
            'keywords'     => '',
            'status'       => 'active',
            'created_at'   => current_time( 'mysql' ),
            'updated_at'   => current_time( 'mysql' ),
        );

        $data = wp_parse_args( $data, $defaults );

        $insert_data = array(
            'content_type' => sanitize_text_field( $data['content_type'] ),
            'title'        => sanitize_text_field( $data['title'] ),
            'chunk_text'   => wp_kses_post( $data['chunk_text'] ),
            'chunk_index'  => absint( $data['chunk_index'] ),
            'word_count'   => absint( $data['word_count'] ),
            'keywords'     => sanitize_text_field( $data['keywords'] ),
            'status'       => sanitize_text_field( $data['status'] ),
            'created_at'   => $data['created_at'],
            'updated_at'   => $data['updated_at'],
        );

        $formats = array( '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s' );

        if ( null !== $data['post_id'] ) {
            $insert_data['post_id'] = absint( $data['post_id'] );
            $formats[]              = '%d';
        }

        $result = $wpdb->insert( $this->table, $insert_data, $formats );

        if ( false === $result ) {
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Bulk insert content chunks.
     *
     * @param array $chunks Array of chunk data arrays.
     * @return int Number of inserted chunks.
     */
    public function bulk_create( $chunks ) {
        $inserted = 0;

        foreach ( $chunks as $chunk ) {
            $result = $this->create( $chunk );
            if ( false !== $result ) {
                $inserted++;
            }
        }

        return $inserted;
    }

    /**
     * Get content chunks by post ID.
     *
     * @param int $post_id Post ID.
     * @return array
     */
    public function get_by_post( $post_id ) {
        global $wpdb;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM `{$this->table}` WHERE post_id = %d AND status = 'active' ORDER BY chunk_index ASC",
                $post_id
            )
        );

        return $results ? $results : array();
    }

    /**
     * Delete content chunks by post ID.
     *
     * @param int $post_id Post ID.
     * @return bool
     */
    public function delete_by_post( $post_id ) {
        global $wpdb;

        $result = $wpdb->delete(
            $this->table,
            array( 'post_id' => $post_id ),
            array( '%d' )
        );

        return false !== $result;
    }

    /**
     * Search content chunks by keyword matching.
     *
     * @param string $query          Search query.
     * @param int    $limit          Max results.
     * @param array  $content_types  Content types to search.
     * @return array
     */
    public function search( $query, $limit = 5, $content_types = array() ) {
        global $wpdb;

        $keywords = $this->extract_keywords( $query );

        if ( empty( $keywords ) ) {
            return array();
        }

        $where  = array( "status = 'active'" );
        $values = array();

        // Build keyword matching conditions
        $keyword_conditions = array();
        foreach ( $keywords as $keyword ) {
            $keyword_conditions[] = "chunk_text LIKE %s";
            $values[]             = '%' . $wpdb->esc_like( $keyword ) . '%';
        }

        // Also search in keywords column
        foreach ( $keywords as $keyword ) {
            $keyword_conditions[] = "keywords LIKE %s";
            $values[]             = '%' . $wpdb->esc_like( $keyword ) . '%';
        }

        // Also match title
        foreach ( $keywords as $keyword ) {
            $keyword_conditions[] = "title LIKE %s";
            $values[]             = '%' . $wpdb->esc_like( $keyword ) . '%';
        }

        $where[] = '(' . implode( ' OR ', $keyword_conditions ) . ')';

        // Filter by content types
        if ( ! empty( $content_types ) ) {
            $type_placeholders = implode( ',', array_fill( 0, count( $content_types ), '%s' ) );
            $where[]           = "content_type IN ({$type_placeholders})";
            $values            = array_merge( $values, $content_types );
        }

        $where_clause = implode( ' AND ', $where );
        $values[]     = $limit;

        // Score results by relevance using parameterized LIKE conditions
        $score_parts  = array();
        $score_values = array();
        foreach ( $keywords as $keyword ) {
            $escaped = $wpdb->esc_like( $keyword );
            $score_parts[]  = "(CASE WHEN chunk_text LIKE %s THEN 2 ELSE 0 END)";
            $score_values[] = '%' . $escaped . '%';
            $score_parts[]  = "(CASE WHEN title LIKE %s THEN 3 ELSE 0 END)";
            $score_values[] = '%' . $escaped . '%';
            $score_parts[]  = "(CASE WHEN keywords LIKE %s THEN 1 ELSE 0 END)";
            $score_values[] = '%' . $escaped . '%';
        }
        $score_expression = implode( ' + ', $score_parts );

        // Merge score values before the where values and limit
        $all_values = array_merge( $score_values, $values );

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *, ({$score_expression}) AS relevance_score FROM `{$this->table}` WHERE {$where_clause} ORDER BY relevance_score DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL
                ...$all_values
            )
        );

        return $results ? $results : array();
    }

    /**
     * Get all indexed content with pagination.
     *
     * @param array $args Query arguments.
     * @return array
     */
    public function get_list( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'status'       => '',
            'content_type' => '',
            'per_page'     => 20,
            'page'         => 1,
        );

        $args = wp_parse_args( $args, $defaults );

        $where  = array( '1=1' );
        $values = array();

        if ( ! empty( $args['status'] ) ) {
            $where[]  = 'status = %s';
            $values[] = $args['status'];
        }

        if ( ! empty( $args['content_type'] ) ) {
            $where[]  = 'content_type = %s';
            $values[] = $args['content_type'];
        }

        $where_clause = implode( ' AND ', $where );

        $per_page = absint( $args['per_page'] );
        $offset   = ( absint( $args['page'] ) - 1 ) * $per_page;

        // Get grouped by post_id for summary view
        $count_query = "SELECT COUNT(DISTINCT COALESCE(post_id, id)) FROM `{$this->table}` WHERE {$where_clause}";
        if ( ! empty( $values ) ) {
            $total = (int) $wpdb->get_var( $wpdb->prepare( $count_query, ...$values ) ); // phpcs:ignore WordPress.DB.PreparedSQL
        } else {
            $total = (int) $wpdb->get_var( $count_query ); // phpcs:ignore WordPress.DB.PreparedSQL
        }

        $query_values   = $values;
        $query_values[] = $per_page;
        $query_values[] = $offset;

        $items_query = "SELECT post_id, content_type, title, COUNT(*) as chunk_count, SUM(word_count) as total_words, MIN(status) as status, MAX(updated_at) as last_updated
            FROM `{$this->table}`
            WHERE {$where_clause}
            GROUP BY post_id, content_type, title
            ORDER BY last_updated DESC
            LIMIT %d OFFSET %d";

        if ( ! empty( $values ) ) {
            $items = $wpdb->get_results( $wpdb->prepare( $items_query, ...$query_values ) ); // phpcs:ignore WordPress.DB.PreparedSQL
        } else {
            $items = $wpdb->get_results( $wpdb->prepare( $items_query, $per_page, $offset ) ); // phpcs:ignore WordPress.DB.PreparedSQL
        }

        return array(
            'items'    => $items ? $items : array(),
            'total'    => $total,
            'pages'    => ceil( $total / $per_page ),
            'page'     => absint( $args['page'] ),
            'per_page' => $per_page,
        );
    }

    /**
     * Get content statistics.
     *
     * @return array
     */
    public function get_stats() {
        global $wpdb;

        return array(
            'total_chunks'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$this->table}` WHERE status = 'active'" ), // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            'total_posts'      => (int) $wpdb->get_var( "SELECT COUNT(DISTINCT post_id) FROM `{$this->table}` WHERE status = 'active' AND post_id IS NOT NULL" ), // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            'total_words'      => (int) $wpdb->get_var( "SELECT COALESCE(SUM(word_count), 0) FROM `{$this->table}` WHERE status = 'active'" ), // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            'content_types'    => $wpdb->get_results( "SELECT content_type, COUNT(*) as count FROM `{$this->table}` WHERE status = 'active' GROUP BY content_type" ), // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            'last_indexed'     => $wpdb->get_var( "SELECT MAX(updated_at) FROM `{$this->table}`" ), // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        );
    }

    /**
     * Clear all training content.
     *
     * @return bool
     */
    public function clear_all() {
        global $wpdb;

        $result = $wpdb->query( "TRUNCATE TABLE `{$this->table}`" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

        return false !== $result;
    }

    /**
     * Extract keywords from a search query.
     *
     * @param string $query The search query.
     * @return array Array of keywords.
     */
    private function extract_keywords( $query ) {
        // Remove common stop words
        $stop_words = array(
            'a', 'an', 'the', 'is', 'it', 'to', 'in', 'for', 'on', 'with',
            'at', 'by', 'from', 'as', 'of', 'and', 'or', 'not', 'be', 'are',
            'was', 'were', 'been', 'being', 'have', 'has', 'had', 'do', 'does',
            'did', 'will', 'would', 'could', 'should', 'may', 'might', 'can',
            'this', 'that', 'these', 'those', 'i', 'me', 'my', 'we', 'you',
            'your', 'he', 'she', 'they', 'what', 'which', 'who', 'when',
            'where', 'why', 'how', 'all', 'each', 'every', 'both', 'few',
            'more', 'most', 'other', 'some', 'such', 'no', 'nor', 'only',
            'own', 'same', 'so', 'than', 'too', 'very', 'just', 'because',
            'but', 'if', 'about', 'up', 'out', 'then', 'there', 'here',
        );

        // Clean and split query
        $query    = strtolower( trim( $query ) );
        $query    = preg_replace( '/[^\w\s]/', ' ', $query );
        $words    = preg_split( '/\s+/', $query );
        $keywords = array();

        foreach ( $words as $word ) {
            $word = trim( $word );
            if ( strlen( $word ) >= 3 && ! in_array( $word, $stop_words, true ) ) {
                $keywords[] = $word;
            }
        }

        return array_unique( array_slice( $keywords, 0, 10 ) );
    }
}
