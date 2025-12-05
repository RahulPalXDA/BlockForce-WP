<?php
/**
 * Logs Tab Display
 *
 * @package BlockForce_WP
 * @subpackage Admin\Tabs
 */

if (!defined('ABSPATH')) {
    exit;
}

class BlockForce_WP_Tab_Logs
{
    private $settings;
    private $text_domain = 'blockforce-wp';

    public function __construct($settings)
    {
        $this->settings = $settings;
    }

    /**
     * Render the Logs tab
     */
    public function render()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'blockforce_logs';

        // Handle Bulk Delete
        if (isset($_POST['blockforce_log_action']) && $_POST['blockforce_log_action'] == 'delete' && isset($_POST['log_ids'])) {
            check_admin_referer('blockforce_log_bulk_action', '_wpnonce_log');
            $ids = array_map('intval', $_POST['log_ids']);
            if (!empty($ids)) {
                $ids_placeholder = implode(',', array_fill(0, count($ids), '%d'));
                $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE id IN ($ids_placeholder)", $ids));
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Selected logs deleted.', $this->text_domain) . '</p></div>';
            }
        }

        // Pagination
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;

        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");
        $total_pages = ceil($total_items / $per_page);

        $logs = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY time DESC LIMIT %d OFFSET %d", $per_page, $offset));

        ?>
        <h3><?php esc_html_e('Login Activity Log', $this->text_domain); ?></h3>

        <form method="post" action="">
            <?php wp_nonce_field('blockforce_log_bulk_action', '_wpnonce_log'); ?>
            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <select name="blockforce_log_action">
                        <option value=""><?php esc_html_e('Bulk Actions', $this->text_domain); ?></option>
                        <option value="delete"><?php esc_html_e('Delete', $this->text_domain); ?></option>
                    </select>
                    <button type="submit" class="button action"><?php esc_html_e('Apply', $this->text_domain); ?></button>
                </div>
                <div class="tablenav-pages">
                    <span
                        class="displaying-num"><?php echo sprintf(_n('%s item', '%s items', $total_items, $this->text_domain), number_format_i18n($total_items)); ?></span>
                    <?php if ($total_pages > 1): ?>
                        <span class="pagination-links">
                            <?php if ($current_page > 1): ?>
                                <a class="prev-page button"
                                    href="<?php echo esc_url(add_query_arg('paged', $current_page - 1)); ?>">‹</a>
                            <?php endif; ?>
                            <span class="paging-input">
                                <?php echo sprintf(esc_html__('Page %1$d of %2$d', $this->text_domain), $current_page, $total_pages); ?>
                            </span>
                            <?php if ($current_page < $total_pages): ?>
                                <a class="next-page button"
                                    href="<?php echo esc_url(add_query_arg('paged', $current_page + 1)); ?>">›</a>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column"><input type="checkbox" id="cb-select-all-1">
                        </td>
                        <th><?php esc_html_e('User', $this->text_domain); ?></th>
                        <th><?php esc_html_e('IP Address', $this->text_domain); ?></th>
                        <th><?php esc_html_e('Date', $this->text_domain); ?></th>
                        <th><?php esc_html_e('Time', $this->text_domain); ?></th>
                        <th><?php esc_html_e('Status', $this->text_domain); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($logs)): ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <th scope="row" class="check-column"><input type="checkbox" name="log_ids[]"
                                        value="<?php echo esc_attr($log->id); ?>"></th>
                                <td><?php echo esc_html($log->user_login); ?></td>
                                <td><?php echo esc_html($log->user_ip); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($log->time))); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('time_format'), strtotime($log->time))); ?></td>
                                <td>
                                    <?php if ($log->status == 'success'): ?>
                                        <span
                                            style="color: #00a32a; font-weight: bold;"><?php esc_html_e('Success', $this->text_domain); ?></span>
                                    <?php else: ?>
                                        <span
                                            style="color: #d63638; font-weight: bold;"><?php esc_html_e('Failed', $this->text_domain); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6"><?php esc_html_e('No logs found.', $this->text_domain); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>
        <script>
            document.getElementById('cb-select-all-1').addEventListener('change', function () {
                var checkboxes = document.querySelectorAll('input[name="log_ids[]"]');
                for (var i = 0; i < checkboxes.length; i++) {
                    checkboxes[i].checked = this.checked;
                }
            });
        </script>
        <?php
    }
}
