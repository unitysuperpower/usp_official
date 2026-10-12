<?php
/**
 * Advanced AJAX endpoints handler for USP Form Builder.
 * Handles forms saving, entry management, file uploads, conditional logic, bulk actions, and email automations.
 *
 * @package    USP_Form_Builder
 * @subpackage USP_Form_Builder/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class USP_AJAX {

    public function __construct() {
        // Admin AJAX Actions
        add_action( 'wp_ajax_usp_save_form', array( $this, 'ajax_save_form' ) );
        add_action( 'wp_ajax_usp_delete_form', array( $this, 'ajax_delete_form' ) );
        add_action( 'wp_ajax_usp_duplicate_form', array( $this, 'ajax_duplicate_form' ) );
        add_action( 'wp_ajax_usp_toggle_form_status', array( $this, 'ajax_toggle_form_status' ) );
        add_action( 'wp_ajax_usp_delete_entry', array( $this, 'ajax_delete_entry' ) );
        add_action( 'wp_ajax_usp_get_entry_details', array( $this, 'ajax_get_entry_details' ) );
        add_action( 'wp_ajax_usp_toggle_entry_status', array( $this, 'ajax_toggle_entry_status' ) );
        add_action( 'wp_ajax_usp_save_entry_note', array( $this, 'ajax_save_entry_note' ) );
        add_action( 'wp_ajax_usp_bulk_entries', array( $this, 'ajax_bulk_entries' ) );
        add_action( 'wp_ajax_usp_get_form_headers', array( $this, 'ajax_get_form_headers' ) );
        add_action( 'admin_post_usp_export_entries', array( $this, 'export_entries_csv' ) );
        add_action( 'wp_ajax_usp_save_security_settings', array( $this, 'ajax_save_security_settings' ) );
        add_action( 'wp_ajax_usp_verify_license', array( $this, 'ajax_verify_license' ) );
        add_action( 'wp_ajax_usp_save_store_product', array( $this, 'ajax_save_store_product' ) );
        add_action( 'wp_ajax_usp_redeploy_shields', array( $this, 'ajax_redeploy_shields' ) );
        add_action( 'wp_ajax_usp_test_webhook', array( $this, 'ajax_test_webhook' ) );
        add_action( 'admin_post_usp_export_form_json', array( $this, 'export_form_json' ) );
        add_action( 'wp_ajax_usp_import_form_json', array( $this, 'ajax_import_form_json' ) );

        // Public Submission & Analytics AJAX Actions
        add_action( 'wp_ajax_usp_submit_form', array( $this, 'ajax_submit_form' ) );
        add_action( 'wp_ajax_nopriv_usp_submit_form', array( $this, 'ajax_submit_form' ) );
        add_action( 'wp_ajax_usp_track_view', array( $this, 'ajax_track_view' ) );
        add_action( 'wp_ajax_nopriv_usp_track_view', array( $this, 'ajax_track_view' ) );
    }

    /**
     * Save or update form via builder.
     */
    public function ajax_save_form() {
        check_ajax_referer( 'usp_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'usp-form-builder' ) ) );
        }

        $form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
        $title   = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : __( 'Untitled Form', 'usp-form-builder' );
        
        $raw_fields   = isset( $_POST['fields_data'] ) ? wp_unslash( $_POST['fields_data'] ) : '[]';
        $raw_settings = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : '{}';

        $fields_data = json_decode( $raw_fields, true );
        $settings    = json_decode( $raw_settings, true );

        if ( ! is_array( $fields_data ) ) {
            $fields_data = array();
        }
        if ( ! is_array( $settings ) ) {
            $settings = array();
        }

        // Sanitize field configurations
        $sanitized_fields = array();
        foreach ( $fields_data as $f ) {
            $type = isset( $f['type'] ) ? sanitize_key( $f['type'] ) : 'text';
            if ( USP_Form::is_pro_field_type( $type ) && ! USP_Security::is_pro_active() ) {
                $supported_types = USP_Form::get_supported_field_types();
                $type_label      = isset( $supported_types[ $type ]['label'] ) ? $supported_types[ $type ]['label'] : $type;
                wp_send_json_error( array(
                    'message' => sprintf(
                        /* translators: %s: field type */
                        __( 'A Pro license is required to add the %s field.', 'usp-form-builder' ),
                        $type_label
                    ),
                ) );
            }

            $field_item = array(
                'id'          => isset( $f['id'] ) ? sanitize_text_field( $f['id'] ) : 'field_' . uniqid(),
                'type'        => $type,
                'label'       => isset( $f['label'] ) ? sanitize_text_field( $f['label'] ) : '',
                'name'        => isset( $f['name'] ) ? sanitize_key( $f['name'] ) : 'field_' . uniqid(),
                'placeholder' => isset( $f['placeholder'] ) ? sanitize_text_field( $f['placeholder'] ) : '',
                'required'    => ! empty( $f['required'] ),
                'help_text'   => isset( $f['help_text'] ) ? sanitize_text_field( $f['help_text'] ) : '',
                'width'       => isset( $f['width'] ) ? sanitize_text_field( $f['width'] ) : '100',
            );

            if ( 'address' === $type ) {
                $field_item['address_show_street'] = ! isset( $f['address_show_street'] ) || ! empty( $f['address_show_street'] );
                $field_item['address_show_postal'] = ! isset( $f['address_show_postal'] ) || ! empty( $f['address_show_postal'] );
            }

            // Conditional Logic rules
            if ( ! empty( $f['enable_conditions'] ) && ! empty( $f['conditions'] ) && is_array( $f['conditions'] ) ) {
                $field_item['enable_conditions'] = true;
                $field_item['condition_action']  = isset( $f['condition_action'] ) ? sanitize_key( $f['condition_action'] ) : 'show';
                $field_item['condition_match']   = isset( $f['condition_match'] ) ? sanitize_key( $f['condition_match'] ) : 'all';
                $field_item['conditions']        = array();
                foreach ( $f['conditions'] as $cond ) {
                    $field_item['conditions'][] = array(
                        'field'    => isset( $cond['field'] ) ? sanitize_key( $cond['field'] ) : '',
                        'operator' => isset( $cond['operator'] ) ? sanitize_text_field( $cond['operator'] ) : '==',
                        'value'    => isset( $cond['value'] ) ? sanitize_text_field( $cond['value'] ) : '',
                    );
                }
            } else {
                $field_item['enable_conditions'] = false;
            }

            if ( in_array( $type, array( 'select', 'radio', 'checkbox' ), true ) && ! empty( $f['options'] ) ) {
                $field_item['options'] = array_map( 'sanitize_text_field', (array) $f['options'] );
            }

            if ( 'textarea' === $type && isset( $f['rows'] ) ) {
                $field_item['rows'] = absint( $f['rows'] );
            }

            if ( 'number' === $type || 'range' === $type ) {
                $field_item['min']         = isset( $f['min'] ) ? sanitize_text_field( $f['min'] ) : '';
                $field_item['max']         = isset( $f['max'] ) ? sanitize_text_field( $f['max'] ) : '';
                $field_item['step']        = isset( $f['step'] ) ? sanitize_text_field( $f['step'] ) : '';
                $field_item['default_val'] = isset( $f['default_val'] ) ? sanitize_text_field( $f['default_val'] ) : '';
            }

            if ( 'file' === $type ) {
                $field_item['allowed_ext'] = isset( $f['allowed_ext'] ) ? sanitize_text_field( $f['allowed_ext'] ) : 'pdf,doc,docx,jpg,jpeg,png';
                $field_item['max_size']    = isset( $f['max_size'] ) ? sanitize_text_field( $f['max_size'] ) : '5';
            }

            if ( 'consent' === $type ) {
                $field_item['consent_text'] = isset( $f['consent_text'] ) ? sanitize_textarea_field( $f['consent_text'] ) : '';
                $field_item['terms_text']   = $field_item['consent_text'];
                $field_item['terms_url']    = isset( $f['terms_url'] ) ? esc_url_raw( $f['terms_url'] ) : '';
                $field_item['terms_label']  = isset( $f['terms_label'] ) ? sanitize_text_field( $f['terms_label'] ) : __( 'Terms & Conditions', 'usp-form-builder' );
            }

            if ( 'hidden' === $type ) {
                $field_item['default_val'] = isset( $f['default_val'] ) ? sanitize_text_field( $f['default_val'] ) : '';
            }

            if ( 'html' === $type ) {
                $field_item['content'] = isset( $f['content'] ) ? wp_kses_post( $f['content'] ) : '';
                $field_item['tag']     = isset( $f['tag'] ) ? sanitize_key( $f['tag'] ) : 'h3';
            }

            if ( 'payment' === $type ) {
                $field_item['payment_amount']   = isset( $f['payment_amount'] ) ? sanitize_text_field( $f['payment_amount'] ) : '50.00';
                $field_item['payment_currency'] = isset( $f['payment_currency'] ) ? sanitize_text_field( $f['payment_currency'] ) : 'USD';
            }

            if ( 'page_break' === $type ) {
                $field_item['step_title']    = isset( $f['step_title'] ) ? sanitize_text_field( $f['step_title'] ) : '';
                $field_item['btn_prev_text'] = isset( $f['btn_prev_text'] ) ? sanitize_text_field( $f['btn_prev_text'] ) : __( 'Previous', 'usp-form-builder' );
                $field_item['btn_next_text'] = isset( $f['btn_next_text'] ) ? sanitize_text_field( $f['btn_next_text'] ) : __( 'Next Step', 'usp-form-builder' );
            }

            if ( 'signature' === $type ) {
                $field_item['canvas_height'] = isset( $f['canvas_height'] ) ? absint( $f['canvas_height'] ) : 160;
                $field_item['pen_color']     = isset( $f['pen_color'] ) ? sanitize_hex_color( $f['pen_color'] ) : '#0f172a';
            }

            if ( 'calculation' === $type ) {
                $field_item['formula']     = isset( $f['formula'] ) ? sanitize_text_field( $f['formula'] ) : '';
                $field_item['calc_prefix'] = isset( $f['calc_prefix'] ) ? sanitize_text_field( $f['calc_prefix'] ) : '$';
                $field_item['calc_suffix'] = isset( $f['calc_suffix'] ) ? sanitize_text_field( $f['calc_suffix'] ) : '';
                $field_item['decimals']    = isset( $f['decimals'] ) ? absint( $f['decimals'] ) : 2;
            }

            $sanitized_fields[] = $field_item;
        }

        $sanitized_settings = array(
            'submit_button_text'    => isset( $settings['submit_button_text'] ) ? sanitize_text_field( $settings['submit_button_text'] ) : __( 'Submit', 'usp-form-builder' ),
            'success_message'       => isset( $settings['success_message'] ) ? sanitize_textarea_field( $settings['success_message'] ) : __( 'Thank you! Your submission has been received.', 'usp-form-builder' ),
            'redirect_url'          => isset( $settings['redirect_url'] ) ? esc_url_raw( $settings['redirect_url'] ) : '',
            'send_email'            => ! empty( $settings['send_email'] ),
            'notification_email'    => isset( $settings['notification_email'] ) ? sanitize_email( $settings['notification_email'] ) : get_option( 'admin_email' ),
            'email_subject'         => isset( $settings['email_subject'] ) ? sanitize_text_field( $settings['email_subject'] ) : '',
            'send_user_auto_reply'  => ! empty( $settings['send_user_auto_reply'] ),
            'auto_reply_subject'    => isset( $settings['auto_reply_subject'] ) ? sanitize_text_field( $settings['auto_reply_subject'] ) : __( 'Thank you for contacting us!', 'usp-form-builder' ),
            'auto_reply_message'    => isset( $settings['auto_reply_message'] ) ? sanitize_textarea_field( $settings['auto_reply_message'] ) : __( "Hello,\n\nWe have received your submission and will get back to you shortly.\n\nBest regards,\nSupport Team", 'usp-form-builder' ),

            // Theme & Style Options
            'theme_preset'          => isset( $settings['theme_preset'] ) ? sanitize_key( $settings['theme_preset'] ) : 'default',
            'primary_color'         => isset( $settings['primary_color'] ) ? sanitize_hex_color( $settings['primary_color'] ) : '#4f46e5',
            'btn_text_color'        => isset( $settings['btn_text_color'] ) ? sanitize_hex_color( $settings['btn_text_color'] ) : '#ffffff',
            'container_bg'          => isset( $settings['container_bg'] ) ? sanitize_text_field( $settings['container_bg'] ) : '#ffffff',
            'border_radius'         => isset( $settings['border_radius'] ) ? sanitize_key( $settings['border_radius'] ) : 'rounded',
            'button_align'          => isset( $settings['button_align'] ) ? sanitize_key( $settings['button_align'] ) : 'left',
            'shadow_style'          => isset( $settings['shadow_style'] ) ? sanitize_key( $settings['shadow_style'] ) : 'medium',
            'custom_css'            => isset( $settings['custom_css'] ) ? wp_strip_all_tags( $settings['custom_css'] ) : '',

            // Scheduling & Submission Restrictions
            'limit_submissions'     => ! empty( $settings['limit_submissions'] ),
            'max_entries'           => isset( $settings['max_entries'] ) ? absint( $settings['max_entries'] ) : 0,
            'close_after_date'      => ! empty( $settings['close_after_date'] ),
            'close_date'            => isset( $settings['close_date'] ) ? sanitize_text_field( $settings['close_date'] ) : '',
            'closed_message'        => isset( $settings['closed_message'] ) ? sanitize_textarea_field( $settings['closed_message'] ) : __( 'This form is currently closed for submissions.', 'usp-form-builder' ),
            'require_login'         => ! empty( $settings['require_login'] ),
            'login_message'         => isset( $settings['login_message'] ) ? sanitize_textarea_field( $settings['login_message'] ) : __( 'You must be logged in to submit this form.', 'usp-form-builder' ),

            // Webhooks & API Integrations
            'enable_webhook'        => ! empty( $settings['enable_webhook'] ),
            'webhook_url'           => isset( $settings['webhook_url'] ) ? esc_url_raw( trim( $settings['webhook_url'] ) ) : '',
            'webhook_secret'        => isset( $settings['webhook_secret'] ) ? sanitize_text_field( trim( $settings['webhook_secret'] ) ) : '',
        );

        $saved_id = USP_DB::save_form(
            array(
                'title'       => $title,
                'fields_data' => $sanitized_fields,
                'settings'    => $sanitized_settings,
                'status'      => 'publish',
            ),
            $form_id
        );

        if ( ! $saved_id ) {
            wp_send_json_error( array( 'message' => __( 'Failed to save form. Please try again.', 'usp-form-builder' ) ) );
        }

        $redirect_url = admin_url( 'admin.php?page=usp-form-builder-new&id=' . $saved_id );

        wp_send_json_success( array(
            'form_id'      => $saved_id,
            'redirect_url' => $redirect_url,
            'message'      => __( 'Form saved successfully!', 'usp-form-builder' ),
        ) );
    }

    /**
     * Delete form via AJAX.
     */
    public function ajax_delete_form() {
        check_ajax_referer( 'usp_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'usp-form-builder' ) ) );
        }

        $form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
        if ( ! $form_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid form ID.', 'usp-form-builder' ) ) );
        }

        $deleted = USP_DB::delete_form( $form_id );
        if ( $deleted ) {
            wp_send_json_success( array( 'message' => __( 'Form deleted successfully.', 'usp-form-builder' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Could not delete form.', 'usp-form-builder' ) ) );
        }
    }

    /**
     * Duplicate form via AJAX.
     */
    public function ajax_duplicate_form() {
        check_ajax_referer( 'usp_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'usp-form-builder' ) ) );
        }

        $form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
        $new_id  = USP_DB::duplicate_form( $form_id );

        if ( $new_id ) {
            wp_send_json_success( array(
                'message'  => __( 'Form duplicated successfully.', 'usp-form-builder' ),
                'new_id'   => $new_id,
                'edit_url' => admin_url( 'admin.php?page=usp-form-builder-new&id=' . $new_id ),
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Could not duplicate form.', 'usp-form-builder' ) ) );
        }
    }

    /**
     * Toggle form publish status (publish, draft, closed) via AJAX.
     */
    public function ajax_toggle_form_status() {
        check_ajax_referer( 'usp_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'usp-form-builder' ) ) );
        }

        $form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
        $status  = isset( $_POST['status'] ) ? sanitize_key( $_POST['status'] ) : 'publish';

        if ( ! $form_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid form ID.', 'usp-form-builder' ) ) );
        }

        $updated = USP_DB::toggle_form_status( $form_id, $status );
        if ( false !== $updated ) {
            wp_send_json_success( array(
                'status'  => $status,
                'message' => sprintf( __( 'Form status updated to %s.', 'usp-form-builder' ), ucfirst( $status ) ),
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to update form status.', 'usp-form-builder' ) ) );
        }
    }

    /**
     * Delete submission entry via AJAX.
     */
    public function ajax_delete_entry() {
        check_ajax_referer( 'usp_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'usp-form-builder' ) ) );
        }

        $entry_id = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
        $deleted  = USP_DB::delete_entry( $entry_id );

        if ( $deleted ) {
            wp_send_json_success( array( 'message' => __( 'Entry deleted successfully.', 'usp-form-builder' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Could not delete entry.', 'usp-form-builder' ) ) );
        }
    }

    /**
     * Get entry details for modal viewer.
     */
    public function ajax_get_entry_details() {
        check_ajax_referer( 'usp_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'usp-form-builder' ) ) );
        }

        $entry_id = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
        $entry    = USP_DB::get_entry( $entry_id );

        if ( ! $entry ) {
            wp_send_json_error( array( 'message' => __( 'Entry not found.', 'usp-form-builder' ) ) );
        }

        // Auto mark as read upon viewing if currently unread
        if ( 'unread' === $entry['status'] ) {
            USP_DB::update_entry_status( $entry_id, 'read' );
            $entry['status'] = 'read';
        }

        wp_send_json_success( array( 'entry' => $entry ) );
    }

    /**
     * Toggle submission status (read / unread / starred).
     */
    public function ajax_toggle_entry_status() {
        check_ajax_referer( 'usp_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'usp-form-builder' ) ) );
        }

        $entry_id = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
        $status   = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : 'read';

        if ( ! $entry_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid entry ID.', 'usp-form-builder' ) ) );
        }

        $updated = USP_DB::update_entry_status( $entry_id, $status );
        if ( false !== $updated ) {
            wp_send_json_success( array(
                'status'  => $status,
                'message' => __( 'Entry status updated.', 'usp-form-builder' ),
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to update entry status.', 'usp-form-builder' ) ) );
        }
    }

    /**
     * Save internal admin notes for a submission.
     */
    public function ajax_save_entry_note() {
        check_ajax_referer( 'usp_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'usp-form-builder' ) ) );
        }

        $entry_id = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
        $notes    = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';

        if ( ! $entry_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid entry ID.', 'usp-form-builder' ) ) );
        }

        USP_DB::maybe_upgrade_schema();
        $updated = USP_DB::update_entry_notes( $entry_id, $notes );

        if ( false !== $updated ) {
            wp_send_json_success( array(
                'message' => __( 'Note saved successfully.', 'usp-form-builder' ),
                'notes'   => $notes,
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to save note.', 'usp-form-builder' ) ) );
        }
    }

    /**
     * Bulk action handler for submissions (read, unread, delete, star).
     */
    public function ajax_bulk_entries() {
        check_ajax_referer( 'usp_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'usp-form-builder' ) ) );
        }

        $bulk_action = isset( $_POST['bulk_action'] ) ? sanitize_key( $_POST['bulk_action'] ) : '';
        $entry_ids   = isset( $_POST['entry_ids'] ) ? array_map( 'absint', (array) $_POST['entry_ids'] ) : array();

        if ( empty( $entry_ids ) ) {
            wp_send_json_error( array( 'message' => __( 'No entries selected.', 'usp-form-builder' ) ) );
        }

        switch ( $bulk_action ) {
            case 'mark_read':
                USP_DB::bulk_update_status( $entry_ids, 'read' );
                wp_send_json_success( array( 'message' => __( 'Entries marked as read.', 'usp-form-builder' ) ) );
                break;

            case 'mark_unread':
                USP_DB::bulk_update_status( $entry_ids, 'unread' );
                wp_send_json_success( array( 'message' => __( 'Entries marked as unread.', 'usp-form-builder' ) ) );
                break;

            case 'star':
                USP_DB::bulk_update_status( $entry_ids, 'starred' );
                wp_send_json_success( array( 'message' => __( 'Entries starred.', 'usp-form-builder' ) ) );
                break;

            case 'delete':
                USP_DB::bulk_delete_entries( $entry_ids );
                wp_send_json_success( array( 'message' => __( 'Entries permanently deleted.', 'usp-form-builder' ) ) );
                break;

            default:
                wp_send_json_error( array( 'message' => __( 'Invalid bulk action.', 'usp-form-builder' ) ) );
                break;
        }
    }

    /**
     * AJAX: Get form submission headers (fields + system columns) for header selection in export modal.
     */
    public function ajax_get_form_headers() {
        check_ajax_referer( 'usp_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'usp-form-builder' ) ) );
        }

        $form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
        $headers = array();

        // System headers always available
        $system_headers = array(
            array( 'key' => 'id',         'label' => __( 'Entry ID', 'usp-form-builder' ),       'is_system' => true, 'default' => true ),
            array( 'key' => 'form_title', 'label' => __( 'Form Title', 'usp-form-builder' ),     'is_system' => true, 'default' => ( $form_id === 0 ) ),
            array( 'key' => 'status',     'label' => __( 'Status', 'usp-form-builder' ),         'is_system' => true, 'default' => true ),
            array( 'key' => 'created_at', 'label' => __( 'Submitted At', 'usp-form-builder' ),   'is_system' => true, 'default' => true ),
            array( 'key' => 'user_ip',    'label' => __( 'Submitter IP', 'usp-form-builder' ),   'is_system' => true, 'default' => true ),
        );

        $field_headers = array();
        $seen_keys     = array();

        if ( $form_id > 0 ) {
            $form = USP_DB::get_form( $form_id );
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
            $recent_entries = USP_DB::get_entries( array( 'form_id' => $form_id, 'limit' => 50 ) );
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
        $system_headers[] = array( 'key' => 'notes', 'label' => __( 'Admin Notes', 'usp-form-builder' ), 'is_system' => true, 'default' => false );

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
            wp_die( esc_html__( 'Permission denied.', 'usp-form-builder' ) );
        }

        check_admin_referer( 'usp_export_entries', 'usp_nonce' );

        $form_id   = isset( $_REQUEST['form_id'] ) ? absint( $_REQUEST['form_id'] ) : 0;
        $status    = isset( $_REQUEST['status'] ) ? sanitize_text_field( $_REQUEST['status'] ) : '';
        $date_from = isset( $_REQUEST['date_from'] ) ? sanitize_text_field( $_REQUEST['date_from'] ) : '';
        $date_to   = isset( $_REQUEST['date_to'] ) ? sanitize_text_field( $_REQUEST['date_to'] ) : '';
        $search    = isset( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : '';
        $format    = isset( $_REQUEST['export_format'] ) ? sanitize_key( $_REQUEST['export_format'] ) : 'csv';

        // Specific selected entries (from checkbox selection) or all matching filters
        $entry_ids_raw = isset( $_REQUEST['entry_ids'] ) ? sanitize_text_field( $_REQUEST['entry_ids'] ) : '';
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

        $entries = USP_DB::get_entries( $filters );

        // If specific entry IDs were selected, narrow down to only those
        if ( ! empty( $entry_ids ) ) {
            $id_lookup = array_flip( $entry_ids );
            $entries = array_filter( $entries, function( $item ) use ( $id_lookup ) {
                return isset( $id_lookup[ (int) $item['id'] ] );
            } );
        }

        // Form Title Meta
        $form_title_meta = __( 'All Forms', 'usp-form-builder' );
        if ( $form_id > 0 ) {
            $form_obj = USP_DB::get_form( $form_id );
            if ( $form_obj && ! empty( $form_obj['title'] ) ) {
                $form_title_meta = $form_obj['title'];
            }
        }

        // Date range display text
        $date_range_meta = __( 'All Time', 'usp-form-builder' );
        if ( ! empty( $date_from ) && ! empty( $date_to ) ) {
            $date_range_meta = $date_from . ' to ' . $date_to;
        } elseif ( ! empty( $date_from ) ) {
            $date_range_meta = 'From ' . $date_from;
        } elseif ( ! empty( $date_to ) ) {
            $date_range_meta = 'Up to ' . $date_to;
        }

        // Selected Headers parsing
        $requested_headers = isset( $_REQUEST['export_headers'] ) ? (array) $_REQUEST['export_headers'] : array();
        $selected_header_keys = array();
        foreach ( $requested_headers as $h_key ) {
            $k = sanitize_text_field( $h_key );
            if ( '' !== $k ) {
                $selected_header_keys[] = $k;
            }
        }

        // Map of known field keys to their display labels
        $field_label_map = array(
            'id'         => __( 'Entry ID', 'usp-form-builder' ),
            'form_title' => __( 'Form Title', 'usp-form-builder' ),
            'status'     => __( 'Status', 'usp-form-builder' ),
            'created_at' => __( 'Submitted At', 'usp-form-builder' ),
            'user_ip'    => __( 'Submitter IP', 'usp-form-builder' ),
            'notes'      => __( 'Admin Notes', 'usp-form-builder' ),
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
            $filename = 'usp-' . $safe_slug . '-' . $timestamp . '.xls';
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
    <Cell ss:StyleID="SubMeta"><Data ss:Type="String">Date Range: <?php echo esc_html( $date_range_meta ); ?> | Exported: <?php echo esc_html( current_time( 'Y-m-d H:i:s' ) ); ?> | Total Records: <?php echo count( $entries ); ?></Data></Cell>
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
            $safe_val = USP_Security::sanitize_csv_cell( $cell_val );
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
        $filename = 'usp-' . $safe_slug . '-' . $timestamp . '.csv';

        header( 'Content-Type: text/csv; charset=UTF-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        $output = fopen( 'php://output', 'w' );
        fputs( $output, "\xEF\xBB\xBF" ); // UTF-8 BOM for Microsoft Excel

        // Form Top Meta Headers
        fputcsv( $output, array( '# USP FORM BUILDER - SUBMISSIONS EXPORT' ) );
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
            $safe_row = array_map( array( 'USP_Security', 'sanitize_csv_cell' ), $raw_row );
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
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm 10mm;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #1e293b;
            background: #fff;
            margin: 0;
            padding: 24px;
            font-size: 12px;
            line-height: 1.4;
        }
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .report-title {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 6px 0;
        }
        .report-meta-grid {
            display: flex;
            gap: 20px;
            font-size: 11.5px;
            color: #64748b;
        }
        .report-meta-item strong {
            color: #334155;
        }
        .print-actions {
            margin-bottom: 16px;
            display: flex;
            gap: 10px;
        }
        .btn-print {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-close {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        th {
            background: #f8fafc;
            color: #475569;
            text-align: left;
            padding: 9px 10px;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
            border-top: 1px solid #cbd5e1;
            border-bottom: 2px solid #cbd5e1;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
            word-break: break-word;
        }
        tr:nth-child(even) td {
            background-color: #fbfcfe;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-read { background: #dcfce7; color: #15803d; }
        .badge-unread { background: #dbeafe; color: #1d4ed8; }
        .badge-starred { background: #fef3c7; color: #b45309; }
        .report-footer {
            margin-top: 24px;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
        @media print {
            .print-actions {
                display: none !important;
            }
            body {
                padding: 0 !important;
            }
            table {
                page-break-inside: auto;
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            thead {
                display: table-header-group;
            }
        }
    </style>
</head>
<body>
    <div class="print-actions">
        <button type="button" class="btn-print" onclick="window.print();">&#128438; Print / Save as PDF</button>
        <button type="button" class="btn-close" onclick="window.close();">Close Window</button>
    </div>

    <div class="report-header">
        <div>
            <h1 class="report-title"><?php echo esc_html( $form_title_meta ); ?></h1>
            <div class="report-meta-grid">
                <div class="report-meta-item"><strong>Date Range:</strong> <?php echo esc_html( $date_range_meta ); ?></div>
                <div class="report-meta-item"><strong>Total Submissions:</strong> <?php echo count( $entries ); ?></div>
                <div class="report-meta-item"><strong>Exported:</strong> <?php echo esc_html( current_time( 'M j, Y g:i A' ) ); ?></div>
            </div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:14px; font-weight:800; color:#2563eb;">USP Form Builder</div>
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
                    <td colspan="<?php echo count( $selected_header_keys ); ?>" style="text-align:center; padding:30px;">
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
        <div>Generated by USP Form Builder — Confidential Submissions Log</div>
        <div>Page 1 of 1</div>
    </div>

    <script>
        // Automatically open the print dialog when loaded if requested
        window.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                window.print();
            }, 300);
        });
    </script>
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
            USP_DB::increment_views( $form_id );
            wp_send_json_success( array( 'tracked' => true, 'form_id' => $form_id ) );
        }
        wp_send_json_error( array( 'tracked' => false ) );
    }

    /**
     * Advanced Public Form Submission Handler.
     * Supports file uploads, conditional logic skipping, merge tags in emails, and auto-responders.
     */
    public function ajax_submit_form() {
        // 1. Anti-spam Honeypot check
        if ( ! empty( $_POST['usp_hp_field'] ) ) {
            wp_send_json_success( array(
                'message'      => __( 'Thank you! Your submission has been received.', 'usp-form-builder' ),
                'redirect_url' => '',
            ) );
        }

        $form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
        if ( ! $form_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid form ID.', 'usp-form-builder' ) ) );
        }

        // 2. Client IP & Security Checks (Blacklist & Rate Limiting)
        $user_ip = ! empty( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

        if ( USP_Security::is_ip_blacklisted( $user_ip ) ) {
            wp_send_json_error( array( 'message' => __( 'Access denied. Your IP address is not permitted to submit forms.', 'usp-form-builder' ) ) );
        }

        $rate_check = USP_Security::check_rate_limit( $user_ip, $form_id );
        if ( is_wp_error( $rate_check ) ) {
            wp_send_json_error( array( 'message' => $rate_check->get_error_message() ) );
        }

        // 3. Time-Gate Bot Trap (Submissions completing faster than natural human speed)
        $time_token = isset( $_POST['usp_time_token'] ) ? sanitize_text_field( wp_unslash( $_POST['usp_time_token'] ) ) : '';
        $time_check = USP_Security::verify_time_token( $time_token, $form_id );
        if ( is_wp_error( $time_check ) ) {
            wp_send_json_error( array( 'message' => $time_check->get_error_message() ) );
        }

        // 4. Anti-Spam CAPTCHA verification (Google reCAPTCHA v2/v3 / Cloudflare Turnstile)
        $captcha_token = isset( $_POST['g-recaptcha-response'] ) 
            ? sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) ) 
            : ( isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : '' );
        $captcha_check = USP_Security::verify_captcha( $captcha_token, $user_ip );
        if ( is_wp_error( $captcha_check ) ) {
            wp_send_json_error( array( 'message' => $captcha_check->get_error_message() ) );
        }

        // 5. Nonce Verification
        $nonce = isset( $_POST['usp_nonce'] ) ? sanitize_text_field( $_POST['usp_nonce'] ) : '';
        if ( ! wp_verify_nonce( $nonce, 'usp_form_submit_' . $form_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Security verification failed. Please refresh the page and try again.', 'usp-form-builder' ) ) );
        }

        // 6. Load Form definition
        $form = USP_DB::get_form( $form_id );
        if ( ! $form || 'publish' !== $form['status'] ) {
            wp_send_json_error( array( 'message' => __( 'Form is no longer active.', 'usp-form-builder' ) ) );
        }

        $fields   = ! empty( $form['fields_data'] ) ? $form['fields_data'] : array();
        $settings = ! empty( $form['settings'] ) ? $form['settings'] : array();

        if ( ! USP_Security::is_pro_active() ) {
            foreach ( $fields as $field ) {
                $field_type = isset( $field['type'] ) ? $field['type'] : '';
                if ( USP_Form::is_pro_field_type( $field_type ) ) {
                    wp_send_json_error( array(
                        'message' => __( 'This form includes Pro fields and cannot accept submissions while its Pro license is inactive.', 'usp-form-builder' ),
                    ) );
                }
            }
        }

        // Check Login restriction
        if ( ! empty( $settings['require_login'] ) && ! is_user_logged_in() ) {
            $login_msg = ! empty( $settings['login_message'] ) ? $settings['login_message'] : __( 'You must be logged in to submit this form.', 'usp-form-builder' );
            wp_send_json_error( array( 'message' => $login_msg ) );
        }

        // Check Expiry Date
        if ( ! empty( $settings['close_after_date'] ) && ! empty( $settings['close_date'] ) ) {
            if ( current_time( 'timestamp' ) > strtotime( $settings['close_date'] . ' 23:59:59' ) ) {
                $closed_msg = ! empty( $settings['closed_message'] ) ? $settings['closed_message'] : __( 'This form is currently closed for submissions.', 'usp-form-builder' );
                wp_send_json_error( array( 'message' => $closed_msg ) );
            }
        }

        // Check Max Submissions Limit
        if ( ! empty( $settings['limit_submissions'] ) && ! empty( $settings['max_entries'] ) ) {
            $entry_counts = USP_DB::get_status_counts( $form_id );
            if ( (int) $entry_counts['all'] >= (int) $settings['max_entries'] ) {
                $closed_msg = ! empty( $settings['closed_message'] ) ? $settings['closed_message'] : __( 'This form has reached its maximum submission limit.', 'usp-form-builder' );
                wp_send_json_error( array( 'message' => $closed_msg ) );
            }
        }

        $errors          = array();
        $submitted_entry = array();
        $user_email      = '';
        $user_name       = '';

        // 4. Validate & Process each field
        foreach ( $fields as $field ) {
            $type     = isset( $field['type'] ) ? $field['type'] : 'text';
            $name     = ! empty( $field['name'] ) ? sanitize_key( $field['name'] ) : '';
            $label    = isset( $field['label'] ) ? $field['label'] : $name;
            $required = ! empty( $field['required'] );

            if ( 'html' === $type || 'page_break' === $type || empty( $name ) ) {
                continue;
            }

            if ( USP_Form::is_pro_field_type( $type ) && ! USP_Security::is_pro_active() ) {
                $errors[ $name ] = __( 'This field requires an active USP Form Builder Pro license.', 'usp-form-builder' );
                continue;
            }

            if ( 'address' === $type ) {
                if ( ! USP_Security::is_pro_active() ) {
                    $errors[ $name ] = __( 'This address field is currently unavailable.', 'usp-form-builder' );
                    continue;
                }
                $raw_address = isset( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : null;
                $address = USP_Address::validate( $raw_address, $required, $field );
                if ( is_wp_error( $address ) ) { $errors[ $name ] = $address->get_error_message(); }
                else { $submitted_entry[] = array( 'name' => $name, 'label' => $label, 'type' => $type, 'value' => $address ); }
                continue;
            }

            // Handle File Upload with Hardened Sandbox
            if ( 'file' === $type ) {
                if ( ! empty( $_FILES[ $name ]['name'] ) ) {
                    $allowed = ! empty( $field['allowed_ext'] ) ? explode( ',', str_replace( ' ', '', strtolower( $field['allowed_ext'] ) ) ) : array( 'pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png' );
                    $max_mb  = ! empty( $field['max_size'] ) ? (float) $field['max_size'] : 5;

                    // Hardened security validation: checks dangerous extensions and MIME sniffing
                    $security_file_check = USP_Security::validate_file_upload( $_FILES[ $name ], $allowed, $max_mb );
                    if ( is_wp_error( $security_file_check ) ) {
                        $errors[ $name ] = $security_file_check->get_error_message();
                        continue;
                    }

                    require_once ABSPATH . 'wp-admin/includes/file.php';
                    $file_name = sanitize_file_name( $_FILES[ $name ]['name'] );
                    $protection_result = USP_Security::protect_upload_directory();
                    if ( is_wp_error( $protection_result ) ) {
                        $errors[ $name ] = $protection_result->get_error_message();
                        continue;
                    }

                    $uploads = wp_upload_dir();
                    if ( ! empty( $uploads['error'] ) ) {
                        $errors[ $name ] = $uploads['error'];
                        continue;
                    }
                    $sandbox_path = trailingslashit( $uploads['basedir'] ) . 'usp-form-builder';
                    $sandbox_url  = trailingslashit( $uploads['baseurl'] ) . 'usp-form-builder';
                    $upload_dir_filter = function( $dirs ) use ( $sandbox_path, $sandbox_url ) {
                        $dirs['path']    = $sandbox_path;
                        $dirs['url']     = $sandbox_url;
                        $dirs['subdir']  = '';
                        $dirs['basedir'] = dirname( $sandbox_path );
                        $dirs['baseurl'] = dirname( $sandbox_url );
                        return $dirs;
                    };
                    add_filter( 'upload_dir', $upload_dir_filter, 99 );
                    $sandbox_uploads = wp_upload_dir( null, true, true );
                    if ( ! empty( $sandbox_uploads['error'] ) || $sandbox_path !== $sandbox_uploads['path'] ) {
                        remove_filter( 'upload_dir', $upload_dir_filter, 99 );
                        wp_upload_dir( null, true, true );
                        $errors[ $name ] = ! empty( $sandbox_uploads['error'] ) ? $sandbox_uploads['error'] : __( 'The upload could not be routed to the protected sandbox.', 'usp-form-builder' );
                        continue;
                    }

                    $upload    = wp_handle_upload( $_FILES[ $name ], array( 'test_form' => false ) );
                    remove_filter( 'upload_dir', $upload_dir_filter, 99 );
                    wp_upload_dir( null, true, true );
                    if ( isset( $upload['error'] ) ) {
                        $errors[ $name ] = $upload['error'];
                        continue;
                    }

                    $clean_val = array(
                        'url'  => esc_url_raw( $upload['url'] ),
                        'file' => $file_name,
                    );
                } elseif ( $required ) {
                    $errors[ $name ] = sprintf( __( '%s is required.', 'usp-form-builder' ), $label );
                    continue;
                } else {
                    $clean_val = '';
                }

                $submitted_entry[] = array(
                    'name'  => $name,
                    'label' => $label,
                    'type'  => $type,
                    'value' => $clean_val,
                );
                continue;
            }

            // Handle Pro: University Admission Package
            if ( 'admission' === $type ) {
                $raw_adm = isset( $_POST[ $name ] ) && is_array( $_POST[ $name ] ) ? $_POST[ $name ] : array();
                $adm_clean = array();
                foreach ( $raw_adm as $ak => $av ) {
                    $adm_clean[ sanitize_key( $ak ) ] = sanitize_text_field( wp_unslash( $av ) );
                }

                if ( $required && empty( $adm_clean['student_name'] ) ) {
                    $errors[ $name ] = sprintf( __( '%s (Applicant Full Name) is required.', 'usp-form-builder' ), $label );
                    continue;
                }

                if ( empty( $user_name ) && ! empty( $adm_clean['student_name'] ) ) {
                    $user_name = $adm_clean['student_name'];
                }

                $submitted_entry[] = array(
                    'name'  => $name,
                    'label' => $label,
                    'type'  => $type,
                    'value' => $adm_clean,
                );
                continue;
            }

            // Handle Pro: International Phone Number with Country Code & National Digit Limit Enforcement
            if ( 'intl_tel' === $type ) {
                $dial_code    = isset( $_POST[ $name . '_dial_code' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $name . '_dial_code' ] ) ) : '+92';
                $country_code = isset( $_POST[ $name . '_country' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $name . '_country' ] ) ) : '';
                $phone_num    = isset( $_POST[ $name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $name ] ) ) : '';

                $raw_digits = preg_replace( '/\D/', '', $phone_num );
                $digit_len  = strlen( $raw_digits );

                if ( $required && empty( $raw_digits ) ) {
                    $errors[ $name ] = sprintf( __( '%s is required.', 'usp-form-builder' ), $label );
                    continue;
                }

                if ( ! empty( $raw_digits ) ) {
                    $rule = USP_Form::get_country_rule_by_dial( $dial_code, $country_code );
                    $min  = isset( $rule['min_digits'] ) ? (int) $rule['min_digits'] : 7;
                    $max  = isset( $rule['max_digits'] ) ? (int) $rule['max_digits'] : 15;

                    if ( $digit_len < $min || $digit_len > $max ) {
                        $expected_text = ( $min === $max ) ? sprintf( __( 'exactly %d digits', 'usp-form-builder' ), $min ) : sprintf( __( 'between %1$d and %2$d digits', 'usp-form-builder' ), $min, $max );
                        $errors[ $name ] = sprintf(
                            __( 'Invalid phone number for %1$s (%2$s). It requires %3$s, but you entered %4$d digits.', 'usp-form-builder' ),
                            $rule['name'],
                            $rule['dial'],
                            $expected_text,
                            $digit_len
                        );
                        continue;
                    }
                }

                $full_phone = $phone_num ? ( $dial_code . ' ' . $phone_num ) : '';
                $submitted_entry[] = array(
                    'name'  => $name,
                    'label' => $label,
                    'type'  => $type,
                    'value' => $full_phone,
                );
                continue;
            }

            // Handle Pro: Payment Gateway / Fee Checkout with Brand Detection & Card Validation
            if ( 'payment' === $type ) {
                $pay_amount  = ! empty( $field['payment_amount'] ) ? sanitize_text_field( $field['payment_amount'] ) : '50.00';
                $currency    = ! empty( $field['payment_currency'] ) ? sanitize_text_field( $field['payment_currency'] ) : 'USD';
                $pay_method  = isset( $_POST[ $name . '_method' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $name . '_method' ] ) ) : 'card';
                $card_num    = isset( $_POST[ $name . '_card' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $name . '_card' ] ) ) : '';
                $card_holder = isset( $_POST[ $name . '_holder' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $name . '_holder' ] ) ) : '';
                $card_exp    = isset( $_POST[ $name . '_exp' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $name . '_exp' ] ) ) : '';
                $card_cvc    = isset( $_POST[ $name . '_cvc' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $name . '_cvc' ] ) ) : '';
                $card_brand  = isset( $_POST[ $name . '_brand' ] ) ? sanitize_key( $_POST[ $name . '_brand' ] ) : '';

                $raw_digits  = preg_replace( '/\D/', '', $card_num );

                if ( $required ) {
                    if ( empty( $card_holder ) ) {
                        $errors[ $name ] = sprintf( __( 'Cardholder name is required for %s.', 'usp-form-builder' ), $label );
                        continue;
                    }
                    if ( empty( $raw_digits ) ) {
                        $errors[ $name ] = sprintf( __( 'Card number is required for %s.', 'usp-form-builder' ), $label );
                        continue;
                    }
                    if ( empty( $card_exp ) ) {
                        $errors[ $name ] = sprintf( __( 'Expiration date is required for %s.', 'usp-form-builder' ), $label );
                        continue;
                    }
                    if ( empty( $card_cvc ) ) {
                        $errors[ $name ] = sprintf( __( 'Security code (CVC) is required for %s.', 'usp-form-builder' ), $label );
                        continue;
                    }
                }

                if ( ! empty( $raw_digits ) ) {
                    if ( empty( $card_brand ) || 'unknown' === $card_brand || 'generic' === $card_brand ) {
                        if ( preg_match( '/^3[47]/', $raw_digits ) ) {
                            $card_brand = 'amex';
                        } elseif ( preg_match( '/^4/', $raw_digits ) ) {
                            $card_brand = 'visa';
                        } elseif ( preg_match( '/^(5[1-5]|2[2-7])/', $raw_digits ) ) {
                            $card_brand = 'mastercard';
                        } elseif ( preg_match( '/^(6011|622|64[4-9]|65)/', $raw_digits ) ) {
                            $card_brand = 'discover';
                        } else {
                            $card_brand = 'card';
                        }
                    }

                    $min_len = ( 'amex' === $card_brand ) ? 15 : 16;
                    if ( strlen( $raw_digits ) < $min_len ) {
                        $errors[ $name ] = sprintf( __( 'Card number must be at least %d digits.', 'usp-form-builder' ), $min_len );
                        continue;
                    }
                }

                $brand_label = strtoupper( $card_brand ? $card_brand : 'Card' );
                $masked_card = $raw_digits ? ( '•••• ' . substr( $raw_digits, -4 ) ) : '';
                $summary     = sprintf( '%s %s via %s (%s) [%s] [Status: Authorized / Demo Mode]', $currency, $pay_amount, $brand_label, $masked_card ? $masked_card : 'Instant', $card_holder ? esc_html( $card_holder ) : 'Guest' );

                $submitted_entry[] = array(
                    'name'  => $name,
                    'label' => $label,
                    'type'  => $type,
                    'value' => $summary,
                );
                continue;
            }

            // Handle Pro: Digital Signature Pad
            if ( 'signature' === $type ) {
                $raw_sig = isset( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : '';
                $clean_sig = sanitize_text_field( $raw_sig );

                if ( $required && ( empty( $clean_sig ) || strpos( $clean_sig, 'data:image/png;base64' ) !== 0 ) ) {
                    $errors[ $name ] = sprintf( __( 'Digital signature is required for %s.', 'usp-form-builder' ), $label );
                    continue;
                }

                $submitted_entry[] = array(
                    'name'  => $name,
                    'label' => $label,
                    'type'  => $type,
                    'value' => $clean_sig,
                );
                continue;
            }

            // Handle Pro: Calculation Field
            if ( 'calculation' === $type ) {
                $calc_val = isset( $_POST[ $name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $name ] ) ) : '0.00';
                $prefix   = isset( $field['calc_prefix'] ) ? sanitize_text_field( $field['calc_prefix'] ) : '$';
                $suffix   = isset( $field['calc_suffix'] ) ? sanitize_text_field( $field['calc_suffix'] ) : '';

                $formatted_val = $prefix . $calc_val . ( $suffix ? ( ' ' . $suffix ) : '' );

                $submitted_entry[] = array(
                    'name'  => $name,
                    'label' => $label,
                    'type'  => $type,
                    'value' => $formatted_val,
                );
                continue;
            }

            // Standard inputs
            $raw_val = isset( $_POST[ $name ] ) ? $_POST[ $name ] : null;

            $is_empty = false;
            if ( is_array( $raw_val ) ) {
                $is_empty = empty( $raw_val );
            } else {
                $is_empty = ( null === $raw_val || '' === trim( (string) $raw_val ) );
            }

            if ( $required && $is_empty ) {
                $errors[ $name ] = sprintf( __( '%s is required.', 'usp-form-builder' ), $label );
                continue;
            }

            if ( 'country' === $type && ! USP_Form::is_valid_country_value( wp_unslash( $raw_val ) ) ) {
                $errors[ $name ] = __( 'Please select a valid country from the list.', 'usp-form-builder' );
                continue;
            }

            // Sanitize by type
            $clean_val = '';
            if ( is_array( $raw_val ) ) {
                $clean_val = array_map( 'sanitize_text_field', wp_unslash( $raw_val ) );
            } else {
                $raw_str = wp_unslash( (string) $raw_val );
                switch ( $type ) {
                    case 'email':
                        $clean_val = sanitize_email( $raw_str );
                        if ( ! empty( $clean_val ) && ! is_email( $clean_val ) ) {
                            $errors[ $name ] = __( 'Please enter a valid email address.', 'usp-form-builder' );
                        }
                        if ( empty( $user_email ) && ! empty( $clean_val ) ) {
                            $user_email = $clean_val;
                        }
                        break;

                    case 'url':
                        $clean_val = esc_url_raw( $raw_str );
                        break;

                    case 'textarea':
                        $clean_val = sanitize_textarea_field( $raw_str );
                        break;

                    case 'number':
                    case 'range':
                        $clean_val = sanitize_text_field( $raw_str );
                        if ( '' !== $clean_val && ! is_numeric( $clean_val ) ) {
                            $errors[ $name ] = __( 'Please enter a valid number.', 'usp-form-builder' );
                        }
                        break;

                    case 'rating':
                        $clean_val = absint( $raw_str );
                        if ( $clean_val < 1 || $clean_val > 5 ) {
                            $clean_val = 5;
                        }
                        break;

                    case 'consent':
                        $clean_val = ! empty( $raw_val ) ? __( 'Agreed & Accepted', 'usp-form-builder' ) : '';
                        if ( $required && empty( $clean_val ) ) {
                            $errors[ $name ] = sprintf( __( '%s is required.', 'usp-form-builder' ), $label );
                        }
                        break;

                    case 'tel':
                        $clean_val  = sanitize_text_field( $raw_str );
                        $raw_digits = preg_replace( '/\D/', '', $clean_val );
                        $digit_len  = strlen( $raw_digits );
                        if ( ! empty( $clean_val ) ) {
                            if ( $digit_len < 7 || $digit_len > 15 ) {
                                $errors[ $name ] = sprintf( __( 'Please enter a valid phone number (between 7 and 15 digits; you entered %d).', 'usp-form-builder' ), $digit_len );
                            }
                        }
                        break;

                    default:
                        $clean_val = sanitize_text_field( $raw_str );
                        if ( empty( $user_name ) && in_array( $name, array( 'name', 'your_name', 'full_name' ), true ) ) {
                            $user_name = $clean_val;
                        }
                        break;
                }
            }

            $submitted_entry[] = array(
                'name'  => $name,
                'label' => $label,
                'type'  => $type,
                'value' => $clean_val,
            );
        }

        // 5. Validation Error check
        if ( ! empty( $errors ) ) {
            wp_send_json_error( array(
                'message' => __( 'Please fix the highlighted errors before submitting.', 'usp-form-builder' ),
                'errors'  => $errors,
            ) );
        }

        // 6. Security Filter: Blocked Keywords & Phishing check
        $keyword_check = USP_Security::check_spam_keywords( $submitted_entry );
        if ( is_wp_error( $keyword_check ) ) {
            wp_send_json_error( array( 'message' => $keyword_check->get_error_message() ) );
        }

        // 7. Record submission in database
        $user_ip    = ! empty( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        $user_agent = ! empty( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

        $entry_id = USP_DB::save_entry( $form_id, $submitted_entry, $user_ip, $user_agent );

        if ( ! $entry_id ) {
            wp_send_json_error( array( 'message' => __( 'Failed to save submission. Please try again.', 'usp-form-builder' ) ) );
        }

        // 7. Admin Email Notification with Dynamic Merge Tags & Reply-To
        if ( ! empty( $settings['send_email'] ) ) {
            $to_email = ! empty( $settings['notification_email'] ) ? $settings['notification_email'] : get_option( 'admin_email' );
            
            $subject_template = ! empty( $settings['email_subject'] ) 
                ? $settings['email_subject'] 
                : sprintf( __( 'New submission for %s [#%d]', 'usp-form-builder' ), '{form_title}', '{entry_id}' );

            // Field table representation
            $fields_summary = '';
            foreach ( $submitted_entry as $item ) {
                $val = $item['value'];
                if ( is_array( $val ) ) {
                    if ( isset( $val['url'] ) ) {
                        $val_formatted = $val['file'] . ' (' . $val['url'] . ')';
                    } elseif ( isset( $val['student_name'] ) ) {
                        $adm_lines = array();
                        foreach ( $val as $ak => $av ) {
                            if ( '' !== trim( (string) $av ) ) {
                                $adm_lines[] = ucwords( str_replace( '_', ' ', $ak ) ) . ': ' . $av;
                            }
                        }
                        $val_formatted = "\n  * " . implode( "\n  * ", $adm_lines );
                    } else {
                        $val_formatted = implode( ', ', $val );
                    }
                } else {
                    $val_formatted = (string) $val;
                }
                $fields_summary .= sprintf( "%s: %s\n", $item['label'], $val_formatted );
            }

            // Replace merge tags
            $replacements = array(
                '{form_title}' => $form['title'],
                '{entry_id}'   => $entry_id,
                '{user_name}'  => $user_name ? $user_name : __( 'Visitor', 'usp-form-builder' ),
                '{user_email}' => $user_email,
                '{all_fields}' => $fields_summary,
                '{date}'       => current_time( 'mysql' ),
                '{user_ip}'    => $user_ip,
            );

            $subject = str_replace( array_keys( $replacements ), array_values( $replacements ), $subject_template );

            $body  = sprintf( __( "A new entry has been submitted for form: %s\n\n", 'usp-form-builder' ), $form['title'] );
            $body .= "----------------------------------------\n";
            $body .= $fields_summary;
            $body .= "----------------------------------------\n";
            $body .= sprintf( __( "Submitted on: %s\nIP: %s", 'usp-form-builder' ), current_time( 'mysql' ), $user_ip );

            $headers = array( 'Content-Type: text/plain; charset=UTF-8' );
            if ( ! empty( $user_email ) ) {
                $headers[] = 'Reply-To: ' . ( $user_name ? $user_name . ' ' : '' ) . '<' . $user_email . '>';
            }

            $raw_recipients   = explode( ',', (string) $to_email );
            $valid_recipients = array();
            foreach ( $raw_recipients as $rec ) {
                $clean_rec = sanitize_email( trim( $rec ) );
                if ( is_email( $clean_rec ) ) {
                    $valid_recipients[] = $clean_rec;
                }
            }
            if ( empty( $valid_recipients ) ) {
                $valid_recipients = array( get_option( 'admin_email' ) );
            }

            @wp_mail( $valid_recipients, $subject, $body, $headers );
        }

        // 8. Auto-responder email to submitter
        if ( ! empty( $settings['send_user_auto_reply'] ) && ! empty( $user_email ) ) {
            $user_subject = ! empty( $settings['auto_reply_subject'] ) ? $settings['auto_reply_subject'] : __( 'Thank you for your submission', 'usp-form-builder' );
            $user_body    = ! empty( $settings['auto_reply_message'] ) ? $settings['auto_reply_message'] : __( 'We have received your submission and will get back to you shortly.', 'usp-form-builder' );

            $user_headers = array( 'Content-Type: text/plain; charset=UTF-8' );
            @wp_mail( $user_email, $user_subject, $user_body, $user_headers );
        }

        // 9. Dispatch Webhook (Zapier, Make, n8n, Slack, Custom CRM) if configured
        if ( ! empty( $settings['enable_webhook'] ) && ! empty( $settings['webhook_url'] ) ) {
            $webhook_payload = array(
                'event'        => 'usp_form_submission',
                'form_id'      => $form_id,
                'form_title'   => $form['title'],
                'entry_id'     => $entry_id,
                'user_ip'      => $user_ip,
                'user_name'    => $user_name,
                'user_email'   => $user_email,
                'submitted_at' => current_time( 'mysql' ),
                'fields'       => $submitted_entry,
            );

            USP_Security::dispatch_webhook(
                $settings['webhook_url'],
                $webhook_payload,
                ! empty( $settings['webhook_secret'] ) ? $settings['webhook_secret'] : ''
            );
        }

        // 10. Return Success response & optional dynamic query redirect
        $success_message = ! empty( $settings['success_message'] ) 
            ? $settings['success_message'] 
            : __( 'Thank you! Your submission has been received.', 'usp-form-builder' );

        $redirect_url = ! empty( $settings['redirect_url'] ) ? esc_url_raw( $settings['redirect_url'] ) : '';
        if ( ! empty( $redirect_url ) && ! empty( $settings['pass_query_string'] ) ) {
            $query_args = array(
                'entry_id' => $entry_id,
                'form_id'  => $form_id,
            );
            if ( ! empty( $user_email ) ) {
                $query_args['user_email'] = $user_email;
            }
            if ( ! empty( $user_name ) ) {
                $query_args['user_name'] = $user_name;
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
     * Save Security & Anti-spam Settings via AJAX.
     */
    public function ajax_save_security_settings() {
        check_ajax_referer( 'usp_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'usp-form-builder' ) ) );
        }

        $settings = array(
            'anti_spam_provider'          => isset( $_POST['anti_spam_provider'] ) ? sanitize_key( $_POST['anti_spam_provider'] ) : 'none',
            'recaptcha_site_key'          => isset( $_POST['recaptcha_site_key'] ) ? sanitize_text_field( trim( $_POST['recaptcha_site_key'] ) ) : '',
            'recaptcha_secret_key'        => isset( $_POST['recaptcha_secret_key'] ) ? sanitize_text_field( trim( $_POST['recaptcha_secret_key'] ) ) : '',
            'recaptcha_v3_score'          => isset( $_POST['recaptcha_v3_score'] ) ? sanitize_text_field( $_POST['recaptcha_v3_score'] ) : '0.5',
            'turnstile_site_key'          => isset( $_POST['turnstile_site_key'] ) ? sanitize_text_field( trim( $_POST['turnstile_site_key'] ) ) : '',
            'turnstile_secret_key'        => isset( $_POST['turnstile_secret_key'] ) ? sanitize_text_field( trim( $_POST['turnstile_secret_key'] ) ) : '',
            'enable_time_gate'            => ! empty( $_POST['enable_time_gate'] ),
            'time_gate_seconds'           => isset( $_POST['time_gate_seconds'] ) ? absint( $_POST['time_gate_seconds'] ) : 2,
            'enable_rate_limiting'        => ! empty( $_POST['enable_rate_limiting'] ),
            'rate_limit_max'              => isset( $_POST['rate_limit_max'] ) ? absint( $_POST['rate_limit_max'] ) : 5,
            'rate_limit_window'           => isset( $_POST['rate_limit_window'] ) ? absint( $_POST['rate_limit_window'] ) : 5,
            'blacklisted_ips'             => isset( $_POST['blacklisted_ips'] ) ? sanitize_textarea_field( wp_unslash( $_POST['blacklisted_ips'] ) ) : '',
            'blocked_keywords'            => isset( $_POST['blocked_keywords'] ) ? sanitize_textarea_field( wp_unslash( $_POST['blocked_keywords'] ) ) : '',
            'enable_upload_hardening'     => ! empty( $_POST['enable_upload_hardening'] ),
            'enable_mime_sniffing'        => ! empty( $_POST['enable_mime_sniffing'] ),
            'enable_csv_injection_shield' => ! empty( $_POST['enable_csv_injection_shield'] ),
        );

        USP_Security::save_settings( $settings );
        $protection_result = USP_Security::protect_upload_directory();
        if ( is_wp_error( $protection_result ) ) {
            wp_send_json_error( array( 'message' => $protection_result->get_error_message() ) );
        }

        wp_send_json_success( array( 'message' => __( 'Security configuration updated & shields armed successfully!', 'usp-form-builder' ) ) );
    }

    /**
     * Process Pro license activation / deactivation via AJAX.
     */
    public function ajax_verify_license() {
        check_ajax_referer( 'usp_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'usp-form-builder' ) ) );
        }

        $key    = isset( $_POST['license_key'] ) ? sanitize_text_field( $_POST['license_key'] ) : '';
        $action = isset( $_POST['license_action'] ) ? sanitize_key( $_POST['license_action'] ) : 'activate';

        $result = USP_Security::process_license_action( $key, $action );

        if ( ! empty( $result['success'] ) ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result );
        }
    }

    /**
     * Save this plugin's product identity and customer-facing store links.
     */
    public function ajax_save_store_product() {
        check_ajax_referer( 'usp_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'usp-form-builder' ) ) );
        }

        $pricing_url = isset( $_POST['store_pricing_url'] ) ? esc_url_raw( wp_unslash( $_POST['store_pricing_url'] ), array( 'http', 'https' ) ) : '';
        $account_url = isset( $_POST['store_account_url'] ) ? esc_url_raw( wp_unslash( $_POST['store_account_url'] ), array( 'http', 'https' ) ) : '';
        if ( ( ! empty( $_POST['store_pricing_url'] ) && empty( $pricing_url ) ) || ( ! empty( $_POST['store_account_url'] ) && empty( $account_url ) ) ) {
            wp_send_json_error( array( 'message' => __( 'Enter valid HTTP or HTTPS URLs for the pricing and customer account pages.', 'usp-form-builder' ) ) );
        }

        $settings                = USP_Security::get_settings();
        $settings['store_pricing_url'] = $pricing_url ? $pricing_url : 'https://usp.com.pk/products/usp-form-builder';
        $settings['store_account_url'] = $account_url;
        USP_Security::save_settings( $settings );
        delete_transient( 'usp_form_builder_license_check_lock' );

        wp_send_json_success( array( 'message' => __( 'Store product configuration saved.', 'usp-form-builder' ) ) );
    }

    /**
     * Re-deploy security sandboxes via AJAX.
     */
    public function ajax_redeploy_shields() {
        check_ajax_referer( 'usp_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'usp-form-builder' ) ) );
        }

        $protection_result = USP_Security::protect_upload_directory( true );
        if ( is_wp_error( $protection_result ) ) {
            wp_send_json_error( array( 'message' => $protection_result->get_error_message() ) );
        }

        wp_send_json_success( array(
            'message' => __( 'Upload directory .htaccess script blocker and index.php silencer successfully redeployed!', 'usp-form-builder' ),
        ) );
    }

    /**
     * Test Webhook Endpoint via AJAX.
     */
    public function ajax_test_webhook() {
        check_ajax_referer( 'usp_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'usp-form-builder' ) ) );
        }

        $webhook_url = isset( $_POST['webhook_url'] ) ? esc_url_raw( trim( $_POST['webhook_url'] ) ) : '';
        $secret      = isset( $_POST['webhook_secret'] ) ? sanitize_text_field( trim( $_POST['webhook_secret'] ) ) : '';

        if ( empty( $webhook_url ) || ! filter_var( $webhook_url, FILTER_VALIDATE_URL ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter a valid Webhook URL (e.g. https://hooks.zapier.com/...)', 'usp-form-builder' ) ) );
        }

        $test_payload = array(
            'event'       => 'usp_test_ping',
            'source'      => 'USP Form Builder Pro',
            'test_time'   => current_time( 'mysql' ),
            'site_url'    => home_url(),
            'message'     => 'This is an instant verification ping from USP Form Builder Pro.',
            'sample_data' => array(
                'applicant_name' => 'Demo User',
                'email'          => 'demo@example.com',
                'status'         => 'verified',
            ),
        );

        $start_time = microtime( true );
        $res = USP_Security::dispatch_webhook( $webhook_url, $test_payload, $secret );
        $duration = round( ( microtime( true ) - $start_time ) * 1000 );

        if ( is_wp_error( $res ) ) {
            wp_send_json_error( array(
                'message' => sprintf( __( 'Webhook Dispatch Failed: %s', 'usp-form-builder' ), $res->get_error_message() ),
            ) );
        }

        $status_code = wp_remote_retrieve_response_code( $res );
        $body        = wp_remote_retrieve_body( $res );

        if ( $status_code >= 200 && $status_code < 300 ) {
            wp_send_json_success( array(
                'message'     => sprintf( __( 'Webhook Delivered Successfully! (HTTP %d in %dms)', 'usp-form-builder' ), $status_code, $duration ),
                'status_code' => $status_code,
                'latency_ms'  => $duration,
                'response'    => wp_trim_words( $body, 15 ),
            ) );
        } else {
            wp_send_json_error( array(
                'message'     => sprintf( __( 'Webhook Endpoint responded with error HTTP %d (Latency: %dms)', 'usp-form-builder' ), $status_code, $duration ),
                'status_code' => $status_code,
            ) );
        }
    }

    /**
     * Export Form Schema to JSON File.
     */
    public function export_form_json() {
        check_admin_referer( 'usp_export_form_json', 'usp_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Permission denied.', 'usp-form-builder' ) );
        }

        $form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;
        $form    = USP_DB::get_form( $form_id );

        if ( ! $form ) {
            wp_die( esc_html__( 'Form not found.', 'usp-form-builder' ) );
        }

        $export_payload = array(
            'usp_version' => USP_FORM_BUILDER_VERSION,
            'exported_at' => current_time( 'mysql' ),
            'generator'   => 'USP Form Builder Pro',
            'title'       => $form['title'],
            'fields_data' => $form['fields_data'],
            'settings'    => $form['settings'],
        );

        $json_content = wp_json_encode( $export_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
        $filename     = 'usp-form-' . sanitize_title( $form['title'] ) . '-' . $form_id . '.json';

        header( 'Content-Description: File Transfer' );
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Expires: 0' );
        header( 'Cache-Control: must-revalidate' );
        header( 'Pragma: public' );
        header( 'Content-Length: ' . strlen( $json_content ) );

        echo $json_content;
        exit;
    }

    /**
     * Import Form Schema from JSON via AJAX.
     */
    public function ajax_import_form_json() {
        check_ajax_referer( 'usp_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'usp-form-builder' ) ) );
        }

        $json_raw = '';

        if ( ! empty( $_FILES['import_file']['tmp_name'] ) ) {
            $json_raw = file_get_contents( $_FILES['import_file']['tmp_name'] );
        } elseif ( ! empty( $_POST['import_json'] ) ) {
            $json_raw = wp_unslash( $_POST['import_json'] );
        }

        if ( empty( $json_raw ) ) {
            wp_send_json_error( array( 'message' => __( 'No JSON data or file provided for import.', 'usp-form-builder' ) ) );
        }

        $data = json_decode( $json_raw, true );
        if ( ! is_array( $data ) || ( empty( $data['fields_data'] ) && empty( $data['title'] ) ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid USP Form JSON schema. Please ensure the file was exported from USP Form Builder.', 'usp-form-builder' ) ) );
        }

        $title       = ! empty( $data['title'] ) ? sanitize_text_field( $data['title'] ) . ' ' . __( '(Imported)', 'usp-form-builder' ) : __( 'Imported Form', 'usp-form-builder' );
        $fields_data = ! empty( $data['fields_data'] ) && is_array( $data['fields_data'] ) ? $data['fields_data'] : array();
        $settings    = ! empty( $data['settings'] ) && is_array( $data['settings'] ) ? $data['settings'] : array();

        $saved_id = USP_DB::save_form( array(
            'title'       => $title,
            'fields_data' => $fields_data,
            'settings'    => $settings,
            'status'      => 'publish',
        ) );

        if ( ! $saved_id ) {
            wp_send_json_error( array( 'message' => __( 'Failed to save imported form in the database.', 'usp-form-builder' ) ) );
        }

        wp_send_json_success( array(
            'message'      => __( 'Form imported successfully!', 'usp-form-builder' ),
            'form_id'      => $saved_id,
            'redirect_url' => admin_url( 'admin.php?page=usp-form-builder-new&id=' . $saved_id ),
        ) );
    }
}
