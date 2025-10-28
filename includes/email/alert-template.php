<?php
/**
 * BlockForce WP: Admin Alert Email Template
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// This template is included from class-blockforce-wp-utils.php
// It has access to the following variables:
// 
// $user_ip           (string) The attacker's IP address
// $new_login_url     (string) The new, secret login URL
// $current_date_time (string) The date and time of the event

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Critical Security Notice</title>
    <style type="text/css">
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
        .container { width: 90%; max-width: 600px; margin: 20px auto; border: 1px solid #ddd; border-radius: 5px; overflow: hidden; }
        .header { background-color: #d63638; padding: 20px; text-align: center; }
        .header h1 { margin: 0; color: #ffffff; font-size: 24px; }
        .content { padding: 30px; }
        .content p { font-size: 16px; line-height: 1.6; color: #333; }
        .content .highlight { background-color: #f6f7f7; padding: 15px; border-left: 4px solid #0073aa; margin: 20px 0; }
        .content .highlight code { background: #fff; padding: 2px 5px; border: 1px solid #ddd; font-family: monospace; }
        .footer { background-color: #f6f7f7; padding: 20px; text-align: center; font-size: 12px; color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Security Notice</h1>
        </div>
        <div class="content">
            <p>Dear Site Administrator,</p>
            <p>This is a critical security notification from your BlockForce WP plugin.<br>Our monitoring system has detected unauthorized login attempts to your WordPress administrative panel.</p>
            
            <p><strong>Attempt Details:</strong></p>
            <div class="highlight">
                <strong>Source IP Address:</strong> <?php echo esc_html($user_ip); ?><br>
                <strong>Date & Time:</strong> <?php echo esc_html($current_date_time); ?>
            </div>

            <p>In response to these attempts, and as a proactive security measure, your admin login URL has been automatically changed to prevent unauthorized access.</p>
            
            <p><strong>Your New Login URL:</strong><br>Please use this new URL to login into your administrative panel:</p>
            <div class="highlight">
                <a href="<?php echo esc_url($new_login_url); ?>">
                    <?php echo esc_url($new_login_url); ?>
                </a>
            </div>

            <p>We also strongly recommend reviewing your user passwords to ensure they are all strong and unique, especially for administrator accounts.</p>
            
            <p>Best Regards,<br>The BlockForce Team.</p>
        </div>
        <div class="footer">
            <p>This automated message was sent from your WordPress site.</p>
        </div>
    </div>
</body>
</html>