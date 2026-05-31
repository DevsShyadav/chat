<?php
/**
 * Conversations - Admin conversations viewer controller.
 *
 * @package WPAICB\Admin
 */

namespace WPAICB\Admin;

use WPAICB\Database\Conversation_Model;
use WPAICB\Database\Message_Model;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Conversations {

    /**
     * Render the conversations page.
     */
    public function render() {
        $conversation_model = new Conversation_Model();
        $message_model      = new Message_Model();

        // Get filter parameters
        $current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
        $status       = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
        $search       = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

        // Get conversations
        $conversations = $conversation_model->get_list( array(
            'status'   => $status,
            'search'   => $search,
            'per_page' => 20,
            'page'     => $current_page,
        ) );

        // Get conversation detail if viewing one
        $viewing_conversation = null;
        $conversation_messages = array();

        if ( isset( $_GET['conversation_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            $conv_id              = absint( $_GET['conversation_id'] ); // phpcs:ignore WordPress.Security.NonceVerification
            $viewing_conversation = $conversation_model->get( $conv_id );

            if ( $viewing_conversation ) {
                $conversation_messages = $message_model->get_by_conversation( $conv_id );
            }
        }

        // Status counts for filters
        $all_count       = $conversation_model->get_stats( 'all' )['total'];
        $active_count    = $conversation_model->get_stats( 'all' )['active'];
        $closed_count    = $conversation_model->get_stats( 'all' )['closed'];
        $escalated_count = $conversation_model->get_stats( 'all' )['escalated'];

        include WPAICB_PLUGIN_DIR . 'templates/admin/conversations.php';
    }
}
