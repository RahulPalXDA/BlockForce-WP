<?php

if (!defined('ABSPATH')) {
    exit;
}

class BlockForce_WP {

    public $settings;
    public $security;
    public $login_url;
    public $admin;
    public $features;
    public $basename;

    private $default_settings = array(
        'attempt_limit' => 2,
        'block_time' => 120,
        'log_time' => 7200,
        'enable_url_change' => 1,
        'enable_ip_blocking' => 1, 
    );
    
    public function __construct($basename) {
        $this->settings = get_option('blockforce_settings', $this->default_settings);
        $this->basename = $basename;
        
        // Load modules
        $this->security   = new BlockForce_WP_Security($this->settings, $this);
        $this->login_url  = new BlockForce_WP_Login_Url($this->settings, $this);
        $this->admin      = new BlockForce_WP_Admin($this->settings, $this);
        $this->features   = new BlockForce_WP_Features($this->settings, $this);
        
        // Initialize hooks for each module
        $this->security->init_hooks();
        $this->login_url->init_hooks();
        $this->admin->init_hooks();
        $this->features->init_hooks();
    }
}