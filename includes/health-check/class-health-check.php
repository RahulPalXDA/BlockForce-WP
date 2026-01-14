<?php
declare(strict_types=1);
if (!defined('ABSPATH'))
    exit;
class BlockForce_WP_Health_Check
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
        add_filter('site_status_tests', array($this, 'add_health_check_tests'));
        add_filter('debug_information', array($this, 'add_debug_information'));
    }
    public function add_health_check_tests($tests)
    {
        $tests['direct']['blockforce_email'] = array('label' => __('BlockForce Email', $this->text_domain), 'test' => array($this, 'test_email'));
        $tests['direct']['blockforce_rewrite'] = array('label' => __('BlockForce Rewrite', $this->text_domain), 'test' => array($this, 'test_rewrite'));
        $tests['direct']['blockforce_config'] = array('label' => __('BlockForce Config', $this->text_domain), 'test' => array($this, 'test_config'));
        return $tests;
    }
    public function test_email()
    {
        $to = !empty($this->settings['alert_email']) ? $this->settings['alert_email'] : get_option('admin_email');
        $status = (empty($to) || !is_email($to)) ? 'critical' : 'good';
        return array('label' => __('Email Delivery', $this->text_domain), 'status' => $status, 'badge' => array('label' => __('Security', $this->text_domain), 'color' => ($status === 'good' ? 'blue' : 'red')), 'description' => $status === 'good' ? sprintf(__('Alerts sent to %s', $this->text_domain), $to) : __('No valid email address configured.', $this->text_domain));
    }
    public function test_rewrite()
    {
        $slug = $this->core->login_url->get_login_slug();
        if (!$slug)
            return array('label' => __('Rewrite Rules', $this->text_domain), 'status' => 'good', 'description' => __('Default URL active, no rewrites needed.', $this->text_domain));
        $rules = get_option('rewrite_rules');
        $exists = isset($rules['^' . preg_quote($slug, '/') . '/?$']);
        return array('label' => __('Rewrite Rules', $this->text_domain), 'status' => $exists ? 'good' : 'recommended', 'description' => $exists ? __('Login URL rewrite rules are active.', $this->text_domain) : __('Rewrite rules missing for custom URL.', $this->text_domain));
    }
    public function test_config()
    {
        $issues = array();
        if (($this->settings['attempt_limit'] ?? 0) < 1)
            $issues[] = __('Invalid attempt limit.', $this->text_domain);
        if (!($this->settings['enable_ip_blocking'] ?? 0) && !($this->settings['enable_url_change'] ?? 0))
            $issues[] = __('No protection active.', $this->text_domain);
        $status = empty($issues) ? 'good' : 'recommended';
        return array('label' => __('Configuration', $this->text_domain), 'status' => $status, 'description' => empty($issues) ? __('Config is valid.', $this->text_domain) : implode(' ', $issues));
    }
    public function add_debug_information($info)
    {
        $slug = $this->core->login_url->get_login_slug();
        $info['blockforce-wp'] = array(
            'label' => __('BlockForce WP', $this->text_domain),
            'fields' => array(
                'version' => array('label' => __('Version', $this->text_domain), 'value' => BFWP_VERSION),
                'status' => array('label' => __('Login Status', $this->text_domain), 'value' => $slug ? __('Custom URL', $this->text_domain) : __('Default', $this->text_domain)),
                'ip_blocking' => array('label' => __('IP Blocking', $this->text_domain), 'value' => ($this->settings['enable_ip_blocking'] ?? 0) ? 'ON' : 'OFF'),
            )
        );
        return $info;
    }
}
