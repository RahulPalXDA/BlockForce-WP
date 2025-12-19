<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'blockforce_blocks';
$items_per_page = 20;
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($page - 1) * $items_per_page;

$total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");
$total_pages = ceil($total_items / $items_per_page);

$blocked_ips = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM $table_name ORDER BY blocked_at DESC LIMIT %d OFFSET %d",
        $items_per_page,
        $offset
    )
);

$base_url = admin_url('admin.php?page=blockforce-wp-blocks');
?>

<div class="wrap blockforce-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Blocked IPs', $args['text_domain']); ?></h1>
    <hr class="wp-header-end">

    <?php settings_errors('blockforce_messages'); ?>

    <div class="blockforce-card">
        <form method="post" action="<?php echo esc_url($base_url); ?>">
            <?php wp_nonce_field('blockforce_bulk_unblock', '_wpnonce_bulk'); ?>
            <input type="hidden" name="blockforce_bulk_action" value="unblock">

            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <button type="submit"
                        class="button action"><?php esc_html_e('Unblock Selected', $args['text_domain']); ?></button>
                </div>
                <div class="tablenav-pages">
                    <span
                        class="displaying-num"><?php echo sprintf(_n('%s item', '%s items', $total_items, $args['text_domain']), number_format_i18n($total_items)); ?></span>
                    <?php if ($total_pages > 1): ?>
                        <span class="pagination-links">
                            <?php if ($page > 1): ?>
                                <a class="first-page button"
                                    href="<?php echo esc_url(add_query_arg('paged', 1, $base_url)); ?>"><span
                                        class="screen-reader-text"><?php esc_html_e('First page', $args['text_domain']); ?></span><span
                                        aria-hidden="true">«</span></a>
                                <a class="prev-page button"
                                    href="<?php echo esc_url(add_query_arg('paged', $page - 1, $base_url)); ?>"><span
                                        class="screen-reader-text"><?php esc_html_e('Previous page', $args['text_domain']); ?></span><span
                                        aria-hidden="true">‹</span></a>
                            <?php endif; ?>
                            <span class="paging-input">
                                <span
                                    class="tablenav-paging-text"><?php echo sprintf(__('%1$s of %2$s', $args['text_domain']), $page, $total_pages); ?></span>
                            </span>
                            <?php if ($page < $total_pages): ?>
                                <a class="next-page button"
                                    href="<?php echo esc_url(add_query_arg('paged', $page + 1, $base_url)); ?>"><span
                                        class="screen-reader-text"><?php esc_html_e('Next page', $args['text_domain']); ?></span><span
                                        aria-hidden="true">›</span></a>
                                <a class="last-page button"
                                    href="<?php echo esc_url(add_query_arg('paged', $total_pages, $base_url)); ?>"><span
                                        class="screen-reader-text"><?php esc_html_e('Last page', $args['text_domain']); ?></span><span
                                        aria-hidden="true">»</span></a>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped table-view-list">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <input id="cb-select-all-1" type="checkbox">
                        </td>
                        <th scope="col" class="manage-column column-primary">
                            <?php esc_html_e('IP Address', $args['text_domain']); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e('Blocked At', $args['text_domain']); ?>
                        </th>
                        <th scope="col" class="manage-column"><?php esc_html_e('Expires At', $args['text_domain']); ?>
                        </th>
                        <th scope="col" class="manage-column"><?php esc_html_e('Reason', $args['text_domain']); ?></th>
                        <th scope="col" class="manage-column"><?php esc_html_e('Actions', $args['text_domain']); ?></th>
                    </tr>
                </thead>
                <tbody id="the-list">
                    <?php if (empty($blocked_ips)): ?>
                        <tr>
                            <td colspan="6"><?php esc_html_e('No blocked IPs found.', $args['text_domain']); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($blocked_ips as $ip): ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="blocked_ips[]" value="<?php echo esc_attr($ip->user_ip); ?>">
                                </th>
                                <td class="column-primary"
                                    data-colname="<?php esc_attr_e('IP Address', $args['text_domain']); ?>">
                                    <strong><?php echo esc_html($ip->user_ip); ?></strong>
                                </td>
                                <td data-colname="<?php esc_attr_e('Blocked At', $args['text_domain']); ?>">
                                    <?php echo esc_html($ip->blocked_at); ?>
                                </td>
                                <td data-colname="<?php esc_attr_e('Expires At', $args['text_domain']); ?>">
                                    <?php echo esc_html($ip->expires_at); ?>
                                </td>
                                <td data-colname="<?php esc_attr_e('Reason', $args['text_domain']); ?>">
                                    <?php echo esc_html($ip->reason); ?>
                                </td>
                                <td data-colname="<?php esc_attr_e('Actions', $args['text_domain']); ?>">
                                    <?php
                                    $unblock_url = wp_nonce_url(
                                        add_query_arg(
                                            array(
                                                'bfwp_action' => 'unblock_ip',
                                                'ip' => $ip->user_ip
                                            ),
                                            $base_url
                                        ),
                                        'blockforce_unblock_' . $ip->user_ip
                                    );
                                    ?>
                                    <a href="<?php echo esc_url($unblock_url); ?>" class="button button-small action-unblock">
                                        <?php esc_html_e('Unblock', $args['text_domain']); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>
    </div>
</div>

<script>
    jQuery(document).ready(function ($) {
        $('#cb-select-all-1').on('click', function () {
            $('input[name="blocked_ips[]"]').prop('checked', this.checked);
        });
    });
</script>