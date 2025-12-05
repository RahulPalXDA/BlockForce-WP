<?php
/**
 * Blocklist Tab Display
 *
 * @package BlockForce_WP
 * @subpackage Admin\Tabs
 */

if (!defined('ABSPATH')) {
    exit;
}

class BlockForce_WP_Tab_Blocklist
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
     * Render the Blocklist tab
     */
    public function render()
    {
        if (!class_exists('BlockForce_WP_Blocklist')) {
            echo '<div class="error"><p>' . esc_html__('Blocklist module not loaded.', $this->text_domain) . '</p></div>';
            return;
        }

        $blocklist = new BlockForce_WP_Blocklist($this->settings, $this->core);
        $sync_status = $blocklist->get_sync_status();

        // Handle Search and Pagination
        $paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        $paged = max(1, $paged);
        $search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $source_filter = isset($_GET['source_filter']) ? sanitize_key($_GET['source_filter']) : '';
        $per_page = 50;

        $args = array(
            'limit' => $per_page,
            'offset' => ($paged - 1) * $per_page,
            'search' => $search_query,
            'source' => $source_filter
        );

        $data = $blocklist->get_ips($args);
        $total_pages = $data['pages'];

        ?>
        <div class="blockforce-card">
            <h2><?php esc_html_e('Global Blocklist Manager', $this->text_domain); ?></h2>
            <p><?php esc_html_e('Manage and view the global blocklist database.', $this->text_domain); ?></p>

            <?php settings_errors('blockforce_settings'); ?>

            <!-- Stats Bar -->
            <div class="blockforce-status-box blockforce-status-default"
                style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <strong><?php esc_html_e('Total IPs:', $this->text_domain); ?></strong>
                    <?php echo number_format_i18n($data['total']); ?>
                    <span style="margin: 0 10px; color: #ccc;">|</span>
                    <strong><?php esc_html_e('Last Sync:', $this->text_domain); ?></strong>
                    <?php echo esc_html($sync_status['last_sync']); ?>
                    <?php if ($sync_status['count'] > 0): ?>
                        <span
                            style="color: #666; font-size: 12px;">(<?php echo sprintf(__('%s IPs fetched', $this->text_domain), number_format_i18n($sync_status['count'])); ?>)</span>
                    <?php endif; ?>
                </div>

                <form method="post" action="">
                    <?php wp_nonce_field('bfwp_blocklist_sync', 'bfwp_blocklist_nonce'); ?>
                    <button type="submit" name="bfwp_blocklist_sync" class="button button-secondary">
                        <span class="dashicons dashicons-update" style="margin-top: 3px;"></span>
                        <?php esc_html_e('Sync Now', $this->text_domain); ?>
                    </button>
                </form>
            </div>

            <!-- Add IP Form -->
            <div style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; margin-bottom: 20px;">
                <form method="post" action="" style="display: flex; align-items: center; gap: 10px;">
                    <?php wp_nonce_field('bfwp_blocklist_action', 'bfwp_blocklist_nonce'); ?>
                    <label><strong><?php esc_html_e('Add Manual IP:', $this->text_domain); ?></strong></label>
                    <input type="text" name="manual_ip" placeholder="192.168.1.1" required style="min-width: 250px;">
                    <button type="submit" name="bfwp_blocklist_add" class="button button-primary">
                        <?php esc_html_e('Add to Blocklist', $this->text_domain); ?>
                    </button>
                    <span class="description"
                        style="margin-left: 10px;"><?php esc_html_e('Manually added IPs are NOT removed during daily sync.', $this->text_domain); ?></span>
                </form>
            </div>

            <!-- Search and Filter -->
            <form method="get" action="">
                <input type="hidden" name="page" value="blockforce-wp">
                <input type="hidden" name="tab" value="blocklist">
                <div class="tablenav top" style="margin-top: 0;">
                    <div class="alignleft actions">
                        <select name="source_filter">
                            <option value="" <?php selected($source_filter, ''); ?>>
                                <?php esc_html_e('All Sources', $this->text_domain); ?>
                            </option>
                            <option value="manual" <?php selected($source_filter, 'manual'); ?>>
                                <?php esc_html_e('Manual', $this->text_domain); ?>
                            </option>
                            <option value="auto" <?php selected($source_filter, 'auto'); ?>>
                                <?php esc_html_e('Auto (Sync)', $this->text_domain); ?>
                            </option>
                        </select>
                        <input type="submit" name="filter_action" id="post-query-submit" class="button"
                            value="<?php esc_attr_e('Filter', $this->text_domain); ?>">
                    </div>

                    <p class="search-box">
                        <label class="screen-reader-text"
                            for="tag-search-input"><?php esc_html_e('Search IPs:', $this->text_domain); ?></label>
                        <input type="search" id="tag-search-input" name="s" value="<?php echo esc_attr($search_query); ?>">
                        <input type="submit" id="search-submit" class="button"
                            value="<?php esc_attr_e('Search IPs', $this->text_domain); ?>">
                    </p>
                </div>
            </form>

            <?php $this->render_table($data, $search_query, $source_filter, $total_pages, $paged); ?>
        </div>
        <?php
    }

    /**
     * Render the IPs table
     */
    private function render_table($data, $search_query, $source_filter, $total_pages, $paged)
    {
        // Enhanced pagination with page number boxes
        $page_links = paginate_links(array(
            'base' => add_query_arg('paged', '%#%'),
            'format' => '',
            'prev_text' => '&laquo; ' . __('Prev', $this->text_domain),
            'next_text' => __('Next', $this->text_domain) . ' &raquo;',
            'first_text' => '&laquo;&laquo;',
            'last_text' => '&raquo;&raquo;',
            'total' => $total_pages,
            'current' => $paged,
            'show_all' => false,
            'end_size' => 1,
            'mid_size' => 2,
            'type' => 'plain',
            'add_args' => array_filter(array(
                's' => $search_query,
                'source_filter' => $source_filter
            ))
        ));
        ?>
        <style>
            .blockforce-pagination {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 5px;
                margin: 15px 0;
            }

            .blockforce-pagination a,
            .blockforce-pagination span.current {
                display: inline-block;
                padding: 8px 12px;
                text-decoration: none;
                border: 1px solid #ddd;
                border-radius: 4px;
                background: #fff;
                color: #0073aa;
                font-weight: 500;
                min-width: 40px;
                text-align: center;
                transition: all 0.2s ease;
            }

            .blockforce-pagination a:hover {
                background: #0073aa;
                color: #fff;
                border-color: #0073aa;
            }

            .blockforce-pagination span.current {
                background: #0073aa;
                color: #fff;
                border-color: #0073aa;
            }

            .blockforce-pagination .dots {
                padding: 8px 5px;
                color: #666;
            }

            .blockforce-pagination .prev-next {
                padding: 8px 15px;
            }
        </style>

        <div class="tablenav top">
            <div class="tablenav-pages"
                style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                <span
                    class="displaying-num"><?php echo sprintf(_n('%s item', '%s items', $data['total'], $this->text_domain), number_format_i18n($data['total'])); ?></span>
                <?php if ($total_pages > 1): ?>
                    <div class="blockforce-pagination">
                        <?php echo $page_links; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped table-view-list">
            <thead>
                <tr>
                    <th style="width: 40%;"><?php esc_html_e('IP Address', $this->text_domain); ?></th>
                    <th style="width: 20%;"><?php esc_html_e('Source', $this->text_domain); ?></th>
                    <th style="width: 25%;"><?php esc_html_e('Date Added', $this->text_domain); ?></th>
                    <th style="width: 15%;"><?php esc_html_e('Actions', $this->text_domain); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($data['items'])): ?>
                    <?php foreach ($data['items'] as $item): ?>
                        <tr>
                            <td><strong><?php echo esc_html($item['ip']); ?></strong></td>
                            <td>
                                <?php if ($item['source'] === 'manual'): ?>
                                    <span
                                        class="blockforce-badge blockforce-badge-enabled"><?php esc_html_e('MANUAL', $this->text_domain); ?></span>
                                <?php else: ?>
                                    <span class="blockforce-badge"
                                        style="background: #eee; color: #666; border-color: #ddd;"><?php esc_html_e('AUTO', $this->text_domain); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($item['created_at']); ?></td>
                            <td>
                                <?php if ($item['source'] === 'manual'):
                                    $del_args = array('page' => 'blockforce-wp', 'tab' => 'blocklist', 'action' => 'delete_ip', 'id' => $item['id']);
                                    if (!empty($search_query))
                                        $del_args['s'] = $search_query;
                                    if (!empty($source_filter))
                                        $del_args['source_filter'] = $source_filter;
                                    $delete_url = wp_nonce_url(add_query_arg($del_args, admin_url('options-general.php')), 'delete_ip_' . $item['id']);
                                    ?>
                                    <a href="<?php echo esc_url($delete_url); ?>"
                                        onclick="return confirm('<?php esc_attr_e('Delete this IP?', $this->text_domain); ?>')"
                                        style="color: #a00;">
                                        <?php esc_html_e('Delete', $this->text_domain); ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color: #ccc;"><?php esc_html_e('Managed by Sync', $this->text_domain); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4"><?php esc_html_e('No IPs found in the blocklist.', $this->text_domain); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="tablenav bottom">
            <?php if ($total_pages > 1): ?>
                <div class="blockforce-pagination">
                    <?php echo $page_links; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
