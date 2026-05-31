<?php
/**
 * Database Migrator - Handles schema migrations.
 *
 * @package WPAICB\Database
 */

namespace WPAICB\Database;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Migrator {

    /**
     * Run pending migrations.
     */
    public function run() {
        $current_version = get_option( 'wpaicb_db_version', '0' );

        if ( version_compare( $current_version, '1.0.0', '<' ) ) {
            $this->migrate_to_1_0_0();
        }

        update_option( 'wpaicb_db_version', WPAICB_DB_VERSION );
    }

    /**
     * Migration for version 1.0.0.
     */
    private function migrate_to_1_0_0() {
        // Initial tables are created by Activator.
        // This method exists for future migrations.
    }

    /**
     * Check if a table exists.
     *
     * @param string $table_name Full table name including prefix.
     * @return bool
     */
    public function table_exists( $table_name ) {
        global $wpdb;

        $result = $wpdb->get_var(
            $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name )
        );

        return $result === $table_name;
    }

    /**
     * Get table status information.
     *
     * @return array
     */
    public function get_table_status() {
        global $wpdb;

        $tables = array(
            'conversations'    => $wpdb->prefix . 'aicb_conversations',
            'messages'         => $wpdb->prefix . 'aicb_messages',
            'training_content' => $wpdb->prefix . 'aicb_training_content',
            'custom_qa'        => $wpdb->prefix . 'aicb_custom_qa',
            'analytics'        => $wpdb->prefix . 'aicb_analytics',
        );

        $status = array();

        foreach ( $tables as $key => $table ) {
            $status[ $key ] = array(
                'exists' => $this->table_exists( $table ),
                'count'  => $this->table_exists( $table ) ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" ) : 0, // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
            );
        }

        return $status;
    }
}
