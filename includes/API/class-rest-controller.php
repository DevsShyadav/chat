<?php
/**
 * REST Controller - Registers and manages all REST API routes.
 *
 * @package WPAICB\API
 */

namespace WPAICB\API;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Rest_Controller {

    /**
     * API namespace.
     *
     * @var string
     */
    const NAMESPACE = 'wpaicb/v1';

    /**
     * Register all REST API routes.
     */
    public function register_routes() {
        $chat_endpoint     = new Chat_Endpoint();
        $admin_endpoint    = new Admin_Endpoint();
        $training_endpoint = new Training_Endpoint();

        $chat_endpoint->register();
        $admin_endpoint->register();
        $training_endpoint->register();
    }
}
