<?php
/**
 * Analytics Model - Data access for analytics table.
 *
 * @package WPAICB\Database
 */

namespace WPAICB\Database;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Analytics_Model {

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
        $this->table = $wpdb->prefix . 'aicb_analytics';
    }

    /**
     * Record an analytics event.
     *
     * @param string $event_type Event type.
     * @param mixed  $event_data Event data.
     * @param string $session_id Session ID.
     * @return int|false
     */
    public function record( $event_type, $event_data = null, $session_id = null ) {
        global $wpdb;

        $data = array(
            'event_type' => sanitize_text_field( $event_type ),
            'event_data' => is_array( $event_data ) || is_object( $event_data ) ? wp_json_encode( $event_data ) : sanitize_text_field( (string) $event_data ),
            'created_at' => current_time( 'mysql' ),
        );

        $formats = array( '%s', '%s', '%s' );

        if ( null !== $session_id ) {
            $data['session_id'] = sanitize_text_field( $session_id );
            $formats[]          = '%s';
        }

        $result = $wpdb->insert( $this->table, $data, $formats );

        if ( false === $result ) {
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Get event count by type.
     *
     * @param string $event_type Event type.
     * @param string $period     Period.
     * @return int
     */
    public function get_event_count( $event_type, $period = 'all' ) {
        global $wpdb;

        $date_condition = $this->get_date_condition( $period );

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM `{$this->table}` WHERE event_type = %s AND {$date_condition}", // phpcs:ignore WordPress.DB.PreparedSQL
                $event_type
            )
        );
    }

    /**
     * Get daily stats for a period.
     *
     * @param string $event_type Event type.
     * @param int    $days       Number of days.
     * @return array
     */
    public function get_daily_stats( $event_type, $days = 30 ) {
        global $wpdb;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE(created_at) as date, COUNT(*) as count
                FROM `{$this->table}`
                WHERE event_type = %s AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC",
                $event_type,
                $days
            )
        );

        // Fill in missing dates with zero
        $daily_data = array();
        $start_date = new \DateTime( "-{$days} days" );
        $end_date   = new \DateTime();

        $existing = array();
        if ( $results ) {
            foreach ( $results as $row ) {
                $existing[ $row->date ] = (int) $row->count;
            }
        }

        $current = clone $start_date;
        while ( $current <= $end_date ) {
            $date_str    = $current->format( 'Y-m-d' );
            $daily_data[] = array(
                'date'  => $date_str,
                'count' => isset( $existing[ $date_str ] ) ? $existing[ $date_str ] : 0,
            );
            $current->modify( '+1 day' );
        }

        return $daily_data;
    }

    /**
     * Get popular questions (most asked topics).
     *
     * @param int    $limit  Max results.
     * @param string $period Period.
     * @return array
     */
    public function get_popular_questions( $limit = 10, $period = 'month' ) {
        global $wpdb;

        $date_condition = $this->get_date_condition( $period );

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT event_data, COUNT(*) as count
                FROM `{$this->table}`
                WHERE event_type = 'question_asked' AND {$date_condition}
                GROUP BY event_data
                ORDER BY count DESC
                LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL
                $limit
            )
        );

        return $results ? $results : array();
    }

    /**
     * Get unanswered questions.
     *
     * @param int    $limit  Max results.
     * @param string $period Period.
     * @return array
     */
    public function get_unanswered_questions( $limit = 10, $period = 'month' ) {
        global $wpdb;

        $date_condition = $this->get_date_condition( $period );

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT event_data, COUNT(*) as count
                FROM `{$this->table}`
                WHERE event_type = 'fallback_triggered' AND {$date_condition}
                GROUP BY event_data
                ORDER BY count DESC
                LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL
                $limit
            )
        );

        return $results ? $results : array();
    }

    /**
     * Get comprehensive dashboard stats.
     *
     * @return array
     */
    public function get_dashboard_stats() {
        return array(
            'conversations_today'  => $this->get_event_count( 'conversation_started', 'today' ),
            'conversations_week'   => $this->get_event_count( 'conversation_started', 'week' ),
            'conversations_month'  => $this->get_event_count( 'conversation_started', 'month' ),
            'messages_today'       => $this->get_event_count( 'message_sent', 'today' ),
            'messages_week'        => $this->get_event_count( 'message_sent', 'week' ),
            'fallbacks_today'      => $this->get_event_count( 'fallback_triggered', 'today' ),
            'fallbacks_week'       => $this->get_event_count( 'fallback_triggered', 'week' ),
            'emails_captured'      => $this->get_event_count( 'email_captured', 'month' ),
            'satisfaction_given'   => $this->get_event_count( 'rating_given', 'month' ),
            'widget_opens_today'   => $this->get_event_count( 'widget_opened', 'today' ),
            'widget_opens_week'    => $this->get_event_count( 'widget_opened', 'week' ),
        );
    }

    /**
     * Get resolution rate.
     *
     * @param string $period Period.
     * @return float
     */
    public function get_resolution_rate( $period = 'month' ) {
        $total_conversations = $this->get_event_count( 'conversation_started', $period );
        $fallbacks           = $this->get_event_count( 'fallback_triggered', $period );

        if ( 0 === $total_conversations ) {
            return 0;
        }

        return round( ( ( $total_conversations - $fallbacks ) / $total_conversations ) * 100, 1 );
    }

    /**
     * Cleanup old analytics data.
     *
     * @param int $days Days to keep.
     * @return int Number of deleted records.
     */
    public function cleanup_old( $days = 90 ) {
        global $wpdb;

        $cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM `{$this->table}` WHERE created_at < %s",
                $cutoff
            )
        );

        return $result ? $result : 0;
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
