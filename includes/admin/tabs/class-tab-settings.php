<?php
/**
 * Settings Tab Display
 *
 * @package BlockForce_WP
 * @subpackage Admin\Tabs
 */

if (!defined('ABSPATH')) {
    exit;
}

class BlockForce_WP_Tab_Settings
{
    private $text_domain = 'blockforce-wp';

    /**
     * Render the Settings tab
     */
    public function render()
    {
        ?>
        <div class="blockforce-card">
            <h2><?php esc_html_e('⚙️ Security Configuration', $this->text_domain); ?></h2>
            <p><?php esc_html_e('Customize how BlockForce WP protects your WordPress site. Hover over the help icons for detailed explanations.', $this->text_domain); ?>
            </p>

            <form action="options.php" method="post">
                <?php
                settings_fields('blockforce_settings');
                do_settings_sections('blockforce_settings');
                submit_button(__('Save Settings', $this->text_domain), 'primary large');
                ?>
            </form>
        </div>
        <?php
    }
}
