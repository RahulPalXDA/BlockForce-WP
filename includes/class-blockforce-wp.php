<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class BlockForce_WP
{
    public $settings;
    public $security;
    public $login_url;
    public $admin;
    public $features;
    public $dashboard;
    public $health_check;
    public $basename;

    private $default_settings = array(
        'attempt_limit' => 3,
        'block_time' => 3600,
        'log_time' => 2592000,
        'log_retention_days' => 30,
        'enable_url_change' => 1,
        'enable_ip_blocking' => 1,
        'disable_debug_logs' => 1,
        'alert_email' => '',
    );

    public function __construct($basename)
    {
        $this->settings = get_option('blockforce_settings', $this->default_settings);
        $this->basename = $basename;

        // Strictly Disable Debug Logs if enabled (default: true)
        if (isset($this->settings['disable_debug_logs']) && $this->settings['disable_debug_logs']) {
            error_reporting(0);
            @ini_set('display_errors', '0');
            @ini_set('log_errors', '0');
            @ini_set('error_log', '/dev/null');
        }

        $this->security = new BlockForce_WP_Security($this->settings, $this);
        $this->login_url = new BlockForce_WP_Login_Url($this->settings, $this);
        $this->admin = new BlockForce_WP_Admin($this->settings, $this);
        $this->features = new BlockForce_WP_Features($this->settings, $this);
        $this->dashboard = new BlockForce_WP_Dashboard($this->settings, $this);
        $this->health_check = new BlockForce_WP_Health_Check($this->settings, $this);

        $this->security->init_hooks();
        $this->login_url->init_hooks();
        $this->admin->init_hooks();
        $this->features->init_hooks();
        $this->dashboard->init_hooks();
        $this->health_check->init_hooks();
    }

    public static function activate()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . BFWP_LOGS_TABLE;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_login varchar(60) NOT NULL,
            user_ip varchar(100) NOT NULL,
            time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            status varchar(20) NOT NULL,
            PRIMARY KEY  (id),
            KEY status_time_index (status, time),
            KEY time_index (time)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        $table_blocks = $wpdb->prefix . BFWP_BLOCKS_TABLE;
        $sql_blocks = "CREATE TABLE $table_blocks (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_ip varchar(100) NOT NULL,
            blocked_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            expires_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            reason varchar(255) DEFAULT '',
            PRIMARY KEY  (id),
            KEY ip_index (user_ip),
            KEY expires_index (expires_at)
        ) $charset_collate;";
        dbDelta($sql_blocks);

        // Call global activation function for settings and cron
        if (function_exists('blockforce_wp_activate')) {
            blockforce_wp_activate();
        }
    }
}
