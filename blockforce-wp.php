<?php
/**
 * Plugin Name: BlockForce WP
 * Description: Login security with IP blocking and automatic URL change.
 * Version: 1.1.0
 * Author: RahulPalXDA
 * License: GPLv2 or later
 * Text Domain: blockforce-wp
 */

if (!defined('ABSPATH')) {
    exit;
}

define('BFWP_VERSION', '1.1.0');
define('BFWP_PATH', plugin_dir_path(__FILE__));
define('BFWP_URL', plugin_dir_url(__FILE__));
define('BFWP_TEXT_DOMAIN', 'blockforce-wp');
define('BFWP_BASENAME', plugin_basename(__FILE__));

require_once BFWP_PATH . 'includes/functions.php';

register_activation_hook(__FILE__, array('BlockForce_WP', 'activate'));
register_deactivation_hook(__FILE__, 'blockforce_wp_deactivate');

function blockforce_wp_autoload()
{
    require_once BFWP_PATH . 'includes/utils/class-utils.php';
    require_once BFWP_PATH . 'includes/core/class-security.php';
    require_once BFWP_PATH . 'includes/core/class-login-url.php';
    require_once BFWP_PATH . 'includes/core/class-features.php';
    require_once BFWP_PATH . 'includes/admin/class-settings.php';
    require_once BFWP_PATH . 'includes/admin/class-admin.php';
    require_once BFWP_PATH . 'includes/dashboard/class-dashboard.php';
    require_once BFWP_PATH . 'includes/health-check/class-health-check.php';
    require_once BFWP_PATH . 'includes/class-blockforce-wp.php';
}

function blockforce_wp_run()
{
    blockforce_wp_autoload();
    new BlockForce_WP(BFWP_BASENAME);
}
add_action('plugins_loaded', 'blockforce_wp_run');