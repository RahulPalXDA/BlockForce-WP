<?php

if (!defined('ABSPATH')) {
    exit;
}

class BlockForce_WP_Admin {

    private $settings;
    private $core;
    private $text_domain = BFWP_TEXT_DOMAIN;

    public function __construct($settings, $core) {
        $this->settings = $settings;
        $this->core = $core;
    }

    public function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('admin_init', array($this, 'handle_plugin_reset')); 
        add_action('update_option_blockforce_settings', array($this, 'on_settings_update'), 10, 2);
        add_filter('plugin_action_links_' . $this->core->basename, array($this, 'add_settings_link'));
    }

    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=blockforce-wp') . '">' . __('Settings', $this->text_domain) . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Flush rewrites when settings are updated (e.g., if login URL settings change)
     */
    public function on_settings_update($old_value, $new_value) {
        $this->core->login_url->flush_rewrite_rules();
    }

    public function add_admin_menu() {
        add_options_page(
            __('BlockForce WP Settings', $this->text_domain),
            __('BlockForce WP', $this->text_domain),
            'manage_options',
            'blockforce-wp',
            array($this, 'options_page')
        );
    }

    public function settings_init() {
        register_setting('blockforce_settings', 'blockforce_settings', array($this, 'sanitize_settings'));
        add_settings_section('blockforce_main_section', __('Login Security Rules', $this->text_domain), null, 'blockforce_settings');
        add_settings_field('attempt_limit', __('Maximum Failed Attempts Allowed', $this->text_domain), array($this, 'attempt_limit_render'), 'blockforce_settings', 'blockforce_main_section');
        add_settings_field('block_time', __('Time to Block IP (in seconds)', $this->text_domain), array($this, 'block_time_render'), 'blockforce_settings', 'blockforce_main_section');
        add_settings_field('log_time', __('Time to Watch for Attacks (in seconds)', $this->text_domain), array($this, 'log_time_render'), 'blockforce_settings', 'blockforce_main_section');
        add_settings_field('enable_ip_blocking', __('Block Attacker\'s IP', $this->text_domain), array($this, 'enable_ip_blocking_render'), 'blockforce_settings', 'blockforce_main_section'); 
        add_settings_field('enable_url_change', __('Auto-Change Login Link', $this->text_domain), array($this, 'enable_url_change_render'), 'blockforce_settings', 'blockforce_main_section');
    }

    public function sanitize_settings($input) {
        $output = array();
        $output['attempt_limit'] = max(1, (int) $input['attempt_limit']); 
        $output['block_time'] = max(1, (int) $input['block_time']); 
        $output['log_time'] = max(1, (int) $input['log_time']); 
        $output['enable_url_change'] = isset($input['enable_url_change']) ? 1 : 0;
        $output['enable_ip_blocking'] = isset($input['enable_ip_blocking']) ? 1 : 0; 
        add_settings_error('blockforce_settings', 'settings_updated', __('Settings saved.', $this->text_domain), 'updated');
        return $output;
    }

    // --- Settings Render Functions ---

    public function attempt_limit_render() {
        ?><input type="number" name="blockforce_settings[attempt_limit]" value="<?php echo esc_attr($this->settings['attempt_limit']); ?>" min="1" style="width: 80px;"><p class="description"><?php esc_html_e('The total number of failed passwords allowed before we block the attacker\'s IP and/or change your login link.', $this->text_domain); ?></p><?php
    }
    
    public function block_time_render() {
        ?><input type="number" name="blockforce_settings[block_time]" value="<?php echo esc_attr($this->settings['block_time']); ?>" min="1" style="width: 100px;"><p class="description"><?php esc_html_e('How long (in seconds) an attacker is prevented from accessing your site after they exceed the allowed attempts.', $this->text_domain); ?></p><?php
    }
    
    public function enable_ip_blocking_render() { 
        ?><input type="checkbox" name="blockforce_settings[enable_ip_blocking]" value="1" <?php checked(1, $this->settings['enable_ip_blocking']); ?>><label><?php esc_html_e('If checked, attackers\' IP addresses will be temporarily banned from the site after too many failed attempts.', $this->text_domain); ?></label><?php
    }
    
    public function log_time_render() {
        ?><input type="number" name="blockforce_settings[log_time]" value="<?php echo esc_attr($this->settings['log_time']); ?>" min="1" style="width: 120px;"><p class="description"><?php esc_html_e('The length of time (in seconds) we monitor and count failed attempts for an IP.', $this->text_domain); ?></p><?php
    }
    
    public function enable_url_change_render() {
        ?><input type="checkbox" name="blockforce_settings[enable_url_change]" value="1" <?php checked(1, $this->settings['enable_url_change']); ?>><label><?php esc_html_e('If checked, the login link will automatically change to a new, secret link after too many failed attempts.', $this->text_domain); ?></label><?php
    }
    
    // --- Admin Page Display ---

    public function options_page() {
        $tabs = array(
            'overview' => __('Overview', $this->text_domain),
            'settings' => __('Security Settings', $this->text_domain),
            'reset'    => __('Reset', $this->text_domain)
        );
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';
        $current_tab = array_key_exists($current_tab, $tabs) ? $current_tab : 'overview';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('BlockForce WP Settings', $this->text_domain); ?></h1>
            <h2 class="nav-tab-wrapper">
                <?php foreach ($tabs as $tab => $name): ?>
                    <a href="?page=blockforce-wp&tab=<?php echo esc_attr($tab); ?>" class="nav-tab <?php echo $current_tab == $tab ? 'nav-tab-active' : ''; ?>"><?php echo esc_html($name); ?></a>
                <?php endforeach; ?>
            </h2>
            <?php 
            if ($current_tab == 'overview') { 
                $this->display_overview_tab(); 
            } elseif ($current_tab == 'settings') { 
                $this->display_settings_tab(); 
            } elseif ($current_tab == 'reset') { 
                $this->display_reset_tab(); 
            }
            ?>
        </div>
        <?php
    }

    private function display_overview_tab() {
        ?>
        <div class="card" style="max-width: 100%;">
            <h2><?php esc_html_e('Security Overview', $this->text_domain); ?></h2>
            <ul style="list-style-type: disc; margin-left: 20px;">
                <li><?php esc_html_e('Blocks IP addresses after failed password attempts', $this->text_domain); ?></li>
                <li><?php esc_html_e('Changes the login link automatically when attacks are detected', $this->text_domain); ?></li>
                <li><?php esc_html_e('Prevents username guessing by showing generic error messages', $this->text_domain); ?></li>
            </ul>
        </div>
        <div class="card" style="max-width: 100%; margin-top: 20px;">
            <h2><?php esc_html_e('Current Login Status', $this->text_domain); ?></h2>
            <?php $this->current_status_render(); ?>
        </div>
        <?php
    }
    
    private function current_status_render() {
        $current_slug = $this->core->login_url->get_login_slug();
        $site_url = get_site_url();
        ?>
        <div style="background: #f6f7f7; padding: 15px; border-left: 4px solid #0073aa; margin: 10px 0;">
            <?php if ($current_slug): ?>
                <span style="color: #d63638;">● <?php esc_html_e('Secret Login Link is ACTIVE', $this->text_domain); ?></span><br>
                <strong><?php esc_html_e('Active Login Link:', $this->text_domain); ?></strong> 
                <code style="background: #fff; padding: 2px 5px; border: 1px solid #ddd;">
                    <?php echo esc_url($site_url . '/' . $current_slug); ?>
                </code>
            <?php else: ?>
                <span style="color: #00a32a;">● <?php esc_html_e('Default Login Link is ACTIVE', $this->text_domain); ?></span><br>
                <strong><?php esc_html_e('Current Login Link:', $this->text_domain); ?></strong> 
                <code style="background: #fff; padding: 2px 5px; border: 1px solid #ddd;">
                    <?php echo esc_url($site_url . '/wp-login.php'); ?>
                </code>
            <?php endif; ?>
        </div>
        <?php
    }
    
    private function display_settings_tab() {
        ?>
        <form action="options.php" method="post">
            <?php
            settings_fields('blockforce_settings');
            do_settings_sections('blockforce_settings');
            submit_button(__('Save Settings', $this->text_domain));
            ?>
        </form>
        <?php
    }

    // --- Reset Tab & Logic ---

    public function handle_plugin_reset() {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }
        
        $action_performed = null;
        $nonce_key = null;
        $nonce_name = null;
        
        if (isset($_GET['blockforce_reset']) && $_GET['blockforce_reset'] === '1') {
            $nonce_key = '_wpnonce_reset';
            $action_performed = 'full';
            $nonce_name = 'blockforce_reset_nonce';
        } elseif (isset($_GET['blockforce_reset_ips']) && $_GET['blockforce_reset_ips'] === '1') {
            $nonce_key = '_wpnonce_reset_ips';
            $action_performed = 'ips';
            $nonce_name = 'blockforce_reset_ips_nonce';
        } elseif (isset($_GET['blockforce_reset_slug']) && $_GET['blockforce_reset_slug'] === '1') {
            $nonce_key = '_wpnonce_reset_slug';
            $action_performed = 'slug';
            $nonce_name = 'blockforce_reset_slug_nonce';
        }
        
        if ($action_performed) {
            if (!isset($_GET[$nonce_key]) || !wp_verify_nonce(sanitize_key($_GET[$nonce_key]), $nonce_name)) {
                wp_die(__('Security check failed.', $this->text_domain));
            }
            
            $slug_changed = false;
            $success_message = '';
            
            if ($action_performed === 'full' || $action_performed === 'ips') {
                // Uses the global function from functions.php
                blockforce_wp_clear_all_transients();
                $success_message = __('IP Blocks and Logs Cleared! All temporary IP bans and failed attempt logs have been cleared.', $this->text_domain);
            }
            
            if ($action_performed === 'full' || $action_performed === 'slug') {
                update_option('blockforce_login_slug', '');
                $slug_changed = true;
                if ($action_performed === 'full') {
                     $success_message = __('Plugin fully reset! All blocks, logs, and the login link have been cleared.', $this->text_domain);
                } else {
                     $success_message = __('Login Link Reset! The custom login link has been reset back to wp-login.php.', $this->text_domain);
                }
            }
            
            if ($slug_changed) {
                $this->core->login_url->flush_rewrite_rules();
            }
            
            if ($success_message) {
                 add_settings_error('blockforce_reset', 'reset_success', $success_message, 'updated');
            }
            
            $redirect_url = admin_url('options-general.php?page=blockforce-wp&tab=reset&settings-updated=true');
            wp_safe_redirect($redirect_url);
            exit;
        }
    }
    
    private function display_reset_tab() {
        $full_reset_url = wp_nonce_url(
            admin_url('options-general.php?page=blockforce-wp&tab=reset&blockforce_reset=1'),
            'blockforce_reset_nonce',
            '_wpnonce_reset'
        );
        $ips_reset_url = wp_nonce_url(
            admin_url('options-general.php?page=blockforce-wp&tab=reset&blockforce_reset_ips=1'),
            'blockforce_reset_ips_nonce',
            '_wpnonce_reset_ips'
        );
        $slug_reset_url = wp_nonce_url(
            admin_url('options-general.php?page=blockforce-wp&tab=reset&blockforce_reset_slug=1'),
            'blockforce_reset_slug_nonce',
            '_wpnonce_reset_slug'
        );
        ?>
        <div class="card" style="max-width: 100%;">
            <h2><?php esc_html_e('Reset Functions', $this->text_domain); ?></h2>
            <?php
            settings_errors('blockforce_reset');
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Reset IP Blocks & Logs', $this->text_domain); ?></th>
                    <td>
                        <p class="description"><?php esc_html_e('Use this to immediately unblock all IP addresses and clear the attack history.', $this->text_domain); ?></p>
                        <a href="<?php echo esc_url($ips_reset_url); ?>" 
                           onclick="return confirm('<?php echo esc_js(__('Are you sure you want to clear all IP blocks and attack logs?', $this->text_domain)); ?>')" 
                           class="button button-secondary">
                            <?php esc_html_e('CLEAR IP BLOCKS', $this->text_domain); ?>
                        </a>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Reset Login Link', $this->text_domain); ?></th>
                    <td>
                        <p class="description"><?php esc_html_e('Use this to force the login link back to the default /wp-login.php.', $this->text_domain); ?></p>
                        <a href="<?php echo esc_url($slug_reset_url); ?>" 
                           onclick="return confirm('<?php echo esc_js(__('Are you sure you want to reset the login link back to wp-login.php?', $this->text_domain)); ?>')" 
                           class="button button-secondary">
                            <?php esc_html_e('RESET LOGIN LINK', $this->text_domain); ?>
                        </a>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Full Plugin Reset', $this->text_domain); ?></th>
                    <td>
                        <p class="description"><?php esc_html_e('This performs both actions above: clears all blocks/logs AND resets the login link back to default.', $this->text_domain); ?></p>
                        <a href="<?php echo esc_url($full_reset_url); ?>" 
                           onclick="return confirm('<?php echo esc_js(__('WARNING: Are you sure you want to perform a full plugin reset?', $this->text_domain)); ?>')" 
                           class="button button-secondary button-large" 
                           style="background-color: #dc3232; color: #fff; border-color: #dc3232; font-weight: bold;">
                            <?php esc_html_e('FULL RESET', $this->text_domain); ?>
                        </a>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
}