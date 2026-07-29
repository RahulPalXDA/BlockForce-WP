<?php
declare(strict_types=1);
if (!defined('ABSPATH'))
    exit;
class BlockForce_WP_Utils
{
    const TRUSTED_IP_HEADERS = array('X-Forwarded-For', 'CF-Connecting-IP', 'X-Real-IP');
    public static function get_user_ip()
    {
        $remote = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
        $remote = filter_var($remote, FILTER_VALIDATE_IP) ? $remote : '127.0.0.1';
        $settings = get_option('blockforce_settings', array());
        $header = !empty($settings['trusted_ip_header']) ? $settings['trusted_ip_header'] : '';
        $proxies = !empty($settings['trusted_proxies']) ? $settings['trusted_proxies'] : '';
        if ($header && $proxies && in_array($header, self::TRUSTED_IP_HEADERS, true) && self::ip_matches_list($remote, $proxies)) {
            $server_key = 'HTTP_' . str_replace('-', '_', strtoupper($header));
            if (!empty($_SERVER[$server_key])) {
                $forwarded = sanitize_text_field(wp_unslash($_SERVER[$server_key]));
                $first = trim(explode(',', $forwarded)[0]);
                if (filter_var($first, FILTER_VALIDATE_IP))
                    return $first;
            }
        }
        return $remote;
    }
    public static function is_valid_ip_or_cidr($entry)
    {
        if (strpos($entry, '/') === false)
            return (bool) filter_var($entry, FILTER_VALIDATE_IP);
        $parts = explode('/', $entry, 2);
        list($subnet, $bits) = array_pad($parts, 2, null);
        if (!filter_var($subnet, FILTER_VALIDATE_IP) || !ctype_digit((string) $bits))
            return false;
        $max_bits = filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? 32 : 128;
        return ((int) $bits) <= $max_bits;
    }
    private static function ip_matches_list($ip, $list)
    {
        foreach (preg_split('/[\r\n,]+/', $list, -1, PREG_SPLIT_NO_EMPTY) as $entry) {
            $entry = trim($entry);
            if ($entry !== '' && self::ip_in_cidr($ip, $entry))
                return true;
        }
        return false;
    }
    private static function ip_in_cidr($ip, $cidr)
    {
        if (strpos($cidr, '/') === false)
            return $ip === $cidr;
        list($subnet, $bits) = explode('/', $cidr, 2);
        $bits = (int) $bits;
        $ip_bin = @inet_pton($ip);
        $subnet_bin = @inet_pton($subnet);
        if ($ip_bin === false || $subnet_bin === false || strlen($ip_bin) !== strlen($subnet_bin))
            return false;
        $bytes = intdiv($bits, 8);
        $remainder_bits = $bits % 8;
        if ($bytes > 0 && strncmp($ip_bin, $subnet_bin, $bytes) !== 0)
            return false;
        if ($remainder_bits === 0)
            return true;
        $mask = chr((0xFF << (8 - $remainder_bits)) & 0xFF);
        return (($ip_bin[$bytes] & $mask) === ($subnet_bin[$bytes] & $mask));
    }
    public static function get_user_agent()
    {
        if (empty($_SERVER['HTTP_USER_AGENT']))
            return '';
        return substr(sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])), 0, 255);
    }
    public static function get_user_agent_name($user_agent = '')
    {
        $user_agent = $user_agent ? $user_agent : self::get_user_agent();
        if (empty($user_agent))
            return 'Unknown';
        $agent = strtolower($user_agent);
        if (strpos($agent, 'googlebot') !== false)
            return 'Googlebot';
        if (strpos($agent, 'bingbot') !== false)
            return 'Bingbot';
        if (strpos($agent, 'ahrefs') !== false)
            return 'AhrefsBot';
        if (strpos($agent, 'semrush') !== false)
            return 'SemrushBot';
        if (strpos($agent, 'facebookexternalhit') !== false)
            return 'Facebook Crawler';
        if (strpos($agent, 'curl') !== false)
            return 'curl';
        if (strpos($agent, 'wget') !== false)
            return 'wget';
        if (strpos($agent, 'python-requests') !== false)
            return 'Python Requests';
        if (strpos($agent, 'go-http-client') !== false)
            return 'Go HTTP Client';
        if (strpos($agent, 'edg/') !== false || strpos($agent, 'edge/') !== false)
            return 'Microsoft Edge';
        if (strpos($agent, 'opr/') !== false || strpos($agent, 'opera') !== false)
            return 'Opera';
        if (strpos($agent, 'chrome/') !== false || strpos($agent, 'crios/') !== false)
            return 'Google Chrome';
        if (strpos($agent, 'firefox/') !== false || strpos($agent, 'fxios/') !== false)
            return 'Mozilla Firefox';
        if (strpos($agent, 'safari/') !== false)
            return 'Safari';
        return 'Other';
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
        if (defined('WP_CLI') && WP_CLI)
            return true;
        $addr = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
        if (!self::is_localhost_ip($addr))
            return false;
        $srv = isset($_SERVER['SERVER_ADDR']) ? sanitize_text_field($_SERVER['SERVER_ADDR']) : '';
        if (!empty($srv) && !self::is_localhost_ip($srv)) {
            error_log('BlockForce WP: Probable IP Spoof detected, treating request as untrusted.');
            return false;
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
    public static function send_admin_alert($ip, $slug, $user_agent = '')
    {
        $sets = get_option('blockforce_settings', array());
        $to = !empty($sets['alert_email']) ? $sets['alert_email'] : get_option('admin_email');
        $site_name = get_bloginfo('name');
        if (empty($site_name)) {
            $site_name = 'WordPress Site';
        }
        $url = get_site_url();
        $user_ip = !empty($ip) ? $ip : '0.0.0.0';
        $user_agent = $user_agent ? substr(sanitize_text_field($user_agent), 0, 255) : self::get_user_agent();
        $user_agent_name = self::get_user_agent_name($user_agent);
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
                $html = "<p>User Agent: " . esc_html($user_agent_name) . " (" . esc_html($user_agent) . ")</p><p>New Login URL: <a href='" . esc_url($new_login_url) . "'>" . esc_url($new_login_url) . "</a></p>";
            }
        } else {
            error_log('BlockForce WP: Email template file not found at ' . $tpl);
            $html = "<p>User Agent: " . esc_html($user_agent_name) . " (" . esc_html($user_agent) . ")</p><p>New Login URL: <a href='" . esc_url($new_login_url) . "'>" . esc_url($new_login_url) . "</a></p>";
        }
        $plain = "Login URL Updated\nIP: $user_ip\nUser Agent: $user_agent_name\nRaw User Agent: $user_agent\nTime: $current_date_time\nNew URL: $new_login_url";
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
