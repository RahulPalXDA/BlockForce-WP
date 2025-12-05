<?php

if (!defined('ABSPATH')) {
    exit;
}

class BlockForce_WP_Blocklist
{
    private $settings;
    private $core;
    private $table_name;

    public function __construct($settings, $core)
    {
        global $wpdb;
        $this->settings = $settings;
        $this->core = $core;
        $this->table_name = $wpdb->prefix . 'bfwp_blocklist';
    }

    public function init_hooks()
    {
        // Add cron scheduling
        add_action('blockforce_daily_blocklist_update', array($this, 'update_blocklist'));

        // Check IP on login page init
        add_action('login_init', array($this, 'check_ip_access'));

        // Also check on admin init to protect /wp-admin
        add_action('admin_init', array($this, 'check_ip_access'));
    }

    /**
     * Check if the current user's IP is in the blocklist
     */
    public function check_ip_access()
    {
        // Check if feature is enabled
        if (!isset($this->settings['enable_global_blocklist']) || !$this->settings['enable_global_blocklist']) {
            return;
        }

        // Don't block logged-in admins
        if (is_user_logged_in() && current_user_can('manage_options')) {
            return;
        }

        $user_ip = BlockForce_WP_Utils::get_user_ip();

        if ($this->is_ip_in_blocklist($user_ip)) {
            // Block access
            wp_die(
                __('Access Denied: Your IP address is listed in our global security blocklist.', 'blockforce-wp'),
                __('Access Denied', 'blockforce-wp'),
                array('response' => 403)
            );
        }
    }

    /**
     * Check database for IP existence
     */
    private function is_ip_in_blocklist($ip)
    {
        global $wpdb;

        // Basic optimization: if table doesn't exist, return false
        if ($wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") != $this->table_name) {
            return false;
        }

        // Prepare query
        $query = $wpdb->prepare("SELECT id FROM {$this->table_name} WHERE ip = %s LIMIT 1", $ip);
        $result = $wpdb->get_var($query);

        return !empty($result);
    }

    /**
     * Fetch and update the blocklist
     */
    public function update_blocklist()
    {
        // Source: FireHol Level 1 (Top malicious IPs)
        $source_url = 'https://iplists.firehol.org/files/firehol_level1.netset';

        $response = wp_remote_get($source_url, array('timeout' => 30));

        if (is_wp_error($response)) {
            error_log('BlockForce WP: Failed to download blocklist. ' . $response->get_error_message());
            return;
        }

        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return;
        }

        // Parse IPs
        $lines = explode("\n", $body);
        $ips_to_add = array();

        foreach ($lines as $line) {
            // Skip comments and CIDR notation (for simplicity, we only block single IPs for now or would need a CIDR matcher)
            // FireHol Level 1 contains mostly single IPs and CIDRs
            // For this basic MVP implementation, we will only extract single IPs to keep SQL simple
            // Real implementation should handle CIDR.

            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            // If it's a CIDR, we skip for MVP (complex math required for SQL lookup) or exact match
            // Creating a robust CIDR matcher in pure SQL/PHP is heavy.
            // Let's filter for just plain IPs for minimal risk 
            if (filter_var($line, FILTER_VALIDATE_IP)) {
                $ips_to_add[] = $line;
            }
        }

        if (!empty($ips_to_add)) {
            $this->save_ips_to_db($ips_to_add);
        }
    }

    /**
     * Save IPs to database efficiently
     */
    private function save_ips_to_db($ips)
    {
        global $wpdb;

        // Truncate table first - we want a fresh list
        $wpdb->query("TRUNCATE TABLE {$this->table_name}");

        // Bulk insert
        $values = array();
        $placeholders = array();

        // Insert in chunks of 500
        $chunk_size = 500;
        $chunks = array_chunk($ips, $chunk_size);

        foreach ($chunks as $chunk) {
            $values_sql = array();
            foreach ($chunk as $ip) {
                $values_sql[] = $wpdb->prepare("(%s)", $ip);
            }

            if (!empty($values_sql)) {
                $sql = "INSERT IGNORE INTO {$this->table_name} (ip) VALUES " . implode(',', $values_sql);
                $wpdb->query($sql);
            }
        }

        error_log('BlockForce WP: Global blocklist updated with ' . count($ips) . ' IPs.');
    }
}
