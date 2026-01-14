<?php
declare(strict_types=1);
if (!defined('ABSPATH'))
    exit;
if (!defined('BFWP_LOGS_TABLE'))
    define('BFWP_LOGS_TABLE', 'blockforce_logs');
if (!defined('BFWP_BLOCKS_TABLE'))
    define('BFWP_BLOCKS_TABLE', 'blockforce_blocks');
function blockforce_wp_activate()
{
    if (!get_option('blockforce_settings'))
        update_option('blockforce_settings', array('attempt_limit' => 2, 'block_time' => 120, 'log_time' => 7200, 'log_retention_days' => 30, 'enable_url_change' => 1, 'enable_ip_blocking' => 1, 'disable_debug_logs' => 1, 'alert_email' => ''));
    if (get_option('blockforce_login_slug', null) === null)
        update_option('blockforce_login_slug', '');
    if (!wp_next_scheduled('blockforce_cleanup'))
        wp_schedule_event(time(), 'hourly', 'blockforce_cleanup');
    flush_rewrite_rules(false);
}
function blockforce_wp_deactivate()
{
    wp_clear_scheduled_hook('blockforce_cleanup');
    flush_rewrite_rules();
}
function blockforce_wp_uninstall_cleanup()
{
    global $wpdb;
    delete_option('blockforce_settings');
    delete_option('blockforce_login_slug');
    delete_option('blockforce_attempts');
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}" . BFWP_LOGS_TABLE);
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}" . BFWP_BLOCKS_TABLE);
    wp_clear_scheduled_hook('blockforce_cleanup');
    blockforce_wp_clear_all_transients();
    flush_rewrite_rules();
}
function blockforce_wp_clear_all_transients()
{
    global $wpdb;
    $l = array($wpdb->esc_like('_transient_bfwp_attempts_') . '%', $wpdb->esc_like('_transient_timeout_bfwp_attempts_') . '%');
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $l[0], $l[1]));
    if (is_multisite()) {
        $sl = array($wpdb->esc_like('_site_transient_bfwp_attempts_') . '%', $wpdb->esc_like('_site_transient_timeout_bfwp_attempts_') . '%');
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s OR meta_key LIKE %s", $sl[0], $sl[1]));
    }
}
