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
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            if (filter_var($line, FILTER_VALIDATE_IP)) {
                $ips_to_add[] = $line;
            }
        }

        if (!empty($ips_to_add)) {
            $this->save_auto_ips_to_db($ips_to_add);
        }

        // Update sync status
        update_option('blockforce_blocklist_last_sync', current_time('mysql'));
        update_option('blockforce_blocklist_count', count($ips_to_add));
    }

    /**
     * Save auto-fetched IPs to database efficiently, preserving manual entries
     */
    private function save_auto_ips_to_db($ips)
    {
        global $wpdb;

        // Delete only 'auto' source IPs
        $wpdb->query("DELETE FROM {$this->table_name} WHERE source = 'auto'");

        // Bulk insert
        $values = array();

        // Insert in chunks of 500
        $chunk_size = 500;
        $chunks = array_chunk($ips, $chunk_size);

        foreach ($chunks as $chunk) {
            $values_sql = array();
            foreach ($chunk as $ip) {
                // Prepared statement for each value pair
                $values_sql[] = $wpdb->prepare("(%s, 'auto')", $ip);
            }

            if (!empty($values_sql)) {
                $sql = "INSERT IGNORE INTO {$this->table_name} (ip, source) VALUES " . implode(',', $values_sql);
                $wpdb->query($sql);
            }
        }

        error_log('BlockForce WP: Global blocklist updated with ' . count($ips) . ' IPs.');
    }

    /**
     * Add a manual IP to the blocklist
     */
    public function add_manual_ip($ip)
    {
        global $wpdb;

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return new WP_Error('invalid_ip', __('Invalid IP address.', 'blockforce-wp'));
        }

        // Check availability
        if ($this->is_ip_in_blocklist($ip)) {
            return new WP_Error('duplicate_ip', __('IP already in blocklist.', 'blockforce-wp'));
        }

        $result = $wpdb->insert(
            $this->table_name,
            array(
                'ip' => $ip,
                'source' => 'manual',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Database error.', 'blockforce-wp'));
        }

        return true;
    }

    /**
     * Delete a manual IP from the blocklist
     */
    public function delete_manual_ip($id)
    {
        global $wpdb;

        // Only allow deleting manual entries for safety via this method
        // (Auto entries are managed by sync)
        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE id = %d AND source = 'manual'",
                $id
            )
        );

        return $result !== false;
    }

    /**
     * Get IPs for the admin table with pagination and search
     */
    public function get_ips($args = array())
    {
        global $wpdb;

        $defaults = array(
            'limit' => 20,
            'offset' => 0,
            'search' => '',
            'source' => '', // 'manual', 'auto', or empty for all
            'orderby' => 'created_at',
            'order' => 'DESC'
        );

        $args = wp_parse_args($args, $defaults);

        // Sanitize
        $limit = absint($args['limit']);
        $offset = absint($args['offset']);
        $orderby = in_array($args['orderby'], array('ip', 'source', 'created_at')) ? $args['orderby'] : 'created_at';
        $order = $args['order'] === 'ASC' ? 'ASC' : 'DESC';

        $where = "1=1";

        // Source filter
        if (!empty($args['source']) && in_array($args['source'], array('manual', 'auto'))) {
            $where .= $wpdb->prepare(" AND source = %s", $args['source']);
        }

        if (!empty($args['search'])) {
            $like = '%' . $wpdb->esc_like($args['search']) . '%';
            $where .= $wpdb->prepare(" AND ip LIKE %s", $like);
        }

        // Get total count
        $total = $wpdb->get_var("SELECT COUNT(id) FROM {$this->table_name} WHERE $where");

        // Get items
        $sql = "SELECT * FROM {$this->table_name} WHERE $where ORDER BY $orderby $order LIMIT $limit OFFSET $offset";
        $items = $wpdb->get_results($sql, ARRAY_A);

        return array(
            'items' => $items,
            'total' => $total,
            'pages' => ceil($total / $limit)
        );
    }

    /**
     * Get last sync status
     */
    public function get_sync_status()
    {
        return array(
            'last_sync' => get_option('blockforce_blocklist_last_sync', __('Never', 'blockforce-wp')),
            'count' => get_option('blockforce_blocklist_count', 0)
        );
    }
}
