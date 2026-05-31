<?php
/**
 * Admin Conversations Template.
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
                <span class="wpaicb-logo-icon">💬</span>
                <?php esc_html_e( 'Conversations', 'wp-ai-chatbot' ); ?>
            </h1>
            <p class="wpaicb-page-subtitle"><?php esc_html_e( 'View and manage chatbot conversations', 'wp-ai-chatbot' ); ?></p>
        </div>
    </div>

    <?php if ( $viewing_conversation ) : ?>
    <!-- Conversation Detail View -->
    <div class="wpaicb-conversation-detail">
        <div class="wpaicb-detail-header">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-ai-chatbot-conversations' ) ); ?>" class="wpaicb-back-link">
                ← <?php esc_html_e( 'Back to Conversations', 'wp-ai-chatbot' ); ?>
            </a>
            <div class="wpaicb-detail-meta">
                <span class="wpaicb-conv-status wpaicb-conv-status-<?php echo esc_attr( $viewing_conversation->status ); ?>">
                    <?php echo esc_html( ucfirst( $viewing_conversation->status ) ); ?>
                </span>
                <span class="wpaicb-detail-date">
                    <?php echo esc_html( gmdate( 'M j, Y g:i A', strtotime( $viewing_conversation->created_at ) ) ); ?>
                </span>
            </div>
        </div>

        <div class="wpaicb-detail-info">
            <div class="wpaicb-info-item">
                <span class="wpaicb-info-label"><?php esc_html_e( 'Visitor', 'wp-ai-chatbot' ); ?></span>
                <span class="wpaicb-info-value"><?php echo esc_html( $viewing_conversation->visitor_email ?: __( 'Anonymous', 'wp-ai-chatbot' ) ); ?></span>
            </div>
            <div class="wpaicb-info-item">
                <span class="wpaicb-info-label"><?php esc_html_e( 'Messages', 'wp-ai-chatbot' ); ?></span>
                <span class="wpaicb-info-value"><?php echo esc_html( $viewing_conversation->message_count ); ?></span>
            </div>
            <div class="wpaicb-info-item">
                <span class="wpaicb-info-label"><?php esc_html_e( 'Page', 'wp-ai-chatbot' ); ?></span>
                <span class="wpaicb-info-value"><?php echo esc_html( $viewing_conversation->page_url ?: '-' ); ?></span>
            </div>
        </div>

        <div class="wpaicb-messages-thread">
            <?php foreach ( $conversation_messages as $message ) : ?>
            <div class="wpaicb-message wpaicb-message-<?php echo esc_attr( $message->role ); ?>">
                <div class="wpaicb-message-avatar">
                    <?php echo 'user' === $message->role ? '👤' : '🤖'; ?>
                </div>
                <div class="wpaicb-message-content">
                    <div class="wpaicb-message-bubble">
                        <?php echo wp_kses_post( nl2br( $message->content ) ); ?>
                    </div>
                    <div class="wpaicb-message-meta">
                        <span class="wpaicb-message-time"><?php echo esc_html( gmdate( 'g:i A', strtotime( $message->created_at ) ) ); ?></span>
                        <?php if ( $message->confidence_score ) : ?>
                            <span class="wpaicb-message-confidence" title="<?php esc_attr_e( 'Confidence Score', 'wp-ai-chatbot' ); ?>">
                                <?php echo esc_html( round( $message->confidence_score * 100 ) ); ?>%
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="wpaicb-detail-actions">
            <button type="button" class="wpaicb-btn wpaicb-btn-danger wpaicb-delete-conversation" data-id="<?php echo esc_attr( $viewing_conversation->id ); ?>">
                <?php esc_html_e( 'Delete Conversation', 'wp-ai-chatbot' ); ?>
            </button>
        </div>
    </div>

    <?php else : ?>
    <!-- Conversations List View -->
    <div class="wpaicb-conversations-wrap">
        <!-- Filters -->
        <div class="wpaicb-filters">
            <div class="wpaicb-filter-tabs">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-ai-chatbot-conversations' ) ); ?>" class="wpaicb-filter-tab <?php echo empty( $status ) ? 'wpaicb-filter-active' : ''; ?>">
                    <?php esc_html_e( 'All', 'wp-ai-chatbot' ); ?> <span class="wpaicb-filter-count"><?php echo esc_html( $all_count ); ?></span>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-ai-chatbot-conversations&status=active' ) ); ?>" class="wpaicb-filter-tab <?php echo 'active' === $status ? 'wpaicb-filter-active' : ''; ?>">
                    <?php esc_html_e( 'Active', 'wp-ai-chatbot' ); ?> <span class="wpaicb-filter-count"><?php echo esc_html( $active_count ); ?></span>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-ai-chatbot-conversations&status=escalated' ) ); ?>" class="wpaicb-filter-tab <?php echo 'escalated' === $status ? 'wpaicb-filter-active' : ''; ?>">
                    <?php esc_html_e( 'Escalated', 'wp-ai-chatbot' ); ?> <span class="wpaicb-filter-count"><?php echo esc_html( $escalated_count ); ?></span>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-ai-chatbot-conversations&status=closed' ) ); ?>" class="wpaicb-filter-tab <?php echo 'closed' === $status ? 'wpaicb-filter-active' : ''; ?>">
                    <?php esc_html_e( 'Closed', 'wp-ai-chatbot' ); ?> <span class="wpaicb-filter-count"><?php echo esc_html( $closed_count ); ?></span>
                </a>
            </div>
            <form method="get" class="wpaicb-search-form">
                <input type="hidden" name="page" value="wp-ai-chatbot-conversations">
                <input type="text" name="s" value="<?php echo esc_attr( $search ); ?>" class="wpaicb-input wpaicb-search-input" placeholder="<?php esc_attr_e( 'Search conversations...', 'wp-ai-chatbot' ); ?>">
            </form>
        </div>

        <!-- Conversations Table -->
        <?php if ( empty( $conversations['items'] ) ) : ?>
            <div class="wpaicb-empty-state wpaicb-empty-state-large">
                <div class="wpaicb-empty-icon">💬</div>
                <h3><?php esc_html_e( 'No conversations yet', 'wp-ai-chatbot' ); ?></h3>
                <p><?php esc_html_e( 'Conversations will appear here once visitors start chatting with your bot.', 'wp-ai-chatbot' ); ?></p>
            </div>
        <?php else : ?>
            <div class="wpaicb-table-wrap">
                <table class="wpaicb-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Visitor', 'wp-ai-chatbot' ); ?></th>
                            <th><?php esc_html_e( 'Messages', 'wp-ai-chatbot' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'wp-ai-chatbot' ); ?></th>
                            <th><?php esc_html_e( 'Started', 'wp-ai-chatbot' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'wp-ai-chatbot' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $conversations['items'] as $conv ) : ?>
                        <tr>
                            <td>
                                <div class="wpaicb-table-user">
                                    <span class="wpaicb-table-avatar"><?php echo esc_html( strtoupper( substr( $conv->visitor_email ?: $conv->session_id, 0, 2 ) ) ); ?></span>
                                    <span class="wpaicb-table-name"><?php echo esc_html( $conv->visitor_email ?: __( 'Anonymous', 'wp-ai-chatbot' ) ); ?></span>
                                </div>
                            </td>
                            <td><?php echo esc_html( $conv->message_count ); ?></td>
                            <td>
                                <span class="wpaicb-conv-status wpaicb-conv-status-<?php echo esc_attr( $conv->status ); ?>">
                                    <?php echo esc_html( ucfirst( $conv->status ) ); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html( human_time_diff( strtotime( $conv->created_at ), time() ) ); ?> <?php esc_html_e( 'ago', 'wp-ai-chatbot' ); ?></td>
                            <td>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-ai-chatbot-conversations&conversation_id=' . $conv->id ) ); ?>" class="wpaicb-btn wpaicb-btn-sm">
                                    <?php esc_html_e( 'View', 'wp-ai-chatbot' ); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ( $conversations['pages'] > 1 ) : ?>
            <div class="wpaicb-pagination">
                <?php
                $base_url = admin_url( 'admin.php?page=wp-ai-chatbot-conversations' );
                if ( $status ) {
                    $base_url .= '&status=' . $status;
                }
                if ( $search ) {
                    $base_url .= '&s=' . urlencode( $search );
                }

                for ( $i = 1; $i <= $conversations['pages']; $i++ ) :
                ?>
                    <a href="<?php echo esc_url( $base_url . '&paged=' . $i ); ?>" class="wpaicb-page-link <?php echo $i === $current_page ? 'wpaicb-page-active' : ''; ?>">
                        <?php echo esc_html( $i ); ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
