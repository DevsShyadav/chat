/**
 * WP AI Chatbot - Frontend Widget JavaScript
 * Handles chat interaction, messaging, and widget behavior
 */
(function() {
    'use strict';

    const WPAICBWidget = {
        config: null,
        isOpen: false,
        isLoading: false,
        sessionId: null,
        elements: {},

        init: function() {
            if (typeof wpaicbWidget === 'undefined') return;
            this.config = wpaicbWidget;

            // Guard: ensure widget DOM exists before proceeding
            const widgetEl = document.getElementById('wpaicb-chat-widget');
            if (!widgetEl) return;

            this.sessionId = this.getSessionId();
            this.cacheElements();
            this.bindEvents();
            this.applyTheme();
        },

        cacheElements: function() {
            this.elements = {
                widget: document.getElementById('wpaicb-chat-widget'),
                trigger: document.getElementById('wpaicb-trigger'),
                window: document.getElementById('wpaicb-window'),
                messages: document.getElementById('wpaicb-messages'),
                input: document.getElementById('wpaicb-input'),
                sendBtn: document.getElementById('wpaicb-send'),
                closeBtn: document.getElementById('wpaicb-close-chat'),
                newChatBtn: document.getElementById('wpaicb-new-chat'),
                emailForm: document.getElementById('wpaicb-email-form'),
                emailInput: document.getElementById('wpaicb-email-input'),
                emailSubmit: document.getElementById('wpaicb-email-submit'),
                rating: document.getElementById('wpaicb-rating'),
            };
        },

        bindEvents: function() {
            const self = this;

            // Toggle widget
            this.elements.trigger.addEventListener('click', () => self.toggle());
            this.elements.closeBtn.addEventListener('click', () => self.close());

            // Send message
            this.elements.sendBtn.addEventListener('click', () => self.sendMessage());
            this.elements.input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    self.sendMessage();
                }
            });

            // Auto-resize textarea
            this.elements.input.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 100) + 'px';
                self.elements.sendBtn.disabled = !this.value.trim();
            });

            // New chat
            this.elements.newChatBtn.addEventListener('click', () => self.newChat());

            // Email submit
            this.elements.emailSubmit.addEventListener('click', () => self.submitEmail());
            this.elements.emailInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') { e.preventDefault(); self.submitEmail(); }
            });

            // Rating stars
            document.querySelectorAll('.wpaicb-star').forEach(star => {
                star.addEventListener('click', function() {
                    self.submitRating(parseInt(this.dataset.rating));
                });
                star.addEventListener('mouseenter', function() {
                    const rating = parseInt(this.dataset.rating);
                    document.querySelectorAll('.wpaicb-star').forEach((s, i) => {
                        s.classList.toggle('wpaicb-star-active', i < rating);
                    });
                });
            });
        },


        // Toggle open/close
        toggle: function() {
            this.isOpen ? this.close() : this.open();
        },

        open: function() {
            this.isOpen = true;
            this.elements.widget.classList.add('wpaicb-widget-open');
            this.elements.trigger.setAttribute('aria-expanded', 'true');
            this.elements.window.setAttribute('aria-hidden', 'false');
            this.elements.input.focus();

            // Show welcome message on first open
            if (!this.elements.messages.hasChildNodes()) {
                this.addWelcomeMessage();
            }

            // Track widget open
            this.trackEvent('opened');
        },

        close: function() {
            this.isOpen = false;
            this.elements.widget.classList.remove('wpaicb-widget-open');
            this.elements.trigger.setAttribute('aria-expanded', 'false');
            this.elements.window.setAttribute('aria-hidden', 'true');

            // Show rating if conversation has messages and rating not yet given
            if (this.elements.messages.querySelectorAll('.wpaicb-msg-user').length > 0 &&
                this.elements.rating.style.display === 'none') {
                this.elements.rating.style.display = 'block';
            }
        },

        // Send a message
        sendMessage: function() {
            const message = this.elements.input.value.trim();
            if (!message || this.isLoading) return;

            // Check if AI is configured
            if (!this.config.isConfigured) {
                this.addMessage(message, 'user');
                this.addMessage('Chat is being set up. Please check back shortly!', 'assistant');
                this.elements.input.value = '';
                return;
            }

            // Clear input
            this.elements.input.value = '';
            this.elements.input.style.height = 'auto';
            this.elements.sendBtn.disabled = true;

            // Add user message to UI
            this.addMessage(message, 'user');

            // Show typing indicator
            this.showTyping();

            // Send to API
            this.isLoading = true;

            fetch(this.config.restUrl + 'chat/message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': this.config.nonce,
                },
                body: JSON.stringify({
                    message: message,
                    session_id: this.sessionId,
                    page_url: window.location.href,
                }),
            })
            .then(response => response.json())
            .then(data => {
                this.hideTyping();
                this.isLoading = false;

                if (data.success) {
                    this.addMessage(data.message, 'assistant');

                    // Show email form if fallback
                    if (data.is_fallback && this.config.emailFallback) {
                        this.showEmailForm();
                    }
                } else if (data.error === 'rate_limited') {
                    this.addMessage(this.config.strings.rateLimit, 'assistant');
                } else {
                    this.addMessage(this.config.strings.error, 'assistant');
                }
            })
            .catch(() => {
                this.hideTyping();
                this.isLoading = false;
                this.addMessage(this.config.strings.error, 'assistant');
            });
        },

        // Add message to the chat UI
        addMessage: function(content, role) {
            const msgDiv = document.createElement('div');
            msgDiv.className = `wpaicb-msg wpaicb-msg-${role}`;

            const bubble = document.createElement('div');
            bubble.className = 'wpaicb-msg-bubble';

            if (role === 'assistant') {
                bubble.innerHTML = content; // AI responses may contain HTML
            } else {
                bubble.textContent = content;
            }

            msgDiv.appendChild(bubble);
            this.elements.messages.appendChild(msgDiv);
            this.scrollToBottom();
        },

        // Add welcome message
        addWelcomeMessage: function() {
            if (this.config.welcomeMessage) {
                const welcome = document.createElement('div');
                welcome.className = 'wpaicb-msg wpaicb-msg-assistant';
                const bubble = document.createElement('div');
                bubble.className = 'wpaicb-msg-bubble';
                bubble.textContent = this.config.welcomeMessage;
                welcome.appendChild(bubble);
                this.elements.messages.appendChild(welcome);
            }
        },


        // Typing indicator
        showTyping: function() {
            if (!this.config.typingIndicator) return;
            const typing = document.createElement('div');
            typing.id = 'wpaicb-typing-indicator';
            typing.className = 'wpaicb-msg wpaicb-msg-assistant';
            typing.innerHTML = '<div class="wpaicb-typing"><span class="wpaicb-typing-dot"></span><span class="wpaicb-typing-dot"></span><span class="wpaicb-typing-dot"></span></div>';
            this.elements.messages.appendChild(typing);
            this.scrollToBottom();
        },

        hideTyping: function() {
            const typing = document.getElementById('wpaicb-typing-indicator');
            if (typing) typing.remove();
        },

        // Email fallback form
        showEmailForm: function() {
            this.elements.emailForm.style.display = 'block';
        },

        hideEmailForm: function() {
            this.elements.emailForm.style.display = 'none';
        },

        submitEmail: function() {
            const email = this.elements.emailInput.value.trim();
            if (!email || !this.isValidEmail(email)) {
                this.elements.emailInput.style.borderColor = '#EF4444';
                return;
            }

            this.elements.emailSubmit.disabled = true;

            fetch(this.config.restUrl + 'chat/email', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': this.config.nonce,
                },
                body: JSON.stringify({
                    session_id: this.sessionId,
                    email: email,
                }),
            })
            .then(response => response.json())
            .then(data => {
                this.hideEmailForm();
                if (data.success) {
                    this.addMessage(this.config.strings.emailThanks, 'assistant');
                }
            })
            .catch(() => {
                this.elements.emailSubmit.disabled = false;
            });
        },

        // Rating
        submitRating: function(rating) {
            fetch(this.config.restUrl + 'chat/rating', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': this.config.nonce,
                },
                body: JSON.stringify({
                    session_id: this.sessionId,
                    rating: rating,
                }),
            })
            .then(() => {
                this.elements.rating.innerHTML = '<p class="wpaicb-rating-label">Thank you for your feedback!</p>';
                setTimeout(() => {
                    this.elements.rating.style.display = 'none';
                }, 2000);
            });
        },

        // New conversation
        newChat: function() {
            fetch(this.config.restUrl + 'chat/new', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': this.config.nonce,
                },
                body: JSON.stringify({ session_id: this.sessionId }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.sessionId = data.session_id;
                    this.setSessionId(data.session_id);
                    this.elements.messages.innerHTML = '';
                    this.addWelcomeMessage();
                    this.hideEmailForm();
                    this.elements.rating.style.display = 'none';
                }
            });
        },

        // Track analytics events
        trackEvent: function(event) {
            fetch(this.config.restUrl + 'chat/' + event, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': this.config.nonce,
                },
                body: JSON.stringify({ session_id: this.sessionId }),
            }).catch(() => {}); // Silent fail for analytics
        },

        // Scroll messages to bottom
        scrollToBottom: function() {
            const el = this.elements.messages;
            setTimeout(() => { el.scrollTop = el.scrollHeight; }, 50);
        },

        // Session management
        getSessionId: function() {
            let id = this.getCookie('wpaicb_session');
            if (!id) {
                id = this.config.sessionId || this.generateUUID();
                this.setSessionId(id);
            }
            return id;
        },

        setSessionId: function(id) {
            document.cookie = `wpaicb_session=${id};path=/;max-age=${60*60*24*30};SameSite=Lax`;
        },

        getCookie: function(name) {
            const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
            return match ? match[2] : null;
        },

        generateUUID: function() {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                const r = Math.random() * 16 | 0;
                const v = c === 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        },

        // Email validation
        isValidEmail: function(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },

        // Dark mode
        applyTheme: function() {
            if (this.config.darkMode === 'dark') {
                this.elements.widget.classList.add('wpaicb-dark-active');
            } else if (this.config.darkMode === 'auto') {
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    this.elements.widget.classList.add('wpaicb-dark-active');
                }
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                    this.elements.widget.classList.toggle('wpaicb-dark-active', e.matches);
                });
            }
        },
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => WPAICBWidget.init());
    } else {
        WPAICBWidget.init();
    }
})();
