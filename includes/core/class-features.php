<?php
if (!defined('ABSPATH')) {
    exit;
}

class BlockForce_WP_Features
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
        add_filter('login_errors', array($this, 'generic_login_errors'));
    }

    public function generic_login_errors($error)
    {
        if (!empty($error) && !is_user_logged_in()) {
            $error = '<strong>' . __('Login Error:', $this->text_domain) . '</strong> ' . __('Invalid username or password.', $this->text_domain) . ' ';
            $error .= '<a href="' . wp_lostpassword_url() . '">' . __('Forgot your password?', $this->text_domain) . '</a>';
        }
        return $error;
    }
}
