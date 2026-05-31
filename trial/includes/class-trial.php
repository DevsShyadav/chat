<?php
/**
 * Trial Manager - Handles the 24-hour trial countdown and auto-deletion.
 *
 * @package WPAICB
 */

namespace WPAICB;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trial {

    /**
     * Run the trial manager.
     */
    public function run() {
        // Ensure trial start time exists (handles installs where activation
        // hook may not have fired, e.g. must-use or manual upload).
        if ( ! get_option( 'wpaicb_trial_started' ) ) {
            add_option( 'wpaicb_trial_started', time() );
        }

        // Check expiry on every load
        add_action( 'init', array( $this, 'check_expiry' ), 1 );

        // Admin countdown banner + assets
        if ( is_admin() ) {
            add_action( 'admin_notices', array( $this, 'render_countdown_banner' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_countdown_assets' ) );
        }

        // Also expose remaining time for the frontend widget branding
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_notice' ) );
    }

    /**
     * Get trial start timestamp.
     *
     * @return int
     */
    public function get_start_time() {
        return (int) get_option( 'wpaicb_trial_started', time() );
    }

    /**
     * Get trial expiry timestamp.
     *
     * @return int
     */
    public function get_expiry_time() {
        return $this->get_start_time() + WPAICB_TRIAL_DURATION;
    }

    /**
     * Get seconds remaining in trial.
     *
     * @return int Seconds remaining (0 if expired).
     */
    public function get_seconds_remaining() {
        $remaining = $this->get_expiry_time() - time();
        return max( 0, $remaining );
    }

    /**
     * Check if the trial has expired.
     *
     * @return bool
     */
    public function is_expired() {
        return $this->get_seconds_remaining() <= 0;
    }

    /**
     * Check expiry and self-destruct if trial is over.
     */
    public function check_expiry() {
        if ( ! $this->is_expired() ) {
            return;
        }

        $this->self_destruct();
    }

    /**
     * Deactivate and delete the trial plugin completely.
     */
    public function self_destruct() {
        // Load required WP functions
        if ( ! function_exists( 'deactivate_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        if ( ! function_exists( 'delete_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        require_once ABSPATH . 'wp-admin/includes/file.php';

        // Initialize filesystem
        global $wp_filesystem;
        if ( empty( $wp_filesystem ) ) {
            WP_Filesystem();
        }

        $plugin_basename = WPAICB_PLUGIN_BASENAME;

        // Clean up plugin data before deletion
        $this->cleanup_data();

        // Deactivate the plugin
        deactivate_plugins( $plugin_basename, true );

        // Delete the plugin files
        if ( function_exists( 'delete_plugins' ) ) {
            delete_plugins( array( $plugin_basename ) );
        }

        // If still on a plugin page, redirect to avoid errors
        if ( is_admin() ) {
            // Set a transient so we can show an "expired" message after redirect
            set_transient( 'wpaicb_trial_expired_notice', 1, 60 );
        }
    }

    /**
     * Clean up all trial data from the database.
     */
    private function cleanup_data() {
        global $wpdb;

        // Remove options
        $options = array(
            'wpaicb_settings', 'wpaicb_db_version', 'wpaicb_activated',
            'wpaicb_onboarding_complete', 'wpaicb_encryption_key',
            'wpaicb_last_index_run', 'wpaicb_trial_started',
        );
        foreach ( $options as $option ) {
            delete_option( $option );
        }

        // Drop tables
        $tables = array(
            $wpdb->prefix . 'aicb_conversations',
            $wpdb->prefix . 'aicb_messages',
            $wpdb->prefix . 'aicb_training_content',
            $wpdb->prefix . 'aicb_custom_qa',
            $wpdb->prefix . 'aicb_analytics',
        );
        foreach ( $tables as $table ) {
            $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" ); // phpcs:ignore
        }

        // Clear scheduled events
        wp_clear_scheduled_hook( 'wpaicb_index_content' );
        wp_clear_scheduled_hook( 'wpaicb_cleanup_old_conversations' );
    }

    /**
     * Enqueue admin countdown assets.
     *
     * @param string $hook Current admin page.
     */
    public function enqueue_countdown_assets( $hook ) {
        wp_enqueue_style(
            'wpaicb-trial',
            WPAICB_PLUGIN_URL . 'assets/admin/css/trial.css',
            array(),
            WPAICB_VERSION
        );
        wp_enqueue_script(
            'wpaicb-trial',
            WPAICB_PLUGIN_URL . 'assets/admin/js/trial.js',
            array(),
            WPAICB_VERSION,
            true
        );
        wp_localize_script( 'wpaicb-trial', 'wpaicbTrial', array(
            'expiry'      => $this->get_expiry_time() * 1000, // JS uses milliseconds
            'now'         => time() * 1000,
            'upgradeUrl'  => WPAICB_UPGRADE_URL,
            'secondsLeft' => $this->get_seconds_remaining(),
        ) );
    }

    /**
     * Render the countdown banner at the top of admin pages.
     */
    public function render_countdown_banner() {
        $remaining = $this->get_seconds_remaining();
        $hours     = floor( $remaining / 3600 );
        $minutes   = floor( ( $remaining % 3600 ) / 60 );
        $seconds   = $remaining % 60;
        ?>
        <div id="wpaicb-trial-banner" class="wpaicb-trial-banner">
            <div class="wpaicb-trial-banner-inner">
                <div class="wpaicb-trial-left">
                    <span class="wpaicb-trial-icon">⏱️</span>
                    <div class="wpaicb-trial-text">
                        <strong><?php esc_html_e( 'WP AI Chatbot — Free Trial', 'wp-ai-chatbot' ); ?></strong>
                        <span><?php esc_html_e( 'Your trial auto-removes when the timer hits zero.', 'wp-ai-chatbot' ); ?></span>
                    </div>
                </div>
                <div class="wpaicb-trial-center">
                    <div class="wpaicb-trial-countdown" id="wpaicb-countdown"
                         data-expiry="<?php echo esc_attr( $this->get_expiry_time() * 1000 ); ?>">
                        <div class="wpaicb-trial-unit"><span class="wpaicb-trial-num" id="wpaicb-cd-h"><?php echo esc_html( sprintf( '%02d', $hours ) ); ?></span><span class="wpaicb-trial-lbl">Hours</span></div>
                        <span class="wpaicb-trial-colon">:</span>
                        <div class="wpaicb-trial-unit"><span class="wpaicb-trial-num" id="wpaicb-cd-m"><?php echo esc_html( sprintf( '%02d', $minutes ) ); ?></span><span class="wpaicb-trial-lbl">Min</span></div>
                        <span class="wpaicb-trial-colon">:</span>
                        <div class="wpaicb-trial-unit"><span class="wpaicb-trial-num" id="wpaicb-cd-s"><?php echo esc_html( sprintf( '%02d', $seconds ) ); ?></span><span class="wpaicb-trial-lbl">Sec</span></div>
                    </div>
                </div>
                <div class="wpaicb-trial-right">
                    <a href="<?php echo esc_url( WPAICB_UPGRADE_URL ); ?>" target="_blank" rel="noopener" class="wpaicb-trial-upgrade">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                        <?php esc_html_e( 'Upgrade to Pro', 'wp-ai-chatbot' ); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Add a small inline notice on the frontend (in widget config) - no-op hook
     * placeholder so trial branding stays consistent. Kept lightweight.
     */
    public function enqueue_frontend_notice() {
        // Intentionally minimal — frontend widget already shows branding.
        // This hook reserved for future trial frontend badges.
    }
}
