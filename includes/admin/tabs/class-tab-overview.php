<?php
/**
 * Overview Tab Display
 *
 * @package BlockForce_WP
 * @subpackage Admin\Tabs
 */

if (!defined('ABSPATH')) {
    exit;
}

class BlockForce_WP_Tab_Overview
{
    private $settings;
    private $core;
    private $text_domain = 'blockforce-wp';

    public function __construct($settings, $core)
    {
        $this->settings = $settings;
        $this->core = $core;
    }

    /**
     * Render the Overview tab
     */
    public function render()
    {
        global $wpdb;
        $login_slug = get_option('blockforce_login_slug', '');
        $login_url = $login_slug ? site_url($login_slug) : wp_login_url();
        $is_custom_url = !empty($login_slug);

        // Get blocked IPs from options
        $blocked_ips = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
                $wpdb->esc_like('bfwp_blocked_') . '%'
            )
        );
        ?>
        <div class="blockforce-card">
            <h2><?php esc_html_e('🛡️ Security Overview', $this->text_domain); ?></h2>

            <div class="blockforce-info-grid">
                <div class="blockforce-info-item">
                    <h3><?php esc_html_e('Login URL Status', $this->text_domain); ?></h3>
                    <?php if ($is_custom_url): ?>
                        <p>
                            <span
                                class="blockforce-badge blockforce-badge-enabled"><?php esc_html_e('PROTECTED', $this->text_domain); ?></span>
                        </p>
                        <p><?php esc_html_e('Current Login URL:', $this->text_domain); ?></p>
                        <code class="blockforce-url-display"><?php echo esc_html($login_url); ?></code>
                    <?php else: ?>
                        <p>
                            <span
                                class="blockforce-badge blockforce-badge-disabled"><?php esc_html_e('DEFAULT', $this->text_domain); ?></span>
                        </p>
                        <p><?php esc_html_e('Using standard wp-login.php', $this->text_domain); ?></p>
                    <?php endif; ?>
                </div>

                <div class="blockforce-info-item">
                    <h3><?php esc_html_e('IP Blocking', $this->text_domain); ?></h3>
                    <?php if (!empty($this->settings['enable_ip_blocking'])): ?>
                        <p>
                            <span
                                class="blockforce-badge blockforce-badge-enabled"><?php esc_html_e('ENABLED', $this->text_domain); ?></span>
                        </p>
                        <p><?php echo sprintf(esc_html__('%d IPs currently blocked', $this->text_domain), count($blocked_ips)); ?>
                        </p>
                    <?php else: ?>
                        <p>
                            <span
                                class="blockforce-badge blockforce-badge-disabled"><?php esc_html_e('DISABLED', $this->text_domain); ?></span>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="blockforce-info-item">
                    <h3><?php esc_html_e('Auto URL Change', $this->text_domain); ?></h3>
                    <?php if (!empty($this->settings['enable_url_change'])): ?>
                        <p>
                            <span
                                class="blockforce-badge blockforce-badge-enabled"><?php esc_html_e('ENABLED', $this->text_domain); ?></span>
                        </p>
                    <?php else: ?>
                        <p>
                            <span
                                class="blockforce-badge blockforce-badge-disabled"><?php esc_html_e('DISABLED', $this->text_domain); ?></span>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="blockforce-info-item">
                    <h3><?php esc_html_e('Global Blocklist', $this->text_domain); ?></h3>
                    <?php if (!empty($this->settings['enable_global_blocklist'])): ?>
                        <p>
                            <span
                                class="blockforce-badge blockforce-badge-enabled"><?php esc_html_e('ENABLED', $this->text_domain); ?></span>
                        </p>
                    <?php else: ?>
                        <p>
                            <span
                                class="blockforce-badge blockforce-badge-disabled"><?php esc_html_e('DISABLED', $this->text_domain); ?></span>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php $this->render_blocked_ips_table($blocked_ips); ?>
    <?php
    }

    /**
     * Render blocked IPs table
     */
    private function render_blocked_ips_table($blocked_ips)
    {
        ?>
        <div class="blockforce-card">
            <h2><?php esc_html_e('🚫 Currently Blocked IPs', $this->text_domain); ?></h2>

            <?php settings_errors('blockforce_bulk'); ?>

            <?php if (!empty($blocked_ips)): ?>
                <form method="post" action="">
                    <?php wp_nonce_field('blockforce_bulk_unblock', '_wpnonce_bulk'); ?>

                    <div class="blockforce-bulk-actions">
                        <select name="blockforce_bulk_action">
                            <option value=""><?php esc_html_e('Bulk Actions', $this->text_domain); ?></option>
                            <option value="unblock"><?php esc_html_e('Unblock Selected', $this->text_domain); ?></option>
                        </select>
                        <button type="submit" class="button"><?php esc_html_e('Apply', $this->text_domain); ?></button>
                    </div>

                    <table class="blockforce-ips-table">
                        <thead>
                            <tr>
                                <th style="width: 30px;"><input type="checkbox" id="select-all-ips"></th>
                                <th><?php esc_html_e('IP Address', $this->text_domain); ?></th>
                                <th><?php esc_html_e('Blocked', $this->text_domain); ?></th>
                                <th><?php esc_html_e('Status', $this->text_domain); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($blocked_ips as $blocked):
                                $ip = str_replace('bfwp_blocked_', '', $blocked->option_name);
                                $parts = explode('|', $blocked->option_value);

                                if (count($parts) === 2) {
                                    $blocked_time = intval($parts[0]);
                                    $expires_at = intval($parts[1]);
                                    $is_active = time() < $expires_at;
                                    $time_left = $expires_at - time();
                                } else {
                                    $blocked_time = intval($blocked->option_value);
                                    $is_active = true;
                                    $time_left = 0;
                                }
                                ?>
                                <tr>
                                    <td><input type="checkbox" name="blocked_ips[]" value="<?php echo esc_attr($ip); ?>"></td>
                                    <td><strong><?php echo esc_html($ip); ?></strong></td>
                                    <td><?php echo esc_html(human_time_diff($blocked_time, time()) . ' ago'); ?></td>
                                    <td>
                                        <?php if ($is_active): ?>
                                            <span
                                                class="blockforce-badge blockforce-badge-enabled"><?php esc_html_e('Active', $this->text_domain); ?></span>
                                            <span style="color: #646970; font-size: 12px; margin-left: 5px;">
                                                (<?php echo esc_html(sprintf(__('%s left', $this->text_domain), human_time_diff(time(), $expires_at))); ?>)
                                            </span>
                                        <?php else: ?>
                                            <span
                                                class="blockforce-badge blockforce-badge-disabled"><?php esc_html_e('Expired', $this->text_domain); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <script>
                        document.getElementById('select-all-ips').addEventListener('change', function () {
                            var checkboxes = document.querySelectorAll('input[name="blocked_ips[]"]');
                            checkboxes.forEach(function (checkbox) {
                                checkbox.checked = this.checked;
                            }, this);
                        });
                    </script>
                </form>
            <?php else: ?>
                <p><?php esc_html_e('No IP addresses are currently blocked.', $this->text_domain); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
}
