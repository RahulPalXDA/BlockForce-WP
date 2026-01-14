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
        $site = get_bloginfo('name');
        $url = get_site_url();
        $login = $url . '/' . $slug;
        $time = current_time('mysql');
        $subject = sprintf('[%s] WordPress Login URL Updated', $site);
        $tpl = BFWP_PATH . 'includes/email/alert-template.php';
        if (file_exists($tpl)) {
            ob_start();
            include $tpl;
            $html = ob_get_clean();
        } else {
            $html = "<p>New Login URL: <a href='" . esc_url($login) . "'>" . esc_url($login) . "</a></p>";
        }
        $plain = "Login URL Updated\nIP: $ip\nTime: $time\nNew URL: $login";
        $domain = parse_url($url, PHP_URL_HOST);
        if (substr($domain, 0, 4) === 'www.')
            $domain = substr($domain, 4);
        $headers = array('From: ' . $site . ' <wordpress@' . $domain . '>', 'Reply-To: ' . get_option('admin_email'), 'Content-Type: text/html; charset=UTF-8');
        $sent = wp_mail($to, $subject, $html, $headers);
        if (!$sent) {
            $headers[2] = 'Content-Type: text/plain; charset=UTF-8';
            $sent = wp_mail($to, $subject, $plain, $headers);
        }
        return $sent;
    }
}
