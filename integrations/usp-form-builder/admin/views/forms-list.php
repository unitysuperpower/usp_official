<?php
/**
 * View: Forms List Table with Real-time Analytics, Conversion Rates, Status Toggles, and Live Search.
 *
 * @package    USP_Form_Builder
 * @subpackage USP_Form_Builder/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$total_forms        = ! empty( $forms ) ? count( $forms ) : 0;
$total_submissions  = USP_DB::get_entry_count();
$total_views        = USP_DB::get_total_views();
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

<div class="wrap usp-admin-wrap">
    <!-- Top Header Bar -->
    <div class="usp-page-header usp-forms-manager-header">
        <div class="usp-header-title-group">
            <div class="usp-title-with-badge">
                <span class="usp-header-icon-box">
                    <span class="dashicons dashicons-feedback"></span>
                </span>
                <div>
                    <h1 class="usp-main-heading"><?php esc_html_e( 'Forms Manager', 'usp-form-builder' ); ?></h1>
                    <p class="usp-main-subheading"><?php esc_html_e( 'Manage your forms, track live impressions, analyze conversions, and deploy anywhere with shortcodes.', 'usp-form-builder' ); ?></p>
                </div>
            </div>
        </div>
        <div class="usp-header-actions-group">
            <button type="button" class="button usp-btn-header-secondary" id="usp-open-import-modal">
                <span class="dashicons dashicons-upload"></span>
                <span><?php esc_html_e( 'Import JSON', 'usp-form-builder' ); ?></span>
            </button>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=usp-form-builder-new' ) ); ?>" class="button button-primary usp-btn-header-primary">
                <span class="dashicons dashicons-plus-alt2"></span>
                <span><?php esc_html_e( 'Create New Form', 'usp-form-builder' ); ?></span>
            </a>
            <?php if ( USP_Security::is_pro_active() ) : ?>
                <span class="usp-badge-pro-pill" title="<?php esc_attr_e( 'USP Form Builder Pro is active', 'usp-form-builder' ); ?>">
                    <span class="dashicons dashicons-star-filled"></span> PRO
                </span>
            <?php else : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=usp-form-pro' ) ); ?>" class="usp-badge-free-pill" title="<?php esc_attr_e( 'Upgrade to Pro Edition', 'usp-form-builder' ); ?>">
                    <span class="dashicons dashicons-star-empty"></span> <?php esc_html_e( 'Get Pro', 'usp-form-builder' ); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Real-time Performance Analytics Cards Grid -->
    <div class="usp-analytics-kpi-grid">
        <!-- Total Forms -->
        <div class="usp-kpi-card">
            <div class="usp-kpi-icon-wrap is-indigo">
                <span class="dashicons dashicons-feedback"></span>
            </div>
            <div class="usp-kpi-content">
                <span class="usp-kpi-label"><?php esc_html_e( 'Total Forms', 'usp-form-builder' ); ?></span>
                <span class="usp-kpi-value"><?php echo esc_html( $total_forms ); ?></span>
                <span class="usp-kpi-hint"><?php esc_html_e( 'Available in database', 'usp-form-builder' ); ?></span>
            </div>
        </div>

        <!-- Total Impressions / Views -->
        <div class="usp-kpi-card">
            <div class="usp-kpi-icon-wrap is-emerald">
                <span class="dashicons dashicons-visibility"></span>
            </div>
            <div class="usp-kpi-content">
                <span class="usp-kpi-label"><?php esc_html_e( 'Total Impressions', 'usp-form-builder' ); ?></span>
                <span class="usp-kpi-value"><?php echo esc_html( number_format_i18n( $total_views ) ); ?></span>
                <span class="usp-kpi-hint"><?php esc_html_e( 'Frontend visits & loads', 'usp-form-builder' ); ?></span>
            </div>
        </div>

        <!-- Submissions Received -->
        <div class="usp-kpi-card">
            <div class="usp-kpi-icon-wrap is-blue">
                <span class="dashicons dashicons-email-alt"></span>
            </div>
            <div class="usp-kpi-content">
                <span class="usp-kpi-label"><?php esc_html_e( 'Submissions Received', 'usp-form-builder' ); ?></span>
                <span class="usp-kpi-value"><?php echo esc_html( number_format_i18n( $total_submissions ) ); ?></span>
                <span class="usp-kpi-hint"><?php esc_html_e( 'Completed entries stored', 'usp-form-builder' ); ?></span>
            </div>
        </div>

        <!-- Overall Conversion Rate -->
        <div class="usp-kpi-card">
            <div class="usp-kpi-icon-wrap is-amber">
                <span class="dashicons dashicons-chart-area"></span>
            </div>
            <div class="usp-kpi-content">
                <span class="usp-kpi-label"><?php esc_html_e( 'Conversion Rate', 'usp-form-builder' ); ?></span>
                <span class="usp-kpi-value"><?php echo esc_html( $overall_conversion ); ?>%</span>
                <span class="usp-kpi-hint"><?php esc_html_e( 'Submissions ÷ Impressions', 'usp-form-builder' ); ?></span>
            </div>
        </div>
    </div>

    <!-- Status Tabs & Live Search Toolbar -->
    <div class="usp-forms-toolbar-card">
        <!-- Status Filter Tabs -->
        <div class="usp-status-filter-pills" role="tablist">
            <button type="button" class="usp-status-pill-btn active" data-filter="all">
                <span><?php esc_html_e( 'All Forms', 'usp-form-builder' ); ?></span>
                <span class="usp-pill-counter" id="usp-count-tab-all"><?php echo esc_html( $total_forms ); ?></span>
            </button>
            <button type="button" class="usp-status-pill-btn" data-filter="publish">
                <span class="usp-status-dot is-live"></span>
                <span><?php esc_html_e( 'Live', 'usp-form-builder' ); ?></span>
                <span class="usp-pill-counter" id="usp-count-tab-live"><?php echo esc_html( $count_live ); ?></span>
            </button>
            <button type="button" class="usp-status-pill-btn" data-filter="draft">
                <span class="usp-status-dot is-draft"></span>
                <span><?php esc_html_e( 'Draft', 'usp-form-builder' ); ?></span>
                <span class="usp-pill-counter" id="usp-count-tab-draft"><?php echo esc_html( $count_draft ); ?></span>
            </button>
            <button type="button" class="usp-status-pill-btn" data-filter="closed">
                <span class="usp-status-dot is-closed"></span>
                <span><?php esc_html_e( 'Closed', 'usp-form-builder' ); ?></span>
                <span class="usp-pill-counter" id="usp-count-tab-closed"><?php echo esc_html( $count_closed ); ?></span>
            </button>
        </div>

        <!-- Live Search Input -->
        <div class="usp-forms-search-box-wrap">
            <span class="dashicons dashicons-search usp-search-icon"></span>
            <input type="search" id="usp-forms-search-box" placeholder="<?php esc_attr_e( 'Search by title, ID, or shortcode...', 'usp-form-builder' ); ?>" autocomplete="off" />
            <button type="button" id="usp-forms-search-clear" class="usp-search-clear-btn" style="display:none;" title="<?php esc_attr_e( 'Clear search query', 'usp-form-builder' ); ?>">&times;</button>
            <span class="usp-search-count-badge">
                <span id="usp-forms-count-visible"><?php echo esc_html( $total_forms ); ?></span> <?php esc_html_e( 'shown', 'usp-form-builder' ); ?>
            </span>
        </div>
    </div>

    <?php if ( empty( $forms ) ) : ?>
        <div class="usp-empty-state">
            <div class="usp-empty-icon dashicons dashicons-feedback"></div>
            <h2><?php esc_html_e( 'No Forms Created Yet', 'usp-form-builder' ); ?></h2>
            <p><?php esc_html_e( 'Get started by creating your first custom form using our visual drag-and-drop studio.', 'usp-form-builder' ); ?></p>
            <div style="display:flex; justify-content:center; gap:12px; margin-top:16px;">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=usp-form-builder-new' ) ); ?>" class="button button-primary button-hero">
                    <span class="dashicons dashicons-plus-alt2" style="vertical-align:middle; margin-top:-2px;"></span> <?php esc_html_e( 'Create Your First Form', 'usp-form-builder' ); ?>
                </a>
            </div>
        </div>
    <?php else : ?>
        <div class="usp-table-responsive-wrapper">
            <table class="wp-list-table widefat striped table-view-list usp-forms-table">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column column-id" style="width: 60px; min-width: 60px; text-align: center;"><?php esc_html_e( 'ID', 'usp-form-builder' ); ?></th>
                        <th scope="col" class="manage-column column-title column-primary" style="min-width: 240px;"><?php esc_html_e( 'Form Title', 'usp-form-builder' ); ?></th>
                        <th scope="col" class="manage-column column-status" style="width: 105px; min-width: 105px; text-align: center;"><?php esc_html_e( 'Status', 'usp-form-builder' ); ?></th>
                        <th scope="col" class="manage-column column-shortcode" style="width: 210px; min-width: 210px;"><?php esc_html_e( 'Embed Shortcode', 'usp-form-builder' ); ?></th>
                        <th scope="col" class="manage-column column-views" style="width: 85px; min-width: 85px; text-align: center;"><?php esc_html_e( 'Views', 'usp-form-builder' ); ?></th>
                        <th scope="col" class="manage-column column-entries" style="width: 110px; min-width: 110px; text-align: center;"><?php esc_html_e( 'Submissions', 'usp-form-builder' ); ?></th>
                        <th scope="col" class="manage-column column-conversion" style="width: 100px; min-width: 100px; text-align: center;"><?php esc_html_e( 'Conversion', 'usp-form-builder' ); ?></th>
                        <th scope="col" class="manage-column column-date" style="width: 115px; min-width: 115px;"><?php esc_html_e( 'Created', 'usp-form-builder' ); ?></th>
                        <th scope="col" class="manage-column column-actions" style="width: 130px; min-width: 130px; text-align: right;"><?php esc_html_e( 'Actions', 'usp-form-builder' ); ?></th>
                    </tr>
                </thead>
                <tbody id="usp-forms-table-body">
                    <?php foreach ( $forms as $form_item ) : 
                        $form_id         = absint( $form_item['id'] );
                        $form_views      = ! empty( $form_item['views'] ) ? absint( $form_item['views'] ) : 0;
                        $form_entries    = absint( $form_item['entry_count'] );
                        $form_status     = ! empty( $form_item['status'] ) ? $form_item['status'] : 'publish';
                        $conv_rate       = $form_views > 0 ? round( ( $form_entries / $form_views ) * 100, 1 ) : 0;
                        $edit_url        = admin_url( 'admin.php?page=usp-form-builder-new&id=' . $form_id );
                        $entries_url     = admin_url( 'admin.php?page=usp-form-entries&form_id=' . $form_id );
                        $shortcode       = '[usp_form id="' . $form_id . '"]';
                        $export_json_url = wp_nonce_url(
                            admin_url( 'admin-post.php?action=usp_export_form_json&form_id=' . $form_id ),
                            'usp_export_form_json',
                            'usp_nonce'
                        );
                    ?>
                        <tr id="usp-form-row-<?php echo esc_attr( $form_id ); ?>" class="usp-form-row" data-title="<?php echo esc_attr( strtolower( $form_item['title'] ) ); ?>" data-id="<?php echo esc_attr( $form_id ); ?>" data-status="<?php echo esc_attr( $form_status ); ?>" data-shortcode="<?php echo esc_attr( strtolower( $shortcode ) ); ?>">
                            <!-- ID -->
                            <td class="column-id" style="text-align: center; vertical-align: middle;">
                                <span class="usp-id-badge">#<?php echo esc_html( $form_id ); ?></span>
                            </td>

                            <!-- Form Title & Row Actions -->
                            <td class="title column-title has-row-actions column-primary" style="min-width: 240px; vertical-align: middle;">
                                <div class="usp-title-row">
                                    <a href="<?php echo esc_url( $edit_url ); ?>" class="usp-form-title-link">
                                        <?php echo esc_html( $form_item['title'] ); ?>
                                    </a>
                                </div>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit Studio', 'usp-form-builder' ); ?></a> | 
                                    </span>
                                    <span class="view">
                                        <a href="<?php echo esc_url( $entries_url ); ?>"><?php esc_html_e( 'Submissions', 'usp-form-builder' ); ?> (<?php echo esc_html( $form_entries ); ?>)</a> | 
                                    </span>
                                    <span class="duplicate">
                                        <a href="#" class="usp-duplicate-form-btn" data-id="<?php echo esc_attr( $form_id ); ?>"><?php esc_html_e( 'Duplicate', 'usp-form-builder' ); ?></a> | 
                                    </span>
                                    <span class="export">
                                        <a href="<?php echo esc_url( $export_json_url ); ?>"><?php esc_html_e( 'Export JSON', 'usp-form-builder' ); ?></a> | 
                                    </span>
                                    <span class="trash">
                                        <a href="#" class="usp-delete-form-btn" data-id="<?php echo esc_attr( $form_id ); ?>" style="color: #ef4444;"><?php esc_html_e( 'Delete', 'usp-form-builder' ); ?></a>
                                    </span>
                                </div>
                            </td>

                            <!-- Status Toggle -->
                            <td class="column-status" style="text-align: center; vertical-align: middle;">
                                <button type="button" class="usp-form-status-toggle" data-id="<?php echo esc_attr( $form_id ); ?>" data-status="<?php echo esc_attr( $form_status ); ?>" title="<?php esc_attr_e( 'Click to toggle: Live / Draft / Closed', 'usp-form-builder' ); ?>">
                                    <?php if ( 'draft' === $form_status ) : ?>
                                        <span class="usp-status-pill is-draft">
                                            <span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'Draft', 'usp-form-builder' ); ?>
                                        </span>
                                    <?php elseif ( 'closed' === $form_status ) : ?>
                                        <span class="usp-status-pill is-closed">
                                            <span class="dashicons dashicons-lock"></span> <?php esc_html_e( 'Closed', 'usp-form-builder' ); ?>
                                        </span>
                                    <?php else : ?>
                                        <span class="usp-status-pill is-live">
                                            <span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Live', 'usp-form-builder' ); ?>
                                        </span>
                                    <?php endif; ?>
                                </button>
                            </td>

                            <!-- Embed Shortcode -->
                            <td class="column-shortcode" style="vertical-align: middle;">
                                <div class="usp-shortcode-copy-box">
                                    <code><?php echo esc_html( $shortcode ); ?></code>
                                    <button type="button" class="usp-copy-btn" data-clipboard="<?php echo esc_attr( $shortcode ); ?>" title="<?php esc_attr_e( 'Copy Shortcode', 'usp-form-builder' ); ?>">
                                        <span class="dashicons dashicons-admin-page"></span>
                                    </button>
                                </div>
                            </td>

                            <!-- Impressions (Views) -->
                            <td class="column-views" style="text-align: center; vertical-align: middle;">
                                <span class="usp-metric-num"><?php echo esc_html( number_format_i18n( $form_views ) ); ?></span>
                            </td>

                            <!-- Submissions Count -->
                            <td class="column-entries" style="text-align: center; vertical-align: middle;">
                                <a href="<?php echo esc_url( $entries_url ); ?>" class="usp-submissions-count-pill" title="<?php esc_attr_e( 'View form submissions', 'usp-form-builder' ); ?>">
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
                                <span class="usp-conversion-pill <?php echo esc_attr( $pill_class ); ?>">
                                    <?php echo esc_html( $conv_rate ); ?>%
                                </span>
                            </td>

                            <!-- Created Date -->
                            <td class="column-date" style="vertical-align: middle;">
                                <span class="usp-date-text"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $form_item['created_at'] ) ) ); ?></span>
                            </td>

                            <!-- Quick Action Buttons -->
                            <td class="column-actions" style="text-align: right; vertical-align: middle;">
                                <div class="usp-row-action-btns">
                                    <a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small usp-action-btn-edit" title="<?php esc_attr_e( 'Open Visual Studio', 'usp-form-builder' ); ?>">
                                        <span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'Edit', 'usp-form-builder' ); ?>
                                    </a>
                                    <button type="button" class="button button-small usp-duplicate-form-btn" data-id="<?php echo esc_attr( $form_id ); ?>" title="<?php esc_attr_e( 'Duplicate Form', 'usp-form-builder' ); ?>">
                                        <span class="dashicons dashicons-admin-page"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <!-- No matching forms state for search/filter -->
                    <tr id="usp-no-forms-match-row" style="display:none;">
                        <td colspan="9" style="text-align:center; padding:54px 20px;">
                            <span class="dashicons dashicons-search" style="font-size:38px; width:38px; height:38px; color:var(--usp-adm-slate-400); margin-bottom:8px;"></span>
                            <h4 style="margin:4px 0 6px 0; font-size:16px; font-weight:700; color:var(--usp-adm-slate-800);"><?php esc_html_e( 'No Matching Forms Found', 'usp-form-builder' ); ?></h4>
                            <p style="margin:0 0 16px 0; font-size:13px; color:var(--usp-adm-slate-500);"><?php esc_html_e( 'No forms match your active search keyword or status filter.', 'usp-form-builder' ); ?></p>
                            <button type="button" class="button button-secondary" id="usp-reset-filter-btn" style="border-radius:6px; font-weight:600;">
                                <span class="dashicons dashicons-undo" style="vertical-align:middle; margin-top:-2px;"></span> <?php esc_html_e( 'Reset Filters', 'usp-form-builder' ); ?>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Import Form Modal -->
<div id="usp-import-modal" class="usp-modal-backdrop" style="display:none;">
    <div class="usp-modal-dialog" style="max-width: 560px;">
        <div class="usp-modal-header">
            <h3><span class="dashicons dashicons-upload" style="vertical-align:middle; color:var(--usp-adm-primary);"></span> <?php esc_html_e( 'Import Form (JSON)', 'usp-form-builder' ); ?></h3>
            <button type="button" class="usp-modal-close" id="usp-import-close-btn">&times;</button>
        </div>
        <div class="usp-modal-body" style="padding: 24px;">
            <p style="color:var(--usp-adm-slate-600); margin-top:0; font-size:13.5px;">
                <?php esc_html_e( 'Upload an exported USP Form (.json) file or paste schema code below to instantly create a new form.', 'usp-form-builder' ); ?>
            </p>

            <div style="margin-bottom: 16px;">
                <label for="usp-import-file-input" style="display:block; font-size:11.5px; font-weight:700; color:var(--usp-adm-slate-700); text-transform:uppercase; margin-bottom:6px; letter-spacing:0.5px;">
                    <?php esc_html_e( 'Upload JSON File:', 'usp-form-builder' ); ?>
                </label>
                <input type="file" id="usp-import-file-input" accept=".json,application/json" style="width:100%; border:1.5px dashed var(--usp-adm-slate-300); border-radius:8px; padding:12px; background:#fff;">
            </div>

            <div style="text-align:center; font-size:11px; color:var(--usp-adm-slate-400); font-weight:700; margin: 12px 0;">— OR —</div>

            <div>
                <label for="usp-import-json-textarea" style="display:block; font-size:11.5px; font-weight:700; color:var(--usp-adm-slate-700); text-transform:uppercase; margin-bottom:6px; letter-spacing:0.5px;">
                    <?php esc_html_e( 'Paste JSON Code:', 'usp-form-builder' ); ?>
                </label>
                <textarea id="usp-import-json-textarea" rows="4" placeholder='{"title":"...","fields_data":[...]}' style="width:100%; font-family:monospace; font-size:11.5px; border-radius:6px; border:1.5px solid var(--usp-adm-slate-300); padding:8px;"></textarea>
            </div>
        </div>
        <div class="usp-modal-footer" style="display:flex; justify-content:flex-end; gap:8px;">
            <button type="button" class="button button-secondary" id="usp-import-cancel-btn"><?php esc_html_e( 'Cancel', 'usp-form-builder' ); ?></button>
            <button type="button" class="button button-primary" id="usp-import-submit-btn" style="font-weight:600;">
                <span class="dashicons dashicons-upload" style="vertical-align:middle; margin-top:-2px;"></span> <?php esc_html_e( 'Import & Open Builder', 'usp-form-builder' ); ?>
            </button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var adminNonce = '<?php echo esc_js( wp_create_nonce( 'usp_admin_nonce' ) ); ?>';
    var activeFilter = 'all';

    // Filter engine: Combines Status Filter Tabs + Search Keyword
    function applyFormsFilter() {
        var query = $('#usp-forms-search-box').val().toLowerCase().trim();
        var visibleCount = 0;

        $('.usp-form-row').each(function() {
            var $row = $(this);
            var title = String($row.data('title') || '');
            var id = String($row.data('id') || '');
            var shortcode = String($row.data('shortcode') || '');
            var status = String($row.data('status') || '');

            var matchesSearch = (query === '') || (title.indexOf(query) !== -1 || id.indexOf(query) !== -1 || shortcode.indexOf(query) !== -1);
            var matchesStatus = (activeFilter === 'all') || (status === activeFilter);

            if (matchesSearch && matchesStatus) {
                $row.show();
                visibleCount++;
            } else {
                $row.hide();
            }
        });

        $('#usp-forms-count-visible').text(visibleCount);

        if (visibleCount === 0) {
            $('#usp-no-forms-match-row').show();
        } else {
            $('#usp-no-forms-match-row').hide();
        }

        if (query.length > 0) {
            $('#usp-forms-search-clear').show();
        } else {
            $('#usp-forms-search-clear').hide();
        }
    }

    // Status Tab Click
    $('.usp-status-pill-btn').on('click', function(e) {
        e.preventDefault();
        $('.usp-status-pill-btn').removeClass('active');
        $(this).addClass('active');
        activeFilter = $(this).data('filter');
        applyFormsFilter();
    });

    // Search Input Event
    $('#usp-forms-search-box').on('input', function() {
        applyFormsFilter();
    });

    // Clear Search Button
    $('#usp-forms-search-clear').on('click', function() {
        $('#usp-forms-search-box').val('').trigger('input').focus();
    });

    // Reset Filters Button in Empty Row
    $('#usp-reset-filter-btn').on('click', function() {
        $('#usp-forms-search-box').val('');
        $('.usp-status-pill-btn[data-filter="all"]').trigger('click');
    });

    // Recalculate status counts on tabs
    function recalculateTabCounts() {
        var total = $('.usp-form-row').length;
        var live = $('.usp-form-row[data-status="publish"]').length;
        var draft = $('.usp-form-row[data-status="draft"]').length;
        var closed = $('.usp-form-row[data-status="closed"]').length;

        $('#usp-count-tab-all').text(total);
        $('#usp-count-tab-live').text(live);
        $('#usp-count-tab-draft').text(draft);
        $('#usp-count-tab-closed').text(closed);
    }

    // Form Status Toggle (Live -> Draft -> Closed -> Live)
    $(document).on('click', '.usp-form-status-toggle', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var $row = $btn.closest('.usp-form-row');
        var formId = $btn.data('id');
        var currentStatus = $btn.data('status');
        var nextStatus = 'publish';

        if (currentStatus === 'publish') {
            nextStatus = 'draft';
        } else if (currentStatus === 'draft') {
            nextStatus = 'closed';
        } else {
            nextStatus = 'publish';
        }

        $btn.css('opacity', '0.5');

        $.post(ajaxurl, {
            action: 'usp_toggle_form_status',
            form_id: formId,
            status: nextStatus,
            nonce: adminNonce
        }, function(res) {
            $btn.css('opacity', '1');
            if (res.success) {
                $btn.data('status', nextStatus);
                $row.attr('data-status', nextStatus).data('status', nextStatus);

                if (nextStatus === 'draft') {
                    $btn.html('<span class="usp-status-pill is-draft"><span class="dashicons dashicons-edit"></span> <?php echo esc_js( __( 'Draft', 'usp-form-builder' ) ); ?></span>');
                } else if (nextStatus === 'closed') {
                    $btn.html('<span class="usp-status-pill is-closed"><span class="dashicons dashicons-lock"></span> <?php echo esc_js( __( 'Closed', 'usp-form-builder' ) ); ?></span>');
                } else {
                    $btn.html('<span class="usp-status-pill is-live"><span class="dashicons dashicons-yes"></span> <?php echo esc_js( __( 'Live', 'usp-form-builder' ) ); ?></span>');
                }

                recalculateTabCounts();
                applyFormsFilter();

                if (window.USPModal && USPModal.toast) {
                    USPModal.toast(res.data.message || 'Status updated', 'success');
                }
            } else {
                if (window.USPModal && USPModal.alert) {
                    USPModal.alert({
                        title: 'Error',
                        message: res.data ? res.data.message : 'Could not update status.',
                        type: 'error'
                    });
                }
            }
        }).fail(function() {
            $btn.css('opacity', '1');
        });
    });

    // Copy shortcode
    $('.usp-copy-btn').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var text = $btn.data('clipboard');
        navigator.clipboard.writeText(text).then(function() {
            var orig = $btn.html();
            $btn.html('<span class="dashicons dashicons-yes" style="color:#059669; font-size:14px; width:14px; height:14px; vertical-align:middle;"></span>');
            if (window.USPModal && USPModal.toast) {
                USPModal.toast('<?php echo esc_js( __( 'Shortcode copied to clipboard!', 'usp-form-builder' ) ); ?>', 'success');
            }
            setTimeout(function() { $btn.html(orig); }, 1800);
        });
    });

    // Delete form with custom modal dialog
    $('.usp-delete-form-btn').on('click', function(e) {
        e.preventDefault();
        var formId = $(this).data('id');

        USPModal.confirm({
            title: '<?php echo esc_js( __( 'Delete Form?', 'usp-form-builder' ) ); ?>',
            message: '<?php echo esc_js( __( 'Are you sure you want to delete this form and all its submissions? This action cannot be undone.', 'usp-form-builder' ) ); ?>',
            type: 'danger',
            confirmText: '<?php echo esc_js( __( 'Yes, Delete Form', 'usp-form-builder' ) ); ?>',
            cancelText: '<?php echo esc_js( __( 'Cancel', 'usp-form-builder' ) ); ?>'
        }).then(function(confirmed) {
            if (!confirmed) return;

            $.post(ajaxurl, {
                action: 'usp_delete_form',
                form_id: formId,
                nonce: adminNonce
            }, function(res) {
                if (res.success) {
                    $('#usp-form-row-' + formId).fadeOut(200, function() { 
                        $(this).remove(); 
                        recalculateTabCounts();
                        applyFormsFilter();
                    });
                    USPModal.toast('<?php echo esc_js( __( 'Form deleted successfully.', 'usp-form-builder' ) ); ?>', 'success');
                } else {
                    USPModal.alert({
                        title: '<?php echo esc_js( __( 'Delete Failed', 'usp-form-builder' ) ); ?>',
                        message: res.data.message || '<?php echo esc_js( __( 'Error deleting form.', 'usp-form-builder' ) ); ?>',
                        type: 'error'
                    });
                }
            });
        });
    });

    // Duplicate form with instant feedback
    $(document).on('click', '.usp-duplicate-form-btn', function(e) {
        e.preventDefault();
        var formId = $(this).data('id');
        var $btn = $(this);
        $btn.addClass('disabled');

        $.post(ajaxurl, {
            action: 'usp_duplicate_form',
            form_id: formId,
            nonce: adminNonce
        }, function(res) {
            $btn.removeClass('disabled');
            if (res.success && res.data.edit_url) {
                if (window.USPModal && USPModal.toast) {
                    USPModal.toast('Form duplicated successfully! Opening...', 'success');
                }
                setTimeout(function() {
                    window.location.href = res.data.edit_url;
                }, 400);
            } else {
                USPModal.alert({
                    title: '<?php echo esc_js( __( 'Duplicate Failed', 'usp-form-builder' ) ); ?>',
                    message: res.data.message || '<?php echo esc_js( __( 'Error duplicating form.', 'usp-form-builder' ) ); ?>',
                    type: 'error'
                });
            }
        }).fail(function() {
            $btn.removeClass('disabled');
            USPModal.alert({
                title: 'Network Error',
                message: 'Failed to duplicate form due to connection error.',
                type: 'error'
            });
        });
    });

    // Import Form Modal Controls
    $('#usp-open-import-modal').on('click', function(e) {
        e.preventDefault();
        $('#usp-import-file-input').val('');
        $('#usp-import-json-textarea').val('');
        $('#usp-import-modal').fadeIn(150);
    });

    $('#usp-import-close-btn, #usp-import-cancel-btn').on('click', function(e) {
        e.preventDefault();
        $('#usp-import-modal').fadeOut(150);
    });

    $('#usp-import-submit-btn').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var fileInput = document.getElementById('usp-import-file-input');
        var jsonText = $('#usp-import-json-textarea').val().trim();

        if (!fileInput.files.length && !jsonText) {
            USPModal.alert({
                title: 'No Data Provided',
                message: 'Please choose a .json file or paste form JSON code to import.',
                type: 'warning'
            });
            return;
        }

        var formData = new FormData();
        formData.append('action', 'usp_import_form_json');
        formData.append('nonce', adminNonce);

        if (fileInput.files.length) {
            formData.append('import_file', fileInput.files[0]);
        } else {
            formData.append('import_json', jsonText);
        }

        $btn.prop('disabled', true).text('Importing...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-upload" style="vertical-align:middle; margin-top:-2px;"></span> Import & Open Builder');
                if (res.success && res.data.redirect_url) {
                    window.location.href = res.data.redirect_url;
                } else {
                    USPModal.alert({
                        title: 'Import Failed',
                        message: res.data ? res.data.message : 'Invalid JSON file.',
                        type: 'error'
                    });
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-upload" style="vertical-align:middle; margin-top:-2px;"></span> Import & Open Builder');
                USPModal.alert({
                    title: 'Network Error',
                    message: 'A network error occurred while importing the form.',
                    type: 'error'
                });
            }
        });
    });
});
</script>
