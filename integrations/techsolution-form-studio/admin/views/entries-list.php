<?php
/**
 * View: Form Entries / Submissions Table with KPI Metric Cards, Quick Date Presets,
 * Advanced Multi-Criteria Filtering, Bulk Actions, Pagination, and Enhanced Inspection Modal.
 *
 * @package    TSFS_Plugin
 * @subpackage TSFS_Plugin/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Included from a TSFS_Admin method, so these variables are local to that method, not globals.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$paged         = isset( $paged ) ? max( 1, absint( $paged ) ) : 1;
$per_page      = isset( $per_page ) ? absint( $per_page ) : 20;
$total_entries = isset( $total_entries ) ? absint( $total_entries ) : count( $entries );
$total_pages   = isset( $total_pages ) ? max( 1, absint( $total_pages ) ) : 1;

$offset        = ( $paged - 1 ) * $per_page;
$start_entry   = $total_entries ? ( $offset + 1 ) : 0;
$end_entry     = min( $offset + count( $entries ), $total_entries );

$has_filters = ( ! empty( $selected_form_id ) || ! empty( $status ) || ! empty( $date_from ) || ! empty( $date_to ) || ! empty( $search ) );

$export_url = wp_nonce_url(
    add_query_arg(
        array(
            'action'    => 'tsfs_export_entries',
            'form_id'   => $selected_form_id,
            'status'    => $status,
            'date_from' => $date_from,
            'date_to'   => $date_to,
            's'         => $search,
        ),
        admin_url( 'admin-post.php' )
    ),
    'tsfs_export_entries',
    'tsfs_nonce'
);

$base_url = admin_url( 'admin.php?page=tsfs-entries' );
if ( ! empty( $selected_form_id ) ) {
    $base_url = add_query_arg( 'form_id', $selected_form_id, $base_url );
}
if ( ! empty( $date_from ) ) {
    $base_url = add_query_arg( 'date_from', $date_from, $base_url );
}
if ( ! empty( $date_to ) ) {
    $base_url = add_query_arg( 'date_to', $date_to, $base_url );
}
if ( ! empty( $search ) ) {
    $base_url = add_query_arg( 's', $search, $base_url );
}
if ( ! empty( $per_page ) && 20 !== $per_page ) {
    $base_url = add_query_arg( 'per_page', $per_page, $base_url );
}

// Preset date calculations
$today_str      = current_time( 'Y-m-d' );
$d7_str         = gmdate( 'Y-m-d', strtotime( '-6 days', strtotime( $today_str ) ) );
$d30_str        = gmdate( 'Y-m-d', strtotime( '-29 days', strtotime( $today_str ) ) );
$month_str      = gmdate( 'Y-m-01', strtotime( $today_str ) );

$is_today_preset = ( $date_from === $today_str && $date_to === $today_str );
$is_7d_preset    = ( $date_from === $d7_str && $date_to === $today_str );
$is_30d_preset   = ( $date_from === $d30_str && $date_to === $today_str );
$is_month_preset = ( $date_from === $month_str && $date_to === $today_str );
$is_all_preset   = ( empty( $date_from ) && empty( $date_to ) );

// Pagination Base URL
$pagination_base_url = admin_url( 'admin.php?page=tsfs-entries' );
if ( ! empty( $selected_form_id ) ) {
    $pagination_base_url = add_query_arg( 'form_id', $selected_form_id, $pagination_base_url );
}
if ( ! empty( $status ) ) {
    $pagination_base_url = add_query_arg( 'status', $status, $pagination_base_url );
}
if ( ! empty( $date_from ) ) {
    $pagination_base_url = add_query_arg( 'date_from', $date_from, $pagination_base_url );
}
if ( ! empty( $date_to ) ) {
    $pagination_base_url = add_query_arg( 'date_to', $date_to, $pagination_base_url );
}
if ( ! empty( $search ) ) {
    $pagination_base_url = add_query_arg( 's', $search, $pagination_base_url );
}
if ( ! empty( $per_page ) && 20 !== $per_page ) {
    $pagination_base_url = add_query_arg( 'per_page', $per_page, $pagination_base_url );
}
wp_add_inline_script(
    'tsfs-entries-js',
    'window.tsfsEntriesPage = ' . wp_json_encode( array(
        'pagination_base_url' => $pagination_base_url,
        'total_entries'       => $total_entries,
        'open_entry_id'       => isset( $_GET['entry_id'] ) ? absint( $_GET['entry_id'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    ) ) . ';',
    'before'
);

$submission_columns = array();
$submission_field_key = static function ( $form_id, $field ) use ( $selected_form_id ) {
    if ( empty( $selected_form_id ) && ! empty( $field['label'] ) ) {
        $identifier = 'label:' . strtolower( trim( $field['label'] ) );
    } elseif ( ! empty( $field['name'] ) ) {
        $identifier = 'name:' . $field['name'];
    } elseif ( ! empty( $field['id'] ) ) {
        $identifier = 'id:' . $field['id'];
    } else {
        $identifier = 'label:' . ( ! empty( $field['label'] ) ? $field['label'] : __( 'Field', 'techsolution-form-studio' ) );
    }

    return empty( $selected_form_id ) ? $identifier : absint( $form_id ) . ':' . $identifier;
};

foreach ( $forms as $form ) {
    $form_id = absint( $form['id'] );
    if ( ! empty( $selected_form_id ) && $selected_form_id !== $form_id ) {
        continue;
    }

    $form_fields = isset( $form['fields_data'] ) && is_array( $form['fields_data'] )
        ? $form['fields_data']
        : ( ! empty( $form['fields_data'] ) ? json_decode( $form['fields_data'], true ) : array() );
    if ( ! is_array( $form_fields ) ) {
        continue;
    }

    foreach ( $form_fields as $field ) {
        if ( ! is_array( $field ) || in_array( isset( $field['type'] ) ? $field['type'] : '', array( 'html', 'page_break' ), true ) ) {
            continue;
        }

        $column_key = $submission_field_key( $form_id, $field );
        if ( ! isset( $submission_columns[ $column_key ] ) ) {
            $submission_columns[ $column_key ] = ! empty( $field['label'] )
                ? $field['label']
                : ( ! empty( $field['name'] ) ? ucfirst( str_replace( '_', ' ', $field['name'] ) ) : __( 'Field', 'techsolution-form-studio' ) );
        }
    }
}

foreach ( $entries as $entry ) {
    $fields = isset( $entry['fields_data'] ) && is_array( $entry['fields_data'] ) ? $entry['fields_data'] : array();

    foreach ( $fields as $field ) {
        if ( ! is_array( $field ) ) {
            continue;
        }

        $column_key = $submission_field_key( $entry['form_id'], $field );
        if ( ! isset( $submission_columns[ $column_key ] ) ) {
            $submission_columns[ $column_key ] = ! empty( $field['label'] )
                ? $field['label']
                : ( ! empty( $field['name'] ) ? ucfirst( str_replace( '_', ' ', $field['name'] ) ) : __( 'Field', 'techsolution-form-studio' ) );
        }
    }
}
?>

<div class="wrap tsfs-admin-wrap">
    <header class="tsfs-page-header">
        <div class="tsfs-header-title-group">
            <span class="tsfs-header-icon-box"><span class="dashicons dashicons-email-alt" aria-hidden="true"></span></span>
            <div>
                <h1 class="tsfs-main-heading"><?php esc_html_e( 'Submissions', 'techsolution-form-studio' ); ?></h1>
                <p class="tsfs-main-subheading">
                    <?php
                    /* translators: %s: number of submissions */
                    echo esc_html( sprintf( _n( '%s submission matches your filters.', '%s submissions match your filters.', $total_entries, 'techsolution-form-studio' ), number_format_i18n( $total_entries ) ) );
                    ?>
                </p>
            </div>
        </div>
        <div class="tsfs-header-actions-group">
            <button type="button" id="tsfs-open-export-modal-btn" class="button button-primary tsfs-btn-primary" title="<?php esc_attr_e( 'Export submissions with customized options and headers', 'techsolution-form-studio' ); ?>">
                <span class="dashicons dashicons-download" aria-hidden="true"></span> <?php esc_html_e( 'Export', 'techsolution-form-studio' ); ?>
            </button>
        </div>
    </header>

    <!-- 1. Top KPI Metric Cards (1-Click Filtering) -->
    <div class="tsfs-kpi-grid">
        <!-- Card 1: Total Submissions -->
        <a href="<?php echo esc_url( remove_query_arg( array( 'status', 'date_from', 'date_to', 'paged' ), $base_url ) ); ?>" 
           class="tsfs-kpi-card tsfs-kpi-card-all <?php echo ( empty( $status ) && empty( $date_from ) && empty( $date_to ) ) ? 'is-active' : ''; ?>"
           title="<?php esc_attr_e( 'Click to view all submissions', 'techsolution-form-studio' ); ?>">
            <div class="tsfs-kpi-icon-wrap">
                <span class="dashicons dashicons-email-alt"></span>
            </div>
            <div class="tsfs-kpi-content">
                <div class="tsfs-kpi-label">
                    <span><?php esc_html_e( 'Total Submissions', 'techsolution-form-studio' ); ?></span>
                </div>
                <div class="tsfs-kpi-value"><?php echo esc_html( number_format_i18n( $status_counts['all'] ) ); ?></div>
                <div class="tsfs-kpi-subtext">
                    <span><?php esc_html_e( 'All records', 'techsolution-form-studio' ); ?></span>
                </div>
            </div>
        </a>

        <!-- Card 2: Unread Inbox -->
        <a href="<?php echo esc_url( add_query_arg( array( 'status' => 'unread', 'paged' => 1 ), remove_query_arg( array( 'date_from', 'date_to' ), $base_url ) ) ); ?>" 
           class="tsfs-kpi-card tsfs-kpi-card-unread <?php echo ( 'unread' === $status ) ? 'is-active' : ''; ?>"
           title="<?php esc_attr_e( 'Click to filter unread submissions', 'techsolution-form-studio' ); ?>">
            <div class="tsfs-kpi-icon-wrap">
                <span class="dashicons dashicons-email"></span>
                <?php if ( ! empty( $status_counts['unread'] ) ) : ?>
                    <span class="tsfs-kpi-pulse-dot" title="<?php esc_attr_e( 'Unread records need attention', 'techsolution-form-studio' ); ?>"></span>
                <?php endif; ?>
            </div>
            <div class="tsfs-kpi-content">
                <div class="tsfs-kpi-label">
                    <span><?php esc_html_e( 'Unread Inbox', 'techsolution-form-studio' ); ?></span>
                </div>
                <div class="tsfs-kpi-value"><?php echo esc_html( number_format_i18n( $status_counts['unread'] ) ); ?></div>
                <div class="tsfs-kpi-subtext">
                    <span><?php esc_html_e( 'Needs review', 'techsolution-form-studio' ); ?></span>
                </div>
            </div>
        </a>

        <!-- Card 3: Starred Records -->
        <a href="<?php echo esc_url( add_query_arg( array( 'status' => 'starred', 'paged' => 1 ), remove_query_arg( array( 'date_from', 'date_to' ), $base_url ) ) ); ?>" 
           class="tsfs-kpi-card tsfs-kpi-card-starred <?php echo ( 'starred' === $status ) ? 'is-active' : ''; ?>"
           title="<?php esc_attr_e( 'Click to view starred submissions', 'techsolution-form-studio' ); ?>">
            <div class="tsfs-kpi-icon-wrap">
                <span class="dashicons dashicons-star-filled"></span>
            </div>
            <div class="tsfs-kpi-content">
                <div class="tsfs-kpi-label">
                    <span><?php esc_html_e( 'Starred / Flagged', 'techsolution-form-studio' ); ?></span>
                </div>
                <div class="tsfs-kpi-value"><?php echo esc_html( number_format_i18n( $status_counts['starred'] ) ); ?></div>
                <div class="tsfs-kpi-subtext">
                    <span><?php esc_html_e( 'High priority', 'techsolution-form-studio' ); ?></span>
                </div>
            </div>
        </a>

        <!-- Card 4: Today's Activity -->
        <a href="<?php echo esc_url( add_query_arg( array( 'date_from' => $today_str, 'date_to' => $today_str, 'paged' => 1 ), remove_query_arg( 'status', $base_url ) ) ); ?>" 
           class="tsfs-kpi-card tsfs-kpi-card-today <?php echo $is_today_preset ? 'is-active' : ''; ?>"
           title="<?php esc_attr_e( 'Click to filter today\'s submissions', 'techsolution-form-studio' ); ?>">
            <div class="tsfs-kpi-icon-wrap">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="tsfs-kpi-content">
                <div class="tsfs-kpi-label">
                    <span><?php esc_html_e( 'Today\'s Submissions', 'techsolution-form-studio' ); ?></span>
                </div>
                <div class="tsfs-kpi-value"><?php echo esc_html( number_format_i18n( $status_counts['today'] ) ); ?></div>
                <div class="tsfs-kpi-subtext">
                    <span><?php esc_html_e( 'Received today', 'techsolution-form-studio' ); ?></span>
                </div>
            </div>
        </a>
    </div>

    <!-- Quick Status Filter Segmented Pills (All / Unread / Read / Starred) -->
    <div style="display:inline-flex; align-items:center; background:#fff; border:1px solid var(--tsfs-adm-slate-200); border-radius:8px; padding:3px; margin-bottom:16px; gap:3px; box-shadow:var(--tsfs-adm-shadow-sm); flex-wrap:wrap;">
        <a href="<?php echo esc_url( remove_query_arg( array( 'status', 'paged' ), $base_url ) ); ?>" 
           style="display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:6px; font-size:13px; font-weight:600; text-decoration:none; transition:all 0.15s ease; <?php echo empty( $status ) ? 'background:var(--tsfs-adm-primary); color:#fff;' : 'color:var(--tsfs-adm-slate-600);'; ?>">
            <span><?php esc_html_e( 'All Submissions', 'techsolution-form-studio' ); ?></span>
            <span style="font-size:11px; font-weight:700; padding:1px 6px; border-radius:10px; <?php echo empty( $status ) ? 'background:rgba(255,255,255,0.25); color:#fff;' : 'background:var(--tsfs-adm-slate-100); color:var(--tsfs-adm-slate-700);'; ?>"><?php echo esc_html( $status_counts['all'] ); ?></span>
        </a>
        <a href="<?php echo esc_url( add_query_arg( array( 'status' => 'unread', 'paged' => 1 ), $base_url ) ); ?>" 
           style="display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:6px; font-size:13px; font-weight:600; text-decoration:none; transition:all 0.15s ease; <?php echo ( 'unread' === $status ) ? 'background:var(--tsfs-adm-primary); color:#fff;' : 'color:var(--tsfs-adm-slate-600);'; ?>">
            <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:<?php echo ( 'unread' === $status ) ? '#fff' : '#2563eb'; ?>;"></span>
            <span><?php esc_html_e( 'Unread', 'techsolution-form-studio' ); ?></span>
            <span style="font-size:11px; font-weight:700; padding:1px 6px; border-radius:10px; <?php echo ( 'unread' === $status ) ? 'background:rgba(255,255,255,0.25); color:#fff;' : 'background:var(--tsfs-adm-slate-100); color:var(--tsfs-adm-slate-700);'; ?>"><?php echo esc_html( $status_counts['unread'] ); ?></span>
        </a>
        <a href="<?php echo esc_url( add_query_arg( array( 'status' => 'read', 'paged' => 1 ), $base_url ) ); ?>" 
           style="display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:6px; font-size:13px; font-weight:600; text-decoration:none; transition:all 0.15s ease; <?php echo ( 'read' === $status ) ? 'background:var(--tsfs-adm-primary); color:#fff;' : 'color:var(--tsfs-adm-slate-600);'; ?>">
            <span class="dashicons dashicons-yes" style="font-size:14px; width:14px; height:14px; color:<?php echo ( 'read' === $status ) ? '#fff' : '#10b981'; ?>;"></span>
            <span><?php esc_html_e( 'Read', 'techsolution-form-studio' ); ?></span>
            <span style="font-size:11px; font-weight:700; padding:1px 6px; border-radius:10px; <?php echo ( 'read' === $status ) ? 'background:rgba(255,255,255,0.25); color:#fff;' : 'background:var(--tsfs-adm-slate-100); color:var(--tsfs-adm-slate-700);'; ?>"><?php echo esc_html( $status_counts['read'] ); ?></span>
        </a>
        <a href="<?php echo esc_url( add_query_arg( array( 'status' => 'starred', 'paged' => 1 ), $base_url ) ); ?>" 
           style="display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:6px; font-size:13px; font-weight:600; text-decoration:none; transition:all 0.15s ease; <?php echo ( 'starred' === $status ) ? 'background:var(--tsfs-adm-primary); color:#fff;' : 'color:var(--tsfs-adm-slate-600);'; ?>">
            <span class="dashicons dashicons-star-filled" style="color:<?php echo ( 'starred' === $status ) ? '#fff' : '#f59e0b'; ?>; font-size:14px; width:14px; height:14px;"></span>
            <span><?php esc_html_e( 'Starred', 'techsolution-form-studio' ); ?></span>
            <span style="font-size:11px; font-weight:700; padding:1px 6px; border-radius:10px; <?php echo ( 'starred' === $status ) ? 'background:rgba(255,255,255,0.25); color:#fff;' : 'background:var(--tsfs-adm-slate-100); color:var(--tsfs-adm-slate-700);'; ?>"><?php echo esc_html( $status_counts['starred'] ); ?></span>
        </a>
    </div>

    <!-- Advanced Filter Bar (With Quick Date Presets) -->
    <div style="background:#fff; border:1px solid var(--tsfs-adm-slate-200); border-radius:var(--tsfs-adm-radius-md); padding:16px 20px; box-shadow:var(--tsfs-adm-shadow-sm); margin-bottom:20px;">
        <form method="get" action="" id="tsfs-entries-filter-form">
            <input type="hidden" name="page" value="tsfs-entries">
            <?php if ( ! empty( $status ) ) : ?>
                <input type="hidden" name="status" value="<?php echo esc_attr( $status ); ?>">
            <?php endif; ?>
            <?php if ( ! empty( $per_page ) && 20 !== $per_page ) : ?>
                <input type="hidden" name="per_page" value="<?php echo esc_attr( $per_page ); ?>">
            <?php endif; ?>

            <div style="display:grid; grid-template-columns: minmax(170px, 1.2fr) minmax(140px, 1fr) minmax(140px, 1fr) minmax(190px, 1.4fr) auto; gap:14px; align-items:end;">
                <!-- Filter by Form -->
                <div>
                    <label for="filter-by-form" style="display:block; font-size:11.5px; font-weight:700; color:var(--tsfs-adm-slate-600); text-transform:uppercase; margin-bottom:6px; letter-spacing:0.4px;">
                        <?php esc_html_e( 'Select Form', 'techsolution-form-studio' ); ?>
                    </label>
                    <select name="form_id" id="filter-by-form" style="width:100%; border-radius:6px; border:1.5px solid var(--tsfs-adm-slate-200); height:38px; font-size:13px; color:var(--tsfs-adm-slate-800); background:#fff;">
                        <option value="0"><?php esc_html_e( '— All Forms —', 'techsolution-form-studio' ); ?></option>
                        <?php foreach ( $forms as $f ) : ?>
                            <option value="<?php echo esc_attr( $f['id'] ); ?>" <?php selected( $selected_form_id, $f['id'] ); ?>>
                                <?php echo esc_html( $f['title'] ); ?> (<?php echo esc_html( $f['entry_count'] ); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Date Range: From -->
                <div>
                    <label for="filter-date-from" style="display:flex; align-items:center; gap:4px; font-size:11.5px; font-weight:700; color:var(--tsfs-adm-slate-600); text-transform:uppercase; margin-bottom:6px; letter-spacing:0.4px;">
                        <span class="dashicons dashicons-calendar-alt" style="font-size:14px; width:14px; height:14px; color:var(--tsfs-adm-slate-500);"></span>
                        <?php esc_html_e( 'Date From', 'techsolution-form-studio' ); ?>
                    </label>
                    <input type="date" name="date_from" id="filter-date-from" value="<?php echo esc_attr( $date_from ); ?>" style="width:100%; border-radius:6px; border:1.5px solid var(--tsfs-adm-slate-200); height:38px; padding:4px 10px; font-size:13px; color:var(--tsfs-adm-slate-800); background:#fff;">
                </div>

                <!-- Date Range: To -->
                <div>
                    <label for="filter-date-to" style="display:flex; align-items:center; gap:4px; font-size:11.5px; font-weight:700; color:var(--tsfs-adm-slate-600); text-transform:uppercase; margin-bottom:6px; letter-spacing:0.4px;">
                        <span class="dashicons dashicons-calendar-alt" style="font-size:14px; width:14px; height:14px; color:var(--tsfs-adm-slate-500);"></span>
                        <?php esc_html_e( 'Date To', 'techsolution-form-studio' ); ?>
                    </label>
                    <input type="date" name="date_to" id="filter-date-to" value="<?php echo esc_attr( $date_to ); ?>" style="width:100%; border-radius:6px; border:1.5px solid var(--tsfs-adm-slate-200); height:38px; padding:4px 10px; font-size:13px; color:var(--tsfs-adm-slate-800); background:#fff;">
                </div>

                <!-- Keyword Search -->
                <div>
                    <label for="filter-search" style="display:flex; align-items:center; gap:4px; font-size:11.5px; font-weight:700; color:var(--tsfs-adm-slate-600); text-transform:uppercase; margin-bottom:6px; letter-spacing:0.4px;">
                        <span class="dashicons dashicons-search" style="font-size:14px; width:14px; height:14px; color:var(--tsfs-adm-slate-500);"></span>
                        <?php esc_html_e( 'Search Content / IP', 'techsolution-form-studio' ); ?>
                    </label>
                    <input type="search" name="s" id="filter-search" placeholder="<?php esc_attr_e( 'e.g. name, email, text...', 'techsolution-form-studio' ); ?>" value="<?php echo esc_attr( $search ); ?>" style="width:100%; border-radius:6px; border:1.5px solid var(--tsfs-adm-slate-200); height:38px; padding:4px 10px; font-size:13px; color:var(--tsfs-adm-slate-800); background:#fff;">
                </div>

                <!-- Filter Action Buttons -->
                <div style="display:flex; gap:8px;">
                    <button type="submit" class="button button-primary" style="height:38px; padding:0 18px; font-weight:600; border-radius:6px; background:var(--tsfs-adm-primary); border-color:var(--tsfs-adm-primary); display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
                        <span class="dashicons dashicons-filter" style="font-size:16px; width:16px; height:16px;"></span> <?php esc_html_e( 'Apply Filter', 'techsolution-form-studio' ); ?>
                    </button>
                    <?php if ( $has_filters ) : ?>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=tsfs-entries' ) ); ?>" class="button button-secondary" style="height:38px; line-height:36px; padding:0 14px; font-weight:600; border-radius:6px;" title="<?php esc_attr_e( 'Reset all filters', 'techsolution-form-studio' ); ?>">
                            <?php esc_html_e( 'Clear', 'techsolution-form-studio' ); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Date Range Preset Buttons (Auto-Apply) -->
            <div class="tsfs-date-presets-row">
                <span class="tsfs-date-presets-label"><?php esc_html_e( 'Quick Dates:', 'techsolution-form-studio' ); ?></span>
                <button type="button" class="tsfs-date-preset-btn <?php echo $is_today_preset ? 'is-active' : ''; ?>" data-from="<?php echo esc_attr( $today_str ); ?>" data-to="<?php echo esc_attr( $today_str ); ?>">
                    <?php esc_html_e( 'Today', 'techsolution-form-studio' ); ?>
                </button>
                <button type="button" class="tsfs-date-preset-btn <?php echo $is_7d_preset ? 'is-active' : ''; ?>" data-from="<?php echo esc_attr( $d7_str ); ?>" data-to="<?php echo esc_attr( $today_str ); ?>">
                    <?php esc_html_e( 'Last 7 Days', 'techsolution-form-studio' ); ?>
                </button>
                <button type="button" class="tsfs-date-preset-btn <?php echo $is_30d_preset ? 'is-active' : ''; ?>" data-from="<?php echo esc_attr( $d30_str ); ?>" data-to="<?php echo esc_attr( $today_str ); ?>">
                    <?php esc_html_e( 'Last 30 Days', 'techsolution-form-studio' ); ?>
                </button>
                <button type="button" class="tsfs-date-preset-btn <?php echo $is_month_preset ? 'is-active' : ''; ?>" data-from="<?php echo esc_attr( $month_str ); ?>" data-to="<?php echo esc_attr( $today_str ); ?>">
                    <?php esc_html_e( 'This Month', 'techsolution-form-studio' ); ?>
                </button>
                <button type="button" class="tsfs-date-preset-btn <?php echo $is_all_preset ? 'is-active' : ''; ?>" data-from="" data-to="">
                    <?php esc_html_e( 'All Time', 'techsolution-form-studio' ); ?>
                </button>
            </div>
        </form>
    </div>

    <?php if ( empty( $entries ) ) : ?>
        <div class="tsfs-empty-state">
            <div class="tsfs-empty-icon dashicons dashicons-email"></div>
            <h2><?php esc_html_e( 'No Submissions Match Your Criteria', 'techsolution-form-studio' ); ?></h2>
            <p><?php esc_html_e( 'Try clearing or widening your date range and search filters.', 'techsolution-form-studio' ); ?></p>
            <?php if ( $has_filters ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=tsfs-entries' ) ); ?>" class="button button-secondary">
                    <?php esc_html_e( 'Clear All Filters', 'techsolution-form-studio' ); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php else : ?>
        <!-- Bulk Actions & Top Pagination Toolbar -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; background:#fff; padding:10px 16px; border:1px solid var(--tsfs-adm-slate-200); border-radius:6px; box-shadow:var(--tsfs-adm-shadow-sm); flex-wrap:wrap; gap:10px;">
            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                <select id="tsfs-bulk-action-selector" style="border-radius:6px; height:34px; border:1.5px solid var(--tsfs-adm-slate-300); font-size:13px;">
                    <option value="-1"><?php esc_html_e( '— Bulk actions —', 'techsolution-form-studio' ); ?></option>
                    <option value="export_selected"><?php esc_html_e( 'Export Selected Submissions...', 'techsolution-form-studio' ); ?></option>
                    <option value="mark_read"><?php esc_html_e( 'Mark as Read', 'techsolution-form-studio' ); ?></option>
                    <option value="mark_unread"><?php esc_html_e( 'Mark as Unread', 'techsolution-form-studio' ); ?></option>
                    <option value="star"><?php esc_html_e( 'Star / Flag', 'techsolution-form-studio' ); ?></option>
                    <option value="delete"><?php esc_html_e( 'Delete Permanently', 'techsolution-form-studio' ); ?></option>
                </select>
                <button type="button" id="tsfs-bulk-apply-btn" class="button action" style="height:34px; border-radius:6px; font-weight:600;">
                    <?php esc_html_e( 'Apply', 'techsolution-form-studio' ); ?>
                </button>
                <span id="tsfs-bulk-selected-count" style="font-size:12px; color:var(--tsfs-adm-slate-500); margin-left:8px; font-weight:600;"></span>
            </div>

            <div style="display:flex; align-items:center; gap:16px;">
                <!-- Per Page Selector Dropdown -->
                <div style="display:inline-flex; align-items:center; gap:6px; font-size:12.5px; color:var(--tsfs-adm-slate-600); font-weight:600;">
                    <span><?php esc_html_e( 'Per page:', 'techsolution-form-studio' ); ?></span>
                    <select id="tsfs-per-page-top" class="tsfs-per-page-select" style="height:32px; font-size:12.5px; border-radius:6px; border:1.5px solid var(--tsfs-adm-slate-300); font-weight:600; padding:2px 24px 2px 8px;">
                        <option value="10" <?php selected( $per_page, 10 ); ?>>10</option>
                        <option value="20" <?php selected( $per_page, 20 ); ?>>20</option>
                        <option value="50" <?php selected( $per_page, 50 ); ?>>50</option>
                        <option value="100" <?php selected( $per_page, 100 ); ?>>100</option>
                    </select>
                </div>

                <!-- Showing count indicator -->
                <span style="font-size:12.5px; color:var(--tsfs-adm-slate-500); font-weight:600;">
                    <?php /* translators: 1: first entry number, 2: last entry number, 3: total entries */ printf( esc_html__( 'Showing %1$d – %2$d of %3$d', 'techsolution-form-studio' ), absint( $start_entry ), absint( $end_entry ), absint( $total_entries ) ); ?>
                </span>
            </div>
        </div>

        <!-- Responsive Submissions Table -->
        <div class="tsfs-table-responsive-wrapper">
            <table class="wp-list-table widefat striped table-view-list tsfs-entries-table">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column check-column" style="width: 38px; min-width: 38px; text-align: center; vertical-align: middle; padding: 12px 0;">
                            <input type="checkbox" id="tsfs-cb-select-all" style="margin: 0 auto; display: block;">
                        </th>
                        <th scope="col" class="manage-column column-star" style="width: 36px; min-width: 36px; text-align: center; vertical-align: middle; padding: 12px 2px;">
                            <span class="dashicons dashicons-star-empty" style="font-size: 16px; width: 16px; height: 16px; color: #94a3b8; display: inline-block;" title="<?php esc_attr_e( 'Star', 'techsolution-form-studio' ); ?>"></span>
                        </th>
                        <th scope="col" class="manage-column column-id" style="width: 65px; min-width: 65px; text-align: center; vertical-align: middle; padding: 12px 8px;">
                            <?php esc_html_e( 'ID', 'techsolution-form-studio' ); ?>
                        </th>
                        <th scope="col" class="manage-column column-status" style="width: 95px; min-width: 95px; text-align: center; vertical-align: middle; padding: 12px 8px;">
                            <?php esc_html_e( 'Status', 'techsolution-form-studio' ); ?>
                        </th>
                        <th scope="col" class="manage-column column-form" style="width: 170px; min-width: 160px; text-align: left; vertical-align: middle; padding: 12px 14px;">
                            <?php esc_html_e( 'Form Title', 'techsolution-form-studio' ); ?>
                        </th>
                        <?php foreach ( $submission_columns as $column_label ) : ?>
                            <th scope="col" class="manage-column column-submission-field">
                                <?php echo esc_html( $column_label ); ?>
                            </th>
                        <?php endforeach; ?>
                        <th scope="col" class="manage-column column-ip" style="width: 130px; min-width: 130px; text-align: center; vertical-align: middle; padding: 12px 8px;">
                            <?php esc_html_e( 'Submitter IP', 'techsolution-form-studio' ); ?>
                        </th>
                        <th scope="col" class="manage-column column-date" style="width: 140px; min-width: 140px; text-align: left; vertical-align: middle; padding: 12px 12px;">
                            <?php esc_html_e( 'Submitted On', 'techsolution-form-studio' ); ?>
                        </th>
                        <th scope="col" class="manage-column column-actions" style="width: 110px; min-width: 110px; text-align: right; vertical-align: middle; padding: 12px 14px;">
                            <?php esc_html_e( 'Actions', 'techsolution-form-studio' ); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $entries as $entry ) : 
                        $is_unread   = ( 'unread' === $entry['status'] );
                        $is_starred  = ( 'starred' === $entry['status'] );
                        $entry_notes = ! empty( $entry['notes'] ) ? trim( $entry['notes'] ) : '';
                        $fields      = is_array( $entry['fields_data'] ) ? $entry['fields_data'] : array();
                        $entry_fields = array();
                        foreach ( $fields as $field_item ) {
                            if ( is_array( $field_item ) ) {
                                $entry_fields[ $submission_field_key( $entry['form_id'], $field_item ) ] = isset( $field_item['value'] ) ? $field_item['value'] : '';
                            }
                        }
                    ?>
                        <tr id="tsfs-entry-row-<?php echo esc_attr( $entry['id'] ); ?>" class="<?php echo $is_unread ? 'tsfs-row-unread' : ''; ?>">
                            <th scope="row" class="check-column" style="width: 38px; text-align: center; vertical-align: middle; padding: 12px 0;">
                                <input type="checkbox" class="tsfs-entry-cb" value="<?php echo esc_attr( $entry['id'] ); ?>" style="margin: 0 auto; display: block;">
                            </th>
                            <td class="column-star" style="width: 36px; text-align: center; vertical-align: middle; padding: 12px 2px;">
                                <button type="button" class="tsfs-star-toggle-btn" data-id="<?php echo esc_attr( $entry['id'] ); ?>" data-starred="<?php echo $is_starred ? '1' : '0'; ?>" style="background:transparent; border:none; cursor:pointer; padding:2px; display:inline-flex; align-items:center; justify-content:center;" title="<?php echo $is_starred ? esc_attr__( 'Starred - Click to unstar', 'techsolution-form-studio' ) : esc_attr__( 'Click to star', 'techsolution-form-studio' ); ?>">
                                    <span class="dashicons <?php echo $is_starred ? 'dashicons-star-filled' : 'dashicons-star-empty'; ?>" style="font-size:18px; width:18px; height:18px; color:<?php echo $is_starred ? '#f59e0b' : '#cbd5e1'; ?>;"></span>
                                </button>
                            </td>
                            <td class="column-id" style="width: 65px; text-align: center; vertical-align: middle; padding: 12px 8px;">
                                <span class="tsfs-id-badge">#<?php echo esc_html( $entry['id'] ); ?></span>
                                <div class="tsfs-row-note-wrapper" id="tsfs-row-note-wrapper-<?php echo esc_attr( $entry['id'] ); ?>" style="margin-top:3px;">
                                    <?php if ( ! empty( $entry_notes ) ) : ?>
                                        <span class="tsfs-row-note-indicator" title="<?php echo esc_attr( wp_trim_words( $entry_notes, 12 ) ); ?>">
                                            <span class="dashicons dashicons-admin-comments" style="font-size:11px; width:11px; height:11px;"></span> Note
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="column-status" style="width: 95px; text-align: center; vertical-align: middle; padding: 12px 8px;">
                                <button type="button" class="tsfs-status-toggle-btn" data-id="<?php echo esc_attr( $entry['id'] ); ?>" data-status="<?php echo esc_attr( $entry['status'] ); ?>" style="border:none; background:transparent; cursor:pointer; padding:0;" title="<?php esc_attr_e( 'Click to toggle status', 'techsolution-form-studio' ); ?>">
                                    <?php if ( $is_unread ) : ?>
                                        <span class="tsfs-entry-status-badge is-unread">
                                            <span class="tsfs-dot-pulse"></span> Unread
                                        </span>
                                    <?php elseif ( $is_starred ) : ?>
                                        <span class="tsfs-entry-status-badge is-starred">
                                            <span class="dashicons dashicons-star-filled"></span> Starred
                                        </span>
                                    <?php else : ?>
                                        <span class="tsfs-entry-status-badge is-read">
                                            <span class="dashicons dashicons-yes"></span> Read
                                        </span>
                                    <?php endif; ?>
                                </button>
                            </td>
                            <td class="column-form" style="width: 170px; text-align: left; vertical-align: middle; padding: 12px 14px;">
                                <span class="tsfs-form-cell-title"><?php echo esc_html( $entry['form_title'] ? $entry['form_title'] : sprintf( /* translators: %d: form ID */ __( 'Form #%d', 'techsolution-form-studio' ), absint( $entry['form_id'] ) ) ); ?></span>
                            </td>
                            <?php foreach ( $submission_columns as $column_key => $column_label ) : ?>
                                <?php
                                $value = isset( $entry_fields[ $column_key ] ) ? $entry_fields[ $column_key ] : '';
                                $display_value = '';
                                if ( is_array( $value ) && ! isset( $value['url'] ) ) {
                                    $value_parts = array();
                                    foreach ( $value as $value_key => $value_item ) {
                                        if ( is_scalar( $value_item ) && '' !== trim( (string) $value_item ) ) {
                                            $value_parts[] = is_numeric( $value_key )
                                                ? (string) $value_item
                                                : ucwords( str_replace( '_', ' ', $value_key ) ) . ': ' . $value_item;
                                        }
                                    }
                                    $display_value = implode( ', ', $value_parts );
                                } elseif ( is_scalar( $value ) ) {
                                    $display_value = (string) $value;
                                }
                                ?>
                                <td class="column-submission-field">
                                    <?php if ( is_array( $value ) && ! empty( $value['url'] ) ) : ?>
                                        <a class="tsfs-submission-file-link" href="<?php echo esc_url( $value['url'] ); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo esc_attr( ! empty( $value['file'] ) ? $value['file'] : __( 'Download attached file', 'techsolution-form-studio' ) ); ?>">
                                            <span class="dashicons dashicons-media-document" aria-hidden="true"></span>
                                            <?php echo esc_html( ! empty( $value['file'] ) ? wp_trim_words( $value['file'], 4 ) : __( 'View file', 'techsolution-form-studio' ) ); ?>
                                        </a>
                                    <?php elseif ( '' !== trim( $display_value ) ) : ?>
                                        <span class="tsfs-submission-cell-value" title="<?php echo esc_attr( $display_value ); ?>">
                                            <?php echo esc_html( wp_trim_words( $display_value, 12 ) ); ?>
                                        </span>
                                    <?php else : ?>
                                        <span class="tsfs-submission-cell-empty" aria-label="<?php esc_attr_e( 'No answer', 'techsolution-form-studio' ); ?>">—</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                            <td class="column-ip" style="width: 130px; text-align: center; vertical-align: middle; padding: 12px 8px;">
                                <span class="tsfs-ip-badge" title="<?php esc_attr_e( 'Submitter IP Address', 'techsolution-form-studio' ); ?>"><?php echo esc_html( $entry['user_ip'] ? $entry['user_ip'] : '—' ); ?></span>
                            </td>
                            <td class="column-date" style="width: 140px; text-align: left; vertical-align: middle; padding: 12px 12px;">
                                <div class="tsfs-date-stack">
                                    <span class="tsfs-date-main">
                                        <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $entry['created_at'] ) ) ); ?>
                                    </span>
                                    <span class="tsfs-date-sub">
                                        <?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $entry['created_at'] ) ) ); ?>
                                    </span>
                                </div>
                            </td>
                            <td class="column-actions" style="width: 110px; text-align: right; vertical-align: middle; padding: 12px 14px;">
                                <div class="tsfs-entry-row-actions">
                                    <button type="button" class="button button-small tsfs-action-btn-view tsfs-view-entry-btn" data-id="<?php echo esc_attr( $entry['id'] ); ?>" title="<?php esc_attr_e( 'Inspect Submission Details', 'techsolution-form-studio' ); ?>">
                                        <span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'View', 'techsolution-form-studio' ); ?>
                                    </button>
                                    <button type="button" class="button button-small tsfs-action-btn-trash tsfs-delete-entry-btn" data-id="<?php echo esc_attr( $entry['id'] ); ?>" title="<?php esc_attr_e( 'Delete Entry', 'techsolution-form-studio' ); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Custom Pagination Bar with Page Jump and Per-Page Selector -->
        <div class="tsfs-pagination-bar">
            <div class="tsfs-pagination-info">
                <?php /* translators: 1: first entry number, 2: last entry number, 3: total entries */ printf( esc_html__( 'Showing %1$d – %2$d of %3$d submissions', 'techsolution-form-studio' ), absint( $start_entry ), absint( $end_entry ), absint( $total_entries ) ); ?>
                <span style="color:var(--tsfs-adm-slate-400); margin:0 8px;">|</span>
                <label style="display:inline-flex; align-items:center; gap:5px; font-weight:600; color:var(--tsfs-adm-slate-600);">
                    <?php esc_html_e( 'Items per page:', 'techsolution-form-studio' ); ?>
                    <select id="tsfs-per-page-bottom" class="tsfs-per-page-select" style="height:30px; font-size:12px; border-radius:6px; border:1px solid var(--tsfs-adm-slate-300); font-weight:600; padding:2px 24px 2px 8px;">
                        <option value="10" <?php selected( $per_page, 10 ); ?>>10</option>
                        <option value="20" <?php selected( $per_page, 20 ); ?>>20</option>
                        <option value="50" <?php selected( $per_page, 50 ); ?>>50</option>
                        <option value="100" <?php selected( $per_page, 100 ); ?>>100</option>
                    </select>
                </label>
            </div>

            <?php if ( $total_pages > 1 ) : ?>
                <div class="tsfs-pagination-controls">
                    <!-- First Page -->
                    <a href="<?php echo esc_url( add_query_arg( 'paged', 1, $pagination_base_url ) ); ?>" 
                       class="tsfs-pagination-btn <?php echo ( $paged <= 1 ) ? 'disabled' : ''; ?>" 
                       title="<?php esc_attr_e( 'First page', 'techsolution-form-studio' ); ?>">
                        <span class="dashicons dashicons-controls-skipback"></span>
                    </a>

                    <!-- Previous Page -->
                    <a href="<?php echo esc_url( add_query_arg( 'paged', max( 1, $paged - 1 ), $pagination_base_url ) ); ?>" 
                       class="tsfs-pagination-btn <?php echo ( $paged <= 1 ) ? 'disabled' : ''; ?>" 
                       title="<?php esc_attr_e( 'Previous page', 'techsolution-form-studio' ); ?>">
                        <span class="dashicons dashicons-arrow-left-alt2"></span>
                    </a>

                    <!-- Page Indicator with Jump Input -->
                    <span class="tsfs-page-indicator">
                        <?php esc_html_e( 'Page', 'techsolution-form-studio' ); ?>
                        <input type="number" id="tsfs-page-jump-input" min="1" max="<?php echo esc_attr( $total_pages ); ?>" value="<?php echo esc_attr( $paged ); ?>" style="width:52px; height:30px; text-align:center; border-radius:6px; border:1px solid var(--tsfs-adm-slate-300); font-weight:700; margin:0 4px; padding:2px 4px;">
                        <?php /* translators: %d: total pages */ printf( esc_html__( 'of %d', 'techsolution-form-studio' ), absint( $total_pages ) ); ?>
                    </span>

                    <!-- Next Page -->
                    <a href="<?php echo esc_url( add_query_arg( 'paged', min( $total_pages, $paged + 1 ), $pagination_base_url ) ); ?>" 
                       class="tsfs-pagination-btn <?php echo ( $paged >= $total_pages ) ? 'disabled' : ''; ?>" 
                       title="<?php esc_attr_e( 'Next page', 'techsolution-form-studio' ); ?>">
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </a>

                    <!-- Last Page -->
                    <a href="<?php echo esc_url( add_query_arg( 'paged', $total_pages, $pagination_base_url ) ); ?>" 
                       class="tsfs-pagination-btn <?php echo ( $paged >= $total_pages ) ? 'disabled' : ''; ?>" 
                       title="<?php esc_attr_e( 'Last page', 'techsolution-form-studio' ); ?>">
                        <span class="dashicons dashicons-controls-skipforward"></span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Print Stylesheet for Submission Details -->


