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
    <title><?php echo esc_html__('WordPress Login URL Updated', 'blockforce-wp'); ?></title>
    <style type="text/css">
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }

        .container {
            width: 90%;
            max-width: 600px;
            margin: 20px auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }

        .header {
            background-color: #d63638;
            padding: 20px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            color: #ffffff;
            font-size: 22px;
            text-transform: uppercase;
        }

        .content {
            padding: 30px;
            background: #fff;
        }

        .content p {
            font-size: 15px;
            line-height: 1.6;
            color: #333;
            margin: 15px 0;
        }

        .content .info-box {
            background-color: #fcf9e8;
            padding: 15px;
            border-left: 4px solid #d63638;
            margin: 20px 0;
        }

        .content .url-box {
            background-color: #f6f7f7;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            border: 1px solid #ddd;
        }

        .content .url-box a {
            color: #d63638;
            text-decoration: none;
            word-break: break-all;
            font-weight: bold;
        }

        .footer {
            background-color: #f6f7f7;
            padding: 15px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1><?php echo esc_html__('LOGIN URL UPDATED', 'blockforce-wp'); ?></h1>
        </div>
        <div class="content">
            <p><?php echo esc_html__('Hello,', 'blockforce-wp'); ?></p>
            <p><?php echo esc_html__('Your WordPress login URL has been updated by BlockForce WP due to multiple failed login attempts.', 'blockforce-wp'); ?>
            </p>

            <div class="info-box">
                <strong><?php echo esc_html__('Activity Details:', 'blockforce-wp'); ?></strong><br>
                <?php echo esc_html__('IP Address:', 'blockforce-wp'); ?> <?php echo esc_html($user_ip); ?><br>
                <?php echo esc_html__('Date & Time:', 'blockforce-wp'); ?> <?php echo esc_html($current_date_time); ?>
            </div>

            <p><?php echo esc_html__('To protect your site, the login page has been moved to a new location.', 'blockforce-wp'); ?>
            </p>

            <div class="url-box">
                <strong><?php echo esc_html__('New Login Page:', 'blockforce-wp'); ?></strong><br>
                <a href="<?php echo esc_url($new_login_url); ?>">
                    <?php echo esc_url($new_login_url); ?>
                </a>
            </div>

            <p><?php echo esc_html__('Please bookmark this URL for future access.', 'blockforce-wp'); ?></p>

            <p><?php echo esc_html__('Regards,', 'blockforce-wp'); ?><br><?php echo esc_html($site_name); ?></p>
        </div>
        <div class="footer">
            <p><?php echo sprintf(esc_html__('Automated message from %s', 'blockforce-wp'), esc_html($site_name)); ?>
            </p>
        </div>
    </div>
</body>

</html>