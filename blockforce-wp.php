<?php
/**
 * Plugin Name: BlockForce WP
 * Plugin URI: https://github.com/RahulPalXDA/BlockForce-WP
 * Description: Minimal, enhanced login security with IP blocking, automatic URL change, and email alerts. Protects your WordPress site from brute-force attacks.
 * Version: 1.1.0
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Author: RahulPalXDA
 * Author URI: https://github.com/RahulPalXDA
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: blockforce-wp
 * 
 * @package BlockForce_WP
 * @author RahulPalXDA
 * @copyright 2024 RahulPalXDA
 * @license GPL-2.0+
 */

if (!defined('ABSPATH')) {
    exit;
}

define('BFWP_VERSION', '1.1.0');
define('BFWP_PATH', plugin_dir_path(__FILE__));
define('BFWP_URL', plugin_dir_url(__FILE__));
define('BFWP_TEXT_DOMAIN', 'blockforce-wp');
define('BFWP_BASENAME', plugin_basename(__FILE__));

// Include helper functions for activation/deactivation
require_once BFWP_PATH . 'includes/functions.php';

// Register activation and deactivation hooks
register_activation_hook(__FILE__, array('BlockForce_WP', 'activate'));
register_deactivation_hook(__FILE__, 'blockforce_wp_deactivate');

/**
 * Autoload plugin classes
 */
function blockforce_wp_autoload()
{
    // Utils (load first as other classes depend on it)
    require_once BFWP_PATH . 'includes/utils/class-utils.php';

    // Core security modules
    require_once BFWP_PATH . 'includes/core/class-security.php';
    require_once BFWP_PATH . 'includes/core/class-login-url.php';
    require_once BFWP_PATH . 'includes/core/class-blocklist.php';

    // Features module (legacy location)
    require_once BFWP_PATH . 'includes/class-blockforce-wp-features.php';

    // Admin modules
    require_once BFWP_PATH . 'includes/admin/class-settings.php';
    require_once BFWP_PATH . 'includes/admin/tabs/class-tab-overview.php';
    require_once BFWP_PATH . 'includes/admin/tabs/class-tab-logs.php';
    require_once BFWP_PATH . 'includes/admin/tabs/class-tab-settings.php';
    require_once BFWP_PATH . 'includes/admin/tabs/class-tab-reset.php';
    require_once BFWP_PATH . 'includes/admin/tabs/class-tab-blocklist.php';
    require_once BFWP_PATH . 'includes/admin/class-admin.php';

    // Other modules
    require_once BFWP_PATH . 'includes/dashboard/class-dashboard.php';
    require_once BFWP_PATH . 'includes/health-check/class-health-check.php';

    // Main plugin controller (must be last)
    require_once BFWP_PATH . 'includes/class-blockforce-wp.php';
}

// Initialize the main plugin controller
function blockforce_wp_run()
{
    blockforce_wp_autoload();
    new BlockForce_WP(BFWP_BASENAME);
}
add_action('plugins_loaded', 'blockforce_wp_run');