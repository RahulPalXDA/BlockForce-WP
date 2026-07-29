<?php
declare(strict_types=1);
if (!defined('ABSPATH'))
    exit;
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
        add_settings_section('blockforce_main_section', __('Security Configuration', $this->text_domain), array($this, 'section_callback'), 'blockforce_settings');
        add_settings_field('attempt_limit', __('Max Failed Attempts', $this->text_domain), array($this, 'render_attempt_limit'), 'blockforce_settings', 'blockforce_main_section');
        add_settings_field('block_time', __('IP Block Duration', $this->text_domain), array($this, 'render_block_time'), 'blockforce_settings', 'blockforce_main_section');
        add_settings_field('log_time', __('Attack Monitoring Window', $this->text_domain), array($this, 'render_log_time'), 'blockforce_settings', 'blockforce_main_section');
        add_settings_field('log_retention_days', __('Log Retention (Days)', $this->text_domain), array($this, 'render_log_retention_days'), 'blockforce_settings', 'blockforce_main_section');
        add_settings_field('enable_ip_blocking', __('Enable IP Blocking', $this->text_domain), array($this, 'render_enable_ip_blocking'), 'blockforce_settings', 'blockforce_main_section');
        add_settings_field('enable_url_change', __('Enable Auto URL Change', $this->text_domain), array($this, 'render_enable_url_change'), 'blockforce_settings', 'blockforce_main_section');
        add_settings_field('disable_debug_logs', __('Disable Debug Logs', $this->text_domain), array($this, 'render_disable_debug_logs'), 'blockforce_settings', 'blockforce_main_section');
        add_settings_field('alert_email', __('Security Alert Email', $this->text_domain), array($this, 'render_alert_email'), 'blockforce_settings', 'blockforce_main_section');
        add_settings_field('trusted_ip_header', __('Trusted Proxy Header', $this->text_domain), array($this, 'render_trusted_ip_header'), 'blockforce_settings', 'blockforce_main_section');
        add_settings_field('trusted_proxies', __('Trusted Proxy IPs', $this->text_domain), array($this, 'render_trusted_proxies'), 'blockforce_settings', 'blockforce_main_section');
        add_settings_field('disable_xmlrpc_multicall', __('Block XML-RPC Multicall', $this->text_domain), array($this, 'render_disable_xmlrpc_multicall'), 'blockforce_settings', 'blockforce_main_section');
        add_settings_field('disable_xmlrpc_entirely', __('Disable XML-RPC Entirely', $this->text_domain), array($this, 'render_disable_xmlrpc_entirely'), 'blockforce_settings', 'blockforce_main_section');
        add_settings_field('disable_user_enum', __('Block Username Enumeration', $this->text_domain), array($this, 'render_disable_user_enum'), 'blockforce_settings', 'blockforce_main_section');
    }
    public function sanitize_settings($input)
    {
        $out = array();
        $out['attempt_limit'] = max(1, (int) $input['attempt_limit']);
        $out['block_time'] = max(1, (int) $input['block_time']);
        $out['log_time'] = max(1, (int) $input['log_time']);
        $out['log_retention_days'] = max(1, (int) $input['log_retention_days']);
        $out['enable_url_change'] = isset($input['enable_url_change']) ? 1 : 0;
        $out['disable_debug_logs'] = isset($input['disable_debug_logs']) ? 1 : 0;
        $out['enable_ip_blocking'] = isset($input['enable_ip_blocking']) ? 1 : 0;
        $out['alert_email'] = isset($input['alert_email']) ? sanitize_email($input['alert_email']) : '';
        $header = isset($input['trusted_ip_header']) ? sanitize_text_field($input['trusted_ip_header']) : '';
        $out['trusted_ip_header'] = in_array($header, BlockForce_WP_Utils::TRUSTED_IP_HEADERS, true) ? $header : '';
        $proxies_raw = isset($input['trusted_proxies']) ? (string) $input['trusted_proxies'] : '';
        $valid_entries = array();
        foreach (preg_split('/[\r\n,]+/', $proxies_raw, -1, PREG_SPLIT_NO_EMPTY) as $entry) {
            $entry = trim($entry);
            if ($entry !== '' && BlockForce_WP_Utils::is_valid_ip_or_cidr($entry))
                $valid_entries[] = $entry;
        }
        $out['trusted_proxies'] = implode("\n", $valid_entries);
        $out['disable_xmlrpc_multicall'] = isset($input['disable_xmlrpc_multicall']) ? 1 : 0;
        $out['disable_xmlrpc_entirely'] = isset($input['disable_xmlrpc_entirely']) ? 1 : 0;
        $out['disable_user_enum'] = isset($input['disable_user_enum']) ? 1 : 0;
        add_settings_error('blockforce_settings', 'settings_updated', __('Settings saved!', $this->text_domain), 'updated');
        return $out;
    }
    public function section_callback()
    {
        echo '<p>' . esc_html__('Configure how BlockForce WP protects your site.', $this->text_domain) . '</p>';
    }
    public function render_attempt_limit()
    {
        echo '<input type="number" name="blockforce_settings[attempt_limit]" value="' . esc_attr($this->settings['attempt_limit']) . '" min="1" max="100" class="blockforce-input-narrow"><p class="description">' . esc_html__('Failed attempts before protection. Default: 5', $this->text_domain) . '</p>';
    }
    public function render_block_time()
    {
        $min = round($this->settings['block_time'] / 60, 1);
        echo '<input type="number" name="blockforce_settings[block_time]" value="' . esc_attr($this->settings['block_time']) . '" min="1" class="blockforce-input-medium"> <span class="description">' . esc_html__('seconds', $this->text_domain) . '</span> <span class="blockforce-time-hint">(' . esc_html(sprintf(__('≈ %s minutes', $this->text_domain), $min)) . ')</span><p class="description">' . esc_html__('Block duration. Default: 86400s (24 hours)', $this->text_domain) . '</p>';
    }
    public function render_enable_ip_blocking()
    {
        $en = $this->settings['enable_ip_blocking'];
        echo '<label><input type="checkbox" name="blockforce_settings[enable_ip_blocking]" value="1" ' . checked(1, $en, false) . '> ' . esc_html__('Block IPs after failed attempts', $this->text_domain) . ' <span class="blockforce-badge ' . ($en ? 'blockforce-badge-enabled' : 'blockforce-badge-disabled') . '">' . ($en ? esc_html__('ENABLED', $this->text_domain) : esc_html__('DISABLED', $this->text_domain)) . '</span></label>';
    }
    public function render_log_time()
    {
        $h = round($this->settings['log_time'] / 3600, 1);
        echo '<input type="number" name="blockforce_settings[log_time]" value="' . esc_attr($this->settings['log_time']) . '" min="1" class="blockforce-input-medium"> <span class="description">' . esc_html__('seconds', $this->text_domain) . '</span> <span class="blockforce-time-hint">(' . esc_html(sprintf(__('≈ %s hours', $this->text_domain), $h)) . ')</span><p class="description">' . esc_html__('Monitoring window. Default: 1800s (30 minutes)', $this->text_domain) . '</p>';
    }
    public function render_log_retention_days()
    {
        echo '<input type="number" name="blockforce_settings[log_retention_days]" value="' . esc_attr($this->settings['log_retention_days'] ?? 90) . '" min="1" max="365" class="blockforce-input-narrow"><p class="description">' . esc_html__('Security log retention in days. Default: 90', $this->text_domain) . '</p>';
    }
    public function render_enable_url_change()
    {
        $en = $this->settings['enable_url_change'];
        echo '<label><input type="checkbox" name="blockforce_settings[enable_url_change]" value="1" ' . checked(1, $en, false) . '> ' . esc_html__('Auto-change login URL', $this->text_domain) . ' <span class="blockforce-badge ' . ($en ? 'blockforce-badge-enabled' : 'blockforce-badge-disabled') . '">' . ($en ? esc_html__('ENABLED', $this->text_domain) : esc_html__('DISABLED', $this->text_domain)) . '</span></label>';
    }
    public function render_disable_debug_logs()
    {
        $en = $this->settings['disable_debug_logs'] ?? 1;
        echo '<label><input type="checkbox" name="blockforce_settings[disable_debug_logs]" value="1" ' . checked(1, $en, false) . '> ' . esc_html__('Hide PHP errors from page output', $this->text_domain) . ' <span class="blockforce-badge ' . ($en ? 'blockforce-badge-enabled' : 'blockforce-badge-disabled') . '">' . ($en ? esc_html__('ACTIVE', $this->text_domain) : esc_html__('INACTIVE', $this->text_domain)) . '</span></label><p class="description">' . esc_html__('Sets display_errors off so visitors never see raw PHP errors. Server-side error logging is left untouched so you can still investigate issues.', $this->text_domain) . '</p>';
    }
    public function render_trusted_ip_header()
    {
        $val = $this->settings['trusted_ip_header'] ?? '';
        echo '<select name="blockforce_settings[trusted_ip_header]">';
        echo '<option value="">' . esc_html__('Disabled (trust REMOTE_ADDR only)', $this->text_domain) . '</option>';
        foreach (BlockForce_WP_Utils::TRUSTED_IP_HEADERS as $header) {
            echo '<option value="' . esc_attr($header) . '" ' . selected($val, $header, false) . '>' . esc_html($header) . '</option>';
        }
        echo '</select><p class="description">' . esc_html__('Only set this if your site sits behind the proxy/CDN listed below. Enabling it without a trusted proxy list lets visitors spoof their IP and bypass blocking entirely.', $this->text_domain) . '</p>';
    }
    public function render_trusted_proxies()
    {
        $val = $this->settings['trusted_proxies'] ?? '';
        echo '<textarea name="blockforce_settings[trusted_proxies]" rows="4" class="large-text code" placeholder="203.0.113.0/24">' . esc_textarea($val) . '</textarea><p class="description">' . esc_html__('One IP address or CIDR range per line. The header above is only trusted when the request comes from one of these addresses.', $this->text_domain) . '</p>';
    }
    public function render_disable_xmlrpc_multicall()
    {
        $en = $this->settings['disable_xmlrpc_multicall'] ?? 1;
        echo '<label><input type="checkbox" name="blockforce_settings[disable_xmlrpc_multicall]" value="1" ' . checked(1, $en, false) . '> ' . esc_html__('Remove the system.multicall XML-RPC method', $this->text_domain) . '</label><p class="description">' . esc_html__('Prevents testing hundreds of password guesses in a single XML-RPC request, bypassing the attempt limit above.', $this->text_domain) . '</p>';
    }
    public function render_disable_xmlrpc_entirely()
    {
        $en = $this->settings['disable_xmlrpc_entirely'] ?? 0;
        echo '<label><input type="checkbox" name="blockforce_settings[disable_xmlrpc_entirely]" value="1" ' . checked(1, $en, false) . '> ' . esc_html__('Disable XML-RPC completely', $this->text_domain) . '</label><p class="description">' . esc_html__('Only enable this if you don\'t use Jetpack, the WordPress mobile app, or other tools that rely on XML-RPC.', $this->text_domain) . '</p>';
    }
    public function render_disable_user_enum()
    {
        $en = $this->settings['disable_user_enum'] ?? 1;
        echo '<label><input type="checkbox" name="blockforce_settings[disable_user_enum]" value="1" ' . checked(1, $en, false) . '> ' . esc_html__('Block anonymous username discovery', $this->text_domain) . '</label><p class="description">' . esc_html__('Hides usernames from the REST API user list and author archive links for logged-out visitors, so the generic login error above can\'t be worked around.', $this->text_domain) . '</p>';
    }
    public function render_alert_email()
    {
        $email = $this->settings['alert_email'] ?? '';
        $def = get_option('admin_email');
        $url = wp_nonce_url(admin_url('admin.php?page=blockforce-wp-settings&blockforce_test_email=1'), 'blockforce_test_email_nonce', '_wpnonce_test_email');
        echo '<input type="email" name="blockforce_settings[alert_email]" value="' . esc_attr($email) . '" class="regular-text" placeholder="' . esc_attr($def) . '"><p class="description">' . esc_html__('Receive security alerts here. Default:', $this->text_domain) . ' ' . esc_html($def) . '</p><div class="blockforce-warning-box blockforce-mt-15"><p class="blockforce-mb-0"><strong>' . esc_html__('Warning:', $this->text_domain) . '</strong> ' . esc_html__('Test email delivery below.', $this->text_domain) . '</p></div><p class="blockforce-mt-15"><a href="' . esc_url($url) . '" class="button button-secondary"><span class="dashicons dashicons-email blockforce-button-icon"></span> ' . esc_html__('Test Email Delivery', $this->text_domain) . '</a></p>';
    }
}
