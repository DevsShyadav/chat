<?php
/**
 * Message Model - Data access for messages table.
 *
 * @package WPAICB\Database
 */

namespace WPAICB\Database;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Message_Model {

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
        $this->table = $wpdb->prefix . 'aicb_messages';
    }

    /**
     * Create a new message.
     *
     * @param array $data Message data.
     * @return int|false Insert ID or false on failure.
     */
    public function create( $data ) {
        global $wpdb;

        $defaults = array(
            'conversation_id'  => 0,
            'role'             => 'user',
            'content'          => '',
            'confidence_score' => null,
            'tokens_used'      => 0,
            'created_at'       => current_time( 'mysql' ),
        );

        $data = wp_parse_args( $data, $defaults );

        $insert_data = array(
            'conversation_id'  => absint( $data['conversation_id'] ),
            'role'             => sanitize_text_field( $data['role'] ),
            'content'          => wp_kses_post( $data['content'] ),
            'tokens_used'      => absint( $data['tokens_used'] ),
            'created_at'       => $data['created_at'],
        );

        $formats = array( '%d', '%s', '%s', '%d', '%s' );

        if ( null !== $data['confidence_score'] ) {
            $insert_data['confidence_score'] = floatval( $data['confidence_score'] );
            $formats[]                       = '%f';
        }

        $result = $wpdb->insert( $this->table, $insert_data, $formats );

        if ( false === $result ) {
            return false;
        }

        // Increment conversation message count
        $conversation_model = new Conversation_Model();
        $conversation_model->increment_message_count( $data['conversation_id'] );

        return $wpdb->insert_id;
    }

    /**
     * Get messages for a conversation.
     *
     * @param int   $conversation_id Conversation ID.
     * @param array $args            Query arguments.
     * @return array
     */
    public function get_by_conversation( $conversation_id, $args = array() ) {
        global $wpdb;

        $defaults = array(
            'limit'  => 50,
            'offset' => 0,
            'order'  => 'ASC',
        );

        $args  = wp_parse_args( $args, $defaults );
        $order = 'DESC' === strtoupper( $args['order'] ) ? 'DESC' : 'ASC';

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM `{$this->table}` WHERE conversation_id = %d ORDER BY created_at {$order} LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL
                $conversation_id,
                absint( $args['limit'] ),
                absint( $args['offset'] )
            )
        );

        return $results ? $results : array();
    }

    /**
     * Get message count for a conversation.
     *
     * @param int $conversation_id Conversation ID.
     * @return int
     */
    public function get_count( $conversation_id ) {
        global $wpdb;

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM `{$this->table}` WHERE conversation_id = %d",
                $conversation_id
            )
        );
    }

    /**
     * Get the last N messages for context.
     *
     * @param int $conversation_id Conversation ID.
     * @param int $limit           Number of messages to retrieve.
     * @return array
     */
    public function get_recent( $conversation_id, $limit = 10 ) {
        global $wpdb;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM (
                    SELECT * FROM `{$this->table}`
                    WHERE conversation_id = %d
                    ORDER BY created_at DESC
                    LIMIT %d
                ) AS recent ORDER BY created_at ASC",
                $conversation_id,
                $limit
            )
        );

        return $results ? $results : array();
    }

    /**
     * Delete messages by conversation ID.
     *
     * @param int $conversation_id Conversation ID.
     * @return bool
     */
    public function delete_by_conversation( $conversation_id ) {
        global $wpdb;

        $result = $wpdb->delete(
            $this->table,
            array( 'conversation_id' => $conversation_id ),
            array( '%d' )
        );

        return false !== $result;
    }

    /**
     * Get total tokens used in a period.
     *
     * @param string $period Period (today, week, month, all).
     * @return int
     */
    public function get_total_tokens( $period = 'today' ) {
        global $wpdb;

        $date_condition = $this->get_date_condition( $period );

        return (int) $wpdb->get_var(
            "SELECT COALESCE(SUM(tokens_used), 0) FROM `{$this->table}` WHERE {$date_condition}" // phpcs:ignore WordPress.DB.PreparedSQL
        );
    }

    /**
     * Get average confidence score.
     *
     * @param string $period Period.
     * @return float
     */
    public function get_avg_confidence( $period = 'all' ) {
        global $wpdb;

        $date_condition = $this->get_date_condition( $period );

        $result = $wpdb->get_var(
            "SELECT AVG(confidence_score) FROM `{$this->table}` WHERE confidence_score IS NOT NULL AND role = 'assistant' AND {$date_condition}" // phpcs:ignore WordPress.DB.PreparedSQL
        );

        return $result ? round( (float) $result, 2 ) : 0;
    }

    /**
     * Get date condition for queries.
     *
     * @param string $period Period identifier.
     * @return string SQL condition.
     */
    private function get_date_condition( $period ) {
        switch ( $period ) {
            case 'today':
                return "DATE(created_at) = CURDATE()";
            case 'week':
                return "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            case 'month':
                return "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            case 'all':
            default:
                return '1=1';
        }
    }
}
