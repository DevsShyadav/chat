<?php
/**
 * PSR-4 Autoloader for WP AI Chatbot.
 *
 * @package WPAICB
 */

namespace WPAICB;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

spl_autoload_register( function ( $class ) {
    $prefix = 'WPAICB\\';
    $base_dir = WPAICB_PLUGIN_DIR . 'includes/';

    $len = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }

    $relative_class = substr( $class, $len );

    // Convert namespace separators to directory separators
    $parts = explode( '\\', $relative_class );
    $class_name = array_pop( $parts );

    // Convert class name to file name (WordPress style)
    $file_name = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';

    // Build directory path from remaining namespace parts
    $directory = '';
    if ( ! empty( $parts ) ) {
        $directory = implode( '/', $parts ) . '/';
    }

    $file = $base_dir . $directory . $file_name;

    if ( file_exists( $file ) ) {
        require_once $file;
    }
});
