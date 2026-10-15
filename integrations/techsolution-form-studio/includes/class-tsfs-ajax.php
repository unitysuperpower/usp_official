<?php
/**
 * Advanced AJAX endpoints handler for TechSolution Form Studio.
 * Handles forms saving, entry management, file uploads, conditional logic, bulk actions, and email automations.
 *
 * @package    TSFS_Plugin
 * @subpackage TSFS_Plugin/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TSFS_AJAX {

    public function __construct() {
        // Admin AJAX Actions
        add_action( 'wp_ajax_tsfs_save_form', array( $this, 'ajax_save_form' ) );
        add_action( 'wp_ajax_tsfs_delete_form', array( $this, 'ajax_delete_form' ) );
        add_action( 'wp_ajax_tsfs_duplicate_form', array( $this, 'ajax_duplicate_form' ) );
        add_action( 'wp_ajax_tsfs_toggle_form_status', array( $this, 'ajax_toggle_form_status' ) );
        add_action( 'wp_ajax_tsfs_delete_entry', array( $this, 'ajax_delete_entry' ) );
        add_action( 'wp_ajax_tsfs_get_entry_details', array( $this, 'ajax_get_entry_details' ) );
        add_action( 'wp_ajax_tsfs_toggle_entry_status', array( $this, 'ajax_toggle_entry_status' ) );
        add_action( 'wp_ajax_tsfs_save_entry_note', array( $this, 'ajax_save_entry_note' ) );
        add_action( 'wp_ajax_tsfs_bulk_entries', array( $this, 'ajax_bulk_entries' ) );
        add_action( 'wp_ajax_tsfs_get_form_headers', array( $this, 'ajax_get_form_headers' ) );
        add_action( 'admin_post_tsfs_export_entries', array( $this, 'export_entries_csv' ) );
        add_action( 'wp_ajax_tsfs_save_security_settings', array( $this, 'ajax_save_security_settings' ) );
        add_action( 'wp_ajax_tsfs_redeploy_shields', array( $this, 'ajax_redeploy_shields' ) );
        add_action( 'wp_ajax_tsfs_test_webhook', array( $this, 'ajax_test_webhook' ) );
        add_action( 'admin_post_tsfs_export_form_json', array( $this, 'export_form_json' ) );
        add_action( 'admin_post_tsfs_download_file', array( $this, 'download_file' ) );
        add_action( 'wp_ajax_tsfs_import_form_json', array( $this, 'ajax_import_form_json' ) );

        // Public Submission & Analytics AJAX Actions
        add_action( 'wp_ajax_tsfs_submit_form', array( $this, 'ajax_submit_form' ) );
        add_action( 'wp_ajax_nopriv_tsfs_submit_form', array( $this, 'ajax_submit_form' ) );
        add_action( 'wp_ajax_tsfs_track_view', array( $this, 'ajax_track_view' ) );
        add_action( 'wp_ajax_nopriv_tsfs_track_view', array( $this, 'ajax_track_view' ) );
    }

    /**
     * Save or update form via builder.
     */
    public function ajax_save_form() {
        check_ajax_referer( 'tsfs_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'techsolution-form-studio' ) ) );
        }

        $form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
        $title   = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : __( 'Untitled Form', 'techsolution-form-studio' );

        // JSON documents from the builder. After decoding, every value is sanitized per key by
        // TSFS_Form::sanitize_fields() and TSFS_Form::sanitize_settings() before it is stored.
        $fields_json   = isset( $_POST['fields_data'] ) ? wp_unslash( $_POST['fields_data'] ) : '[]'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $settings_json = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : '{}'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $fields        = TSFS_Form::sanitize_fields( json_decode( (string) $fields_json, true ) );
        $settings      = TSFS_Form::sanitize_settings( json_decode( (string) $settings_json, true ) );

        $saved_id = TSFS_DB::save_form(
            array(
                'title'       => $title,
                'fields_data' => $fields,
                'settings'    => $settings,
                'status'      => 'publish',
            ),
            $form_id
        );

        if ( ! $saved_id ) {
            wp_send_json_error( array( 'message' => __( 'Failed to save form. Please try again.', 'techsolution-form-studio' ) ) );
        }

        wp_send_json_success( array(
            'form_id'      => $saved_id,
            'redirect_url' => admin_url( 'admin.php?page=tsfs-builder&id=' . $saved_id ),
            'message'      => __( 'Form saved successfully!', 'techsolution-form-studio' ),
        ) );
    }

    /**
     * Delete form via AJAX.
     */
    public function ajax_delete_form() {
        check_ajax_referer( 'tsfs_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'techsolution-form-studio' ) ) );
        }

        $form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
        if ( ! $form_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid form ID.', 'techsolution-form-studio' ) ) );
        }

        $deleted = TSFS_DB::delete_form( $form_id );
        if ( $deleted ) {
            wp_send_json_success( array( 'message' => __( 'Form deleted successfully.', 'techsolution-form-studio' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Could not delete form.', 'techsolution-form-studio' ) ) );
        }
    }

    /**
     * Duplicate form via AJAX.
     */
    public function ajax_duplicate_form() {
        check_ajax_referer( 'tsfs_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'techsolution-form-studio' ) ) );
        }

        $form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
        $new_id  = TSFS_DB::duplicate_form( $form_id );

        if ( $new_id ) {
            wp_send_json_success( array(
                'message'  => __( 'Form duplicated successfully.', 'techsolution-form-studio' ),
                'new_id'   => $new_id,
                'edit_url' => admin_url( 'admin.php?page=tsfs-builder&id=' . $new_id ),
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Could not duplicate form.', 'techsolution-form-studio' ) ) );
        }
    }

    /**
     * Toggle form publish status (publish, draft, closed) via AJAX.
     */
    public function ajax_toggle_form_status() {
        check_ajax_referer( 'tsfs_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'techsolution-form-studio' ) ) );
        }

        $form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
        $status  = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'publish';

        if ( ! $form_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid form ID.', 'techsolution-form-studio' ) ) );
        }

        $updated = TSFS_DB::toggle_form_status( $form_id, $status );
        if ( false !== $updated ) {
            wp_send_json_success( array(
                'status'  => $status,
                /* translators: placeholders are values inserted into the message: value inserted into the message. */
                'message' => sprintf( __( 'Form status updated to %s.', 'techsolution-form-studio' ), ucfirst( $status ) ),
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to update form status.', 'techsolution-form-studio' ) ) );
        }
    }

    /**
     * Delete submission entry via AJAX.
     */
    public function ajax_delete_entry() {
        check_ajax_referer( 'tsfs_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'techsolution-form-studio' ) ) );
        }

        $entry_id = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
        $deleted  = TSFS_DB::delete_entry( $entry_id );

        if ( $deleted ) {
            wp_send_json_success( array( 'message' => __( 'Entry deleted successfully.', 'techsolution-form-studio' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Could not delete entry.', 'techsolution-form-studio' ) ) );
        }
    }

    /**
     * Get entry details for modal viewer.
     */
    public function ajax_get_entry_details() {
        check_ajax_referer( 'tsfs_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'techsolution-form-studio' ) ) );
        }

        $entry_id = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
        $entry    = TSFS_DB::get_entry( $entry_id );

        if ( ! $entry ) {
            wp_send_json_error( array( 'message' => __( 'Entry not found.', 'techsolution-form-studio' ) ) );
        }

        // Auto mark as read upon viewing if currently unread
        if ( 'unread' === $entry['status'] ) {
            TSFS_DB::update_entry_status( $entry_id, 'read' );
            $entry['status'] = 'read';
        }

        wp_send_json_success( array( 'entry' => $entry ) );
    }

    /**
     * Toggle submission status (read / unread / starred).
     */
    public function ajax_toggle_entry_status() {
        check_ajax_referer( 'tsfs_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'techsolution-form-studio' ) ) );
        }

        $entry_id = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
        $status   = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'read';

        if ( ! $entry_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid entry ID.', 'techsolution-form-studio' ) ) );
        }

        $updated = TSFS_DB::update_entry_status( $entry_id, $status );
        if ( false !== $updated ) {
            wp_send_json_success( array(
                'status'  => $status,
                'message' => __( 'Entry status updated.', 'techsolution-form-studio' ),
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to update entry status.', 'techsolution-form-studio' ) ) );
        }
    }

    /**
     * Save internal admin notes for a submission.
     */
    public function ajax_save_entry_note() {
        check_ajax_referer( 'tsfs_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'techsolution-form-studio' ) ) );
        }

        $entry_id = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
        $notes    = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';

        if ( ! $entry_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid entry ID.', 'techsolution-form-studio' ) ) );
        }

        TSFS_DB::maybe_upgrade_schema();
        $updated = TSFS_DB::update_entry_notes( $entry_id, $notes );

        if ( false !== $updated ) {
            wp_send_json_success( array(
                'message' => __( 'Note saved successfully.', 'techsolution-form-studio' ),
                'notes'   => $notes,
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to save note.', 'techsolution-form-studio' ) ) );
        }
    }

    /**
     * Bulk action handler for submissions (read, unread, delete, star).
     */
    public function ajax_bulk_entries() {
        check_ajax_referer( 'tsfs_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'techsolution-form-studio' ) ) );
        }

        $bulk_action = isset( $_POST['bulk_action'] ) ? sanitize_key( wp_unslash( $_POST['bulk_action'] ) ) : '';
        $entry_ids   = isset( $_POST['entry_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['entry_ids'] ) ) : array();

        if ( empty( $entry_ids ) ) {
            wp_send_json_error( array( 'message' => __( 'No entries selected.', 'techsolution-form-studio' ) ) );
        }

        switch ( $bulk_action ) {
            case 'mark_read':
                TSFS_DB::bulk_update_status( $entry_ids, 'read' );
                wp_send_json_success( array( 'message' => __( 'Entries marked as read.', 'techsolution-form-studio' ) ) );
                break;

            case 'mark_unread':
                TSFS_DB::bulk_update_status( $entry_ids, 'unread' );
                wp_send_json_success( array( 'message' => __( 'Entries marked as unread.', 'techsolution-form-studio' ) ) );
                break;

            case 'star':
                TSFS_DB::bulk_update_status( $entry_ids, 'starred' );
                wp_send_json_success( array( 'message' => __( 'Entries starred.', 'techsolution-form-studio' ) ) );
                break;

            case 'delete':
                TSFS_DB::bulk_delete_entries( $entry_ids );
                wp_send_json_success( array( 'message' => __( 'Entries permanently deleted.', 'techsolution-form-studio' ) ) );
                break;

            default:
                wp_send_json_error( array( 'message' => __( 'Invalid bulk action.', 'techsolution-form-studio' ) ) );
                break;
        }
    }

    /**
     * AJAX: Get form submission headers (fields + system columns) for header selection in export modal.
     */
    public function ajax_get_form_headers() {
        check_ajax_referer( 'tsfs_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'techsolution-form-studio' ) ) );
        }

        $form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
        $headers = array();

        // System headers always available
        $system_headers = array(
            array( 'key' => 'id',         'label' => __( 'Entry ID', 'techsolution-form-studio' ),       'is_system' => true, 'default' => true ),
            array( 'key' => 'form_title', 'label' => __( 'Form Title', 'techsolution-form-studio' ),     'is_system' => true, 'default' => ( $form_id === 0 ) ),
            array( 'key' => 'status',     'label' => __( 'Status', 'techsolution-form-studio' ),         'is_system' => true, 'default' => true ),
            array( 'key' => 'created_at', 'label' => __( 'Submitted At', 'techsolution-form-studio' ),   'is_system' => true, 'default' => true ),
            array( 'key' => 'user_ip',    'label' => __( 'Submitter IP', 'techsolution-form-studio' ),   'is_system' => true, 'default' => true ),
        );

        $field_headers = array();
        $seen_keys     = array();

        if ( $form_id > 0 ) {
            $form = TSFS_DB::get_form( $form_id );
            if ( $form && ! empty( $form['fields_data'] ) && is_array( $form['fields_data'] ) ) {
                foreach ( $form['fields_data'] as $field ) {
                    $key   = ! empty( $field['name'] ) ? sanitize_key( $field['name'] ) : '';
                    $label = ! empty( $field['label'] ) ? sanitize_text_field( $field['label'] ) : $key;
                    $type  = ! empty( $field['type'] ) ? sanitize_text_field( $field['type'] ) : 'text';

                    // Exclude non-input decorative fields from submission headers
                    if ( in_array( $type, array( 'html_block', 'divider', 'heading' ), true ) ) {
                        continue;
                    }

                    if ( $key && ! isset( $seen_keys[ $key ] ) ) {
                        $seen_keys[ $key ] = true;
                        $field_headers[] = array(
                            'key'       => $key,
                            'label'     => $label,
                            'is_system' => false,
                            'default'   => true,
                        );
                    }
                }
            }
        }

        // If specific form has no fields saved yet or All Forms is chosen, inspect recent entries to discover all submitted field keys
        if ( empty( $field_headers ) ) {
            $recent_entries = TSFS_DB::get_entries( array( 'form_id' => $form_id, 'limit' => 50 ) );
            foreach ( $recent_entries as $entry ) {
                if ( ! empty( $entry['fields_data'] ) && is_array( $entry['fields_data'] ) ) {
                    foreach ( $entry['fields_data'] as $field ) {
                        $key   = ! empty( $field['name'] ) ? sanitize_key( $field['name'] ) : ( ! empty( $field['label'] ) ? sanitize_key( $field['label'] ) : '' );
                        $label = ! empty( $field['label'] ) ? sanitize_text_field( $field['label'] ) : $key;
                        if ( $key && ! isset( $seen_keys[ $key ] ) ) {
                            $seen_keys[ $key ] = true;
                            $field_headers[] = array(
                                'key'       => $key,
                                'label'     => $label,
                                'is_system' => false,
                                'default'   => true,
                            );
                        }
                    }
                }
            }
        }

        // Add Notes column as optional system column
        $system_headers[] = array( 'key' => 'notes', 'label' => __( 'Admin Notes', 'techsolution-form-studio' ), 'is_system' => true, 'default' => false );

        wp_send_json_success( array(
            'system_headers' => $system_headers,
            'field_headers'  => $field_headers,
        ) );
    }

    /**
     * Enhanced Export: Exports filtered or all submissions with user-selected headers,
     * header meta (Form Title, Date From & To, Generated Time), and multiple format support (CSV, Excel XML/XLS, PDF/Print, HTML Report).
     */
    public function export_entries_csv() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Permission denied.', 'techsolution-form-studio' ) );
        }

        check_admin_referer( 'tsfs_export_entries', 'tsfs_nonce' );

        $form_id   = isset( $_REQUEST['form_id'] ) ? absint( $_REQUEST['form_id'] ) : 0;
        $status    = isset( $_REQUEST['status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['status'] ) ) : '';
        $date_from = isset( $_REQUEST['date_from'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['date_from'] ) ) : '';
        $date_to   = isset( $_REQUEST['date_to'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['date_to'] ) ) : '';
        $search    = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
        $format    = isset( $_REQUEST['export_format'] ) ? sanitize_key( wp_unslash( $_REQUEST['export_format'] ) ) : 'csv';

        // Specific selected entries (from checkbox selection) or all matching filters
        $entry_ids_raw = isset( $_REQUEST['entry_ids'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['entry_ids'] ) ) : '';
        $entry_ids     = array();
        if ( ! empty( $entry_ids_raw ) ) {
            $parts = explode( ',', $entry_ids_raw );
            foreach ( $parts as $p ) {
                $pid = absint( trim( $p ) );
                if ( $pid > 0 ) {
                    $entry_ids[] = $pid;
                }
            }
        }

        $filters = array(
            'form_id'   => $form_id,
            'status'    => $status,
            'date_from' => $date_from,
            'date_to'   => $date_to,
            'search'    => $search,
            'limit'     => 0, // All matching
        );

        $entries = TSFS_DB::get_entries( $filters );

        // If specific entry IDs were selected, narrow down to only those
        if ( ! empty( $entry_ids ) ) {
            $id_lookup = array_flip( $entry_ids );
            $entries = array_filter( $entries, function( $item ) use ( $id_lookup ) {
                return isset( $id_lookup[ (int) $item['id'] ] );
            } );
        }

        // Form Title Meta
        $form_title_meta = __( 'All Forms', 'techsolution-form-studio' );
        if ( $form_id > 0 ) {
            $form_obj = TSFS_DB::get_form( $form_id );
            if ( $form_obj && ! empty( $form_obj['title'] ) ) {
                $form_title_meta = $form_obj['title'];
            }
        }

        // Date range display text
        $date_range_meta = __( 'All Time', 'techsolution-form-studio' );
        if ( ! empty( $date_from ) && ! empty( $date_to ) ) {
            $date_range_meta = $date_from . ' to ' . $date_to;
        } elseif ( ! empty( $date_from ) ) {
            $date_range_meta = 'From ' . $date_from;
        } elseif ( ! empty( $date_to ) ) {
            $date_range_meta = 'Up to ' . $date_to;
        }

        // Selected Headers parsing
        $requested_headers = isset( $_REQUEST['export_headers'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_REQUEST['export_headers'] ) ) : array();
        $selected_header_keys = array();
        foreach ( $requested_headers as $h_key ) {
            $k = sanitize_text_field( $h_key );
            if ( '' !== $k ) {
                $selected_header_keys[] = $k;
            }
        }

        // Map of known field keys to their display labels
        $field_label_map = array(
            'id'         => __( 'Entry ID', 'techsolution-form-studio' ),
            'form_title' => __( 'Form Title', 'techsolution-form-studio' ),
            'status'     => __( 'Status', 'techsolution-form-studio' ),
            'created_at' => __( 'Submitted At', 'techsolution-form-studio' ),
            'user_ip'    => __( 'Submitter IP', 'techsolution-form-studio' ),
            'notes'      => __( 'Admin Notes', 'techsolution-form-studio' ),
        );

        if ( $form_id > 0 && ! empty( $form_obj['fields_data'] ) ) {
            foreach ( $form_obj['fields_data'] as $fld ) {
                if ( ! empty( $fld['name'] ) ) {
                    $field_label_map[ $fld['name'] ] = ! empty( $fld['label'] ) ? $fld['label'] : $fld['name'];
                }
            }
        }

        // Also harvest labels from entries data
        foreach ( $entries as $e_item ) {
            if ( ! empty( $e_item['fields_data'] ) && is_array( $e_item['fields_data'] ) ) {
                foreach ( $e_item['fields_data'] as $fld ) {
                    $f_key = ! empty( $fld['name'] ) ? $fld['name'] : ( ! empty( $fld['label'] ) ? sanitize_key( $fld['label'] ) : '' );
                    if ( $f_key && ! isset( $field_label_map[ $f_key ] ) ) {
                        $field_label_map[ $f_key ] = ! empty( $fld['label'] ) ? $fld['label'] : $f_key;
                    }
                }
            }
        }

        // If no specific headers were selected, default to standard set + discovered fields
        if ( empty( $selected_header_keys ) ) {
            $selected_header_keys = array( 'id', 'form_title', 'status', 'created_at', 'user_ip' );
            foreach ( $field_label_map as $k => $lbl ) {
                if ( ! in_array( $k, $selected_header_keys, true ) && 'notes' !== $k ) {
                    $selected_header_keys[] = $k;
                }
            }
        }

        // Helper to extract a single clean scalar/text value from an entry's field
        $extract_field_val = function( $entry, $col_key ) {
            if ( 'id' === $col_key ) {
                return (string) $entry['id'];
            }
            if ( 'form_title' === $col_key ) {
                return (string) ( ! empty( $entry['form_title'] ) ? $entry['form_title'] : 'Form #' . $entry['form_id'] );
            }
            if ( 'status' === $col_key ) {
                return ucfirst( (string) $entry['status'] );
            }
            if ( 'created_at' === $col_key ) {
                return (string) $entry['created_at'];
            }
            if ( 'user_ip' === $col_key ) {
                return (string) ( ! empty( $entry['user_ip'] ) ? $entry['user_ip'] : 'N/A' );
            }
            if ( 'notes' === $col_key ) {
                return (string) ( ! empty( $entry['notes'] ) ? $entry['notes'] : '' );
            }

            // Search in fields_data array
            if ( ! empty( $entry['fields_data'] ) && is_array( $entry['fields_data'] ) ) {
                foreach ( $entry['fields_data'] as $f ) {
                    $current_k = ! empty( $f['name'] ) ? $f['name'] : ( ! empty( $f['label'] ) ? sanitize_key( $f['label'] ) : '' );
                    if ( $current_k === $col_key ) {
                        $val = isset( $f['value'] ) ? $f['value'] : '';
                        if ( is_array( $val ) ) {
                            if ( isset( $val['url'] ) ) {
                                return $val['url'];
                            }
                            $parts = array();
                            foreach ( $val as $vk => $vv ) {
                                $parts[] = is_numeric( $vk ) ? $vv : ( ucfirst( str_replace( '_', ' ', $vk ) ) . ': ' . $vv );
                            }
                            return implode( '; ', $parts );
                        }
                        return (string) $val;
                    }
                }
            }

            return '';
        };

        // Determine sanitized filename prefix
        $safe_slug = ! empty( $form_obj['title'] ) ? sanitize_title( $form_obj['title'] ) : 'submissions';
        $timestamp = current_time( 'Y-m-d-His' );

        // ----------------------------------------------------
        // 1. PDF / Print-Ready Document Output
        // ----------------------------------------------------
        if ( 'pdf' === $format ) {
            $this->render_pdf_export_page(
                $entries,
                $selected_header_keys,
                $field_label_map,
                $form_title_meta,
                $date_range_meta,
                $extract_field_val
            );
            exit;
        }

        // ----------------------------------------------------
        // 2. Excel (Native XML Spreadsheet .xls)
        // ----------------------------------------------------
        if ( 'excel' === $format || 'xls' === $format ) {
            $filename = 'tsfs-' . $safe_slug . '-' . $timestamp . '.xls';
            header( 'Content-Type: application/vnd.ms-excel; charset=UTF-8' );
            header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
            header( 'Pragma: no-cache' );
            header( 'Expires: 0' );

            echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
            echo "<?mso-application progid=\"Excel.Sheet\"?>\n";
            ?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <Styles>
  <Style ss:ID="Default" ss:Name="Normal">
   <Alignment ss:Vertical="Center"/>
   <Font ss:FontName="Segoe UI" ss:Size="10" ss:Color="#1e293b"/>
  </Style>
  <Style ss:ID="HeaderMeta">
   <Font ss:FontName="Segoe UI" ss:Size="14" ss:Bold="1" ss:Color="#1e293b"/>
  </Style>
  <Style ss:ID="SubMeta">
   <Font ss:FontName="Segoe UI" ss:Size="9" ss:Color="#64748b"/>
  </Style>
  <Style ss:ID="ColHeader">
   <Alignment ss:Vertical="Center"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/>
   </Borders>
   <Font ss:FontName="Segoe UI" ss:Size="10" ss:Bold="1" ss:Color="#0f172a"/>
   <Interior ss:Color="#f1f5f9" ss:Pattern="Solid"/>
  </Style>
  <Style ss:ID="RowData">
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#f1f5f9"/>
   </Borders>
  </Style>
 </Styles>
 <Worksheet ss:Name="Submissions">
  <Table>
   <!-- Meta Header Section -->
   <Row ss:Height="26">
    <Cell ss:StyleID="HeaderMeta"><Data ss:Type="String"><?php echo esc_html( $form_title_meta ); ?> — Submissions Report</Data></Cell>
   </Row>
   <Row ss:Height="18">
    <Cell ss:StyleID="SubMeta"><Data ss:Type="String">Date Range: <?php echo esc_html( $date_range_meta ); ?> | Exported: <?php echo esc_html( current_time( 'Y-m-d H:i:s' ) ); ?> | Total Records: <?php echo absint( count( $entries ) ); ?></Data></Cell>
   </Row>
   <Row ss:Height="10"></Row>

   <!-- Column Headers -->
   <Row ss:Height="24">
    <?php foreach ( $selected_header_keys as $col_key ) : 
        $col_label = isset( $field_label_map[ $col_key ] ) ? $field_label_map[ $col_key ] : ucfirst( str_replace( '_', ' ', $col_key ) );
    ?>
        <Cell ss:StyleID="ColHeader"><Data ss:Type="String"><?php echo esc_html( $col_label ); ?></Data></Cell>
    <?php endforeach; ?>
   </Row>

   <!-- Data Rows -->
   <?php foreach ( $entries as $entry ) : ?>
    <Row ss:Height="20">
        <?php foreach ( $selected_header_keys as $col_key ) : 
            $cell_val = $extract_field_val( $entry, $col_key );
            $safe_val = TSFS_Security::sanitize_csv_cell( $cell_val );
        ?>
            <Cell ss:StyleID="RowData"><Data ss:Type="String"><?php echo esc_html( $safe_val ); ?></Data></Cell>
        <?php endforeach; ?>
    </Row>
   <?php endforeach; ?>
  </Table>
 </Worksheet>
</Workbook>
            <?php
            exit;
        }

        // ----------------------------------------------------
        // 3. CSV Spreadsheet with Meta Header Block & Formula Shield
        // ----------------------------------------------------
        $filename = 'tsfs-' . $safe_slug . '-' . $timestamp . '.csv';

        header( 'Content-Type: text/csv; charset=UTF-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        $output = fopen( 'php://output', 'w' );
        fputs( $output, "\xEF\xBB\xBF" ); // UTF-8 BOM for Microsoft Excel

        // Form Top Meta Headers
        fputcsv( $output, array( '# TECHSOLUTION FORM STUDIO - SUBMISSIONS EXPORT' ) );
        fputcsv( $output, array( 'Form Title:', $form_title_meta ) );
        fputcsv( $output, array( 'Date Range:', $date_range_meta ) );
        fputcsv( $output, array( 'Generated At:', current_time( 'Y-m-d H:i:s' ) ) );
        fputcsv( $output, array( 'Total Submissions:', count( $entries ) ) );
        fputcsv( $output, array( '' ) ); // Empty separator row

        // Column Header Row
        $header_labels = array();
        foreach ( $selected_header_keys as $col_key ) {
            $header_labels[] = isset( $field_label_map[ $col_key ] ) ? $field_label_map[ $col_key ] : ucfirst( str_replace( '_', ' ', $col_key ) );
        }
        fputcsv( $output, $header_labels );

        // Table Rows
        foreach ( $entries as $entry ) {
            $raw_row = array();
            foreach ( $selected_header_keys as $col_key ) {
                $raw_row[] = $extract_field_val( $entry, $col_key );
            }
            $safe_row = array_map( array( 'TSFS_Security', 'sanitize_csv_cell' ), $raw_row );
            fputcsv( $output, $safe_row );
        }

        fclose( $output );
        exit;
    }

    /**
     * Renders a clean, executive print-ready PDF preview page with automatic browser print trigger.
     */
    private function render_pdf_export_page( $entries, $selected_header_keys, $field_label_map, $form_title_meta, $date_range_meta, $extract_field_val ) {
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html( $form_title_meta ); ?> — Submissions Report</title>
    <?php
    wp_register_style( 'tsfs-print', TSFS_URL . 'admin/assets/css/tsfs-print.css', array(), TSFS_VERSION );
    wp_register_script( 'tsfs-print', TSFS_URL . 'admin/assets/js/tsfs-print.js', array(), TSFS_VERSION, true );
    wp_print_styles( array( 'tsfs-print' ) );
    ?>
</head>
<body>
    <div class="print-actions">
        <button type="button" class="btn-print" data-tsfs-print><?php esc_html_e( 'Print / Save as PDF', 'techsolution-form-studio' ); ?></button>
        <button type="button" class="btn-close" data-tsfs-close><?php esc_html_e( 'Close Window', 'techsolution-form-studio' ); ?></button>
    </div>

    <div class="report-header">
        <div>
            <h1 class="report-title"><?php echo esc_html( $form_title_meta ); ?></h1>
            <div class="report-meta-grid">
                <div class="report-meta-item"><strong>Date Range:</strong> <?php echo esc_html( $date_range_meta ); ?></div>
                <div class="report-meta-item"><strong>Total Submissions:</strong> <?php echo absint( count( $entries ) ); ?></div>
                <div class="report-meta-item"><strong>Exported:</strong> <?php echo esc_html( current_time( 'M j, Y g:i A' ) ); ?></div>
            </div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:14px; font-weight:800; color:#2563eb;">TechSolution Form Studio</div>
            <div style="font-size:10px; color:#94a3b8;"><?php echo esc_html( home_url() ); ?></div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <?php foreach ( $selected_header_keys as $col_key ) : 
                    $col_label = isset( $field_label_map[ $col_key ] ) ? $field_label_map[ $col_key ] : ucfirst( str_replace( '_', ' ', $col_key ) );
                ?>
                    <th><?php echo esc_html( $col_label ); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $entries ) ) : ?>
                <tr>
                    <td colspan="<?php echo absint( count( $selected_header_keys ) ); ?>" style="text-align:center; padding:30px;">
                        No submissions found matching criteria.
                    </td>
                </tr>
            <?php else : ?>
                <?php foreach ( $entries as $entry ) : ?>
                    <tr>
                        <?php foreach ( $selected_header_keys as $col_key ) : 
                            $cell_val = $extract_field_val( $entry, $col_key );
                        ?>
                            <td>
                                <?php if ( 'status' === $col_key ) : 
                                    $st = strtolower( $cell_val );
                                ?>
                                    <span class="badge badge-<?php echo esc_attr( $st ); ?>"><?php echo esc_html( $cell_val ); ?></span>
                                <?php else : ?>
                                    <?php echo esc_html( $cell_val ); ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="report-footer">
        <div>Generated by TechSolution Form Studio — Confidential Submissions Log</div>
        <div>Page 1 of 1</div>
    </div>

    <?php wp_print_scripts( array( 'tsfs-print' ) ); ?>
</body>
</html>
        <?php
    }

    /**
     * Track impression / view counter for a form.
     */
    public function ajax_track_view() {
        $form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
        if ( $form_id > 0 ) {
            TSFS_DB::increment_views( $form_id );
            wp_send_json_success( array( 'tracked' => true, 'form_id' => $form_id ) );
        }
        wp_send_json_error( array( 'tracked' => false ) );
    }

    /**
     * Public form submission handler.
     * Verifies the nonce and anti-spam checks, then validates and sanitizes every field before saving.
     */
    public function ajax_submit_form() {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- these two values are needed to verify the nonce below.
        $form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
        $nonce   = isset( $_POST['tsfs_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['tsfs_nonce'] ) ) : '';
        if ( ! $form_id || ! wp_verify_nonce( $nonce, 'tsfs_form_submit_' . $form_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Security verification failed. Please refresh the page and try again.', 'techsolution-form-studio' ) ) );
        }

        // Anti-spam honeypot: bots fill the hidden field; respond as if successful and store nothing.
        if ( ! empty( $_POST['tsfs_hp_field'] ) ) {
            wp_send_json_success( array(
                'message'      => __( 'Thank you! Your submission has been received.', 'techsolution-form-studio' ),
                'redirect_url' => '',
            ) );
        }

        // Client IP & security checks (block list & rate limiting)
        $user_ip = ! empty( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        if ( ! filter_var( $user_ip, FILTER_VALIDATE_IP ) ) {
            $user_ip = '';
        }

        if ( TSFS_Security::is_ip_blacklisted( $user_ip ) ) {
            wp_send_json_error( array( 'message' => __( 'Access denied. Your IP address is not permitted to submit forms.', 'techsolution-form-studio' ) ) );
        }

        $rate_check = TSFS_Security::check_rate_limit( $user_ip, $form_id );
        if ( is_wp_error( $rate_check ) ) {
            wp_send_json_error( array( 'message' => $rate_check->get_error_message() ) );
        }

        // Time-gate bot trap (submissions completing faster than a person can type)
        $time_token = isset( $_POST['tsfs_time_token'] ) ? sanitize_text_field( wp_unslash( $_POST['tsfs_time_token'] ) ) : '';
        $time_check = TSFS_Security::verify_time_token( $time_token, $form_id );
        if ( is_wp_error( $time_check ) ) {
            wp_send_json_error( array( 'message' => $time_check->get_error_message() ) );
        }

        // CAPTCHA verification (Google reCAPTCHA v2/v3 / Cloudflare Turnstile)
        $captcha_token = isset( $_POST['g-recaptcha-response'] )
            ? sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) )
            : ( isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : '' );
        $captcha_check = TSFS_Security::verify_captcha( $captcha_token, $user_ip );
        if ( is_wp_error( $captcha_check ) ) {
            wp_send_json_error( array( 'message' => $captcha_check->get_error_message() ) );
        }

        // Load form definition
        $form = TSFS_DB::get_form( $form_id );
        if ( ! $form || 'publish' !== $form['status'] ) {
            wp_send_json_error( array( 'message' => __( 'Form is no longer active.', 'techsolution-form-studio' ) ) );
        }

        $fields   = ! empty( $form['fields_data'] ) ? $form['fields_data'] : array();
        $settings = ! empty( $form['settings'] ) ? $form['settings'] : array();

        // Login restriction
        if ( ! empty( $settings['require_login'] ) && ! is_user_logged_in() ) {
            $login_msg = ! empty( $settings['login_message'] ) ? $settings['login_message'] : __( 'You must be logged in to submit this form.', 'techsolution-form-studio' );
            wp_send_json_error( array( 'message' => $login_msg ) );
        }

        // Closing date
        if ( ! empty( $settings['close_after_date'] ) && ! empty( $settings['close_date'] ) ) {
            if ( current_time( 'timestamp' ) > strtotime( $settings['close_date'] . ' 23:59:59' ) ) {
                $closed_msg = ! empty( $settings['closed_message'] ) ? $settings['closed_message'] : __( 'This form is currently closed for submissions.', 'techsolution-form-studio' );
                wp_send_json_error( array( 'message' => $closed_msg ) );
            }
        }

        // Maximum submissions
        if ( ! empty( $settings['limit_submissions'] ) && ! empty( $settings['max_entries'] ) ) {
            $entry_counts = TSFS_DB::get_status_counts( $form_id );
            if ( (int) $entry_counts['all'] >= (int) $settings['max_entries'] ) {
                $closed_msg = ! empty( $settings['closed_message'] ) ? $settings['closed_message'] : __( 'This form has reached its maximum submission limit.', 'techsolution-form-studio' );
                wp_send_json_error( array( 'message' => $closed_msg ) );
            }
        }

        $errors          = array();
        $submitted_entry = array();
        $user_email      = '';
        $user_name       = '';
        $core_types      = TSFS_Form::get_core_field_types();

        foreach ( $fields as $field ) {
            $type     = isset( $field['type'] ) ? $field['type'] : 'text';
            $name     = ! empty( $field['name'] ) ? sanitize_key( $field['name'] ) : '';
            $label    = isset( $field['label'] ) ? $field['label'] : $name;
            $required = ! empty( $field['required'] );

            if ( 'html' === $type || 'page_break' === $type || empty( $name ) ) {
                continue;
            }

            // Fields hidden by conditional logic are not submitted by the browser and are skipped here as well.
            if ( ! $this->is_field_visible( $field ) ) {
                continue;
            }

            // Field types provided by add-ons process their own input.
            if ( ! in_array( $type, $core_types, true ) ) {
                /**
                 * Validate and sanitize the submitted value of an add-on field type.
                 *
                 * @param null|array|WP_Error $result  Return array( 'value' => ..., optional 'user_name', 'user_email' ), or WP_Error.
                 * @param array               $field   Saved field configuration.
                 * @param array               $context Keys: form_id, name, label, required.
                 */
                $result = apply_filters( 'tsfs_process_field_' . $type, null, $field, array(
                    'form_id'  => $form_id,
                    'name'     => $name,
                    'label'    => $label,
                    'required' => $required,
                ) );
                if ( is_wp_error( $result ) ) {
                    $errors[ $name ] = $result->get_error_message();
                } elseif ( is_array( $result ) && array_key_exists( 'value', $result ) ) {
                    $submitted_entry[] = array( 'name' => $name, 'label' => $label, 'type' => $type, 'value' => $result['value'] );
                    if ( empty( $user_name ) && ! empty( $result['user_name'] ) ) {
                        $user_name = sanitize_text_field( $result['user_name'] );
                    }
                    if ( empty( $user_email ) && ! empty( $result['user_email'] ) && is_email( $result['user_email'] ) ) {
                        $user_email = sanitize_email( $result['user_email'] );
                    }
                }
                continue;
            }

            if ( 'file' === $type ) {
                $upload = $this->process_file_upload( $field, $name, $label, $required );
                if ( is_wp_error( $upload ) ) {
                    $errors[ $name ] = $upload->get_error_message();
                } else {
                    $submitted_entry[] = array( 'name' => $name, 'label' => $label, 'type' => $type, 'value' => $upload );
                }
                continue;
            }

            // Read and sanitize the posted value immediately; type-specific checks follow.
            $raw_val = null;
            if ( isset( $_POST[ $name ] ) ) {
                $raw_val = is_array( $_POST[ $name ] )
                    ? array_map( 'sanitize_text_field', array_filter( wp_unslash( $_POST[ $name ] ), 'is_scalar' ) )
                    : sanitize_textarea_field( wp_unslash( $_POST[ $name ] ) );
            }

            $is_empty = is_array( $raw_val ) ? empty( $raw_val ) : ( null === $raw_val || '' === trim( $raw_val ) );

            if ( 'consent' === $type ) {
                $accepted = TSFS_Form::consent_accepted( $raw_val );
                if ( ( $required && ! $accepted ) || ( ! $is_empty && ! $accepted ) ) {
                    $errors[ $name ] = __( 'Please check the consent box to continue.', 'techsolution-form-studio' );
                } else {
                    $submitted_entry[] = array( 'name' => $name, 'label' => $label, 'type' => $type, 'value' => $accepted ? __( 'Agreed & Accepted', 'techsolution-form-studio' ) : '' );
                }
                continue;
            }

            if ( $required && $is_empty ) {
                /* translators: %s: field label */
                $errors[ $name ] = sprintf( __( '%s is required.', 'techsolution-form-studio' ), $label );
                continue;
            }

            $clean_val = $this->validate_core_value( $type, $field, $raw_val, $is_empty );
            if ( is_wp_error( $clean_val ) ) {
                $errors[ $name ] = $clean_val->get_error_message();
                continue;
            }

            if ( 'email' === $type && empty( $user_email ) && ! empty( $clean_val ) ) {
                $user_email = $clean_val;
            }
            if ( 'text' === $type && empty( $user_name ) && in_array( $name, array( 'name', 'your_name', 'full_name' ), true ) ) {
                $user_name = $clean_val;
            }

            $submitted_entry[] = array(
                'name'  => $name,
                'label' => $label,
                'type'  => $type,
                'value' => $clean_val,
            );
        }

        if ( ! empty( $errors ) ) {
            wp_send_json_error( array(
                'message' => __( 'Please fix the highlighted errors before submitting.', 'techsolution-form-studio' ),
                'errors'  => $errors,
            ) );
        }

        // Blocked keywords & phishing check
        $keyword_check = TSFS_Security::check_spam_keywords( $submitted_entry );
        if ( is_wp_error( $keyword_check ) ) {
            wp_send_json_error( array( 'message' => $keyword_check->get_error_message() ) );
        }

        // Record submission. IP address and user agent are stored only when enabled in Settings → Privacy.
        $security   = TSFS_Security::get_settings();
        $stored_ip  = ! empty( $security['store_ip_address'] ) ? $user_ip : '';
        $user_agent = ! empty( $security['store_user_agent'] ) && ! empty( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

        $entry_id = TSFS_DB::save_entry( $form_id, $submitted_entry, $stored_ip, $user_agent );

        if ( ! $entry_id ) {
            wp_send_json_error( array( 'message' => __( 'Failed to save submission. Please try again.', 'techsolution-form-studio' ) ) );
        }

        // Plain-text field summary for emails
        $fields_summary = '';
        foreach ( $submitted_entry as $item ) {
            $fields_summary .= sprintf( "%s: %s\n", $item['label'], TSFS_Form::format_value_text( $item['value'], "\n  " ) );
        }

        $replacements = array(
            '{form_title}' => $form['title'],
            '{entry_id}'   => $entry_id,
            '{user_name}'  => $user_name ? $user_name : __( 'Visitor', 'techsolution-form-studio' ),
            '{user_email}' => $user_email,
            '{all_fields}' => $fields_summary,
            '{date}'       => current_time( 'mysql' ),
            '{user_ip}'    => $stored_ip,
        );
        foreach ( $submitted_entry as $item ) {
            $replacements[ '{field:' . $item['name'] . '}' ] = TSFS_Form::format_value_text( $item['value'] );
        }

        // Admin email notification with merge tags & Reply-To
        if ( ! empty( $settings['send_email'] ) ) {
            $to_email = ! empty( $settings['notification_email'] ) ? $settings['notification_email'] : get_option( 'admin_email' );

            /* translators: 1: form title merge tag, 2: entry ID merge tag */
            $subject_template = ! empty( $settings['email_subject'] ) ? $settings['email_subject'] : sprintf( __( 'New submission for %1$s [#%2$s]', 'techsolution-form-studio' ), '{form_title}', '{entry_id}' );
            $subject = str_replace( array_keys( $replacements ), array_values( $replacements ), $subject_template );

            /* translators: %s: form title */
            $body  = sprintf( __( "A new entry has been submitted for form: %s\n\n", 'techsolution-form-studio' ), $form['title'] );
            $body .= "----------------------------------------\n";
            $body .= $fields_summary;
            $body .= "----------------------------------------\n";
            /* translators: 1: submission date, 2: entry ID */
            $body .= sprintf( __( "Submitted on: %1\$s\nEntry ID: %2\$d", 'techsolution-form-studio' ), current_time( 'mysql' ), $entry_id );
            $body .= "\n" . admin_url( 'admin.php?page=tsfs-entries&entry_id=' . $entry_id );

            $headers = array( 'Content-Type: text/plain; charset=UTF-8' );
            if ( ! empty( $user_email ) ) {
                $headers[] = 'Reply-To: ' . ( $user_name ? str_replace( array( "\r", "\n", '<', '>', ',' ), '', $user_name ) . ' ' : '' ) . '<' . $user_email . '>';
            }

            $valid_recipients = array();
            foreach ( explode( ',', (string) $to_email ) as $rec ) {
                $clean_rec = sanitize_email( trim( $rec ) );
                if ( is_email( $clean_rec ) ) {
                    $valid_recipients[] = $clean_rec;
                }
            }
            if ( empty( $valid_recipients ) ) {
                $valid_recipients = array( get_option( 'admin_email' ) );
            }

            wp_mail( $valid_recipients, $subject, $body, $headers );
        }

        // Auto-responder email to the submitter
        if ( ! empty( $settings['send_user_auto_reply'] ) && ! empty( $user_email ) ) {
            $user_subject = ! empty( $settings['auto_reply_subject'] ) ? $settings['auto_reply_subject'] : __( 'Thank you for your submission', 'techsolution-form-studio' );
            $user_body    = ! empty( $settings['auto_reply_message'] ) ? $settings['auto_reply_message'] : __( 'We have received your submission and will get back to you shortly.', 'techsolution-form-studio' );
            // Only site-controlled tags: submitted values are never echoed back, so the form cannot relay arbitrary text.
            $safe_tags    = array( '{form_title}' => $form['title'], '{entry_id}' => $entry_id, '{date}' => current_time( 'mysql' ) );
            $user_subject = str_replace( array_keys( $safe_tags ), array_values( $safe_tags ), $user_subject );
            $user_body    = str_replace( array_keys( $safe_tags ), array_values( $safe_tags ), $user_body );
            wp_mail( $user_email, $user_subject, $user_body, array( 'Content-Type: text/plain; charset=UTF-8' ) );
        }

        // Webhook to the URL configured by the site owner (Zapier, Make, n8n, CRM)
        if ( ! empty( $settings['enable_webhook'] ) && ! empty( $settings['webhook_url'] ) ) {
            TSFS_Security::dispatch_webhook(
                $settings['webhook_url'],
                array(
                    'event'        => 'tsfs_form_submission',
                    'form_id'      => $form_id,
                    'form_title'   => $form['title'],
                    'entry_id'     => $entry_id,
                    'user_ip'      => $stored_ip,
                    'user_name'    => $user_name,
                    'user_email'   => $user_email,
                    'submitted_at' => current_time( 'mysql' ),
                    'fields'       => $submitted_entry,
                ),
                ! empty( $settings['webhook_secret'] ) ? $settings['webhook_secret'] : ''
            );
        }

        /**
         * Fires after a submission is saved and notifications are sent.
         *
         * @param int   $entry_id        Entry ID.
         * @param array $form            Form record.
         * @param array $submitted_entry Sanitized field values.
         */
        do_action( 'tsfs_after_submission', $entry_id, $form, $submitted_entry );

        $success_message = ! empty( $settings['success_message'] ) ? $settings['success_message'] : __( 'Thank you! Your submission has been received.', 'techsolution-form-studio' );

        $redirect_url = ! empty( $settings['redirect_url'] ) ? esc_url_raw( $settings['redirect_url'] ) : '';
        if ( ! empty( $redirect_url ) && ! empty( $settings['pass_query_string'] ) ) {
            $query_args = array(
                'entry_id' => $entry_id,
                'form_id'  => $form_id,
            );
            if ( ! empty( $user_name ) ) {
                $query_args['user_name'] = rawurlencode( $user_name );
            }
            $redirect_url = add_query_arg( $query_args, $redirect_url );
        }

        wp_send_json_success( array(
            'message'      => $success_message,
            'redirect_url' => $redirect_url,
            'entry_id'     => $entry_id,
        ) );
    }

    /**
     * Evaluate a field's conditional logic rules against the submitted values (mirrors the browser logic).
     */
    private function is_field_visible( $field ) {
        if ( empty( $field['enable_conditions'] ) || empty( $field['conditions'] ) || ! is_array( $field['conditions'] ) ) {
            return true;
        }
        $results = array();
        foreach ( $field['conditions'] as $rule ) {
            $target   = isset( $rule['field'] ) ? sanitize_key( $rule['field'] ) : '';
            $operator = isset( $rule['operator'] ) ? $rule['operator'] : '==';
            $expected = strtolower( trim( isset( $rule['value'] ) ? (string) $rule['value'] : '' ) );
            $current  = '';
            if ( '' !== $target && isset( $_POST[ $target ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in ajax_submit_form().
                $current = is_array( $_POST[ $target ] )
                    ? array_map( 'strtolower', array_map( 'trim', array_map( 'sanitize_text_field', array_filter( wp_unslash( $_POST[ $target ] ), 'is_scalar' ) ) ) )
                    : strtolower( trim( sanitize_text_field( wp_unslash( $_POST[ $target ] ) ) ) );
            }
            if ( is_array( $current ) ) {
                $met = 'empty' === $operator ? empty( $current ) : ( 'not_empty' === $operator ? ! empty( $current ) : ( '!=' === $operator ? ! in_array( $expected, $current, true ) : in_array( $expected, $current, true ) ) );
            } else {
                switch ( $operator ) {
                    case '!=':
                        $met = $current !== $expected;
                        break;
                    case 'contains':
                        $met = '' !== $expected && false !== strpos( $current, $expected );
                        break;
                    case 'empty':
                        $met = '' === $current;
                        break;
                    case 'not_empty':
                        $met = '' !== $current;
                        break;
                    default:
                        $met = $current === $expected;
                }
            }
            $results[] = $met;
        }
        $satisfied = ( isset( $field['condition_match'] ) && 'any' === $field['condition_match'] ) ? in_array( true, $results, true ) : ! in_array( false, $results, true );
        return ( isset( $field['condition_action'] ) && 'hide' === $field['condition_action'] ) ? ! $satisfied : $satisfied;
    }

    /**
     * Validate an already sanitized core field value and normalize it for storage.
     *
     * @return mixed|WP_Error
     */
    private function validate_core_value( $type, $field, $value, $is_empty ) {
        if ( $is_empty ) {
            return is_array( $value ) ? array() : '';
        }

        switch ( $type ) {
            case 'email':
                $email = sanitize_email( $value );
                return is_email( $email ) ? $email : new WP_Error( 'invalid', __( 'Please enter a valid email address.', 'techsolution-form-studio' ) );

            case 'url':
                $url = esc_url_raw( $value, array( 'http', 'https' ) );
                return $url ? $url : new WP_Error( 'invalid', __( 'Please enter a valid website address starting with http:// or https://.', 'techsolution-form-studio' ) );

            case 'textarea':
                return is_array( $value ) ? '' : $value;

            case 'number':
            case 'range':
                if ( is_array( $value ) || ! is_numeric( $value ) ) {
                    return new WP_Error( 'invalid', __( 'Please enter a valid number.', 'techsolution-form-studio' ) );
                }
                $number = 0 + $value;
                $min    = isset( $field['min'] ) && is_numeric( $field['min'] ) ? 0 + $field['min'] : ( 'range' === $type ? 1 : null );
                $max    = isset( $field['max'] ) && is_numeric( $field['max'] ) ? 0 + $field['max'] : ( 'range' === $type ? 10 : null );
                if ( ( null !== $min && $number < $min ) || ( null !== $max && $number > $max ) ) {
                    /* translators: 1: minimum, 2: maximum */
                    return new WP_Error( 'invalid', sprintf( __( 'Please enter a number between %1$s and %2$s.', 'techsolution-form-studio' ), null === $min ? '-∞' : $min, null === $max ? '∞' : $max ) );
                }
                return (string) $number;

            case 'rating':
                $stars = absint( $value );
                $max   = TSFS_Form::rating_max( $field );
                return ( $stars >= 1 && $stars <= $max ) ? (string) $stars : new WP_Error( 'invalid', __( 'Please choose a rating.', 'techsolution-form-studio' ) );

            case 'date':
                $date = DateTime::createFromFormat( 'Y-m-d', (string) $value );
                return ( $date && $date->format( 'Y-m-d' ) === $value ) ? $value : new WP_Error( 'invalid', __( 'Please enter a valid date.', 'techsolution-form-studio' ) );

            case 'time':
                return preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', (string) $value ) ? $value : new WP_Error( 'invalid', __( 'Please enter a valid time.', 'techsolution-form-studio' ) );

            case 'tel':
                $digits = strlen( preg_replace( '/\D/', '', (string) $value ) );
                if ( $digits < 7 || $digits > 15 || ! preg_match( '/^[0-9+\-\s().]+$/', (string) $value ) ) {
                    /* translators: %d: number of digits entered */
                    return new WP_Error( 'invalid', sprintf( __( 'Please enter a valid phone number (between 7 and 15 digits; you entered %d).', 'techsolution-form-studio' ), $digits ) );
                }
                return $value;

            case 'cnic':
                $cnic = TSFS_Form::normalize_cnic( (string) $value );
                return false === $cnic ? new WP_Error( 'invalid', __( 'Enter CNIC / Form-B as 12345-1234567-1 (13 digits).', 'techsolution-form-studio' ) ) : $cnic;

            case 'country':
                return TSFS_Form::is_valid_country_value( $value ) ? $value : new WP_Error( 'invalid', __( 'Please select a valid country from the list.', 'techsolution-form-studio' ) );

            case 'select':
            case 'radio':
                $options = isset( $field['options'] ) ? (array) $field['options'] : array();
                return ( ! is_array( $value ) && in_array( $value, $options, true ) ) ? $value : new WP_Error( 'invalid', __( 'Please choose one of the available options.', 'techsolution-form-studio' ) );

            case 'checkbox':
                $options = isset( $field['options'] ) ? (array) $field['options'] : array();
                $value   = (array) $value;
                return array_diff( $value, $options ) ? new WP_Error( 'invalid', __( 'Please choose from the available options.', 'techsolution-form-studio' ) ) : array_values( $value );

            case 'hidden':
                return is_array( $value ) ? '' : sanitize_text_field( $value );

            default:
                return is_array( $value ) ? implode( ', ', $value ) : sanitize_text_field( $value );
        }
    }

    /**
     * Validate and store an uploaded file in the protected uploads sandbox.
     *
     * @return array|string|WP_Error File data, '' when no optional file was sent, or an error.
     */
    private function process_file_upload( $field, $name, $label, $required ) {
        if ( empty( $_FILES[ $name ]['name'] ) || ! isset( $_FILES[ $name ]['tmp_name'] ) || ! is_uploaded_file( $_FILES[ $name ]['tmp_name'] ) ) {
            /* translators: %s: field label */
            return $required ? new WP_Error( 'required', sprintf( __( '%s is required.', 'techsolution-form-studio' ), $label ) ) : '';
        }

        $file = array(
            'name'     => sanitize_file_name( wp_unslash( $_FILES[ $name ]['name'] ) ),
            'type'     => isset( $_FILES[ $name ]['type'] ) ? sanitize_mime_type( wp_unslash( $_FILES[ $name ]['type'] ) ) : '',
            'tmp_name' => $_FILES[ $name ]['tmp_name'], // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- verified with is_uploaded_file().
            'error'    => isset( $_FILES[ $name ]['error'] ) ? absint( $_FILES[ $name ]['error'] ) : UPLOAD_ERR_NO_FILE,
            'size'     => isset( $_FILES[ $name ]['size'] ) ? absint( $_FILES[ $name ]['size'] ) : 0,
        );

        $allowed = explode( ',', TSFS_Form::sanitize_extension_list( isset( $field['allowed_ext'] ) ? $field['allowed_ext'] : '' ) );
        $max_mb  = ! empty( $field['max_size'] ) ? (float) $field['max_size'] : 5;

        $check = TSFS_Security::validate_file_upload( $file, $allowed, $max_mb );
        if ( is_wp_error( $check ) ) {
            return $check;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        $protection = TSFS_Security::protect_upload_directory();
        if ( is_wp_error( $protection ) ) {
            return $protection;
        }

        $uploads = wp_upload_dir();
        if ( ! empty( $uploads['error'] ) ) {
            return new WP_Error( 'upload', $uploads['error'] );
        }
        $sandbox_path = trailingslashit( $uploads['basedir'] ) . 'techsolution-form-studio';
        $sandbox_url  = trailingslashit( $uploads['baseurl'] ) . 'techsolution-form-studio';
        $filter = function ( $dirs ) use ( $sandbox_path, $sandbox_url ) {
            $dirs['path']    = $sandbox_path;
            $dirs['url']     = $sandbox_url;
            $dirs['subdir']  = '';
            $dirs['basedir'] = dirname( $sandbox_path );
            $dirs['baseurl'] = dirname( $sandbox_url );
            return $dirs;
        };

        add_filter( 'upload_dir', $filter, 99 );
        $mimes = array();
        foreach ( wp_get_mime_types() as $exts => $mime ) {
            if ( array_intersect( explode( '|', $exts ), $allowed ) ) {
                $mimes[ $exts ] = $mime;
            }
        }
        $random_name = function ( $dir, $name, $ext ) {
            return wp_generate_password( 24, false, false ) . strtolower( $ext );
        };
        $upload = wp_handle_upload( $file, array( 'test_form' => false, 'mimes' => $mimes, 'unique_filename_callback' => $random_name ) );
        remove_filter( 'upload_dir', $filter, 99 );

        if ( isset( $upload['error'] ) ) {
            return new WP_Error( 'upload', $upload['error'] );
        }

        $stored = basename( $upload['file'] );
        return array(
            'url'    => esc_url_raw( TSFS_Security::file_download_url( $stored ) ),
            'file'   => $file['name'],
            'stored' => $stored,
        );
    }

    /**
     * Save Security & Anti-spam Settings via AJAX.
     */
    public function ajax_save_security_settings() {
        check_ajax_referer( 'tsfs_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'techsolution-form-studio' ) ) );
        }

        $provider = isset( $_POST['anti_spam_provider'] ) ? sanitize_key( wp_unslash( $_POST['anti_spam_provider'] ) ) : 'none';
        $score    = isset( $_POST['recaptcha_v3_score'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['recaptcha_v3_score'] ) ) : 0.5;
        $text     = function ( $key ) {
            return isset( $_POST[ $key ] ) ? sanitize_text_field( trim( wp_unslash( $_POST[ $key ] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce checked above.
        };

        $settings = array(
            'anti_spam_provider'          => in_array( $provider, array( 'none', 'recaptcha_v2', 'recaptcha_v3', 'turnstile' ), true ) ? $provider : 'none',
            'recaptcha_site_key'          => $text( 'recaptcha_site_key' ),
            'recaptcha_secret_key'        => $text( 'recaptcha_secret_key' ),
            'recaptcha_v3_score'          => (string) max( 0.1, min( 0.9, $score ) ),
            'turnstile_site_key'          => $text( 'turnstile_site_key' ),
            'turnstile_secret_key'        => $text( 'turnstile_secret_key' ),
            'enable_time_gate'            => ! empty( $_POST['enable_time_gate'] ),
            'time_gate_seconds'           => isset( $_POST['time_gate_seconds'] ) ? min( 60, absint( $_POST['time_gate_seconds'] ) ) : 2,
            'enable_rate_limiting'        => ! empty( $_POST['enable_rate_limiting'] ),
            'rate_limit_max'              => isset( $_POST['rate_limit_max'] ) ? max( 1, absint( $_POST['rate_limit_max'] ) ) : 5,
            'rate_limit_window'           => isset( $_POST['rate_limit_window'] ) ? max( 1, absint( $_POST['rate_limit_window'] ) ) : 5,
            'blacklisted_ips'             => isset( $_POST['blacklisted_ips'] ) ? sanitize_textarea_field( wp_unslash( $_POST['blacklisted_ips'] ) ) : '',
            'blocked_keywords'            => isset( $_POST['blocked_keywords'] ) ? sanitize_textarea_field( wp_unslash( $_POST['blocked_keywords'] ) ) : '',
            'enable_upload_hardening'     => ! empty( $_POST['enable_upload_hardening'] ),
            'enable_mime_sniffing'        => ! empty( $_POST['enable_mime_sniffing'] ),
            'enable_csv_injection_shield' => ! empty( $_POST['enable_csv_injection_shield'] ),
            'store_ip_address'            => ! empty( $_POST['store_ip_address'] ),
            'store_user_agent'            => ! empty( $_POST['store_user_agent'] ),
            'entry_retention_days'        => isset( $_POST['entry_retention_days'] ) ? min( 3650, absint( $_POST['entry_retention_days'] ) ) : 0,
            'delete_data_on_uninstall'    => ! empty( $_POST['delete_data_on_uninstall'] ),
        );

        TSFS_Security::save_settings( $settings );
        $protection_result = TSFS_Security::protect_upload_directory();
        if ( is_wp_error( $protection_result ) ) {
            wp_send_json_error( array( 'message' => $protection_result->get_error_message() ) );
        }

        wp_send_json_success( array( 'message' => __( 'Security configuration updated & shields armed successfully!', 'techsolution-form-studio' ) ) );
    }

    /**
     * Re-deploy security sandboxes via AJAX.
     */
    public function ajax_redeploy_shields() {
        check_ajax_referer( 'tsfs_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'techsolution-form-studio' ) ) );
        }

        $protection_result = TSFS_Security::protect_upload_directory( true );
        if ( is_wp_error( $protection_result ) ) {
            wp_send_json_error( array( 'message' => $protection_result->get_error_message() ) );
        }

        wp_send_json_success( array(
            'message' => __( 'Upload directory .htaccess script blocker and index.php silencer successfully redeployed!', 'techsolution-form-studio' ),
        ) );
    }

    /**
     * Test Webhook Endpoint via AJAX.
     */
    public function ajax_test_webhook() {
        check_ajax_referer( 'tsfs_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'techsolution-form-studio' ) ) );
        }

        $webhook_url = isset( $_POST['webhook_url'] ) ? esc_url_raw( trim( wp_unslash( $_POST['webhook_url'] ) ) ) : '';
        $secret      = isset( $_POST['webhook_secret'] ) ? sanitize_text_field( trim( wp_unslash( $_POST['webhook_secret'] ) ) ) : '';

        if ( empty( $webhook_url ) || ! filter_var( $webhook_url, FILTER_VALIDATE_URL ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter a valid Webhook URL (e.g. https://hooks.zapier.com/...)', 'techsolution-form-studio' ) ) );
        }

        $test_payload = array(
            'event'       => 'tsfs_test_ping',
            'source'      => 'TechSolution Form Studio',
            'test_time'   => current_time( 'mysql' ),
            'site_url'    => home_url(),
            'message'     => 'This is a test request from TechSolution Form Studio.',
            'sample_data' => array(
                'applicant_name' => 'Demo User',
                'email'          => 'demo@example.com',
                'status'         => 'verified',
            ),
        );

        $start_time = microtime( true );
        $res = TSFS_Security::dispatch_webhook( $webhook_url, $test_payload, $secret );
        $duration = round( ( microtime( true ) - $start_time ) * 1000 );

        if ( is_wp_error( $res ) ) {
            wp_send_json_error( array(
                /* translators: placeholders are values inserted into the message: value inserted into the message. */
                'message' => sprintf( __( 'Webhook Dispatch Failed: %s', 'techsolution-form-studio' ), $res->get_error_message() ),
            ) );
        }

        $status_code = wp_remote_retrieve_response_code( $res );
        $body        = wp_remote_retrieve_body( $res );

        if ( $status_code >= 200 && $status_code < 300 ) {
            wp_send_json_success( array(
                /* translators: placeholders are values inserted into the message: value inserted into the message. */
                'message'     => sprintf( __( 'Webhook Delivered Successfully! (HTTP %d in %dms)', 'techsolution-form-studio' ), $status_code, $duration ),
                'status_code' => $status_code,
                'latency_ms'  => $duration,
                'response'    => wp_trim_words( $body, 15 ),
            ) );
        } else {
            wp_send_json_error( array(
                /* translators: placeholders are values inserted into the message: value inserted into the message. */
                'message'     => sprintf( __( 'Webhook Endpoint responded with error HTTP %d (Latency: %dms)', 'techsolution-form-studio' ), $status_code, $duration ),
                'status_code' => $status_code,
            ) );
        }
    }

    /**
     * Stream an uploaded file to an administrator. Uploads are not publicly reachable.
     */
    public function download_file() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Permission denied.', 'techsolution-form-studio' ), 403 );
        }
        $name = isset( $_GET['file'] ) ? sanitize_file_name( basename( rawurldecode( wp_unslash( $_GET['file'] ) ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only download for administrators.
        $path = trailingslashit( TSFS_Security::upload_dir() ) . $name;
        if ( '' === $name || '.' === $name[0] || 'index.php' === $name || ! is_file( $path ) ) {
            wp_die( esc_html__( 'File not found.', 'techsolution-form-studio' ), 404 );
        }
        $type = wp_check_filetype( $name );
        nocache_headers();
        header( 'Content-Type: ' . ( $type['type'] ? $type['type'] : 'application/octet-stream' ) );
        header( 'Content-Disposition: ' . ( 0 === strpos( (string) $type['type'], 'image/' ) ? 'inline' : 'attachment' ) . '; filename="' . $name . '"' );
        header( 'Content-Length: ' . filesize( $path ) );
        header( 'X-Content-Type-Options: nosniff' );
        readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
        exit;
    }

    /**
     * Export Form Schema to JSON File.
     */
    public function export_form_json() {
        check_admin_referer( 'tsfs_export_form_json', 'tsfs_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Permission denied.', 'techsolution-form-studio' ) );
        }

        $form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;
        $form    = TSFS_DB::get_form( $form_id );

        if ( ! $form ) {
            wp_die( esc_html__( 'Form not found.', 'techsolution-form-studio' ) );
        }

        $export_payload = array(
            'tsfs_version' => TSFS_VERSION,
            'exported_at'  => current_time( 'mysql' ),
            'generator'    => 'TechSolution Form Studio',
            'title'       => $form['title'],
            'fields_data' => $form['fields_data'],
            'settings'    => $form['settings'],
        );

        $json_content = wp_json_encode( $export_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
        $filename     = 'tsfs-form-' . sanitize_title( $form['title'] ) . '-' . $form_id . '.json';

        header( 'Content-Description: File Transfer' );
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Expires: 0' );
        header( 'Cache-Control: must-revalidate' );
        header( 'Pragma: public' );
        header( 'Content-Length: ' . strlen( $json_content ) );

        echo $json_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON file download, not HTML.
        exit;
    }

    /**
     * Import Form Schema from JSON via AJAX.
     */
    public function ajax_import_form_json() {
        check_ajax_referer( 'tsfs_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'techsolution-form-studio' ) ) );
        }

        $json_raw = '';
        if ( ! empty( $_FILES['import_file']['tmp_name'] ) && is_uploaded_file( $_FILES['import_file']['tmp_name'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- temporary upload path checked with is_uploaded_file().
            $size = isset( $_FILES['import_file']['size'] ) ? absint( $_FILES['import_file']['size'] ) : 0;
            if ( $size > MB_IN_BYTES ) {
                wp_send_json_error( array( 'message' => __( 'The import file is larger than 1 MB.', 'techsolution-form-studio' ) ) );
            }
            $json_raw = file_get_contents( $_FILES['import_file']['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        } elseif ( ! empty( $_POST['import_json'] ) ) {
            // Decoded below; every value is then sanitized per key before it is stored.
            $json_raw = wp_unslash( $_POST['import_json'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        }

        if ( empty( $json_raw ) ) {
            wp_send_json_error( array( 'message' => __( 'No JSON data or file provided for import.', 'techsolution-form-studio' ) ) );
        }

        $data = json_decode( (string) $json_raw, true );
        if ( ! is_array( $data ) || ( empty( $data['fields_data'] ) && empty( $data['title'] ) ) ) {
            wp_send_json_error( array( 'message' => __( 'This file is not a valid form export. Export the form again from TechSolution Form Studio.', 'techsolution-form-studio' ) ) );
        }

        $title       = ! empty( $data['title'] ) && is_string( $data['title'] ) ? sanitize_text_field( $data['title'] ) . ' ' . __( '(Imported)', 'techsolution-form-studio' ) : __( 'Imported Form', 'techsolution-form-studio' );
        $fields_data = TSFS_Form::sanitize_fields( isset( $data['fields_data'] ) ? $data['fields_data'] : array() );
        $settings    = TSFS_Form::sanitize_settings( isset( $data['settings'] ) ? $data['settings'] : array() );

        $saved_id = TSFS_DB::save_form( array(
            'title'       => $title,
            'fields_data' => $fields_data,
            'settings'    => $settings,
            'status'      => 'publish',
        ) );

        if ( ! $saved_id ) {
            wp_send_json_error( array( 'message' => __( 'Failed to save imported form in the database.', 'techsolution-form-studio' ) ) );
        }

        wp_send_json_success( array(
            'message'      => __( 'Form imported successfully!', 'techsolution-form-studio' ),
            'form_id'      => $saved_id,
            'redirect_url' => admin_url( 'admin.php?page=tsfs-builder&id=' . $saved_id ),
        ) );
    }
}
