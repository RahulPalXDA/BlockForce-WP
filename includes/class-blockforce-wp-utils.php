<?php

if (!defined('ABSPATH')) {
    exit;
}

class BlockForce_WP_Utils {

    /**
     * Get the user's IP address.
     */
    public static function get_user_ip() {
        $ip_keys = array('HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = sanitize_text_field($_SERVER[$key]);
                $ip = trim(explode(',', $ip)[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '127.0.0.1';
    }

    /**
     * Generate a random slug for the login URL using cryptographically secure randomness.
     * 
     * @return string A 12-character hexadecimal string (cryptographically secure)
     */
    public static function generate_random_slug() {
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
     * Send the admin alert email using the HTML template.
     * 
     * @param string $user_ip The IP address of the attacker
     * @param string $new_login_slug The new login slug that was generated
     * @return bool True if email was sent successfully, false otherwise
     */
    public static function send_admin_alert($user_ip, $new_login_slug) {
        // Get the plugin settings to check for a custom email
        $settings = get_option('blockforce_settings');
        
        $target_email = '';
        if (isset($settings['alert_email']) && is_email($settings['alert_email'])) {
            $target_email = $settings['alert_email'];
        } else {
            // Fallback to the default admin email
            $target_email = get_option('admin_email');
        }
        
        if (empty($target_email)) {
            error_log('BlockForce WP: No valid email address configured for security alerts');
            return false;
        }
        
        // Template variables
        $new_login_url = site_url('/' . $new_login_slug);
        $current_date_time = current_time('mysql');
        $subject = 'Important Security Notice: Login URL Changed';
        
        // Load the HTML template
        $template_path = BFWP_PATH . 'includes/email/alert-template.php';
        
        if (file_exists($template_path)) {
            // Use output buffering to "render" the PHP template file
            ob_start();
            
            // Make variables available to the included template
            include $template_path;
            
            $message = ob_get_clean();
        } else {
            // Fallback to plain text if template is missing
            $message = "Security Alert: Login URL changed due to failed attempts from IP: " . $user_ip . "\n";
            $message .= "New Login URL: " . $new_login_url;
            error_log('BlockForce WP: Email template not found at ' . $template_path);
        }

        // Set content type to HTML
        add_filter('wp_mail_content_type', array(__CLASS__, 'set_html_content_type'));
        
        $sent = wp_mail($target_email, $subject, $message);
        
        // Reset content type to plain text
        remove_filter('wp_mail_content_type', array(__CLASS__, 'set_html_content_type'));
        
        // Log the result
        if (!$sent) {
            error_log('BlockForce WP: Failed to send alert email to ' . $target_email . ' for IP: ' . $user_ip);
        } else {
            error_log('BlockForce WP: Security alert email sent successfully to ' . $target_email);
        }
        
        return $sent;
    }

    /**
     * Helper function to set wp_mail content type to HTML.
     */
    public static function set_html_content_type() {
        return 'text/html';
    }
}