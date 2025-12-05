<?php
/**
 * Reset Tab Display
 *
 * @package BlockForce_WP
 * @subpackage Admin\Tabs
 */

if (!defined('ABSPATH')) {
    exit;
}

class BlockForce_WP_Tab_Reset
{
    private $text_domain = 'blockforce-wp';

    /**
     * Render the Reset tab
     */
    public function render()
    {
        $reset_url = wp_nonce_url(
            admin_url('options-general.php?page=blockforce-wp&tab=reset&blockforce_reset=1'),
            'blockforce_reset_nonce',
            '_wpnonce_reset'
        );
        ?>
        <div class="blockforce-card">
            <h2><?php esc_html_e('Reset & Maintenance Tools', $this->text_domain); ?></h2>
            <p><?php esc_html_e('Reset the plugin to its initial state. This will clear all blocks and restore the default login URL.', $this->text_domain); ?>
            </p>

            <?php settings_errors('blockforce_reset'); ?>

            <!-- Reset Login URL Only Section -->
            <div class="blockforce-card" style="border-left: 4px solid #0073aa; margin-top: 20px;">
                <h3 style="margin-top: 0;"><?php esc_html_e('Reset Login URL Only', $this->text_domain); ?></h3>
                <p><?php esc_html_e('Use this if you forgot your custom login URL or want to revert to the default.', $this->text_domain); ?>
                </p>
                <p>
                    <a href="<?php echo esc_url(wp_nonce_url(
                        admin_url('options-general.php?page=blockforce-wp&tab=reset&blockforce_reset_url=1'),
                        'blockforce_reset_url_nonce',
                        '_wpnonce_reset_url'
                    )); ?>" class="button button-secondary">
                        <?php esc_html_e('Reset Login URL to Default', $this->text_domain); ?>
                    </a>
                </p>
                <p class="description">
                    <?php esc_html_e('This will only change the login URL. Logic logs and blocked IPs will remain.', $this->text_domain); ?>
                </p>
            </div>

            <div class="blockforce-warning-box" style="margin: 20px 0;">
                <p style="margin: 0;"><strong><?php esc_html_e('Warning:', $this->text_domain); ?></strong>
                    <?php esc_html_e('This action performs a partial reset of the plugin\'s security data. It will:', $this->text_domain); ?>
                </p>
                <ul style="list-style-type: disc; margin-left: 20px; margin-top: 5px;">
                    <li><?php esc_html_e('Clear all blocked IP addresses (both temporary and permanent).', $this->text_domain); ?>
                    </li>
                    <li><?php esc_html_e('Delete all login activity logs from the database.', $this->text_domain); ?></li>
                    <li><?php esc_html_e('Reset the custom login URL back to the default wp-login.php.', $this->text_domain); ?>
                    </li>
                </ul>
                <p style="margin-top: 10px; margin-bottom: 0;">
                    <strong><?php esc_html_e('Note:', $this->text_domain); ?></strong>
                    <?php esc_html_e('Your configuration settings (e.g., attempt limits, email alerts) will NOT be changed. To fully uninstall the plugin and remove all data, please deactivate and delete it from the Plugins page.', $this->text_domain); ?>
                </p>
            </div>

            <p>
                <a href="<?php echo esc_url($reset_url); ?>"
                    onclick="return confirm('<?php echo esc_js(__('Are you sure? This will clear all security data and reset the login URL.', $this->text_domain)); ?>')"
                    class="button button-secondary button-large"
                    style="background-color: #d63638; color: #fff; border-color: #d63638;">
                    <span class="dashicons dashicons-update" style="margin-top: 3px;"></span>
                    <?php esc_html_e('Reset Plugin', $this->text_domain); ?>
                </a>
            </p>
        </div>
        <?php
    }
}
