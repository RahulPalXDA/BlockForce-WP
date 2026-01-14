<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class BlockForce_WP_Dashboard
{
    private $settings;
    private $core;
    private $text_domain = BFWP_TEXT_DOMAIN;

    public function __construct($settings, $core)
    {
        $this->settings = $settings;
        $this->core = $core;
    }

    public function init_hooks()
    {
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
    }

    public function add_dashboard_widget()
    {
        wp_add_dashboard_widget(
            'blockforce_wp_dashboard_widget',
            '<span class="dashicons dashicons-shield"></span> ' . __('BlockForce WP Security', $this->text_domain),
            array($this, 'render_dashboard_widget')
        );
    }

    public function render_dashboard_widget()
    {
        $current_slug = $this->core->login_url->get_login_slug();
        $site_url = get_site_url();
        $ip_blocking_enabled = isset($this->settings['enable_ip_blocking']) ? (bool) $this->settings['enable_ip_blocking'] : true;
        $url_change_enabled = isset($this->settings['enable_url_change']) ? (bool) $this->settings['enable_url_change'] : true;
        $stats = $this->get_attack_statistics();
        ?>
        <style>
            .blockforce-dashboard-widget {
                margin: -12px -12px 0 -12px;
            }

            .blockforce-dashboard-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: #fff;
                padding: 20px;
                margin-bottom: 15px;
            }

            .blockforce-dashboard-header h3 {
                margin: 0 0 5px 0;
                color: #fff;
                font-size: 16px;
            }

            .blockforce-dashboard-header p {
                margin: 0;
                opacity: 0.9;
                font-size: 13px;
            }

            .blockforce-dashboard-content {
                padding: 0 12px 12px 12px;
            }

            .blockforce-stat-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
                gap: 10px;
                margin-bottom: 15px;
            }

            .blockforce-stat-card {
                background: #f6f7f7;
                padding: 12px;
                border-radius: 4px;
                text-align: center;
                border-left: 3px solid #0073aa;
            }

            .blockforce-stat-card.danger {
                border-left-color: #d63638;
            }

            .blockforce-stat-card.success {
                border-left-color: #00a32a;
            }

            .blockforce-stat-card.warning {
                border-left-color: #dba617;
            }

            .blockforce-stat-number {
                font-size: 24px;
                font-weight: bold;
                line-height: 1;
                margin-bottom: 5px;
            }

            .blockforce-stat-label {
                font-size: 11px;
                color: #646970;
                text-transform: uppercase;
            }

            .blockforce-login-url-box {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                padding: 12px;
                margin-bottom: 15px;
            }

            .blockforce-login-url-box strong {
                display: block;
                margin-bottom: 8px;
                font-size: 13px;
            }

            .blockforce-login-url {
                background: #f6f7f7;
                padding: 8px 10px;
                border-radius: 3px;
                font-family: monospace;
                font-size: 12px;
                word-break: break-all;
                border: 1px solid #ddd;
            }

            .blockforce-status-indicators {
                display: flex;
                gap: 10px;
                margin-bottom: 15px;
                flex-wrap: wrap;
            }

            .blockforce-status-badge {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 6px 10px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: 500;
            }

            .blockforce-status-badge.enabled {
                background: #d7f0db;
                color: #00a32a;
            }

            .blockforce-status-badge.disabled {
                background: #f0f0f1;
                color: #646970;
            }

            .blockforce-status-badge .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
            }

            .blockforce-quick-actions {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
            }

            .blockforce-quick-actions .button {
                flex: 1;
                min-width: 120px;
                text-align: center;
                font-size: 12px;
            }

            .blockforce-quick-actions .dashicons {
                margin-top: 3px;
            }

            .blockforce-icon-lock {
                color: #d63638;
            }

            .blockforce-icon-home {
                color: #00a32a;
            }

            @media screen and (max-width: 782px) {
                .blockforce-stat-grid {
                    grid-template-columns: repeat(2, 1fr);
                }

                .blockforce-quick-actions {
                    flex-direction: column;
                }

                .blockforce-quick-actions .button {
                    width: 100%;
                }
            }
        </style>
        <div class="blockforce-dashboard-widget">
            <div class="blockforce-dashboard-header">
                <h3><?php esc_html_e('Security Status', $this->text_domain); ?></h3>
                <p><?php esc_html_e('Your WordPress login is protected', $this->text_domain); ?></p>
            </div>
            <div class="blockforce-dashboard-content">
                <div class="blockforce-stat-grid">
                    <div class="blockforce-stat-card <?php echo $stats['blocked_today'] > 0 ? 'danger' : 'success'; ?>">
                        <div class="blockforce-stat-number"><?php echo esc_html((string) $stats['blocked_today']); ?></div>
                        <div class="blockforce-stat-label"><?php esc_html_e('Blocked Today', $this->text_domain); ?></div>
                    </div>
                    <div class="blockforce-stat-card <?php echo $stats['attempts_today'] > 0 ? 'warning' : 'success'; ?>">
                        <div class="blockforce-stat-number"><?php echo esc_html((string) $stats['attempts_today']); ?></div>
                        <div class="blockforce-stat-label"><?php esc_html_e('Failed Attempts', $this->text_domain); ?></div>
                    </div>
                    <div class="blockforce-stat-card">
                        <div class="blockforce-stat-number"><?php echo esc_html((string) $stats['active_blocks']); ?></div>
                        <div class="blockforce-stat-label"><?php esc_html_e('Active Blocks', $this->text_domain); ?></div>
                    </div>
                </div>
                <div class="blockforce-login-url-box">
                    <strong>
                        <?php if ($current_slug): ?>
                            <span class="dashicons dashicons-lock blockforce-icon-lock"></span>
                            <?php esc_html_e('Secret Login URL:', $this->text_domain); ?>
                        <?php else: ?>
                            <span class="dashicons dashicons-admin-home blockforce-icon-home"></span>
                            <?php esc_html_e('Default Login URL:', $this->text_domain); ?>
                        <?php endif; ?>
                    </strong>
                    <div class="blockforce-login-url">
                        <?php echo esc_url($current_slug ? $site_url . '/' . $current_slug : $site_url . '/wp-login.php'); ?>
                    </div>
                </div>
                <div class="blockforce-status-indicators">
                    <div class="blockforce-status-badge <?php echo $ip_blocking_enabled ? 'enabled' : 'disabled'; ?>">
                        <span class="dashicons dashicons-<?php echo $ip_blocking_enabled ? 'yes-alt' : 'dismiss'; ?>"></span>
                        <?php esc_html_e('IP Blocking', $this->text_domain); ?>
                    </div>
                    <div class="blockforce-status-badge <?php echo $url_change_enabled ? 'enabled' : 'disabled'; ?>">
                        <span class="dashicons dashicons-<?php echo $url_change_enabled ? 'yes-alt' : 'dismiss'; ?>"></span>
                        <?php esc_html_e('Auto URL Change', $this->text_domain); ?>
                    </div>
                </div>
                <div class="blockforce-quick-actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=blockforce-wp')); ?>" class="button button-primary">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php esc_html_e('Overview', $this->text_domain); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=blockforce-wp-settings')); ?>" class="button">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php esc_html_e('Settings', $this->text_domain); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

    private function get_attack_statistics()
    {
        global $wpdb;

        // Blocked IPs (Active and Today)
        $table_blocks = $wpdb->prefix . BFWP_BLOCKS_TABLE;

        // Count active blocks
        $active_blocks = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_blocks WHERE expires_at > %s",
                current_time('mysql')
            )
        );

        // Count blocks today
        $day_ago_mysql = date('Y-m-d H:i:s', strtotime('-1 day', current_time('timestamp')));
        $blocked_today = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_blocks WHERE blocked_at > %s",
                $day_ago_mysql
            )
        );

        // Persistent Logs for Failed Attempts (More accurate than transients)
        $table_logs = $wpdb->prefix . BFWP_LOGS_TABLE;

        // Low-level check if table exists to avoid errors on fresh installs before migration
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_logs'") === $table_logs) {
            $attempts_today = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_logs WHERE status = 'failed' AND time > %s",
                    $day_ago_mysql
                )
            );
        } else {
            $attempts_today = 0;
        }

        return array(
            'blocked_today' => $blocked_today,
            'attempts_today' => $attempts_today,
            'active_blocks' => $active_blocks,
        );
    }
}
