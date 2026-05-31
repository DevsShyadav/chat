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
        $this->ensure_settings_exist();
        $this->check_db_update();
        $this->init_components();
        $this->register_hooks();
    }

    /**
     * Ensure default settings exist (handles case where plugin files
     * were replaced without re-activation).
     */
    private function ensure_settings_exist() {
        if ( false === get_option( 'wpaicb_settings' ) ) {
            add_option( 'wpaicb_settings', array(
                'ai_provider'        => 'openai',
                'openai_api_key'     => '',
                'openai_model'       => 'gpt-5.4-mini',
                'gemini_api_key'     => '',
                'gemini_model'       => 'gemini-2.5-flash',
                'groq_api_key'       => '',
                'groq_model'         => 'llama-4-scout-17b-16e-instruct',
                'max_tokens'         => 500,
                'temperature'        => 0.7,
                'system_prompt'      => 'You are a helpful customer support assistant. Answer questions based on the provided context.',
                'welcome_message'    => 'Hi! How can I help you today?',
                'fallback_message'   => 'I\'m not sure I can help with that. Would you like to leave your email so our team can get back to you?',
                'widget_position'    => 'bottom-right',
                'widget_color'       => '#10B981',
                'widget_title'       => 'Chat with us',
                'widget_subtitle'    => 'We typically reply within minutes',
                'show_on_mobile'     => true,
                'show_branding'      => true,
                'email_fallback'     => true,
                'fallback_email'     => get_option( 'admin_email' ),
                'rate_limit'         => 20,
                'rate_limit_window'  => 60,
                'content_types'      => array( 'post', 'page' ),
                'excluded_posts'     => array(),
                'chunk_size'         => 500,
                'max_context_chunks' => 5,
                'confidence_threshold' => 0.3,
                'auto_index'         => true,
                'dark_mode'          => 'auto',
                'widget_icon'        => 'chat',
                'sound_enabled'      => true,
                'typing_indicator'   => true,
                'suggestion_chips'   => array(),
            ) );
        }
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

            // AJAX handlers for settings save (most reliable method)
            add_action( 'wp_ajax_wpaicb_save_settings', array( $this, 'ajax_save_settings' ) );
        }

        // Frontend hooks - render in footer (wp_body_open as backup for themes without wp_footer)
        add_action( 'wp_enqueue_scripts', array( $this->widget, 'enqueue_assets' ) );
        add_action( 'wp_footer', array( $this->widget, 'render' ), 99 );
        add_action( 'wp_body_open', array( $this->widget, 'render' ) );

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

    /**
     * AJAX handler for saving settings.
     * Uses wp_ajax instead of REST API for maximum reliability.
     */
    public function ajax_save_settings() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpaicb_admin_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ) );
        }

        // Verify capability
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ) );
        }

        // Get settings from POST data
        $raw_settings = isset( $_POST['settings'] ) ? $_POST['settings'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

        if ( empty( $raw_settings ) || ! is_array( $raw_settings ) ) {
            wp_send_json_error( array( 'message' => 'No settings provided.' ) );
        }

        // Sanitize settings
        $sanitized = Security\Sanitizer::sanitize_settings( $raw_settings );

        // Save settings
        Admin\Admin::update_settings( $sanitized );

        wp_send_json_success( array( 'message' => 'Settings saved successfully!' ) );
    }
}
