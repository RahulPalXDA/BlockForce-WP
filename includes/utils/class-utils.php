<?php
declare(strict_types=1);
if (!defined('ABSPATH'))
    exit;
class BlockForce_WP_Utils
{
    public static function get_user_ip()
    {
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
            if (filter_var($ip, FILTER_VALIDATE_IP))
                return $ip;
        }
        return '127.0.0.1';
    }
    public static function is_localhost_ip($ip)
    {
        if (empty($ip))
            return false;
        $ip = trim($ip);
        if ($ip === '::1')
            return true;
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ip_long = ip2long($ip);
            return ($ip_long >= ip2long('127.0.0.0') && $ip_long <= ip2long('127.255.255.255'));
        }
        return false;
    }
    public static function is_authentic_localhost()
    {
        $addr = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
        if (!self::is_localhost_ip($addr))
            return false;
        $srv = isset($_SERVER['SERVER_ADDR']) ? sanitize_text_field($_SERVER['SERVER_ADDR']) : '';
        if (!empty($srv) && !self::is_localhost_ip($srv))
            error_log('BlockForce WP: Probable IP Spoof detected.');
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
    public static function send_admin_alert($ip, $slug)
    {
        $sets = get_option('blockforce_settings', array());
        $to = !empty($sets['alert_email']) ? $sets['alert_email'] : get_option('admin_email');
        $site_name = get_bloginfo('name');
        if (empty($site_name)) {
            $site_name = 'WordPress Site';
        }
        $url = get_site_url();
        $user_ip = !empty($ip) ? $ip : '0.0.0.0';
        $new_login_url = $url . '/' . $slug;
        $current_date_time = current_time('mysql');
        if (empty($current_date_time)) {
            $current_date_time = date('Y-m-d H:i:s');
        }
        if (empty($new_login_url) || empty($slug)) {
            error_log('BlockForce WP: Failed to generate login URL - slug is empty');
            return false;
        }
        $subject = sprintf('[%s] WordPress Login URL Updated', $site_name);
        $tpl = BFWP_PATH . 'includes/email/alert-template.php';
        if (file_exists($tpl)) {
            ob_start();
            include $tpl;
            $html = ob_get_clean();
            if (empty($html)) {
                error_log('BlockForce WP: Email template generated empty HTML');
                $html = "<p>New Login URL: <a href='" . esc_url($new_login_url) . "'>" . esc_url($new_login_url) . "</a></p>";
            }
        } else {
            error_log('BlockForce WP: Email template file not found at ' . $tpl);
            $html = "<p>New Login URL: <a href='" . esc_url($new_login_url) . "'>" . esc_url($new_login_url) . "</a></p>";
        }
        $plain = "Login URL Updated\nIP: $user_ip\nTime: $current_date_time\nNew URL: $new_login_url";
        $domain = parse_url($url, PHP_URL_HOST);
        if (substr($domain, 0, 4) === 'www.')
            $domain = substr($domain, 4);
        if (empty($domain)) {
            $domain = 'localhost';
        }
        $headers = array('From: ' . $site_name . ' <wordpress@' . $domain . '>', 'Reply-To: ' . get_option('admin_email'), 'Content-Type: text/html; charset=UTF-8');
        $sent = wp_mail($to, $subject, $html, $headers);
        if (!$sent) {
            $headers[2] = 'Content-Type: text/plain; charset=UTF-8';
            $sent = wp_mail($to, $subject, $plain, $headers);
        }
        if (!$sent) {
            error_log('BlockForce WP: Failed to send admin alert email to ' . $to);
        }
        return $sent;
    }
}
