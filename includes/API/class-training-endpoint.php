<?php
/**
 * Training Endpoint - REST API for content training operations.
 *
 * @package WPAICB\API
 */

namespace WPAICB\API;

use WPAICB\Content\Indexer;
use WPAICB\Database\Content_Model;
use WPAICB\Admin\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Training_Endpoint {

    /**
     * API namespace.
     *
     * @var string
     */
    private $namespace = 'wpaicb/v1';

    /**
     * Register training routes.
     */
    public function register() {
        // Index all content
        register_rest_route( $this->namespace, '/training/index', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'index_all' ),
            'permission_callback' => array( $this, 'admin_permission_check' ),
        ) );

        // Index single post
        register_rest_route( $this->namespace, '/training/index/(?P<post_id>\d+)', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'index_post' ),
            'permission_callback' => array( $this, 'admin_permission_check' ),
            'args'                => array(
                'post_id' => array(
                    'required'          => true,
                    'type'              => 'integer',
                    'validate_callback' => function ( $value ) {
                        return is_numeric( $value ) && $value > 0;
                    },
                ),
            ),
        ) );

        // Get training status
        register_rest_route( $this->namespace, '/training/status', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_status' ),
            'permission_callback' => array( $this, 'admin_permission_check' ),
        ) );

        // Clear all training data
        register_rest_route( $this->namespace, '/training/clear', array(
            'methods'             => 'DELETE',
            'callback'            => array( $this, 'clear_all' ),
            'permission_callback' => array( $this, 'admin_permission_check' ),
        ) );

        // Update content types
        register_rest_route( $this->namespace, '/training/content-types', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'update_content_types' ),
            'permission_callback' => array( $this, 'admin_permission_check' ),
            'args'                => array(
                'content_types' => array(
                    'required' => true,
                    'type'     => 'array',
                ),
            ),
        ) );
    }

    /**
     * Index all content handler.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function index_all( $request ) {
        // Increase time limit for large sites
        if ( function_exists( 'set_time_limit' ) ) {
            set_time_limit( 300 ); // 5 minutes
        }

        $indexer = new Indexer();
        $result  = $indexer->index_all( true );

        return new \WP_REST_Response( array(
            'success' => true,
            'message' => sprintf(
                /* translators: %1$d: posts indexed, %2$d: chunks created */
                __( 'Indexed %1$d posts, created %2$d content chunks.', 'wp-ai-chatbot' ),
                $result['posts_indexed'],
                $result['chunks_created']
            ),
            'data' => $result,
        ), 200 );
    }

    /**
     * Index single post handler.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function index_post( $request ) {
        $post_id = (int) $request->get_param( 'post_id' );

        $indexer = new Indexer();
        $chunks  = $indexer->index_post( $post_id );

        if ( $chunks > 0 ) {
            return new \WP_REST_Response( array(
                'success' => true,
                'message' => sprintf(
                    /* translators: %d: chunks created */
                    __( 'Post indexed successfully. Created %d chunks.', 'wp-ai-chatbot' ),
                    $chunks
                ),
                'chunks' => $chunks,
            ), 200 );
        }

        return new \WP_REST_Response( array(
            'success' => false,
            'message' => __( 'Failed to index post. It may not have publishable content.', 'wp-ai-chatbot' ),
        ), 400 );
    }

    /**
     * Get training status handler.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_status( $request ) {
        $indexer = new Indexer();
        $status  = $indexer->get_status();

        $content_model = new Content_Model();
        $stats         = $content_model->get_stats();

        return new \WP_REST_Response( array(
            'success' => true,
            'status'  => $status,
            'stats'   => $stats,
        ), 200 );
    }

    /**
     * Clear all training data handler.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function clear_all( $request ) {
        $content_model = new Content_Model();
        $result        = $content_model->clear_all();

        if ( $result ) {
            return new \WP_REST_Response( array(
                'success' => true,
                'message' => __( 'All training data has been cleared.', 'wp-ai-chatbot' ),
            ), 200 );
        }

        return new \WP_REST_Response( array(
            'success' => false,
            'message' => __( 'Failed to clear training data.', 'wp-ai-chatbot' ),
        ), 500 );
    }

    /**
     * Update content types handler.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function update_content_types( $request ) {
        $content_types = $request->get_param( 'content_types' );

        if ( ! is_array( $content_types ) ) {
            return new \WP_REST_Response( array(
                'success' => false,
                'message' => __( 'Invalid content types.', 'wp-ai-chatbot' ),
            ), 400 );
        }

        // Sanitize
        $content_types = array_map( 'sanitize_text_field', $content_types );

        // Validate they are real post types
        $valid_types = get_post_types( array( 'public' => true ), 'names' );
        $content_types = array_intersect( $content_types, $valid_types );

        Admin::update_settings( array( 'content_types' => $content_types ) );

        return new \WP_REST_Response( array(
            'success'       => true,
            'message'       => __( 'Content types updated.', 'wp-ai-chatbot' ),
            'content_types' => $content_types,
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
