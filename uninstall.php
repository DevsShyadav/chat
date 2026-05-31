<?php
/**
 * Uninstall handler - Removes all plugin data.
 *
 * @package WPAICB
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Remove all plugin options
$options = array(
    'wpaicb_settings',
    'wpaicb_db_version',
    'wpaicb_activated',
    'wpaicb_onboarding_complete',
    'wpaicb_encryption_key',
    'wpaicb_last_index_run',
);

foreach ( $options as $option ) {
    delete_option( $option );
}

// Remove database tables
$tables = array(
    $wpdb->prefix . 'aicb_conversations',
    $wpdb->prefix . 'aicb_messages',
    $wpdb->prefix . 'aicb_training_content',
    $wpdb->prefix . 'aicb_custom_qa',
    $wpdb->prefix . 'aicb_analytics',
);

foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

// Clear any transients
$wpdb->query(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wpaicb_%' OR option_name LIKE '_transient_timeout_wpaicb_%'"
); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

// Clear scheduled events
wp_clear_scheduled_hook( 'wpaicb_index_content' );
wp_clear_scheduled_hook( 'wpaicb_cleanup_old_conversations' );
