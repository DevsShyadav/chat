<?php
/**
 * Widget - Renders the frontend chat widget.
 *
 * @package WPAICB\Chat
 */

namespace WPAICB\Chat;

use WPAICB\Admin\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Widget {

    /**
     * Enqueue frontend assets.
     */
    public function enqueue_assets() {
        // Don't load in admin
        if ( is_admin() ) {
            return;
        }

        // Check if widget should show on this device
        $settings = Admin::get_settings();

        // Check mobile visibility
        $show_on_mobile = isset( $settings['show_on_mobile'] ) ? $settings['show_on_mobile'] : true;
        if ( ! $show_on_mobile && wp_is_mobile() ) {
            return;
        }

        // Widget CSS
        wp_enqueue_style(
            'wpaicb-widget',
            WPAICB_PLUGIN_URL . 'assets/frontend/css/widget.css',
            array(),
            WPAICB_VERSION
        );

        // Widget JS
        wp_enqueue_script(
            'wpaicb-widget',
            WPAICB_PLUGIN_URL . 'assets/frontend/js/widget.js',
            array(),
            WPAICB_VERSION,
            true
        );

        // Pass configuration to JS
        wp_localize_script( 'wpaicb-widget', 'wpaicbWidget', $this->get_widget_config() );
    }

    /**
     * Render the widget HTML in footer.
     */
    public function render() {
        // Don't render in admin
        if ( is_admin() ) {
            return;
        }

        $settings = Admin::get_settings();

        // Check mobile visibility
        $show_on_mobile = isset( $settings['show_on_mobile'] ) ? $settings['show_on_mobile'] : true;
        if ( ! $show_on_mobile && wp_is_mobile() ) {
            return;
        }

        include WPAICB_PLUGIN_DIR . 'templates/frontend/widget.php';
    }

    /**
     * Get widget configuration for JavaScript.
     *
     * @return array
     */
    private function get_widget_config() {
        $settings = Admin::get_settings();

        $is_configured = ! empty( $settings['openai_api_key'] ) || ! empty( $settings['gemini_api_key'] ) || ! empty( $settings['groq_api_key'] );

        return array(
            'restUrl'        => rest_url( 'wpaicb/v1/' ),
            'nonce'          => wp_create_nonce( 'wp_rest' ),
            'sessionId'      => $this->get_or_create_session_id(),
            'pageUrl'        => $this->get_current_url(),
            'isConfigured'   => $is_configured,
            'position'       => $settings['widget_position'] ?? 'bottom-right',
            'color'          => $settings['widget_color'] ?? '#6366F1',
            'title'          => $settings['widget_title'] ?? __( 'Chat with us', 'wp-ai-chatbot' ),
            'subtitle'       => $settings['widget_subtitle'] ?? __( 'We typically reply within minutes', 'wp-ai-chatbot' ),
            'welcomeMessage' => $settings['welcome_message'] ?? __( 'Hi! How can I help you today?', 'wp-ai-chatbot' ),
            'fallbackMessage' => $settings['fallback_message'] ?? '',
            'emailFallback'  => (bool) ( $settings['email_fallback'] ?? true ),
            'darkMode'       => $settings['dark_mode'] ?? 'auto',
            'showBranding'   => (bool) ( $settings['show_branding'] ?? true ),
            'soundEnabled'   => (bool) ( $settings['sound_enabled'] ?? true ),
            'typingIndicator' => (bool) ( $settings['typing_indicator'] ?? true ),
            'suggestionChips' => $settings['suggestion_chips'] ?? array(),
            'strings'        => array(
                'placeholder'    => __( 'Type your message...', 'wp-ai-chatbot' ),
                'send'           => __( 'Send', 'wp-ai-chatbot' ),
                'typing'         => __( 'Typing...', 'wp-ai-chatbot' ),
                'emailLabel'     => __( 'Leave your email for follow-up', 'wp-ai-chatbot' ),
                'emailPlaceholder' => __( 'your@email.com', 'wp-ai-chatbot' ),
                'emailSubmit'    => __( 'Submit', 'wp-ai-chatbot' ),
                'emailThanks'    => __( 'Thanks! We\'ll get back to you soon.', 'wp-ai-chatbot' ),
                'rateTitle'      => __( 'Was this helpful?', 'wp-ai-chatbot' ),
                'poweredBy'      => __( 'Powered by WP AI Chatbot', 'wp-ai-chatbot' ),
                'close'          => __( 'Close', 'wp-ai-chatbot' ),
                'minimize'       => __( 'Minimize', 'wp-ai-chatbot' ),
                'newChat'        => __( 'New conversation', 'wp-ai-chatbot' ),
                'error'          => __( 'Something went wrong. Please try again.', 'wp-ai-chatbot' ),
                'rateLimit'      => __( 'You\'re sending messages too quickly. Please wait a moment.', 'wp-ai-chatbot' ),
            ),
        );
    }

    /**
     * Get or create a session ID for the visitor.
     *
     * @return string
     */
    private function get_or_create_session_id() {
        // Use a cookie-based session for consistency
        $cookie_name = 'wpaicb_session';

        if ( isset( $_COOKIE[ $cookie_name ] ) ) {
            return sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) );
        }

        // Generate a new session ID (will be set by JavaScript)
        return wp_generate_uuid4();
    }

    /**
     * Get the current page URL.
     *
     * @return string
     */
    private function get_current_url() {
        global $wp;

        if ( isset( $wp ) && is_object( $wp ) && isset( $wp->request ) ) {
            return home_url( add_query_arg( array(), $wp->request ) );
        }

        // Fallback: use server variables
        if ( isset( $_SERVER['REQUEST_URI'] ) ) {
            return home_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
        }

        return home_url( '/' );
    }

    /**
     * Adjust hex color brightness.
     *
     * @param string $hex Hex color code.
     * @param int    $steps Steps to adjust (-255 to 255).
     * @return string Adjusted hex color.
     */
    public function adjust_color( $hex, $steps ) {
        $hex = ltrim( $hex, '#' );

        if ( strlen( $hex ) === 3 ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = max( 0, min( 255, hexdec( substr( $hex, 0, 2 ) ) + $steps ) );
        $g = max( 0, min( 255, hexdec( substr( $hex, 2, 2 ) ) + $steps ) );
        $b = max( 0, min( 255, hexdec( substr( $hex, 4, 2 ) ) + $steps ) );

        return '#' . str_pad( dechex( $r ), 2, '0', STR_PAD_LEFT )
                   . str_pad( dechex( $g ), 2, '0', STR_PAD_LEFT )
                   . str_pad( dechex( $b ), 2, '0', STR_PAD_LEFT );
    }
}
