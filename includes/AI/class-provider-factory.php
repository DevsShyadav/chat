<?php
/**
 * Provider Factory - Creates the appropriate AI provider instance.
 *
 * @package WPAICB\AI
 */

namespace WPAICB\AI;

use WPAICB\Admin\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Provider_Factory {

    /**
     * Create an AI provider instance based on settings.
     *
     * @param string $provider Provider name override (optional).
     * @return Provider_Interface|null
     */
    public static function create( $provider = '' ) {
        if ( empty( $provider ) ) {
            $provider = Admin::get_settings( 'ai_provider', 'openai' );
        }

        switch ( $provider ) {
            case 'openai':
                return new Openai_Provider();
            case 'gemini':
                return new Gemini_Provider();
            case 'groq':
                return new Groq_Provider();
            default:
                return null;
        }
    }

    /**
     * Get all available providers.
     *
     * @return array
     */
    public static function get_available_providers() {
        return array(
            'openai' => array(
                'name'        => 'OpenAI',
                'description' => 'GPT-5.4, GPT-5.4 Mini, o4-mini',
                'url'         => 'https://platform.openai.com/api-keys',
            ),
            'gemini' => array(
                'name'        => 'Google Gemini',
                'description' => 'Gemini 2.5 Flash/Pro, 3.5 Flash',
                'url'         => 'https://aistudio.google.com/app/apikey',
            ),
            'groq' => array(
                'name'        => 'Groq',
                'description' => 'LLaMA 4, DeepSeek R1, Qwen (Fastest)',
                'url'         => 'https://console.groq.com/keys',
            ),
        );
    }
}
