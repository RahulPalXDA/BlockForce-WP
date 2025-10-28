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
     * Generate a random slug for the login URL.
     */
    public static function generate_random_slug() {
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $slug = '';
        for ($i = 0; $i < 12; $i++) {
            $slug .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $slug;
    }

    /**
     * Send the admin alert email using the HTML template.
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
            return;
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
        }

        // Set content type to HTML
        add_filter('wp_mail_content_type', array(__CLASS__, 'set_html_content_type'));
        
        @wp_mail($target_email, $subject, $message);
        
        // Reset content type to plain text
        remove_filter('wp_mail_content_type', array(__CLASS__, 'set_html_content_type'));
    }

    /**
     * Helper function to set wp_mail content type to HTML.
     */
    public static function set_html_content_type() {
        return 'text/html';
    }
}