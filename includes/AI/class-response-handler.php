<?php
/**
 * Response Handler - Processes AI responses and determines confidence.
 *
 * @package WPAICB\AI
 */

namespace WPAICB\AI;

use WPAICB\Admin\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Response_Handler {

    /**
     * Uncertain phrases that indicate the AI doesn't know the answer.
     *
     * @var array
     */
    private $uncertain_phrases = array(
        'i don\'t have',
        'i\'m not sure',
        'i cannot find',
        'i don\'t know',
        'not mentioned',
        'no information',
        'cannot determine',
        'unable to find',
        'not available in',
        'i apologize',
        'i\'m sorry, i',
        'based on the provided context, i cannot',
        'the context does not',
        'there is no mention',
        'i don\'t have enough information',
        'not specified in',
        'i couldn\'t find',
    );

    /**
     * Process an AI response and calculate confidence.
     *
     * @param array $ai_response Raw AI response from provider.
     * @param array $context_chunks Context chunks used for the query.
     * @return array Processed response with confidence score.
     */
    public function process( $ai_response, $context_chunks = array() ) {
        if ( ! $ai_response['success'] ) {
            return array(
                'content'          => '',
                'confidence_score' => 0,
                'tokens_used'      => 0,
                'is_fallback'      => true,
                'error'            => $ai_response['error'],
            );
        }

        $content          = $ai_response['content'];
        $confidence_score = $this->calculate_confidence( $content, $context_chunks );
        $threshold        = (float) Admin::get_settings( 'confidence_threshold', 0.3 );
        $is_fallback      = $confidence_score < $threshold;

        return array(
            'content'          => $this->format_response( $content ),
            'confidence_score' => $confidence_score,
            'tokens_used'      => $ai_response['tokens_used'],
            'is_fallback'      => $is_fallback,
            'error'            => '',
        );
    }

    /**
     * Calculate confidence score for a response.
     *
     * @param string $content Response content.
     * @param array  $context_chunks Context used.
     * @return float Score between 0 and 1.
     */
    private function calculate_confidence( $content, $context_chunks ) {
        $score = 1.0;
        $content_lower = strtolower( $content );

        // Check for uncertain phrases
        foreach ( $this->uncertain_phrases as $phrase ) {
            if ( strpos( $content_lower, $phrase ) !== false ) {
                $score -= 0.4;
                break;
            }
        }

        // Factor in context availability
        if ( empty( $context_chunks ) ) {
            $score -= 0.3;
        }

        // Very short responses might indicate uncertainty
        $word_count = str_word_count( $content );
        if ( $word_count < 10 ) {
            $score -= 0.1;
        }

        // If response contains a question back to the user, might be uncertain
        if ( substr_count( $content, '?' ) > 1 ) {
            $score -= 0.1;
        }

        return max( 0, min( 1, round( $score, 2 ) ) );
    }

    /**
     * Format the response content for display.
     *
     * @param string $content Raw response content.
     * @return string Formatted content.
     */
    private function format_response( $content ) {
        // Trim whitespace
        $content = trim( $content );

        // Convert markdown-style bold to HTML
        $content = preg_replace( '/\*\*(.*?)\*\*/', '<strong>$1</strong>', $content );

        // Convert markdown-style links to HTML
        $content = preg_replace( '/\[(.*?)\]\((.*?)\)/', '<a href="$2" target="_blank" rel="noopener">$1</a>', $content );

        // Convert markdown-style lists
        $content = preg_replace( '/^[\-\*]\s+(.*)$/m', '<li>$1</li>', $content );
        if ( strpos( $content, '<li>' ) !== false ) {
            // Wrap consecutive <li> groups in <ul> (without /s to avoid wrapping across paragraphs)
            $content = preg_replace( '/((?:<li>.*?<\/li>\s*)+)/m', '<ul>$1</ul>', $content );
        }

        return $content;
    }

    /**
     * Build the system prompt with site context.
     *
     * @param array $context_chunks Relevant content chunks.
     * @return string Complete system prompt.
     */
    public function build_system_prompt( $context_chunks = array() ) {
        $base_prompt = Admin::get_settings( 'system_prompt', '' );
        $site_name   = get_bloginfo( 'name' );

        // Replace placeholders
        $prompt = str_replace( '{site_name}', $site_name, $base_prompt );

        // Append context if available
        if ( ! empty( $context_chunks ) ) {
            $prompt .= "\n\n---\nRelevant information from the website:\n\n";

            foreach ( $context_chunks as $index => $chunk ) {
                $title = isset( $chunk->title ) ? $chunk->title : '';
                $text  = isset( $chunk->chunk_text ) ? $chunk->chunk_text : '';

                $prompt .= sprintf( "[Source %d: %s]\n%s\n\n", $index + 1, $title, $text );
            }

            $prompt .= "---\nUse the above information to answer the user's question. If the answer is not found in the provided information, say so honestly.";
        }

        return $prompt;
    }

    /**
     * Build conversation messages for the AI from stored messages.
     *
     * @param array  $stored_messages Messages from database.
     * @param string $new_message The new user message.
     * @return array Formatted messages array.
     */
    public function build_messages( $stored_messages, $new_message ) {
        $messages = array();

        // Add recent conversation history (last 6 messages for context)
        $recent = array_slice( $stored_messages, -6 );
        foreach ( $recent as $msg ) {
            if ( 'system' === $msg->role ) {
                continue;
            }
            $messages[] = array(
                'role'    => $msg->role,
                'content' => $msg->content,
            );
        }

        // Add new message
        $messages[] = array(
            'role'    => 'user',
            'content' => $new_message,
        );

        return $messages;
    }
}
