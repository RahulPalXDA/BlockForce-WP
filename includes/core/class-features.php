<?php
declare(strict_types=1);
if (!defined('ABSPATH'))
    exit;
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
        add_action('login_head', array($this, 'add_referrer_policy_meta'));
        add_action('login_init', array($this, 'add_referrer_policy_header'));
        add_filter('login_headerurl', array($this, 'fix_login_logo_url'));
        add_filter('login_headertext', array($this, 'fix_login_logo_title'));
    }
    public function generic_login_errors($error)
    {
        if (!empty($error) && !is_user_logged_in()) {
            $error = '<strong>' . __('Login Error:', $this->text_domain) . '</strong> ' . __('Invalid username or password.', $this->text_domain);
            $error .= ' <a href="' . wp_lostpassword_url() . '">' . __('Forgot your password?', $this->text_domain) . '</a>';
        }
        return $error;
    }
    public function add_referrer_policy_meta()
    {
        echo '<meta name="referrer" content="no-referrer">' . "\n";
    }
    public function add_referrer_policy_header()
    {
        if (!headers_sent())
            header('Referrer-Policy: no-referrer');
    }
    public function fix_login_logo_url()
    {
        return home_url();
    }
    public function fix_login_logo_title()
    {
        return get_bloginfo('name');
    }
}
