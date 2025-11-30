<?php

if (!defined('ABSPATH')) {
    exit;
}

class BlockForce_WP_Login_Url
{

    private $settings;
    private $core;
    private $login_slug;

    public function __construct($settings, $core)
    {
        $this->settings = $settings;
        $this->core = $core;
        $this->login_slug = $this->get_login_slug();
    }

    public function init_hooks()
    {
        add_filter('rewrite_rules_array', array($this, 'filter_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_custom_login_url'));
        add_action('login_init', array($this, 'redirect_default_login_if_custom_active'));
        add_action('admin_init', array($this, 'prevent_wpadmin_redirect'));

        add_filter('site_url', array($this, 'change_login_url'), 10, 4);
        add_filter('network_site_url', array($this, 'change_login_url'), 10, 3);
        add_filter('wp_redirect', array($this, 'change_login_redirect_url'), 10, 2);
        add_filter('logout_redirect', array($this, 'change_logout_redirect_url'), 10, 3);

        // Remove default WordPress redirects to prevent exposing the secret URL
        add_action('init', array($this, 'remove_default_redirects'));
    }

    public function get_login_slug()
    {
        return get_option('blockforce_login_slug', '');
    }

    public function flush_rewrite_rules()
    {
        flush_rewrite_rules(false);
    }

    public function filter_rewrite_rules($rules)
    {
        $current_slug = $this->get_login_slug();
        if ($current_slug) {
            $new_rules = array();
            $new_rules['^' . preg_quote($current_slug, '/') . '/?$'] = 'index.php?blockforce_login=1';
            return $new_rules + $rules;
        }
        return $rules;
    }

    public function add_query_vars($vars)
    {
        $vars[] = 'blockforce_login';
        return $vars;
    }

    public function redirect_default_login_if_custom_active()
    {
        global $pagenow;
        if ($this->login_slug && $pagenow === 'wp-login.php') {
            $this->trigger_404();
            exit;
        }
    }

    public function prevent_wpadmin_redirect()
    {
        $user_ip = BlockForce_WP_Utils::get_user_ip();

        if (is_admin() && !is_user_logged_in() && !defined('DOING_AJAX')) {
            $is_blocked = isset($this->settings['enable_ip_blocking']) && $this->core->security->is_ip_blocked($user_ip);
            if ($this->login_slug || $is_blocked) {
                $this->trigger_404();
                exit;
            }
        }
    }

    /**
     * Trigger a 404 error by redirecting to a non-existent slug.
     * This ensures the theme's native 404 page is rendered correctly.
     */
    private function trigger_404()
    {
        wp_safe_redirect(home_url('404'));
        exit;
    }

    public function handle_custom_login_url()
    {
        if (!$this->login_slug) {
            return;
        }

        if (get_query_var('blockforce_login') === '1') {
            $user_ip = BlockForce_WP_Utils::get_user_ip();
            $is_blocked = isset($this->settings['enable_ip_blocking']) && $this->core->security->is_ip_blocked($user_ip);

            if ($is_blocked) {
                wp_safe_redirect(home_url());
                exit;
            }

            require_once(ABSPATH . 'wp-login.php');
            exit;
        }
    }

    public function change_login_url($url, $path, $scheme = null, $blog_id = null)
    {
        if ($this->login_slug && strpos($url, 'wp-login.php') !== false) {
            $url = str_replace('wp-login.php', $this->login_slug, $url);
        }
        return $url;
    }

    public function change_login_redirect_url($location, $status)
    {
        if ($this->login_slug && strpos($location, 'wp-login.php') !== false) {
            $location = str_replace('wp-login.php', $this->login_slug, $location);
        }
        return $location;
    }

    public function change_logout_redirect_url($redirect_to, $requested_redirect_to, $user)
    {
        if ($this->login_slug) {
            $redirect_to = site_url($this->login_slug, 'login');
        }
        return $redirect_to;
    }

    /**
     * Helper to check if we are on any version of the login page.
     */
    public function is_login_page()
    {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field($_SERVER['REQUEST_URI']) : '';
        if ($this->login_slug && strpos($request_uri, '/' . $this->login_slug) !== false) {
            return true;
        }
        global $pagenow;
        if (isset($pagenow) && $pagenow === 'wp-login.php') {
            return true;
        }
        return false;
    }

    /**
     * Remove default WordPress redirects that might expose the secret URL.
     * 
     * WordPress by default redirects /login, /admin, /dashboard to the login page.
     * We want to stop this behavior when a custom slug is active.
     */
    public function remove_default_redirects()
    {
        if ($this->login_slug) {
            remove_action('template_redirect', 'wp_redirect_admin_locations', 1000);
        }
    }
}