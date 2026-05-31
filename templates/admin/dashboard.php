<?php
/**
 * Admin Dashboard Template.
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
                <span class="wpaicb-logo-icon">🤖</span>
                <?php esc_html_e( 'AI Chatbot Dashboard', 'wp-ai-chatbot' ); ?>
            </h1>
            <p class="wpaicb-page-subtitle"><?php esc_html_e( 'Monitor your chatbot performance and conversations', 'wp-ai-chatbot' ); ?></p>
        </div>
        <div class="wpaicb-header-right">
            <span class="wpaicb-status-badge <?php echo $is_configured ? 'wpaicb-status-active' : 'wpaicb-status-inactive'; ?>">
                <span class="wpaicb-status-dot"></span>
                <?php echo $is_configured ? esc_html__( 'Active', 'wp-ai-chatbot' ) : esc_html__( 'Not Configured', 'wp-ai-chatbot' ); ?>
            </span>
        </div>
    </div>

    <?php if ( ! $is_configured ) : ?>
    <!-- Onboarding Banner -->
    <div class="wpaicb-onboarding-banner">
        <div class="wpaicb-onboarding-content">
            <div class="wpaicb-onboarding-icon">🚀</div>
            <div class="wpaicb-onboarding-text">
                <h3><?php esc_html_e( 'Welcome! Let\'s set up your AI Chatbot', 'wp-ai-chatbot' ); ?></h3>
                <p><?php esc_html_e( 'Configure your AI provider, train on your content, and start answering customer questions automatically.', 'wp-ai-chatbot' ); ?></p>
            </div>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-ai-chatbot-settings' ) ); ?>" class="wpaicb-btn wpaicb-btn-primary">
                <?php esc_html_e( 'Get Started', 'wp-ai-chatbot' ); ?> →
            </a>
        </div>
        <div class="wpaicb-onboarding-steps">
            <div class="wpaicb-step <?php echo $is_configured ? 'wpaicb-step-done' : 'wpaicb-step-current'; ?>">
                <span class="wpaicb-step-number">1</span>
                <span class="wpaicb-step-label"><?php esc_html_e( 'Add API Key', 'wp-ai-chatbot' ); ?></span>
            </div>
            <div class="wpaicb-step <?php echo $has_content ? 'wpaicb-step-done' : ''; ?>">
                <span class="wpaicb-step-number">2</span>
                <span class="wpaicb-step-label"><?php esc_html_e( 'Train Content', 'wp-ai-chatbot' ); ?></span>
            </div>
            <div class="wpaicb-step">
                <span class="wpaicb-step-number">3</span>
                <span class="wpaicb-step-label"><?php esc_html_e( 'Customize Widget', 'wp-ai-chatbot' ); ?></span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="wpaicb-stats-grid">
        <div class="wpaicb-stat-card">
            <div class="wpaicb-stat-icon wpaicb-stat-icon-blue">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
            </div>
            <div class="wpaicb-stat-content">
                <span class="wpaicb-stat-value"><?php echo esc_html( $conv_stats['total'] ); ?></span>
                <span class="wpaicb-stat-label"><?php esc_html_e( 'Conversations Today', 'wp-ai-chatbot' ); ?></span>
            </div>
            <div class="wpaicb-stat-trend wpaicb-stat-trend-up">
                <span class="wpaicb-stat-week"><?php echo esc_html( $conv_stats_week['total'] ); ?> <?php esc_html_e( 'this week', 'wp-ai-chatbot' ); ?></span>
            </div>
        </div>

        <div class="wpaicb-stat-card">
            <div class="wpaicb-stat-icon wpaicb-stat-icon-green">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            </div>
            <div class="wpaicb-stat-content">
                <span class="wpaicb-stat-value"><?php echo esc_html( $resolution_rate ); ?>%</span>
                <span class="wpaicb-stat-label"><?php esc_html_e( 'Resolution Rate', 'wp-ai-chatbot' ); ?></span>
            </div>
            <div class="wpaicb-stat-trend">
                <span class="wpaicb-stat-week"><?php esc_html_e( 'past 7 days', 'wp-ai-chatbot' ); ?></span>
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
            <div class="wpaicb-stat-trend">
                <span class="wpaicb-stat-week"><?php echo esc_html( $content_stats['total_posts'] ); ?> <?php esc_html_e( 'pages indexed', 'wp-ai-chatbot' ); ?></span>
            </div>
        </div>

        <div class="wpaicb-stat-card">
            <div class="wpaicb-stat-icon wpaicb-stat-icon-orange">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
            </div>
            <div class="wpaicb-stat-content">
                <span class="wpaicb-stat-value"><?php echo esc_html( $dashboard_stats['emails_captured'] ); ?></span>
                <span class="wpaicb-stat-label"><?php esc_html_e( 'Emails Captured', 'wp-ai-chatbot' ); ?></span>
            </div>
            <div class="wpaicb-stat-trend">
                <span class="wpaicb-stat-week"><?php esc_html_e( 'this month', 'wp-ai-chatbot' ); ?></span>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="wpaicb-dashboard-grid">
        <!-- Activity Chart -->
        <div class="wpaicb-card wpaicb-chart-card">
            <div class="wpaicb-card-header">
                <h3><?php esc_html_e( 'Conversations (Last 7 Days)', 'wp-ai-chatbot' ); ?></h3>
            </div>
            <div class="wpaicb-card-body">
                <div class="wpaicb-mini-chart" id="wpaicb-activity-chart" data-values="<?php echo esc_attr( wp_json_encode( $daily_chats ) ); ?>">
                    <div class="wpaicb-chart-bars">
                        <?php
                        $max_val = 1;
                        foreach ( $daily_chats as $day ) {
                            if ( $day['count'] > $max_val ) {
                                $max_val = $day['count'];
                            }
                        }
                        foreach ( $daily_chats as $day ) :
                            $height = $max_val > 0 ? ( $day['count'] / $max_val ) * 100 : 0;
                            $date_label = gmdate( 'D', strtotime( $day['date'] ) );
                        ?>
                        <div class="wpaicb-chart-bar-wrap">
                            <div class="wpaicb-chart-bar" style="height: <?php echo esc_attr( max( 4, $height ) ); ?>%;" title="<?php echo esc_attr( $day['count'] . ' conversations' ); ?>"></div>
                            <span class="wpaicb-chart-label"><?php echo esc_html( $date_label ); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Conversations -->
        <div class="wpaicb-card">
            <div class="wpaicb-card-header">
                <h3><?php esc_html_e( 'Recent Conversations', 'wp-ai-chatbot' ); ?></h3>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-ai-chatbot-conversations' ) ); ?>" class="wpaicb-link">
                    <?php esc_html_e( 'View All', 'wp-ai-chatbot' ); ?> →
                </a>
            </div>
            <div class="wpaicb-card-body">
                <?php if ( empty( $recent['items'] ) ) : ?>
                    <div class="wpaicb-empty-state">
                        <p><?php esc_html_e( 'No conversations yet. Your chatbot is ready and waiting!', 'wp-ai-chatbot' ); ?></p>
                    </div>
                <?php else : ?>
                    <div class="wpaicb-conversations-list">
                        <?php foreach ( $recent['items'] as $conv ) : ?>
                        <div class="wpaicb-conversation-item">
                            <div class="wpaicb-conv-avatar">
                                <?php echo esc_html( strtoupper( substr( $conv->visitor_email ?: $conv->session_id, 0, 2 ) ) ); ?>
                            </div>
                            <div class="wpaicb-conv-details">
                                <span class="wpaicb-conv-name">
                                    <?php echo esc_html( $conv->visitor_email ?: __( 'Anonymous Visitor', 'wp-ai-chatbot' ) ); ?>
                                </span>
                                <span class="wpaicb-conv-meta">
                                    <?php echo esc_html( $conv->message_count ); ?> <?php esc_html_e( 'messages', 'wp-ai-chatbot' ); ?> &middot;
                                    <?php echo esc_html( human_time_diff( strtotime( $conv->created_at ), time() ) ); ?> <?php esc_html_e( 'ago', 'wp-ai-chatbot' ); ?>
                                </span>
                            </div>
                            <span class="wpaicb-conv-status wpaicb-conv-status-<?php echo esc_attr( $conv->status ); ?>">
                                <?php echo esc_html( ucfirst( $conv->status ) ); ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="wpaicb-quick-actions">
        <h3><?php esc_html_e( 'Quick Actions', 'wp-ai-chatbot' ); ?></h3>
        <div class="wpaicb-actions-grid">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-ai-chatbot-training' ) ); ?>" class="wpaicb-action-card">
                <span class="wpaicb-action-icon">📚</span>
                <span class="wpaicb-action-label"><?php esc_html_e( 'Re-index Content', 'wp-ai-chatbot' ); ?></span>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-ai-chatbot-settings' ) ); ?>" class="wpaicb-action-card">
                <span class="wpaicb-action-icon">⚙️</span>
                <span class="wpaicb-action-label"><?php esc_html_e( 'Configure Widget', 'wp-ai-chatbot' ); ?></span>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-ai-chatbot-training#custom-qa' ) ); ?>" class="wpaicb-action-card">
                <span class="wpaicb-action-icon">💡</span>
                <span class="wpaicb-action-label"><?php esc_html_e( 'Add Custom Q&A', 'wp-ai-chatbot' ); ?></span>
            </a>
            <a href="#" class="wpaicb-action-card" id="wpaicb-test-chatbot">
                <span class="wpaicb-action-icon">🧪</span>
                <span class="wpaicb-action-label"><?php esc_html_e( 'Test Chatbot', 'wp-ai-chatbot' ); ?></span>
            </a>
        </div>
    </div>
</div>
