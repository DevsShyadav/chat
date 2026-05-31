<?php
/**
 * Chat Endpoint - Public REST API for chat interactions.
 *
 * @package WPAICB\API
 */

namespace WPAICB\API;

use WPAICB\Chat\Chat_Controller;
use WPAICB\Security\Sanitizer;
use WPAICB\Database\Analytics_Model;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Chat_Endpoint {

    /**
     * API namespace.
     *
     * @var string
     */
    private $namespace = 'wpaicb/v1';

    /**
     * Register chat routes.
     */
    public function register() {
        // Send message
        register_rest_route( $this->namespace, '/chat/message', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'send_message' ),
            'permission_callback' => array( $this, 'public_permission_check' ),
            'args'                => array(
                'message' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => array( 'WPAICB\Security\Sanitizer', 'sanitize_message' ),
                    'validate_callback' => function ( $value ) {
                        return ! empty( trim( $value ) ) && strlen( $value ) <= 1000;
                    },
                ),
                'session_id' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'page_url' => array(
                    'required' => false,
                    'type'     => 'string',
                    'default'  => '',
                    'sanitize_callback' => 'esc_url_raw',
                ),
            ),
        ) );

        // Submit email (fallback)
        register_rest_route( $this->namespace, '/chat/email', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'submit_email' ),
            'permission_callback' => array( $this, 'public_permission_check' ),
            'args'                => array(
                'session_id' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'email' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'validate_callback' => function ( $value ) {
                        return is_email( $value );
                    },
                    'sanitize_callback' => 'sanitize_email',
                ),
                'name' => array(
                    'required'          => false,
                    'type'              => 'string',
                    'default'           => '',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ) );

        // Submit rating
        register_rest_route( $this->namespace, '/chat/rating', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'submit_rating' ),
            'permission_callback' => array( $this, 'public_permission_check' ),
            'args'                => array(
                'session_id' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'rating' => array(
                    'required'          => true,
                    'type'              => 'integer',
                    'validate_callback' => function ( $value ) {
                        return is_numeric( $value ) && $value >= 1 && $value <= 5;
                    },
                ),
            ),
        ) );

        // New conversation
        register_rest_route( $this->namespace, '/chat/new', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'new_conversation' ),
            'permission_callback' => array( $this, 'public_permission_check' ),
            'args'                => array(
                'session_id' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ) );

        // Widget opened event
        register_rest_route( $this->namespace, '/chat/opened', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'widget_opened' ),
            'permission_callback' => array( $this, 'public_permission_check' ),
            'args'                => array(
                'session_id' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ) );
    }

    /**
     * Send message handler.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function send_message( $request ) {
        $message    = $request->get_param( 'message' );
        $session_id = $request->get_param( 'session_id' );
        $page_url   = $request->get_param( 'page_url' );

        $controller = new Chat_Controller();
        $result     = $controller->process_message( $message, $session_id, $page_url );

        if ( ! $result['success'] ) {
            $status_code = 'rate_limited' === $result['error'] ? 429 : 500;
            return new \WP_REST_Response( $result, $status_code );
        }

        return new \WP_REST_Response( $result, 200 );
    }

    /**
     * Submit email handler.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function submit_email( $request ) {
        $session_id = $request->get_param( 'session_id' );
        $email      = $request->get_param( 'email' );
        $name       = $request->get_param( 'name' );

        $controller = new Chat_Controller();
        $result     = $controller->submit_email( $session_id, $email, $name );

        $status = $result['success'] ? 200 : 400;
        return new \WP_REST_Response( $result, $status );
    }

    /**
     * Submit rating handler.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function submit_rating( $request ) {
        $session_id = $request->get_param( 'session_id' );
        $rating     = (int) $request->get_param( 'rating' );

        $controller = new Chat_Controller();
        $result     = $controller->submit_rating( $session_id, $rating );

        $status = $result['success'] ? 200 : 400;
        return new \WP_REST_Response( $result, $status );
    }

    /**
     * New conversation handler.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function new_conversation( $request ) {
        $session_id = $request->get_param( 'session_id' );

        $controller = new Chat_Controller();
        $result     = $controller->new_conversation( $session_id );

        return new \WP_REST_Response( $result, 200 );
    }

    /**
     * Widget opened event handler.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function widget_opened( $request ) {
        $session_id = $request->get_param( 'session_id' );

        $analytics = new Analytics_Model();
        $analytics->record( 'widget_opened', null, $session_id );

        return new \WP_REST_Response( array( 'success' => true ), 200 );
    }

    /**
     * Public permission check - allows unauthenticated access.
     *
     * @return bool
     */
    public function public_permission_check() {
        return true;
    }
}
