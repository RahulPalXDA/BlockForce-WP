<?php
/**
 * Plugin Name: BlockForce WP
 * Description: Minimal, enhanced login security with IP blocking, automatic URL change, and minimal plain-text alerts.
 * Version: 1.0 (Initial)
 * Author: RahulPalXDA
 * Text Domain: blockforce-wp
 */

if (!defined('ABSPATH')) {
    exit;
}

define('BFWP_PATH', plugin_dir_path(__FILE__));
define('BFWP_TEXT_DOMAIN', 'blockforce-wp');
define('BFWP_BASENAME', plugin_basename(__FILE__));

// Include helper functions for activation/deactivation
require_once BFWP_PATH . 'includes/functions.php';

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'blockforce_wp_activate');
register_deactivation_hook(__FILE__, 'blockforce_wp_deactivate');

// Include all class files
require_once BFWP_PATH . 'includes/class-blockforce-wp-utils.php';
require_once BFWP_PATH . 'includes/class-blockforce-wp-security.php';
require_once BFWP_PATH . 'includes/class-blockforce-wp-login-url.php';
require_once BFWP_PATH . 'includes/class-blockforce-wp-admin.php';
require_once BFWP_PATH . 'includes/class-blockforce-wp-features.php';
require_once BFWP_PATH . 'includes/class-blockforce-wp.php';

// Initialize the main plugin controller
function blockforce_wp_run() {
    new BlockForce_WP(BFWP_BASENAME);
}
add_action('plugins_loaded', 'blockforce_wp_run');