<?php
/**
 * Admin Controller - Main admin functionality orchestrator.
 *
 * @package WPAICB\Admin
 */

namespace WPAICB\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin {

    /**
     * Menu slug prefix.
     *
     * @var string
     */
    const MENU_SLUG = 'wp-ai-chatbot';

    /**
     * Register admin menu pages.
     */
    public function register_menu() {
        add_menu_page(
            __( 'AI Chatbot', 'wp-ai-chatbot' ),
            __( 'AI Chatbot', 'wp-ai-chatbot' ),
            'manage_options',
            self::MENU_SLUG,
            array( $this, 'render_dashboard' ),
            'dashicons-format-chat',
            30
        );

        add_submenu_page(
            self::MENU_SLUG,
            __( 'Dashboard', 'wp-ai-chatbot' ),
            __( 'Dashboard', 'wp-ai-chatbot' ),
            'manage_options',
            self::MENU_SLUG,
            array( $this, 'render_dashboard' )
        );

        add_submenu_page(
            self::MENU_SLUG,
            __( 'Conversations', 'wp-ai-chatbot' ),
            __( 'Conversations', 'wp-ai-chatbot' ),
            'manage_options',
            self::MENU_SLUG . '-conversations',
            array( $this, 'render_conversations' )
        );

        add_submenu_page(
            self::MENU_SLUG,
            __( 'Training', 'wp-ai-chatbot' ),
            __( 'Training', 'wp-ai-chatbot' ),
            'manage_options',
            self::MENU_SLUG . '-training',
            array( $this, 'render_training' )
        );

        add_submenu_page(
            self::MENU_SLUG,
            __( 'Settings', 'wp-ai-chatbot' ),
            __( 'Settings', 'wp-ai-chatbot' ),
            'manage_options',
            self::MENU_SLUG . '-settings',
            array( $this, 'render_settings' )
        );
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_assets( $hook ) {
        // Only load on our plugin pages - check if current screen belongs to our plugin
        $screen = get_current_screen();
        if ( ! $screen ) {
            return;
        }

        // Check if we're on one of our plugin pages by looking for our menu slug in the screen id
        if ( strpos( $screen->id, self::MENU_SLUG ) === false ) {
            return;
        }

        // Google Fonts (Inter)
        wp_enqueue_style(
            'wpaicb-google-fonts',
            'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
            array(),
            WPAICB_VERSION
        );

        // Admin CSS
        wp_enqueue_style(
            'wpaicb-admin',
            WPAICB_PLUGIN_URL . 'assets/admin/css/admin.css',
            array(),
            WPAICB_VERSION
        );

        // Admin JS
        wp_enqueue_script(
            'wpaicb-admin',
            WPAICB_PLUGIN_URL . 'assets/admin/js/admin.js',
            array( 'jquery' ),
            WPAICB_VERSION,
            true
        );

        // Localize script
        wp_localize_script( 'wpaicb-admin', 'wpaicbAdmin', array(
            'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
            'restUrl'    => rest_url( 'wpaicb/v1/' ),
            'nonce'      => wp_create_nonce( 'wpaicb_admin_nonce' ),
            'restNonce'  => wp_create_nonce( 'wp_rest' ),
            'pluginUrl'  => WPAICB_PLUGIN_URL,
            'strings'    => array(
                'saving'        => __( 'Saving...', 'wp-ai-chatbot' ),
                'saved'         => __( 'Settings saved!', 'wp-ai-chatbot' ),
                'error'         => __( 'An error occurred. Please try again.', 'wp-ai-chatbot' ),
                'confirm_delete' => __( 'Are you sure you want to delete this?', 'wp-ai-chatbot' ),
                'indexing'      => __( 'Indexing content...', 'wp-ai-chatbot' ),
                'indexed'       => __( 'Content indexed successfully!', 'wp-ai-chatbot' ),
                'testing'       => __( 'Testing connection...', 'wp-ai-chatbot' ),
                'connected'     => __( 'Connection successful!', 'wp-ai-chatbot' ),
                'failed'        => __( 'Connection failed. Please check your API key.', 'wp-ai-chatbot' ),
            ),
        ) );
    }

    /**
     * Render dashboard page.
     */
    public function render_dashboard() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'wp-ai-chatbot' ) );
        }

        $dashboard = new Dashboard();
        $dashboard->render();
    }

    /**
     * Render conversations page.
     */
    public function render_conversations() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'wp-ai-chatbot' ) );
        }

        $conversations = new Conversations();
        $conversations->render();
    }

    /**
     * Render training page.
     */
    public function render_training() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'wp-ai-chatbot' ) );
        }

        $training = new Training();
        $training->render();
    }

    /**
     * Render settings page.
     */
    public function render_settings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'wp-ai-chatbot' ) );
        }

        $settings = new Settings();
        $settings->render();
    }

    /**
     * Get plugin settings.
     *
     * @param string $key Specific setting key.
     * @param mixed  $default Default value.
     * @return mixed
     */
    public static function get_settings( $key = '', $default = null ) {
        $settings = get_option( 'wpaicb_settings', array() );

        if ( empty( $key ) ) {
            return $settings;
        }

        return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
    }

    /**
     * Update plugin settings.
     *
     * @param array $new_settings Settings to update.
     * @return bool
     */
    public static function update_settings( $new_settings ) {
        $current_settings = get_option( 'wpaicb_settings', array() );
        $merged           = array_merge( $current_settings, $new_settings );

        return update_option( 'wpaicb_settings', $merged );
    }
}
