<?php
if (!defined('ABSPATH')) {
    exit;
}

$text_domain = $args['text_domain'];

$reset_options = array(
    array(
        'id' => 'clear_logs',
        'icon' => 'dashicons-list-view',
        'title' => __('Clear Activity Logs', $text_domain),
        'desc' => __('Remove all login attempt records from the database. This clears the Activity Log page.', $text_domain),
        'button' => __('Clear Logs', $text_domain),
        'confirm' => __('Are you sure you want to delete all activity logs?', $text_domain),
        'color' => 'blue',
    ),
    array(
        'id' => 'clear_blocked',
        'icon' => 'dashicons-shield-alt',
        'title' => __('Clear Blocked IPs', $text_domain),
        'desc' => __('Remove all blocked IP addresses. Blocked attackers will be able to access the login page again.', $text_domain),
        'button' => __('Clear Blocked IPs', $text_domain),
        'confirm' => __('Are you sure you want to unblock all IP addresses?', $text_domain),
        'color' => 'orange',
    ),
    array(
        'id' => 'clear_attempts',
        'icon' => 'dashicons-backup',
        'title' => __('Clear Attempt Tracking', $text_domain),
        'desc' => __('Reset the failed login attempt counters. This won\'t unblock IPs but resets their attempt count.', $text_domain),
        'button' => __('Clear Attempts', $text_domain),
        'confirm' => __('Are you sure you want to reset attempt tracking?', $text_domain),
        'color' => 'blue',
    ),
    array(
        'id' => 'reset_url',
        'icon' => 'dashicons-admin-links',
        'title' => __('Reset Login URL', $text_domain),
        'desc' => __('Restore the default WordPress login URL (wp-login.php). You will receive an email with the current URL before this change.', $text_domain),
        'button' => __('Reset Login URL', $text_domain),
        'confirm' => __('Are you sure you want to reset the login URL to default?', $text_domain),
        'color' => 'orange',
    ),
);
?>
<div class="wrap blockforce-wrap">
    <h1><span class="dashicons dashicons-update"></span> <?php esc_html_e('Reset & Tools', $text_domain); ?></h1>

    <?php settings_errors('blockforce_messages'); ?>

    <p class="blockforce-page-desc">
        <?php esc_html_e('Use these tools to reset specific parts of the plugin. Each action is independent.', $text_domain); ?>
    </p>

    <div class="blockforce-reset-grid">
        <?php foreach ($reset_options as $option):
            $url = wp_nonce_url(
                admin_url('admin.php?page=blockforce-wp-reset&bfwp_action=' . $option['id']),
                'bfwp_reset_' . $option['id']
            );
            ?>
            <div class="blockforce-reset-card blockforce-reset-card--<?php echo esc_attr($option['color']); ?>">
                <div class="blockforce-reset-card__icon">
                    <span class="dashicons <?php echo esc_attr($option['icon']); ?>"></span>
                </div>
                <div class="blockforce-reset-card__content">
                    <h3><?php echo esc_html($option['title']); ?></h3>
                    <p><?php echo esc_html($option['desc']); ?></p>
                    <a href="<?php echo esc_url($url); ?>" class="button button-secondary"
                        onclick="return confirm('<?php echo esc_js($option['confirm']); ?>')">
                        <?php echo esc_html($option['button']); ?>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="blockforce-card blockforce-card--mt blockforce-card--danger">
        <h2><span class="dashicons dashicons-warning"></span> <?php esc_html_e('Full Plugin Reset', $text_domain); ?>
        </h2>
        <p><?php esc_html_e('This will perform ALL reset actions at once:', $text_domain); ?></p>
        <ul class="blockforce-reset-list">
            <li><?php esc_html_e('Clear all activity logs', $text_domain); ?></li>
            <li><?php esc_html_e('Unblock all IP addresses', $text_domain); ?></li>
            <li><?php esc_html_e('Reset attempt tracking', $text_domain); ?></li>
            <li><?php esc_html_e('Restore default login URL', $text_domain); ?></li>
        </ul>
        <p><strong><?php esc_html_e('Note:', $text_domain); ?></strong>
            <?php esc_html_e('Your configuration settings will NOT be changed.', $text_domain); ?></p>

        <?php
        $full_reset_url = wp_nonce_url(
            admin_url('admin.php?page=blockforce-wp-reset&bfwp_action=full_reset'),
            'bfwp_reset_full_reset'
        );
        ?>
        <p class="blockforce-mt-15">
            <a href="<?php echo esc_url($full_reset_url); ?>" class="button button-large blockforce-button-danger"
                onclick="return confirm('<?php echo esc_js(__('WARNING: This will reset ALL plugin data. Are you absolutely sure?', $text_domain)); ?>')">
                <span class="dashicons dashicons-trash"></span>
                <?php esc_html_e('Reset Everything', $text_domain); ?>
            </a>
        </p>
    </div>
</div>