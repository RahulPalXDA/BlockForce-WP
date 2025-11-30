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
// $site_name         (string) The site name

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>WordPress Login URL Updated</title>
    <style type="text/css">
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
        .container { width: 90%; max-width: 600px; margin: 20px auto; border: 1px solid #ddd; border-radius: 5px; overflow: hidden; }
        .header { background-color: #d63638; padding: 20px; text-align: center; }
        .header h1 { margin: 0; color: #ffffff; font-size: 22px; text-transform: uppercase; }
        .content { padding: 30px; background: #fff; }
        .content p { font-size: 15px; line-height: 1.6; color: #333; margin: 15px 0; }
        .content .info-box { background-color: #fcf9e8; padding: 15px; border-left: 4px solid #d63638; margin: 20px 0; }
        .content .url-box { background-color: #f6f7f7; padding: 15px; border-radius: 4px; margin: 20px 0; border: 1px solid #ddd; }
        .content .url-box a { color: #d63638; text-decoration: none; word-break: break-all; font-weight: bold; }
        .footer { background-color: #f6f7f7; padding: 15px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>LOGIN URL UPDATED</h1>
        </div>
        <div class="content">
            <p>Hello,</p>
            <p>Your WordPress login URL has been updated by BlockForce WP due to multiple failed login attempts.</p>
            
            <div class="info-box">
                <strong>Activity Details:</strong><br>
                IP Address: <?php echo esc_html($user_ip); ?><br>
                Date & Time: <?php echo esc_html($current_date_time); ?>
            </div>

            <p>To protect your site, the login page has been moved to a new location.</p>
            
            <div class="url-box">
                <strong>New Login Page:</strong><br>
                <a href="<?php echo esc_url($new_login_url); ?>">
                    <?php echo esc_url($new_login_url); ?>
                </a>
            </div>

            <p>Please bookmark this URL for future access.</p>
            
            <p>Regards,<br><?php echo esc_html($site_name); ?></p>
        </div>
        <div class="footer">
            <p>Automated message from <?php echo esc_html($site_name); ?></p>
        </div>
    </div>
</body>
</html>