<!-- Advanced Submissions Export Modal (Format selection, Header picker, Date/Form meta preview) -->
<div id="tsfs-export-modal" class="tsfs-modal-backdrop" style="display:none; z-index:99999;">
    <div class="tsfs-modal-dialog" style="max-width: 680px; width: 95%;">
        <!-- Modal Top Header -->
        <div class="tsfs-modal-header" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color:#ffffff; padding:18px 24px; border-top-left-radius:8px; border-top-right-radius:8px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <span class="dashicons dashicons-download" style="font-size:24px; width:24px; height:24px; color:#38bdf8;"></span>
                <div>
                    <h3 style="margin:0; font-size:17px; font-weight:700; color:#ffffff;">
                        <?php esc_html_e( 'Export Submissions', 'techsolution-form-studio' ); ?>
                    </h3>
                    <p style="margin:2px 0 0 0; font-size:12px; color:#94a3b8;">
                        <?php esc_html_e( 'Choose file format, date filters, and customizable table headers.', 'techsolution-form-studio' ); ?>
                    </p>
                </div>
            </div>
            <button type="button" class="tsfs-modal-close" id="tsfs-export-modal-close-btn" style="color:#94a3b8; font-size:24px; cursor:pointer;" title="<?php esc_attr_e( 'Close', 'techsolution-form-studio' ); ?>">&times;</button>
        </div>

        <form id="tsfs-export-modal-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="tsfs_export_entries">
            <input type="hidden" name="tsfs_nonce" value="<?php echo esc_attr( wp_create_nonce( 'tsfs_export_entries' ) ); ?>">
            <input type="hidden" name="s" value="<?php echo esc_attr( $search ); ?>">
            <input type="hidden" name="status" id="tsfs-export-form-status" value="<?php echo esc_attr( $status ); ?>">
            <input type="hidden" name="entry_ids" id="tsfs-export-selected-ids" value="">

            <div class="tsfs-modal-body" style="padding: 22px 24px; max-height: calc(85vh - 140px); overflow-y: auto;">
                
                <!-- 1. Selection Scope Banner -->
                <div id="tsfs-export-scope-banner" style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:12px 16px; margin-bottom:18px; display:flex; align-items:center; justify-content:space-between;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <span class="dashicons dashicons-info" style="color:#2563eb; font-size:18px; width:18px; height:18px;"></span>
                        <div>
                            <strong style="color:#1e3a8a; font-size:13px;" id="tsfs-export-scope-title"><?php esc_html_e( 'Exporting Filtered Submissions', 'techsolution-form-studio' ); ?></strong>
                            <div style="font-size:12px; color:#3b82f6;" id="tsfs-export-scope-desc"><?php /* translators: %d: number of records */ printf( esc_html__( 'Total matching: %d records found', 'techsolution-form-studio' ), absint( $total_entries ) ); ?></div>
                        </div>
                    </div>
                    <span class="tsfs-badge-free" id="tsfs-export-scope-badge" style="background:#dbeafe; color:#1e40af; font-weight:700; font-size:11.5px; padding:3px 10px; border-radius:12px;">
                        <?php echo esc_html( $total_entries ); ?> <?php esc_html_e( 'Records', 'techsolution-form-studio' ); ?>
                    </span>
                </div>

                <!-- 2. Export Format Selection -->
                <div style="margin-bottom: 20px;">
                    <label style="display:block; font-size:12px; font-weight:700; color:var(--tsfs-adm-slate-700); text-transform:uppercase; margin-bottom:8px; letter-spacing:0.5px;">
                        <?php esc_html_e( '1. Select Export Format', 'techsolution-form-studio' ); ?> <span style="color:#ef4444;">*</span>
                    </label>
                    <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:12px;">
                        <!-- Format: CSV -->
                        <label class="tsfs-format-card is-selected" style="border:2px solid #2563eb; background:#f8fafc; border-radius:8px; padding:12px 14px; cursor:pointer; display:flex; flex-direction:column; gap:4px; transition:all 0.15s ease;">
                            <div style="display:flex; align-items:center; justify-content:space-between;">
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span class="dashicons dashicons-media-spreadsheet" style="color:#059669; font-size:20px; width:20px; height:20px;"></span>
                                    <strong style="font-size:13.5px; color:#0f172a;">CSV</strong>
                                </div>
                                <input type="radio" name="export_format" value="csv" checked style="margin:0;">
                            </div>
                            <span style="font-size:11px; color:#64748b;"><?php esc_html_e( 'Universal flat CSV spreadsheet with UTF-8 BOM', 'techsolution-form-studio' ); ?></span>
                        </label>

                        <!-- Format: Excel -->
                        <label class="tsfs-format-card" style="border:1.5px solid #cbd5e1; background:#ffffff; border-radius:8px; padding:12px 14px; cursor:pointer; display:flex; flex-direction:column; gap:4px; transition:all 0.15s ease;">
                            <div style="display:flex; align-items:center; justify-content:space-between;">
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span class="dashicons dashicons-media-default" style="color:#10b981; font-size:20px; width:20px; height:20px;"></span>
                                    <strong style="font-size:13.5px; color:#0f172a;">Excel (.xls)</strong>
                                </div>
                                <input type="radio" name="export_format" value="excel" style="margin:0;">
                            </div>
                            <span style="font-size:11px; color:#64748b;"><?php esc_html_e( 'Formatted XML workbook with meta header styling', 'techsolution-form-studio' ); ?></span>
                        </label>

                        <!-- Format: PDF / Print -->
                        <label class="tsfs-format-card" style="border:1.5px solid #cbd5e1; background:#ffffff; border-radius:8px; padding:12px 14px; cursor:pointer; display:flex; flex-direction:column; gap:4px; transition:all 0.15s ease;">
                            <div style="display:flex; align-items:center; justify-content:space-between;">
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span class="dashicons dashicons-pdf" style="color:#dc2626; font-size:20px; width:20px; height:20px;"></span>
                                    <strong style="font-size:13.5px; color:#0f172a;">PDF / Print</strong>
                                </div>
                                <input type="radio" name="export_format" value="pdf" style="margin:0;">
                            </div>
                            <span style="font-size:11px; color:#64748b;"><?php esc_html_e( 'Executive landscape PDF report ready to print/save', 'techsolution-form-studio' ); ?></span>
                        </label>
                    </div>
                </div>

                <!-- 3. Form & Date Range Meta Options -->
                <div style="margin-bottom: 20px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:16px;">
                    <label style="display:block; font-size:12px; font-weight:700; color:var(--tsfs-adm-slate-700); text-transform:uppercase; margin-bottom:12px; letter-spacing:0.5px;">
                        <?php esc_html_e( '2. Form & Date Range Scope', 'techsolution-form-studio' ); ?>
                    </label>
                    
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
                        <!-- Select Form -->
                        <div style="grid-column: span 2;">
                            <label for="tsfs-export-modal-form-id" style="display:block; font-size:11.5px; font-weight:600; color:#475569; margin-bottom:4px;">
                                <?php esc_html_e( 'Target Form:', 'techsolution-form-studio' ); ?>
                            </label>
                            <select name="form_id" id="tsfs-export-modal-form-id" style="width:100%; border-radius:6px; border:1.5px solid #cbd5e1; height:36px; font-size:13px; background:#fff;">
                                <option value="0" <?php selected( $selected_form_id, 0 ); ?>><?php esc_html_e( '— All Forms Combined —', 'techsolution-form-studio' ); ?></option>
                                <?php foreach ( $forms as $f ) : ?>
                                    <option value="<?php echo esc_attr( $f['id'] ); ?>" <?php selected( $selected_form_id, $f['id'] ); ?>>
                                        <?php echo esc_html( $f['title'] ); ?> (<?php echo esc_html( $f['entry_count'] ); ?> submissions)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Date From -->
                        <div>
                            <label for="tsfs-export-date-from" style="display:block; font-size:11.5px; font-weight:600; color:#475569; margin-bottom:4px;">
                                <?php esc_html_e( 'Date From:', 'techsolution-form-studio' ); ?>
                            </label>
                            <input type="date" name="date_from" id="tsfs-export-date-from" value="<?php echo esc_attr( $date_from ); ?>" style="width:100%; border-radius:6px; border:1.5px solid #cbd5e1; height:36px; font-size:13px; background:#fff;">
                        </div>

                        <!-- Date To -->
                        <div>
                            <label for="tsfs-export-date-to" style="display:block; font-size:11.5px; font-weight:600; color:#475569; margin-bottom:4px;">
                                <?php esc_html_e( 'Date To:', 'techsolution-form-studio' ); ?>
                            </label>
                            <input type="date" name="date_to" id="tsfs-export-date-to" value="<?php echo esc_attr( $date_to ); ?>" style="width:100%; border-radius:6px; border:1.5px solid #cbd5e1; height:36px; font-size:13px; background:#fff;">
                        </div>
                    </div>

                    <div style="font-size:11.5px; color:#64748b; display:flex; align-items:center; gap:6px;">
                        <span class="dashicons dashicons-yes-alt" style="color:#10b981; font-size:14px; width:14px; height:14px;"></span>
                        <span><?php esc_html_e( 'The exported file will include Form Title and Date From/To meta directly at the top of the report.', 'techsolution-form-studio' ); ?></span>
                    </div>
                </div>

                <!-- 4. Select Submission Headers (Columns to Export) -->
                <div>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                        <label style="display:block; font-size:12px; font-weight:700; color:var(--tsfs-adm-slate-700); text-transform:uppercase; letter-spacing:0.5px; margin:0;">
                            <?php esc_html_e( '3. Select Submission Headers (Columns)', 'techsolution-form-studio' ); ?> <span style="color:#ef4444;">*</span>
                        </label>
                        <div style="display:flex; gap:8px;">
                            <button type="button" id="tsfs-export-select-all-headers" class="button button-small" style="font-size:11px; height:24px; line-height:22px; border-radius:4px;">
                                <?php esc_html_e( 'Select All', 'techsolution-form-studio' ); ?>
                            </button>
                            <button type="button" id="tsfs-export-deselect-all-headers" class="button button-small" style="font-size:11px; height:24px; line-height:22px; border-radius:4px;">
                                <?php esc_html_e( 'Deselect All', 'techsolution-form-studio' ); ?>
                            </button>
                        </div>
                    </div>

                    <!-- Headers Checkboxes Container -->
                    <div id="tsfs-export-headers-container" style="border:1.5px solid #e2e8f0; border-radius:8px; padding:14px; background:#ffffff; max-height:210px; overflow-y:auto;">
                        <div id="tsfs-export-headers-loading" style="text-align:center; padding:18px; color:#64748b; font-size:12px;">
                            <span class="spinner is-active" style="float:none; margin:0 6px 0 0; vertical-align:middle;"></span>
                            <?php esc_html_e( 'Loading available field headers for this form...', 'techsolution-form-studio' ); ?>
                        </div>
                        <div id="tsfs-export-headers-list" style="display:grid; grid-template-columns:repeat(2, 1fr); gap:8px 16px;"></div>
                    </div>
                    <span id="tsfs-export-headers-count" style="display:block; margin-top:6px; font-size:11.5px; color:#64748b;"></span>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="tsfs-modal-footer" style="padding:16px 24px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; border-bottom-left-radius:8px; border-bottom-right-radius:8px;">
                <button type="button" class="button button-secondary" id="tsfs-export-modal-cancel-btn" style="height:36px; padding:0 16px; font-weight:600; border-radius:6px;">
                    <?php esc_html_e( 'Cancel', 'techsolution-form-studio' ); ?>
                </button>
                <button type="submit" id="tsfs-export-submit-btn" class="button button-primary" style="height:36px; padding:0 22px; font-weight:700; border-radius:6px; background:#2563eb; border-color:#2563eb; display:inline-flex; align-items:center; gap:6px;">
                    <span class="dashicons dashicons-download" style="font-size:16px; width:16px; height:16px;"></span>
                    <span id="tsfs-export-submit-label"><?php esc_html_e( 'Download Export', 'techsolution-form-studio' ); ?></span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Enhanced Entry Details Modal (Inspection, Next/Prev, Toolbar, Internal Notes) -->
