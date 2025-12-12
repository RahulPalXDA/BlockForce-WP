<?php
if (!defined('ABSPATH')) {
    exit;
}

$text_domain = $args['text_domain'];

global $wpdb;
$table_name = $wpdb->prefix . 'blockforce_logs';

if (isset($_POST['blockforce_log_action']) && $_POST['blockforce_log_action'] == 'delete' && isset($_POST['log_ids'])) {
    check_admin_referer('blockforce_log_bulk_action', '_wpnonce_log');
    $ids = array_map('intval', $_POST['log_ids']);
    if (!empty($ids)) {
        $ids_placeholder = implode(',', array_fill(0, count($ids), '%d'));
        $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE id IN ($ids_placeholder)", $ids));
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Selected logs deleted.', $text_domain) . '</p></div>';
    }
}

$per_page = 25;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;
$total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");
$total_pages = ceil($total_items / $per_page);
$logs = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY time DESC LIMIT %d OFFSET %d", $per_page, $offset));
?>
<div class="wrap blockforce-wrap">
    <h1><span class="dashicons dashicons-list-view"></span> <?php esc_html_e('Activity Log', $text_domain); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('blockforce_log_bulk_action', '_wpnonce_log'); ?>
        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <select name="blockforce_log_action">
                    <option value=""><?php esc_html_e('Bulk Actions', $text_domain); ?></option>
                    <option value="delete"><?php esc_html_e('Delete', $text_domain); ?></option>
                </select>
                <button type="submit" class="button action"><?php esc_html_e('Apply', $text_domain); ?></button>
            </div>
            <div class="tablenav-pages">
                <span
                    class="displaying-num"><?php echo sprintf(_n('%s item', '%s items', $total_items, $text_domain), number_format_i18n($total_items)); ?></span>
                <?php if ($total_pages > 1): ?>
                    <span class="pagination-links">
                        <?php if ($current_page > 1): ?>
                            <a class="prev-page button"
                                href="<?php echo esc_url(add_query_arg('paged', $current_page - 1)); ?>">‹</a>
                        <?php endif; ?>
                        <span class="paging-input"><?php echo $current_page; ?> / <?php echo $total_pages; ?></span>
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
                    <td class="manage-column column-cb check-column"><input type="checkbox" id="cb-select-all"></td>
                    <th><?php esc_html_e('User', $text_domain); ?></th>
                    <th><?php esc_html_e('IP Address', $text_domain); ?></th>
                    <th><?php esc_html_e('Date & Time', $text_domain); ?></th>
                    <th><?php esc_html_e('Status', $text_domain); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <th scope="row" class="check-column"><input type="checkbox" name="log_ids[]"
                                    value="<?php echo esc_attr($log->id); ?>"></th>
                            <td><strong><?php echo esc_html($log->user_login); ?></strong></td>
                            <td><?php echo esc_html($log->user_ip); ?></td>
                            <td><?php echo esc_html(date_i18n('M j, Y @ g:i a', strtotime($log->time))); ?></td>
                            <td>
                                <?php if ($log->status == 'success'): ?>
                                    <span
                                        class="blockforce-badge blockforce-badge-enabled"><?php esc_html_e('Success', $text_domain); ?></span>
                                <?php else: ?>
                                    <span
                                        class="blockforce-badge blockforce-badge-disabled"><?php esc_html_e('Failed', $text_domain); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5"><?php esc_html_e('No login activity recorded yet.', $text_domain); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </form>
</div>
<script>
    document.getElementById('cb-select-all').addEventListener('change', function () {
        document.querySelectorAll('input[name="log_ids[]"]').forEach(cb => cb.checked = this.checked);
    });
</script>