<?php

if (!defined('ABSPATH')) {
    exit;
}

class BlockForce_WP_Security
{

    private $settings;
    private $core; // Main plugin controller

    public function __construct($settings, $core)
    {
        $this->settings = $settings;
        $this->core = $core;
    }

    public function init_hooks()
    {
        add_action('wp_login_failed', array($this, 'handle_failed_login'));
        add_filter('authenticate', array($this, 'check_blocked_ip'), 30, 3);
        add_action('wp_login', array($this, 'log_login_success'), 10, 2);
        add_action('init', array($this, 'check_and_redirect_blocked_users'), 1);

        // This hook is for the empty cleanup function
        add_action('blockforce_cleanup', array($this, 'cleanup_old_attempts'));
    }

    public function log_login_success($user_login, $user)
    {
        $this->log_activity($user_login, 'success');
    }

    public function handle_failed_login($username)
    {
        $this->log_activity($username, 'failed');
        $this->track_login_attempt($username);
    }

    private function log_activity($username, $status)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'blockforce_logs';
        $user_ip = BlockForce_WP_Utils::get_user_ip();

        $wpdb->insert(
            $table_name,
            array(
                'user_login' => $username,
                'user_ip' => $user_ip,
                'time' => current_time('mysql'),
                'status' => $status
            ),
            array('%s', '%s', '%s', '%s')
        );
    }

    /**
     * Track failed login attempts.
     */
    public function track_login_attempt($username)
    {
        $user_ip = BlockForce_WP_Utils::get_user_ip();
        $current_time = time();

        if (empty($user_ip)) {
            return;
        }

        $attempts = $this->get_ip_attempts($user_ip);
        $attempts[] = $current_time;

        // Clean old attempts based on log_time
        $log_time = isset($this->settings['log_time']) ? (int) $this->settings['log_time'] : 7200;
        $attempts = array_filter($attempts, function ($time) use ($current_time, $log_time) {
            return ($current_time - $time) < $log_time;
        });

        $this->update_ip_attempts($user_ip, $attempts);

        // Check recent attempts for blocking
        $block_time = isset($this->settings['block_time']) ? (int) $this->settings['block_time'] : 120;
        $recent_attempts = array_filter($attempts, function ($time) use ($current_time, $block_time) {
            return ($current_time - $time) < $block_time;
        });

        $attempt_limit = isset($this->settings['attempt_limit']) ? (int) $this->settings['attempt_limit'] : 2;
        $enable_url_change = isset($this->settings['enable_url_change']) ? (int) $this->settings['enable_url_change'] : 1;
        $enable_ip_blocking = isset($this->settings['enable_ip_blocking']) ? (int) $this->settings['enable_ip_blocking'] : 1;

        $recent_attempt_count = count($recent_attempts);
        $persistent_attempt_count = count($attempts);
        $should_redirect_home = false;

        // Auto-change login URL if enabled
        if ($enable_url_change && $persistent_attempt_count >= $attempt_limit) {
            $this->change_login_url_and_alert($username, $user_ip, $persistent_attempt_count);
            $should_redirect_home = true;
        }

        // Block IP if enabled
        if ($enable_ip_blocking && $recent_attempt_count >= $attempt_limit && !$this->is_ip_blocked($user_ip)) {
            $this->block_ip($user_ip, $block_time);
            $should_redirect_home = true;
        }

        if ($should_redirect_home) {
            wp_safe_redirect(home_url());
            exit;
        }
    }

    /**
     * Handles the logic for changing the URL and sending the alert.
     * IMPORTANT: Only changes the login URL if the email is sent successfully.
     * This prevents admin lockout if SMTP/email fails.
     * 
     * @param string $username The username that was attempted
     * @param string $user_ip The IP address of the attacker
     * @param int $attempt_count Number of failed attempts
     */
    private function change_login_url_and_alert($username, $user_ip, $attempt_count)
    {
        $new_login_slug = BlockForce_WP_Utils::generate_random_slug();

        // CRITICAL: Send email FIRST, only change URL if email succeeds
        $email_sent = BlockForce_WP_Utils::send_admin_alert($user_ip, $new_login_slug);

        if ($email_sent) {
            // Email sent successfully, safe to change the login URL
            update_option('blockforce_login_slug', $new_login_slug);

            // Call the flush method from the login_url module via the core controller
            $this->core->login_url->flush_rewrite_rules();

            error_log('BlockForce WP: Login URL changed successfully to /' . $new_login_slug . ' after ' . $attempt_count . ' attempts from IP: ' . $user_ip);
        } else {
            // Email failed - DO NOT change the login URL to prevent lockout
            error_log('BlockForce WP: Login URL change ABORTED - email failed to send. Attack from IP: ' . $user_ip . ' (' . $attempt_count . ' attempts)');
        }
    }

    /**
     * Redirects blocked users away from the login page.
     */
    public function check_and_redirect_blocked_users()
    {
        // We need the login_url module to check if we're on the login page
        if (!$this->core->login_url->is_login_page()) {
            return;
        }

        $user_ip = BlockForce_WP_Utils::get_user_ip();

        if (isset($this->settings['enable_ip_blocking']) && $this->settings['enable_ip_blocking'] && $this->is_ip_blocked($user_ip)) {
            wp_safe_redirect(home_url());
            exit;
        }
    }

    /**
     * Check if the user's IP is blocked during authentication.
     *
     * @param WP_User|WP_Error|null $user
     * @param string $username
     * @param string $password
     * @return WP_User|WP_Error|null
     */
    public function check_blocked_ip($user, $username, $password)
    {
        $user_ip = BlockForce_WP_Utils::get_user_ip();
        if ($this->is_ip_blocked($user_ip)) {
            return new WP_Error('bfwp_blocked', __('Your IP address is temporarily blocked due to too many failed login attempts.', 'blockforce-wp'));
        }
        return $user;
    }

    // --- Transient Helper Methods ---

    private function get_ip_attempts($user_ip)
    {
        $attempts = get_transient('bfwp_attempts_' . $user_ip);
        return $attempts ?: array();
    }

    private function update_ip_attempts($user_ip, $attempts)
    {
        $log_time = isset($this->settings['log_time']) ? (int) $this->settings['log_time'] : 7200;
        set_transient('bfwp_attempts_' . $user_ip, $attempts, $log_time);
    }

    public function is_ip_blocked($user_ip)
    {
        $block_data = get_option('bfwp_blocked_' . $user_ip);
        if ($block_data !== false) {
            // Check if block is still active
            $parts = explode('|', $block_data);
            if (count($parts) === 2) {
                $expires_at = intval($parts[1]);
                if (BlockForce_WP_Utils::get_current_time() < $expires_at) {
                    return true;
                }
            }
            // If expired, we still keep the record (don't return true)
        }
        return false;
    }

    private function block_ip($user_ip, $block_time)
    {
        // Store permanent record with expiration time
        // Format: timestamp_blocked|timestamp_expires
        $current_time = BlockForce_WP_Utils::get_current_time();
        $expires_at = $current_time + $block_time;
        $value = $current_time . '|' . $expires_at;

        add_option('bfwp_blocked_' . $user_ip, $value, '', 'no');
        update_option('bfwp_blocked_' . $user_ip, $value, 'no');
    }

    public function unblock_ip($user_ip)
    {
        delete_option('bfwp_blocked_' . $user_ip);
        delete_transient('bfwp_attempts_' . $user_ip);
    }

    public function cleanup_old_attempts()
    {
        // Transients auto-expire, so no manual cleanup is needed.
    }
}