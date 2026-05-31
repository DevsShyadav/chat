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

        // Available AI models per provider
        $models = array(
            'openai' => array(
                'gpt-3.5-turbo'    => 'GPT-3.5 Turbo (Fast & Affordable)',
                'gpt-4'            => 'GPT-4 (Most Capable)',
                'gpt-4-turbo'      => 'GPT-4 Turbo (Fast & Capable)',
                'gpt-4o'           => 'GPT-4o (Latest)',
                'gpt-4o-mini'      => 'GPT-4o Mini (Budget)',
            ),
            'gemini' => array(
                'gemini-pro'       => 'Gemini Pro',
                'gemini-1.5-pro'   => 'Gemini 1.5 Pro',
                'gemini-1.5-flash' => 'Gemini 1.5 Flash (Fast)',
            ),
            'groq' => array(
                'llama3-8b-8192'    => 'LLaMA 3 8B (Fast)',
                'llama3-70b-8192'   => 'LLaMA 3 70B (Capable)',
                'mixtral-8x7b-32768' => 'Mixtral 8x7B',
                'gemma-7b-it'       => 'Gemma 7B',
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
