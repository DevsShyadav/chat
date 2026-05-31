<?php
/**
 * Plugin Deactivator - Handles deactivation logic.
 *
 * @package WPAICB
 */

namespace WPAICB;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Deactivator {

    /**
     * Run deactivation routines.
     */
    public function deactivate() {
        $this->clear_scheduled_events();
        flush_rewrite_rules();
    }

    /**
     * Clear all scheduled cron events.
     */
    private function clear_scheduled_events() {
        $events = array(
            'wpaicb_index_content',
            'wpaicb_cleanup_old_conversations',
        );

        foreach ( $events as $event ) {
            $timestamp = wp_next_scheduled( $event );
            if ( $timestamp ) {
                wp_unschedule_event( $timestamp, $event );
            }
        }
    }
}
