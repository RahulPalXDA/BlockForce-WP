<?php
/**
 * Settings Field Renderers
 * 
 * Handles the WordPress Settings API registration and field rendering.
 *
 * @package BlockForce_WP
 * @subpackage Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

class BlockForce_WP_Admin_Settings
{
    private $settings;
    private $text_domain = 'blockforce-wp';

    public function __construct($settings)
    {
        $this->settings = $settings;
    }

    /**
     * Register settings with WordPress Settings API
     */
    public function register()
    {
        register_setting(
            'blockforce_settings',
            'blockforce_settings',
            array($this, 'sanitize_settings')
        );

        add_settings_section(
            'blockforce_section',
            '',
            array($this, 'section_callback'),
            'blockforce_settings'
        );

        // Attempt Limit
        add_settings_field(
            'attempt_limit',
            __('Failed Login Attempt Limit', $this->text_domain),
            array($this, 'render_attempt_limit'),
            'blockforce_settings',
            'blockforce_section'
        );

        // Block Time
        add_settings_field(
            'block_time',
            __('Block Duration', $this->text_domain),
            array($this, 'render_block_time'),
            'blockforce_settings',
            'blockforce_section'
        );

        // Enable IP Blocking
        add_settings_field(
            'enable_ip_blocking',
            __('Enable IP Blocking', $this->text_domain),
            array($this, 'render_enable_ip_blocking'),
            'blockforce_settings',
            'blockforce_section'
        );

        // Log Time
        add_settings_field(
            'log_time',
            __('Login Attempt Window', $this->text_domain),
            array($this, 'render_log_time'),
            'blockforce_settings',
            'blockforce_section'
        );

        // Enable Global Blocklist
        add_settings_field(
            'enable_global_blocklist',
            __('Enable Global Blocklist', $this->text_domain),
            array($this, 'render_enable_global_blocklist'),
            'blockforce_settings',
            'blockforce_section'
        );

        // Enable URL Change
        add_settings_field(
            'enable_url_change',
            __('Enable Auto Login URL Change', $this->text_domain),
            array($this, 'render_enable_url_change'),
            'blockforce_settings',
            'blockforce_section'
        );

        // Alert Email
        add_settings_field(
            'alert_email',
            __('Alert Email Address', $this->text_domain),
            array($this, 'render_alert_email'),
            'blockforce_settings',
            'blockforce_section'
        );
    }

    /**
     * Sanitize settings input
     */
    public function sanitize_settings($input)
    {
        $output = array();
        $output['attempt_limit'] = isset($input['attempt_limit']) ? absint($input['attempt_limit']) : 2;
        $output['block_time'] = isset($input['block_time']) ? absint($input['block_time']) : 120;
        $output['log_time'] = isset($input['log_time']) ? absint($input['log_time']) : 7200;
        $output['enable_ip_blocking'] = isset($input['enable_ip_blocking']) ? 1 : 0;
        $output['enable_global_blocklist'] = isset($input['enable_global_blocklist']) ? 1 : 0;
        $output['enable_url_change'] = isset($input['enable_url_change']) ? 1 : 0;
        $output['alert_email'] = isset($input['alert_email']) ? sanitize_email($input['alert_email']) : '';
        return $output;
    }

    /**
     * Settings section callback
     */
    public function section_callback()
    {
        ?>
        <p class="description">
            <?php esc_html_e('Configure how BlockForce WP protects your site from brute-force attacks.', $this->text_domain); ?>
        </p>
        <?php
    }

    // --- Field Renderers ---

    public function render_attempt_limit()
    {
        $value = isset($this->settings['attempt_limit']) ? $this->settings['attempt_limit'] : 2;
        ?>
        <input type="number" name="blockforce_settings[attempt_limit]" value="<?php echo esc_attr($value); ?>" min="1" max="100"
            class="small-text">
        <p class="description">
            <?php esc_html_e('Number of failed login attempts before action is taken.', $this->text_domain); ?>
        </p>
        <?php
    }

    public function render_block_time()
    {
        $value = isset($this->settings['block_time']) ? $this->settings['block_time'] : 120;
        ?>
        <select name="blockforce_settings[block_time]">
            <option value="60" <?php selected($value, 60); ?>><?php esc_html_e('1 minute', $this->text_domain); ?></option>
            <option value="300" <?php selected($value, 300); ?>><?php esc_html_e('5 minutes', $this->text_domain); ?></option>
            <option value="900" <?php selected($value, 900); ?>><?php esc_html_e('15 minutes', $this->text_domain); ?></option>
            <option value="1800" <?php selected($value, 1800); ?>><?php esc_html_e('30 minutes', $this->text_domain); ?>
            </option>
            <option value="3600" <?php selected($value, 3600); ?>><?php esc_html_e('1 hour', $this->text_domain); ?></option>
            <option value="86400" <?php selected($value, 86400); ?>><?php esc_html_e('24 hours', $this->text_domain); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e('How long an IP will be blocked after exceeding the attempt limit.', $this->text_domain); ?>
        </p>
        <?php
    }

    public function render_enable_ip_blocking()
    {
        $value = isset($this->settings['enable_ip_blocking']) ? $this->settings['enable_ip_blocking'] : 1;
        ?>
        <label>
            <input type="checkbox" name="blockforce_settings[enable_ip_blocking]" value="1" <?php checked($value, 1); ?>>
            <?php esc_html_e('Temporarily block IP addresses after too many failed login attempts', $this->text_domain); ?>
        </label>
        <?php
    }

    public function render_log_time()
    {
        $value = isset($this->settings['log_time']) ? $this->settings['log_time'] : 7200;
        ?>
        <select name="blockforce_settings[log_time]">
            <option value="3600" <?php selected($value, 3600); ?>><?php esc_html_e('1 hour', $this->text_domain); ?></option>
            <option value="7200" <?php selected($value, 7200); ?>><?php esc_html_e('2 hours', $this->text_domain); ?></option>
            <option value="86400" <?php selected($value, 86400); ?>><?php esc_html_e('24 hours', $this->text_domain); ?>
            </option>
            <option value="604800" <?php selected($value, 604800); ?>><?php esc_html_e('7 days', $this->text_domain); ?>
            </option>
            <option value="2592000" <?php selected($value, 2592000); ?>><?php esc_html_e('30 days', $this->text_domain); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e('Time window for counting failed login attempts. Older attempts are ignored.', $this->text_domain); ?>
        </p>
        <?php
    }

    public function render_enable_global_blocklist()
    {
        $value = isset($this->settings['enable_global_blocklist']) ? $this->settings['enable_global_blocklist'] : 0;
        ?>
        <label>
            <input type="checkbox" name="blockforce_settings[enable_global_blocklist]" value="1" <?php checked($value, 1); ?>>
            <?php esc_html_e('Block known malicious IPs using the FireHol Level 1 blocklist', $this->text_domain); ?>
        </label>
        <p class="description">
            <?php esc_html_e('The blocklist is updated daily and includes approximately 5000+ known bad IPs/ranges.', $this->text_domain); ?>
        </p>
        <?php
    }

    public function render_enable_url_change()
    {
        $value = isset($this->settings['enable_url_change']) ? $this->settings['enable_url_change'] : 1;
        ?>
        <label>
            <input type="checkbox" name="blockforce_settings[enable_url_change]" value="1" <?php checked($value, 1); ?>>
            <?php esc_html_e('Automatically change login URL after brute-force attack detection', $this->text_domain); ?>
        </label>
        <?php
    }

    public function render_alert_email()
    {
        $value = isset($this->settings['alert_email']) ? $this->settings['alert_email'] : '';
        $admin_email = get_option('admin_email');
        ?>
        <input type="email" name="blockforce_settings[alert_email]" value="<?php echo esc_attr($value); ?>" class="regular-text"
            placeholder="<?php echo esc_attr($admin_email); ?>">
        <p class="description">
            <?php esc_html_e('Email address for security alerts. Leave empty to use admin email.', $this->text_domain); ?>
        </p>

        <?php
        $test_email_url = wp_nonce_url(
            admin_url('options-general.php?page=blockforce-wp&tab=settings&blockforce_test_email=1'),
            'blockforce_test_email_nonce',
            '_wpnonce_test_email'
        );
        ?>
        <p style="margin-top: 10px;">
            <a href="<?php echo esc_url($test_email_url); ?>" class="button button-secondary">
                <?php esc_html_e('Send Test Email', $this->text_domain); ?>
            </a>
        </p>
        <?php
        settings_errors('blockforce_test_email');
    }
}
