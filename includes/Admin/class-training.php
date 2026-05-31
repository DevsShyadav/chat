<?php
/**
 * Training - Admin training management controller.
 *
 * @package WPAICB\Admin
 */

namespace WPAICB\Admin;

use WPAICB\Database\Content_Model;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Training {

    /**
     * Render the training page.
     */
    public function render() {
        $content_model = new Content_Model();
        $settings      = Admin::get_settings();

        // Get content stats
        $content_stats = $content_model->get_stats();

        // Get indexed content list
        $current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
        $content_list = $content_model->get_list( array(
            'per_page' => 20,
            'page'     => $current_page,
        ) );

        // Get available content types
        $post_types = get_post_types( array( 'public' => true ), 'objects' );
        $selected_types = isset( $settings['content_types'] ) ? $settings['content_types'] : array( 'post', 'page' );

        // Get custom Q&A pairs
        global $wpdb;
        $custom_qa = $wpdb->get_results(
            "SELECT * FROM `{$wpdb->prefix}aicb_custom_qa` ORDER BY priority DESC, created_at DESC" // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        );

        include WPAICB_PLUGIN_DIR . 'templates/admin/training.php';
    }
}
