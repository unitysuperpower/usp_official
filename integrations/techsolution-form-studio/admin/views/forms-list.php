<?php
/**
 * View: Forms List Table with Real-time Analytics, Conversion Rates, Status Toggles, and Live Search.
 *
 * @package    TSFS_Plugin
 * @subpackage TSFS_Plugin/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$total_forms        = ! empty( $forms ) ? count( $forms ) : 0;
$total_submissions  = TSFS_DB::get_entry_count();
$total_views        = TSFS_DB::get_total_views();
$overall_conversion = $total_views > 0 ? round( ( $total_submissions / $total_views ) * 100, 1 ) : 0;

$count_live   = 0;
$count_draft  = 0;
$count_closed = 0;
if ( ! empty( $forms ) ) {
    foreach ( $forms as $f_item ) {
        $st = ! empty( $f_item['status'] ) ? $f_item['status'] : 'publish';
        if ( 'draft' === $st ) {
            $count_draft++;
        } elseif ( 'closed' === $st ) {
            $count_closed++;
        } else {
            $count_live++;
        }
    }
}
?>

<div class="wrap tsfs-admin-wrap">
    <!-- Top Header Bar -->
    <div class="tsfs-page-header tsfs-forms-manager-header">
        <div class="tsfs-header-title-group">
            <div class="tsfs-title-with-badge">
                <span class="tsfs-header-icon-box">
                    <span class="dashicons dashicons-feedback"></span>
                </span>
                <div>
                    <h1 class="tsfs-main-heading"><?php esc_html_e( 'Forms Manager', 'techsolution-form-studio' ); ?></h1>
                    <p class="tsfs-main-subheading"><?php esc_html_e( 'Manage your forms, track live impressions, analyze conversions, and deploy anywhere with shortcodes.', 'techsolution-form-studio' ); ?></p>
                </div>
            </div>
        </div>
        <div class="tsfs-header-actions-group">
            <button type="button" class="button tsfs-btn-header-secondary" id="tsfs-open-import-modal">
                <span class="dashicons dashicons-upload"></span>
                <span><?php esc_html_e( 'Import JSON', 'techsolution-form-studio' ); ?></span>
            </button>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=tsfs-builder' ) ); ?>" class="button button-primary tsfs-btn-header-primary">
                <span class="dashicons dashicons-plus-alt2"></span>
                <span><?php esc_html_e( 'Create New Form', 'techsolution-form-studio' ); ?></span>
            </a>
        </div>
    </div>

    <!-- Real-time Performance Analytics Cards Grid -->
    <div class="tsfs-analytics-kpi-grid">
        <!-- Total Forms -->
        <div class="tsfs-kpi-card">
            <div class="tsfs-kpi-icon-wrap is-indigo">
                <span class="dashicons dashicons-feedback"></span>
            </div>
            <div class="tsfs-kpi-content">
                <span class="tsfs-kpi-label"><?php esc_html_e( 'Total Forms', 'techsolution-form-studio' ); ?></span>
                <span class="tsfs-kpi-value"><?php echo esc_html( $total_forms ); ?></span>
                <span class="tsfs-kpi-hint"><?php esc_html_e( 'Available in database', 'techsolution-form-studio' ); ?></span>
            </div>
        </div>

        <!-- Total Impressions / Views -->
        <div class="tsfs-kpi-card">
            <div class="tsfs-kpi-icon-wrap is-emerald">
                <span class="dashicons dashicons-visibility"></span>
            </div>
            <div class="tsfs-kpi-content">
                <span class="tsfs-kpi-label"><?php esc_html_e( 'Total Impressions', 'techsolution-form-studio' ); ?></span>
                <span class="tsfs-kpi-value"><?php echo esc_html( number_format_i18n( $total_views ) ); ?></span>
                <span class="tsfs-kpi-hint"><?php esc_html_e( 'Frontend visits & loads', 'techsolution-form-studio' ); ?></span>
            </div>
        </div>

        <!-- Submissions Received -->
        <div class="tsfs-kpi-card">
            <div class="tsfs-kpi-icon-wrap is-blue">
                <span class="dashicons dashicons-email-alt"></span>
            </div>
            <div class="tsfs-kpi-content">
                <span class="tsfs-kpi-label"><?php esc_html_e( 'Submissions Received', 'techsolution-form-studio' ); ?></span>
                <span class="tsfs-kpi-value"><?php echo esc_html( number_format_i18n( $total_submissions ) ); ?></span>
                <span class="tsfs-kpi-hint"><?php esc_html_e( 'Completed entries stored', 'techsolution-form-studio' ); ?></span>
            </div>
        </div>

        <!-- Overall Conversion Rate -->
        <div class="tsfs-kpi-card">
            <div class="tsfs-kpi-icon-wrap is-amber">
                <span class="dashicons dashicons-chart-area"></span>
            </div>
            <div class="tsfs-kpi-content">
                <span class="tsfs-kpi-label"><?php esc_html_e( 'Conversion Rate', 'techsolution-form-studio' ); ?></span>
                <span class="tsfs-kpi-value"><?php echo esc_html( $overall_conversion ); ?>%</span>
                <span class="tsfs-kpi-hint"><?php esc_html_e( 'Submissions ÷ Impressions', 'techsolution-form-studio' ); ?></span>
            </div>
        </div>
    </div>

    <!-- Status Tabs & Live Search Toolbar -->
    <div class="tsfs-forms-toolbar-card">
        <!-- Status Filter Tabs -->
        <div class="tsfs-status-filter-pills" role="tablist">
            <button type="button" class="tsfs-status-pill-btn active" data-filter="all">
                <span><?php esc_html_e( 'All Forms', 'techsolution-form-studio' ); ?></span>
                <span class="tsfs-pill-counter" id="tsfs-count-tab-all"><?php echo esc_html( $total_forms ); ?></span>
            </button>
            <button type="button" class="tsfs-status-pill-btn" data-filter="publish">
                <span class="tsfs-status-dot is-live"></span>
                <span><?php esc_html_e( 'Live', 'techsolution-form-studio' ); ?></span>
                <span class="tsfs-pill-counter" id="tsfs-count-tab-live"><?php echo esc_html( $count_live ); ?></span>
            </button>
            <button type="button" class="tsfs-status-pill-btn" data-filter="draft">
                <span class="tsfs-status-dot is-draft"></span>
                <span><?php esc_html_e( 'Draft', 'techsolution-form-studio' ); ?></span>
                <span class="tsfs-pill-counter" id="tsfs-count-tab-draft"><?php echo esc_html( $count_draft ); ?></span>
            </button>
            <button type="button" class="tsfs-status-pill-btn" data-filter="closed">
                <span class="tsfs-status-dot is-closed"></span>
                <span><?php esc_html_e( 'Closed', 'techsolution-form-studio' ); ?></span>
                <span class="tsfs-pill-counter" id="tsfs-count-tab-closed"><?php echo esc_html( $count_closed ); ?></span>
            </button>
        </div>

        <!-- Live Search Input -->
        <div class="tsfs-forms-search-box-wrap">
            <span class="dashicons dashicons-search tsfs-search-icon"></span>
            <input type="search" id="tsfs-forms-search-box" placeholder="<?php esc_attr_e( 'Search by title, ID, or shortcode...', 'techsolution-form-studio' ); ?>" autocomplete="off" />
            <button type="button" id="tsfs-forms-search-clear" class="tsfs-search-clear-btn" style="display:none;" title="<?php esc_attr_e( 'Clear search query', 'techsolution-form-studio' ); ?>">&times;</button>
            <span class="tsfs-search-count-badge">
                <span id="tsfs-forms-count-visible"><?php echo esc_html( $total_forms ); ?></span> <?php esc_html_e( 'shown', 'techsolution-form-studio' ); ?>
            </span>
        </div>
    </div>

    <?php if ( empty( $forms ) ) : ?>
        <div class="tsfs-empty-state">
            <div class="tsfs-empty-icon dashicons dashicons-feedback"></div>
            <h2><?php esc_html_e( 'No Forms Created Yet', 'techsolution-form-studio' ); ?></h2>
            <p><?php esc_html_e( 'Get started by creating your first custom form using our visual drag-and-drop studio.', 'techsolution-form-studio' ); ?></p>
            <div style="display:flex; justify-content:center; gap:12px; margin-top:16px;">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=tsfs-builder' ) ); ?>" class="button button-primary button-hero">
                    <span class="dashicons dashicons-plus-alt2" style="vertical-align:middle; margin-top:-2px;"></span> <?php esc_html_e( 'Create Your First Form', 'techsolution-form-studio' ); ?>
                </a>
            </div>
        </div>
    <?php else : ?>
        <div class="tsfs-table-responsive-wrapper">
            <table class="wp-list-table widefat striped table-view-list tsfs-forms-table">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column column-id" style="width: 60px; min-width: 60px; text-align: center;"><?php esc_html_e( 'ID', 'techsolution-form-studio' ); ?></th>
                        <th scope="col" class="manage-column column-title column-primary" style="min-width: 240px;"><?php esc_html_e( 'Form Title', 'techsolution-form-studio' ); ?></th>
                        <th scope="col" class="manage-column column-status" style="width: 105px; min-width: 105px; text-align: center;"><?php esc_html_e( 'Status', 'techsolution-form-studio' ); ?></th>
                        <th scope="col" class="manage-column column-shortcode" style="width: 210px; min-width: 210px;"><?php esc_html_e( 'Embed Shortcode', 'techsolution-form-studio' ); ?></th>
                        <th scope="col" class="manage-column column-views" style="width: 85px; min-width: 85px; text-align: center;"><?php esc_html_e( 'Views', 'techsolution-form-studio' ); ?></th>
                        <th scope="col" class="manage-column column-entries" style="width: 110px; min-width: 110px; text-align: center;"><?php esc_html_e( 'Submissions', 'techsolution-form-studio' ); ?></th>
                        <th scope="col" class="manage-column column-conversion" style="width: 100px; min-width: 100px; text-align: center;"><?php esc_html_e( 'Conversion', 'techsolution-form-studio' ); ?></th>
                        <th scope="col" class="manage-column column-date" style="width: 115px; min-width: 115px;"><?php esc_html_e( 'Created', 'techsolution-form-studio' ); ?></th>
                        <th scope="col" class="manage-column column-actions" style="width: 130px; min-width: 130px; text-align: right;"><?php esc_html_e( 'Actions', 'techsolution-form-studio' ); ?></th>
                    </tr>
                </thead>
                <tbody id="tsfs-forms-table-body">
                    <?php foreach ( $forms as $form_item ) : 
                        $form_id         = absint( $form_item['id'] );
                        $form_views      = ! empty( $form_item['views'] ) ? absint( $form_item['views'] ) : 0;
                        $form_entries    = absint( $form_item['entry_count'] );
                        $form_status     = ! empty( $form_item['status'] ) ? $form_item['status'] : 'publish';
                        $conv_rate       = $form_views > 0 ? round( ( $form_entries / $form_views ) * 100, 1 ) : 0;
                        $edit_url        = admin_url( 'admin.php?page=tsfs-builder&id=' . $form_id );
                        $entries_url     = admin_url( 'admin.php?page=tsfs-entries&form_id=' . $form_id );
                        $shortcode       = '[tsfs_form id="' . $form_id . '"]';
                        $export_json_url = wp_nonce_url(
                            admin_url( 'admin-post.php?action=tsfs_export_form_json&form_id=' . $form_id ),
                            'tsfs_export_form_json',
                            'tsfs_nonce'
                        );
                    ?>
                        <tr id="tsfs-form-row-<?php echo esc_attr( $form_id ); ?>" class="tsfs-form-row" data-title="<?php echo esc_attr( strtolower( $form_item['title'] ) ); ?>" data-id="<?php echo esc_attr( $form_id ); ?>" data-status="<?php echo esc_attr( $form_status ); ?>" data-shortcode="<?php echo esc_attr( strtolower( $shortcode ) ); ?>">
                            <!-- ID -->
                            <td class="column-id" style="text-align: center; vertical-align: middle;">
                                <span class="tsfs-id-badge">#<?php echo esc_html( $form_id ); ?></span>
                            </td>

                            <!-- Form Title & Row Actions -->
                            <td class="title column-title has-row-actions column-primary" style="min-width: 240px; vertical-align: middle;">
                                <div class="tsfs-title-row">
                                    <a href="<?php echo esc_url( $edit_url ); ?>" class="tsfs-form-title-link">
                                        <?php echo esc_html( $form_item['title'] ); ?>
                                    </a>
                                </div>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit Studio', 'techsolution-form-studio' ); ?></a> | 
                                    </span>
                                    <span class="view">
                                        <a href="<?php echo esc_url( $entries_url ); ?>"><?php esc_html_e( 'Submissions', 'techsolution-form-studio' ); ?> (<?php echo esc_html( $form_entries ); ?>)</a> | 
                                    </span>
                                    <span class="duplicate">
                                        <a href="#" class="tsfs-duplicate-form-btn" data-id="<?php echo esc_attr( $form_id ); ?>"><?php esc_html_e( 'Duplicate', 'techsolution-form-studio' ); ?></a> | 
                                    </span>
                                    <span class="export">
                                        <a href="<?php echo esc_url( $export_json_url ); ?>"><?php esc_html_e( 'Export JSON', 'techsolution-form-studio' ); ?></a> | 
                                    </span>
                                    <span class="trash">
                                        <a href="#" class="tsfs-delete-form-btn" data-id="<?php echo esc_attr( $form_id ); ?>" style="color: #ef4444;"><?php esc_html_e( 'Delete', 'techsolution-form-studio' ); ?></a>
                                    </span>
                                </div>
                            </td>

                            <!-- Status Toggle -->
                            <td class="column-status" style="text-align: center; vertical-align: middle;">
                                <button type="button" class="tsfs-form-status-toggle" data-id="<?php echo esc_attr( $form_id ); ?>" data-status="<?php echo esc_attr( $form_status ); ?>" title="<?php esc_attr_e( 'Click to toggle: Live / Draft / Closed', 'techsolution-form-studio' ); ?>">
                                    <?php if ( 'draft' === $form_status ) : ?>
                                        <span class="tsfs-status-pill is-draft">
                                            <span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'Draft', 'techsolution-form-studio' ); ?>
                                        </span>
                                    <?php elseif ( 'closed' === $form_status ) : ?>
                                        <span class="tsfs-status-pill is-closed">
                                            <span class="dashicons dashicons-lock"></span> <?php esc_html_e( 'Closed', 'techsolution-form-studio' ); ?>
                                        </span>
                                    <?php else : ?>
                                        <span class="tsfs-status-pill is-live">
                                            <span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Live', 'techsolution-form-studio' ); ?>
                                        </span>
                                    <?php endif; ?>
                                </button>
                            </td>

                            <!-- Embed Shortcode -->
                            <td class="column-shortcode" style="vertical-align: middle;">
                                <div class="tsfs-shortcode-copy-box">
                                    <code><?php echo esc_html( $shortcode ); ?></code>
                                    <button type="button" class="tsfs-copy-btn" data-clipboard="<?php echo esc_attr( $shortcode ); ?>" title="<?php esc_attr_e( 'Copy Shortcode', 'techsolution-form-studio' ); ?>">
                                        <span class="dashicons dashicons-admin-page"></span>
                                    </button>
                                </div>
                            </td>

                            <!-- Impressions (Views) -->
                            <td class="column-views" style="text-align: center; vertical-align: middle;">
                                <span class="tsfs-metric-num"><?php echo esc_html( number_format_i18n( $form_views ) ); ?></span>
                            </td>

                            <!-- Submissions Count -->
                            <td class="column-entries" style="text-align: center; vertical-align: middle;">
                                <a href="<?php echo esc_url( $entries_url ); ?>" class="tsfs-submissions-count-pill" title="<?php esc_attr_e( 'View form submissions', 'techsolution-form-studio' ); ?>">
                                    <span class="dashicons dashicons-email-alt"></span>
                                    <span><?php echo esc_html( number_format_i18n( $form_entries ) ); ?></span>
                                </a>
                            </td>

                            <!-- Conversion Rate -->
                            <td class="column-conversion" style="text-align: center; vertical-align: middle;">
                                <?php
                                $pill_class = 'is-zero';
                                if ( $conv_rate >= 15 ) {
                                    $pill_class = 'is-high';
                                } elseif ( $conv_rate > 0 ) {
                                    $pill_class = 'is-active';
                                }
                                ?>
                                <span class="tsfs-conversion-pill <?php echo esc_attr( $pill_class ); ?>">
                                    <?php echo esc_html( $conv_rate ); ?>%
                                </span>
                            </td>

                            <!-- Created Date -->
                            <td class="column-date" style="vertical-align: middle;">
                                <span class="tsfs-date-text"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $form_item['created_at'] ) ) ); ?></span>
                            </td>

                            <!-- Quick Action Buttons -->
                            <td class="column-actions" style="text-align: right; vertical-align: middle;">
                                <div class="tsfs-row-action-btns">
                                    <a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small tsfs-action-btn-edit" title="<?php esc_attr_e( 'Open Visual Studio', 'techsolution-form-studio' ); ?>">
                                        <span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'Edit', 'techsolution-form-studio' ); ?>
                                    </a>
                                    <button type="button" class="button button-small tsfs-duplicate-form-btn" data-id="<?php echo esc_attr( $form_id ); ?>" title="<?php esc_attr_e( 'Duplicate Form', 'techsolution-form-studio' ); ?>">
                                        <span class="dashicons dashicons-admin-page"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <!-- No matching forms state for search/filter -->
                    <tr id="tsfs-no-forms-match-row" style="display:none;">
                        <td colspan="9" style="text-align:center; padding:54px 20px;">
                            <span class="dashicons dashicons-search" style="font-size:38px; width:38px; height:38px; color:var(--tsfs-adm-slate-400); margin-bottom:8px;"></span>
                            <h4 style="margin:4px 0 6px 0; font-size:16px; font-weight:700; color:var(--tsfs-adm-slate-800);"><?php esc_html_e( 'No Matching Forms Found', 'techsolution-form-studio' ); ?></h4>
                            <p style="margin:0 0 16px 0; font-size:13px; color:var(--tsfs-adm-slate-500);"><?php esc_html_e( 'No forms match your active search keyword or status filter.', 'techsolution-form-studio' ); ?></p>
                            <button type="button" class="button button-secondary" id="tsfs-reset-filter-btn" style="border-radius:6px; font-weight:600;">
                                <span class="dashicons dashicons-undo" style="vertical-align:middle; margin-top:-2px;"></span> <?php esc_html_e( 'Reset Filters', 'techsolution-form-studio' ); ?>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Import Form Modal -->
<div id="tsfs-import-modal" class="tsfs-modal-backdrop" style="display:none;">
    <div class="tsfs-modal-dialog" style="max-width: 560px;">
        <div class="tsfs-modal-header">
            <h3><span class="dashicons dashicons-upload" style="vertical-align:middle; color:var(--tsfs-adm-primary);"></span> <?php esc_html_e( 'Import Form (JSON)', 'techsolution-form-studio' ); ?></h3>
            <button type="button" class="tsfs-modal-close" id="tsfs-import-close-btn">&times;</button>
        </div>
        <div class="tsfs-modal-body" style="padding: 24px;">
            <p style="color:var(--tsfs-adm-slate-600); margin-top:0; font-size:13.5px;">
                <?php esc_html_e( 'Upload an exported Form Studio (.json) file or paste schema code below to instantly create a new form.', 'techsolution-form-studio' ); ?>
            </p>

            <div style="margin-bottom: 16px;">
                <label for="tsfs-import-file-input" style="display:block; font-size:11.5px; font-weight:700; color:var(--tsfs-adm-slate-700); text-transform:uppercase; margin-bottom:6px; letter-spacing:0.5px;">
                    <?php esc_html_e( 'Upload JSON File:', 'techsolution-form-studio' ); ?>
                </label>
                <input type="file" id="tsfs-import-file-input" accept=".json,application/json" style="width:100%; border:1.5px dashed var(--tsfs-adm-slate-300); border-radius:8px; padding:12px; background:#fff;">
            </div>

            <div style="text-align:center; font-size:11px; color:var(--tsfs-adm-slate-400); font-weight:700; margin: 12px 0;">— OR —</div>

            <div>
                <label for="tsfs-import-json-textarea" style="display:block; font-size:11.5px; font-weight:700; color:var(--tsfs-adm-slate-700); text-transform:uppercase; margin-bottom:6px; letter-spacing:0.5px;">
                    <?php esc_html_e( 'Paste JSON Code:', 'techsolution-form-studio' ); ?>
                </label>
                <textarea id="tsfs-import-json-textarea" rows="4" placeholder='{"title":"...","fields_data":[...]}' style="width:100%; font-family:monospace; font-size:11.5px; border-radius:6px; border:1.5px solid var(--tsfs-adm-slate-300); padding:8px;"></textarea>
            </div>
        </div>
        <div class="tsfs-modal-footer" style="display:flex; justify-content:flex-end; gap:8px;">
            <button type="button" class="button button-secondary" id="tsfs-import-cancel-btn"><?php esc_html_e( 'Cancel', 'techsolution-form-studio' ); ?></button>
            <button type="button" class="button button-primary" id="tsfs-import-submit-btn" style="font-weight:600;">
                <span class="dashicons dashicons-upload" style="vertical-align:middle; margin-top:-2px;"></span> <?php esc_html_e( 'Import & Open Builder', 'techsolution-form-studio' ); ?>
            </button>
        </div>
    </div>
</div>
