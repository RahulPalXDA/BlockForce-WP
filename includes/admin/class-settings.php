<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class BlockForce_WP_Admin_Settings
{
    private $settings;
    private $text_domain = BFWP_TEXT_DOMAIN;

    public function __construct($settings)
    {
        $this->settings = $settings;
    }

    public function register()
    {
        register_setting('blockforce_settings', 'blockforce_settings', array($this, 'sanitize_settings'));

        add_settings_section(
            'blockforce_main_section',
            __('Security Configuration', $this->text_domain),
            array($this, 'section_callback'),
            'blockforce_settings'
        );

        add_settings_field('attempt_limit', __('Maximum Failed Attempts', $this->text_domain), array($this, 'render_attempt_limit'), 'blockforce_settings', 'blockforce_main_section');
        add_settings_field('block_time', __('IP Block Duration', $this->text_domain), array($this, 'render_block_time'), 'blockforce_settings', 'blockforce_main_section');
        add_settings_field('log_time', __('Attack Monitoring Window', $this->text_domain), array($this, 'render_log_time'), 'blockforce_settings', 'blockforce_main_section');
        add_settings_field('log_retention_days', __('Log Retention (Days)', $this->text_domain), array($this, 'render_log_retention_days'), 'blockforce_settings', 'blockforce_main_section');
        add_settings_field('enable_ip_blocking', __('Enable IP Blocking', $this->text_domain), array($this, 'render_enable_ip_blocking'), 'blockforce_settings', 'blockforce_main_section');
        add_settings_field('enable_url_change', __('Enable Auto URL Change', $this->text_domain), array($this, 'render_enable_url_change'), 'blockforce_settings', 'blockforce_main_section');
        add_settings_field('disable_debug_logs', __('Strictly Disable Debug Logs', $this->text_domain), array($this, 'render_disable_debug_logs'), 'blockforce_settings', 'blockforce_main_section');
        add_settings_field('alert_email', __('Security Alert Email', $this->text_domain), array($this, 'render_alert_email'), 'blockforce_settings', 'blockforce_main_section');
    }

    public function sanitize_settings($input)
    {
        $output = array();
        $output['attempt_limit'] = max(1, (int) $input['attempt_limit']);
        $output['block_time'] = max(1, (int) $input['block_time']);
        $output['log_time'] = max(1, (int) $input['log_time']);
        $output['log_retention_days'] = max(1, (int) $input['log_retention_days']);
        $output['enable_url_change'] = isset($input['enable_url_change']) ? 1 : 0;
        $output['disable_debug_logs'] = isset($input['disable_debug_logs']) ? 1 : 0;
        $output['enable_ip_blocking'] = isset($input['enable_ip_blocking']) ? 1 : 0;
        $output['alert_email'] = isset($input['alert_email']) ? sanitize_email($input['alert_email']) : '';

        add_settings_error('blockforce_settings', 'settings_updated', __('Settings saved successfully!', $this->text_domain), 'updated');
        return $output;
    }

    public function section_callback()
    {
        ?>
        <p><?php esc_html_e('Configure how BlockForce WP protects your WordPress login page from brute-force attacks.', $this->text_domain); ?>
        </p>
        <?php
    }

    public function render_attempt_limit()
    {
        ?>
        <input type="number" name="blockforce_settings[attempt_limit]"
            value="<?php echo esc_attr($this->settings['attempt_limit']); ?>" min="1" max="100" class="blockforce-input-narrow">
        <p class="description">
            <?php esc_html_e('Failed attempts before triggering protection. Default: 2', $this->text_domain); ?>
        </p>
        <?php
    }

    public function render_block_time()
    {
        $minutes = round($this->settings['block_time'] / 60, 1);
        ?>
        <input type="number" name="blockforce_settings[block_time]"
            value="<?php echo esc_attr($this->settings['block_time']); ?>" min="1" class="blockforce-input-medium">
        <span class="description"><?php esc_html_e('seconds', $this->text_domain); ?></span>
        <span
            class="blockforce-time-hint">(<?php echo esc_html(sprintf(__('≈ %s minutes', $this->text_domain), $minutes)); ?>)</span>
        <p class="description">
            <?php esc_html_e('Block duration for malicious IPs. Default: 120 seconds', $this->text_domain); ?>
        </p>
        <?php
    }

    public function render_enable_ip_blocking()
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

    public function render_log_time()
    {
        $hours = round($this->settings['log_time'] / 3600, 1);
        ?>
        <input type="number" name="blockforce_settings[log_time]" value="<?php echo esc_attr($this->settings['log_time']); ?>"
            min="1" class="blockforce-input-medium">
        <span class="description"><?php esc_html_e('seconds', $this->text_domain); ?></span>
        <span
            class="blockforce-time-hint">(<?php echo esc_html(sprintf(__('≈ %s hours', $this->text_domain), $hours)); ?>)</span>
        <p class="description">
            <?php esc_html_e('Monitoring window for persistent attacks. Default: 7200 seconds', $this->text_domain); ?>
        </p>
        <?php
    }

    public function render_log_retention_days()
    {
        ?>
                <input type="number" name="blockforce_settings[log_retention_days]"
                    value="<?php echo esc_attr($this->settings['log_retention_days'] ?? 30); ?>" min="1" max="365"
                    class="blockforce-input-narrow">
                <p class="description">
                    <?php esc_html_e('Number of days to keep security logs. Highly active sites should keep this low. Default: 30', $this->text_domain); ?>
                </p>
                <?php
    }

    public function render_enable_url_change()
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

    public function render_disable_debug_logs()
    {
        // Default to enabled if not set, or check current setting
        $enabled = isset($this->settings['disable_debug_logs']) ? $this->settings['disable_debug_logs'] : 1;
        ?>
        <label>
            <input type="checkbox" name="blockforce_settings[disable_debug_logs]" value="1" <?php checked(1, $enabled); ?>>
            <?php esc_html_e('Strictly disable all debug logs and PHP errors', $this->text_domain); ?>
            <span class="blockforce-badge <?php echo $enabled ? 'blockforce-badge-enabled' : 'blockforce-badge-disabled'; ?>">
                <?php echo $enabled ? esc_html__('ACTIVE', $this->text_domain) : esc_html__('INACTIVE', $this->text_domain); ?>
            </span>
        </label>
        <p class="description">
            <?php esc_html_e('Forces error_reporting(0) to prevent any leakage of sensitive information via logs.', $this->text_domain); ?>
        </p>
        <?php
    }

    public function render_alert_email()
    {
        $email = isset($this->settings['alert_email']) ? $this->settings['alert_email'] : '';
        $default_email = get_option('admin_email');
        $test_email_url = wp_nonce_url(admin_url('admin.php?page=blockforce-wp-settings&blockforce_test_email=1'), 'blockforce_test_email_nonce', '_wpnonce_test_email');
        ?>
        <input type="email" name="blockforce_settings[alert_email]" value="<?php echo esc_attr($email); ?>" class="regular-text"
            placeholder="<?php echo esc_attr($default_email); ?>">
        <p class="description"><?php esc_html_e('Receive security alerts here. Default:', $this->text_domain); ?>
            <?php echo esc_html($default_email); ?>
        </p>

        <div class="blockforce-warning-box blockforce-mt-15">
            <p class="blockforce-mb-0"><strong><?php esc_html_e('Warning:', $this->text_domain); ?></strong>
                <?php esc_html_e('Test email delivery below. URL changes only if email succeeds.', $this->text_domain); ?></p>
        </div>

        <p class="blockforce-mt-15">
            <a href="<?php echo esc_url($test_email_url); ?>" class="button button-secondary">
                <span class="dashicons dashicons-email blockforce-button-icon"></span>
                <?php esc_html_e('Test Email Delivery', $this->text_domain); ?>
            </a>
        </p>
        <?php
    }
}
