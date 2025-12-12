<?php
if (!defined('ABSPATH')) {
    exit;
}

function blockforce_wp_activate()
{
    if (!get_option('blockforce_settings')) {
        $default_settings = array(
            'attempt_limit' => 2,
            'block_time' => 120,
            'log_time' => 7200,
            'enable_url_change' => 1,
            'enable_ip_blocking' => 1,
            'alert_email' => '',
        );
        update_option('blockforce_settings', $default_settings);
    }
    if (get_option('blockforce_login_slug', null) === null) {
        update_option('blockforce_login_slug', '');
    }

    if (!wp_next_scheduled('blockforce_cleanup')) {
        wp_schedule_event(time(), 'hourly', 'blockforce_cleanup');
    }

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

    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'bfwp_blocked_%'");

    $table_name = $wpdb->prefix . 'blockforce_logs';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");

    wp_clear_scheduled_hook('blockforce_cleanup');
    blockforce_wp_clear_all_transients();
    flush_rewrite_rules();
}

function blockforce_wp_clear_all_transients()
{
    global $wpdb;

    $patterns = array(
        $wpdb->esc_like('_transient_bfwp_blocked_') . '%',
        $wpdb->esc_like('_transient_timeout_bfwp_blocked_') . '%',
        $wpdb->esc_like('_transient_bfwp_attempts_') . '%',
        $wpdb->esc_like('_transient_timeout_bfwp_attempts_') . '%'
    );

    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE %s 
                OR option_name LIKE %s 
                OR option_name LIKE %s 
                OR option_name LIKE %s",
            $patterns[0],
            $patterns[1],
            $patterns[2],
            $patterns[3]
        )
    );

    if (is_multisite()) {
        $site_patterns = array(
            $wpdb->esc_like('_site_transient_bfwp_blocked_') . '%',
            $wpdb->esc_like('_site_transient_timeout_bfwp_blocked_') . '%',
            $wpdb->esc_like('_site_transient_bfwp_attempts_') . '%',
            $wpdb->esc_like('_site_transient_timeout_bfwp_attempts_') . '%'
        );

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->sitemeta} 
                 WHERE meta_key LIKE %s 
                    OR meta_key LIKE %s 
                    OR meta_key LIKE %s 
                    OR meta_key LIKE %s",
                $site_patterns[0],
                $site_patterns[1],
                $site_patterns[2],
                $site_patterns[3]
            )
        );
    }
}
