<?php
/**
 * BlockForce WP Dashboard Widget
 *
 * @package BlockForce_WP
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class BlockForce_WP_Dashboard
 *
 * Handles the WordPress admin dashboard widget functionality.
 */
class BlockForce_WP_Dashboard {

    /**
     * Plugin settings
     *
     * @var array
     */
    private $settings;

    /**
     * Main plugin controller
     *
     * @var BlockForce_WP
     */
    private $core;

    /**
     * Text domain for translations
     *
     * @var string
     */
    private $text_domain = BFWP_TEXT_DOMAIN;

    /**
     * Constructor
     *
     * @param array         $settings Plugin settings.
     * @param BlockForce_WP $core     Main plugin controller.
     */
    public function __construct($settings, $core) {
        $this->settings = $settings;
        $this->core = $core;
    }

    /**
     * Initialize hooks
     *
     * @return void
     */
    public function init_hooks() {
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
    }

    /**
     * Add dashboard widget
     *
     * @return void
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'blockforce_wp_dashboard_widget',
            '<span class="dashicons dashicons-shield"></span> ' . __('BlockForce WP Security', $this->text_domain),
            array($this, 'render_dashboard_widget')
        );
    }

    /**
     * Render dashboard widget content
     *
     * @return void
     */
    public function render_dashboard_widget() {
        $current_slug = $this->core->login_url->get_login_slug();
        $site_url = get_site_url();
        $ip_blocking_enabled = isset($this->settings['enable_ip_blocking']) ? $this->settings['enable_ip_blocking'] : 1;
        $url_change_enabled = isset($this->settings['enable_url_change']) ? $this->settings['enable_url_change'] : 1;
        
        // Get statistics
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
                <!-- Statistics Grid -->
                <div class="blockforce-stat-grid">
                    <div class="blockforce-stat-card <?php echo $stats['blocked_today'] > 0 ? 'danger' : 'success'; ?>">
                        <div class="blockforce-stat-number"><?php echo esc_html($stats['blocked_today']); ?></div>
                        <div class="blockforce-stat-label"><?php esc_html_e('Blocked Today', $this->text_domain); ?></div>
                    </div>
                    <div class="blockforce-stat-card <?php echo $stats['attempts_today'] > 0 ? 'warning' : 'success'; ?>">
                        <div class="blockforce-stat-number"><?php echo esc_html($stats['attempts_today']); ?></div>
                        <div class="blockforce-stat-label"><?php esc_html_e('Failed Attempts', $this->text_domain); ?></div>
                    </div>
                    <div class="blockforce-stat-card">
                        <div class="blockforce-stat-number"><?php echo esc_html($stats['active_blocks']); ?></div>
                        <div class="blockforce-stat-label"><?php esc_html_e('Active Blocks', $this->text_domain); ?></div>
                    </div>
                </div>
                
                <!-- Current Login URL -->
                <div class="blockforce-login-url-box">
                    <strong>
                        <?php if ($current_slug): ?>
                            <span class="dashicons dashicons-lock" style="color: #d63638;"></span>
                            <?php esc_html_e('Secret Login URL:', $this->text_domain); ?>
                        <?php else: ?>
                            <span class="dashicons dashicons-admin-home" style="color: #00a32a;"></span>
                            <?php esc_html_e('Default Login URL:', $this->text_domain); ?>
                        <?php endif; ?>
                    </strong>
                    <div class="blockforce-login-url">
                        <?php echo esc_url($current_slug ? $site_url . '/' . $current_slug : $site_url . '/wp-login.php'); ?>
                    </div>
                </div>
                
                <!-- Protection Status -->
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
                
                <!-- Quick Actions -->
                <div class="blockforce-quick-actions">
                    <a href="<?php echo esc_url(admin_url('options-general.php?page=blockforce-wp')); ?>" class="button button-primary">
                        <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span>
                        <?php esc_html_e('Settings', $this->text_domain); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('options-general.php?page=blockforce-wp&tab=reset')); ?>" class="button">
                        <span class="dashicons dashicons-update" style="margin-top: 3px;"></span>
                        <?php esc_html_e('Reset', $this->text_domain); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Get attack statistics
     *
     * @return array Statistics data.
     */
    private function get_attack_statistics() {
        global $wpdb;
        
        // Get all transients related to BlockForce
        $blocked_ips = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value 
                 FROM {$wpdb->options} 
                 WHERE option_name LIKE %s 
                 AND option_name NOT LIKE %s",
                $wpdb->esc_like('_transient_bfwp_blocked_') . '%',
                $wpdb->esc_like('_transient_timeout_') . '%'
            )
        );
        
        $attempt_logs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value 
                 FROM {$wpdb->options} 
                 WHERE option_name LIKE %s 
                 AND option_name NOT LIKE %s",
                $wpdb->esc_like('_transient_bfwp_attempts_') . '%',
                $wpdb->esc_like('_transient_timeout_') . '%'
            )
        );
        
        $current_time = time();
        $day_ago = $current_time - DAY_IN_SECONDS;
        
        $blocked_today = 0;
        $attempts_today = 0;
        $active_blocks = 0;
        
        // Count active blocks
        foreach ($blocked_ips as $blocked) {
            $block_time = intval($blocked->option_value);
            if ($block_time > 0) {
                $active_blocks++;
                if ($block_time >= $day_ago) {
                    $blocked_today++;
                }
            }
        }
        
        // Count recent attempts
        foreach ($attempt_logs as $log) {
            $attempts = maybe_unserialize($log->option_value);
            if (is_array($attempts)) {
                foreach ($attempts as $timestamp) {
                    if ($timestamp >= $day_ago) {
                        $attempts_today++;
                    }
                }
            }
        }
        
        return array(
            'blocked_today' => $blocked_today,
            'attempts_today' => $attempts_today,
            'active_blocks' => $active_blocks,
        );
    }
}
