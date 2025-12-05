<?php
/**
 * Admin Controller
 * 
 * Main admin interface controller that delegates to specialized modules.
 *
 * @package BlockForce_WP
 * @subpackage Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

class BlockForce_WP_Admin
{
    private $settings;
    private $core;
    private $text_domain = 'blockforce-wp';

    public function __construct($settings, $core)
    {
        $this->settings = $settings;
        $this->core = $core;
    }

    /**
     * Initialize admin hooks
     */
    public function init_hooks()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('admin_init', array($this, 'handle_bulk_unblock'));
        add_action('admin_init', array($this, 'handle_plugin_reset'));
        add_action('admin_init', array($this, 'handle_login_url_reset'));
        add_action('admin_init', array($this, 'handle_test_email'));
        add_action('admin_init', array($this, 'handle_blocklist_action'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        add_action('update_option_blockforce_settings', array($this, 'on_settings_update'), 10, 2);
        add_filter('plugin_action_links_' . $this->core->basename, array($this, 'add_settings_link'));
    }

    /**
     * Add settings link to plugins page
     */
    public function add_settings_link($links)
    {
        $settings_link = '<a href="' . admin_url('options-general.php?page=blockforce-wp') . '">' . __('Settings', $this->text_domain) . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Enqueue admin styles
     */
    public function enqueue_admin_styles($hook)
    {
        if ($hook !== 'settings_page_blockforce-wp') {
            return;
        }

        wp_enqueue_style(
            'blockforce-admin',
            plugins_url('assets/css/admin.css', dirname(dirname(__FILE__))),
            array(),
            filemtime(BFWP_PATH . 'assets/css/admin.css')
        );
    }

    /**
     * Flush rewrites when settings are updated
     */
    public function on_settings_update($old_value, $new_value)
    {
        if ($this->core->login_url) {
            $this->core->login_url->flush_rewrite_rules();
        }
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        add_options_page(
            __('BlockForce WP', $this->text_domain),
            __('BlockForce WP', $this->text_domain),
            'manage_options',
            'blockforce-wp',
            array($this, 'options_page')
        );
    }

    /**
     * Initialize settings
     */
    public function settings_init()
    {
        $settings_handler = new BlockForce_WP_Admin_Settings($this->settings);
        $settings_handler->register();
    }

    /**
     * Render the main options page
     */
    public function options_page()
    {
        $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'overview';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('BlockForce WP Security', $this->text_domain); ?></h1>

            <nav class="nav-tab-wrapper">
                <a href="?page=blockforce-wp&tab=overview"
                    class="nav-tab <?php echo $tab === 'overview' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Overview', $this->text_domain); ?>
                </a>
                <a href="?page=blockforce-wp&tab=logs" class="nav-tab <?php echo $tab === 'logs' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Logs', $this->text_domain); ?>
                </a>
                <a href="?page=blockforce-wp&tab=blocklist"
                    class="nav-tab <?php echo $tab === 'blocklist' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Blocklist', $this->text_domain); ?>
                </a>
                <a href="?page=blockforce-wp&tab=settings"
                    class="nav-tab <?php echo $tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Settings', $this->text_domain); ?>
                </a>
                <a href="?page=blockforce-wp&tab=reset" class="nav-tab <?php echo $tab === 'reset' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Reset', $this->text_domain); ?>
                </a>
            </nav>

            <div class="tab-content" style="margin-top: 20px;">
                <?php $this->render_tab($tab); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render the active tab
     */
    private function render_tab($tab)
    {
        switch ($tab) {
            case 'logs':
                $tab_handler = new BlockForce_WP_Tab_Logs($this->settings);
                $tab_handler->render();
                break;

            case 'blocklist':
                $tab_handler = new BlockForce_WP_Tab_Blocklist($this->settings, $this->core);
                $tab_handler->render();
                break;

            case 'settings':
                $tab_handler = new BlockForce_WP_Tab_Settings();
                $tab_handler->render();
                break;

            case 'reset':
                $tab_handler = new BlockForce_WP_Tab_Reset();
                $tab_handler->render();
                break;

            case 'overview':
            default:
                $tab_handler = new BlockForce_WP_Tab_Overview($this->settings, $this->core);
                $tab_handler->render();
                break;
        }
    }

    // --- Action Handlers ---

    /**
     * Handle bulk unblock action
     */
    public function handle_bulk_unblock()
    {
        if (!isset($_GET['page']) || $_GET['page'] !== 'blockforce-wp') {
            return;
        }

        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['blockforce_bulk_action']) && $_POST['blockforce_bulk_action'] === 'unblock') {
            if (!isset($_POST['_wpnonce_bulk']) || !wp_verify_nonce(sanitize_key($_POST['_wpnonce_bulk']), 'blockforce_bulk_unblock')) {
                wp_die(__('Security check failed.', $this->text_domain));
            }

            if (isset($_POST['blocked_ips']) && is_array($_POST['blocked_ips'])) {
                $count = 0;
                foreach ($_POST['blocked_ips'] as $ip) {
                    $ip = sanitize_text_field($ip);
                    delete_option('bfwp_blocked_' . $ip);
                    $count++;
                }

                add_settings_error(
                    'blockforce_bulk',
                    'bulk_success',
                    sprintf(_n('%d IP unblocked successfully.', '%d IPs unblocked successfully.', $count, $this->text_domain), $count),
                    'updated'
                );
            }

            wp_safe_redirect(admin_url('options-general.php?page=blockforce-wp&tab=overview'));
            exit;
        }
    }

    /**
     * Handle plugin reset action
     */
    public function handle_plugin_reset()
    {
        if (!isset($_GET['page']) || $_GET['page'] !== 'blockforce-wp') {
            return;
        }

        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        if (isset($_GET['blockforce_reset']) && $_GET['blockforce_reset'] === '1') {
            if (!isset($_GET['_wpnonce_reset']) || !wp_verify_nonce(sanitize_key($_GET['_wpnonce_reset']), 'blockforce_reset_nonce')) {
                wp_die(__('Security check failed.', $this->text_domain));
            }

            blockforce_wp_clear_all_transients();

            global $wpdb;
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $wpdb->esc_like('bfwp_blocked_') . '%'
            ));

            $table_name = $wpdb->prefix . 'blockforce_logs';
            $wpdb->query("TRUNCATE TABLE $table_name");

            update_option('blockforce_login_slug', '');
            $this->core->login_url->flush_rewrite_rules();

            add_settings_error(
                'blockforce_reset',
                'reset_success',
                __('Plugin reset successfully! All blocks cleared and login URL restored to wp-login.php.', $this->text_domain),
                'updated'
            );

            wp_safe_redirect(admin_url('options-general.php?page=blockforce-wp&tab=reset&settings-updated=true'));
            exit;
        }
    }

    /**
     * Handle login URL reset action
     */
    public function handle_login_url_reset()
    {
        if (!isset($_GET['page']) || $_GET['page'] !== 'blockforce-wp') {
            return;
        }

        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        if (isset($_GET['blockforce_reset_url']) && $_GET['blockforce_reset_url'] === '1') {
            if (!isset($_GET['_wpnonce_reset_url']) || !wp_verify_nonce(sanitize_key($_GET['_wpnonce_reset_url']), 'blockforce_reset_url_nonce')) {
                wp_die(__('Security check failed.', $this->text_domain));
            }

            update_option('blockforce_login_slug', '');
            $this->core->login_url->flush_rewrite_rules();

            add_settings_error(
                'blockforce_reset',
                'reset_url_success',
                __('Login URL reset successfully to default wp-login.php.', $this->text_domain),
                'updated'
            );

            wp_safe_redirect(admin_url('options-general.php?page=blockforce-wp&tab=reset&settings-updated=true'));
            exit;
        }
    }

    /**
     * Handle test email action
     */
    public function handle_test_email()
    {
        if (!isset($_GET['page']) || $_GET['page'] !== 'blockforce-wp') {
            return;
        }

        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        if (isset($_GET['blockforce_test_email']) && $_GET['blockforce_test_email'] === '1') {
            if (!isset($_GET['_wpnonce_test_email']) || !wp_verify_nonce(sanitize_key($_GET['_wpnonce_test_email']), 'blockforce_test_email_nonce')) {
                wp_die(__('Security check failed.', $this->text_domain));
            }

            $alert_email = isset($this->settings['alert_email']) && !empty($this->settings['alert_email'])
                ? $this->settings['alert_email']
                : get_option('admin_email');

            $subject = 'BlockForce WP - Test Email';
            $message = '<html><body>';
            $message .= '<h2>BlockForce WP Test Email</h2>';
            $message .= '<p>This is a test email to verify that your WordPress site can send emails successfully.</p>';
            $message .= '<p><strong>Email Configuration:</strong></p>';
            $message .= '<ul>';
            $message .= '<li>Recipient: ' . esc_html($alert_email) . '</li>';
            $message .= '<li>Sent at: ' . current_time('mysql') . '</li>';
            $message .= '<li>Site URL: ' . get_site_url() . '</li>';
            $message .= '</ul>';
            $message .= '<p>If you received this email, your email delivery is working correctly.</p>';
            $message .= '</body></html>';

            add_filter('wp_mail_content_type', array('BlockForce_WP_Utils', 'set_html_content_type'));
            $sent = wp_mail($alert_email, $subject, $message);
            remove_filter('wp_mail_content_type', array('BlockForce_WP_Utils', 'set_html_content_type'));

            if ($sent) {
                add_settings_error(
                    'blockforce_test_email',
                    'test_email_success',
                    sprintf(__('Test email sent successfully to %s.', $this->text_domain), $alert_email),
                    'updated'
                );
            } else {
                add_settings_error(
                    'blockforce_test_email',
                    'test_email_failed',
                    __('Failed to send test email. Please check your email configuration.', $this->text_domain),
                    'error'
                );
            }

            wp_safe_redirect(admin_url('options-general.php?page=blockforce-wp&tab=settings&settings-updated=true'));
            exit;
        }
    }

    /**
     * Handle blocklist actions (sync, add, delete)
     */
    public function handle_blocklist_action()
    {
        if (!isset($_GET['page']) || $_GET['page'] !== 'blockforce-wp') {
            return;
        }

        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        // Handle Sync
        if (isset($_POST['bfwp_blocklist_sync'])) {
            check_admin_referer('bfwp_blocklist_sync', 'bfwp_blocklist_nonce');

            if (class_exists('BlockForce_WP_Blocklist')) {
                $blocklist = new BlockForce_WP_Blocklist($this->settings, $this->core);
                $blocklist->update_blocklist();

                add_settings_error(
                    'blockforce_settings',
                    'sync_success',
                    __('Global blocklist synchronized successfully.', $this->text_domain),
                    'updated'
                );
            }
        }

        // Handle Add Manual IP
        if (isset($_POST['bfwp_blocklist_add']) && !empty($_POST['manual_ip'])) {
            check_admin_referer('bfwp_blocklist_action', 'bfwp_blocklist_nonce');

            $ip = sanitize_text_field($_POST['manual_ip']);
            $blocklist = new BlockForce_WP_Blocklist($this->settings, $this->core);
            $result = $blocklist->add_manual_ip($ip);

            if (is_wp_error($result)) {
                add_settings_error('blockforce_settings', 'add_error', $result->get_error_message(), 'error');
            } else {
                add_settings_error(
                    'blockforce_settings',
                    'add_success',
                    sprintf(__('IP %s added to blocklist.', $this->text_domain), esc_html($ip)),
                    'updated'
                );
            }
        }

        // Handle Delete Manual IP
        if (isset($_GET['action']) && $_GET['action'] === 'delete_ip' && isset($_GET['id'])) {
            check_admin_referer('delete_ip_' . $_GET['id']);

            $id = absint($_GET['id']);
            $blocklist = new BlockForce_WP_Blocklist($this->settings, $this->core);
            $success = $blocklist->delete_manual_ip($id);

            if ($success) {
                add_settings_error('blockforce_settings', 'delete_success', __('IP removed from blocklist.', $this->text_domain), 'updated');
            } else {
                add_settings_error('blockforce_settings', 'delete_error', __('Failed to remove IP.', $this->text_domain), 'error');
            }

            wp_safe_redirect(remove_query_arg(array('action', 'id', '_wpnonce')));
            exit;
        }
    }
}
