/**
 * WP AI Chatbot - Admin JavaScript
 */
(function($) {
    'use strict';

    const WPAICB = {
        init: function() {
            this.bindTabs();
            this.bindProviderCards();
            this.bindTogglePassword();
            this.bindSettingsForm();
            this.bindTestConnection();
            this.bindReindex();
            this.bindQA();
            this.bindDeleteConversation();
            this.bindColorInput();
            this.bindRangeInputs();
        },

        // Tab switching
        bindTabs: function() {
            $(document).on('click', '.wpaicb-tab', function(e) {
                e.preventDefault();
                const tab = $(this).data('tab');
                $('.wpaicb-tab').removeClass('wpaicb-tab-active');
                $(this).addClass('wpaicb-tab-active');
                $('.wpaicb-tab-content').removeClass('wpaicb-tab-content-active');
                $(`.wpaicb-tab-content[data-tab="${tab}"]`).addClass('wpaicb-tab-content-active');
            });
        },

        // Provider card selection
        bindProviderCards: function() {
            $(document).on('click', '.wpaicb-provider-card', function() {
                $('.wpaicb-provider-card').removeClass('wpaicb-provider-active');
                $(this).addClass('wpaicb-provider-active');
                const provider = $(this).find('input').val();
                $('.wpaicb-provider-settings').hide();
                $(`.wpaicb-provider-settings[data-provider="${provider}"]`).show();
            });
        },

        // Toggle password visibility
        bindTogglePassword: function() {
            $(document).on('click', '.wpaicb-toggle-password', function(e) {
                e.preventDefault();
                const input = $(this).siblings('input');
                const type = input.attr('type') === 'password' ? 'text' : 'password';
                input.attr('type', type);
            });
        },

        // Settings form submission - uses wp_ajax for maximum reliability
        bindSettingsForm: function() {
            $(document).on('submit', '#wpaicb-settings-form', function(e) {
                e.preventDefault();
                const $form = $(this);
                const $btn = $('#wpaicb-save-settings');
                const $status = $('#wpaicb-save-status');

                $btn.prop('disabled', true).text(wpaicbAdmin.strings.saving);
                $status.text('');

                // Gather ALL form data using serialize for reliability
                const formDataObj = {};

                // Inputs
                $form.find('input[name^="wpaicb["]').each(function() {
                    const $el = $(this);
                    const name = $el.attr('name').replace('wpaicb[', '').replace(']', '');
                    const type = $el.attr('type') || 'text';

                    if (type === 'checkbox') {
                        formDataObj[name] = $el.is(':checked') ? '1' : '0';
                    } else if (type === 'radio') {
                        if ($el.is(':checked')) {
                            formDataObj[name] = $el.val();
                        }
                    } else {
                        formDataObj[name] = $el.val();
                    }
                });

                // Selects
                $form.find('select[name^="wpaicb["]').each(function() {
                    const name = $(this).attr('name').replace('wpaicb[', '').replace(']', '');
                    formDataObj[name] = $(this).val();
                });

                // Textareas
                $form.find('textarea[name^="wpaicb["]').each(function() {
                    const name = $(this).attr('name').replace('wpaicb[', '').replace(']', '');
                    formDataObj[name] = $(this).val();
                });

                // Force unchecked checkboxes to 0
                $form.find('input[type="checkbox"][name^="wpaicb["]').each(function() {
                    const name = $(this).attr('name').replace('wpaicb[', '').replace(']', '');
                    if (!$(this).is(':checked')) {
                        formDataObj[name] = '0';
                    }
                });

                // Build POST data for admin-ajax.php
                const postData = {
                    action: 'wpaicb_save_settings',
                    nonce: wpaicbAdmin.nonce,
                    settings: formDataObj
                };

                $.ajax({
                    url: wpaicbAdmin.ajaxUrl,
                    method: 'POST',
                    data: postData,
                    success: function(response) {
                        if (response.success) {
                            $status.text(response.data.message || wpaicbAdmin.strings.saved).css('color', '#10B981');
                            setTimeout(function() { location.reload(); }, 800);
                        } else {
                            $status.text(response.data.message || wpaicbAdmin.strings.error).css('color', '#EF4444');
                        }
                    },
                    error: function(xhr) {
                        $status.text(wpaicbAdmin.strings.error).css('color', '#EF4444');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html(
                            '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg> Save Settings'
                        );
                    }
                });
            });
        },


        // Test AI connection
        bindTestConnection: function() {
            $(document).on('click', '#wpaicb-test-connection', function(e) {
                e.preventDefault();
                const $btn = $(this);
                const $status = $('#wpaicb-connection-status');

                $btn.prop('disabled', true);
                $status.text(wpaicbAdmin.strings.testing).css('color', '#6B7280');

                $.ajax({
                    url: wpaicbAdmin.restUrl + 'admin/test-connection',
                    method: 'POST',
                    headers: { 'X-WP-Nonce': wpaicbAdmin.restNonce },
                    success: function(response) {
                        $status.text(wpaicbAdmin.strings.connected).css('color', '#10B981');
                    },
                    error: function(xhr) {
                        const msg = xhr.responseJSON?.message || wpaicbAdmin.strings.failed;
                        $status.text(msg).css('color', '#EF4444');
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });
        },

        // Re-index content
        bindReindex: function() {
            $(document).on('click', '#wpaicb-reindex-btn', function(e) {
                e.preventDefault();
                const $btn = $(this);
                const originalText = $btn.html();

                $btn.prop('disabled', true).html(
                    '<span class="wpaicb-spinner"></span> ' + wpaicbAdmin.strings.indexing
                );

                $.ajax({
                    url: wpaicbAdmin.restUrl + 'training/index',
                    method: 'POST',
                    headers: { 'X-WP-Nonce': wpaicbAdmin.restNonce },
                    success: function(response) {
                        $btn.html('&#10003; ' + wpaicbAdmin.strings.indexed);
                        setTimeout(() => {
                            $btn.html(originalText).prop('disabled', false);
                            location.reload();
                        }, 2000);
                    },
                    error: function(xhr) {
                        const msg = xhr.responseJSON?.message || wpaicbAdmin.strings.error;
                        alert(msg);
                        $btn.html(originalText).prop('disabled', false);
                    }
                });
            });
        },

        // Custom Q&A management
        bindQA: function() {
            // Show form
            $(document).on('click', '#wpaicb-add-qa', function() {
                $('#wpaicb-qa-form').slideDown(200);
                $(this).hide();
            });

            // Cancel
            $(document).on('click', '#wpaicb-cancel-qa', function() {
                $('#wpaicb-qa-form').slideUp(200);
                $('#wpaicb-add-qa').show();
                $('#wpaicb-qa-question, #wpaicb-qa-answer').val('');
            });

            // Save Q&A
            $(document).on('click', '#wpaicb-save-qa', function() {
                const question = $('#wpaicb-qa-question').val().trim();
                const answer = $('#wpaicb-qa-answer').val().trim();

                if (!question || !answer) {
                    alert('Please fill in both question and answer.');
                    return;
                }

                const $btn = $(this);
                $btn.prop('disabled', true).text('Saving...');

                $.ajax({
                    url: wpaicbAdmin.restUrl + 'admin/qa',
                    method: 'POST',
                    headers: { 'X-WP-Nonce': wpaicbAdmin.restNonce },
                    contentType: 'application/json',
                    data: JSON.stringify({ question, answer }),
                    success: function(response) {
                        location.reload();
                    },
                    error: function(xhr) {
                        alert(xhr.responseJSON?.message || 'Failed to save Q&A.');
                        $btn.prop('disabled', false).text('Save Q&A');
                    }
                });
            });

            // Delete Q&A
            $(document).on('click', '.wpaicb-delete-qa', function() {
                if (!confirm(wpaicbAdmin.strings.confirm_delete)) return;

                const id = $(this).data('id');
                const $item = $(this).closest('.wpaicb-qa-item');

                $.ajax({
                    url: wpaicbAdmin.restUrl + 'admin/qa/' + id,
                    method: 'DELETE',
                    headers: { 'X-WP-Nonce': wpaicbAdmin.restNonce },
                    success: function() {
                        $item.slideUp(200, () => $item.remove());
                    },
                    error: function() {
                        alert('Failed to delete Q&A.');
                    }
                });
            });
        },

        // Delete conversation
        bindDeleteConversation: function() {
            $(document).on('click', '.wpaicb-delete-conversation', function() {
                if (!confirm(wpaicbAdmin.strings.confirm_delete)) return;

                const id = $(this).data('id');

                $.ajax({
                    url: wpaicbAdmin.restUrl + 'admin/conversations/' + id,
                    method: 'DELETE',
                    headers: { 'X-WP-Nonce': wpaicbAdmin.restNonce },
                    success: function() {
                        window.location.href = wpaicbAdmin.ajaxUrl.replace('admin-ajax.php', 'admin.php?page=wp-ai-chatbot-conversations');
                    },
                    error: function() {
                        alert('Failed to delete conversation.');
                    }
                });
            });
        },

        // Color input sync
        bindColorInput: function() {
            $(document).on('input', '.wpaicb-color-input', function() {
                const val = $(this).val();
                $(this).siblings('.wpaicb-color-text').val(val);
            });

            $(document).on('input', '.wpaicb-color-text', function() {
                const val = $(this).val();
                if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
                    const target = $(this).data('color-for');
                    $('#' + target).val(val);
                }
            });
        },

        // Range input value display
        bindRangeInputs: function() {
            $(document).on('input', '.wpaicb-range', function() {
                $(this).siblings('.wpaicb-range-labels').find('.wpaicb-range-value').text($(this).val());
            });
        }
    };

    $(document).ready(function() {
        WPAICB.init();
    });

})(jQuery);
