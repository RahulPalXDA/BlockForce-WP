<?php
/**
 * Fired when the plugin is uninstalled.
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Include the helper functions
require_once plugin_dir_path(__FILE__) . 'includes/functions.php';

// Call the main cleanup function
blockforce_wp_uninstall_cleanup();