<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cleanup on plugin activation
 */
function blockforce_wp_activate()
{
    // Set default options if they don't exist
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

    // Schedule cron
    if (!wp_next_scheduled('blockforce_cleanup')) {
        wp_schedule_event(time(), 'hourly', 'blockforce_cleanup');
    }

    // Flush rewrite rules
    flush_rewrite_rules(false);
}

/**
 * Cleanup on plugin deactivation
 */
function blockforce_wp_deactivate()
{
    // Clear scheduled events
    wp_clear_scheduled_hook('blockforce_cleanup');

    // Remove custom rewrite rules
    flush_rewrite_rules();
}

/**
 * Complete plugin cleanup on uninstall
 */
function blockforce_wp_uninstall_cleanup()
{
    global $wpdb;

    // Remove all options
    delete_option('blockforce_settings');
    delete_option('blockforce_login_slug');
    delete_option('blockforce_attempts');

    // Remove persistent block history (options starting with bfwp_blocked_)
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'bfwp_blocked_%'");

    // Drop custom logs table
    $table_name = $wpdb->prefix . 'blockforce_logs';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");

    // Clear scheduled events
    wp_clear_scheduled_hook('blockforce_cleanup');

    // Clear all transients
    blockforce_wp_clear_all_transients();

    // Flush rewrite rules to remove custom login rules
    flush_rewrite_rules();
}


/**
 * Clear all BlockForce WP transients from database
 * Optimized to use efficient single queries for better performance
 */
function blockforce_wp_clear_all_transients()
{
    global $wpdb;

    // Prepare the LIKE patterns for transient cleanup
    $patterns = array(
        $wpdb->esc_like('_transient_bfwp_blocked_') . '%',
        $wpdb->esc_like('_transient_timeout_bfwp_blocked_') . '%',
        $wpdb->esc_like('_transient_bfwp_attempts_') . '%',
        $wpdb->esc_like('_transient_timeout_bfwp_attempts_') . '%'
    );

    // Single optimized query for regular transients
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

    // Handle multisite transients
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