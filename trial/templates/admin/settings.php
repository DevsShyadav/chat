<?php
/**
 * Admin Settings Template.
 *
 * @package WPAICB
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wpaicb-admin-wrap">
    <div class="wpaicb-admin-header">
        <div class="wpaicb-header-left">
            <h1 class="wpaicb-page-title">
                <span class="wpaicb-logo-icon">⚙️</span>
                <?php esc_html_e( 'Settings', 'wp-ai-chatbot' ); ?>
            </h1>
            <p class="wpaicb-page-subtitle"><?php esc_html_e( 'Configure your AI chatbot preferences', 'wp-ai-chatbot' ); ?></p>
        </div>
    </div>

    <div class="wpaicb-settings-wrap">
        <!-- Settings Tabs -->
        <div class="wpaicb-tabs">
            <button class="wpaicb-tab wpaicb-tab-active" data-tab="api-keys">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path></svg>
                <?php esc_html_e( 'API Keys', 'wp-ai-chatbot' ); ?>
            </button>
            <button class="wpaicb-tab" data-tab="widget">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                <?php esc_html_e( 'Widget', 'wp-ai-chatbot' ); ?>
            </button>
            <button class="wpaicb-tab" data-tab="behavior">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68 1.65 1.65 0 0 0 10 3.17V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                <?php esc_html_e( 'Behavior', 'wp-ai-chatbot' ); ?>
            </button>
            <button class="wpaicb-tab" data-tab="email">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                <?php esc_html_e( 'Email Fallback', 'wp-ai-chatbot' ); ?>
            </button>
        </div>

        <form id="wpaicb-settings-form" class="wpaicb-settings-form" method="post">
            <?php wp_nonce_field( 'wpaicb_save_settings', 'wpaicb_settings_nonce' ); ?>

            <!-- API Keys Tab -->
            <div class="wpaicb-tab-content wpaicb-tab-content-active" data-tab="api-keys">
                <div class="wpaicb-card">
                    <div class="wpaicb-card-header">
                        <h3><?php esc_html_e( 'AI Provider Configuration', 'wp-ai-chatbot' ); ?></h3>
                        <p class="wpaicb-card-description"><?php esc_html_e( 'Choose your AI provider and enter your API key. You only need one provider.', 'wp-ai-chatbot' ); ?></p>
                    </div>
                    <div class="wpaicb-card-body">
                        <!-- Provider Selection -->
                        <div class="wpaicb-field-group">
                            <label class="wpaicb-label"><?php esc_html_e( 'AI Provider', 'wp-ai-chatbot' ); ?></label>
                            <div class="wpaicb-provider-cards">
                                <label class="wpaicb-provider-card <?php echo 'openai' === $settings['ai_provider'] ? 'wpaicb-provider-active' : ''; ?>">
                                    <input type="radio" name="wpaicb[ai_provider]" value="openai" <?php checked( $settings['ai_provider'], 'openai' ); ?>>
                                    <span class="wpaicb-provider-name">OpenAI</span>
                                    <span class="wpaicb-provider-desc">GPT-3.5 / GPT-4</span>
                                </label>
                                <label class="wpaicb-provider-card <?php echo 'gemini' === $settings['ai_provider'] ? 'wpaicb-provider-active' : ''; ?>">
                                    <input type="radio" name="wpaicb[ai_provider]" value="gemini" <?php checked( $settings['ai_provider'], 'gemini' ); ?>>
                                    <span class="wpaicb-provider-name">Google Gemini</span>
                                    <span class="wpaicb-provider-desc">Gemini Pro / Flash</span>
                                </label>
                                <label class="wpaicb-provider-card <?php echo 'groq' === $settings['ai_provider'] ? 'wpaicb-provider-active' : ''; ?>">
                                    <input type="radio" name="wpaicb[ai_provider]" value="groq" <?php checked( $settings['ai_provider'], 'groq' ); ?>>
                                    <span class="wpaicb-provider-name">Groq</span>
                                    <span class="wpaicb-provider-desc">LLaMA 3 / Mixtral</span>
                                </label>
                            </div>
                        </div>


                        <!-- OpenAI Settings -->
                        <div class="wpaicb-provider-settings" data-provider="openai" <?php echo 'openai' !== $settings['ai_provider'] ? 'style="display:none;"' : ''; ?>>
                            <div class="wpaicb-field-group">
                                <label class="wpaicb-label" for="wpaicb-openai-key"><?php esc_html_e( 'OpenAI API Key', 'wp-ai-chatbot' ); ?></label>
                                <div class="wpaicb-input-wrap">
                                    <input type="password" id="wpaicb-openai-key" name="wpaicb[openai_api_key]" value="<?php echo esc_attr( $settings['openai_api_key'] ); ?>" class="wpaicb-input" placeholder="sk-...">
                                    <button type="button" class="wpaicb-btn-icon wpaicb-toggle-password" title="<?php esc_attr_e( 'Show/Hide', 'wp-ai-chatbot' ); ?>">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    </button>
                                </div>
                                <p class="wpaicb-field-help">
                                    <?php
                                    printf(
                                        /* translators: %s: link to OpenAI */
                                        esc_html__( 'Get your API key from %s', 'wp-ai-chatbot' ),
                                        '<a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener">platform.openai.com</a>'
                                    );
                                    ?>
                                </p>
                            </div>
                            <div class="wpaicb-field-group">
                                <label class="wpaicb-label" for="wpaicb-openai-model"><?php esc_html_e( 'Model', 'wp-ai-chatbot' ); ?></label>
                                <select id="wpaicb-openai-model" name="wpaicb[openai_model]" class="wpaicb-select">
                                    <?php foreach ( $models['openai'] as $value => $label ) : ?>
                                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['openai_model'], $value ); ?>><?php echo esc_html( $label ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Gemini Settings -->
                        <div class="wpaicb-provider-settings" data-provider="gemini" <?php echo 'gemini' !== $settings['ai_provider'] ? 'style="display:none;"' : ''; ?>>
                            <div class="wpaicb-field-group">
                                <label class="wpaicb-label" for="wpaicb-gemini-key"><?php esc_html_e( 'Gemini API Key', 'wp-ai-chatbot' ); ?></label>
                                <div class="wpaicb-input-wrap">
                                    <input type="password" id="wpaicb-gemini-key" name="wpaicb[gemini_api_key]" value="<?php echo esc_attr( $settings['gemini_api_key'] ); ?>" class="wpaicb-input" placeholder="AI...">
                                    <button type="button" class="wpaicb-btn-icon wpaicb-toggle-password">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    </button>
                                </div>
                                <p class="wpaicb-field-help">
                                    <?php
                                    printf(
                                        esc_html__( 'Get your API key from %s', 'wp-ai-chatbot' ),
                                        '<a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener">Google AI Studio</a>'
                                    );
                                    ?>
                                </p>
                            </div>
                            <div class="wpaicb-field-group">
                                <label class="wpaicb-label" for="wpaicb-gemini-model"><?php esc_html_e( 'Model', 'wp-ai-chatbot' ); ?></label>
                                <select id="wpaicb-gemini-model" name="wpaicb[gemini_model]" class="wpaicb-select">
                                    <?php foreach ( $models['gemini'] as $value => $label ) : ?>
                                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['gemini_model'], $value ); ?>><?php echo esc_html( $label ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Groq Settings -->
                        <div class="wpaicb-provider-settings" data-provider="groq" <?php echo 'groq' !== $settings['ai_provider'] ? 'style="display:none;"' : ''; ?>>
                            <div class="wpaicb-field-group">
                                <label class="wpaicb-label" for="wpaicb-groq-key"><?php esc_html_e( 'Groq API Key', 'wp-ai-chatbot' ); ?></label>
                                <div class="wpaicb-input-wrap">
                                    <input type="password" id="wpaicb-groq-key" name="wpaicb[groq_api_key]" value="<?php echo esc_attr( $settings['groq_api_key'] ); ?>" class="wpaicb-input" placeholder="gsk_...">
                                    <button type="button" class="wpaicb-btn-icon wpaicb-toggle-password">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    </button>
                                </div>
                                <p class="wpaicb-field-help">
                                    <?php
                                    printf(
                                        esc_html__( 'Get your API key from %s', 'wp-ai-chatbot' ),
                                        '<a href="https://console.groq.com/keys" target="_blank" rel="noopener">console.groq.com</a>'
                                    );
                                    ?>
                                </p>
                            </div>
                            <div class="wpaicb-field-group">
                                <label class="wpaicb-label" for="wpaicb-groq-model"><?php esc_html_e( 'Model', 'wp-ai-chatbot' ); ?></label>
                                <select id="wpaicb-groq-model" name="wpaicb[groq_model]" class="wpaicb-select">
                                    <?php foreach ( $models['groq'] as $value => $label ) : ?>
                                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['groq_model'], $value ); ?>><?php echo esc_html( $label ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Test Connection Button -->
                        <div class="wpaicb-field-group">
                            <button type="button" id="wpaicb-test-connection" class="wpaicb-btn wpaicb-btn-secondary">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                <?php esc_html_e( 'Test Connection', 'wp-ai-chatbot' ); ?>
                            </button>
                            <span id="wpaicb-connection-status" class="wpaicb-connection-status"></span>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Widget Tab -->
            <div class="wpaicb-tab-content" data-tab="widget">
                <div class="wpaicb-card">
                    <div class="wpaicb-card-header">
                        <h3><?php esc_html_e( 'Widget Appearance', 'wp-ai-chatbot' ); ?></h3>
                        <p class="wpaicb-card-description"><?php esc_html_e( 'Customize how your chat widget looks on your website.', 'wp-ai-chatbot' ); ?></p>
                    </div>
                    <div class="wpaicb-card-body">
                        <div class="wpaicb-field-group">
                            <label class="wpaicb-label" for="wpaicb-widget-title"><?php esc_html_e( 'Widget Title', 'wp-ai-chatbot' ); ?></label>
                            <input type="text" id="wpaicb-widget-title" name="wpaicb[widget_title]" value="<?php echo esc_attr( $settings['widget_title'] ); ?>" class="wpaicb-input">
                        </div>

                        <div class="wpaicb-field-group">
                            <label class="wpaicb-label" for="wpaicb-widget-subtitle"><?php esc_html_e( 'Widget Subtitle', 'wp-ai-chatbot' ); ?></label>
                            <input type="text" id="wpaicb-widget-subtitle" name="wpaicb[widget_subtitle]" value="<?php echo esc_attr( $settings['widget_subtitle'] ); ?>" class="wpaicb-input">
                        </div>

                        <div class="wpaicb-field-group">
                            <label class="wpaicb-label" for="wpaicb-welcome-message"><?php esc_html_e( 'Welcome Message', 'wp-ai-chatbot' ); ?></label>
                            <textarea id="wpaicb-welcome-message" name="wpaicb[welcome_message]" class="wpaicb-textarea" rows="3"><?php echo esc_textarea( $settings['welcome_message'] ); ?></textarea>
                        </div>

                        <div class="wpaicb-field-row">
                            <div class="wpaicb-field-group wpaicb-field-half">
                                <label class="wpaicb-label" for="wpaicb-widget-color"><?php esc_html_e( 'Primary Color', 'wp-ai-chatbot' ); ?></label>
                                <div class="wpaicb-color-input-wrap">
                                    <input type="color" id="wpaicb-widget-color" name="wpaicb[widget_color]" value="<?php echo esc_attr( $settings['widget_color'] ); ?>" class="wpaicb-color-input">
                                    <input type="text" value="<?php echo esc_attr( $settings['widget_color'] ); ?>" class="wpaicb-input wpaicb-color-text" data-color-for="wpaicb-widget-color">
                                </div>
                            </div>

                            <div class="wpaicb-field-group wpaicb-field-half">
                                <label class="wpaicb-label" for="wpaicb-widget-position"><?php esc_html_e( 'Position', 'wp-ai-chatbot' ); ?></label>
                                <select id="wpaicb-widget-position" name="wpaicb[widget_position]" class="wpaicb-select">
                                    <?php foreach ( $positions as $value => $label ) : ?>
                                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['widget_position'], $value ); ?>><?php echo esc_html( $label ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="wpaicb-field-row">
                            <div class="wpaicb-field-group wpaicb-field-half">
                                <label class="wpaicb-label" for="wpaicb-widget-icon"><?php esc_html_e( 'Widget Icon', 'wp-ai-chatbot' ); ?></label>
                                <select id="wpaicb-widget-icon" name="wpaicb[widget_icon]" class="wpaicb-select">
                                    <?php foreach ( $icons as $value => $label ) : ?>
                                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['widget_icon'], $value ); ?>><?php echo esc_html( $label ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="wpaicb-field-group wpaicb-field-half">
                                <label class="wpaicb-label" for="wpaicb-dark-mode"><?php esc_html_e( 'Dark Mode', 'wp-ai-chatbot' ); ?></label>
                                <select id="wpaicb-dark-mode" name="wpaicb[dark_mode]" class="wpaicb-select">
                                    <option value="auto" <?php selected( $settings['dark_mode'], 'auto' ); ?>><?php esc_html_e( 'Auto (System)', 'wp-ai-chatbot' ); ?></option>
                                    <option value="light" <?php selected( $settings['dark_mode'], 'light' ); ?>><?php esc_html_e( 'Always Light', 'wp-ai-chatbot' ); ?></option>
                                    <option value="dark" <?php selected( $settings['dark_mode'], 'dark' ); ?>><?php esc_html_e( 'Always Dark', 'wp-ai-chatbot' ); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="wpaicb-field-group">
                            <label class="wpaicb-toggle-wrap">
                                <input type="checkbox" name="wpaicb[show_on_mobile]" value="1" <?php checked( $settings['show_on_mobile'], true ); ?>>
                                <span class="wpaicb-toggle"></span>
                                <span class="wpaicb-toggle-label"><?php esc_html_e( 'Show on Mobile Devices', 'wp-ai-chatbot' ); ?></span>
                            </label>
                        </div>

                        <div class="wpaicb-field-group">
                            <label class="wpaicb-toggle-wrap">
                                <input type="checkbox" name="wpaicb[sound_enabled]" value="1" <?php checked( $settings['sound_enabled'], true ); ?>>
                                <span class="wpaicb-toggle"></span>
                                <span class="wpaicb-toggle-label"><?php esc_html_e( 'Enable Sound Notifications', 'wp-ai-chatbot' ); ?></span>
                            </label>
                        </div>

                        <div class="wpaicb-field-group">
                            <label class="wpaicb-toggle-wrap">
                                <input type="checkbox" name="wpaicb[typing_indicator]" value="1" <?php checked( $settings['typing_indicator'], true ); ?>>
                                <span class="wpaicb-toggle"></span>
                                <span class="wpaicb-toggle-label"><?php esc_html_e( 'Show Typing Indicator', 'wp-ai-chatbot' ); ?></span>
                            </label>
                        </div>

                        <div class="wpaicb-field-group">
                            <label class="wpaicb-toggle-wrap">
                                <input type="checkbox" name="wpaicb[show_branding]" value="1" <?php checked( $settings['show_branding'], true ); ?>>
                                <span class="wpaicb-toggle"></span>
                                <span class="wpaicb-toggle-label"><?php esc_html_e( 'Show "Powered by" Branding', 'wp-ai-chatbot' ); ?></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Behavior Tab -->
            <div class="wpaicb-tab-content" data-tab="behavior">
                <div class="wpaicb-card">
                    <div class="wpaicb-card-header">
                        <h3><?php esc_html_e( 'AI Behavior', 'wp-ai-chatbot' ); ?></h3>
                        <p class="wpaicb-card-description"><?php esc_html_e( 'Control how your AI chatbot responds to visitors.', 'wp-ai-chatbot' ); ?></p>
                    </div>
                    <div class="wpaicb-card-body">
                        <div class="wpaicb-field-group">
                            <label class="wpaicb-label" for="wpaicb-system-prompt"><?php esc_html_e( 'System Prompt', 'wp-ai-chatbot' ); ?></label>
                            <textarea id="wpaicb-system-prompt" name="wpaicb[system_prompt]" class="wpaicb-textarea" rows="5"><?php echo esc_textarea( $settings['system_prompt'] ); ?></textarea>
                            <p class="wpaicb-field-help"><?php esc_html_e( 'Use {site_name} as a placeholder for your site name.', 'wp-ai-chatbot' ); ?></p>
                        </div>

                        <div class="wpaicb-field-group">
                            <label class="wpaicb-label" for="wpaicb-fallback-message"><?php esc_html_e( 'Fallback Message', 'wp-ai-chatbot' ); ?></label>
                            <textarea id="wpaicb-fallback-message" name="wpaicb[fallback_message]" class="wpaicb-textarea" rows="3"><?php echo esc_textarea( $settings['fallback_message'] ); ?></textarea>
                            <p class="wpaicb-field-help"><?php esc_html_e( 'Shown when the AI cannot answer a question confidently.', 'wp-ai-chatbot' ); ?></p>
                        </div>

                        <div class="wpaicb-field-row">
                            <div class="wpaicb-field-group wpaicb-field-half">
                                <label class="wpaicb-label" for="wpaicb-max-tokens"><?php esc_html_e( 'Max Response Length (tokens)', 'wp-ai-chatbot' ); ?></label>
                                <input type="number" id="wpaicb-max-tokens" name="wpaicb[max_tokens]" value="<?php echo esc_attr( $settings['max_tokens'] ); ?>" class="wpaicb-input" min="100" max="2000" step="50">
                            </div>

                            <div class="wpaicb-field-group wpaicb-field-half">
                                <label class="wpaicb-label" for="wpaicb-temperature"><?php esc_html_e( 'Creativity (Temperature)', 'wp-ai-chatbot' ); ?></label>
                                <input type="range" id="wpaicb-temperature" name="wpaicb[temperature]" value="<?php echo esc_attr( $settings['temperature'] ); ?>" class="wpaicb-range" min="0" max="1" step="0.1">
                                <div class="wpaicb-range-labels">
                                    <span><?php esc_html_e( 'Precise', 'wp-ai-chatbot' ); ?></span>
                                    <span class="wpaicb-range-value"><?php echo esc_html( $settings['temperature'] ); ?></span>
                                    <span><?php esc_html_e( 'Creative', 'wp-ai-chatbot' ); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="wpaicb-field-row">
                            <div class="wpaicb-field-group wpaicb-field-half">
                                <label class="wpaicb-label" for="wpaicb-max-context"><?php esc_html_e( 'Max Context Chunks', 'wp-ai-chatbot' ); ?></label>
                                <input type="number" id="wpaicb-max-context" name="wpaicb[max_context_chunks]" value="<?php echo esc_attr( $settings['max_context_chunks'] ); ?>" class="wpaicb-input" min="1" max="20" step="1">
                                <p class="wpaicb-field-help"><?php esc_html_e( 'Number of content chunks sent as context. More = better answers but higher cost.', 'wp-ai-chatbot' ); ?></p>
                            </div>

                            <div class="wpaicb-field-group wpaicb-field-half">
                                <label class="wpaicb-label" for="wpaicb-confidence"><?php esc_html_e( 'Confidence Threshold', 'wp-ai-chatbot' ); ?></label>
                                <input type="range" id="wpaicb-confidence" name="wpaicb[confidence_threshold]" value="<?php echo esc_attr( $settings['confidence_threshold'] ); ?>" class="wpaicb-range" min="0" max="1" step="0.1">
                                <div class="wpaicb-range-labels">
                                    <span><?php esc_html_e( 'Lenient', 'wp-ai-chatbot' ); ?></span>
                                    <span class="wpaicb-range-value"><?php echo esc_html( $settings['confidence_threshold'] ); ?></span>
                                    <span><?php esc_html_e( 'Strict', 'wp-ai-chatbot' ); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="wpaicb-field-row">
                            <div class="wpaicb-field-group wpaicb-field-half">
                                <label class="wpaicb-label" for="wpaicb-rate-limit"><?php esc_html_e( 'Rate Limit (messages)', 'wp-ai-chatbot' ); ?></label>
                                <input type="number" id="wpaicb-rate-limit" name="wpaicb[rate_limit]" value="<?php echo esc_attr( $settings['rate_limit'] ); ?>" class="wpaicb-input" min="5" max="100">
                            </div>

                            <div class="wpaicb-field-group wpaicb-field-half">
                                <label class="wpaicb-label" for="wpaicb-rate-window"><?php esc_html_e( 'Per (minutes)', 'wp-ai-chatbot' ); ?></label>
                                <input type="number" id="wpaicb-rate-window" name="wpaicb[rate_limit_window]" value="<?php echo esc_attr( $settings['rate_limit_window'] ); ?>" class="wpaicb-input" min="1" max="1440">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email Fallback Tab -->
            <div class="wpaicb-tab-content" data-tab="email">
                <div class="wpaicb-card">
                    <div class="wpaicb-card-header">
                        <h3><?php esc_html_e( 'Email Fallback', 'wp-ai-chatbot' ); ?></h3>
                        <p class="wpaicb-card-description"><?php esc_html_e( 'When the AI cannot answer a question, visitors can leave their email for human follow-up.', 'wp-ai-chatbot' ); ?></p>
                    </div>
                    <div class="wpaicb-card-body">
                        <div class="wpaicb-field-group">
                            <label class="wpaicb-toggle-wrap">
                                <input type="checkbox" name="wpaicb[email_fallback]" value="1" <?php checked( $settings['email_fallback'], true ); ?>>
                                <span class="wpaicb-toggle"></span>
                                <span class="wpaicb-toggle-label"><?php esc_html_e( 'Enable Email Fallback', 'wp-ai-chatbot' ); ?></span>
                            </label>
                        </div>

                        <div class="wpaicb-field-group">
                            <label class="wpaicb-label" for="wpaicb-fallback-email"><?php esc_html_e( 'Notification Email', 'wp-ai-chatbot' ); ?></label>
                            <input type="email" id="wpaicb-fallback-email" name="wpaicb[fallback_email]" value="<?php echo esc_attr( $settings['fallback_email'] ); ?>" class="wpaicb-input" placeholder="support@yoursite.com">
                            <p class="wpaicb-field-help"><?php esc_html_e( 'You will receive an email notification when a visitor leaves their email.', 'wp-ai-chatbot' ); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="wpaicb-form-actions">
                <button type="submit" class="wpaicb-btn wpaicb-btn-primary wpaicb-btn-lg" id="wpaicb-save-settings">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                    <?php esc_html_e( 'Save Settings', 'wp-ai-chatbot' ); ?>
                </button>
                <span class="wpaicb-save-status" id="wpaicb-save-status"></span>
            </div>
        </form>
    </div>
</div>
