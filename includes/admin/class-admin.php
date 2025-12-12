<?php
if (!defined('ABSPATH')) {
    exit;
}

class BlockForce_WP_Admin
{
    private $settings;
    private $core;
    private $text_domain = BFWP_TEXT_DOMAIN;
    private $settings_handler;
    private $menu_slug = 'blockforce-wp';

    public function __construct($settings, $core)
    {
        $this->settings = $settings;
        $this->core = $core;
        $this->settings_handler = new BlockForce_WP_Admin_Settings($settings);
    }

    public function init_hooks()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this->settings_handler, 'register'));
        add_action('admin_init', array($this, 'handle_reset_actions'));
        add_action('admin_init', array($this, 'handle_test_email'));
        add_action('admin_init', array($this, 'handle_bulk_unblock'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        add_action('update_option_blockforce_settings', array($this, 'on_settings_update'), 10, 2);
        add_filter('plugin_action_links_' . $this->core->basename, array($this, 'add_settings_link'));
    }

    public function add_settings_link($links)
    {
        $settings_link = '<a href="' . admin_url('admin.php?page=blockforce-wp-settings') . '">' . __('Settings', $this->text_domain) . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public function enqueue_admin_styles($hook)
    {
        $allowed_hooks = array(
            'toplevel_page_blockforce-wp',
            'blockforce-wp_page_blockforce-wp-logs',
            'blockforce-wp_page_blockforce-wp-settings',
            'blockforce-wp_page_blockforce-wp-reset',
        );
        if (!in_array($hook, $allowed_hooks)) {
            return;
        }

        wp_enqueue_style('blockforce-admin', BFWP_URL . 'assets/css/admin.css', array(), BFWP_VERSION);
    }

    public function on_settings_update($old_value, $new_value)
    {
        $this->core->login_url->flush_rewrite_rules();
    }

    public function add_admin_menu()
    {
        add_menu_page(
            __('BlockForce WP', $this->text_domain),
            __('BlockForce WP', $this->text_domain),
            'manage_options',
            $this->menu_slug,
            array($this, 'render_overview_page'),
            'dashicons-privacy',
            80
        );

        add_submenu_page(
            $this->menu_slug,
            __('Overview', $this->text_domain),
            __('Overview', $this->text_domain),
            'manage_options',
            $this->menu_slug,
            array($this, 'render_overview_page')
        );

        add_submenu_page(
            $this->menu_slug,
            __('Activity Log', $this->text_domain),
            __('Activity Log', $this->text_domain),
            'manage_options',
            $this->menu_slug . '-logs',
            array($this, 'render_logs_page')
        );

        add_submenu_page(
            $this->menu_slug,
            __('Settings', $this->text_domain),
            __('Settings', $this->text_domain),
            'manage_options',
            $this->menu_slug . '-settings',
            array($this, 'render_settings_page')
        );

        add_submenu_page(
            $this->menu_slug,
            __('Reset & Tools', $this->text_domain),
            __('Reset & Tools', $this->text_domain),
            'manage_options',
            $this->menu_slug . '-reset',
            array($this, 'render_reset_page')
        );
    }

    private function get_page_args()
    {
        return array(
            'settings' => $this->settings,
            'core' => $this->core,
            'text_domain' => $this->text_domain,
        );
    }

    public function render_overview_page()
    {
        $args = $this->get_page_args();
        include BFWP_PATH . 'includes/admin/views/page-overview.php';
    }

    public function render_logs_page()
    {
        $args = $this->get_page_args();
        include BFWP_PATH . 'includes/admin/views/page-logs.php';
    }

    public function render_settings_page()
    {
        $args = $this->get_page_args();
        include BFWP_PATH . 'includes/admin/views/page-settings.php';
    }

    public function render_reset_page()
    {
        $args = $this->get_page_args();
        include BFWP_PATH . 'includes/admin/views/page-reset.php';
    }

    private function is_our_page()
    {
        if (!is_admin() || !current_user_can('manage_options')) {
            return false;
        }
        $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
        return strpos($page, 'blockforce-wp') === 0;
    }

    public function handle_bulk_unblock()
    {
        if (!$this->is_our_page()) {
            return;
        }

        if (isset($_POST['blockforce_bulk_action']) && $_POST['blockforce_bulk_action'] === 'unblock') {
            if (!wp_verify_nonce(sanitize_key($_POST['_wpnonce_bulk'] ?? ''), 'blockforce_bulk_unblock')) {
                wp_die(__('Security check failed.', $this->text_domain), __('Error', $this->text_domain), array('response' => 403));
            }
            check_admin_referer('blockforce_bulk_unblock', '_wpnonce_bulk');

            if (isset($_POST['blocked_ips']) && is_array($_POST['blocked_ips'])) {
                $count = 0;
                foreach ($_POST['blocked_ips'] as $ip) {
                    delete_option('bfwp_blocked_' . sanitize_text_field($ip));
                    $count++;
                }
                add_settings_error('blockforce_messages', 'bulk_success', sprintf(_n('%d IP unblocked.', '%d IPs unblocked.', $count, $this->text_domain), $count), 'updated');
            }

            wp_safe_redirect(admin_url('admin.php?page=blockforce-wp'));
            exit;
        }
    }

    public function handle_reset_actions()
    {
        if (!$this->is_our_page()) {
            return;
        }

        $action = isset($_GET['bfwp_action']) ? sanitize_key($_GET['bfwp_action']) : '';
        if (empty($action)) {
            return;
        }

        if (!wp_verify_nonce(sanitize_key($_GET['_wpnonce'] ?? ''), 'bfwp_reset_' . $action)) {
            wp_die(__('Security check failed.', $this->text_domain), __('Error', $this->text_domain), array('response' => 403));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'blockforce_logs';
        $message = '';

        switch ($action) {
            case 'clear_logs':
                $wpdb->query("TRUNCATE TABLE $table_name");
                $message = __('Activity logs cleared successfully.', $this->text_domain);
                break;

            case 'clear_blocked':
                $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'bfwp_blocked_%'");
                $message = __('All blocked IPs cleared successfully.', $this->text_domain);
                break;

            case 'clear_attempts':
                blockforce_wp_clear_all_transients();
                $message = __('Login attempt tracking cleared successfully.', $this->text_domain);
                break;

            case 'reset_url':
                delete_option('blockforce_login_slug');
                $this->core->login_url->flush_rewrite_rules();
                $message = __('Login URL restored to default (wp-login.php).', $this->text_domain);
                break;

            case 'full_reset':
                $wpdb->query("TRUNCATE TABLE $table_name");
                $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'bfwp_blocked_%'");
                blockforce_wp_clear_all_transients();
                delete_option('blockforce_login_slug');
                $this->core->login_url->flush_rewrite_rules();
                $message = __('Full plugin reset completed. All data cleared and login URL restored.', $this->text_domain);
                break;
        }

        if ($message) {
            add_settings_error('blockforce_messages', 'reset_success', $message, 'updated');
        }

        wp_safe_redirect(admin_url('admin.php?page=blockforce-wp-reset&reset_done=1'));
        exit;
    }

    public function handle_test_email()
    {
        if (!$this->is_our_page()) {
            return;
        }

        if (!isset($_GET['blockforce_test_email']) || $_GET['blockforce_test_email'] !== '1') {
            return;
        }

        if (!wp_verify_nonce(sanitize_key($_GET['_wpnonce_test_email'] ?? ''), 'blockforce_test_email_nonce')) {
            wp_die(__('Security check failed.', $this->text_domain), __('Error', $this->text_domain), array('response' => 403));
        }

        $target_email = !empty($this->settings['alert_email']) ? $this->settings['alert_email'] : get_option('admin_email');
        $sent = BlockForce_WP_Utils::send_admin_alert('0.0.0.0', 'test-' . wp_generate_password(8, false));

        if ($sent) {
            add_settings_error('blockforce_messages', 'email_success', sprintf(__('Test email sent to %s.', $this->text_domain), $target_email), 'updated');
        } else {
            add_settings_error('blockforce_messages', 'email_failed', sprintf(__('Failed to send email to %s.', $this->text_domain), $target_email), 'error');
        }

        wp_safe_redirect(admin_url('admin.php?page=blockforce-wp-settings'));
        exit;
    }
}
