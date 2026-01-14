<?php
declare(strict_types=1);
if (!defined('WP_UNINSTALL_PLUGIN'))
    exit;
require_once plugin_dir_path(__FILE__) . 'includes/functions.php';
blockforce_wp_uninstall_cleanup();