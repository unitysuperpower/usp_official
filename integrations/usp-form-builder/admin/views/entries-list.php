<?php
/**
 * View: Form Entries / Submissions Table with KPI Metric Cards, Quick Date Presets,
 * Advanced Multi-Criteria Filtering, Bulk Actions, Pagination, and Enhanced Inspection Modal.
 *
 * @package    USP_Form_Builder
 * @subpackage USP_Form_Builder/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
            'action'    => 'usp_export_entries',
            'form_id'   => $selected_form_id,
            'status'    => $status,
            'date_from' => $date_from,
            'date_to'   => $date_to,
            's'         => $search,
        ),
        admin_url( 'admin-post.php' )
    ),
    'usp_export_entries',
    'usp_nonce'
);

$base_url = admin_url( 'admin.php?page=usp-form-entries' );
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
$d7_str         = date( 'Y-m-d', strtotime( '-6 days', strtotime( $today_str ) ) );
$d30_str        = date( 'Y-m-d', strtotime( '-29 days', strtotime( $today_str ) ) );
$month_str      = date( 'Y-m-01', strtotime( $today_str ) );

$is_today_preset = ( $date_from === $today_str && $date_to === $today_str );
$is_7d_preset    = ( $date_from === $d7_str && $date_to === $today_str );
$is_30d_preset   = ( $date_from === $d30_str && $date_to === $today_str );
$is_month_preset = ( $date_from === $month_str && $date_to === $today_str );
$is_all_preset   = ( empty( $date_from ) && empty( $date_to ) );

// Pagination Base URL
$pagination_base_url = admin_url( 'admin.php?page=usp-form-entries' );
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

$submission_columns = array();
$submission_field_key = static function ( $form_id, $field ) use ( $selected_form_id ) {
    if ( empty( $selected_form_id ) && ! empty( $field['label'] ) ) {
        $identifier = 'label:' . strtolower( trim( $field['label'] ) );
    } elseif ( ! empty( $field['name'] ) ) {
        $identifier = 'name:' . $field['name'];
    } elseif ( ! empty( $field['id'] ) ) {
        $identifier = 'id:' . $field['id'];
    } else {
        $identifier = 'label:' . ( ! empty( $field['label'] ) ? $field['label'] : __( 'Field', 'usp-form-builder' ) );
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
                : ( ! empty( $field['name'] ) ? ucfirst( str_replace( '_', ' ', $field['name'] ) ) : __( 'Field', 'usp-form-builder' ) );
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
                : ( ! empty( $field['name'] ) ? ucfirst( str_replace( '_', ' ', $field['name'] ) ) : __( 'Field', 'usp-form-builder' ) );
        }
    }
}
?>

<div class="wrap usp-admin-wrap">
    <!-- Page Header Bar -->
    <div class="usp-page-header">
        <div>
            <h1 class="wp-heading-inline">
                <span class="dashicons dashicons-email-alt" style="color:var(--usp-adm-primary); font-size:26px; width:26px; height:26px;"></span>
                <?php esc_html_e( 'Form Submissions', 'usp-form-builder' ); ?>
            </h1>
        </div>
        <div class="usp-header-badge" style="display:flex; align-items:center; gap:8px;">
            <button type="button" id="usp-open-export-modal-btn" class="button button-primary" style="font-weight:600; border-radius:6px; height:34px; display:inline-flex; align-items:center; gap:6px; background:var(--usp-adm-primary); border-color:var(--usp-adm-primary); color:#fff; cursor:pointer;" title="<?php esc_attr_e( 'Export submissions with customized options and headers', 'usp-form-builder' ); ?>">
                <span class="dashicons dashicons-download" style="font-size:16px; width:16px; height:16px;"></span> <?php esc_html_e( 'Export Submissions', 'usp-form-builder' ); ?>
            </button>
            <span class="usp-badge-free" style="height:32px; display:inline-flex; align-items:center; padding:0 12px; border-radius:16px; font-weight:700; font-size:12px;">
                <?php echo esc_html( number_format_i18n( $total_entries ) ); ?> <?php esc_html_e( 'Submissions Found', 'usp-form-builder' ); ?>
            </span>
        </div>
    </div>

    <!-- 1. Top KPI Metric Cards (1-Click Filtering) -->
    <div class="usp-kpi-grid">
        <!-- Card 1: Total Submissions -->
        <a href="<?php echo esc_url( remove_query_arg( array( 'status', 'date_from', 'date_to', 'paged' ), $base_url ) ); ?>" 
           class="usp-kpi-card usp-kpi-card-all <?php echo ( empty( $status ) && empty( $date_from ) && empty( $date_to ) ) ? 'is-active' : ''; ?>"
           title="<?php esc_attr_e( 'Click to view all submissions', 'usp-form-builder' ); ?>">
            <div class="usp-kpi-icon-wrap">
                <span class="dashicons dashicons-email-alt"></span>
            </div>
            <div class="usp-kpi-content">
                <div class="usp-kpi-label">
                    <span><?php esc_html_e( 'Total Submissions', 'usp-form-builder' ); ?></span>
                </div>
                <div class="usp-kpi-value"><?php echo esc_html( number_format_i18n( $status_counts['all'] ) ); ?></div>
                <div class="usp-kpi-subtext">
                    <span><?php esc_html_e( 'All records', 'usp-form-builder' ); ?></span>
                </div>
            </div>
        </a>

        <!-- Card 2: Unread Inbox -->
        <a href="<?php echo esc_url( add_query_arg( array( 'status' => 'unread', 'paged' => 1 ), remove_query_arg( array( 'date_from', 'date_to' ), $base_url ) ) ); ?>" 
           class="usp-kpi-card usp-kpi-card-unread <?php echo ( 'unread' === $status ) ? 'is-active' : ''; ?>"
           title="<?php esc_attr_e( 'Click to filter unread submissions', 'usp-form-builder' ); ?>">
            <div class="usp-kpi-icon-wrap">
                <span class="dashicons dashicons-email"></span>
                <?php if ( ! empty( $status_counts['unread'] ) ) : ?>
                    <span class="usp-kpi-pulse-dot" title="<?php esc_attr_e( 'Unread records need attention', 'usp-form-builder' ); ?>"></span>
                <?php endif; ?>
            </div>
            <div class="usp-kpi-content">
                <div class="usp-kpi-label">
                    <span><?php esc_html_e( 'Unread Inbox', 'usp-form-builder' ); ?></span>
                </div>
                <div class="usp-kpi-value"><?php echo esc_html( number_format_i18n( $status_counts['unread'] ) ); ?></div>
                <div class="usp-kpi-subtext">
                    <span><?php esc_html_e( 'Needs review', 'usp-form-builder' ); ?></span>
                </div>
            </div>
        </a>

        <!-- Card 3: Starred Records -->
        <a href="<?php echo esc_url( add_query_arg( array( 'status' => 'starred', 'paged' => 1 ), remove_query_arg( array( 'date_from', 'date_to' ), $base_url ) ) ); ?>" 
           class="usp-kpi-card usp-kpi-card-starred <?php echo ( 'starred' === $status ) ? 'is-active' : ''; ?>"
           title="<?php esc_attr_e( 'Click to view starred submissions', 'usp-form-builder' ); ?>">
            <div class="usp-kpi-icon-wrap">
                <span class="dashicons dashicons-star-filled"></span>
            </div>
            <div class="usp-kpi-content">
                <div class="usp-kpi-label">
                    <span><?php esc_html_e( 'Starred / Flagged', 'usp-form-builder' ); ?></span>
                </div>
                <div class="usp-kpi-value"><?php echo esc_html( number_format_i18n( $status_counts['starred'] ) ); ?></div>
                <div class="usp-kpi-subtext">
                    <span><?php esc_html_e( 'High priority', 'usp-form-builder' ); ?></span>
                </div>
            </div>
        </a>

        <!-- Card 4: Today's Activity -->
        <a href="<?php echo esc_url( add_query_arg( array( 'date_from' => $today_str, 'date_to' => $today_str, 'paged' => 1 ), remove_query_arg( 'status', $base_url ) ) ); ?>" 
           class="usp-kpi-card usp-kpi-card-today <?php echo $is_today_preset ? 'is-active' : ''; ?>"
           title="<?php esc_attr_e( 'Click to filter today\'s submissions', 'usp-form-builder' ); ?>">
            <div class="usp-kpi-icon-wrap">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="usp-kpi-content">
                <div class="usp-kpi-label">
                    <span><?php esc_html_e( 'Today\'s Submissions', 'usp-form-builder' ); ?></span>
                </div>
                <div class="usp-kpi-value"><?php echo esc_html( number_format_i18n( $status_counts['today'] ) ); ?></div>
                <div class="usp-kpi-subtext">
                    <span><?php esc_html_e( 'Received today', 'usp-form-builder' ); ?></span>
                </div>
            </div>
        </a>
    </div>

    <!-- Quick Status Filter Segmented Pills (All / Unread / Read / Starred) -->
    <div style="display:inline-flex; align-items:center; background:#fff; border:1px solid var(--usp-adm-slate-200); border-radius:8px; padding:3px; margin-bottom:16px; gap:3px; box-shadow:var(--usp-adm-shadow-sm); flex-wrap:wrap;">
        <a href="<?php echo esc_url( remove_query_arg( array( 'status', 'paged' ), $base_url ) ); ?>" 
           style="display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:6px; font-size:13px; font-weight:600; text-decoration:none; transition:all 0.15s ease; <?php echo empty( $status ) ? 'background:var(--usp-adm-primary); color:#fff;' : 'color:var(--usp-adm-slate-600);'; ?>">
            <span><?php esc_html_e( 'All Submissions', 'usp-form-builder' ); ?></span>
            <span style="font-size:11px; font-weight:700; padding:1px 6px; border-radius:10px; <?php echo empty( $status ) ? 'background:rgba(255,255,255,0.25); color:#fff;' : 'background:var(--usp-adm-slate-100); color:var(--usp-adm-slate-700);'; ?>"><?php echo esc_html( $status_counts['all'] ); ?></span>
        </a>
        <a href="<?php echo esc_url( add_query_arg( array( 'status' => 'unread', 'paged' => 1 ), $base_url ) ); ?>" 
           style="display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:6px; font-size:13px; font-weight:600; text-decoration:none; transition:all 0.15s ease; <?php echo ( 'unread' === $status ) ? 'background:var(--usp-adm-primary); color:#fff;' : 'color:var(--usp-adm-slate-600);'; ?>">
            <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:<?php echo ( 'unread' === $status ) ? '#fff' : '#2563eb'; ?>;"></span>
            <span><?php esc_html_e( 'Unread', 'usp-form-builder' ); ?></span>
            <span style="font-size:11px; font-weight:700; padding:1px 6px; border-radius:10px; <?php echo ( 'unread' === $status ) ? 'background:rgba(255,255,255,0.25); color:#fff;' : 'background:var(--usp-adm-slate-100); color:var(--usp-adm-slate-700);'; ?>"><?php echo esc_html( $status_counts['unread'] ); ?></span>
        </a>
        <a href="<?php echo esc_url( add_query_arg( array( 'status' => 'read', 'paged' => 1 ), $base_url ) ); ?>" 
           style="display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:6px; font-size:13px; font-weight:600; text-decoration:none; transition:all 0.15s ease; <?php echo ( 'read' === $status ) ? 'background:var(--usp-adm-primary); color:#fff;' : 'color:var(--usp-adm-slate-600);'; ?>">
            <span class="dashicons dashicons-yes" style="font-size:14px; width:14px; height:14px; color:<?php echo ( 'read' === $status ) ? '#fff' : '#10b981'; ?>;"></span>
            <span><?php esc_html_e( 'Read', 'usp-form-builder' ); ?></span>
            <span style="font-size:11px; font-weight:700; padding:1px 6px; border-radius:10px; <?php echo ( 'read' === $status ) ? 'background:rgba(255,255,255,0.25); color:#fff;' : 'background:var(--usp-adm-slate-100); color:var(--usp-adm-slate-700);'; ?>"><?php echo esc_html( $status_counts['read'] ); ?></span>
        </a>
        <a href="<?php echo esc_url( add_query_arg( array( 'status' => 'starred', 'paged' => 1 ), $base_url ) ); ?>" 
           style="display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:6px; font-size:13px; font-weight:600; text-decoration:none; transition:all 0.15s ease; <?php echo ( 'starred' === $status ) ? 'background:var(--usp-adm-primary); color:#fff;' : 'color:var(--usp-adm-slate-600);'; ?>">
            <span class="dashicons dashicons-star-filled" style="color:<?php echo ( 'starred' === $status ) ? '#fff' : '#f59e0b'; ?>; font-size:14px; width:14px; height:14px;"></span>
            <span><?php esc_html_e( 'Starred', 'usp-form-builder' ); ?></span>
            <span style="font-size:11px; font-weight:700; padding:1px 6px; border-radius:10px; <?php echo ( 'starred' === $status ) ? 'background:rgba(255,255,255,0.25); color:#fff;' : 'background:var(--usp-adm-slate-100); color:var(--usp-adm-slate-700);'; ?>"><?php echo esc_html( $status_counts['starred'] ); ?></span>
        </a>
    </div>

    <!-- Advanced Filter Bar (With Quick Date Presets) -->
    <div style="background:#fff; border:1px solid var(--usp-adm-slate-200); border-radius:var(--usp-adm-radius-md); padding:16px 20px; box-shadow:var(--usp-adm-shadow-sm); margin-bottom:20px;">
        <form method="get" action="" id="usp-entries-filter-form">
            <input type="hidden" name="page" value="usp-form-entries">
            <?php if ( ! empty( $status ) ) : ?>
                <input type="hidden" name="status" value="<?php echo esc_attr( $status ); ?>">
            <?php endif; ?>
            <?php if ( ! empty( $per_page ) && 20 !== $per_page ) : ?>
                <input type="hidden" name="per_page" value="<?php echo esc_attr( $per_page ); ?>">
            <?php endif; ?>

            <div style="display:grid; grid-template-columns: minmax(170px, 1.2fr) minmax(140px, 1fr) minmax(140px, 1fr) minmax(190px, 1.4fr) auto; gap:14px; align-items:end;">
                <!-- Filter by Form -->
                <div>
                    <label for="filter-by-form" style="display:block; font-size:11.5px; font-weight:700; color:var(--usp-adm-slate-600); text-transform:uppercase; margin-bottom:6px; letter-spacing:0.4px;">
                        <?php esc_html_e( 'Select Form', 'usp-form-builder' ); ?>
                    </label>
                    <select name="form_id" id="filter-by-form" style="width:100%; border-radius:6px; border:1.5px solid var(--usp-adm-slate-200); height:38px; font-size:13px; color:var(--usp-adm-slate-800); background:#fff;">
                        <option value="0"><?php esc_html_e( '— All Forms —', 'usp-form-builder' ); ?></option>
                        <?php foreach ( $forms as $f ) : ?>
                            <option value="<?php echo esc_attr( $f['id'] ); ?>" <?php selected( $selected_form_id, $f['id'] ); ?>>
                                <?php echo esc_html( $f['title'] ); ?> (<?php echo esc_html( $f['entry_count'] ); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Date Range: From -->
                <div>
                    <label for="filter-date-from" style="display:flex; align-items:center; gap:4px; font-size:11.5px; font-weight:700; color:var(--usp-adm-slate-600); text-transform:uppercase; margin-bottom:6px; letter-spacing:0.4px;">
                        <span class="dashicons dashicons-calendar-alt" style="font-size:14px; width:14px; height:14px; color:var(--usp-adm-slate-500);"></span>
                        <?php esc_html_e( 'Date From', 'usp-form-builder' ); ?>
                    </label>
                    <input type="date" name="date_from" id="filter-date-from" value="<?php echo esc_attr( $date_from ); ?>" style="width:100%; border-radius:6px; border:1.5px solid var(--usp-adm-slate-200); height:38px; padding:4px 10px; font-size:13px; color:var(--usp-adm-slate-800); background:#fff;">
                </div>

                <!-- Date Range: To -->
                <div>
                    <label for="filter-date-to" style="display:flex; align-items:center; gap:4px; font-size:11.5px; font-weight:700; color:var(--usp-adm-slate-600); text-transform:uppercase; margin-bottom:6px; letter-spacing:0.4px;">
                        <span class="dashicons dashicons-calendar-alt" style="font-size:14px; width:14px; height:14px; color:var(--usp-adm-slate-500);"></span>
                        <?php esc_html_e( 'Date To', 'usp-form-builder' ); ?>
                    </label>
                    <input type="date" name="date_to" id="filter-date-to" value="<?php echo esc_attr( $date_to ); ?>" style="width:100%; border-radius:6px; border:1.5px solid var(--usp-adm-slate-200); height:38px; padding:4px 10px; font-size:13px; color:var(--usp-adm-slate-800); background:#fff;">
                </div>

                <!-- Keyword Search -->
                <div>
                    <label for="filter-search" style="display:flex; align-items:center; gap:4px; font-size:11.5px; font-weight:700; color:var(--usp-adm-slate-600); text-transform:uppercase; margin-bottom:6px; letter-spacing:0.4px;">
                        <span class="dashicons dashicons-search" style="font-size:14px; width:14px; height:14px; color:var(--usp-adm-slate-500);"></span>
                        <?php esc_html_e( 'Search Content / IP', 'usp-form-builder' ); ?>
                    </label>
                    <input type="search" name="s" id="filter-search" placeholder="<?php esc_attr_e( 'e.g. name, email, text...', 'usp-form-builder' ); ?>" value="<?php echo esc_attr( $search ); ?>" style="width:100%; border-radius:6px; border:1.5px solid var(--usp-adm-slate-200); height:38px; padding:4px 10px; font-size:13px; color:var(--usp-adm-slate-800); background:#fff;">
                </div>

                <!-- Filter Action Buttons -->
                <div style="display:flex; gap:8px;">
                    <button type="submit" class="button button-primary" style="height:38px; padding:0 18px; font-weight:600; border-radius:6px; background:var(--usp-adm-primary); border-color:var(--usp-adm-primary); display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
                        <span class="dashicons dashicons-filter" style="font-size:16px; width:16px; height:16px;"></span> <?php esc_html_e( 'Apply Filter', 'usp-form-builder' ); ?>
                    </button>
                    <?php if ( $has_filters ) : ?>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=usp-form-entries' ) ); ?>" class="button button-secondary" style="height:38px; line-height:36px; padding:0 14px; font-weight:600; border-radius:6px;" title="<?php esc_attr_e( 'Reset all filters', 'usp-form-builder' ); ?>">
                            <?php esc_html_e( 'Clear', 'usp-form-builder' ); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Date Range Preset Buttons (Auto-Apply) -->
            <div class="usp-date-presets-row">
                <span class="usp-date-presets-label"><?php esc_html_e( 'Quick Dates:', 'usp-form-builder' ); ?></span>
                <button type="button" class="usp-date-preset-btn <?php echo $is_today_preset ? 'is-active' : ''; ?>" data-from="<?php echo esc_attr( $today_str ); ?>" data-to="<?php echo esc_attr( $today_str ); ?>">
                    <?php esc_html_e( 'Today', 'usp-form-builder' ); ?>
                </button>
                <button type="button" class="usp-date-preset-btn <?php echo $is_7d_preset ? 'is-active' : ''; ?>" data-from="<?php echo esc_attr( $d7_str ); ?>" data-to="<?php echo esc_attr( $today_str ); ?>">
                    <?php esc_html_e( 'Last 7 Days', 'usp-form-builder' ); ?>
                </button>
                <button type="button" class="usp-date-preset-btn <?php echo $is_30d_preset ? 'is-active' : ''; ?>" data-from="<?php echo esc_attr( $d30_str ); ?>" data-to="<?php echo esc_attr( $today_str ); ?>">
                    <?php esc_html_e( 'Last 30 Days', 'usp-form-builder' ); ?>
                </button>
                <button type="button" class="usp-date-preset-btn <?php echo $is_month_preset ? 'is-active' : ''; ?>" data-from="<?php echo esc_attr( $month_str ); ?>" data-to="<?php echo esc_attr( $today_str ); ?>">
                    <?php esc_html_e( 'This Month', 'usp-form-builder' ); ?>
                </button>
                <button type="button" class="usp-date-preset-btn <?php echo $is_all_preset ? 'is-active' : ''; ?>" data-from="" data-to="">
                    <?php esc_html_e( 'All Time', 'usp-form-builder' ); ?>
                </button>
            </div>
        </form>
    </div>

    <?php if ( empty( $entries ) ) : ?>
        <div class="usp-empty-state">
            <div class="usp-empty-icon dashicons dashicons-email"></div>
            <h2><?php esc_html_e( 'No Submissions Match Your Criteria', 'usp-form-builder' ); ?></h2>
            <p><?php esc_html_e( 'Try clearing or widening your date range and search filters.', 'usp-form-builder' ); ?></p>
            <?php if ( $has_filters ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=usp-form-entries' ) ); ?>" class="button button-secondary">
                    <?php esc_html_e( 'Clear All Filters', 'usp-form-builder' ); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php else : ?>
        <!-- Bulk Actions & Top Pagination Toolbar -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; background:#fff; padding:10px 16px; border:1px solid var(--usp-adm-slate-200); border-radius:6px; box-shadow:var(--usp-adm-shadow-sm); flex-wrap:wrap; gap:10px;">
            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                <select id="usp-bulk-action-selector" style="border-radius:6px; height:34px; border:1.5px solid var(--usp-adm-slate-300); font-size:13px;">
                    <option value="-1"><?php esc_html_e( '— Bulk actions —', 'usp-form-builder' ); ?></option>
                    <option value="export_selected"><?php esc_html_e( 'Export Selected Submissions...', 'usp-form-builder' ); ?></option>
                    <option value="mark_read"><?php esc_html_e( 'Mark as Read', 'usp-form-builder' ); ?></option>
                    <option value="mark_unread"><?php esc_html_e( 'Mark as Unread', 'usp-form-builder' ); ?></option>
                    <option value="star"><?php esc_html_e( 'Star / Flag', 'usp-form-builder' ); ?></option>
                    <option value="delete"><?php esc_html_e( 'Delete Permanently', 'usp-form-builder' ); ?></option>
                </select>
                <button type="button" id="usp-bulk-apply-btn" class="button action" style="height:34px; border-radius:6px; font-weight:600;">
                    <?php esc_html_e( 'Apply', 'usp-form-builder' ); ?>
                </button>
                <span id="usp-bulk-selected-count" style="font-size:12px; color:var(--usp-adm-slate-500); margin-left:8px; font-weight:600;"></span>
            </div>

            <div style="display:flex; align-items:center; gap:16px;">
                <!-- Per Page Selector Dropdown -->
                <div style="display:inline-flex; align-items:center; gap:6px; font-size:12.5px; color:var(--usp-adm-slate-600); font-weight:600;">
                    <span><?php esc_html_e( 'Per page:', 'usp-form-builder' ); ?></span>
                    <select id="usp-per-page-top" class="usp-per-page-select" style="height:32px; font-size:12.5px; border-radius:6px; border:1.5px solid var(--usp-adm-slate-300); font-weight:600; padding:2px 24px 2px 8px;">
                        <option value="10" <?php selected( $per_page, 10 ); ?>>10</option>
                        <option value="20" <?php selected( $per_page, 20 ); ?>>20</option>
                        <option value="50" <?php selected( $per_page, 50 ); ?>>50</option>
                        <option value="100" <?php selected( $per_page, 100 ); ?>>100</option>
                    </select>
                </div>

                <!-- Showing count indicator -->
                <span style="font-size:12.5px; color:var(--usp-adm-slate-500); font-weight:600;">
                    <?php printf( esc_html__( 'Showing %1$d – %2$d of %3$d', 'usp-form-builder' ), $start_entry, $end_entry, $total_entries ); ?>
                </span>
            </div>
        </div>

        <!-- Responsive Submissions Table -->
        <div class="usp-table-responsive-wrapper">
            <table class="wp-list-table widefat striped table-view-list usp-entries-table">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column check-column" style="width: 38px; min-width: 38px; text-align: center; vertical-align: middle; padding: 12px 0;">
                            <input type="checkbox" id="usp-cb-select-all" style="margin: 0 auto; display: block;">
                        </th>
                        <th scope="col" class="manage-column column-star" style="width: 36px; min-width: 36px; text-align: center; vertical-align: middle; padding: 12px 2px;">
                            <span class="dashicons dashicons-star-empty" style="font-size: 16px; width: 16px; height: 16px; color: #94a3b8; display: inline-block;" title="<?php esc_attr_e( 'Star', 'usp-form-builder' ); ?>"></span>
                        </th>
                        <th scope="col" class="manage-column column-id" style="width: 65px; min-width: 65px; text-align: center; vertical-align: middle; padding: 12px 8px;">
                            <?php esc_html_e( 'ID', 'usp-form-builder' ); ?>
                        </th>
                        <th scope="col" class="manage-column column-status" style="width: 95px; min-width: 95px; text-align: center; vertical-align: middle; padding: 12px 8px;">
                            <?php esc_html_e( 'Status', 'usp-form-builder' ); ?>
                        </th>
                        <th scope="col" class="manage-column column-form" style="width: 170px; min-width: 160px; text-align: left; vertical-align: middle; padding: 12px 14px;">
                            <?php esc_html_e( 'Form Title', 'usp-form-builder' ); ?>
                        </th>
                        <?php foreach ( $submission_columns as $column_label ) : ?>
                            <th scope="col" class="manage-column column-submission-field">
                                <?php echo esc_html( $column_label ); ?>
                            </th>
                        <?php endforeach; ?>
                        <th scope="col" class="manage-column column-ip" style="width: 130px; min-width: 130px; text-align: center; vertical-align: middle; padding: 12px 8px;">
                            <?php esc_html_e( 'Submitter IP', 'usp-form-builder' ); ?>
                        </th>
                        <th scope="col" class="manage-column column-date" style="width: 140px; min-width: 140px; text-align: left; vertical-align: middle; padding: 12px 12px;">
                            <?php esc_html_e( 'Submitted On', 'usp-form-builder' ); ?>
                        </th>
                        <th scope="col" class="manage-column column-actions" style="width: 110px; min-width: 110px; text-align: right; vertical-align: middle; padding: 12px 14px;">
                            <?php esc_html_e( 'Actions', 'usp-form-builder' ); ?>
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
                        <tr id="usp-entry-row-<?php echo esc_attr( $entry['id'] ); ?>" class="<?php echo $is_unread ? 'usp-row-unread' : ''; ?>">
                            <th scope="row" class="check-column" style="width: 38px; text-align: center; vertical-align: middle; padding: 12px 0;">
                                <input type="checkbox" class="usp-entry-cb" value="<?php echo esc_attr( $entry['id'] ); ?>" style="margin: 0 auto; display: block;">
                            </th>
                            <td class="column-star" style="width: 36px; text-align: center; vertical-align: middle; padding: 12px 2px;">
                                <button type="button" class="usp-star-toggle-btn" data-id="<?php echo esc_attr( $entry['id'] ); ?>" data-starred="<?php echo $is_starred ? '1' : '0'; ?>" style="background:transparent; border:none; cursor:pointer; padding:2px; display:inline-flex; align-items:center; justify-content:center;" title="<?php echo $is_starred ? esc_attr__( 'Starred - Click to unstar', 'usp-form-builder' ) : esc_attr__( 'Click to star', 'usp-form-builder' ); ?>">
                                    <span class="dashicons <?php echo $is_starred ? 'dashicons-star-filled' : 'dashicons-star-empty'; ?>" style="font-size:18px; width:18px; height:18px; color:<?php echo $is_starred ? '#f59e0b' : '#cbd5e1'; ?>;"></span>
                                </button>
                            </td>
                            <td class="column-id" style="width: 65px; text-align: center; vertical-align: middle; padding: 12px 8px;">
                                <span class="usp-id-badge">#<?php echo esc_html( $entry['id'] ); ?></span>
                                <div class="usp-row-note-wrapper" id="usp-row-note-wrapper-<?php echo esc_attr( $entry['id'] ); ?>" style="margin-top:3px;">
                                    <?php if ( ! empty( $entry_notes ) ) : ?>
                                        <span class="usp-row-note-indicator" title="<?php echo esc_attr( wp_trim_words( $entry_notes, 12 ) ); ?>">
                                            <span class="dashicons dashicons-admin-comments" style="font-size:11px; width:11px; height:11px;"></span> Note
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="column-status" style="width: 95px; text-align: center; vertical-align: middle; padding: 12px 8px;">
                                <button type="button" class="usp-status-toggle-btn" data-id="<?php echo esc_attr( $entry['id'] ); ?>" data-status="<?php echo esc_attr( $entry['status'] ); ?>" style="border:none; background:transparent; cursor:pointer; padding:0;" title="<?php esc_attr_e( 'Click to toggle status', 'usp-form-builder' ); ?>">
                                    <?php if ( $is_unread ) : ?>
                                        <span class="usp-entry-status-badge is-unread">
                                            <span class="usp-dot-pulse"></span> Unread
                                        </span>
                                    <?php elseif ( $is_starred ) : ?>
                                        <span class="usp-entry-status-badge is-starred">
                                            <span class="dashicons dashicons-star-filled"></span> Starred
                                        </span>
                                    <?php else : ?>
                                        <span class="usp-entry-status-badge is-read">
                                            <span class="dashicons dashicons-yes"></span> Read
                                        </span>
                                    <?php endif; ?>
                                </button>
                            </td>
                            <td class="column-form" style="width: 170px; text-align: left; vertical-align: middle; padding: 12px 14px;">
                                <span class="usp-form-cell-title"><?php echo esc_html( $entry['form_title'] ? $entry['form_title'] : __( 'Form #' . $entry['form_id'], 'usp-form-builder' ) ); ?></span>
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
                                        <a class="usp-submission-file-link" href="<?php echo esc_url( $value['url'] ); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo esc_attr( ! empty( $value['file'] ) ? $value['file'] : __( 'Download attached file', 'usp-form-builder' ) ); ?>">
                                            <span class="dashicons dashicons-media-document" aria-hidden="true"></span>
                                            <?php echo esc_html( ! empty( $value['file'] ) ? wp_trim_words( $value['file'], 4 ) : __( 'View file', 'usp-form-builder' ) ); ?>
                                        </a>
                                    <?php elseif ( '' !== trim( $display_value ) ) : ?>
                                        <span class="usp-submission-cell-value" title="<?php echo esc_attr( $display_value ); ?>">
                                            <?php echo esc_html( wp_trim_words( $display_value, 12 ) ); ?>
                                        </span>
                                    <?php else : ?>
                                        <span class="usp-submission-cell-empty" aria-label="<?php esc_attr_e( 'No answer', 'usp-form-builder' ); ?>">—</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                            <td class="column-ip" style="width: 130px; text-align: center; vertical-align: middle; padding: 12px 8px;">
                                <span class="usp-ip-badge" title="<?php esc_attr_e( 'Submitter IP Address', 'usp-form-builder' ); ?>"><?php echo esc_html( $entry['user_ip'] ? $entry['user_ip'] : '—' ); ?></span>
                            </td>
                            <td class="column-date" style="width: 140px; text-align: left; vertical-align: middle; padding: 12px 12px;">
                                <div class="usp-date-stack">
                                    <span class="usp-date-main">
                                        <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $entry['created_at'] ) ) ); ?>
                                    </span>
                                    <span class="usp-date-sub">
                                        <?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $entry['created_at'] ) ) ); ?>
                                    </span>
                                </div>
                            </td>
                            <td class="column-actions" style="width: 110px; text-align: right; vertical-align: middle; padding: 12px 14px;">
                                <div class="usp-entry-row-actions">
                                    <button type="button" class="button button-small usp-action-btn-view usp-view-entry-btn" data-id="<?php echo esc_attr( $entry['id'] ); ?>" title="<?php esc_attr_e( 'Inspect Submission Details', 'usp-form-builder' ); ?>">
                                        <span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'View', 'usp-form-builder' ); ?>
                                    </button>
                                    <button type="button" class="button button-small usp-action-btn-trash usp-delete-entry-btn" data-id="<?php echo esc_attr( $entry['id'] ); ?>" title="<?php esc_attr_e( 'Delete Entry', 'usp-form-builder' ); ?>">
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
        <div class="usp-pagination-bar">
            <div class="usp-pagination-info">
                <?php printf( esc_html__( 'Showing %1$d – %2$d of %3$d submissions', 'usp-form-builder' ), $start_entry, $end_entry, $total_entries ); ?>
                <span style="color:var(--usp-adm-slate-400); margin:0 8px;">|</span>
                <label style="display:inline-flex; align-items:center; gap:5px; font-weight:600; color:var(--usp-adm-slate-600);">
                    <?php esc_html_e( 'Items per page:', 'usp-form-builder' ); ?>
                    <select id="usp-per-page-bottom" class="usp-per-page-select" style="height:30px; font-size:12px; border-radius:6px; border:1px solid var(--usp-adm-slate-300); font-weight:600; padding:2px 24px 2px 8px;">
                        <option value="10" <?php selected( $per_page, 10 ); ?>>10</option>
                        <option value="20" <?php selected( $per_page, 20 ); ?>>20</option>
                        <option value="50" <?php selected( $per_page, 50 ); ?>>50</option>
                        <option value="100" <?php selected( $per_page, 100 ); ?>>100</option>
                    </select>
                </label>
            </div>

            <?php if ( $total_pages > 1 ) : ?>
                <div class="usp-pagination-controls">
                    <!-- First Page -->
                    <a href="<?php echo esc_url( add_query_arg( 'paged', 1, $pagination_base_url ) ); ?>" 
                       class="usp-pagination-btn <?php echo ( $paged <= 1 ) ? 'disabled' : ''; ?>" 
                       title="<?php esc_attr_e( 'First page', 'usp-form-builder' ); ?>">
                        <span class="dashicons dashicons-controls-skipback"></span>
                    </a>

                    <!-- Previous Page -->
                    <a href="<?php echo esc_url( add_query_arg( 'paged', max( 1, $paged - 1 ), $pagination_base_url ) ); ?>" 
                       class="usp-pagination-btn <?php echo ( $paged <= 1 ) ? 'disabled' : ''; ?>" 
                       title="<?php esc_attr_e( 'Previous page', 'usp-form-builder' ); ?>">
                        <span class="dashicons dashicons-arrow-left-alt2"></span>
                    </a>

                    <!-- Page Indicator with Jump Input -->
                    <span class="usp-page-indicator">
                        <?php esc_html_e( 'Page', 'usp-form-builder' ); ?>
                        <input type="number" id="usp-page-jump-input" min="1" max="<?php echo esc_attr( $total_pages ); ?>" value="<?php echo esc_attr( $paged ); ?>" style="width:52px; height:30px; text-align:center; border-radius:6px; border:1px solid var(--usp-adm-slate-300); font-weight:700; margin:0 4px; padding:2px 4px;">
                        <?php printf( esc_html__( 'of %d', 'usp-form-builder' ), $total_pages ); ?>
                    </span>

                    <!-- Next Page -->
                    <a href="<?php echo esc_url( add_query_arg( 'paged', min( $total_pages, $paged + 1 ), $pagination_base_url ) ); ?>" 
                       class="usp-pagination-btn <?php echo ( $paged >= $total_pages ) ? 'disabled' : ''; ?>" 
                       title="<?php esc_attr_e( 'Next page', 'usp-form-builder' ); ?>">
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </a>

                    <!-- Last Page -->
                    <a href="<?php echo esc_url( add_query_arg( 'paged', $total_pages, $pagination_base_url ) ); ?>" 
                       class="usp-pagination-btn <?php echo ( $paged >= $total_pages ) ? 'disabled' : ''; ?>" 
                       title="<?php esc_attr_e( 'Last page', 'usp-form-builder' ); ?>">
                        <span class="dashicons dashicons-controls-skipforward"></span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Print Stylesheet for Submission Details -->
<style>
@media print {
    body * {
        visibility: hidden !important;
    }
    #usp-entry-modal, #usp-entry-modal * {
        visibility: visible !important;
    }
    #usp-entry-modal {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        height: auto !important;
        background: #ffffff !important;
        padding: 0 !important;
    }
    .usp-modal-dialog {
        box-shadow: none !important;
        border: none !important;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    #usp-modal-close-btn, #usp-modal-done-btn, .usp-modal-toolbar-top, .usp-modal-footer, .usp-admin-notes-footer {
        display: none !important;
    }
    .usp-modal-footer {
        border-top: none !important;
        padding: 0 !important;
    }
}
</style>

<!-- Advanced Submissions Export Modal (Format selection, Header picker, Date/Form meta preview) -->
<div id="usp-export-modal" class="usp-modal-backdrop" style="display:none; z-index:99999;">
    <div class="usp-modal-dialog" style="max-width: 680px; width: 95%;">
        <!-- Modal Top Header -->
        <div class="usp-modal-header" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color:#ffffff; padding:18px 24px; border-top-left-radius:8px; border-top-right-radius:8px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <span class="dashicons dashicons-download" style="font-size:24px; width:24px; height:24px; color:#38bdf8;"></span>
                <div>
                    <h3 style="margin:0; font-size:17px; font-weight:700; color:#ffffff;">
                        <?php esc_html_e( 'Export Submissions', 'usp-form-builder' ); ?>
                    </h3>
                    <p style="margin:2px 0 0 0; font-size:12px; color:#94a3b8;">
                        <?php esc_html_e( 'Choose file format, date filters, and customizable table headers.', 'usp-form-builder' ); ?>
                    </p>
                </div>
            </div>
            <button type="button" class="usp-modal-close" id="usp-export-modal-close-btn" style="color:#94a3b8; font-size:24px; cursor:pointer;" title="<?php esc_attr_e( 'Close', 'usp-form-builder' ); ?>">&times;</button>
        </div>

        <form id="usp-export-modal-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="usp_export_entries">
            <input type="hidden" name="usp_nonce" value="<?php echo esc_attr( wp_create_nonce( 'usp_export_entries' ) ); ?>">
            <input type="hidden" name="s" value="<?php echo esc_attr( $search ); ?>">
            <input type="hidden" name="status" id="usp-export-form-status" value="<?php echo esc_attr( $status ); ?>">
            <input type="hidden" name="entry_ids" id="usp-export-selected-ids" value="">

            <div class="usp-modal-body" style="padding: 22px 24px; max-height: calc(85vh - 140px); overflow-y: auto;">
                
                <!-- 1. Selection Scope Banner -->
                <div id="usp-export-scope-banner" style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:12px 16px; margin-bottom:18px; display:flex; align-items:center; justify-content:space-between;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <span class="dashicons dashicons-info" style="color:#2563eb; font-size:18px; width:18px; height:18px;"></span>
                        <div>
                            <strong style="color:#1e3a8a; font-size:13px;" id="usp-export-scope-title"><?php esc_html_e( 'Exporting Filtered Submissions', 'usp-form-builder' ); ?></strong>
                            <div style="font-size:12px; color:#3b82f6;" id="usp-export-scope-desc"><?php printf( esc_html__( 'Total matching: %d records found', 'usp-form-builder' ), $total_entries ); ?></div>
                        </div>
                    </div>
                    <span class="usp-badge-free" id="usp-export-scope-badge" style="background:#dbeafe; color:#1e40af; font-weight:700; font-size:11.5px; padding:3px 10px; border-radius:12px;">
                        <?php echo esc_html( $total_entries ); ?> <?php esc_html_e( 'Records', 'usp-form-builder' ); ?>
                    </span>
                </div>

                <!-- 2. Export Format Selection -->
                <div style="margin-bottom: 20px;">
                    <label style="display:block; font-size:12px; font-weight:700; color:var(--usp-adm-slate-700); text-transform:uppercase; margin-bottom:8px; letter-spacing:0.5px;">
                        <?php esc_html_e( '1. Select Export Format', 'usp-form-builder' ); ?> <span style="color:#ef4444;">*</span>
                    </label>
                    <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:12px;">
                        <!-- Format: CSV -->
                        <label class="usp-format-card is-selected" style="border:2px solid #2563eb; background:#f8fafc; border-radius:8px; padding:12px 14px; cursor:pointer; display:flex; flex-direction:column; gap:4px; transition:all 0.15s ease;">
                            <div style="display:flex; align-items:center; justify-content:space-between;">
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span class="dashicons dashicons-media-spreadsheet" style="color:#059669; font-size:20px; width:20px; height:20px;"></span>
                                    <strong style="font-size:13.5px; color:#0f172a;">CSV</strong>
                                </div>
                                <input type="radio" name="export_format" value="csv" checked style="margin:0;">
                            </div>
                            <span style="font-size:11px; color:#64748b;"><?php esc_html_e( 'Universal flat CSV spreadsheet with UTF-8 BOM', 'usp-form-builder' ); ?></span>
                        </label>

                        <!-- Format: Excel -->
                        <label class="usp-format-card" style="border:1.5px solid #cbd5e1; background:#ffffff; border-radius:8px; padding:12px 14px; cursor:pointer; display:flex; flex-direction:column; gap:4px; transition:all 0.15s ease;">
                            <div style="display:flex; align-items:center; justify-content:space-between;">
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span class="dashicons dashicons-media-default" style="color:#10b981; font-size:20px; width:20px; height:20px;"></span>
                                    <strong style="font-size:13.5px; color:#0f172a;">Excel (.xls)</strong>
                                </div>
                                <input type="radio" name="export_format" value="excel" style="margin:0;">
                            </div>
                            <span style="font-size:11px; color:#64748b;"><?php esc_html_e( 'Formatted XML workbook with meta header styling', 'usp-form-builder' ); ?></span>
                        </label>

                        <!-- Format: PDF / Print -->
                        <label class="usp-format-card" style="border:1.5px solid #cbd5e1; background:#ffffff; border-radius:8px; padding:12px 14px; cursor:pointer; display:flex; flex-direction:column; gap:4px; transition:all 0.15s ease;">
                            <div style="display:flex; align-items:center; justify-content:space-between;">
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span class="dashicons dashicons-pdf" style="color:#dc2626; font-size:20px; width:20px; height:20px;"></span>
                                    <strong style="font-size:13.5px; color:#0f172a;">PDF / Print</strong>
                                </div>
                                <input type="radio" name="export_format" value="pdf" style="margin:0;">
                            </div>
                            <span style="font-size:11px; color:#64748b;"><?php esc_html_e( 'Executive landscape PDF report ready to print/save', 'usp-form-builder' ); ?></span>
                        </label>
                    </div>
                </div>

                <!-- 3. Form & Date Range Meta Options -->
                <div style="margin-bottom: 20px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:16px;">
                    <label style="display:block; font-size:12px; font-weight:700; color:var(--usp-adm-slate-700); text-transform:uppercase; margin-bottom:12px; letter-spacing:0.5px;">
                        <?php esc_html_e( '2. Form & Date Range Scope', 'usp-form-builder' ); ?>
                    </label>
                    
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
                        <!-- Select Form -->
                        <div style="grid-column: span 2;">
                            <label for="usp-export-modal-form-id" style="display:block; font-size:11.5px; font-weight:600; color:#475569; margin-bottom:4px;">
                                <?php esc_html_e( 'Target Form:', 'usp-form-builder' ); ?>
                            </label>
                            <select name="form_id" id="usp-export-modal-form-id" style="width:100%; border-radius:6px; border:1.5px solid #cbd5e1; height:36px; font-size:13px; background:#fff;">
                                <option value="0" <?php selected( $selected_form_id, 0 ); ?>><?php esc_html_e( '— All Forms Combined —', 'usp-form-builder' ); ?></option>
                                <?php foreach ( $forms as $f ) : ?>
                                    <option value="<?php echo esc_attr( $f['id'] ); ?>" <?php selected( $selected_form_id, $f['id'] ); ?>>
                                        <?php echo esc_html( $f['title'] ); ?> (<?php echo esc_html( $f['entry_count'] ); ?> submissions)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Date From -->
                        <div>
                            <label for="usp-export-date-from" style="display:block; font-size:11.5px; font-weight:600; color:#475569; margin-bottom:4px;">
                                <?php esc_html_e( 'Date From:', 'usp-form-builder' ); ?>
                            </label>
                            <input type="date" name="date_from" id="usp-export-date-from" value="<?php echo esc_attr( $date_from ); ?>" style="width:100%; border-radius:6px; border:1.5px solid #cbd5e1; height:36px; font-size:13px; background:#fff;">
                        </div>

                        <!-- Date To -->
                        <div>
                            <label for="usp-export-date-to" style="display:block; font-size:11.5px; font-weight:600; color:#475569; margin-bottom:4px;">
                                <?php esc_html_e( 'Date To:', 'usp-form-builder' ); ?>
                            </label>
                            <input type="date" name="date_to" id="usp-export-date-to" value="<?php echo esc_attr( $date_to ); ?>" style="width:100%; border-radius:6px; border:1.5px solid #cbd5e1; height:36px; font-size:13px; background:#fff;">
                        </div>
                    </div>

                    <div style="font-size:11.5px; color:#64748b; display:flex; align-items:center; gap:6px;">
                        <span class="dashicons dashicons-yes-alt" style="color:#10b981; font-size:14px; width:14px; height:14px;"></span>
                        <span><?php esc_html_e( 'The exported file will include Form Title and Date From/To meta directly at the top of the report.', 'usp-form-builder' ); ?></span>
                    </div>
                </div>

                <!-- 4. Select Submission Headers (Columns to Export) -->
                <div>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                        <label style="display:block; font-size:12px; font-weight:700; color:var(--usp-adm-slate-700); text-transform:uppercase; letter-spacing:0.5px; margin:0;">
                            <?php esc_html_e( '3. Select Submission Headers (Columns)', 'usp-form-builder' ); ?> <span style="color:#ef4444;">*</span>
                        </label>
                        <div style="display:flex; gap:8px;">
                            <button type="button" id="usp-export-select-all-headers" class="button button-small" style="font-size:11px; height:24px; line-height:22px; border-radius:4px;">
                                <?php esc_html_e( 'Select All', 'usp-form-builder' ); ?>
                            </button>
                            <button type="button" id="usp-export-deselect-all-headers" class="button button-small" style="font-size:11px; height:24px; line-height:22px; border-radius:4px;">
                                <?php esc_html_e( 'Deselect All', 'usp-form-builder' ); ?>
                            </button>
                        </div>
                    </div>

                    <!-- Headers Checkboxes Container -->
                    <div id="usp-export-headers-container" style="border:1.5px solid #e2e8f0; border-radius:8px; padding:14px; background:#ffffff; max-height:210px; overflow-y:auto;">
                        <div id="usp-export-headers-loading" style="text-align:center; padding:18px; color:#64748b; font-size:12px;">
                            <span class="spinner is-active" style="float:none; margin:0 6px 0 0; vertical-align:middle;"></span>
                            <?php esc_html_e( 'Loading available field headers for this form...', 'usp-form-builder' ); ?>
                        </div>
                        <div id="usp-export-headers-list" style="display:grid; grid-template-columns:repeat(2, 1fr); gap:8px 16px;"></div>
                    </div>
                    <span id="usp-export-headers-count" style="display:block; margin-top:6px; font-size:11.5px; color:#64748b;"></span>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="usp-modal-footer" style="padding:16px 24px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; border-bottom-left-radius:8px; border-bottom-right-radius:8px;">
                <button type="button" class="button button-secondary" id="usp-export-modal-cancel-btn" style="height:36px; padding:0 16px; font-weight:600; border-radius:6px;">
                    <?php esc_html_e( 'Cancel', 'usp-form-builder' ); ?>
                </button>
                <button type="submit" id="usp-export-submit-btn" class="button button-primary" style="height:36px; padding:0 22px; font-weight:700; border-radius:6px; background:#2563eb; border-color:#2563eb; display:inline-flex; align-items:center; gap:6px;">
                    <span class="dashicons dashicons-download" style="font-size:16px; width:16px; height:16px;"></span>
                    <span id="usp-export-submit-label"><?php esc_html_e( 'Download Export', 'usp-form-builder' ); ?></span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Enhanced Entry Details Modal (Inspection, Next/Prev, Toolbar, Internal Notes) -->
<div id="usp-entry-modal" class="usp-modal-backdrop" style="display:none;">
    <div class="usp-modal-dialog" style="max-width: 760px;">
        <!-- Modal Top Header -->
        <div class="usp-modal-header">
            <div style="display:flex; align-items:center; gap:10px;">
                <span class="dashicons dashicons-email-alt" style="font-size:22px; width:22px; height:22px; color:var(--usp-adm-primary);"></span>
                <h3 id="usp-modal-title" style="margin:0; font-size:16px; font-weight:700;">
                    <?php esc_html_e( 'Submission Details', 'usp-form-builder' ); ?>
                </h3>
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
                <span id="usp-modal-nav-counter" style="font-size:12px; font-weight:700; color:var(--usp-adm-slate-500); background:var(--usp-adm-slate-200); padding:3px 9px; border-radius:12px;">1 of 1</span>
                <button type="button" class="usp-modal-nav-btn" id="usp-modal-prev-btn" title="<?php esc_attr_e( 'Previous Submission (Left arrow key)', 'usp-form-builder' ); ?>">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                </button>
                <button type="button" class="usp-modal-nav-btn" id="usp-modal-next-btn" title="<?php esc_attr_e( 'Next Submission (Right arrow key)', 'usp-form-builder' ); ?>">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </button>
                <button type="button" class="usp-modal-close" id="usp-modal-close-btn" title="<?php esc_attr_e( 'Close dialog (Esc)', 'usp-form-builder' ); ?>">&times;</button>
            </div>
        </div>

        <!-- In-Modal Quick Action Toolbar -->
        <div class="usp-modal-toolbar-top">
            <div class="usp-modal-quick-actions">
                <!-- Star Toggle Button -->
                <button type="button" class="usp-modal-action-btn" id="usp-modal-star-btn" title="<?php esc_attr_e( 'Toggle star flag for this entry', 'usp-form-builder' ); ?>">
                    <span class="dashicons dashicons-star-empty" id="usp-modal-star-icon"></span>
                    <span id="usp-modal-star-label"><?php esc_html_e( 'Star', 'usp-form-builder' ); ?></span>
                </button>

                <!-- Status Toggle Button -->
                <button type="button" class="usp-modal-action-btn" id="usp-modal-status-btn" title="<?php esc_attr_e( 'Toggle Read / Unread status', 'usp-form-builder' ); ?>">
                    <span class="dashicons dashicons-marker" id="usp-modal-status-icon"></span>
                    <span id="usp-modal-status-label"><?php esc_html_e( 'Mark Unread', 'usp-form-builder' ); ?></span>
                </button>

                <!-- 1-Click Copy Summary -->
                <button type="button" class="usp-modal-action-btn" id="usp-modal-copy-btn" title="<?php esc_attr_e( 'Copy clean plain-text summary to clipboard', 'usp-form-builder' ); ?>">
                    <span class="dashicons dashicons-clipboard"></span>
                    <span><?php esc_html_e( 'Copy Summary', 'usp-form-builder' ); ?></span>
                </button>
            </div>

            <div>
                <!-- Print Record -->
                <button type="button" class="usp-modal-action-btn" onclick="window.print();" title="<?php esc_attr_e( 'Print clean record sheet', 'usp-form-builder' ); ?>">
                    <span class="dashicons dashicons-printer"></span>
                    <span><?php esc_html_e( 'Print', 'usp-form-builder' ); ?></span>
                </button>
            </div>
        </div>

        <!-- Modal Body Content -->
        <div class="usp-modal-body" id="usp-modal-body">
            <div class="spinner is-active" style="float:none; margin: 30px auto; display:block;"></div>
        </div>

        <!-- Modal Footer -->
        <div class="usp-modal-footer" style="display:flex; justify-content:space-between; align-items:center;">
            <span style="font-size:11.5px; color:var(--usp-adm-slate-400);">
                <span class="dashicons dashicons-info" style="font-size:14px; width:14px; height:14px; vertical-align:middle;"></span>
                <?php esc_html_e( 'Keyboard: ← / → to navigate, Esc to close', 'usp-form-builder' ); ?>
            </span>
            <button type="button" class="button button-primary" id="usp-modal-done-btn"><?php esc_html_e( 'Done', 'usp-form-builder' ); ?></button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var adminNonce          = '<?php echo esc_js( wp_create_nonce( 'usp_admin_nonce' ) ); ?>';
    var paginationBaseUrl   = '<?php echo esc_js( $pagination_base_url ); ?>';
    var visibleEntryIds     = [];
    var currentEntryIndex   = -1;
    var currentEntryData    = null;

    // Build visible entries array for next/previous traversal
    function refreshVisibleEntries() {
        visibleEntryIds = [];
        $('.usp-view-entry-btn').each(function() {
            visibleEntryIds.push(parseInt($(this).data('id'), 10));
        });
    }
    refreshVisibleEntries();

    // ==========================================
    // 1. Bulk Selection Management
    // ==========================================
    function updateSelectedCount() {
        var count = $('.usp-entry-cb:checked').length;
        if (count > 0) {
            $('#usp-bulk-selected-count').text(count + ' selected');
        } else {
            $('#usp-bulk-selected-count').text('');
        }
    }

    $('#usp-cb-select-all').on('change', function() {
        var isChecked = $(this).is(':checked');
        $('.usp-entry-cb').prop('checked', isChecked);
        updateSelectedCount();
    });

    $(document).on('change', '.usp-entry-cb', function() {
        updateSelectedCount();
        var allChecked = ($('.usp-entry-cb:checked').length === $('.usp-entry-cb').length && $('.usp-entry-cb').length > 0);
        $('#usp-cb-select-all').prop('checked', allChecked);
    });

    // Bulk Apply Button
    $('#usp-bulk-apply-btn').on('click', function(e) {
        e.preventDefault();
        var action = $('#usp-bulk-action-selector').val();
        if (action === '-1') {
            if (window.USPModal && USPModal.alert) {
                USPModal.alert({
                    title: 'Action Required',
                    message: 'Please select an action from the bulk actions dropdown.',
                    type: 'warning',
                    buttonText: 'Got it'
                });
            } else {
                alert('Please select an action from the bulk actions dropdown.');
            }
            return;
        }

        var selectedIds = [];
        $('.usp-entry-cb:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            if (window.USPModal && USPModal.alert) {
                USPModal.alert({
                    title: 'No Entries Selected',
                    message: 'Please select at least one submission entry using the row checkboxes.',
                    type: 'warning',
                    buttonText: 'Understood'
                });
            } else {
                alert('Please select at least one submission entry using the row checkboxes.');
            }
            return;
        }

        if (action === 'export_selected') {
            openExportModal({
                selectedIds: selectedIds,
                count: selectedIds.length
            });
            return;
        }

        function executeBulkAction() {
            var $btn = $('#usp-bulk-apply-btn');
            $btn.prop('disabled', true).text('Applying...');

            $.post(ajaxurl, {
                action: 'usp_bulk_entries',
                nonce: adminNonce,
                bulk_action: action,
                entry_ids: selectedIds
            }, function(res) {
                $btn.prop('disabled', false).text('Apply');
                if (res.success) {
                    window.location.reload();
                } else {
                    if (window.USPModal && USPModal.alert) {
                        USPModal.alert({
                            title: 'Bulk Action Failed',
                            message: res.data.message || 'Error performing bulk action.',
                            type: 'error'
                        });
                    } else {
                        alert(res.data.message || 'Error performing bulk action.');
                    }
                }
            }).fail(function() {
                $btn.prop('disabled', false).text('Apply');
                if (window.USPModal && USPModal.alert) {
                    USPModal.alert({
                        title: 'Network Error',
                        message: 'A network error occurred while processing your request.',
                        type: 'error'
                    });
                }
            });
        }

        if (action === 'delete') {
            if (window.USPModal && USPModal.confirm) {
                USPModal.confirm({
                    title: 'Delete Submissions?',
                    message: 'Are you sure you want to permanently delete ' + selectedIds.length + ' submission(s)? This action cannot be undone.',
                    type: 'danger',
                    confirmText: 'Yes, Delete ' + selectedIds.length + ' Submissions',
                    cancelText: 'Cancel'
                }).then(function(confirmed) {
                    if (confirmed) {
                        executeBulkAction();
                    }
                });
            } else if (confirm('Are you sure you want to permanently delete ' + selectedIds.length + ' submissions?')) {
                executeBulkAction();
            }
        } else {
            executeBulkAction();
        }
    });

    // ==========================================
    // 2. Star & Status Row Toggles
    // ==========================================
    function updateRowStarState(entryId, isStarred) {
        var $btn = $('#usp-entry-row-' + entryId).find('.usp-star-toggle-btn');
        $btn.data('starred', isStarred ? '1' : '0');
        var $icon = $btn.find('.dashicons');
        if (isStarred) {
            $icon.removeClass('dashicons-star-empty').addClass('dashicons-star-filled').css('color', '#f59e0b');
            $btn.attr('title', 'Starred - Click to unstar');
        } else {
            $icon.removeClass('dashicons-star-filled').addClass('dashicons-star-empty').css('color', '#cbd5e1');
            $btn.attr('title', 'Click to star');
        }
    }

    function updateRowStatusState(entryId, status) {
        var $btn = $('#usp-entry-row-' + entryId).find('.usp-status-toggle-btn');
        var $row = $('#usp-entry-row-' + entryId);
        $btn.data('status', status);

        if (status === 'unread') {
            $btn.html('<span style="display:inline-flex; align-items:center; gap:4px; background:#eff6ff; color:#1d4ed8; padding:3px 9px; border-radius:12px; font-size:11px; font-weight:700; border:1px solid #bfdbfe;"><span style="width:6px; height:6px; border-radius:50%; background:#2563eb;"></span> Unread</span>');
            $row.addClass('usp-row-unread').css('background', '#f8fafc');
        } else if (status === 'starred') {
            $btn.html('<span style="display:inline-flex; align-items:center; gap:3px; background:#fef3c7; color:#b45309; padding:3px 9px; border-radius:12px; font-size:11px; font-weight:700; border:1px solid #fde68a;"><span class="dashicons dashicons-star-filled" style="font-size:11px; width:11px; height:11px;"></span> Starred</span>');
            $row.removeClass('usp-row-unread').css('background', '#ffffff');
        } else {
            $btn.html('<span style="display:inline-flex; align-items:center; gap:4px; background:#f1f5f9; color:#475569; padding:3px 9px; border-radius:12px; font-size:11px; font-weight:600; border:1px solid #e2e8f0;"><span class="dashicons dashicons-yes" style="font-size:11px; width:11px; height:11px; color:#10b981;"></span> Read</span>');
            $row.removeClass('usp-row-unread').css('background', '#ffffff');
        }
    }

    // Row Star Toggle
    $(document).on('click', '.usp-star-toggle-btn', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var entryId = $btn.data('id');
        var isStarred = $btn.data('starred') == '1';
        var nextStatus = isStarred ? 'read' : 'starred';

        $.post(ajaxurl, {
            action: 'usp_toggle_entry_status',
            entry_id: entryId,
            status: nextStatus,
            nonce: adminNonce
        }, function(res) {
            if (res.success) {
                updateRowStarState(entryId, !isStarred);
                if (window.USPModal && USPModal.toast) {
                    USPModal.toast(!isStarred ? 'Submission starred' : 'Submission unstarred', !isStarred ? 'success' : 'info');
                }
            }
        });
    });

    // Row Status Toggle
    $(document).on('click', '.usp-status-toggle-btn', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var entryId = $btn.data('id');
        var currentStatus = $btn.data('status');
        var nextStatus = (currentStatus === 'unread') ? 'read' : 'unread';

        $.post(ajaxurl, {
            action: 'usp_toggle_entry_status',
            entry_id: entryId,
            status: nextStatus,
            nonce: adminNonce
        }, function(res) {
            if (res.success) {
                updateRowStatusState(entryId, nextStatus);
                if (window.USPModal && USPModal.toast) {
                    USPModal.toast(nextStatus === 'unread' ? 'Marked as unread' : 'Marked as read', 'info');
                }
            }
        });
    });

    // Single Delete
    $('.usp-delete-entry-btn').on('click', function(e) {
        e.preventDefault();
        var entryId = $(this).data('id');

        if (window.USPModal && USPModal.confirm) {
            USPModal.confirm({
                title: 'Delete Submission?',
                message: 'Are you sure you want to permanently delete this submission record? This action cannot be undone.',
                type: 'danger',
                confirmText: 'Yes, Delete Record',
                cancelText: 'Cancel'
            }).then(function(confirmed) {
                if (!confirmed) return;
                doDelete(entryId);
            });
        } else if (confirm('Are you sure you want to permanently delete this submission record?')) {
            doDelete(entryId);
        }

        function doDelete(id) {
            $.post(ajaxurl, {
                action: 'usp_delete_entry',
                entry_id: id,
                nonce: adminNonce
            }, function(res) {
                if (res.success) {
                    $('#usp-entry-row-' + id).fadeOut(200, function() { 
                        $(this).remove(); 
                        refreshVisibleEntries();
                    });
                    if (window.USPModal && USPModal.toast) {
                        USPModal.toast('Submission record deleted.', 'success');
                    }
                } else {
                    if (window.USPModal && USPModal.alert) {
                        USPModal.alert({
                            title: 'Delete Failed',
                            message: res.data.message || 'Error deleting entry',
                            type: 'error'
                        });
                    }
                }
            });
        }
    });

    // ==========================================
    // 3. Quick Date Range Presets Auto-Apply
    // ==========================================
    $('.usp-date-preset-btn').on('click', function(e) {
        e.preventDefault();
        var from = $(this).data('from');
        var to   = $(this).data('to');

        $('#filter-date-from').val(from);
        $('#filter-date-to').val(to);

        // Submit filter form immediately for fast UX
        $('#usp-entries-filter-form').submit();
    });

    // ==========================================
    // 4. Custom Pagination & Per-Page Controls
    // ==========================================
    $('.usp-per-page-select').on('change', function() {
        var perPage = $(this).val();
        var url = new URL(window.location.href);
        url.searchParams.set('per_page', perPage);
        url.searchParams.set('paged', '1');
        window.location.href = url.toString();
    });

    $('#usp-page-jump-input').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var targetPage = parseInt($(this).val(), 10);
            var maxPage    = parseInt($(this).attr('max'), 10) || 1;
            if (isNaN(targetPage) || targetPage < 1) targetPage = 1;
            if (targetPage > maxPage) targetPage = maxPage;

            var url = new URL(window.location.href);
            url.searchParams.set('paged', targetPage);
            window.location.href = url.toString();
        }
    });

    // ==========================================
    // 5. Inspection Modal: Next, Prev, Toolbar, Notes
    // ==========================================
    function updateModalToolbar(entry) {
        var isStarred = (entry.status === 'starred');
        var isUnread  = (entry.status === 'unread');

        // Star Button
        var $starBtn = $('#usp-modal-star-btn');
        var $starIcon = $('#usp-modal-star-icon');
        if (isStarred) {
            $starBtn.addClass('is-active-star');
            $starIcon.removeClass('dashicons-star-empty').addClass('dashicons-star-filled').css('color', '#f59e0b');
            $('#usp-modal-star-label').text('Starred');
        } else {
            $starBtn.removeClass('is-active-star');
            $starIcon.removeClass('dashicons-star-filled').addClass('dashicons-star-empty').css('color', 'inherit');
            $('#usp-modal-star-label').text('Star');
        }

        // Status Button
        var $statusIcon = $('#usp-modal-status-icon');
        if (isUnread) {
            $statusIcon.removeClass('dashicons-marker').addClass('dashicons-yes').css('color', '#10b981');
            $('#usp-modal-status-label').text('Mark as Read');
        } else {
            $statusIcon.removeClass('dashicons-yes').addClass('dashicons-marker').css('color', '#2563eb');
            $('#usp-modal-status-label').text('Mark as Unread');
        }
    }

    function loadEntryInModal(entryId, index) {
        currentEntryIndex = index;
        var total = visibleEntryIds.length;

        $('#usp-modal-nav-counter').text((index + 1) + ' of ' + total);
        $('#usp-modal-prev-btn').prop('disabled', index <= 0);
        $('#usp-modal-next-btn').prop('disabled', index >= total - 1);

        $('#usp-modal-title').text('Submission #' + entryId);
        var $body = $('#usp-modal-body');
        $body.html('<div class="spinner is-active" style="float:none; margin: 30px auto; display:block;"></div>');

        $.post(ajaxurl, {
            action: 'usp_get_entry_details',
            entry_id: entryId,
            nonce: adminNonce
        }, function(res) {
            if (res.success && res.data.entry) {
                var entry = res.data.entry;
                currentEntryData = entry;

                // Sync table row status
                updateRowStatusState(entryId, entry.status);
                updateRowStarState(entryId, entry.status === 'starred');

                // Update in-modal toolbar buttons
                updateModalToolbar(entry);

                // Build Body HTML
                var html = '<div class="usp-entry-details-container">';

                // Metadata Card
                html += '<div style="background:var(--usp-adm-slate-50); padding:14px 18px; border-radius:8px; border:1px solid var(--usp-adm-slate-200); font-size:13px; color:var(--usp-adm-slate-700); margin-bottom:16px; display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:10px;">';
                html += '<div><span style="font-size:11px; font-weight:700; color:var(--usp-adm-slate-400); text-transform:uppercase; display:block;">Form:</span> <strong style="color:var(--usp-adm-slate-900);">' + (entry.form_title || 'Form #' + entry.form_id) + '</strong></div>';
                html += '<div><span style="font-size:11px; font-weight:700; color:var(--usp-adm-slate-400); text-transform:uppercase; display:block;">Date & Time:</span> ' + entry.created_at + '</div>';
                html += '<div><span style="font-size:11px; font-weight:700; color:var(--usp-adm-slate-400); text-transform:uppercase; display:block;">Submitter IP:</span> <code>' + (entry.user_ip || 'N/A') + '</code></div>';
                html += '</div>';

                // 4. Internal Admin Notes Card
                html += '<div class="usp-admin-notes-box">';
                html += '<div class="usp-admin-notes-header">';
                html += '<div class="usp-admin-notes-title">';
                html += '<span class="dashicons dashicons-admin-comments"></span>';
                html += '<span>Internal Admin Notes</span>';
                html += '<span class="usp-admin-notes-badge">Staff Only</span>';
                html += '</div>';
                html += '<div class="usp-admin-notes-status" id="usp-admin-notes-status">';
                html += '<span class="dashicons dashicons-yes"></span> Saved';
                html += '</div>';
                html += '</div>';
                html += '<textarea id="usp-admin-notes-input" class="usp-admin-notes-textarea" rows="3" placeholder="Add private notes, customer follow-up notes, or staff remarks...">' + (entry.notes ? $('<div>').text(entry.notes).html() : '') + '</textarea>';
                html += '<div class="usp-admin-notes-footer">';
                html += '<span style="font-size:11px; color:var(--usp-adm-slate-400);">Private notes visible only in admin dashboard.</span>';
                html += '<button type="button" class="button button-secondary" id="usp-admin-save-note-btn" style="font-size:12px; height:28px; line-height:26px;">';
                html += '<span class="dashicons dashicons-saved" style="font-size:13px; width:13px; height:13px; vertical-align:middle;"></span> Save Note';
                html += '</button>';
                html += '</div>';
                html += '</div>';

                // Submitted Fields Table
                html += '<div style="margin-bottom:6px;"><strong style="font-size:13.5px; color:var(--usp-adm-slate-800);">' + '<?php echo esc_js( __( 'Submitted Form Fields', 'usp-form-builder' ) ); ?>' + '</strong></div>';
                html += '<table class="widefat striped" style="border:1px solid var(--usp-adm-slate-200); border-radius:6px; overflow:hidden;"><tbody>';

                if (Array.isArray(entry.fields_data)) {
                    entry.fields_data.forEach(function(item) {
                        var val = item.value;
                        var type = item.type || '';
                        var valFormatted = '';

                        if (typeof val === 'object' && val !== null && val.url) {
                            var isImg = /\.(jpe?g|png|gif|webp)$/i.test(val.url);
                            valFormatted = '<div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">';
                            if (isImg) {
                                valFormatted += '<a href="' + encodeURI(val.url) + '" target="_blank"><img src="' + encodeURI(val.url) + '" style="width:52px; height:52px; object-fit:cover; border-radius:6px; border:1px solid #cbd5e1; box-shadow:0 1px 3px rgba(0,0,0,0.1);" alt="Preview"></a>';
                            }
                            valFormatted += '<a href="' + encodeURI(val.url) + '" target="_blank" class="button button-small" style="display:inline-flex; align-items:center; gap:5px; color:#15803d; border-color:#86efac; background:#f0fdf4; font-weight:600;"><span class="dashicons dashicons-download" style="font-size:14px; width:14px; height:14px; vertical-align:middle;"></span> ' + (val.file || 'Download File') + '</a>';
                            valFormatted += '</div>';
                        } else if (type === 'admission' || (typeof val === 'object' && val !== null && !Array.isArray(val) && val.student_name)) {
                            valFormatted = '<div style="background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:8px; padding:14px 16px; margin-top:2px;">';
                            valFormatted += '<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; border-bottom:1px solid #e2e8f0; padding-bottom:8px;">';
                            valFormatted += '<strong style="color:#0f172a; font-size:13.5px; display:flex; align-items:center; gap:6px;"><span class="dashicons dashicons-welcome-learn-more" style="color:#2563eb;"></span> ' + (val.student_name || 'Applicant Profile') + '</strong>';
                            if (val.gpa) {
                                valFormatted += '<span style="background:#dbeafe; color:#1e40af; font-size:11px; font-weight:700; padding:3px 9px; border-radius:12px;">GPA: ' + val.gpa + '</span>';
                            }
                            valFormatted += '</div>';
                            valFormatted += '<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:10px; font-size:12px; color:#334155;">';
                            if (val.dob) valFormatted += '<div><strong>Date of Birth:</strong> ' + val.dob + '</div>';
                            if (val.gender) valFormatted += '<div><strong>Gender:</strong> ' + val.gender + '</div>';
                            if (val.email) valFormatted += '<div><strong>Email:</strong> <a href="mailto:' + val.email + '">' + val.email + '</a></div>';
                            if (val.phone) valFormatted += '<div><strong>Phone:</strong> <a href="tel:' + val.phone + '">' + val.phone + '</a></div>';
                            if (val.program) valFormatted += '<div style="grid-column: 1 / -1;"><strong>Program Applied:</strong> <span style="font-weight:700; color:#2563eb;">' + val.program + '</span></div>';
                            if (val.prev_school) valFormatted += '<div style="grid-column: 1 / -1;"><strong>Previous School:</strong> ' + val.prev_school + '</div>';
                            if (val.address) valFormatted += '<div style="grid-column: 1 / -1;"><strong>Address:</strong> ' + val.address + '</div>';
                            valFormatted += '</div></div>';
                        } else if (type === 'payment' || (typeof val === 'string' && val.indexOf('[Status:') !== -1)) {
                            valFormatted = '<div style="background:#f0fdf4; border:1.5px solid #86efac; border-radius:8px; padding:12px 14px; margin-top:2px;">';
                            valFormatted += '<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">';
                            valFormatted += '<span style="font-weight:700; color:#166534; display:flex; align-items:center; gap:6px;"><span class="dashicons dashicons-cart" style="font-size:16px;"></span> ' + val.replace(/\[Status:.*?\]/, '').trim() + '</span>';
                            valFormatted += '<span style="background:#15803d; color:#ffffff; font-size:10px; font-weight:800; padding:2px 7px; border-radius:10px; text-transform:uppercase; letter-spacing:0.5px;">Authorized</span>';
                            valFormatted += '</div>';
                            valFormatted += '<div style="font-size:11px; color:#15803d; display:flex; align-items:center; gap:4px;"><span class="dashicons dashicons-lock" style="font-size:13px; width:13px; height:13px;"></span> 256-Bit SSL Encrypted Verification Sandbox</div>';
                            valFormatted += '</div>';
                        } else if (type === 'intl_tel' || (typeof val === 'string' && /^\+\d{1,4}\s/.test(val))) {
                            valFormatted = '<span style="display:inline-flex; align-items:center; gap:6px; font-weight:600; color:#0f172a;"><span class="dashicons dashicons-phone" style="color:#2563eb; font-size:15px; width:15px; height:15px;"></span> <a href="tel:' + val + '" style="text-decoration:none; color:inherit;">' + val + '</a></span>';
                        } else if (typeof val === 'object' && val !== null && !Array.isArray(val)) {
                            valFormatted = '<dl style="margin:0; font-size:12px;">';
                            for (var k in val) {
                                if (val.hasOwnProperty(k)) {
                                    valFormatted += '<dt style="font-weight:700; color:#475569; text-transform:capitalize;">' + k.replace(/_/g, ' ') + ':</dt><dd style="margin:0 0 6px 10px; color:#0f172a;">' + val[k] + '</dd>';
                                }
                            }
                            valFormatted += '</dl>';
                        } else if (Array.isArray(val)) {
                            valFormatted = val.map(function(item) {
                                return '<span style="display:inline-block; background:#f1f5f9; color:#334155; padding:2px 8px; border-radius:12px; font-size:12px; font-weight:600; margin:2px 4px 2px 0; border:1px solid #e2e8f0;">' + item + '</span>';
                            }).join(' ');
                        } else if (typeof val === 'string') {
                            valFormatted = val.replace(/\n/g, '<br>');
                        } else {
                            valFormatted = String(val || '');
                        }

                        html += '<tr>';
                        html += '<td style="width: 32%; font-weight:700; color:var(--usp-adm-slate-800);">' + (item.label || item.name) + '</td>';
                        html += '<td style="color:var(--usp-adm-slate-700);">' + (valFormatted || '<em style="color:#94a3b8;">(Empty)</em>') + '</td>';
                        html += '</tr>';
                    });
                }
                html += '</tbody></table></div>';
                $body.html(html);
            } else {
                $body.html('<div class="notice notice-error"><p>' + (res.data.message || 'Failed to load details') + '</p></div>');
            }
        });
    }

    // Open Modal on "View" button click
    $(document).on('click', '.usp-view-entry-btn', function(e) {
        e.preventDefault();
        refreshVisibleEntries();
        var entryId = parseInt($(this).data('id'), 10);
        var idx = visibleEntryIds.indexOf(entryId);
        if (idx === -1) idx = 0;

        $('#usp-entry-modal').fadeIn(150);
        loadEntryInModal(entryId, idx);
    });

    // Next / Previous Navigation buttons
    $('#usp-modal-prev-btn').on('click', function(e) {
        e.preventDefault();
        if (currentEntryIndex > 0) {
            var prevId = visibleEntryIds[currentEntryIndex - 1];
            loadEntryInModal(prevId, currentEntryIndex - 1);
        }
    });

    $('#usp-modal-next-btn').on('click', function(e) {
        e.preventDefault();
        if (currentEntryIndex < visibleEntryIds.length - 1) {
            var nextId = visibleEntryIds[currentEntryIndex + 1];
            loadEntryInModal(nextId, currentEntryIndex + 1);
        }
    });

    // In-Modal Star Toggle Button
    $('#usp-modal-star-btn').on('click', function(e) {
        e.preventDefault();
        if (!currentEntryData) return;

        var entryId = currentEntryData.id;
        var isStarred = (currentEntryData.status === 'starred');
        var nextStatus = isStarred ? 'read' : 'starred';

        $.post(ajaxurl, {
            action: 'usp_toggle_entry_status',
            entry_id: entryId,
            status: nextStatus,
            nonce: adminNonce
        }, function(res) {
            if (res.success) {
                currentEntryData.status = nextStatus;
                updateModalToolbar(currentEntryData);
                updateRowStarState(entryId, !isStarred);
                updateRowStatusState(entryId, nextStatus);

                if (window.USPModal && USPModal.toast) {
                    USPModal.toast(!isStarred ? 'Submission starred' : 'Submission unstarred', !isStarred ? 'success' : 'info');
                }
            }
        });
    });

    // In-Modal Status Toggle Button
    $('#usp-modal-status-btn').on('click', function(e) {
        e.preventDefault();
        if (!currentEntryData) return;

        var entryId = currentEntryData.id;
        var currentStatus = currentEntryData.status;
        var nextStatus = (currentStatus === 'unread') ? 'read' : 'unread';

        $.post(ajaxurl, {
            action: 'usp_toggle_entry_status',
            entry_id: entryId,
            status: nextStatus,
            nonce: adminNonce
        }, function(res) {
            if (res.success) {
                currentEntryData.status = nextStatus;
                updateModalToolbar(currentEntryData);
                updateRowStatusState(entryId, nextStatus);

                if (window.USPModal && USPModal.toast) {
                    USPModal.toast(nextStatus === 'unread' ? 'Marked as unread' : 'Marked as read', 'info');
                }
            }
        });
    });

    // In-Modal 1-Click Copy Summary
    $('#usp-modal-copy-btn').on('click', function(e) {
        e.preventDefault();
        if (!currentEntryData) return;

        var entry = currentEntryData;
        var summaryLines = [];
        summaryLines.push('=========================================');
        summaryLines.push('USP Form Submission #' + entry.id);
        summaryLines.push('Form: ' + (entry.form_title || 'Form #' + entry.form_id));
        summaryLines.push('Submitted On: ' + entry.created_at);
        summaryLines.push('Submitter IP: ' + (entry.user_ip || 'N/A'));
        summaryLines.push('Status: ' + entry.status.toUpperCase());
        summaryLines.push('=========================================');
        summaryLines.push('');
        summaryLines.push('--- FIELD SUBMISSIONS ---');

        if (Array.isArray(entry.fields_data)) {
            entry.fields_data.forEach(function(item) {
                var label = item.label || item.name || 'Field';
                var val   = item.value;
                var valStr = '';

                if (typeof val === 'object' && val !== null) {
                    if (val.url) {
                        valStr = (val.file || 'File') + ': ' + val.url;
                    } else if (val.student_name) {
                        valStr = val.student_name + (val.program ? ' (' + val.program + ')' : '');
                    } else {
                        valStr = JSON.stringify(val);
                    }
                } else {
                    valStr = String(val || '');
                }

                summaryLines.push(label + ': ' + valStr);
            });
        }

        if (entry.notes) {
            summaryLines.push('');
            summaryLines.push('--- INTERNAL ADMIN NOTES ---');
            summaryLines.push(entry.notes);
        }

        var fullSummary = summaryLines.join('\n');

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(fullSummary).then(function() {
                if (window.USPModal && USPModal.toast) {
                    USPModal.toast('Submission summary copied to clipboard!', 'success');
                } else {
                    alert('Submission summary copied to clipboard!');
                }
            });
        } else {
            // Fallback for non-https / older browsers
            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(fullSummary).select();
            document.execCommand('copy');
            $temp.remove();
            if (window.USPModal && USPModal.toast) {
                USPModal.toast('Submission summary copied to clipboard!', 'success');
            } else {
                alert('Submission summary copied to clipboard!');
            }
        }
    });

    // In-Modal Save Admin Notes
    $(document).on('click', '#usp-admin-save-note-btn', function(e) {
        e.preventDefault();
        if (!currentEntryData) return;

        var entryId  = currentEntryData.id;
        var noteText = $('#usp-admin-notes-input').val();
        var $btn     = $(this);
        var $status  = $('#usp-admin-notes-status');

        $btn.prop('disabled', true).text('Saving...');

        $.post(ajaxurl, {
            action: 'usp_save_entry_note',
            entry_id: entryId,
            notes: noteText,
            nonce: adminNonce
        }, function(res) {
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved" style="font-size:13px; width:13px; height:13px; vertical-align:middle;"></span> Save Note');
            if (res.success) {
                currentEntryData.notes = noteText;
                $status.addClass('visible');
                setTimeout(function() {
                    $status.removeClass('visible');
                }, 2500);

                // Update table row note badge
                var $badgeWrapper = $('#usp-row-note-wrapper-' + entryId);
                if (noteText.trim()) {
                    $badgeWrapper.html('<span class="usp-row-note-indicator" title="' + $('<div>').text(noteText).html() + '"><span class="dashicons dashicons-admin-comments" style="font-size:11px; width:11px; height:11px;"></span> Note</span>');
                } else {
                    $badgeWrapper.empty();
                }

                if (window.USPModal && USPModal.toast) {
                    USPModal.toast('Admin note saved.', 'success');
                }
            } else {
                if (window.USPModal && USPModal.alert) {
                    USPModal.alert({
                        title: 'Error',
                        message: res.data.message || 'Failed to save note.',
                        type: 'error'
                    });
                }
            }
        }).fail(function() {
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved" style="font-size:13px; width:13px; height:13px; vertical-align:middle;"></span> Save Note');
            if (window.USPModal && USPModal.alert) {
                USPModal.alert({
                    title: 'Network Error',
                    message: 'Failed to save note due to network error.',
                    type: 'error'
                });
            }
        });
    });

    // Close Modal
    function closeModal() {
        $('#usp-entry-modal').fadeOut(150);
        currentEntryIndex = -1;
        currentEntryData  = null;
    }

    $('#usp-modal-close-btn, #usp-modal-done-btn, .usp-modal-backdrop').on('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    // Keyboard Arrow Navigation (Left/Right) & Escape Key
    $(document).on('keydown', function(e) {
        if (!$('#usp-entry-modal').is(':visible')) {
            return;
        }

        // Do not intercept arrows if typing in an input or textarea
        var tag = (e.target.tagName || '').toLowerCase();
        if (tag === 'textarea' || tag === 'input') {
            return;
        }

        if (e.key === 'Escape') {
            if ($('#usp-export-modal').is(':visible')) {
                closeExportModal();
            } else {
                closeModal();
            }
        } else if (e.key === 'ArrowLeft') {
            $('#usp-modal-prev-btn').click();
        } else if (e.key === 'ArrowRight') {
            $('#usp-modal-next-btn').click();
        }
    });

    // ==========================================
    // 5. Advanced Submissions Export Modal Logic
    // ==========================================
    var cachedFormHeaders = {};

    function openExportModal(options) {
        options = options || {};
        var selectedIds = options.selectedIds || [];
        var count       = options.count || 0;

        // Reset scope banner
        if (selectedIds.length > 0) {
            $('#usp-export-selected-ids').val(selectedIds.join(','));
            $('#usp-export-scope-title').text('Exporting ' + selectedIds.length + ' Selected Submission(s)');
            $('#usp-export-scope-desc').text('Only the submissions chosen via table checkboxes will be exported.');
            $('#usp-export-scope-badge').text(selectedIds.length + ' Selected');
        } else {
            $('#usp-export-selected-ids').val('');
            $('#usp-export-scope-title').text('Exporting Filtered Submissions');
            $('#usp-export-scope-desc').text('All matching current form and date criteria (<?php echo esc_js( $total_entries ); ?> total).');
            $('#usp-export-scope-badge').text('<?php echo esc_js( $total_entries ); ?> Records');
        }

        // Sync date values from active filter bar if not explicitly changed
        var activeDateFrom = $('#filter-date-from').val() || '';
        var activeDateTo   = $('#filter-date-to').val() || '';
        $('#usp-export-date-from').val(activeDateFrom);
        $('#usp-export-date-to').val(activeDateTo);

        // Sync form id
        var activeFormId = $('#filter-by-form').val() || '0';
        $('#usp-export-modal-form-id').val(activeFormId);

        // Load headers for the chosen form
        loadFormExportHeaders(activeFormId);

        // Open modal
        $('#usp-export-modal').fadeIn(150);
    }

    function closeExportModal() {
        $('#usp-export-modal').fadeOut(150);
    }

    // Top Header button
    $('#usp-open-export-modal-btn').on('click', function(e) {
        e.preventDefault();
        openExportModal();
    });

    // Close buttons
    $('#usp-export-modal-close-btn, #usp-export-modal-cancel-btn').on('click', function(e) {
        e.preventDefault();
        closeExportModal();
    });

    $('#usp-export-modal').on('click', function(e) {
        if (e.target === this) {
            closeExportModal();
        }
    });

    // Format card selection interaction
    $('.usp-format-card').on('click', function() {
        $('.usp-format-card').removeClass('is-selected').css({
            'border-color': '#cbd5e1',
            'background': '#ffffff'
        });
        $(this).addClass('is-selected').css({
            'border-color': '#2563eb',
            'background': '#f8fafc'
        });
        $(this).find('input[type="radio"]').prop('checked', true);

        var fmt = $(this).find('input[type="radio"]').val();
        if (fmt === 'pdf') {
            $('#usp-export-submit-label').text('Generate PDF Report');
        } else if (fmt === 'excel') {
            $('#usp-export-submit-label').text('Download Excel File');
        } else {
            $('#usp-export-submit-label').text('Download CSV File');
        }
    });

    // Form change in modal re-fetches headers dynamically
    $('#usp-export-modal-form-id').on('change', function() {
        var newFormId = $(this).val();
        loadFormExportHeaders(newFormId);
    });

    function updateHeadersCount() {
        var total   = $('.usp-export-header-cb').length;
        var checked = $('.usp-export-header-cb:checked').length;
        $('#usp-export-headers-count').text(checked + ' of ' + total + ' columns selected for export.');
    }

    function loadFormExportHeaders(formId) {
        var $list    = $('#usp-export-headers-list');
        var $loading = $('#usp-export-headers-loading');

        $list.empty();
        $loading.show();

        if (cachedFormHeaders[formId]) {
            renderHeaderCheckboxes(cachedFormHeaders[formId]);
            $loading.hide();
            return;
        }

        $.post(ajaxurl, {
            action: 'usp_get_form_headers',
            nonce: adminNonce,
            form_id: formId
        }, function(res) {
            $loading.hide();
            if (res.success && res.data) {
                cachedFormHeaders[formId] = res.data;
                renderHeaderCheckboxes(res.data);
            } else {
                $list.html('<div style="grid-column:span 2; color:#ef4444; font-size:12px;">Failed to load headers.</div>');
            }
        }).fail(function() {
            $loading.hide();
            $list.html('<div style="grid-column:span 2; color:#ef4444; font-size:12px;">Network error loading headers.</div>');
        });
    }

    function renderHeaderCheckboxes(data) {
        var $list = $('#usp-export-headers-list');
        $list.empty();

        var systemHeaders = data.system_headers || [];
        var fieldHeaders  = data.field_headers || [];

        // 1. System Headers Section
        if (systemHeaders.length > 0) {
            var $sysTitle = $('<div style="grid-column:span 2; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; border-bottom:1px solid #f1f5f9; padding-bottom:4px; margin-top:2px;">Standard Record Headers</div>');
            $list.append($sysTitle);

            systemHeaders.forEach(function(item) {
                var checkedAttr = item.default ? 'checked' : '';
                var html = '<label style="display:flex; align-items:center; gap:8px; font-size:12.5px; color:#334155; cursor:pointer; padding:3px 0;">' +
                    '<input type="checkbox" name="export_headers[]" value="' + $('<div>').text(item.key).html() + '" class="usp-export-header-cb" ' + checkedAttr + ' style="border-radius:4px;">' +
                    '<span>' + $('<div>').text(item.label).html() + '</span>' +
                '</label>';
                $list.append(html);
            });
        }

        // 2. Custom Form Fields Section
        if (fieldHeaders.length > 0) {
            var $fieldTitle = $('<div style="grid-column:span 2; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; border-bottom:1px solid #f1f5f9; padding-bottom:4px; margin-top:8px;">Form Submission Fields</div>');
            $list.append($fieldTitle);

            fieldHeaders.forEach(function(item) {
                var checkedAttr = item.default ? 'checked' : '';
                var html = '<label style="display:flex; align-items:center; gap:8px; font-size:12.5px; color:#1e293b; font-weight:500; cursor:pointer; padding:3px 0;">' +
                    '<input type="checkbox" name="export_headers[]" value="' + $('<div>').text(item.key).html() + '" class="usp-export-header-cb" ' + checkedAttr + ' style="border-radius:4px;">' +
                    '<span>' + $('<div>').text(item.label).html() + '</span>' +
                '</label>';
                $list.append(html);
            });
        } else {
            var $noFields = $('<div style="grid-column:span 2; font-size:12px; color:#94a3b8; font-style:italic; padding:6px 0;">No individual input fields detected for this form yet. Standard headers will be exported.</div>');
            $list.append($noFields);
        }

        updateHeadersCount();
    }

    $(document).on('change', '.usp-export-header-cb', function() {
        updateHeadersCount();
    });

    $('#usp-export-select-all-headers').on('click', function(e) {
        e.preventDefault();
        $('.usp-export-header-cb').prop('checked', true);
        updateHeadersCount();
    });

    $('#usp-export-deselect-all-headers').on('click', function(e) {
        e.preventDefault();
        $('.usp-export-header-cb').prop('checked', false);
        updateHeadersCount();
    });

    // Form submission validation
    $('#usp-export-modal-form').on('submit', function(e) {
        var checkedHeaders = $('.usp-export-header-cb:checked').length;
        if (checkedHeaders === 0) {
            e.preventDefault();
            if (window.USPModal && USPModal.alert) {
                USPModal.alert({
                    title: 'Header Selection Required',
                    message: 'Please check at least one submission header column to export.',
                    type: 'warning'
                });
            } else {
                alert('Please check at least one submission header column to export.');
            }
            return false;
        }

        // If format is PDF, open in new tab for smooth print preview
        var fmt = $('input[name="export_format"]:checked').val();
        if (fmt === 'pdf') {
            $(this).attr('target', '_blank');
        } else {
            $(this).removeAttr('target');
        }

        setTimeout(function() {
            closeExportModal();
        }, 600);
        return true;
    });
});
</script>
