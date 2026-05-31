<?php
/**
 * Conversation Model - Data access for conversations table.
 *
 * @package WPAICB\Database
 */

namespace WPAICB\Database;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Conversation_Model {

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
        $this->table = $wpdb->prefix . 'aicb_conversations';
    }

    /**
     * Create a new conversation.
     *
     * @param array $data Conversation data.
     * @return int|false Insert ID or false on failure.
     */
    public function create( $data ) {
        global $wpdb;

        $defaults = array(
            'session_id'    => wp_generate_uuid4(),
            'visitor_ip'    => $this->get_visitor_ip(),
            'visitor_name'  => '',
            'visitor_email' => '',
            'status'        => 'active',
            'message_count' => 0,
            'page_url'      => '',
            'created_at'    => current_time( 'mysql' ),
            'updated_at'    => current_time( 'mysql' ),
        );

        $data = wp_parse_args( $data, $defaults );

        $result = $wpdb->insert(
            $this->table,
            array(
                'session_id'    => sanitize_text_field( $data['session_id'] ),
                'visitor_ip'    => sanitize_text_field( $data['visitor_ip'] ),
                'visitor_name'  => sanitize_text_field( $data['visitor_name'] ),
                'visitor_email' => sanitize_email( $data['visitor_email'] ),
                'status'        => sanitize_text_field( $data['status'] ),
                'message_count' => absint( $data['message_count'] ),
                'page_url'      => esc_url_raw( $data['page_url'] ),
                'created_at'    => $data['created_at'],
                'updated_at'    => $data['updated_at'],
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
        );

        if ( false === $result ) {
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Get a conversation by ID.
     *
     * @param int $id Conversation ID.
     * @return object|null
     */
    public function get( $id ) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM `{$this->table}` WHERE id = %d",
                $id
            )
        );
    }

    /**
     * Get a conversation by session ID.
     *
     * @param string $session_id Session ID.
     * @return object|null
     */
    public function get_by_session( $session_id ) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM `{$this->table}` WHERE session_id = %s",
                $session_id
            )
        );
    }

    /**
     * Update a conversation.
     *
     * @param int   $id   Conversation ID.
     * @param array $data Data to update.
     * @return bool
     */
    public function update( $id, $data ) {
        global $wpdb;

        $data['updated_at'] = current_time( 'mysql' );

        $update_data   = array();
        $update_format = array();

        if ( isset( $data['visitor_name'] ) ) {
            $update_data['visitor_name'] = sanitize_text_field( $data['visitor_name'] );
            $update_format[]             = '%s';
        }

        if ( isset( $data['visitor_email'] ) ) {
            $update_data['visitor_email'] = sanitize_email( $data['visitor_email'] );
            $update_format[]              = '%s';
        }

        if ( isset( $data['status'] ) ) {
            $update_data['status'] = sanitize_text_field( $data['status'] );
            $update_format[]       = '%s';
        }

        if ( isset( $data['satisfaction_rating'] ) ) {
            $update_data['satisfaction_rating'] = absint( $data['satisfaction_rating'] );
            $update_format[]                    = '%d';
        }

        if ( isset( $data['message_count'] ) ) {
            $update_data['message_count'] = absint( $data['message_count'] );
            $update_format[]              = '%d';
        }

        $update_data['updated_at'] = $data['updated_at'];
        $update_format[]           = '%s';

        $result = $wpdb->update(
            $this->table,
            $update_data,
            array( 'id' => $id ),
            $update_format,
            array( '%d' )
        );

        return false !== $result;
    }

    /**
     * Increment message count.
     *
     * @param int $id Conversation ID.
     * @return bool
     */
    public function increment_message_count( $id ) {
        global $wpdb;

        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE `{$this->table}` SET message_count = message_count + 1, updated_at = %s WHERE id = %d",
                current_time( 'mysql' ),
                $id
            )
        );

        return false !== $result;
    }

    /**
     * Get conversations with pagination.
     *
     * @param array $args Query arguments.
     * @return array
     */
    public function get_list( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'status'   => '',
            'search'   => '',
            'per_page' => 20,
            'page'     => 1,
            'orderby'  => 'created_at',
            'order'    => 'DESC',
        );

        $args = wp_parse_args( $args, $defaults );

        $where  = array( '1=1' );
        $values = array();

        if ( ! empty( $args['status'] ) ) {
            $where[]  = 'status = %s';
            $values[] = $args['status'];
        }

        if ( ! empty( $args['search'] ) ) {
            $where[]  = '(visitor_email LIKE %s OR visitor_name LIKE %s OR session_id LIKE %s)';
            $search   = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $values[] = $search;
            $values[] = $search;
            $values[] = $search;
        }

        $where_clause = implode( ' AND ', $where );

        $allowed_orderby = array( 'created_at', 'updated_at', 'message_count', 'status' );
        $orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
        $order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

        $per_page = absint( $args['per_page'] );
        $offset   = ( absint( $args['page'] ) - 1 ) * $per_page;

        // Get total count
        if ( ! empty( $values ) ) {
            $total = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM `{$this->table}` WHERE {$where_clause}", // phpcs:ignore WordPress.DB.PreparedSQL
                    ...$values
                )
            );
        } else {
            $total = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM `{$this->table}` WHERE {$where_clause}" // phpcs:ignore WordPress.DB.PreparedSQL
            );
        }

        // Get items
        $query_values   = $values;
        $query_values[] = $per_page;
        $query_values[] = $offset;

        if ( ! empty( $values ) ) {
            $items = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM `{$this->table}` WHERE {$where_clause} ORDER BY `{$orderby}` {$order} LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL
                    ...$query_values
                )
            );
        } else {
            $items = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM `{$this->table}` WHERE {$where_clause} ORDER BY `{$orderby}` {$order} LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL
                    $per_page,
                    $offset
                )
            );
        }

        return array(
            'items'      => $items ? $items : array(),
            'total'      => $total,
            'pages'      => ceil( $total / $per_page ),
            'page'       => absint( $args['page'] ),
            'per_page'   => $per_page,
        );
    }

    /**
     * Delete a conversation and its messages.
     *
     * @param int $id Conversation ID.
     * @return bool
     */
    public function delete( $id ) {
        global $wpdb;

        // Delete messages first
        $wpdb->delete(
            $wpdb->prefix . 'aicb_messages',
            array( 'conversation_id' => $id ),
            array( '%d' )
        );

        // Delete conversation
        $result = $wpdb->delete(
            $this->table,
            array( 'id' => $id ),
            array( '%d' )
        );

        return false !== $result;
    }

    /**
     * Delete old conversations.
     *
     * @param int $days Number of days to keep.
     * @return int Number of deleted conversations.
     */
    public function cleanup_old( $days = 90 ) {
        global $wpdb;

        $cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        // Get IDs to delete
        $ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT id FROM `{$this->table}` WHERE created_at < %s AND status = 'closed'",
                $cutoff
            )
        );

        if ( empty( $ids ) ) {
            return 0;
        }

        $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

        // Delete messages
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM `{$wpdb->prefix}aicb_messages` WHERE conversation_id IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL
                ...$ids
            )
        );

        // Delete conversations
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM `{$this->table}` WHERE id IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL
                ...$ids
            )
        );

        return count( $ids );
    }

    /**
     * Get conversation statistics.
     *
     * @param string $period Period (today, week, month, all).
     * @return array
     */
    public function get_stats( $period = 'today' ) {
        global $wpdb;

        $date_condition = $this->get_date_condition( $period );

        $stats = array(
            'total'     => 0,
            'active'    => 0,
            'closed'    => 0,
            'escalated' => 0,
            'avg_messages' => 0,
            'avg_rating'   => 0,
        );

        $stats['total'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM `{$this->table}` WHERE {$date_condition}" // phpcs:ignore WordPress.DB.PreparedSQL
        );

        $stats['active'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM `{$this->table}` WHERE status = 'active' AND {$date_condition}" // phpcs:ignore WordPress.DB.PreparedSQL
        );

        $stats['closed'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM `{$this->table}` WHERE status = 'closed' AND {$date_condition}" // phpcs:ignore WordPress.DB.PreparedSQL
        );

        $stats['escalated'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM `{$this->table}` WHERE status = 'escalated' AND {$date_condition}" // phpcs:ignore WordPress.DB.PreparedSQL
        );

        $stats['avg_messages'] = (float) $wpdb->get_var(
            "SELECT AVG(message_count) FROM `{$this->table}` WHERE {$date_condition}" // phpcs:ignore WordPress.DB.PreparedSQL
        );

        $stats['avg_rating'] = (float) $wpdb->get_var(
            "SELECT AVG(satisfaction_rating) FROM `{$this->table}` WHERE satisfaction_rating IS NOT NULL AND {$date_condition}" // phpcs:ignore WordPress.DB.PreparedSQL
        );

        return $stats;
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

    /**
     * Get the visitor's IP address.
     *
     * @return string
     */
    private function get_visitor_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        );

        foreach ( $ip_keys as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
                // Handle comma-separated IPs (X-Forwarded-For)
                if ( strpos( $ip, ',' ) !== false ) {
                    $ip = trim( explode( ',', $ip )[0] );
                }
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }
}
