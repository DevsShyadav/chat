<?php
/**
 * Provider Interface - Contract for all AI providers.
 *
 * @package WPAICB\AI
 */

namespace WPAICB\AI;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

interface Provider_Interface {

    /**
     * Send a chat completion request.
     *
     * @param array  $messages Array of message objects with 'role' and 'content'.
     * @param string $system_prompt System prompt for the AI.
     * @param array  $options Additional options (temperature, max_tokens, etc).
     * @return array Response array with 'content', 'tokens_used', 'success', 'error'.
     */
    public function chat( $messages, $system_prompt = '', $options = array() );

    /**
     * Test the API connection.
     *
     * @return array Array with 'success' and 'message' keys.
     */
    public function test_connection();

    /**
     * Get the provider name.
     *
     * @return string
     */
    public function get_name();

    /**
     * Get the current model being used.
     *
     * @return string
     */
    public function get_model();

    /**
     * Check if the provider is properly configured.
     *
     * @return bool
     */
    public function is_configured();
}
