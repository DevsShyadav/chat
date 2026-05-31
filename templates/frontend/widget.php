<?php
/**
 * Frontend Widget Template.
 *
 * @package WPAICB
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$widget_settings = \WPAICB\Admin\Admin::get_settings();
$position        = $widget_settings['widget_position'] ?? 'bottom-right';
$color           = $widget_settings['widget_color'] ?? '#10B981';
$dark_mode       = $widget_settings['dark_mode'] ?? 'auto';
?>
<!-- WP AI Chatbot Widget -->
<div id="wpaicb-chat-widget"
     class="wpaicb-widget wpaicb-widget-<?php echo esc_attr( $position ); ?> wpaicb-theme-<?php echo esc_attr( $dark_mode ); ?>"
     data-position="<?php echo esc_attr( $position ); ?>"
     style="--wpaicb-primary: <?php echo esc_attr( $color ); ?>; --wpaicb-primary-dark: <?php echo esc_attr( $this->adjust_color( $color, -20 ) ); ?>; --wpaicb-primary-light: <?php echo esc_attr( $this->adjust_color( $color, 0 ) . '1F' ); ?>;"
     aria-label="<?php esc_attr_e( 'Chat Widget', 'wp-ai-chatbot' ); ?>"
     role="complementary">

    <!-- Chat Bubble Trigger -->
    <button id="wpaicb-trigger"
            class="wpaicb-trigger"
            aria-label="<?php esc_attr_e( 'Open chat', 'wp-ai-chatbot' ); ?>"
            aria-expanded="false">
        <span class="wpaicb-trigger-icon wpaicb-trigger-icon-open">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
        </span>
        <span class="wpaicb-trigger-icon wpaicb-trigger-icon-close">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </span>
        <span class="wpaicb-trigger-pulse"></span>
    </button>

    <!-- Chat Window -->
    <div id="wpaicb-window" class="wpaicb-window" aria-hidden="true">
        <!-- Header -->
        <div class="wpaicb-header">
            <div class="wpaicb-header-info">
                <div class="wpaicb-header-avatar">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><path d="M8 14s1.5 2 4 2 4-2 4-2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line></svg>
                </div>
                <div class="wpaicb-header-text">
                    <span class="wpaicb-header-title"><?php echo esc_html( $widget_settings['widget_title'] ?? __( 'Chat with us', 'wp-ai-chatbot' ) ); ?></span>
                    <span class="wpaicb-header-subtitle"><?php echo esc_html( $widget_settings['widget_subtitle'] ?? __( 'We typically reply within minutes', 'wp-ai-chatbot' ) ); ?></span>
                </div>
            </div>
            <div class="wpaicb-header-actions">
                <button class="wpaicb-header-btn" id="wpaicb-new-chat" title="<?php esc_attr_e( 'New conversation', 'wp-ai-chatbot' ); ?>">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"></path></svg>
                </button>
                <button class="wpaicb-header-btn" id="wpaicb-close-chat" title="<?php esc_attr_e( 'Close', 'wp-ai-chatbot' ); ?>">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>
            </div>
        </div>

        <!-- Messages Container -->
        <div class="wpaicb-messages" id="wpaicb-messages" role="log" aria-live="polite">
            <!-- Welcome message inserted by JS -->
        </div>

        <!-- Email Fallback Form (hidden by default) -->
        <div class="wpaicb-email-form" id="wpaicb-email-form" style="display:none;">
            <div class="wpaicb-email-form-inner">
                <p class="wpaicb-email-label"><?php esc_html_e( 'Leave your email for follow-up', 'wp-ai-chatbot' ); ?></p>
                <div class="wpaicb-email-input-wrap">
                    <input type="email" id="wpaicb-email-input" class="wpaicb-email-input" placeholder="<?php esc_attr_e( 'your@email.com', 'wp-ai-chatbot' ); ?>" required>
                    <button type="button" id="wpaicb-email-submit" class="wpaicb-email-submit">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Rating (hidden by default) -->
        <div class="wpaicb-rating" id="wpaicb-rating" style="display:none;">
            <p class="wpaicb-rating-label"><?php esc_html_e( 'Was this helpful?', 'wp-ai-chatbot' ); ?></p>
            <div class="wpaicb-rating-stars">
                <button class="wpaicb-star" data-rating="1" title="1">★</button>
                <button class="wpaicb-star" data-rating="2" title="2">★</button>
                <button class="wpaicb-star" data-rating="3" title="3">★</button>
                <button class="wpaicb-star" data-rating="4" title="4">★</button>
                <button class="wpaicb-star" data-rating="5" title="5">★</button>
            </div>
        </div>

        <!-- Input Area -->
        <div class="wpaicb-input-area">
            <div class="wpaicb-input-container">
                <textarea id="wpaicb-input"
                          class="wpaicb-chat-input"
                          placeholder="<?php esc_attr_e( 'Type your message...', 'wp-ai-chatbot' ); ?>"
                          rows="1"
                          maxlength="1000"
                          aria-label="<?php esc_attr_e( 'Type your message', 'wp-ai-chatbot' ); ?>"></textarea>
                <button id="wpaicb-send"
                        class="wpaicb-send-btn"
                        aria-label="<?php esc_attr_e( 'Send message', 'wp-ai-chatbot' ); ?>"
                        disabled>
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                </button>
            </div>
            <?php if ( $widget_settings['show_branding'] ?? true ) : ?>
            <div class="wpaicb-branding">
                <span><?php esc_html_e( 'Powered by', 'wp-ai-chatbot' ); ?> <strong>WP AI Chatbot</strong></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
/**
 * Helper to darken/lighten a hex color.
 * Defined as a method on the Widget class but also accessible via inline call.
 */
if ( ! function_exists( 'wpaicb_adjust_color' ) ) {
    /**
     * Adjust hex color brightness.
     *
     * @param string $hex Hex color code.
     * @param int    $steps Steps to adjust (-255 to 255).
     * @return string Adjusted hex color.
     */
    function wpaicb_adjust_color( $hex, $steps ) {
        $hex = ltrim( $hex, '#' );
        $r   = max( 0, min( 255, hexdec( substr( $hex, 0, 2 ) ) + $steps ) );
        $g   = max( 0, min( 255, hexdec( substr( $hex, 2, 2 ) ) + $steps ) );
        $b   = max( 0, min( 255, hexdec( substr( $hex, 4, 2 ) ) + $steps ) );

        return '#' . str_pad( dechex( $r ), 2, '0', STR_PAD_LEFT )
                   . str_pad( dechex( $g ), 2, '0', STR_PAD_LEFT )
                   . str_pad( dechex( $b ), 2, '0', STR_PAD_LEFT );
    }
}
