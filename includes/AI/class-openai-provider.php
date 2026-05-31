<?php
/**
 * OpenAI Provider - Implementation for OpenAI API.
 *
 * @package WPAICB\AI
 */

namespace WPAICB\AI;

use WPAICB\Admin\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Openai_Provider implements Provider_Interface {

    /**
     * API base URL.
     *
     * @var string
     */
    private $api_url = 'https://api.openai.com/v1/chat/completions';

    /**
     * API key.
     *
     * @var string
     */
    private $api_key;

    /**
     * Model name.
     *
     * @var string
     */
    private $model;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->api_key = Admin::get_settings( 'openai_api_key', '' );
        $this->model   = Admin::get_settings( 'openai_model', 'gpt-3.5-turbo' );
    }

    /**
     * Send a chat completion request.
     *
     * @param array  $messages Array of message objects.
     * @param string $system_prompt System prompt.
     * @param array  $options Additional options.
     * @return array Response array.
     */
    public function chat( $messages, $system_prompt = '', $options = array() ) {
        if ( ! $this->is_configured() ) {
            return array(
                'success' => false,
                'error'   => __( 'OpenAI API key is not configured.', 'wp-ai-chatbot' ),
                'content' => '',
                'tokens_used' => 0,
            );
        }

        $defaults = array(
            'temperature' => (float) Admin::get_settings( 'temperature', 0.7 ),
            'max_tokens'  => (int) Admin::get_settings( 'max_tokens', 500 ),
        );

        $options = wp_parse_args( $options, $defaults );

        // Build messages array with system prompt
        $api_messages = array();

        if ( ! empty( $system_prompt ) ) {
            $api_messages[] = array(
                'role'    => 'system',
                'content' => $system_prompt,
            );
        }

        foreach ( $messages as $msg ) {
            $api_messages[] = array(
                'role'    => sanitize_text_field( $msg['role'] ),
                'content' => $msg['content'],
            );
        }

        $body = array(
            'model'       => $this->model,
            'messages'    => $api_messages,
            'temperature' => $options['temperature'],
            'max_tokens'  => $options['max_tokens'],
        );

        $response = wp_remote_post( $this->api_url, array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
            ),
            'body' => wp_json_encode( $body ),
        ) );

        if ( is_wp_error( $response ) ) {
            return array(
                'success'     => false,
                'error'       => $response->get_error_message(),
                'content'     => '',
                'tokens_used' => 0,
            );
        }

        $status_code   = wp_remote_retrieve_response_code( $response );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 !== $status_code ) {
            $error_message = isset( $response_body['error']['message'] ) ? $response_body['error']['message'] : __( 'Unknown API error', 'wp-ai-chatbot' );
            return array(
                'success'     => false,
                'error'       => $error_message,
                'content'     => '',
                'tokens_used' => 0,
            );
        }

        $content     = isset( $response_body['choices'][0]['message']['content'] ) ? $response_body['choices'][0]['message']['content'] : '';
        $tokens_used = isset( $response_body['usage']['total_tokens'] ) ? (int) $response_body['usage']['total_tokens'] : 0;

        return array(
            'success'     => true,
            'content'     => $content,
            'tokens_used' => $tokens_used,
            'error'       => '',
        );
    }

    /**
     * Test the API connection.
     *
     * @return array
     */
    public function test_connection() {
        if ( ! $this->is_configured() ) {
            return array(
                'success' => false,
                'message' => __( 'API key is not set.', 'wp-ai-chatbot' ),
            );
        }

        $result = $this->chat(
            array( array( 'role' => 'user', 'content' => 'Say "Connection successful!" in exactly those words.' ) ),
            'You are a test assistant. Respond with exactly what is asked.',
            array( 'max_tokens' => 20 )
        );

        if ( $result['success'] ) {
            return array(
                'success' => true,
                'message' => __( 'Connection successful! Model: ', 'wp-ai-chatbot' ) . $this->model,
            );
        }

        return array(
            'success' => false,
            'message' => $result['error'],
        );
    }

    /**
     * Get the provider name.
     *
     * @return string
     */
    public function get_name() {
        return 'OpenAI';
    }

    /**
     * Get the current model.
     *
     * @return string
     */
    public function get_model() {
        return $this->model;
    }

    /**
     * Check if configured.
     *
     * @return bool
     */
    public function is_configured() {
        return ! empty( $this->api_key );
    }
}
