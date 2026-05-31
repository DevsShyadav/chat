<?php
/**
 * Plugin Activator - Handles activation logic.
 *
 * @package WPAICB
 */

namespace WPAICB;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Activator {

    /**
     * Run activation routines.
     */
    public function activate() {
        $this->create_tables();
        $this->set_default_options();
        $this->schedule_events();

        update_option( 'wpaicb_db_version', WPAICB_DB_VERSION );
        update_option( 'wpaicb_activated', time() );

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create database tables.
     */
    public function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = array();

        // Conversations table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aicb_conversations (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id VARCHAR(64) NOT NULL,
            visitor_ip VARCHAR(45) DEFAULT '',
            visitor_name VARCHAR(255) DEFAULT '',
            visitor_email VARCHAR(255) DEFAULT '',
            status VARCHAR(20) DEFAULT 'active',
            satisfaction_rating TINYINT(1) DEFAULT NULL,
            message_count INT(11) DEFAULT 0,
            page_url TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY session_id (session_id),
            KEY status (status),
            KEY created_at (created_at)
        ) {$charset_collate};";

        // Messages table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aicb_messages (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            conversation_id BIGINT(20) UNSIGNED NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'user',
            content TEXT NOT NULL,
            confidence_score FLOAT DEFAULT NULL,
            tokens_used INT(11) DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY conversation_id (conversation_id),
            KEY role (role),
            KEY created_at (created_at)
        ) {$charset_collate};";

        // Training content table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aicb_training_content (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT(20) UNSIGNED DEFAULT NULL,
            content_type VARCHAR(50) NOT NULL DEFAULT 'post',
            title VARCHAR(255) NOT NULL DEFAULT '',
            chunk_text TEXT NOT NULL,
            chunk_index INT(11) NOT NULL DEFAULT 0,
            word_count INT(11) DEFAULT 0,
            keywords TEXT DEFAULT NULL,
            status VARCHAR(20) DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY content_type (content_type),
            KEY status (status)
        ) {$charset_collate};";

        // Custom Q&A table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aicb_custom_qa (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            question TEXT NOT NULL,
            answer TEXT NOT NULL,
            priority INT(11) DEFAULT 0,
            status VARCHAR(20) DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY priority (priority)
        ) {$charset_collate};";

        // Analytics table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aicb_analytics (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event_type VARCHAR(50) NOT NULL,
            event_data TEXT DEFAULT NULL,
            session_id VARCHAR(64) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY session_id (session_id),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        foreach ( $sql as $query ) {
            dbDelta( $query );
        }
    }

    /**
     * Set default plugin options.
     */
    private function set_default_options() {
        $defaults = array(
            'wpaicb_settings' => array(
                'ai_provider'        => 'openai',
                'openai_api_key'     => '',
                'openai_model'       => 'gpt-5.4-mini',
                'gemini_api_key'     => '',
                'gemini_model'       => 'gemini-2.5-flash',
                'groq_api_key'       => '',
                'groq_model'         => 'llama-4-scout-17b-16e-instruct',
                'max_tokens'         => 500,
                'temperature'        => 0.7,
                'system_prompt'      => 'You are a helpful customer support assistant for {site_name}. Answer questions based on the provided context. If you cannot find the answer in the context, politely say you don\'t have that information and offer to connect the visitor with a human.',
                'welcome_message'    => 'Hi! 👋 How can I help you today?',
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
            ),
        );

        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( $key ) ) {
                add_option( $key, $value );
            }
        }
    }

    /**
     * Schedule cron events.
     */
    private function schedule_events() {
        if ( ! wp_next_scheduled( 'wpaicb_index_content' ) ) {
            wp_schedule_event( time(), 'hourly', 'wpaicb_index_content' );
        }

        if ( ! wp_next_scheduled( 'wpaicb_cleanup_old_conversations' ) ) {
            wp_schedule_event( time(), 'daily', 'wpaicb_cleanup_old_conversations' );
        }
    }
}