<div id="tsfs-entry-modal" class="tsfs-modal-backdrop" style="display:none;">
    <div class="tsfs-modal-dialog" style="max-width: 760px;">
        <!-- Modal Top Header -->
        <div class="tsfs-modal-header">
            <div style="display:flex; align-items:center; gap:10px;">
                <span class="dashicons dashicons-email-alt" style="font-size:22px; width:22px; height:22px; color:var(--tsfs-adm-primary);"></span>
                <h3 id="tsfs-modal-title" style="margin:0; font-size:16px; font-weight:700;">
                    <?php esc_html_e( 'Submission Details', 'techsolution-form-studio' ); ?>
                </h3>
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
                <span id="tsfs-modal-nav-counter" style="font-size:12px; font-weight:700; color:var(--tsfs-adm-slate-500); background:var(--tsfs-adm-slate-200); padding:3px 9px; border-radius:12px;">1 of 1</span>
                <button type="button" class="tsfs-modal-nav-btn" id="tsfs-modal-prev-btn" title="<?php esc_attr_e( 'Previous Submission (Left arrow key)', 'techsolution-form-studio' ); ?>">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                </button>
                <button type="button" class="tsfs-modal-nav-btn" id="tsfs-modal-next-btn" title="<?php esc_attr_e( 'Next Submission (Right arrow key)', 'techsolution-form-studio' ); ?>">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </button>
                <button type="button" class="tsfs-modal-close" id="tsfs-modal-close-btn" title="<?php esc_attr_e( 'Close dialog (Esc)', 'techsolution-form-studio' ); ?>">&times;</button>
            </div>
        </div>

        <!-- In-Modal Quick Action Toolbar -->
        <div class="tsfs-modal-toolbar-top">
            <div class="tsfs-modal-quick-actions">
                <!-- Star Toggle Button -->
                <button type="button" class="tsfs-modal-action-btn" id="tsfs-modal-star-btn" title="<?php esc_attr_e( 'Toggle star flag for this entry', 'techsolution-form-studio' ); ?>">
                    <span class="dashicons dashicons-star-empty" id="tsfs-modal-star-icon"></span>
                    <span id="tsfs-modal-star-label"><?php esc_html_e( 'Star', 'techsolution-form-studio' ); ?></span>
                </button>

                <!-- Status Toggle Button -->
                <button type="button" class="tsfs-modal-action-btn" id="tsfs-modal-status-btn" title="<?php esc_attr_e( 'Toggle Read / Unread status', 'techsolution-form-studio' ); ?>">
                    <span class="dashicons dashicons-marker" id="tsfs-modal-status-icon"></span>
                    <span id="tsfs-modal-status-label"><?php esc_html_e( 'Mark Unread', 'techsolution-form-studio' ); ?></span>
                </button>

                <!-- 1-Click Copy Summary -->
                <button type="button" class="tsfs-modal-action-btn" id="tsfs-modal-copy-btn" title="<?php esc_attr_e( 'Copy clean plain-text summary to clipboard', 'techsolution-form-studio' ); ?>">
                    <span class="dashicons dashicons-clipboard"></span>
                    <span><?php esc_html_e( 'Copy Summary', 'techsolution-form-studio' ); ?></span>
                </button>
            </div>

            <div>
                <!-- Print Record -->
                <button type="button" class="tsfs-modal-action-btn" data-tsfs-print-entry title="<?php esc_attr_e( 'Print clean record sheet', 'techsolution-form-studio' ); ?>">
                    <span class="dashicons dashicons-printer"></span>
                    <span><?php esc_html_e( 'Print', 'techsolution-form-studio' ); ?></span>
                </button>
            </div>
        </div>

        <!-- Modal Body Content -->
        <div class="tsfs-modal-body" id="tsfs-modal-body">
            <div class="spinner is-active" style="float:none; margin: 30px auto; display:block;"></div>
        </div>

        <!-- Modal Footer -->
        <div class="tsfs-modal-footer" style="display:flex; justify-content:space-between; align-items:center;">
            <span style="font-size:11.5px; color:var(--tsfs-adm-slate-400);">
                <span class="dashicons dashicons-info" style="font-size:14px; width:14px; height:14px; vertical-align:middle;"></span>
                <?php esc_html_e( 'Keyboard: ← / → to navigate, Esc to close', 'techsolution-form-studio' ); ?>
            </span>
            <button type="button" class="button button-primary" id="tsfs-modal-done-btn"><?php esc_html_e( 'Done', 'techsolution-form-studio' ); ?></button>
        </div>
    </div>
</div>
