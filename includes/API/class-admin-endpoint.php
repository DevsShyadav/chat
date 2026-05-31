<?php
/**
 * Admin Endpoint - REST API for admin operations.
 *
 * @package WPAICB\API
 */

namespace WPAICB\API;

use WPAICB\Admin\Admin;
use WPAICB\AI\Provider_Factory;
use WPAICB\Database\Conversation_Model;
use WPAICB\Database\Analytics_Model;
use WPAICB\Security\Sanitizer;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin_Endpoint {

    /**
     * API namespace.
     *
     * @var string
     */
    private $namespace = 'wpaicb/v1';

    /**
     * Register admin routes.
     */
    public function register() {
        // Save settings
        register_rest_route( $this->namespace, '/admin/settings', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'save_settings' ),
            'permission_callback' => array( $this, 'admin_permission_check' ),
        ) );

        // Get settings
        register_rest_route( $this->namespace, '/admin/settings', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_settings' ),
            'permission_callback' => array( $this, 'admin_permission_check' ),
        ) );

        // Test AI connection
        register_rest_route( $this->namespace, '/admin/test-connection', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'test_connection' ),
            'permission_callback' => array( $this, 'admin_permission_check' ),
        ) );

        // Get dashboard stats
        register_rest_route( $this->namespace, '/admin/stats', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_stats' ),
            'permission_callback' => array( $this, 'admin_permission_check' ),
        ) );

        // Delete conversation
        register_rest_route( $this->namespace, '/admin/conversations/(?P<id>\d+)', array(
            'methods'             => 'DELETE',
            'callback'            => array( $this, 'delete_conversation' ),
            'permission_callback' => array( $this, 'admin_permission_check' ),
            'args'                => array(
                'id' => array(
                    'required'          => true,
                    'type'              => 'integer',
                    'validate_callback' => function ( $value ) {
                        return is_numeric( $value ) && $value > 0;
                    },
                ),
            ),
        ) );

        // Add custom Q&A
        register_rest_route( $this->namespace, '/admin/qa', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'add_qa' ),
            'permission_callback' => array( $this, 'admin_permission_check' ),
            'args'                => array(
                'question' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ),
                'answer' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ),
            ),
        ) );

        // Delete custom Q&A
        register_rest_route( $this->namespace, '/admin/qa/(?P<id>\d+)', array(
            'methods'             => 'DELETE',
            'callback'            => array( $this, 'delete_qa' ),
            'permission_callback' => array( $this, 'admin_permission_check' ),
            'args'                => array(
                'id' => array(
                    'required'          => true,
                    'type'              => 'integer',
                    'validate_callback' => function ( $value ) {
                        return is_numeric( $value ) && $value > 0;
                    },
                ),
            ),
        ) );
    }

    /**
     * Save settings handler.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function save_settings( $request ) {
        $raw_settings = $request->get_json_params();

        if ( empty( $raw_settings ) ) {
            return new \WP_REST_Response( array(
                'success' => false,
                'message' => __( 'No settings provided.', 'wp-ai-chatbot' ),
            ), 400 );
        }

        // Sanitize all settings
        $sanitized = Sanitizer::sanitize_settings( $raw_settings );

        // Update settings
        Admin::update_settings( $sanitized );

        return new \WP_REST_Response( array(
            'success' => true,
            'message' => __( 'Settings saved successfully.', 'wp-ai-chatbot' ),
        ), 200 );
    }

    /**
     * Get settings handler.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_settings( $request ) {
        $settings = Admin::get_settings();

        // Mask API keys for security
        $masked = $settings;
        $key_fields = array( 'openai_api_key', 'gemini_api_key', 'groq_api_key' );
        foreach ( $key_fields as $field ) {
            if ( ! empty( $masked[ $field ] ) ) {
                $masked[ $field ] = str_repeat( '*', max( 0, strlen( $masked[ $field ] ) - 8 ) ) . substr( $masked[ $field ], -8 );
            }
        }

        return new \WP_REST_Response( array(
            'success'  => true,
            'settings' => $masked,
        ), 200 );
    }

    /**
     * Test AI connection handler.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function test_connection( $request ) {
        $provider = Provider_Factory::create();

        if ( ! $provider ) {
            return new \WP_REST_Response( array(
                'success' => false,
                'message' => __( 'No AI provider configured.', 'wp-ai-chatbot' ),
            ), 400 );
        }

        $result = $provider->test_connection();

        $status = $result['success'] ? 200 : 400;
        return new \WP_REST_Response( $result, $status );
    }

    /**
     * Get dashboard stats handler.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_stats( $request ) {
        $analytics          = new Analytics_Model();
        $conversation_model = new Conversation_Model();

        return new \WP_REST_Response( array(
            'success' => true,
            'stats'   => array(
                'dashboard'      => $analytics->get_dashboard_stats(),
                'resolution_rate' => $analytics->get_resolution_rate( 'week' ),
                'conversations'  => $conversation_model->get_stats( 'today' ),
            ),
        ), 200 );
    }

    /**
     * Delete conversation handler.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function delete_conversation( $request ) {
        $id = (int) $request->get_param( 'id' );

        $conversation_model = new Conversation_Model();
        $result             = $conversation_model->delete( $id );

        if ( $result ) {
            return new \WP_REST_Response( array(
                'success' => true,
                'message' => __( 'Conversation deleted.', 'wp-ai-chatbot' ),
            ), 200 );
        }

        return new \WP_REST_Response( array(
            'success' => false,
            'message' => __( 'Failed to delete conversation.', 'wp-ai-chatbot' ),
        ), 500 );
    }

    /**
     * Add custom Q&A handler.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function add_qa( $request ) {
        global $wpdb;

        $question = $request->get_param( 'question' );
        $answer   = $request->get_param( 'answer' );

        if ( empty( $question ) || empty( $answer ) ) {
            return new \WP_REST_Response( array(
                'success' => false,
                'message' => __( 'Question and answer are required.', 'wp-ai-chatbot' ),
            ), 400 );
        }

        $result = $wpdb->insert(
            $wpdb->prefix . 'aicb_custom_qa',
            array(
                'question'   => $question,
                'answer'     => $answer,
                'priority'   => 0,
                'status'     => 'active',
                'created_at' => current_time( 'mysql' ),
                'updated_at' => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%d', '%s', '%s', '%s' )
        );

        if ( false === $result ) {
            return new \WP_REST_Response( array(
                'success' => false,
                'message' => __( 'Failed to save Q&A.', 'wp-ai-chatbot' ),
            ), 500 );
        }

        return new \WP_REST_Response( array(
            'success' => true,
            'message' => __( 'Q&A pair added successfully.', 'wp-ai-chatbot' ),
            'id'      => $wpdb->insert_id,
        ), 201 );
    }

    /**
     * Delete custom Q&A handler.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function delete_qa( $request ) {
        global $wpdb;

        $id = (int) $request->get_param( 'id' );

        $result = $wpdb->delete(
            $wpdb->prefix . 'aicb_custom_qa',
            array( 'id' => $id ),
            array( '%d' )
        );

        if ( false === $result ) {
            return new \WP_REST_Response( array(
                'success' => false,
                'message' => __( 'Failed to delete Q&A.', 'wp-ai-chatbot' ),
            ), 500 );
        }

        return new \WP_REST_Response( array(
            'success' => true,
            'message' => __( 'Q&A pair deleted.', 'wp-ai-chatbot' ),
        ), 200 );
    }

    /**
     * Admin permission check.
     *
     * @return bool
     */
    public function admin_permission_check() {
        return current_user_can( 'manage_options' );
    }
}
