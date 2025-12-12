<?php
if (!defined('ABSPATH')) {
    exit;
}

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
        $tests['direct']['blockforce_email_delivery'] = array(
            'label' => __('BlockForce WP Email Delivery', $this->text_domain),
            'test' => array($this, 'test_email_delivery'),
        );
        $tests['direct']['blockforce_rewrite_rules'] = array(
            'label' => __('BlockForce WP Rewrite Rules', $this->text_domain),
            'test' => array($this, 'test_rewrite_rules'),
        );
        $tests['direct']['blockforce_configuration'] = array(
            'label' => __('BlockForce WP Configuration', $this->text_domain),
            'test' => array($this, 'test_configuration'),
        );
        $tests['direct']['blockforce_security_status'] = array(
            'label' => __('BlockForce WP Security Status', $this->text_domain),
            'test' => array($this, 'test_security_status'),
        );
        return $tests;
    }

    public function test_email_delivery()
    {
        $result = array(
            'label' => __('BlockForce WP can send security alerts', $this->text_domain),
            'status' => 'good',
            'badge' => array('label' => __('Security', $this->text_domain), 'color' => 'blue'),
            'description' => sprintf('<p>%s</p>', __('Email alerts are essential for receiving notifications when your login URL changes.', $this->text_domain)),
            'actions' => '',
            'test' => 'blockforce_email_delivery',
        );

        $alert_email = isset($this->settings['alert_email']) && !empty($this->settings['alert_email'])
            ? $this->settings['alert_email']
            : get_option('admin_email');

        if (empty($alert_email) || !is_email($alert_email)) {
            $result['status'] = 'critical';
            $result['label'] = __('BlockForce WP email is not configured', $this->text_domain);
            $result['description'] = sprintf('<p>%s</p>', __('No valid email address is configured for security alerts.', $this->text_domain));
            $result['actions'] = sprintf('<p><a href="%s">%s</a></p>', admin_url('admin.php?page=blockforce-wp-settings'), __('Configure Email Settings', $this->text_domain));
        } else {
            $result['description'] .= sprintf('<p>%s <code>%s</code></p>', __('Security alerts will be sent to:', $this->text_domain), esc_html($alert_email));
        }

        return $result;
    }

    public function test_rewrite_rules()
    {
        $result = array(
            'label' => __('BlockForce WP rewrite rules are working', $this->text_domain),
            'status' => 'good',
            'badge' => array('label' => __('Security', $this->text_domain), 'color' => 'blue'),
            'description' => sprintf('<p>%s</p>', __('Rewrite rules allow BlockForce WP to change your login URL dynamically.', $this->text_domain)),
            'actions' => '',
            'test' => 'blockforce_rewrite_rules',
        );

        $current_slug = $this->core->login_url->get_login_slug();

        if ($current_slug) {
            $rules = get_option('rewrite_rules');
            $expected_rule = '^' . preg_quote($current_slug, '/') . '/?$';
            $rule_exists = false;

            if (is_array($rules)) {
                foreach ($rules as $pattern => $rewrite) {
                    if ($pattern === $expected_rule) {
                        $rule_exists = true;
                        break;
                    }
                }
            }

            if (!$rule_exists) {
                $result['status'] = 'recommended';
                $result['label'] = __('BlockForce WP rewrite rules may need refresh', $this->text_domain);
                $result['description'] = sprintf('<p>%s</p>', __('The custom login URL rewrite rule was not found.', $this->text_domain));
                $result['actions'] = sprintf('<p><a href="%s" class="button button-primary">%s</a></p>', admin_url('admin.php?page=blockforce-wp-reset'), __('Reset Login URL', $this->text_domain));
            } else {
                $result['description'] .= sprintf('<p>%s <code>%s</code></p>', __('Custom login URL is active:', $this->text_domain), esc_html($current_slug));
            }
        }

        return $result;
    }

    public function test_configuration()
    {
        $result = array(
            'label' => __('BlockForce WP is properly configured', $this->text_domain),
            'status' => 'good',
            'badge' => array('label' => __('Security', $this->text_domain), 'color' => 'blue'),
            'description' => sprintf('<p>%s</p>', __('BlockForce WP configuration has been validated.', $this->text_domain)),
            'actions' => '',
            'test' => 'blockforce_configuration',
        );

        $issues = array();

        $attempt_limit = isset($this->settings['attempt_limit']) ? intval($this->settings['attempt_limit']) : 0;
        if ($attempt_limit < 1 || $attempt_limit > 100) {
            $issues[] = __('Attempt limit is out of valid range (1-100)', $this->text_domain);
        }

        $block_time = isset($this->settings['block_time']) ? intval($this->settings['block_time']) : 0;
        if ($block_time < 1) {
            $issues[] = __('Block time must be at least 1 second', $this->text_domain);
        }

        $log_time = isset($this->settings['log_time']) ? intval($this->settings['log_time']) : 0;
        if ($log_time < 1) {
            $issues[] = __('Log time must be at least 1 second', $this->text_domain);
        }

        $ip_blocking = isset($this->settings['enable_ip_blocking']) ? $this->settings['enable_ip_blocking'] : 0;
        $url_change = isset($this->settings['enable_url_change']) ? $this->settings['enable_url_change'] : 0;

        if (!$ip_blocking && !$url_change) {
            $issues[] = __('No protection methods are enabled.', $this->text_domain);
        }

        if (!empty($issues)) {
            $result['status'] = 'recommended';
            $result['label'] = __('BlockForce WP configuration needs attention', $this->text_domain);
            $result['description'] = '<p>' . __('Configuration issues found:', $this->text_domain) . '</p><ul>';
            foreach ($issues as $issue) {
                $result['description'] .= '<li>' . esc_html($issue) . '</li>';
            }
            $result['description'] .= '</ul>';
            $result['actions'] = sprintf('<p><a href="%s">%s</a></p>', admin_url('admin.php?page=blockforce-wp-settings'), __('Review Settings', $this->text_domain));
        }

        return $result;
    }

    public function test_security_status()
    {
        $result = array(
            'label' => __('BlockForce WP is protecting your site', $this->text_domain),
            'status' => 'good',
            'badge' => array('label' => __('Security', $this->text_domain), 'color' => 'blue'),
            'description' => sprintf('<p>%s</p>', __('BlockForce WP is actively monitoring and protecting your WordPress login.', $this->text_domain)),
            'actions' => '',
            'test' => 'blockforce_security_status',
        );

        $ip_blocking = isset($this->settings['enable_ip_blocking']) ? $this->settings['enable_ip_blocking'] : 0;
        $url_change = isset($this->settings['enable_url_change']) ? $this->settings['enable_url_change'] : 0;

        $features = array();
        if ($ip_blocking) {
            $features[] = __('IP Blocking', $this->text_domain);
        }
        if ($url_change) {
            $features[] = __('Auto URL Change', $this->text_domain);
        }

        if (!empty($features)) {
            $result['description'] .= sprintf('<p><strong>%s</strong> %s</p>', __('Active Features:', $this->text_domain), implode(', ', $features));
        } else {
            $result['status'] = 'critical';
            $result['label'] = __('BlockForce WP protection is disabled', $this->text_domain);
            $result['description'] = sprintf('<p>%s</p>', __('All protection features are currently disabled.', $this->text_domain));
            $result['actions'] = sprintf('<p><a href="%s" class="button button-primary">%s</a></p>', admin_url('admin.php?page=blockforce-wp-settings'), __('Enable Protection', $this->text_domain));
        }

        return $result;
    }

    public function add_debug_information($info)
    {
        $current_slug = $this->core->login_url->get_login_slug();

        $info['blockforce-wp'] = array(
            'label' => __('BlockForce WP', $this->text_domain),
            'fields' => array(
                'version' => array('label' => __('Plugin Version', $this->text_domain), 'value' => BFWP_VERSION),
                'login_url_status' => array('label' => __('Login URL Status', $this->text_domain), 'value' => $current_slug ? __('Custom URL Active', $this->text_domain) : __('Default URL', $this->text_domain)),
                'current_slug' => array('label' => __('Current Login Slug', $this->text_domain), 'value' => $current_slug ? $current_slug : __('None', $this->text_domain), 'private' => true),
                'ip_blocking' => array('label' => __('IP Blocking', $this->text_domain), 'value' => isset($this->settings['enable_ip_blocking']) && $this->settings['enable_ip_blocking'] ? __('Enabled', $this->text_domain) : __('Disabled', $this->text_domain)),
                'url_change' => array('label' => __('Auto URL Change', $this->text_domain), 'value' => isset($this->settings['enable_url_change']) && $this->settings['enable_url_change'] ? __('Enabled', $this->text_domain) : __('Disabled', $this->text_domain)),
                'attempt_limit' => array('label' => __('Attempt Limit', $this->text_domain), 'value' => isset($this->settings['attempt_limit']) ? $this->settings['attempt_limit'] : 'N/A'),
                'block_time' => array('label' => __('Block Time (seconds)', $this->text_domain), 'value' => isset($this->settings['block_time']) ? $this->settings['block_time'] : 'N/A'),
                'log_time' => array('label' => __('Log Time (seconds)', $this->text_domain), 'value' => isset($this->settings['log_time']) ? $this->settings['log_time'] : 'N/A'),
                'alert_email' => array('label' => __('Alert Email', $this->text_domain), 'value' => isset($this->settings['alert_email']) && !empty($this->settings['alert_email']) ? $this->settings['alert_email'] : get_option('admin_email'), 'private' => true),
            ),
        );

        return $info;
    }
}
