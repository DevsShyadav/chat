<?php
/**
 * Main Plugin class - Orchestrates all plugin functionality.
 *
 * @package WPAICB
 */

namespace WPAICB;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Plugin {

    /**
     * Admin instance.
     *
     * @var Admin\Admin
     */
    private $admin;

    /**
     * Chat widget instance.
     *
     * @var Chat\Widget
     */
    private $widget;

    /**
     * REST API controller.
     *
     * @var API\Rest_Controller
     */
    private $api;

    /**
     * Content indexer.
     *
     * @var Content\Indexer
     */
    private $indexer;

    /**
     * Run the plugin.
     */
    public function run() {
        $this->load_textdomain();
        $this->check_db_update();
        $this->init_components();
        $this->register_hooks();
    }

    /**
     * Load plugin textdomain.
     */
    private function load_textdomain() {
        load_plugin_textdomain(
            'wp-ai-chatbot',
            false,
            dirname( WPAICB_PLUGIN_BASENAME ) . '/languages'
        );
    }

    /**
     * Check if database needs updating.
     */
    private function check_db_update() {
        $current_version = get_option( 'wpaicb_db_version', '0' );
        if ( version_compare( $current_version, WPAICB_DB_VERSION, '<' ) ) {
            $activator = new Activator();
            $activator->create_tables();
            update_option( 'wpaicb_db_version', WPAICB_DB_VERSION );
        }
    }

    /**
     * Initialize plugin components.
     */
    private function init_components() {
        // Admin
        if ( is_admin() ) {
            $this->admin = new Admin\Admin();
        }

        // Frontend widget
        $this->widget = new Chat\Widget();

        // REST API
        $this->api = new API\Rest_Controller();

        // Content indexer
        $this->indexer = new Content\Indexer();
    }

    /**
     * Register WordPress hooks.
     */
    private function register_hooks() {
        // Admin hooks
        if ( is_admin() ) {
            add_action( 'admin_menu', array( $this->admin, 'register_menu' ) );
            add_action( 'admin_enqueue_scripts', array( $this->admin, 'enqueue_assets' ) );
        }

        // Frontend hooks
        add_action( 'wp_enqueue_scripts', array( $this->widget, 'enqueue_assets' ) );
        add_action( 'wp_footer', array( $this->widget, 'render' ) );

        // REST API hooks
        add_action( 'rest_api_init', array( $this->api, 'register_routes' ) );

        // Content hooks
        add_action( 'save_post', array( $this->indexer, 'handle_post_save' ), 10, 3 );
        add_action( 'delete_post', array( $this->indexer, 'handle_post_delete' ) );
        add_action( 'wpaicb_index_content', array( $this->indexer, 'process_index_queue' ) );

        // Cleanup old conversations cron handler
        add_action( 'wpaicb_cleanup_old_conversations', array( $this, 'cleanup_old_conversations' ) );

        // Schedule cron if not scheduled
        if ( ! wp_next_scheduled( 'wpaicb_index_content' ) ) {
            wp_schedule_event( time(), 'hourly', 'wpaicb_index_content' );
        }
    }

    /**
     * Cleanup old closed conversations (cron handler).
     */
    public function cleanup_old_conversations() {
        $conversation_model = new Database\Conversation_Model();
        $conversation_model->cleanup_old( 90 );

        $analytics_model = new Database\Analytics_Model();
        $analytics_model->cleanup_old( 90 );
    }
}
