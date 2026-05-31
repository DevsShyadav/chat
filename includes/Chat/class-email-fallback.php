<?php
/**
 * Email Fallback - Handles email notifications when AI can't answer.
 *
 * @package WPAICB\Chat
 */

namespace WPAICB\Chat;

use WPAICB\Admin\Admin;
use WPAICB\Database\Conversation_Model;
use WPAICB\Database\Message_Model;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Email_Fallback {

    /**
     * Send notification email to admin when visitor leaves email.
     *
     * @param int    $conversation_id Conversation ID.
     * @param string $visitor_email Visitor's email.
     * @param string $visitor_name Visitor's name.
     * @return bool Whether email was sent successfully.
     */
    public function send_notification( $conversation_id, $visitor_email, $visitor_name = '' ) {
        $settings       = Admin::get_settings();
        $fallback_email = $settings['fallback_email'] ?? get_option( 'admin_email' );

        if ( empty( $fallback_email ) ) {
            return false;
        }

        // Get conversation messages for context
        $message_model = new Message_Model();
        $messages      = $message_model->get_by_conversation( $conversation_id );

        // Build email content
        $site_name = get_bloginfo( 'name' );
        $subject   = sprintf(
            /* translators: %s: site name */
            __( '[%s] Chatbot: Visitor needs help', 'wp-ai-chatbot' ),
            $site_name
        );

        $body = $this->build_email_body( $visitor_email, $visitor_name, $messages, $conversation_id );

        // Email headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'Reply-To: ' . $visitor_email,
        );

        // Send the email
        $sent = wp_mail( $fallback_email, $subject, $body, $headers );

        return $sent;
    }

    /**
     * Build the notification email body.
     *
     * @param string $visitor_email Visitor's email.
     * @param string $visitor_name Visitor's name.
     * @param array  $messages Conversation messages.
     * @param int    $conversation_id Conversation ID.
     * @return string HTML email body.
     */
    private function build_email_body( $visitor_email, $visitor_name, $messages, $conversation_id ) {
        $site_name     = get_bloginfo( 'name' );
        $admin_url     = admin_url( 'admin.php?page=wp-ai-chatbot-conversations&conversation_id=' . $conversation_id );
        $display_name  = ! empty( $visitor_name ) ? $visitor_name : $visitor_email;

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #1a1a2e; margin: 0; padding: 0; background: #f5f5f7; }
                .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; margin-top: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
                .header { background: linear-gradient(135deg, #6366F1, #8B5CF6); color: white; padding: 24px 32px; }
                .header h1 { margin: 0; font-size: 20px; font-weight: 600; }
                .header p { margin: 8px 0 0; opacity: 0.9; font-size: 14px; }
                .content { padding: 32px; }
                .visitor-info { background: #f8f9fb; border-radius: 8px; padding: 16px; margin-bottom: 24px; }
                .visitor-info h3 { margin: 0 0 8px; font-size: 14px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
                .visitor-info p { margin: 4px 0; font-size: 15px; }
                .messages { border-left: 3px solid #e5e7eb; padding-left: 16px; margin: 24px 0; }
                .message { margin-bottom: 16px; }
                .message-role { font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; margin-bottom: 4px; }
                .message-content { font-size: 14px; color: #374151; }
                .cta { text-align: center; margin-top: 24px; }
                .cta a { display: inline-block; background: #6366F1; color: white; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 500; }
                .footer { text-align: center; padding: 16px 32px; color: #9ca3af; font-size: 12px; border-top: 1px solid #f3f4f6; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php esc_html_e( 'Visitor Needs Help', 'wp-ai-chatbot' ); ?></h1>
                    <p><?php esc_html_e( 'Your chatbot couldn\'t answer a visitor\'s question', 'wp-ai-chatbot' ); ?></p>
                </div>
                <div class="content">
                    <div class="visitor-info">
                        <h3><?php esc_html_e( 'Visitor Details', 'wp-ai-chatbot' ); ?></h3>
                        <?php if ( ! empty( $visitor_name ) ) : ?>
                            <p><strong><?php esc_html_e( 'Name:', 'wp-ai-chatbot' ); ?></strong> <?php echo esc_html( $visitor_name ); ?></p>
                        <?php endif; ?>
                        <p><strong><?php esc_html_e( 'Email:', 'wp-ai-chatbot' ); ?></strong> <a href="mailto:<?php echo esc_attr( $visitor_email ); ?>"><?php echo esc_html( $visitor_email ); ?></a></p>
                    </div>

                    <h3><?php esc_html_e( 'Conversation', 'wp-ai-chatbot' ); ?></h3>
                    <div class="messages">
                        <?php
                        $display_messages = array_slice( $messages, -6 );
                        foreach ( $display_messages as $msg ) :
                            $role_label = 'user' === $msg->role ? __( 'Visitor', 'wp-ai-chatbot' ) : __( 'AI Bot', 'wp-ai-chatbot' );
                        ?>
                        <div class="message">
                            <div class="message-role"><?php echo esc_html( $role_label ); ?></div>
                            <div class="message-content"><?php echo esc_html( wp_trim_words( $msg->content, 50 ) ); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="cta">
                        <a href="<?php echo esc_url( $admin_url ); ?>"><?php esc_html_e( 'View Full Conversation', 'wp-ai-chatbot' ); ?></a>
                    </div>
                </div>
                <div class="footer">
                    <?php
                    printf(
                        /* translators: %s: site name */
                        esc_html__( 'This notification was sent by WP AI Chatbot on %s', 'wp-ai-chatbot' ),
                        esc_html( $site_name )
                    );
                    ?>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
