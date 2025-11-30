<?php

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
        'enable_url_change' => 1,
        'enable_ip_blocking' => 1,
        'alert_email' => '',
    );

    public function __construct($basename)
    {
        $this->settings = get_option('blockforce_settings', $this->default_settings);
        $this->basename = $basename;

        // Load modules
        $this->security = new BlockForce_WP_Security($this->settings, $this);
        $this->login_url = new BlockForce_WP_Login_Url($this->settings, $this);
        $this->admin = new BlockForce_WP_Admin($this->settings, $this);
        $this->features = new BlockForce_WP_Features($this->settings, $this);
        $this->dashboard = new BlockForce_WP_Dashboard($this->settings, $this);
        $this->health_check = new BlockForce_WP_Health_Check($this->settings, $this);

        // Initialize hooks for each module
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
        $table_name = $wpdb->prefix . 'blockforce_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_login varchar(60) NOT NULL,
            user_ip varchar(100) NOT NULL,
            time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            status varchar(20) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
