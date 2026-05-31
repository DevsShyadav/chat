<?php
/**
 * Plugin Name: WP AI Chatbot
 * Plugin URI: https://devsarun.io/
 * Description: Plug & Play AI-powered customer support chatbot. BYOK (OpenAI/Gemini/Groq) — trains on your site content, answers customer questions 24/7.
 * Version: 1.0.3
 * Author: DevsArun
 * Author URI: https://devsarun.io/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-ai-chatbot
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'WPAICB_VERSION', '1.0.3' );
define( 'WPAICB_PLUGIN_FILE', __FILE__ );
define( 'WPAICB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPAICB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPAICB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPAICB_DB_VERSION', '1.0.0' );

// Autoloader
require_once WPAICB_PLUGIN_DIR . 'includes/class-autoloader.php';

/**
 * Plugin activation hook.
 */
function wpaicb_activate() {
    $activator = new \WPAICB\Activator();
    $activator->activate();
}
register_activation_hook( __FILE__, 'wpaicb_activate' );

/**
 * Plugin deactivation hook.
 */
function wpaicb_deactivate() {
    $deactivator = new \WPAICB\Deactivator();
    $deactivator->deactivate();
}
register_deactivation_hook( __FILE__, 'wpaicb_deactivate' );

/**
 * Initialize the plugin.
 */
function wpaicb_init() {
    $plugin = new \WPAICB\Plugin();
    $plugin->run();
}
add_action( 'plugins_loaded', 'wpaicb_init' );
