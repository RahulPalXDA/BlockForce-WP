<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class BlockForce_WP_Utils
{
    public static function get_current_time()
    {
        return time();
    }

    public static function get_user_ip()
    {
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        return '127.0.0.1';
    }

    public static function is_localhost_ip($ip)
    {
        if (empty($ip)) {
            return false;
        }

        $ip = trim($ip);

        if ($ip === '::1') {
            return true;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ip_long = ip2long($ip);
            $loopback_start = ip2long('127.0.0.0');
            $loopback_end = ip2long('127.255.255.255');

            if ($ip_long >= $loopback_start && $ip_long <= $loopback_end) {
                return true;
            }
        }

        return false;
    }

    public static function is_authentic_localhost()
    {
        $remote_addr = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';

        if (!self::is_localhost_ip($remote_addr)) {
            return false;
        }

        $server_addr = isset($_SERVER['SERVER_ADDR']) ? sanitize_text_field($_SERVER['SERVER_ADDR']) : '';

        if (!empty($server_addr) && !self::is_localhost_ip($server_addr)) {
            error_log('BlockForce WP: Localhost request with non-localhost SERVER_ADDR. REMOTE_ADDR: ' . $remote_addr . ', SERVER_ADDR: ' . $server_addr);
        }

        return true;
    }

    public static function generate_random_slug()
    {
        try {
            return bin2hex(random_bytes(6));
        } catch (Exception $e) {
            return substr(str_replace(array('-', '_'), '', wp_generate_password(12, false)), 0, 12);
        }
    }

    public static function send_admin_alert($user_ip, $new_slug)
    {
        $settings = get_option('blockforce_settings', array());
        $target_email = isset($settings['alert_email']) && !empty($settings['alert_email'])
            ? $settings['alert_email']
            : get_option('admin_email');

        $site_url = get_site_url();
        $site_name = get_bloginfo('name');
        $new_login_url = $site_url . '/' . $new_slug;
        $current_date_time = current_time('mysql');

        $subject = sprintf('[%s] WordPress Login URL Updated', $site_name);

        $template_path = BFWP_PATH . 'includes/email/alert-template.php';

        $html_message = '';
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            $html_message = ob_get_clean();
        } else {
            $html_message = "<p>Your WordPress login URL has been updated.</p>";
            $html_message .= "<p>New URL: <a href='" . esc_url($new_login_url) . "'>" . esc_url($new_login_url) . "</a></p>";
        }

        $plain_message = "WordPress Login URL Updated\n\n";
        $plain_message .= "Hello,\n\n";
        $plain_message .= "Your WordPress login URL has been updated by BlockForce WP due to multiple failed login attempts.\n\n";
        $plain_message .= "Activity Details:\n";
        $plain_message .= "IP Address: " . $user_ip . "\n";
        $plain_message .= "Date & Time: " . $current_date_time . "\n\n";
        $plain_message .= "New Login Page:\n";
        $plain_message .= $new_login_url . "\n\n";
        $plain_message .= "Please bookmark this URL for future access.\n\n";
        $plain_message .= "Regards,\n" . $site_name;

        $admin_email = get_option('admin_email');
        $domain = parse_url($site_url, PHP_URL_HOST);
        if (substr($domain, 0, 4) === 'www.') {
            $domain = substr($domain, 4);
        }

        $from_email = 'wordpress@' . $domain;
        $from_name = $site_name;

        $headers = array();
        $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
        $headers[] = 'Reply-To: ' . $admin_email;
        $headers[] = 'X-Mailer: WordPress/' . get_bloginfo('version') . '; ' . home_url();
        $headers[] = 'X-Priority: 1';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';

        $sent = wp_mail($target_email, $subject, $html_message, $headers);

        if (!$sent) {
            $plain_headers = array();
            $plain_headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
            $plain_headers[] = 'Reply-To: ' . $admin_email;
            $plain_headers[] = 'Content-Type: text/plain; charset=UTF-8';

            $sent = wp_mail($target_email, $subject, $plain_message, $plain_headers);
        }

        return $sent;
    }

    public static function set_html_content_type()
    {
        return 'text/html';
    }
}
