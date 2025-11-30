<?php

if (!defined('ABSPATH')) {
    exit;
}

class BlockForce_WP_Admin
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('admin_init', array($this, 'handle_plugin_reset'));
        add_action('admin_init', array($this, 'handle_test_email'));
        add_action('admin_init', array($this, 'handle_bulk_unblock'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        add_action('update_option_blockforce_settings', array($this, 'on_settings_update'), 10, 2);
        add_filter('plugin_action_links_' . $this->core->basename, array($this, 'add_settings_link'));
    }

    public function add_settings_link($links)
    {
        $settings_link = '<a href="' . admin_url('options-general.php?page=blockforce-wp') . '">' . __('Settings', $this->text_domain) . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Enqueue admin styles for better UI
     */
    public function enqueue_admin_styles($hook)
    {
        if ($hook !== 'settings_page_blockforce-wp') {
            return;
        }

        // Add inline styles for better UI
        $custom_css = "
            .blockforce-card {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                padding: 20px;
                margin-bottom: 20px;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .blockforce-card h2 {
                margin-top: 0;
                padding-bottom: 10px;
                border-bottom: 1px solid #f0f0f1;
            }
            .blockforce-status-box {
                background: #f6f7f7;
                padding: 15px;
                border-left: 4px solid #0073aa;
                margin: 15px 0;
                border-radius: 2px;
            }
            .blockforce-status-active {
                border-left-color: #d63638;
            }
            .blockforce-status-default {
                border-left-color: #00a32a;
            }
            .blockforce-url-display {
                background: #fff;
                padding: 8px 12px;
                border: 1px solid #ddd;
                border-radius: 3px;
                font-family: monospace;
                font-size: 14px;
                display: inline-block;
                margin-top: 5px;
                word-break: break-all;
            }
            .blockforce-help-box {
                background: #f0f6fc;
                border-left: 4px solid #0073aa;
                padding: 12px 15px;
                margin: 15px 0;
                border-radius: 2px;
            }
            .blockforce-help-box h4 {
                margin-top: 0;
                color: #0073aa;
            }
            .blockforce-warning-box {
                background: #fcf9e8;
                border-left: 4px solid #dba617;
                padding: 12px 15px;
                margin: 15px 0;
                border-radius: 2px;
            }
            .blockforce-info-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 15px;
                margin: 20px 0;
            }
            .blockforce-info-item {
                background: #f6f7f7;
                padding: 15px;
                border-radius: 4px;
                border: 1px solid #ddd;
            }
            .blockforce-info-item h3 {
                margin-top: 0;
                font-size: 14px;
                color: #1d2327;
            }
            .blockforce-badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: 600;
                margin-left: 8px;
            }
            .blockforce-badge-enabled {
                background: #d7f0db;
                color: #00a32a;
            }
            .blockforce-badge-disabled {
                background: #f0f0f1;
                color: #646970;
            }
            .blockforce-feature-list {
                list-style: none;
                padding: 0;
            }
            .blockforce-feature-list li {
                padding: 8px 0;
                padding-left: 25px;
                position: relative;
            }
            .blockforce-feature-list li:before {
                content: '✓';
                position: absolute;
                left: 0;
                color: #00a32a;
                font-weight: bold;
            }
            
            /* Mobile Responsive Styles */
            @media screen and (max-width: 782px) {
                .blockforce-card {
                    padding: 15px;
                    margin-bottom: 15px;
                }
                
                .blockforce-info-grid {
                    grid-template-columns: 1fr;
                    gap: 10px;
                }
                
                .blockforce-url-display {
                    font-size: 12px;
                    padding: 6px 10px;
                    display: block;
                    width: 100%;
                    box-sizing: border-box;
                }
                
                    display: block;
                    margin-left: 0;
                    margin-top: 5px;
                    text-align: center;
                }
                
                .form-table th,
                .form-table td {
                    display: block;
                    width: 100%;
                    padding: 10px 0;
                }
                
                .form-table th {
                    padding-bottom: 5px;
                }
                
                .form-table td {
                    padding-top: 0;
                }
                
                input[type='number'],
                input[type='email'],
                .regular-text {
                    width: 100% !important;
                    max-width: 100%;
                }
            }
            
            @media screen and (max-width: 600px) {
                .blockforce-card h2 {
                    font-size: 18px;
                }
                
                .nav-tab-wrapper .nav-tab {
                    font-size: 13px;
                    padding: 8px 12px;
                }
            }
            
            /* Improved button styles for mobile */
            @media screen and (max-width: 782px) {
                .button,
                .button-primary,
                .button-secondary {
                    padding: 10px 15px;
                    height: auto;
                    line-height: 1.4;
                }
                
                .button .dashicons {
                    vertical-align: middle;
                }
            }
            
            /* Blocked IPs Table */
            .blockforce-ips-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 15px;
                background: #fff;
            }
            .blockforce-ips-table th,
            .blockforce-ips-table td {
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid #ddd;
            }
            .blockforce-ips-table th {
                background: #f6f7f7;
                font-weight: 600;
                font-size: 13px;
            }
            .blockforce-ips-table tbody tr:hover {
                background: #f9f9f9;
            }
            .blockforce-ips-table input[type=\"checkbox\"] {
                margin: 0;
            }
            .blockforce-bulk-actions {
                display: flex;
                gap: 10px;
                align-items: center;
                margin-bottom: 10px;
            }
            @media screen and (max-width: 782px) {
                .blockforce-ips-table {
                    font-size: 12px;
                }
                .blockforce-ips-table th,
                .blockforce-ips-table td {
                    padding: 8px 6px;
                }
                .blockforce-bulk-actions {
                    flex-direction: column;
                    align-items: stretch;
                }
                .blockforce-bulk-actions select,
                .blockforce-bulk-actions button {
                    width: 100%;
                }
            }
        ";
        wp_add_inline_style('wp-admin', $custom_css);
    }

    /**
     * Flush rewrites when settings are updated
     */
    public function on_settings_update($old_value, $new_value)
    {
        $this->core->login_url->flush_rewrite_rules();
    }

    public function add_admin_menu()
    {
        add_options_page(
            __('BlockForce WP Settings', $this->text_domain),
            __('BlockForce WP', $this->text_domain),
            'manage_options',
            'blockforce-wp',
            array($this, 'options_page')
        );
    }

    public function settings_init()
    {
        register_setting('blockforce_settings', 'blockforce_settings', array($this, 'sanitize_settings'));
        add_settings_section('blockforce_main_section', __('Security Configuration', $this->text_domain), array($this, 'settings_section_callback'), 'blockforce_settings');
        add_settings_field('attempt_limit', __('Maximum Failed Attempts', $this->text_domain), array($this, 'attempt_limit_render'), 'blockforce_settings', 'blockforce_main_section');
        add_settings_field('block_time', __('IP Block Duration', $this->text_domain), array($this, 'block_time_render'), 'blockforce_settings', 'blockforce_main_section');
        add_settings_field('log_time', __('Attack Monitoring Window', $this->text_domain), array($this, 'log_time_render'), 'blockforce_settings', 'blockforce_main_section');
        add_settings_field('enable_ip_blocking', __('Enable IP Blocking', $this->text_domain), array($this, 'enable_ip_blocking_render'), 'blockforce_settings', 'blockforce_main_section');
        add_settings_field('enable_url_change', __('Enable Auto URL Change', $this->text_domain), array($this, 'enable_url_change_render'), 'blockforce_settings', 'blockforce_main_section');
        add_settings_field('alert_email', __('Security Alert Email', $this->text_domain), array($this, 'alert_email_render'), 'blockforce_settings', 'blockforce_main_section');
    }

    public function settings_section_callback()
    {
        ?>
        <p><?php esc_html_e('Configure how BlockForce WP protects your WordPress login page from brute-force attacks.', $this->text_domain); ?>
        </p>
        <?php
    }

    public function sanitize_settings($input)
    {
        $output = array();
        $output['attempt_limit'] = max(1, (int) $input['attempt_limit']); 
        $output['block_time'] = max(1, (int) $input['block_time']); 
        $output['log_time'] = max(1, (int) $input['log_time']);
        $output['enable_url_change'] = isset($input['enable_url_change']) ? 1 : 0;
        $output['enable_ip_blocking'] = isset($input['enable_ip_blocking']) ? 1 : 0;
        $output['alert_email'] = isset($input['alert_email']) ? sanitize_email($input['alert_email']) : '';
        add_settings_error('blockforce_settings', 'settings_updated', __('Settings saved successfully!', $this->text_domain), 'updated');
        return $output;
    }

    // --- Settings Render Functions ---

    public function attempt_limit_render()
    {
        ?>
        <input type="number" name="blockforce_settings[attempt_limit]"
            value="<?php echo esc_attr($this->settings['attempt_limit']); ?>" min="1" max="100" style="width: 100px;">
        <p class="description">
            <?php esc_html_e('Failed attempts before triggering protection. Default: 2', $this->text_domain); ?>
        </p>
        <?php
    }

    public function block_time_render() {
        $minutes = round($this->settings['block_time'] / 60, 1);
        ?>
        <input type="number" name="blockforce_settings[block_time]" value="<?php echo esc_attr($this->settings['block_time']); ?>" min="1" style="width: 120px;"> 
        <span class="description"><?php esc_html_e('seconds', $this->text_domain); ?></span>
        <span style="margin-left: 10px; color: #646970;">(<?php echo esc_html(sprintf(__('≈ %s minutes', $this->text_domain), $minutes)); ?>)</span>
        <p class="description">
            <?php esc_html_e('Block duration for malicious IPs. Default: 120 seconds', $this->text_domain); ?>
        </p>
        <?php
    }


    public function enable_ip_blocking_render()
    {
        $enabled = $this->settings['enable_ip_blocking'];
        ?>
        <label>
            <input type="checkbox" name="blockforce_settings[enable_ip_blocking]" value="1" <?php checked(1, $enabled); ?>>
            <?php esc_html_e('Block IPs after failed attempts', $this->text_domain); ?>
            <span class="blockforce-badge <?php echo $enabled ? 'blockforce-badge-enabled' : 'blockforce-badge-disabled'; ?>">
                <?php echo $enabled ? esc_html__('ENABLED', $this->text_domain) : esc_html__('DISABLED', $this->text_domain); ?>
            </span>
        </label>
        <?php
    }

    public function log_time_render()
    {
        $hours = round($this->settings['log_time'] / 3600, 1);
        ?>
        <input type="number" name="blockforce_settings[log_time]" value="<?php echo esc_attr($this->settings['log_time']); ?>"
            min="1" style="width: 120px;">
        <span class="description"><?php esc_html_e('seconds', $this->text_domain); ?></span>
        <span
            style="margin-left: 10px; color: #646970;">(<?php echo esc_html(sprintf(__('≈ %s hours', $this->text_domain), $hours)); ?>)</span>
        <p class="description">
            <?php esc_html_e('Monitoring window for persistent attacks. Default: 7200 seconds', $this->text_domain); ?>
        </p>
        <?php
    }

    public function enable_url_change_render()
    {
        $enabled = $this->settings['enable_url_change'];
        ?>
        <label>
            <input type="checkbox" name="blockforce_settings[enable_url_change]" value="1" <?php checked(1, $enabled); ?>>
            <?php esc_html_e('Auto-change login URL on persistent attacks', $this->text_domain); ?>
            <span class="blockforce-badge <?php echo $enabled ? 'blockforce-badge-enabled' : 'blockforce-badge-disabled'; ?>">
                <?php echo $enabled ? esc_html__('ENABLED', $this->text_domain) : esc_html__('DISABLED', $this->text_domain); ?>
            </span>
        </label>
        <?php
    }

    public function alert_email_render()
    {
        $email = isset($this->settings['alert_email']) ? $this->settings['alert_email'] : '';
        $default_email = get_option('admin_email');
        $test_email_url = wp_nonce_url(
            admin_url('options-general.php?page=blockforce-wp&tab=settings&blockforce_test_email=1'),
            'blockforce_test_email_nonce',
            '_wpnonce_test_email'
        );
        ?>
        <input type="email" name="blockforce_settings[alert_email]" value="<?php echo esc_attr($email); ?>" class="regular-text"
            placeholder="<?php echo esc_attr($default_email); ?>">
        <p class="description">
            <?php esc_html_e('Receive security alerts here. Default:', $this->text_domain); ?>
            <?php echo esc_html($default_email); ?>
        </p>

        <div class="blockforce-warning-box" style="margin-top: 15px;">
            <p style="margin: 0;"><strong><?php esc_html_e('Warning:', $this->text_domain); ?></strong>
                <?php esc_html_e('Test email delivery below. URL changes only if email succeeds.', $this->text_domain); ?></p>
        </div>

        <p style="margin-top: 15px;">
            <a href="<?php echo esc_url($test_email_url); ?>" class="button button-secondary">
                <span class="dashicons dashicons-email" style="margin-top: 3px;"></span>
                <?php esc_html_e('Test Email Delivery', $this->text_domain); ?>
            </a>
        </p>
        <?php
    }

    // --- Admin Page Display ---

    public function options_page()
    {
        $tabs = array(
            'overview' => __('Overview', $this->text_domain),
            'settings' => __('Security Settings', $this->text_domain),
            'reset' => __('Reset', $this->text_domain)
        );
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';
        $current_tab = array_key_exists($current_tab, $tabs) ? $current_tab : 'overview';
        ?>
        <div class="wrap">
            <h1>
                <span class="dashicons dashicons-shield" style="font-size: 32px; width: 32px; height: 32px;"></span>
                <?php esc_html_e('BlockForce WP', $this->text_domain); ?>
            </h1>
            <p class="description" style="font-size: 14px; margin-bottom: 20px;">
                <?php esc_html_e('Advanced login security and brute-force attack protection for WordPress', $this->text_domain); ?>
            </p>

            <h2 class="nav-tab-wrapper">
                <?php foreach ($tabs as $tab => $name): ?>
                    <a href="?page=blockforce-wp&tab=<?php echo esc_attr($tab); ?>"
                        class="nav-tab <?php echo $current_tab == $tab ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($name); ?>
                    </a>
                <?php endforeach; ?>
            </h2>

            <?php
            if ($current_tab == 'overview') {
                $this->display_overview_tab();
            } elseif ($current_tab == 'settings') {
                $this->display_settings_tab();
            } elseif ($current_tab == 'reset') {
                $this->display_reset_tab();
            }
            ?>
        </div>
        <?php
    }

    private function display_overview_tab()
    {
        $current_slug = $this->core->login_url->get_login_slug();
        $site_url = get_site_url();
        $ip_blocking_enabled = $this->settings['enable_ip_blocking'];
        $url_change_enabled = $this->settings['enable_url_change'];
        ?>

        <!-- Current Status Card -->
        <div class="blockforce-card">
            <h2><?php esc_html_e('Current Login Status', $this->text_domain); ?></h2>
            <div
                class="blockforce-status-box <?php echo $current_slug ? 'blockforce-status-active' : 'blockforce-status-default'; ?>">
                <?php if ($current_slug): ?>
                    <p style="margin: 0 0 10px 0;">
                        <span style="color: #d63638; font-size: 18px;">●</span>
                        <strong
                            style="font-size: 16px;"><?php esc_html_e('Secret Login URL is ACTIVE', $this->text_domain); ?></strong>
                    </p>
                    <p style="margin: 0;">
                        <strong><?php esc_html_e('Your login page is now located at:', $this->text_domain); ?></strong>
                    </p>
                    <div class="blockforce-url-display">
                        <?php echo esc_url($site_url . '/' . $current_slug); ?>
                    </div>
                    <p style="margin-top: 15px; color: #646970;">
                        <span class="dashicons dashicons-info"></span>
                        <?php esc_html_e('Bookmark this URL! The default wp-login.php is now disabled.', $this->text_domain); ?>
                    </p>
                <?php else: ?>
                    <p style="margin: 0 0 10px 0;">
                        <span style="color: #00a32a; font-size: 18px;">●</span>
                        <strong
                            style="font-size: 16px;"><?php esc_html_e('Default Login URL is Active', $this->text_domain); ?></strong>
                    </p>
                    <p style="margin: 0;">
                        <strong><?php esc_html_e('Your login page is at the standard location:', $this->text_domain); ?></strong>
                    </p>
                    <div class="blockforce-url-display">
                        <?php echo esc_url($site_url . '/wp-login.php'); ?>
                    </div>
                    <p style="margin-top: 15px; color: #646970;">
                        <span class="dashicons dashicons-info"></span>
                        <?php esc_html_e('The URL will automatically change if an attack is detected (if enabled in settings).', $this->text_domain); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Blocked IPs Section -->
        <div class="blockforce-card" style="margin-top: 20px;">
            <h2><?php esc_html_e('Blocked IP Addresses', $this->text_domain); ?></h2>
            <?php settings_errors('blockforce_bulk'); ?>
            <?php
            global $wpdb;
            $blocked_ips = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT option_name, option_value 
                     FROM {$wpdb->options} 
                     WHERE option_name LIKE %s 
                     AND option_name NOT LIKE %s",
                    $wpdb->esc_like('bfwp_blocked_') . '%',
                    $wpdb->esc_like('_transient_') . '%' // Exclude transients just in case
                )
            );
            
            if (!empty($blocked_ips)): ?>
                <form method="post" action="">
                    <?php wp_nonce_field('blockforce_bulk_unblock', '_wpnonce_bulk'); ?>
                    <div class="blockforce-bulk-actions">
                        <select name="blockforce_bulk_action">
                            <option value=""><?php esc_html_e('Bulk Actions', $this->text_domain); ?></option>
                            <option value="unblock"><?php esc_html_e('Delete Record', $this->text_domain); ?></option>
                        </select>
                        <button type="submit" class="button"><?php esc_html_e('Apply', $this->text_domain); ?></button>
                    </div>
                    
                    <table class="blockforce-ips-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;"><input type="checkbox" id="select-all-ips"></th>
                                <th><?php esc_html_e('IP Address', $this->text_domain); ?></th>
                                <th><?php esc_html_e('Blocked Since', $this->text_domain); ?></th>
                                <th><?php esc_html_e('Status', $this->text_domain); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($blocked_ips as $blocked): 
                                $ip = str_replace('bfwp_blocked_', '', $blocked->option_name);
                                $parts = explode('|', $blocked->option_value);
                                
                                if (count($parts) === 2) {
                                    $blocked_time = intval($parts[0]);
                                    $expires_at = intval($parts[1]);
                                    $is_active = time() < $expires_at;
                                    $time_left = $expires_at - time();
                                } else {
                                    // Fallback for old permanent blocks
                                    $blocked_time = intval($blocked->option_value);
                                    $is_active = true; // Treat as permanent if no expiry
                                    $time_left = 0;
                                }
                                ?>
                                <tr>
                                    <td><input type="checkbox" name="blocked_ips[]" value="<?php echo esc_attr($ip); ?>"></td>
                                    <td><strong><?php echo esc_html($ip); ?></strong></td>
                                    <td><?php echo esc_html(human_time_diff($blocked_time, time()) . ' ago'); ?></td>
                                    <td>
                                        <?php if ($is_active): ?>
                                            <span class="blockforce-badge blockforce-badge-enabled"><?php esc_html_e('Active', $this->text_domain); ?></span>
                                            <span style="color: #646970; font-size: 12px; margin-left: 5px;">
                                                (<?php echo esc_html(sprintf(__('%s left', $this->text_domain), human_time_diff(time(), $expires_at))); ?>)
                                            </span>
                                        <?php else: ?>
                                            <span class="blockforce-badge blockforce-badge-disabled"><?php esc_html_e('Expired', $this->text_domain); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <script>
                        document.getElementById('select-all-ips').addEventListener('change', function () {
                            var checkboxes = document.querySelectorAll('input[name="blocked_ips[]"]');
                            checkboxes.forEach(function (checkbox) {
                                checkbox.checked = this.checked;
                            }, this);
                        });
                    </script>
                </form>
            <?php else: ?>
                <p><?php esc_html_e('No IP addresses are currently blocked.', $this->text_domain); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    private function display_settings_tab()
    {
        ?>
        <div class="blockforce-card">
            <h2><?php esc_html_e('⚙️ Security Configuration', $this->text_domain); ?></h2>
            <p><?php esc_html_e('Customize how BlockForce WP protects your WordPress site. Hover over the help icons for detailed explanations.', $this->text_domain); ?>
            </p>

            <form action="options.php" method="post">
                <?php
                settings_fields('blockforce_settings');
                do_settings_sections('blockforce_settings');
                submit_button(__('Save Settings', $this->text_domain), 'primary large');
                ?>
            </form>
        </div>
        <?php
    }


    // --- Bulk Unblock Handler ---

    public function handle_bulk_unblock()
    {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['blockforce_bulk_action']) && $_POST['blockforce_bulk_action'] === 'unblock') {
            if (!isset($_POST['_wpnonce_bulk']) || !wp_verify_nonce(sanitize_key($_POST['_wpnonce_bulk']), 'blockforce_bulk_unblock')) {
                wp_die(__('Security check failed.', $this->text_domain));
            }

            if (isset($_POST['blocked_ips']) && is_array($_POST['blocked_ips'])) {
                $count = 0;
                foreach ($_POST['blocked_ips'] as $ip) {
                    $ip = sanitize_text_field($ip);
                    delete_option('bfwp_blocked_' . $ip);
                    $count++;
                }

                add_settings_error(
                    'blockforce_bulk',
                    'bulk_success',
                    sprintf(
                        _n('%d IP unblocked successfully.', '%d IPs unblocked successfully.', $count, $this->text_domain),
                        $count
                    ),
                    'updated'
                );
            }

            $redirect_url = admin_url('options-general.php?page=blockforce-wp&tab=overview');
            wp_safe_redirect($redirect_url);
            exit;
        }
    }

    // --- Reset Tab & Logic ---

    public function handle_plugin_reset()
    {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        if (isset($_GET['blockforce_reset']) && $_GET['blockforce_reset'] === '1') {
            if (!isset($_GET['_wpnonce_reset']) || !wp_verify_nonce(sanitize_key($_GET['_wpnonce_reset']), 'blockforce_reset_nonce')) {
                wp_die(__('Security check failed.', $this->text_domain));
            }

            // Clear all transients and permanent blocks
            blockforce_wp_clear_all_transients();

            // Clear permanent IP blocks
            global $wpdb;
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                    $wpdb->esc_like('bfwp_blocked_') . '%'
                )
            );

            // Reset login slug
            update_option('blockforce_login_slug', '');
            $this->core->login_url->flush_rewrite_rules();

            add_settings_error(
                'blockforce_reset',
                'reset_success',
                __('Plugin reset successfully! All blocks cleared and login URL restored to wp-login.php.', $this->text_domain),
                'updated'
            );

            $redirect_url = admin_url('options-general.php?page=blockforce-wp&tab=reset&settings-updated=true');
            wp_safe_redirect($redirect_url);
            exit;
        }
    }

    /**
     * Handle test email request
     *
     * @return void
     */
    public function handle_test_email()
    {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        if (isset($_GET['blockforce_test_email']) && $_GET['blockforce_test_email'] === '1') {
            if (!isset($_GET['_wpnonce_test_email']) || !wp_verify_nonce(sanitize_key($_GET['_wpnonce_test_email']), 'blockforce_test_email_nonce')) {
                wp_die(__('Security check failed.', $this->text_domain));
            }

            // Get the alert email
            $alert_email = isset($this->settings['alert_email']) && !empty($this->settings['alert_email'])
                ? $this->settings['alert_email']
                : get_option('admin_email');

            // Send test email
            $subject = 'BlockForce WP - Test Email';
            $message = '<html><body>';
            $message .= '<h2>BlockForce WP Test Email</h2>';
            $message .= '<p>This is a test email to verify that your WordPress site can send emails successfully.</p>';
            $message .= '<p><strong>Email Configuration:</strong></p>';
            $message .= '<ul>';
            $message .= '<li>Recipient: ' . esc_html($alert_email) . '</li>';
            $message .= '<li>Sent at: ' . current_time('mysql') . '</li>';
            $message .= '<li>Site URL: ' . get_site_url() . '</li>';
            $message .= '</ul>';
            $message .= '<p>If you received this email, your email delivery is working correctly and BlockForce WP will be able to send you security alerts.</p>';
            $message .= '</body></html>';

            add_filter('wp_mail_content_type', array('BlockForce_WP_Utils', 'set_html_content_type'));
            $sent = wp_mail($alert_email, $subject, $message);
            remove_filter('wp_mail_content_type', array('BlockForce_WP_Utils', 'set_html_content_type'));

            if ($sent) {
                add_settings_error(
                    'blockforce_test_email',
                    'test_email_success',
                    sprintf(
                        __('Test email sent successfully to %s. Please check your inbox.', $this->text_domain),
                        $alert_email
                    ),
                    'updated'
                );
            } else {
                add_settings_error(
                    'blockforce_test_email',
                    'test_email_failed',
                    __('Failed to send test email. Please check your email configuration. WARNING: If email delivery does not work, you may get locked out if the login URL changes!', $this->text_domain),
                    'error'
                );
            }

            $redirect_url = admin_url('options-general.php?page=blockforce-wp&tab=settings&settings-updated=true');
            wp_safe_redirect($redirect_url);
            exit;
        }
    }

    private function display_reset_tab()
    {
        $reset_url = wp_nonce_url(
            admin_url('options-general.php?page=blockforce-wp&tab=reset&blockforce_reset=1'),
            'blockforce_reset_nonce',
            '_wpnonce_reset'
        );
        ?>
        <div class="blockforce-card">
            <h2><?php esc_html_e('Reset & Maintenance Tools', $this->text_domain); ?></h2>
            <p><?php esc_html_e('Reset the plugin to its initial state. This will clear all blocks and restore the default login URL.', $this->text_domain); ?>
            </p>

            <?php settings_errors('blockforce_reset'); ?>

            <div class="blockforce-warning-box" style="margin: 20px 0;">
                <p style="margin: 0;"><strong><?php esc_html_e('Warning:', $this->text_domain); ?></strong>
                    <?php esc_html_e('This action will clear all IP blocks, failed attempt logs, and reset the login URL to wp-login.php. Your settings will be preserved.', $this->text_domain); ?>
                </p>
            </div>

            <p>
                <a href="<?php echo esc_url($reset_url); ?>"
                    onclick="return confirm('<?php echo esc_js(__('Are you sure you want to reset the plugin?\n\nThis will:\n• Clear all IP blocks and logs\n• Reset login URL to wp-login.php\n• Keep your settings', $this->text_domain)); ?>')"
                    class="button button-secondary button-large"
                    style="background-color: #d63638; color: #fff; border-color: #d63638;">
                    <span class="dashicons dashicons-update" style="margin-top: 3px;"></span>
                    <?php esc_html_e('Reset Plugin', $this->text_domain); ?>
                </a>
            </p>
        </div>
        <?php
    }
}
