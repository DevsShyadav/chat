<?php
/**
 * Admin Training Template.
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
                <span class="wpaicb-logo-icon">📚</span>
                <?php esc_html_e( 'Training', 'wp-ai-chatbot' ); ?>
            </h1>
            <p class="wpaicb-page-subtitle"><?php esc_html_e( 'Manage content your chatbot learns from', 'wp-ai-chatbot' ); ?></p>
        </div>
        <div class="wpaicb-header-right">
            <button type="button" id="wpaicb-reindex-btn" class="wpaicb-btn wpaicb-btn-primary">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
                <?php esc_html_e( 'Re-index All Content', 'wp-ai-chatbot' ); ?>
            </button>
        </div>
    </div>

    <!-- Training Stats -->
    <div class="wpaicb-stats-grid wpaicb-stats-grid-3">
        <div class="wpaicb-stat-card">
            <div class="wpaicb-stat-icon wpaicb-stat-icon-blue">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            </div>
            <div class="wpaicb-stat-content">
                <span class="wpaicb-stat-value"><?php echo esc_html( $content_stats['total_posts'] ); ?></span>
                <span class="wpaicb-stat-label"><?php esc_html_e( 'Pages Indexed', 'wp-ai-chatbot' ); ?></span>
            </div>
        </div>

        <div class="wpaicb-stat-card">
            <div class="wpaicb-stat-icon wpaicb-stat-icon-purple">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5z"></path><path d="M2 17l10 5 10-5"></path><path d="M2 12l10 5 10-5"></path></svg>
            </div>
            <div class="wpaicb-stat-content">
                <span class="wpaicb-stat-value"><?php echo esc_html( $content_stats['total_chunks'] ); ?></span>
                <span class="wpaicb-stat-label"><?php esc_html_e( 'Content Chunks', 'wp-ai-chatbot' ); ?></span>
            </div>
        </div>

        <div class="wpaicb-stat-card">
            <div class="wpaicb-stat-icon wpaicb-stat-icon-green">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
            </div>
            <div class="wpaicb-stat-content">
                <span class="wpaicb-stat-value"><?php echo esc_html( number_format( $content_stats['total_words'] ) ); ?></span>
                <span class="wpaicb-stat-label"><?php esc_html_e( 'Total Words', 'wp-ai-chatbot' ); ?></span>
            </div>
        </div>
    </div>

    <!-- Content Type Selection -->
    <div class="wpaicb-card">
        <div class="wpaicb-card-header">
            <h3><?php esc_html_e( 'Content Sources', 'wp-ai-chatbot' ); ?></h3>
            <p class="wpaicb-card-description"><?php esc_html_e( 'Select which content types to include in training.', 'wp-ai-chatbot' ); ?></p>
        </div>
        <div class="wpaicb-card-body">
            <div class="wpaicb-content-types-grid">
                <?php foreach ( $post_types as $post_type ) : ?>
                <label class="wpaicb-content-type-card">
                    <input type="checkbox" name="content_types[]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $selected_types, true ) ); ?>>
                    <span class="wpaicb-ct-check"></span>
                    <span class="wpaicb-ct-name"><?php echo esc_html( $post_type->labels->name ); ?></span>
                    <span class="wpaicb-ct-count">
                        <?php
                        $count = wp_count_posts( $post_type->name );
                        echo esc_html( $count->publish ?? 0 );
                        ?>
                        <?php esc_html_e( 'published', 'wp-ai-chatbot' ); ?>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Indexed Content List -->
    <div class="wpaicb-card">
        <div class="wpaicb-card-header">
            <h3><?php esc_html_e( 'Indexed Content', 'wp-ai-chatbot' ); ?></h3>
            <?php if ( $content_stats['last_indexed'] ) : ?>
                <span class="wpaicb-last-indexed">
                    <?php esc_html_e( 'Last indexed:', 'wp-ai-chatbot' ); ?>
                    <?php echo esc_html( human_time_diff( strtotime( $content_stats['last_indexed'] ), time() ) ); ?> <?php esc_html_e( 'ago', 'wp-ai-chatbot' ); ?>
                </span>
            <?php endif; ?>
        </div>
        <div class="wpaicb-card-body">
            <?php if ( empty( $content_list['items'] ) ) : ?>
                <div class="wpaicb-empty-state">
                    <p><?php esc_html_e( 'No content indexed yet. Click "Re-index All Content" to get started.', 'wp-ai-chatbot' ); ?></p>
                </div>
            <?php else : ?>
                <div class="wpaicb-table-wrap">
                    <table class="wpaicb-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Title', 'wp-ai-chatbot' ); ?></th>
                                <th><?php esc_html_e( 'Type', 'wp-ai-chatbot' ); ?></th>
                                <th><?php esc_html_e( 'Chunks', 'wp-ai-chatbot' ); ?></th>
                                <th><?php esc_html_e( 'Words', 'wp-ai-chatbot' ); ?></th>
                                <th><?php esc_html_e( 'Status', 'wp-ai-chatbot' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $content_list['items'] as $item ) : ?>
                            <tr>
                                <td>
                                    <?php if ( $item->post_id ) : ?>
                                        <a href="<?php echo esc_url( get_edit_post_link( $item->post_id ) ); ?>" target="_blank"><?php echo esc_html( $item->title ); ?></a>
                                    <?php else : ?>
                                        <?php echo esc_html( $item->title ); ?>
                                    <?php endif; ?>
                                </td>
                                <td><span class="wpaicb-badge"><?php echo esc_html( ucfirst( $item->content_type ) ); ?></span></td>
                                <td><?php echo esc_html( $item->chunk_count ); ?></td>
                                <td><?php echo esc_html( number_format( $item->total_words ) ); ?></td>
                                <td>
                                    <span class="wpaicb-status-dot wpaicb-status-dot-<?php echo esc_attr( $item->status ); ?>"></span>
                                    <?php echo esc_html( ucfirst( $item->status ) ); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Custom Q&A Section -->
    <div class="wpaicb-card" id="custom-qa">
        <div class="wpaicb-card-header">
            <h3><?php esc_html_e( 'Custom Q&A Pairs', 'wp-ai-chatbot' ); ?></h3>
            <button type="button" id="wpaicb-add-qa" class="wpaicb-btn wpaicb-btn-sm wpaicb-btn-secondary">
                + <?php esc_html_e( 'Add Q&A', 'wp-ai-chatbot' ); ?>
            </button>
        </div>
        <div class="wpaicb-card-body">
            <p class="wpaicb-card-description"><?php esc_html_e( 'Add specific question-answer pairs that take priority over AI-generated responses.', 'wp-ai-chatbot' ); ?></p>

            <!-- Add Q&A Form (hidden by default) -->
            <div id="wpaicb-qa-form" class="wpaicb-qa-form" style="display:none;">
                <div class="wpaicb-field-group">
                    <label class="wpaicb-label"><?php esc_html_e( 'Question', 'wp-ai-chatbot' ); ?></label>
                    <input type="text" id="wpaicb-qa-question" class="wpaicb-input" placeholder="<?php esc_attr_e( 'e.g., What are your business hours?', 'wp-ai-chatbot' ); ?>">
                </div>
                <div class="wpaicb-field-group">
                    <label class="wpaicb-label"><?php esc_html_e( 'Answer', 'wp-ai-chatbot' ); ?></label>
                    <textarea id="wpaicb-qa-answer" class="wpaicb-textarea" rows="3" placeholder="<?php esc_attr_e( 'e.g., We are open Monday to Friday, 9 AM to 5 PM EST.', 'wp-ai-chatbot' ); ?>"></textarea>
                </div>
                <div class="wpaicb-qa-form-actions">
                    <button type="button" id="wpaicb-save-qa" class="wpaicb-btn wpaicb-btn-primary wpaicb-btn-sm"><?php esc_html_e( 'Save Q&A', 'wp-ai-chatbot' ); ?></button>
                    <button type="button" id="wpaicb-cancel-qa" class="wpaicb-btn wpaicb-btn-sm"><?php esc_html_e( 'Cancel', 'wp-ai-chatbot' ); ?></button>
                </div>
            </div>

            <!-- Existing Q&A List -->
            <div class="wpaicb-qa-list" id="wpaicb-qa-list">
                <?php if ( empty( $custom_qa ) ) : ?>
                    <div class="wpaicb-empty-state wpaicb-qa-empty">
                        <p><?php esc_html_e( 'No custom Q&A pairs yet. Add common questions and answers to improve your chatbot.', 'wp-ai-chatbot' ); ?></p>
                    </div>
                <?php else : ?>
                    <?php foreach ( $custom_qa as $qa ) : ?>
                    <div class="wpaicb-qa-item" data-id="<?php echo esc_attr( $qa->id ); ?>">
                        <div class="wpaicb-qa-content">
                            <div class="wpaicb-qa-question"><strong>Q:</strong> <?php echo esc_html( $qa->question ); ?></div>
                            <div class="wpaicb-qa-answer"><strong>A:</strong> <?php echo esc_html( $qa->answer ); ?></div>
                        </div>
                        <div class="wpaicb-qa-actions">
                            <button type="button" class="wpaicb-btn-icon wpaicb-delete-qa" data-id="<?php echo esc_attr( $qa->id ); ?>" title="<?php esc_attr_e( 'Delete', 'wp-ai-chatbot' ); ?>">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
