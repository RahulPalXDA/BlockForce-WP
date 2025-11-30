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