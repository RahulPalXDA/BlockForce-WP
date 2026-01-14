<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class BlockForce_WP_Security
{
    private $settings;
    private $core;

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
        add_action('lostpassword_post', array($this, 'handle_lostpassword_attempt'));
        add_filter('lostpassword_errors', array($this, 'handle_lostpassword_errors'), 10, 2);
        add_action('init', array($this, 'check_and_redirect_blocked_users'), 1);
        add_action('blockforce_cleanup', array($this, 'cleanup_old_attempts'));
    }

    public function log_login_success($user_login, $user)
    {
        $this->log_activity($user_login, 'success');
    }

    public function handle_failed_login($username)
    {
        $this->log_activity($username, 'failed');
        $this->track_security_event($username, 'login');
    }

    public function handle_lostpassword_attempt()
    {
        $user_login = isset($_POST['user_login']) ? sanitize_text_field($_POST['user_login']) : 'unknown';
        $this->log_activity($user_login, 'lostpassword_request');
        $this->track_security_event($user_login, 'lostpassword');
    }

    public function handle_lostpassword_errors($errors, $user_data)
    {
        if ($errors->get_error_code()) {
            $user_login = isset($_POST['user_login']) ? sanitize_text_field($_POST['user_login']) : 'unknown';
            $this->log_activity($user_login, 'lostpassword_failed');
            // We already track the request in lostpassword_post, 
            // but we can double down here if it's an explicit error
        }
        return $errors;
    }

    private function log_activity($username, $status)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . BFWP_LOGS_TABLE;
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

    public function track_security_event($username, $type = 'login')
    {
        if (BlockForce_WP_Utils::is_authentic_localhost()) {
            return;
        }

        $user_ip = BlockForce_WP_Utils::get_user_ip();
        $current_time = time();

        if (empty($user_ip)) {
            return;
        }

        $attempts = $this->get_ip_attempts($user_ip);
        $attempts[] = array(
            'time' => $current_time,
            'type' => $type
        );

        $log_time = isset($this->settings['log_time']) ? (int) $this->settings['log_time'] : 7200;
        $attempts = array_filter($attempts, function ($event) use ($current_time, $log_time) {
            return ($current_time - $event['time']) < $log_time;
        });

        $this->update_ip_attempts($user_ip, $attempts);

        $block_time = isset($this->settings['block_time']) ? (int) $this->settings['block_time'] : 120;
        $attempt_limit = isset($this->settings['attempt_limit']) ? (int) $this->settings['attempt_limit'] : 2;
        $enable_url_change = isset($this->settings['enable_url_change']) ? (int) $this->settings['enable_url_change'] : 1;
        $enable_ip_blocking = isset($this->settings['enable_ip_blocking']) ? (int) $this->settings['enable_ip_blocking'] : 1;

        $login_attempt_count = 0;
        $lostpassword_attempt_count = 0;

        foreach ($attempts as $event) {
            if ($event['type'] === 'login') {
                $login_attempt_count++;
            } elseif ($event['type'] === 'lostpassword') {
                $lostpassword_attempt_count++;
            }
        }

        $should_redirect_home = false;

        // URL change logic (Based on total security events or specific login failures)
        if ($enable_url_change && $login_attempt_count >= $attempt_limit) {
            $this->change_login_url_and_alert($username, $user_ip, $login_attempt_count);
            $should_redirect_home = true;
        }

        // IP Blocking logic (Based on total suspect activity)
        $total_suspect_activity = count($attempts);
        if ($enable_ip_blocking && $total_suspect_activity >= $attempt_limit && !$this->is_ip_blocked($user_ip)) {
            $reason = ($lostpassword_attempt_count >= $attempt_limit) ? 'bruteforce_lostpassword' : 'bruteforce_login';
            $this->block_ip($user_ip, $block_time, $reason);
            $should_redirect_home = true;
        }

        if ($should_redirect_home) {
            wp_safe_redirect(home_url());
            exit;
        }
    }

    private function change_login_url_and_alert($username, $user_ip, $attempt_count)
    {
        $new_login_slug = BlockForce_WP_Utils::generate_random_slug();
        $email_sent = BlockForce_WP_Utils::send_admin_alert($user_ip, $new_login_slug);

        if ($email_sent) {
            update_option('blockforce_login_slug', $new_login_slug);
            $this->core->login_url->flush_rewrite_rules();
        }
    }

    public function check_and_redirect_blocked_users()
    {
        if (!$this->core->login_url->is_login_page()) {
            return;
        }

        $user_ip = BlockForce_WP_Utils::get_user_ip();

        if (isset($this->settings['enable_ip_blocking']) && $this->settings['enable_ip_blocking'] && $this->is_ip_blocked($user_ip)) {
            wp_safe_redirect(home_url());
            exit;
        }
    }

    public function check_blocked_ip($user, $username, $password)
    {
        $user_ip = BlockForce_WP_Utils::get_user_ip();
        if ($this->is_ip_blocked($user_ip)) {
            return new WP_Error('bfwp_blocked', __('Your IP address is temporarily blocked due to too many failed login attempts.', 'blockforce-wp'));
        }
        return $user;
    }

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
        if (BlockForce_WP_Utils::is_localhost_ip($user_ip)) {
            return false;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . BFWP_BLOCKS_TABLE;

        $blocked = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table_name WHERE user_ip = %s AND expires_at > %s",
                $user_ip,
                current_time('mysql')
            )
        );

        return $blocked !== null;
    }

    private function block_ip($user_ip, $block_time, $reason = 'failed_login_attempts')
    {
        global $wpdb;
        $table_name = $wpdb->prefix . BFWP_BLOCKS_TABLE;
        $current_time = current_time('mysql');
        $expires_at = date('Y-m-d H:i:s', strtotime("+$block_time seconds", strtotime($current_time)));

        // Clean up any existing active blocks for this IP first to avoid duplicates
        $wpdb->delete($table_name, array('user_ip' => $user_ip));

        $wpdb->insert(
            $table_name,
            array(
                'user_ip' => $user_ip,
                'blocked_at' => $current_time,
                'expires_at' => $expires_at,
                'reason' => $reason,
            ),
            array('%s', '%s', '%s', '%s')
        );
    }

    public function unblock_ip($user_ip)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . BFWP_BLOCKS_TABLE;
        $wpdb->delete($table_name, array('user_ip' => $user_ip));
        delete_transient('bfwp_attempts_' . $user_ip);
    }

    public function cleanup_old_attempts()
    {
        global $wpdb;
        $table_logs = $wpdb->prefix . BFWP_LOGS_TABLE;
        $retention_days = isset($this->settings['log_retention_days']) ? (int) $this->settings['log_retention_days'] : 30;

        // Delete logs older than configured retention period
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table_logs WHERE time < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $retention_days
            )
        );

        // Clean up expired blocks
        $table_blocks = $wpdb->prefix . BFWP_BLOCKS_TABLE;
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table_blocks WHERE expires_at < %s",
                current_time('mysql')
            )
        );
    }
}
