<?php
/**
 * Chat Controller - Handles the chat logic flow.
 *
 * @package WPAICB\Chat
 */

namespace WPAICB\Chat;

use WPAICB\Admin\Admin;
use WPAICB\AI\Provider_Factory;
use WPAICB\AI\Response_Handler;
use WPAICB\Content\Retriever;
use WPAICB\Database\Conversation_Model;
use WPAICB\Database\Message_Model;
use WPAICB\Database\Analytics_Model;
use WPAICB\Security\Rate_Limiter;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Chat_Controller {

    /**
     * Conversation model.
     *
     * @var Conversation_Model
     */
    private $conversation_model;

    /**
     * Message model.
     *
     * @var Message_Model
     */
    private $message_model;

    /**
     * Analytics model.
     *
     * @var Analytics_Model
     */
    private $analytics_model;

    /**
     * Content retriever.
     *
     * @var Retriever
     */
    private $retriever;

    /**
     * Response handler.
     *
     * @var Response_Handler
     */
    private $response_handler;

    /**
     * Rate limiter.
     *
     * @var Rate_Limiter
     */
    private $rate_limiter;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->conversation_model = new Conversation_Model();
        $this->message_model      = new Message_Model();
        $this->analytics_model    = new Analytics_Model();
        $this->retriever          = new Retriever();
        $this->response_handler   = new Response_Handler();
        $this->rate_limiter       = new Rate_Limiter();
    }

    /**
     * Process a user message and return AI response.
     *
     * @param string $message User's message.
     * @param string $session_id Session identifier.
     * @param string $page_url Page where the chat was initiated.
     * @return array Response array.
     */
    public function process_message( $message, $session_id, $page_url = '' ) {
        // Rate limiting check
        if ( ! $this->rate_limiter->check( $session_id ) ) {
            return array(
                'success'    => false,
                'error'      => 'rate_limited',
                'message'    => __( 'You\'re sending messages too quickly. Please wait a moment.', 'wp-ai-chatbot' ),
            );
        }

        // Get or create conversation
        $conversation = $this->get_or_create_conversation( $session_id, $page_url );

        if ( ! $conversation ) {
            return array(
                'success' => false,
                'error'   => 'conversation_error',
                'message' => __( 'Failed to create conversation.', 'wp-ai-chatbot' ),
            );
        }

        // Store user message
        $this->message_model->create( array(
            'conversation_id' => $conversation->id,
            'role'            => 'user',
            'content'         => $message,
        ) );

        // Track analytics
        $this->analytics_model->record( 'message_sent', $message, $session_id );

        // Retrieve relevant context
        $context_chunks = $this->retriever->get_relevant_context( $message );

        // Get conversation history for context (exclude the message we just stored)
        $history  = $this->message_model->get_recent( $conversation->id, 10 );
        // Remove the last message from history since build_messages will add it
        if ( ! empty( $history ) ) {
            $last = end( $history );
            if ( 'user' === $last->role && $last->content === $message ) {
                array_pop( $history );
            }
        }
        $messages = $this->response_handler->build_messages( $history, $message );

        // Build system prompt with context
        $system_prompt = $this->response_handler->build_system_prompt( $context_chunks );

        // Get AI provider and generate response
        $provider = Provider_Factory::create();

        if ( ! $provider || ! $provider->is_configured() ) {
            return array(
                'success' => false,
                'error'   => 'not_configured',
                'message' => __( 'AI provider is not configured.', 'wp-ai-chatbot' ),
            );
        }

        $ai_response = $provider->chat( $messages, $system_prompt );

        // Process the response (confidence scoring, formatting)
        $processed = $this->response_handler->process( $ai_response, $context_chunks );

        // Store assistant message
        $this->message_model->create( array(
            'conversation_id'  => $conversation->id,
            'role'             => 'assistant',
            'content'          => $processed['content'],
            'confidence_score' => $processed['confidence_score'],
            'tokens_used'      => $processed['tokens_used'],
        ) );

        // Handle fallback scenario
        if ( $processed['is_fallback'] ) {
            $this->analytics_model->record( 'fallback_triggered', $message, $session_id );

            $this->conversation_model->update( $conversation->id, array(
                'status' => 'escalated',
            ) );
        }

        return array(
            'success'          => true,
            'message'          => $processed['content'],
            'confidence_score' => $processed['confidence_score'],
            'is_fallback'      => $processed['is_fallback'],
            'conversation_id'  => $conversation->id,
            'session_id'       => $session_id,
        );
    }

    /**
     * Get or create a conversation for the session.
     *
     * @param string $session_id Session ID.
     * @param string $page_url Current page URL.
     * @return object|null Conversation object.
     */
    private function get_or_create_conversation( $session_id, $page_url = '' ) {
        $conversation = $this->conversation_model->get_by_session( $session_id );

        if ( $conversation ) {
            // Reopen if closed
            if ( 'closed' === $conversation->status ) {
                $this->conversation_model->update( $conversation->id, array(
                    'status' => 'active',
                ) );
            }
            return $conversation;
        }

        // Create new conversation
        $conv_id = $this->conversation_model->create( array(
            'session_id' => $session_id,
            'page_url'   => $page_url,
            'status'     => 'active',
        ) );

        if ( ! $conv_id ) {
            return null;
        }

        // Track new conversation
        $this->analytics_model->record( 'conversation_started', $page_url, $session_id );

        return $this->conversation_model->get( $conv_id );
    }

    /**
     * Submit visitor email for fallback.
     *
     * @param string $session_id Session ID.
     * @param string $email Visitor email.
     * @param string $name Visitor name.
     * @return array
     */
    public function submit_email( $session_id, $email, $name = '' ) {
        $conversation = $this->conversation_model->get_by_session( $session_id );

        if ( ! $conversation ) {
            return array(
                'success' => false,
                'message' => __( 'Conversation not found.', 'wp-ai-chatbot' ),
            );
        }

        // Update conversation with email
        $this->conversation_model->update( $conversation->id, array(
            'visitor_email' => $email,
            'visitor_name'  => $name,
            'status'        => 'escalated',
        ) );

        // Track email capture
        $this->analytics_model->record( 'email_captured', $email, $session_id );

        // Trigger email fallback
        $email_fallback = new Email_Fallback();
        $email_fallback->send_notification( $conversation->id, $email, $name );

        return array(
            'success' => true,
            'message' => __( 'Thanks! We\'ll get back to you soon.', 'wp-ai-chatbot' ),
        );
    }

    /**
     * Submit satisfaction rating.
     *
     * @param string $session_id Session ID.
     * @param int    $rating Rating (1-5).
     * @return array
     */
    public function submit_rating( $session_id, $rating ) {
        $conversation = $this->conversation_model->get_by_session( $session_id );

        if ( ! $conversation ) {
            return array(
                'success' => false,
                'message' => __( 'Conversation not found.', 'wp-ai-chatbot' ),
            );
        }

        $this->conversation_model->update( $conversation->id, array(
            'satisfaction_rating' => min( 5, max( 1, $rating ) ),
            'status'             => 'closed',
        ) );

        $this->analytics_model->record( 'rating_given', $rating, $session_id );

        return array(
            'success' => true,
            'message' => __( 'Thank you for your feedback!', 'wp-ai-chatbot' ),
        );
    }

    /**
     * Start a new conversation (reset session).
     *
     * @param string $old_session_id Current session ID.
     * @return array New session data.
     */
    public function new_conversation( $old_session_id ) {
        // Close old conversation
        $old_conversation = $this->conversation_model->get_by_session( $old_session_id );
        if ( $old_conversation ) {
            $this->conversation_model->update( $old_conversation->id, array(
                'status' => 'closed',
            ) );
        }

        // Generate new session
        $new_session_id = wp_generate_uuid4();

        return array(
            'success'    => true,
            'session_id' => $new_session_id,
        );
    }
}
