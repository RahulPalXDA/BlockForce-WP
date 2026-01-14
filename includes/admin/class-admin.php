<?php
declare(strict_types=1);
if (!defined('ABSPATH'))
    exit;
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
        add_action('admin_init', array($this, 'handle_single_unblock_action'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        add_action('update_option_blockforce_settings', array($this, 'on_settings_update'), 10, 2);
        add_filter('plugin_action_links_' . $this->core->basename, array($this, 'add_settings_link'));
    }
    public function add_settings_link($links)
    {
        array_unshift($links, '<a href="' . admin_url('admin.php?page=blockforce-wp-settings') . '">' . __('Settings', $this->text_domain) . '</a>');
        return $links;
    }
    public function enqueue_admin_styles($hook)
    {
        if (in_array($hook, array('toplevel_page_blockforce-wp', 'blockforce-wp_page_blockforce-wp-logs', 'blockforce-wp_page_blockforce-wp-settings', 'blockforce-wp_page_blockforce-wp-reset', 'blockforce-wp_page_blockforce-wp-blocks'))) {
            wp_enqueue_style('blockforce-admin', BFWP_URL . 'assets/css/admin.css', array(), BFWP_VERSION);
        }
    }
    public function on_settings_update($old, $new)
    {
        $this->core->login_url->flush_rewrite_rules();
    }
    public function add_admin_menu()
    {
        add_menu_page(__('BlockForce WP', $this->text_domain), __('BlockForce WP', $this->text_domain), 'manage_options', $this->menu_slug, array($this, 'render_overview_page'), 'dashicons-privacy', 80);
        add_submenu_page($this->menu_slug, __('Overview', $this->text_domain), __('Overview', $this->text_domain), 'manage_options', $this->menu_slug, array($this, 'render_overview_page'));
        add_submenu_page($this->menu_slug, __('Activity Log', $this->text_domain), __('Activity Log', $this->text_domain), 'manage_options', $this->menu_slug . '-logs', array($this, 'render_logs_page'));
        add_submenu_page($this->menu_slug, __('Blocked IPs', $this->text_domain), __('Blocked IPs', $this->text_domain), 'manage_options', $this->menu_slug . '-blocks', array($this, 'render_blocks_page'));
        add_submenu_page($this->menu_slug, __('Settings', $this->text_domain), __('Settings', $this->text_domain), 'manage_options', $this->menu_slug . '-settings', array($this, 'render_settings_page'));
        add_submenu_page($this->menu_slug, __('Reset & Tools', $this->text_domain), __('Reset & Tools', $this->text_domain), 'manage_options', $this->menu_slug . '-reset', array($this, 'render_reset_page'));
    }
    private function get_page_args()
    {
        return array('settings' => $this->settings, 'core' => $this->core, 'text_domain' => $this->text_domain);
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
    public function render_blocks_page()
    {
        $args = $this->get_page_args();
        include BFWP_PATH . 'includes/admin/views/page-blocks.php';
    }
    public function handle_single_unblock_action()
    {
        if ($this->is_our_page() && isset($_GET['bfwp_action'], $_GET['ip']) && $_GET['bfwp_action'] === 'unblock_ip') {
            $ip = sanitize_text_field($_GET['ip']);
            if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'blockforce_unblock_' . $ip))
                wp_die(__('Security check failed.', $this->text_domain));
            $this->core->security->unblock_ip($ip);
            add_settings_error('blockforce_messages', 'unblock_success', sprintf(__('IP %s unblocked successfully.', $this->text_domain), $ip), 'updated');
            wp_safe_redirect(admin_url('admin.php?page=blockforce-wp-blocks'));
            exit;
        }
    }
    private function is_our_page()
    {
        return (is_admin() && current_user_can('manage_options') && strpos($_GET['page'] ?? '', 'blockforce-wp') === 0);
    }
    public function handle_bulk_unblock()
    {
        if ($this->is_our_page() && isset($_POST['blockforce_bulk_action']) && $_POST['blockforce_bulk_action'] === 'unblock') {
            check_admin_referer('blockforce_bulk_unblock', '_wpnonce_bulk');
            $count = 0;
            if (isset($_POST['blocked_ips']) && is_array($_POST['blocked_ips'])) {
                foreach ($_POST['blocked_ips'] as $ip) {
                    $this->core->security->unblock_ip(sanitize_text_field($ip));
                    $count++;
                }
                add_settings_error('blockforce_messages', 'bulk_success', sprintf(_n('%d IP unblocked.', '%d IPs unblocked.', $count, $this->text_domain), $count), 'updated');
            }
            wp_safe_redirect(admin_url('admin.php?page=blockforce-wp-blocks'));
            exit;
        }
    }
    public function handle_reset_actions()
    {
        if (!$this->is_our_page() || empty($_GET['bfwp_action']))
            return;
        $action = sanitize_key($_GET['bfwp_action']);
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'bfwp_reset_' . $action))
            wp_die(__('Security check failed.', $this->text_domain));
        global $wpdb;
        $logs = $wpdb->prefix . BFWP_LOGS_TABLE;
        $blocks = $wpdb->prefix . BFWP_BLOCKS_TABLE;
        $msg = '';
        switch ($action) {
            case 'clear_logs':
                $wpdb->query("DELETE FROM $logs");
                $msg = __('Activity logs cleared.', $this->text_domain);
                break;
            case 'clear_blocked':
                $wpdb->query("DELETE FROM $blocks");
                $msg = __('Blocked IPs cleared.', $this->text_domain);
                break;
            case 'clear_attempts':
                blockforce_wp_clear_all_transients();
                $msg = __('Attempts cleared.', $this->text_domain);
                break;
            case 'reset_url':
                delete_option('blockforce_login_slug');
                $this->core->login_url->flush_rewrite_rules();
                $msg = __('URL reset.', $this->text_domain);
                break;
            case 'full_reset':
                $wpdb->query("DELETE FROM $logs");
                $wpdb->query("DELETE FROM $blocks");
                blockforce_wp_clear_all_transients();
                delete_option('blockforce_login_slug');
                $this->core->login_url->flush_rewrite_rules();
                $msg = __('Full reset done.', $this->text_domain);
                break;
        }
        if ($msg)
            add_settings_error('blockforce_messages', 'reset_success', $msg, 'updated');
        wp_safe_redirect(admin_url('admin.php?page=blockforce-wp-reset&reset_done=1'));
        exit;
    }
    public function handle_test_email()
    {
        if ($this->is_our_page() && isset($_GET['blockforce_test_email']) && $_GET['blockforce_test_email'] === '1') {
            check_admin_referer('blockforce_test_email_nonce', '_wpnonce_test_email');
            $email = !empty($this->settings['alert_email']) ? $this->settings['alert_email'] : get_option('admin_email');
            if (BlockForce_WP_Utils::send_admin_alert('0.0.0.0', 'test-' . wp_generate_password(8, false))) {
                add_settings_error('blockforce_messages', 'email_success', sprintf(__('Test email sent to %s.', $this->text_domain), $email), 'updated');
            } else {
                add_settings_error('blockforce_messages', 'email_failed', sprintf(__('Failed to send to %s.', $this->text_domain), $email), 'error');
            }
            wp_safe_redirect(admin_url('admin.php?page=blockforce-wp-settings'));
            exit;
        }
    }
}
