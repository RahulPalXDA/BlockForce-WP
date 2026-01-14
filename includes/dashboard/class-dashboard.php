<?php
declare(strict_types=1);
if (!defined('ABSPATH'))
    exit;
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
        wp_add_dashboard_widget('blockforce_wp_dashboard_widget', '<span class="dashicons dashicons-shield"></span> ' . __('BlockForce WP Security', $this->text_domain), array($this, 'render_dashboard_widget'));
    }
    public function render_dashboard_widget()
    {
        $slug = $this->core->login_url->get_login_slug();
        $url = get_site_url();
        $ip_en = isset($this->settings['enable_ip_blocking']) ? (bool) $this->settings['enable_ip_blocking'] : true;
        $url_en = isset($this->settings['enable_url_change']) ? (bool) $this->settings['enable_url_change'] : true;
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

            .blockforce-stat-card.warning {
                border-left-color: #dba617;
            }

            .blockforce-stat-card.success {
                border-left-color: #00a32a;
            }

            .blockforce-stat-number {
                font-size: 24px;
                font-weight: bold;
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
            }

            .blockforce-status-badge.enabled {
                background: #d7f0db;
                color: #00a32a;
            }

            .blockforce-status-badge.disabled {
                background: #f0f0f1;
                color: #646970;
            }

            .blockforce-quick-actions {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
            }

            .blockforce-quick-actions .button {
                flex: 1;
                text-align: center;
                font-size: 12px;
            }
        </style>
        <div class="blockforce-dashboard-widget">
            <div class="blockforce-dashboard-header">
                <h3><?php esc_html_e('Security Status', $this->text_domain); ?></h3>
                <p><?php esc_html_e('Protected by BlockForce', $this->text_domain); ?></p>
            </div>
            <div class="blockforce-dashboard-content">
                <div class="blockforce-stat-grid">
                    <div class="blockforce-stat-card <?php echo $stats['blocked_today'] > 0 ? 'danger' : 'success'; ?>">
                        <div class="blockforce-stat-number"><?php echo (int) $stats['blocked_today']; ?></div>
                        <div class="blockforce-stat-label"><?php esc_html_e('Blocked Today', $this->text_domain); ?></div>
                    </div>
                    <div class="blockforce-stat-card <?php echo $stats['attempts_today'] > 0 ? 'warning' : 'success'; ?>">
                        <div class="blockforce-stat-number"><?php echo (int) $stats['attempts_today']; ?></div>
                        <div class="blockforce-stat-label"><?php esc_html_e('Failed Today', $this->text_domain); ?></div>
                    </div>
                    <div class="blockforce-stat-card">
                        <div class="blockforce-stat-number"><?php echo (int) $stats['active_blocks']; ?></div>
                        <div class="blockforce-stat-label"><?php esc_html_e('Active Blocks', $this->text_domain); ?></div>
                    </div>
                </div>
                <div class="blockforce-login-url-box">
                    <strong><?php echo $slug ? '<span class="dashicons dashicons-lock" style="color:#d63638"></span> ' . esc_html__('Secret URL:', $this->text_domain) : '<span class="dashicons dashicons-admin-home" style="color:#00a32a"></span> ' . esc_html__('Default URL:', $this->text_domain); ?></strong>
                    <div class="blockforce-login-url"><?php echo esc_url($slug ? $url . '/' . $slug : $url . '/wp-login.php'); ?>
                    </div>
                </div>
                <div class="blockforce-status-indicators">
                    <div class="blockforce-status-badge <?php echo $ip_en ? 'enabled' : 'disabled'; ?>"><span
                            class="dashicons dashicons-<?php echo $ip_en ? 'yes-alt' : 'dismiss'; ?>"></span>
                        <?php esc_html_e('IP Block', $this->text_domain); ?></div>
                    <div class="blockforce-status-badge <?php echo $url_en ? 'enabled' : 'disabled'; ?>"><span
                            class="dashicons dashicons-<?php echo $url_en ? 'yes-alt' : 'dismiss'; ?>"></span>
                        <?php esc_html_e('Auto URL', $this->text_domain); ?></div>
                </div>
                <div class="blockforce-quick-actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=blockforce-wp')); ?>"
                        class="button button-primary"><?php esc_html_e('Overview', $this->text_domain); ?></a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=blockforce-wp-settings')); ?>"
                        class="button"><?php esc_html_e('Settings', $this->text_domain); ?></a>
                </div>
            </div>
        </div>
        <?php
    }
    private function get_attack_statistics()
    {
        global $wpdb;
        $blocks = $wpdb->prefix . BFWP_BLOCKS_TABLE;
        $logs = $wpdb->prefix . BFWP_LOGS_TABLE;
        $now = current_time('mysql');
        $ago = date('Y-m-d H:i:s', strtotime('-1 day', current_time('timestamp')));
        $active = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $blocks WHERE expires_at > %s", $now));
        $bl_today = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $blocks WHERE blocked_at > %s", $ago));
        $att_today = 0;
        if ($wpdb->get_var("SHOW TABLES LIKE '$logs'") === $logs) {
            $att_today = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $logs WHERE status = 'failed' AND time > %s", $ago));
        }
        return array('blocked_today' => $bl_today, 'attempts_today' => $att_today, 'active_blocks' => $active);
    }
}
