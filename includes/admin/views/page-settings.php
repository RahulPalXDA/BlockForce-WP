<?php
if (!defined('ABSPATH')) {
    exit;
}

$text_domain = $args['text_domain'];
?>
<div class="wrap blockforce-wrap">
    <h1><span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e('Settings', $text_domain); ?></h1>

    <?php settings_errors('blockforce_messages'); ?>
    <?php settings_errors('blockforce_settings'); ?>

    <div class="blockforce-card">
        <form action="options.php" method="post">
            <?php
            settings_fields('blockforce_settings');
            do_settings_sections('blockforce_settings');
            submit_button(__('Save Settings', $text_domain), 'primary large');
            ?>
        </form>
    </div>
</div>