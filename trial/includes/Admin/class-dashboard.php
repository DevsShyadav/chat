<?php
/**
 * Dashboard - Admin dashboard page controller.
 *
 * @package WPAICB\Admin
 */

namespace WPAICB\Admin;

use WPAICB\Database\Conversation_Model;
use WPAICB\Database\Analytics_Model;
use WPAICB\Database\Content_Model;
use WPAICB\Database\Message_Model;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Dashboard {

    /**
     * Render the dashboard page.
     */
    public function render() {
        $conversation_model = new Conversation_Model();
        $analytics_model    = new Analytics_Model();
        $content_model      = new Content_Model();
        $message_model      = new Message_Model();

        $settings = Admin::get_settings();

        // Gather stats
        $conv_stats      = $conversation_model->get_stats( 'today' );
        $conv_stats_week = $conversation_model->get_stats( 'week' );
        $dashboard_stats = $analytics_model->get_dashboard_stats();
        $content_stats   = $content_model->get_stats();
        $resolution_rate = $analytics_model->get_resolution_rate( 'week' );
        $daily_chats     = $analytics_model->get_daily_stats( 'conversation_started', 7 );

        // Recent conversations
        $recent = $conversation_model->get_list( array(
            'per_page' => 5,
            'page'     => 1,
        ) );

        // Check configuration status
        $is_configured   = ! empty( $settings['openai_api_key'] ) || ! empty( $settings['gemini_api_key'] ) || ! empty( $settings['groq_api_key'] );
        $has_content     = $content_stats['total_chunks'] > 0;
        $onboarding_done = get_option( 'wpaicb_onboarding_complete', false );

        include WPAICB_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }
}
