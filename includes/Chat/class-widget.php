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
     * Whether widget has already been rendered (prevent duplicates).
     *
     * @var bool
     */
    private $rendered = false;

    /**
     * Enqueue frontend assets.
     */
    public function enqueue_assets() {
        // Don't load in admin or during AJAX/REST/cron
        if ( is_admin() || wp_doing_ajax() || defined( 'REST_REQUEST' ) || defined( 'DOING_CRON' ) ) {
            return;
        }

        // Check if widget should show on this device
        $settings = Admin::get_settings();
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

        // Widget JS (loaded in footer)
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
        // Prevent double render
        if ( $this->rendered ) {
            return;
        }

        // Don't render in admin or during AJAX/REST/cron
        if ( is_admin() || wp_doing_ajax() ) {
            return;
        }
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return;
        }
        if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
            return;
        }

        // Mark as rendered to prevent duplicate output
        $this->rendered = true;

        // Output a clear marker so deployment can be verified via View Source
        echo "\n<!-- WPAICB Widget START v" . esc_html( WPAICB_VERSION ) . " -->\n";

        $this->output_widget_html();

        echo "\n<!-- WPAICB Widget Rendered OK v" . esc_html( WPAICB_VERSION ) . " -->\n";
    }

    /**
     * Output the widget HTML directly (no template include for reliability).
     */
    private function output_widget_html() {
        $settings  = Admin::get_settings();
        $position  = isset( $settings['widget_position'] ) ? $settings['widget_position'] : 'bottom-right';
        $color     = isset( $settings['widget_color'] ) && ! empty( $settings['widget_color'] ) ? $settings['widget_color'] : '#6366F1';
        $dark_mode = isset( $settings['dark_mode'] ) ? $settings['dark_mode'] : 'auto';
        $title     = isset( $settings['widget_title'] ) ? $settings['widget_title'] : 'Chat with us';
        $subtitle  = isset( $settings['widget_subtitle'] ) ? $settings['widget_subtitle'] : 'We typically reply within minutes';
        $branding  = isset( $settings['show_branding'] ) ? $settings['show_branding'] : true;

        $color_dark  = $this->adjust_color( $color, -20 );
        $color_light = $color . '1F';
        ?>
<!-- WP AI Chatbot Widget -->
<div id="wpaicb-chat-widget"
     class="wpaicb-widget wpaicb-widget-<?php echo esc_attr( $position ); ?> wpaicb-theme-<?php echo esc_attr( $dark_mode ); ?>"
     style="--wpaicb-primary:<?php echo esc_attr( $color ); ?>;--wpaicb-primary-dark:<?php echo esc_attr( $color_dark ); ?>;--wpaicb-primary-light:<?php echo esc_attr( $color_light ); ?>;">

    <button id="wpaicb-trigger" class="wpaicb-trigger" aria-label="Open chat" aria-expanded="false">
        <span class="wpaicb-trigger-icon wpaicb-trigger-icon-open">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
        </span>
        <span class="wpaicb-trigger-icon wpaicb-trigger-icon-close">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </span>
        <span class="wpaicb-trigger-pulse"></span>
    </button>

    <div id="wpaicb-window" class="wpaicb-window" aria-hidden="true">
        <div class="wpaicb-header">
            <div class="wpaicb-header-info">
                <div class="wpaicb-header-avatar">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><path d="M8 14s1.5 2 4 2 4-2 4-2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line></svg>
                </div>
                <div class="wpaicb-header-text">
                    <span class="wpaicb-header-title"><?php echo esc_html( $title ); ?></span>
                    <span class="wpaicb-header-subtitle"><?php echo esc_html( $subtitle ); ?></span>
                </div>
            </div>
            <div class="wpaicb-header-actions">
                <button class="wpaicb-header-btn" id="wpaicb-new-chat" title="New conversation">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"></path></svg>
                </button>
                <button class="wpaicb-header-btn" id="wpaicb-close-chat" title="Close">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>
            </div>
        </div>

        <div class="wpaicb-messages" id="wpaicb-messages" role="log" aria-live="polite"></div>

        <div class="wpaicb-email-form" id="wpaicb-email-form" style="display:none;">
            <div class="wpaicb-email-form-inner">
                <p class="wpaicb-email-label">Leave your email for follow-up</p>
                <div class="wpaicb-email-input-wrap">
                    <input type="email" id="wpaicb-email-input" class="wpaicb-email-input" placeholder="your@email.com">
                    <button type="button" id="wpaicb-email-submit" class="wpaicb-email-submit">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="wpaicb-rating" id="wpaicb-rating" style="display:none;">
            <p class="wpaicb-rating-label">Was this helpful?</p>
            <div class="wpaicb-rating-stars">
                <button class="wpaicb-star" data-rating="1" title="1">&#9733;</button>
                <button class="wpaicb-star" data-rating="2" title="2">&#9733;</button>
                <button class="wpaicb-star" data-rating="3" title="3">&#9733;</button>
                <button class="wpaicb-star" data-rating="4" title="4">&#9733;</button>
                <button class="wpaicb-star" data-rating="5" title="5">&#9733;</button>
            </div>
        </div>

        <div class="wpaicb-input-area">
            <div class="wpaicb-input-container">
                <textarea id="wpaicb-input" class="wpaicb-chat-input" placeholder="Type your message..." rows="1" maxlength="1000"></textarea>
                <button id="wpaicb-send" class="wpaicb-send-btn" disabled>
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                </button>
            </div>
            <?php if ( $branding ) : ?>
            <div class="wpaicb-branding">
                <span>Powered by <strong>WP AI Chatbot</strong></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
        <?php
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
            'position'       => isset( $settings['widget_position'] ) ? $settings['widget_position'] : 'bottom-right',
            'color'          => isset( $settings['widget_color'] ) ? $settings['widget_color'] : '#6366F1',
            'title'          => isset( $settings['widget_title'] ) ? $settings['widget_title'] : 'Chat with us',
            'subtitle'       => isset( $settings['widget_subtitle'] ) ? $settings['widget_subtitle'] : 'We typically reply within minutes',
            'welcomeMessage' => isset( $settings['welcome_message'] ) ? $settings['welcome_message'] : 'Hi! How can I help you today?',
            'fallbackMessage' => isset( $settings['fallback_message'] ) ? $settings['fallback_message'] : '',
            'emailFallback'  => isset( $settings['email_fallback'] ) ? (bool) $settings['email_fallback'] : true,
            'darkMode'       => isset( $settings['dark_mode'] ) ? $settings['dark_mode'] : 'auto',
            'showBranding'   => isset( $settings['show_branding'] ) ? (bool) $settings['show_branding'] : true,
            'soundEnabled'   => isset( $settings['sound_enabled'] ) ? (bool) $settings['sound_enabled'] : true,
            'typingIndicator' => isset( $settings['typing_indicator'] ) ? (bool) $settings['typing_indicator'] : true,
            'suggestionChips' => isset( $settings['suggestion_chips'] ) ? $settings['suggestion_chips'] : array(),
            'strings'        => array(
                'placeholder'      => 'Type your message...',
                'send'             => 'Send',
                'typing'           => 'Typing...',
                'emailLabel'       => 'Leave your email for follow-up',
                'emailPlaceholder' => 'your@email.com',
                'emailSubmit'      => 'Submit',
                'emailThanks'      => 'Thanks! We\'ll get back to you soon.',
                'rateTitle'        => 'Was this helpful?',
                'poweredBy'        => 'Powered by WP AI Chatbot',
                'close'            => 'Close',
                'minimize'         => 'Minimize',
                'newChat'          => 'New conversation',
                'error'            => 'Something went wrong. Please try again.',
                'rateLimit'        => 'You\'re sending messages too quickly. Please wait a moment.',
            ),
        );
    }

    /**
     * Get or create a session ID for the visitor.
     *
     * @return string
     */
    private function get_or_create_session_id() {
        $cookie_name = 'wpaicb_session';

        if ( isset( $_COOKIE[ $cookie_name ] ) ) {
            return sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) );
        }

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

        if ( isset( $_SERVER['REQUEST_URI'] ) ) {
            return home_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
        }

        return home_url( '/' );
    }

    /**
     * Adjust hex color brightness.
     *
     * @param string $hex Hex color code.
     * @param int    $steps Steps to adjust.
     * @return string Adjusted hex color.
     */
    public function adjust_color( $hex, $steps ) {
        $hex = ltrim( (string) $hex, '#' );

        if ( strlen( $hex ) === 3 ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if ( strlen( $hex ) !== 6 ) {
            return '#6366F1'; // Fallback to default if invalid
        }

        $r = max( 0, min( 255, hexdec( substr( $hex, 0, 2 ) ) + $steps ) );
        $g = max( 0, min( 255, hexdec( substr( $hex, 2, 2 ) ) + $steps ) );
        $b = max( 0, min( 255, hexdec( substr( $hex, 4, 2 ) ) + $steps ) );

        return '#' . str_pad( dechex( $r ), 2, '0', STR_PAD_LEFT )
                   . str_pad( dechex( $g ), 2, '0', STR_PAD_LEFT )
                   . str_pad( dechex( $b ), 2, '0', STR_PAD_LEFT );
    }
}
