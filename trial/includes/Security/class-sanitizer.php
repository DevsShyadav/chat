<?php
/**
 * Sanitizer - Centralized input sanitization utilities.
 *
 * @package WPAICB\Security
 */

namespace WPAICB\Security;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Sanitizer {

    /**
     * Sanitize a chat message from user.
     *
     * @param string $message Raw message input.
     * @return string Sanitized message.
     */
    public static function sanitize_message( $message ) {
        // Remove null bytes
        $message = str_replace( chr( 0 ), '', $message );

        // Strip HTML tags but preserve basic formatting
        $message = wp_strip_all_tags( $message );

        // Limit length
        $message = mb_substr( $message, 0, 1000 );

        // Normalize whitespace
        $message = preg_replace( '/\s+/', ' ', $message );

        return trim( $message );
    }

    /**
     * Sanitize an email address.
     *
     * @param string $email Raw email input.
     * @return string Sanitized email or empty string if invalid.
     */
    public static function sanitize_email( $email ) {
        $email = sanitize_email( $email );

        if ( ! is_email( $email ) ) {
            return '';
        }

        return $email;
    }

    /**
     * Sanitize a session ID.
     *
     * @param string $session_id Raw session ID.
     * @return string Sanitized session ID.
     */
    public static function sanitize_session_id( $session_id ) {
        // UUID format: 8-4-4-4-12 hex characters
        $session_id = sanitize_text_field( $session_id );

        if ( ! preg_match( '/^[a-f0-9\-]{36}$/i', $session_id ) ) {
            return '';
        }

        return $session_id;
    }

    /**
     * Sanitize settings array.
     *
     * @param array $settings Raw settings input.
     * @return array Sanitized settings.
     */
    public static function sanitize_settings( $settings ) {
        $sanitized = array();

        if ( isset( $settings['ai_provider'] ) ) {
            $sanitized['ai_provider'] = sanitize_text_field( $settings['ai_provider'] );
            if ( ! in_array( $sanitized['ai_provider'], array( 'openai', 'gemini', 'groq' ), true ) ) {
                $sanitized['ai_provider'] = 'openai';
            }
        }

        // API Keys - sanitize but don't strip (they contain special chars)
        $key_fields = array( 'openai_api_key', 'gemini_api_key', 'groq_api_key' );
        foreach ( $key_fields as $field ) {
            if ( isset( $settings[ $field ] ) ) {
                $sanitized[ $field ] = sanitize_text_field( $settings[ $field ] );
            }
        }

        // Model selections
        $model_fields = array( 'openai_model', 'gemini_model', 'groq_model' );
        foreach ( $model_fields as $field ) {
            if ( isset( $settings[ $field ] ) ) {
                $sanitized[ $field ] = sanitize_text_field( $settings[ $field ] );
            }
        }

        // Numeric fields
        if ( isset( $settings['max_tokens'] ) ) {
            $sanitized['max_tokens'] = max( 100, min( 2000, absint( $settings['max_tokens'] ) ) );
        }

        if ( isset( $settings['temperature'] ) ) {
            $sanitized['temperature'] = max( 0, min( 1, floatval( $settings['temperature'] ) ) );
        }

        if ( isset( $settings['max_context_chunks'] ) ) {
            $sanitized['max_context_chunks'] = max( 1, min( 20, absint( $settings['max_context_chunks'] ) ) );
        }

        if ( isset( $settings['confidence_threshold'] ) ) {
            $sanitized['confidence_threshold'] = max( 0, min( 1, floatval( $settings['confidence_threshold'] ) ) );
        }

        if ( isset( $settings['rate_limit'] ) ) {
            $sanitized['rate_limit'] = max( 5, min( 100, absint( $settings['rate_limit'] ) ) );
        }

        if ( isset( $settings['rate_limit_window'] ) ) {
            $sanitized['rate_limit_window'] = max( 1, min( 1440, absint( $settings['rate_limit_window'] ) ) );
        }

        if ( isset( $settings['chunk_size'] ) ) {
            $sanitized['chunk_size'] = max( 100, min( 2000, absint( $settings['chunk_size'] ) ) );
        }

        // Text fields
        $text_fields = array( 'system_prompt', 'welcome_message', 'fallback_message', 'widget_title', 'widget_subtitle' );
        foreach ( $text_fields as $field ) {
            if ( isset( $settings[ $field ] ) ) {
                $sanitized[ $field ] = sanitize_textarea_field( $settings[ $field ] );
            }
        }

        // Color
        if ( isset( $settings['widget_color'] ) ) {
            $sanitized['widget_color'] = sanitize_hex_color( $settings['widget_color'] ) ?: '#10B981';
        }

        // Select fields
        if ( isset( $settings['widget_position'] ) ) {
            $sanitized['widget_position'] = in_array( $settings['widget_position'], array( 'bottom-right', 'bottom-left' ), true )
                ? $settings['widget_position'] : 'bottom-right';
        }

        if ( isset( $settings['widget_icon'] ) ) {
            $sanitized['widget_icon'] = in_array( $settings['widget_icon'], array( 'chat', 'bot', 'support', 'message' ), true )
                ? $settings['widget_icon'] : 'chat';
        }

        if ( isset( $settings['dark_mode'] ) ) {
            $sanitized['dark_mode'] = in_array( $settings['dark_mode'], array( 'auto', 'light', 'dark' ), true )
                ? $settings['dark_mode'] : 'auto';
        }

        // Boolean fields
        $bool_fields = array( 'show_on_mobile', 'show_branding', 'email_fallback', 'auto_index', 'sound_enabled', 'typing_indicator' );
        foreach ( $bool_fields as $field ) {
            if ( isset( $settings[ $field ] ) ) {
                $sanitized[ $field ] = (bool) $settings[ $field ];
            }
        }

        // Email
        if ( isset( $settings['fallback_email'] ) ) {
            $sanitized['fallback_email'] = sanitize_email( $settings['fallback_email'] );
        }

        // Arrays
        if ( isset( $settings['content_types'] ) && is_array( $settings['content_types'] ) ) {
            $sanitized['content_types'] = array_map( 'sanitize_text_field', $settings['content_types'] );
        }

        if ( isset( $settings['excluded_posts'] ) && is_array( $settings['excluded_posts'] ) ) {
            $sanitized['excluded_posts'] = array_map( 'absint', $settings['excluded_posts'] );
        }

        if ( isset( $settings['suggestion_chips'] ) && is_array( $settings['suggestion_chips'] ) ) {
            $sanitized['suggestion_chips'] = array_map( 'sanitize_text_field', $settings['suggestion_chips'] );
        }

        return $sanitized;
    }

    /**
     * Sanitize a Q&A pair.
     *
     * @param array $qa Raw Q&A data.
     * @return array|null Sanitized data or null if invalid.
     */
    public static function sanitize_qa( $qa ) {
        if ( empty( $qa['question'] ) || empty( $qa['answer'] ) ) {
            return null;
        }

        return array(
            'question' => sanitize_textarea_field( $qa['question'] ),
            'answer'   => sanitize_textarea_field( $qa['answer'] ),
            'priority' => isset( $qa['priority'] ) ? absint( $qa['priority'] ) : 0,
            'status'   => isset( $qa['status'] ) && in_array( $qa['status'], array( 'active', 'inactive' ), true )
                ? $qa['status'] : 'active',
        );
    }
}
