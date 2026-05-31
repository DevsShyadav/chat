<?php
/**
 * Gemini Provider - Implementation for Google Gemini API.
 *
 * @package WPAICB\AI
 */

namespace WPAICB\AI;

use WPAICB\Admin\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Gemini_Provider implements Provider_Interface {

    /**
     * API base URL.
     *
     * @var string
     */
    private $api_base = 'https://generativelanguage.googleapis.com/v1beta/models/';

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
        $this->api_key = Admin::get_settings( 'gemini_api_key', '' );
        $this->model   = Admin::get_settings( 'gemini_model', 'gemini-pro' );
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
                'success'     => false,
                'error'       => __( 'Gemini API key is not configured.', 'wp-ai-chatbot' ),
                'content'     => '',
                'tokens_used' => 0,
            );
        }

        $defaults = array(
            'temperature' => (float) Admin::get_settings( 'temperature', 0.7 ),
            'max_tokens'  => (int) Admin::get_settings( 'max_tokens', 500 ),
        );

        $options = wp_parse_args( $options, $defaults );

        // Build Gemini-format messages
        $contents = array();

        // Add system prompt as first user context if provided
        if ( ! empty( $system_prompt ) ) {
            $contents[] = array(
                'role'  => 'user',
                'parts' => array( array( 'text' => '[System Instructions]: ' . $system_prompt ) ),
            );
            $contents[] = array(
                'role'  => 'model',
                'parts' => array( array( 'text' => 'Understood. I will follow these instructions.' ) ),
            );
        }

        foreach ( $messages as $msg ) {
            $role = 'user' === $msg['role'] ? 'user' : 'model';

            // Gemini requires alternating user/model turns - merge consecutive same-role messages
            if ( ! empty( $contents ) ) {
                $last_index = count( $contents ) - 1;
                if ( $contents[ $last_index ]['role'] === $role ) {
                    $contents[ $last_index ]['parts'][0]['text'] .= "\n" . $msg['content'];
                    continue;
                }
            }

            $contents[] = array(
                'role'  => $role,
                'parts' => array( array( 'text' => $msg['content'] ) ),
            );
        }

        $body = array(
            'contents'         => $contents,
            'generationConfig' => array(
                'temperature'     => $options['temperature'],
                'maxOutputTokens' => $options['max_tokens'],
                'topP'            => 0.95,
                'topK'            => 40,
            ),
        );

        $url = $this->api_base . $this->model . ':generateContent?key=' . $this->api_key;

        $response = wp_remote_post( $url, array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
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
            $error_message = isset( $response_body['error']['message'] ) ? $response_body['error']['message'] : __( 'Unknown Gemini API error', 'wp-ai-chatbot' );
            return array(
                'success'     => false,
                'error'       => $error_message,
                'content'     => '',
                'tokens_used' => 0,
            );
        }

        $content = '';
        if ( isset( $response_body['candidates'][0]['content']['parts'][0]['text'] ) ) {
            $content = $response_body['candidates'][0]['content']['parts'][0]['text'];
        }

        // Gemini returns token count in usageMetadata
        $tokens_used = 0;
        if ( isset( $response_body['usageMetadata']['totalTokenCount'] ) ) {
            $tokens_used = (int) $response_body['usageMetadata']['totalTokenCount'];
        }

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
            '',
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
        return 'Google Gemini';
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
