<?php

if (!defined('ABSPATH')) {
    exit;
}

class BlockForce_WP_Utils
{
    /**
     * Get the current Unix timestamp.
     *
     * @return int The current Unix timestamp.
     */
    public static function get_current_time()
    {
        return time();
    }

    /**
     * Get the user's IP address.
     */
    public static function get_user_ip()
    {
        // SECURITY FIX: Only trust REMOTE_ADDR.
        // Trusting headers like X-Forwarded-For allows IP spoofing.
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        return '127.0.0.1';
    }

    /**
     * Check if an IP address is a localhost IP.
     *
     * Covers all standard localhost representations:
     * - 127.0.0.1 (IPv4 loopback)
     * - ::1 (IPv6 loopback)
     * - 127.0.0.0/8 range (all 127.x.x.x addresses)
     *
     * @param string $ip The IP address to check.
     * @return bool True if the IP is a localhost IP, false otherwise.
     */
    public static function is_localhost_ip($ip)
    {
        if (empty($ip)) {
            return false;
        }

        // Normalize the IP
        $ip = trim($ip);

        // IPv6 loopback
        if ($ip === '::1') {
            return true;
        }

        // IPv4 loopback range: 127.0.0.0/8
        // This covers 127.0.0.1, 127.0.0.2, 127.1.1.1, etc.
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

    /**
     * Check if the current request is an authentic localhost connection.
     *
     * SECURITY: This prevents spoofing attacks by validating that:
     * 1. REMOTE_ADDR is a localhost IP (cannot be spoofed at application level)
     * 2. SERVER_ADDR is also a localhost IP (server is listening on loopback)
     *
     * A hacker cannot spoof REMOTE_ADDR through HTTP headers - it comes from
     * the actual TCP/IP socket connection. The only way to have a localhost
     * REMOTE_ADDR is to be physically on the same machine.
     *
     * @return bool True if the request is from an authentic localhost, false otherwise.
     */
    public static function is_authentic_localhost()
    {
        // Get the client IP from REMOTE_ADDR (secure, cannot be spoofed)
        $remote_addr = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';

        // First check: REMOTE_ADDR must be localhost
        if (!self::is_localhost_ip($remote_addr)) {
            return false;
        }

        // Second check: SERVER_ADDR should also be localhost for full validation
        // This ensures the server is running on loopback interface
        $server_addr = isset($_SERVER['SERVER_ADDR']) ? sanitize_text_field($_SERVER['SERVER_ADDR']) : '';

        // If SERVER_ADDR is available, validate it's also localhost
        // (Some server configurations may not expose SERVER_ADDR)
        if (!empty($server_addr) && !self::is_localhost_ip($server_addr)) {
            // Server is not on localhost, but client claims to be - suspicious
            // However, this could happen with Docker/container setups where
            // the web server binds to 0.0.0.0 but request comes from localhost
            // So we still allow it if REMOTE_ADDR is verified localhost
            // because REMOTE_ADDR cannot be spoofed at the application level

            // Log for monitoring but don't block
            error_log('BlockForce WP: Localhost request detected with non-localhost SERVER_ADDR. REMOTE_ADDR: ' . $remote_addr . ', SERVER_ADDR: ' . $server_addr);
        }

        // REMOTE_ADDR is localhost - this is authentic
        // REMOTE_ADDR comes from the actual TCP socket, not HTTP headers
        return true;
    }

    /**
     * Generate a random slug for the login URL using cryptographically secure randomness.
     *
     * @return string A 12-character hexadecimal string (cryptographically secure)
     */
    public static function generate_random_slug()
    {
        try {
            // Generate 6 random bytes and convert to 12-character hex string
            // This is cryptographically secure, unlike rand()
            return bin2hex(random_bytes(6));
        } catch (Exception $e) {
            // Fallback to wp_generate_password if random_bytes fails
            // Remove special characters to keep URL-friendly
            return substr(str_replace(array('-', '_'), '', wp_generate_password(12, false)), 0, 12);
        }
    }


    /**
     * Send an admin alert email when login URL changes
     *
     * @param string $user_ip The IP address that triggered the change.
     * @param string $new_slug The new login slug.
     * @return bool True if email sent successfully, false otherwise.
     */
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

        // Improved subject line - less alarming, more professional
        $subject = sprintf('[%s] WordPress Login URL Updated', $site_name);

        // Load the HTML template
        $template_path = BFWP_PATH . 'includes/email/alert-template.php';

        $html_message = '';
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            $html_message = ob_get_clean();
        } else {
            // Fallback HTML
            $html_message = "<p>Your WordPress login URL has been updated.</p>";
            $html_message .= "<p>New URL: <a href='" . esc_url($new_login_url) . "'>" . esc_url($new_login_url) . "</a></p>";
            error_log('BlockForce WP: Email template not found at ' . $template_path);
        }

        // Create plain text version (anti-spam measure)
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

        // Set up email headers
        $admin_email = get_option('admin_email');

        // Get the domain from the site URL
        $domain = parse_url($site_url, PHP_URL_HOST);
        if (substr($domain, 0, 4) === 'www.') {
            $domain = substr($domain, 4);
        }

        // Use wordpress@domain.com as the sender to avoid DMARC/SPF issues
        // This is critical for deliverability if admin_email is Gmail/Yahoo etc.
        $from_email = 'wordpress@' . $domain;
        $from_name = $site_name;

        $headers = array();
        $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
        $headers[] = 'Reply-To: ' . $admin_email;
        $headers[] = 'X-Mailer: WordPress/' . get_bloginfo('version') . '; ' . home_url();
        $headers[] = 'X-Priority: 1';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';

        // Send HTML version
        $sent = wp_mail($target_email, $subject, $html_message, $headers);

        // Log the result
        if (!$sent) {
            error_log('BlockForce WP: Failed to send alert email to ' . $target_email . ' for IP: ' . $user_ip);

            // Try sending plain text version as fallback
            $plain_headers = array();
            $plain_headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
            $plain_headers[] = 'Reply-To: ' . $admin_email;
            $plain_headers[] = 'Content-Type: text/plain; charset=UTF-8';

            $sent = wp_mail($target_email, $subject, $plain_message, $plain_headers);

            if ($sent) {
                error_log('BlockForce WP: Plain text fallback email sent successfully to ' . $target_email);
            }
        } else {
            error_log('BlockForce WP: Alert email sent successfully to ' . $target_email);
        }

        return $sent;
    }

    /**
     * Helper function to set wp_mail content type to HTML.
     */
    public static function set_html_content_type()
    {
        return 'text/html';
    }
}
