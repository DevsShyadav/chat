<?php
/**
 * Settings - Admin settings page controller.
 *
 * @package WPAICB\Admin
 */

namespace WPAICB\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Settings {

    /**
     * Render the settings page.
     */
    public function render() {
        $settings = Admin::get_settings();

        // Available AI models per provider (updated May 2026)
        $models = array(
            'openai' => array(
                'gpt-5.4-mini'     => 'GPT-5.4 Mini (Best Value - Recommended)',
                'gpt-5.4'          => 'GPT-5.4 (Most Capable)',
                'gpt-4.1-mini'     => 'GPT-4.1 Mini (Fast & Budget)',
                'gpt-4.1'          => 'GPT-4.1 (Reliable)',
                'gpt-4o-mini'      => 'GPT-4o Mini (Legacy Budget)',
                'gpt-4o'           => 'GPT-4o (Legacy)',
                'o4-mini'          => 'o4-mini (Reasoning)',
                'o3-mini'          => 'o3-mini (Reasoning Budget)',
            ),
            'gemini' => array(
                'gemini-2.5-flash' => 'Gemini 2.5 Flash (Fast - Recommended)',
                'gemini-2.5-pro'   => 'Gemini 2.5 Pro (Most Capable)',
                'gemini-2.0-flash' => 'Gemini 2.0 Flash (Stable)',
                'gemini-3.5-flash' => 'Gemini 3.5 Flash (Newest)',
            ),
            'groq' => array(
                'llama-4-scout-17b-16e-instruct'  => 'LLaMA 4 Scout 17B (Fast - Recommended)',
                'llama-4-maverick-17b-128e-instruct' => 'LLaMA 4 Maverick 17B (Capable)',
                'deepseek-r1-distill-llama-70b'   => 'DeepSeek R1 70B (Reasoning)',
                'qwen-qwq-32b'                    => 'Qwen QwQ 32B (Reasoning)',
                'llama-3.3-70b-versatile'         => 'LLaMA 3.3 70B (Versatile)',
                'llama-3.1-8b-instant'            => 'LLaMA 3.1 8B (Fastest)',
                'mixtral-8x7b-32768'              => 'Mixtral 8x7B (32K Context)',
                'gemma2-9b-it'                    => 'Gemma 2 9B (Compact)',
            ),
        );

        // Widget position options
        $positions = array(
            'bottom-right' => __( 'Bottom Right', 'wp-ai-chatbot' ),
            'bottom-left'  => __( 'Bottom Left', 'wp-ai-chatbot' ),
        );

        // Widget icon options
        $icons = array(
            'chat'     => __( 'Chat Bubble', 'wp-ai-chatbot' ),
            'bot'      => __( 'Robot', 'wp-ai-chatbot' ),
            'support'  => __( 'Support', 'wp-ai-chatbot' ),
            'message'  => __( 'Message', 'wp-ai-chatbot' ),
        );

        // Content types available for indexing
        $post_types = get_post_types( array( 'public' => true ), 'objects' );

        include WPAICB_PLUGIN_DIR . 'templates/admin/settings.php';
    }
}
