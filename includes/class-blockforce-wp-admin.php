<?php

if (!defined('ABSPATH')) {
    exit;
}

class BlockForce_WP_Admin {

    private $settings;
    private $core;
    private $text_domain = BFWP_TEXT_DOMAIN;

    public function __construct($settings, $core) {
        $this->settings = $settings;
        $this->core = $core;
    }

    public function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('admin_init', array($this, 'handle_plugin_reset')); 
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        add_action('update_option_blockforce_settings', array($this, 'on_settings_update'), 10, 2);
        add_filter('plugin_action_links_' . $this->core->basename, array($this, 'add_settings_link'));
    }

    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=blockforce-wp') . '">' . __('Settings', $this->text_domain) . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Enqueue admin styles for better UI
     */
    public function enqueue_admin_styles($hook) {
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
        ";
        wp_add_inline_style('wp-admin', $custom_css);
    }

    /**
     * Flush rewrites when settings are updated
     */
    public function on_settings_update($old_value, $new_value) {
        $this->core->login_url->flush_rewrite_rules();
    }

    public function add_admin_menu() {
        add_options_page(
            __('BlockForce WP Settings', $this->text_domain),
            __('BlockForce WP', $this->text_domain),
            'manage_options',
            'blockforce-wp',
            array($this, 'options_page')
        );
    }

    public function settings_init() {
        register_setting('blockforce_settings', 'blockforce_settings', array($this, 'sanitize_settings'));
        add_settings_section('blockforce_main_section', __('Security Configuration', $this->text_domain), array($this, 'settings_section_callback'), 'blockforce_settings');
        add_settings_field('attempt_limit', __('Maximum Failed Attempts', $this->text_domain), array($this, 'attempt_limit_render'), 'blockforce_settings', 'blockforce_main_section');
        add_settings_field('block_time', __('IP Block Duration', $this->text_domain), array($this, 'block_time_render'), 'blockforce_settings', 'blockforce_main_section');
        add_settings_field('log_time', __('Attack Monitoring Window', $this->text_domain), array($this, 'log_time_render'), 'blockforce_settings', 'blockforce_main_section');
        add_settings_field('enable_ip_blocking', __('Enable IP Blocking', $this->text_domain), array($this, 'enable_ip_blocking_render'), 'blockforce_settings', 'blockforce_main_section'); 
        add_settings_field('enable_url_change', __('Enable Auto URL Change', $this->text_domain), array($this, 'enable_url_change_render'), 'blockforce_settings', 'blockforce_main_section');
        add_settings_field('alert_email', __('Security Alert Email', $this->text_domain), array($this, 'alert_email_render'), 'blockforce_settings', 'blockforce_main_section');
    }

    public function settings_section_callback() {
        ?>
        <p><?php esc_html_e('Configure how BlockForce WP protects your WordPress login page from brute-force attacks.', $this->text_domain); ?></p>
        <?php
    }

    public function sanitize_settings($input) {
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

    public function attempt_limit_render() {
        ?>
        <input type="number" name="blockforce_settings[attempt_limit]" value="<?php echo esc_attr($this->settings['attempt_limit']); ?>" min="1" max="100" style="width: 100px;">
        <p class="description">
            <?php esc_html_e('Number of failed login attempts before triggering security measures.', $this->text_domain); ?>
            <br><strong><?php esc_html_e('Default: 2 attempts', $this->text_domain); ?></strong>
        </p>
        <div class="blockforce-help-box">
            <h4><?php esc_html_e('💡 How it works:', $this->text_domain); ?></h4>
            <p><?php esc_html_e('When an IP address fails to login this many times, BlockForce WP will activate protection measures based on your settings below.', $this->text_domain); ?></p>
            <p><strong><?php esc_html_e('Recommended:', $this->text_domain); ?></strong> <?php esc_html_e('2-5 attempts for maximum security, 5-10 for balanced protection.', $this->text_domain); ?></p>
        </div>
        <?php
    }
    
    public function block_time_render() {
        $minutes = round($this->settings['block_time'] / 60, 1);
        ?>
        <input type="number" name="blockforce_settings[block_time]" value="<?php echo esc_attr($this->settings['block_time']); ?>" min="1" style="width: 120px;"> 
        <span class="description"><?php esc_html_e('seconds', $this->text_domain); ?></span>
        <span style="margin-left: 10px; color: #646970;">(<?php echo esc_html(sprintf(__('≈ %s minutes', $this->text_domain), $minutes)); ?>)</span>
        <p class="description">
            <?php esc_html_e('How long to block an IP address after exceeding failed attempts.', $this->text_domain); ?>
            <br><strong><?php esc_html_e('Default: 120 seconds (2 minutes)', $this->text_domain); ?></strong>
        </p>
        <div class="blockforce-help-box">
            <h4><?php esc_html_e('💡 How it works:', $this->text_domain); ?></h4>
            <p><?php esc_html_e('When IP blocking is enabled, attackers will be completely blocked from accessing your site for this duration.', $this->text_domain); ?></p>
            <p><strong><?php esc_html_e('Common values:', $this->text_domain); ?></strong></p>
            <ul>
                <li><?php esc_html_e('120 seconds (2 min) - Quick blocks for automated bots', $this->text_domain); ?></li>
                <li><?php esc_html_e('900 seconds (15 min) - Balanced protection', $this->text_domain); ?></li>
                <li><?php esc_html_e('3600 seconds (1 hour) - Strong deterrent', $this->text_domain); ?></li>
            </ul>
        </div>
        <?php
    }
    
    public function enable_ip_blocking_render() { 
        $enabled = $this->settings['enable_ip_blocking'];
        ?>
        <label>
            <input type="checkbox" name="blockforce_settings[enable_ip_blocking]" value="1" <?php checked(1, $enabled); ?>>
            <?php esc_html_e('Temporarily block IP addresses after failed login attempts', $this->text_domain); ?>
            <span class="blockforce-badge <?php echo $enabled ? 'blockforce-badge-enabled' : 'blockforce-badge-disabled'; ?>">
                <?php echo $enabled ? esc_html__('ENABLED', $this->text_domain) : esc_html__('DISABLED', $this->text_domain); ?>
            </span>
        </label>
        <p class="description">
            <?php esc_html_e('Recommended: Keep this enabled for immediate protection against brute-force attacks.', $this->text_domain); ?>
        </p>
        <div class="blockforce-help-box">
            <h4><?php esc_html_e('💡 What this does:', $this->text_domain); ?></h4>
            <p><?php esc_html_e('When enabled, any IP that exceeds the maximum failed attempts will be temporarily banned from accessing your entire website. This provides immediate protection against automated attacks.', $this->text_domain); ?></p>
        </div>
        <?php
    }
    
    public function log_time_render() {
        $hours = round($this->settings['log_time'] / 3600, 1);
        ?>
        <input type="number" name="blockforce_settings[log_time]" value="<?php echo esc_attr($this->settings['log_time']); ?>" min="1" style="width: 120px;">
        <span class="description"><?php esc_html_e('seconds', $this->text_domain); ?></span>
        <span style="margin-left: 10px; color: #646970;">(<?php echo esc_html(sprintf(__('≈ %s hours', $this->text_domain), $hours)); ?>)</span>
        <p class="description">
            <?php esc_html_e('Time window for monitoring and counting failed login attempts from each IP.', $this->text_domain); ?>
            <br><strong><?php esc_html_e('Default: 7200 seconds (2 hours)', $this->text_domain); ?></strong>
        </p>
        <div class="blockforce-help-box">
            <h4><?php esc_html_e('💡 How it works:', $this->text_domain); ?></h4>
            <p><?php esc_html_e('BlockForce WP tracks failed attempts within this time window. If an IP accumulates enough failures within this period, the login URL will automatically change (if enabled).', $this->text_domain); ?></p>
            <p><?php esc_html_e('This helps detect slow, persistent attacks that spread attempts over time to avoid detection.', $this->text_domain); ?></p>
            <p><strong><?php esc_html_e('Recommended:', $this->text_domain); ?></strong> <?php esc_html_e('7200 seconds (2 hours) for most sites.', $this->text_domain); ?></p>
        </div>
        <?php
    }
    
    public function enable_url_change_render() {
        $enabled = $this->settings['enable_url_change'];
        ?>
        <label>
            <input type="checkbox" name="blockforce_settings[enable_url_change]" value="1" <?php checked(1, $enabled); ?>>
            <?php esc_html_e('Automatically change login URL when persistent attacks are detected', $this->text_domain); ?>
            <span class="blockforce-badge <?php echo $enabled ? 'blockforce-badge-enabled' : 'blockforce-badge-disabled'; ?>">
                <?php echo $enabled ? esc_html__('ENABLED', $this->text_domain) : esc_html__('DISABLED', $this->text_domain); ?>
            </span>
        </label>
        <p class="description">
            <?php esc_html_e('Recommended: Enable this for maximum protection against determined attackers.', $this->text_domain); ?>
        </p>
        <div class="blockforce-warning-box">
            <h4><?php esc_html_e('⚠️ Important:', $this->text_domain); ?></h4>
            <p><?php esc_html_e('When the login URL changes, you will receive an email with the new URL. Make sure your email is configured correctly!', $this->text_domain); ?></p>
            <p><?php esc_html_e('The URL will ONLY change if the email is sent successfully. This prevents you from being locked out.', $this->text_domain); ?></p>
        </div>
        <div class="blockforce-help-box">
            <h4><?php esc_html_e('💡 How it works:', $this->text_domain); ?></h4>
            <p><?php esc_html_e('When enabled, if an attacker persists beyond the monitoring window, your wp-login.php will be automatically moved to a secret, random URL (e.g., /a1b2c3d4e5f6).', $this->text_domain); ?></p>
            <p><?php esc_html_e('This makes it impossible for bots to find your login page, effectively stopping the attack.', $this->text_domain); ?></p>
        </div>
        <?php
    }

    public function alert_email_render() {
        $email = isset($this->settings['alert_email']) ? $this->settings['alert_email'] : '';
        $default_email = get_option('admin_email');
        ?>
        <input type="email" name="blockforce_settings[alert_email]" value="<?php echo esc_attr($email); ?>" class="regular-text" placeholder="<?php echo esc_attr($default_email); ?>">
        <p class="description">
            <?php esc_html_e('Email address to receive security alerts when the login URL changes.', $this->text_domain); ?>
            <br><strong><?php esc_html_e('Default:', $this->text_domain); ?></strong> <?php echo esc_html($default_email); ?>
        </p>
        <div class="blockforce-help-box">
            <h4><?php esc_html_e('💡 Email alerts:', $this->text_domain); ?></h4>
            <p><?php esc_html_e('When your login URL changes automatically, BlockForce WP will send a professional HTML email to this address containing:', $this->text_domain); ?></p>
            <ul>
                <li><?php esc_html_e('The new login URL (clickable link)', $this->text_domain); ?></li>
                <li><?php esc_html_e('The attacker\'s IP address', $this->text_domain); ?></li>
                <li><?php esc_html_e('Date and time of the attack', $this->text_domain); ?></li>
            </ul>
            <p><strong><?php esc_html_e('Important:', $this->text_domain); ?></strong> <?php esc_html_e('Make sure this email address is monitored regularly!', $this->text_domain); ?></p>
        </div>
        <?php
    }
    
    // --- Admin Page Display ---

    public function options_page() {
        $tabs = array(
            'overview' => __('Overview', $this->text_domain),
            'settings' => __('Security Settings', $this->text_domain),
            'reset'    => __('Reset', $this->text_domain)
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
                    <a href="?page=blockforce-wp&tab=<?php echo esc_attr($tab); ?>" class="nav-tab <?php echo $current_tab == $tab ? 'nav-tab-active' : ''; ?>">
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

    private function display_overview_tab() {
        $current_slug = $this->core->login_url->get_login_slug();
        $site_url = get_site_url();
        $ip_blocking_enabled = $this->settings['enable_ip_blocking'];
        $url_change_enabled = $this->settings['enable_url_change'];
        ?>
        
        <!-- Current Status Card -->
        <div class="blockforce-card">
            <h2><?php esc_html_e('🔐 Current Login Status', $this->text_domain); ?></h2>
            <div class="blockforce-status-box <?php echo $current_slug ? 'blockforce-status-active' : 'blockforce-status-default'; ?>">
                <?php if ($current_slug): ?>
                    <p style="margin: 0 0 10px 0;">
                        <span style="color: #d63638; font-size: 18px;">●</span>
                        <strong style="font-size: 16px;"><?php esc_html_e('Secret Login URL is ACTIVE', $this->text_domain); ?></strong>
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
                        <strong style="font-size: 16px;"><?php esc_html_e('Default Login URL is Active', $this->text_domain); ?></strong>
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

        <!-- Protection Status Card -->
        <div class="blockforce-card">
            <h2><?php esc_html_e('🛡️ Active Protection Features', $this->text_domain); ?></h2>
            <div class="blockforce-info-grid">
                <div class="blockforce-info-item">
                    <h3>
                        <span class="dashicons dashicons-<?php echo $ip_blocking_enabled ? 'yes-alt' : 'dismiss'; ?>" style="color: <?php echo $ip_blocking_enabled ? '#00a32a' : '#646970'; ?>;"></span>
                        <?php esc_html_e('IP Blocking', $this->text_domain); ?>
                        <span class="blockforce-badge <?php echo $ip_blocking_enabled ? 'blockforce-badge-enabled' : 'blockforce-badge-disabled'; ?>">
                            <?php echo $ip_blocking_enabled ? esc_html__('ENABLED', $this->text_domain) : esc_html__('DISABLED', $this->text_domain); ?>
                        </span>
                    </h3>
                    <p><?php esc_html_e('Temporarily blocks malicious IP addresses after failed login attempts.', $this->text_domain); ?></p>
                    <?php if ($ip_blocking_enabled): ?>
                        <p><strong><?php esc_html_e('Block duration:', $this->text_domain); ?></strong> <?php echo esc_html($this->settings['block_time']); ?> <?php esc_html_e('seconds', $this->text_domain); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="blockforce-info-item">
                    <h3>
                        <span class="dashicons dashicons-<?php echo $url_change_enabled ? 'yes-alt' : 'dismiss'; ?>" style="color: <?php echo $url_change_enabled ? '#00a32a' : '#646970'; ?>;"></span>
                        <?php esc_html_e('Auto URL Change', $this->text_domain); ?>
                        <span class="blockforce-badge <?php echo $url_change_enabled ? 'blockforce-badge-enabled' : 'blockforce-badge-disabled'; ?>">
                            <?php echo $url_change_enabled ? esc_html__('ENABLED', $this->text_domain) : esc_html__('DISABLED', $this->text_domain); ?>
                        </span>
                    </h3>
                    <p><?php esc_html_e('Automatically moves your login page to a secret URL when persistent attacks are detected.', $this->text_domain); ?></p>
                    <?php if ($url_change_enabled): ?>
                        <p><strong><?php esc_html_e('Monitoring window:', $this->text_domain); ?></strong> <?php echo esc_html($this->settings['log_time']); ?> <?php esc_html_e('seconds', $this->text_domain); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <h3 style="margin-top: 20px;"><?php esc_html_e('Additional Security Features:', $this->text_domain); ?></h3>
            <ul class="blockforce-feature-list">
                <li><?php esc_html_e('Generic login error messages to prevent username enumeration', $this->text_domain); ?></li>
                <li><?php esc_html_e('Cryptographically secure random URL generation', $this->text_domain); ?></li>
                <li><?php esc_html_e('Email alerts with attack details and new login URLs', $this->text_domain); ?></li>
                <li><?php esc_html_e('Fail-safe design: URL only changes if email is sent successfully', $this->text_domain); ?></li>
            </ul>
        </div>

        <!-- How It Works Card -->
        <div class="blockforce-card">
            <h2><?php esc_html_e('📚 How BlockForce WP Works', $this->text_domain); ?></h2>
            <p><?php esc_html_e('BlockForce WP uses a dual-layer defense system to protect your WordPress login:', $this->text_domain); ?></p>
            
            <h3><?php esc_html_e('Layer 1: IP Blocking (Fast Response)', $this->text_domain); ?></h3>
            <p><?php esc_html_e('Designed to stop rapid, automated attacks:', $this->text_domain); ?></p>
            <ul>
                <li><?php echo sprintf(esc_html__('If an IP fails %d times', $this->text_domain), $this->settings['attempt_limit']); ?></li>
                <li><?php echo sprintf(esc_html__('Within %d seconds', $this->text_domain), $this->settings['block_time']); ?></li>
                <li><?php echo sprintf(esc_html__('Then: IP is blocked for %d seconds', $this->text_domain), $this->settings['block_time']); ?></li>
            </ul>
            
            <h3 style="margin-top: 20px;"><?php esc_html_e('Layer 2: Login URL Change (Long-term Protection)', $this->text_domain); ?></h3>
            <p><?php esc_html_e('Designed to stop slow, persistent bot attacks:', $this->text_domain); ?></p>
            <ul>
                <li><?php echo sprintf(esc_html__('If an IP accumulates %d total failures', $this->text_domain), $this->settings['attempt_limit']); ?></li>
                <li><?php echo sprintf(esc_html__('Within %d seconds (monitoring window)', $this->text_domain), $this->settings['log_time']); ?></li>
                <li><?php esc_html_e('Then: Login URL changes to a secret location and you receive an email', $this->text_domain); ?></li>
            </ul>
        </div>
        <?php
    }
    
    private function display_settings_tab() {
        ?>
        <div class="blockforce-card">
            <h2><?php esc_html_e('⚙️ Security Configuration', $this->text_domain); ?></h2>
            <p><?php esc_html_e('Customize how BlockForce WP protects your WordPress site. Hover over the help icons for detailed explanations.', $this->text_domain); ?></p>
            
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

    // --- Reset Tab & Logic ---

    public function handle_plugin_reset() {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }
        
        $action_performed = null;
        $nonce_key = null;
        $nonce_name = null;
        
        if (isset($_GET['blockforce_reset']) && $_GET['blockforce_reset'] === '1') {
            $nonce_key = '_wpnonce_reset';
            $action_performed = 'full';
            $nonce_name = 'blockforce_reset_nonce';
        } elseif (isset($_GET['blockforce_reset_ips']) && $_GET['blockforce_reset_ips'] === '1') {
            $nonce_key = '_wpnonce_reset_ips';
            $action_performed = 'ips';
            $nonce_name = 'blockforce_reset_ips_nonce';
        } elseif (isset($_GET['blockforce_reset_slug']) && $_GET['blockforce_reset_slug'] === '1') {
            $nonce_key = '_wpnonce_reset_slug';
            $action_performed = 'slug';
            $nonce_name = 'blockforce_reset_slug_nonce';
        }
        
        if ($action_performed) {
            if (!isset($_GET[$nonce_key]) || !wp_verify_nonce(sanitize_key($_GET[$nonce_key]), $nonce_name)) {
                wp_die(__('Security check failed.', $this->text_domain));
            }
            
            $slug_changed = false;
            $success_message = '';
            
            if ($action_performed === 'full' || $action_performed === 'ips') {
                blockforce_wp_clear_all_transients();
                $success_message = __('✓ IP Blocks and Logs Cleared! All temporary IP bans and failed attempt logs have been removed.', $this->text_domain);
            }
            
            if ($action_performed === 'full' || $action_performed === 'slug') {
                update_option('blockforce_login_slug', '');
                $slug_changed = true;
                if ($action_performed === 'full') {
                     $success_message = __('✓ Plugin Fully Reset! All blocks, logs, and the login link have been restored to defaults.', $this->text_domain);
                } else {
                     $success_message = __('✓ Login Link Reset! Your login page has been restored to wp-login.php.', $this->text_domain);
                }
            }
            
            if ($slug_changed) {
                $this->core->login_url->flush_rewrite_rules();
            }
            
            if ($success_message) {
                 add_settings_error('blockforce_reset', 'reset_success', $success_message, 'updated');
            }
            
            $redirect_url = admin_url('options-general.php?page=blockforce-wp&tab=reset&settings-updated=true');
            wp_safe_redirect($redirect_url);
            exit;
        }
    }
    
    private function display_reset_tab() {
        $full_reset_url = wp_nonce_url(
            admin_url('options-general.php?page=blockforce-wp&tab=reset&blockforce_reset=1'),
            'blockforce_reset_nonce',
            '_wpnonce_reset'
        );
        $ips_reset_url = wp_nonce_url(
            admin_url('options-general.php?page=blockforce-wp&tab=reset&blockforce_reset_ips=1'),
            'blockforce_reset_ips_nonce',
            '_wpnonce_reset_ips'
        );
        $slug_reset_url = wp_nonce_url(
            admin_url('options-general.php?page=blockforce-wp&tab=reset&blockforce_reset_slug=1'),
            'blockforce_reset_slug_nonce',
            '_wpnonce_reset_slug'
        );
        ?>
        <div class="blockforce-card">
            <h2><?php esc_html_e('🔄 Reset & Maintenance Tools', $this->text_domain); ?></h2>
            <p><?php esc_html_e('Use these tools to manually reset various aspects of BlockForce WP. These actions cannot be undone.', $this->text_domain); ?></p>
            
            <?php settings_errors('blockforce_reset'); ?>
            
            <table class="form-table" style="margin-top: 20px;">
                <tr>
                    <th scope="row" style="width: 250px;">
                        <strong><?php esc_html_e('Clear IP Blocks & Logs', $this->text_domain); ?></strong>
                    </th>
                    <td>
                        <p class="description" style="margin-bottom: 10px;">
                            <?php esc_html_e('Immediately unblock all currently blocked IP addresses and clear the failed attempt history.', $this->text_domain); ?>
                        </p>
                        <p class="description" style="margin-bottom: 15px;">
                            <strong><?php esc_html_e('Use this when:', $this->text_domain); ?></strong>
                            <?php esc_html_e('You accidentally blocked yourself, or want to give attackers a fresh start.', $this->text_domain); ?>
                        </p>
                        <a href="<?php echo esc_url($ips_reset_url); ?>" 
                           onclick="return confirm('<?php echo esc_js(__('Are you sure you want to clear all IP blocks and attack logs?\n\nThis will unblock all currently blocked IPs.', $this->text_domain)); ?>')" 
                           class="button button-secondary">
                            <span class="dashicons dashicons-dismiss" style="margin-top: 3px;"></span>
                            <?php esc_html_e('Clear IP Blocks', $this->text_domain); ?>
                        </a>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <strong><?php esc_html_e('Reset Login URL', $this->text_domain); ?></strong>
                    </th>
                    <td>
                        <p class="description" style="margin-bottom: 10px;">
                            <?php esc_html_e('Restore your login page to the default wp-login.php location.', $this->text_domain); ?>
                        </p>
                        <p class="description" style="margin-bottom: 15px;">
                            <strong><?php esc_html_e('Use this when:', $this->text_domain); ?></strong>
                            <?php esc_html_e('You want to return to the standard WordPress login URL.', $this->text_domain); ?>
                        </p>
                        <a href="<?php echo esc_url($slug_reset_url); ?>" 
                           onclick="return confirm('<?php echo esc_js(__('Are you sure you want to reset the login URL?\n\nYour login page will return to /wp-login.php', $this->text_domain)); ?>')" 
                           class="button button-secondary">
                            <span class="dashicons dashicons-admin-home" style="margin-top: 3px;"></span>
                            <?php esc_html_e('Reset Login URL', $this->text_domain); ?>
                        </a>
                    </td>
                </tr>
                
                <tr style="border-top: 2px solid #f0f0f1;">
                    <th scope="row" style="padding-top: 20px;">
                        <strong style="color: #d63638;"><?php esc_html_e('Full Plugin Reset', $this->text_domain); ?></strong>
                    </th>
                    <td style="padding-top: 20px;">
                        <div class="blockforce-warning-box">
                            <p style="margin: 0;">
                                <strong><?php esc_html_e('⚠️ Warning:', $this->text_domain); ?></strong>
                                <?php esc_html_e('This will perform BOTH actions above: clear all IP blocks/logs AND reset the login URL to default.', $this->text_domain); ?>
                            </p>
                        </div>
                        <p class="description" style="margin-bottom: 15px;">
                            <strong><?php esc_html_e('Use this when:', $this->text_domain); ?></strong>
                            <?php esc_html_e('You want to completely reset BlockForce WP to its initial state (settings will be preserved).', $this->text_domain); ?>
                        </p>
                        <a href="<?php echo esc_url($full_reset_url); ?>" 
                           onclick="return confirm('<?php echo esc_js(__('⚠️ WARNING: Full Plugin Reset\n\nThis will:\n• Clear all IP blocks and logs\n• Reset login URL to wp-login.php\n\nYour settings will be preserved.\n\nAre you absolutely sure?', $this->text_domain)); ?>')" 
                           class="button button-secondary button-large" 
                           style="background-color: #d63638; color: #fff; border-color: #d63638; font-weight: bold;">
                            <span class="dashicons dashicons-warning" style="margin-top: 3px;"></span>
                            <?php esc_html_e('Full Reset', $this->text_domain); ?>
                        </a>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
}
