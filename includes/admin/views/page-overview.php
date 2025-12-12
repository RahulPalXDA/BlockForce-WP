<?php
if (!defined('ABSPATH')) {
    exit;
}

$settings = $args['settings'];
$core = $args['core'];
$text_domain = $args['text_domain'];

$current_slug = $core->login_url->get_login_slug();
$site_url = get_site_url();
?>
<div class="wrap blockforce-wrap">
    <h1><span class="dashicons dashicons-privacy"></span> <?php esc_html_e('BlockForce WP', $text_domain); ?></h1>

    <?php settings_errors('blockforce_messages'); ?>

    <div class="blockforce-card">
        <h2><?php esc_html_e('Current Login Status', $text_domain); ?></h2>
        <div
            class="blockforce-status-box <?php echo $current_slug ? 'blockforce-status-active' : 'blockforce-status-default'; ?>">
            <?php if ($current_slug): ?>
                <p class="blockforce-mb-10 blockforce-mt-0">
                    <span class="blockforce-status-indicator blockforce-status-indicator--active">●</span>
                    <strong
                        class="blockforce-status-title"><?php esc_html_e('Secret Login URL is ACTIVE', $text_domain); ?></strong>
                </p>
                <p class="blockforce-mb-0 blockforce-mt-0">
                    <strong><?php esc_html_e('Your login page:', $text_domain); ?></strong></p>
                <div class="blockforce-url-display"><?php echo esc_url($site_url . '/' . $current_slug); ?></div>
                <p class="blockforce-mt-15 blockforce-text-muted">
                    <span class="dashicons dashicons-info"></span>
                    <?php esc_html_e('Bookmark this URL! The default wp-login.php is disabled.', $text_domain); ?>
                </p>
            <?php else: ?>
                <p class="blockforce-mb-10 blockforce-mt-0">
                    <span class="blockforce-status-indicator blockforce-status-indicator--default">●</span>
                    <strong
                        class="blockforce-status-title"><?php esc_html_e('Default Login URL Active', $text_domain); ?></strong>
                </p>
                <p class="blockforce-mb-0 blockforce-mt-0">
                    <strong><?php esc_html_e('Your login page:', $text_domain); ?></strong></p>
                <div class="blockforce-url-display"><?php echo esc_url($site_url . '/wp-login.php'); ?></div>
                <p class="blockforce-mt-15 blockforce-text-muted">
                    <span class="dashicons dashicons-info"></span>
                    <?php esc_html_e('URL will auto-change on attack detection (if enabled).', $text_domain); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <div class="blockforce-card blockforce-card--mt">
        <h2><?php esc_html_e('Blocked IP Addresses', $text_domain); ?></h2>
        <?php
        global $wpdb;
        $blocked_ips = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name NOT LIKE %s",
                $wpdb->esc_like('bfwp_blocked_') . '%',
                $wpdb->esc_like('_transient_') . '%'
            )
        );

        if (!empty($blocked_ips)): ?>
            <form method="post" action="">
                <?php wp_nonce_field('blockforce_bulk_unblock', '_wpnonce_bulk'); ?>
                <div class="blockforce-bulk-actions">
                    <select name="blockforce_bulk_action">
                        <option value=""><?php esc_html_e('Bulk Actions', $text_domain); ?></option>
                        <option value="unblock"><?php esc_html_e('Unblock Selected', $text_domain); ?></option>
                    </select>
                    <button type="submit" class="button"><?php esc_html_e('Apply', $text_domain); ?></button>
                </div>
                <table class="blockforce-ips-table">
                    <thead>
                        <tr>
                            <th class="blockforce-col-checkbox"><input type="checkbox" id="select-all-ips"></th>
                            <th><?php esc_html_e('IP Address', $text_domain); ?></th>
                            <th><?php esc_html_e('Blocked Since', $text_domain); ?></th>
                            <th><?php esc_html_e('Status', $text_domain); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($blocked_ips as $blocked):
                            $ip = str_replace('bfwp_blocked_', '', $blocked->option_name);
                            $parts = explode('|', $blocked->option_value);
                            $blocked_time = intval($parts[0]);
                            $expires_at = count($parts) === 2 ? intval($parts[1]) : 0;
                            $is_active = $expires_at ? time() < $expires_at : true;
                            ?>
                            <tr>
                                <td><input type="checkbox" name="blocked_ips[]" value="<?php echo esc_attr($ip); ?>"></td>
                                <td><strong><?php echo esc_html($ip); ?></strong></td>
                                <td><?php echo esc_html(human_time_diff($blocked_time, time()) . ' ago'); ?></td>
                                <td>
                                    <?php if ($is_active): ?>
                                        <span
                                            class="blockforce-badge blockforce-badge-enabled"><?php esc_html_e('Active', $text_domain); ?></span>
                                        <?php if ($expires_at): ?>
                                            <span
                                                class="blockforce-time-left">(<?php echo esc_html(human_time_diff(time(), $expires_at)); ?>
                                                left)</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span
                                            class="blockforce-badge blockforce-badge-disabled"><?php esc_html_e('Expired', $text_domain); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
            <script>
                document.getElementById('select-all-ips').addEventListener('change', function () {
                    document.querySelectorAll('input[name="blocked_ips[]"]').forEach(cb => cb.checked = this.checked);
                });
            </script>
        <?php else: ?>
            <p class="blockforce-text-muted">
                <?php esc_html_e('No IP addresses are currently blocked. Your site is monitoring for attacks.', $text_domain); ?>
            </p>
        <?php endif; ?>
    </div>
</div>