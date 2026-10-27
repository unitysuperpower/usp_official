<?php
/**
 * Admin area management class.
 *
 * @package    TSFS_Plugin
 * @subpackage TSFS_Plugin/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TSFS_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_admin_menus' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_filter( 'admin_body_class', array( $this, 'filter_admin_body_class' ) );
    }

    /**
     * Register admin menu and submenus.
     */
    public function register_admin_menus() {
        // Main Menu: Dashboard
        add_menu_page(
            __( 'TechSolution Form Studio', 'techsolution-form-studio' ),
            __( 'Form Studio', 'techsolution-form-studio' ),
            'manage_options',
            'tsfs-dashboard',
            array( $this, 'render_dashboard_page' ),
            'dashicons-feedback',
            30
        );

        // Submenu: Dashboard
        add_submenu_page(
            'tsfs-dashboard',
            __( 'Dashboard', 'techsolution-form-studio' ),
            __( 'Dashboard', 'techsolution-form-studio' ),
            'manage_options',
            'tsfs-dashboard',
            array( $this, 'render_dashboard_page' )
        );

        // Submenu: All Forms
        add_submenu_page(
            'tsfs-dashboard',
            __( 'All Forms', 'techsolution-form-studio' ),
            __( 'All Forms', 'techsolution-form-studio' ),
            'manage_options',
            'tsfs-forms',
            array( $this, 'render_forms_list_page' )
        );

        // Submenu: Add New Form (Form Builder)
        add_submenu_page(
            'tsfs-dashboard',
            __( 'Add New Form', 'techsolution-form-studio' ),
            __( 'Add New', 'techsolution-form-studio' ),
            'manage_options',
            'tsfs-builder',
            array( $this, 'render_form_builder_page' )
        );

        // Submenu: Submissions / Entries
        add_submenu_page(
            'tsfs-dashboard',
            __( 'Form Submissions', 'techsolution-form-studio' ),
            __( 'Submissions', 'techsolution-form-studio' ),
            'manage_options',
            'tsfs-entries',
            array( $this, 'render_entries_page' )
        );

        // Submenu: Settings (anti-spam, uploads, privacy)
        add_submenu_page(
            'tsfs-dashboard',
            __( 'Settings', 'techsolution-form-studio' ),
            __( 'Settings', 'techsolution-form-studio' ),
            'manage_options',
            'tsfs-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Enqueue Admin Styles and Scripts.
     */
    public function enqueue_admin_assets( $hook ) {
        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only selects which assets to load.
        if ( 0 !== strpos( $page, 'tsfs-' ) ) {
            return;
        }

        wp_enqueue_style( 'tsfs-admin-css', TSFS_URL . 'admin/assets/css/tsfs-admin.css', array( 'dashicons' ), TSFS_VERSION );

        // Shared admin JS (dialogs, alerts, toasts)
        wp_enqueue_script( 'tsfs-admin-js', TSFS_URL . 'admin/assets/js/tsfs-admin.js', array( 'jquery' ), TSFS_VERSION, true );
        wp_localize_script( 'tsfs-admin-js', 'tsfsAdminData', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'tsfs_admin_nonce' ),
        ) );

        $screen_scripts = array(
            'tsfs-entries'  => 'tsfs-entries',
            'tsfs-forms'    => 'tsfs-forms-list',
            'tsfs-settings' => 'tsfs-settings',
        );
        if ( isset( $screen_scripts[ $page ] ) ) {
            $handle = $screen_scripts[ $page ] . '-js';
            wp_enqueue_script( $handle, TSFS_URL . 'admin/assets/js/' . $screen_scripts[ $page ] . '.js', array( 'jquery', 'wp-i18n', 'tsfs-admin-js' ), TSFS_VERSION, true );
            wp_set_script_translations( $handle, 'techsolution-form-studio' );
        }

        if ( 'tsfs-builder' !== $page ) {
            return;
        }

        TSFS_Public::register_assets();
        wp_enqueue_editor(); // Visual editor for section notes.
        wp_enqueue_style( 'tsfs-country-css' );
        wp_enqueue_script( 'tsfs-country-js' );

        wp_enqueue_script( 'tsfs-builder-js', TSFS_URL . 'admin/assets/js/tsfs-builder.js', array( 'jquery', 'tsfs-admin-js', 'tsfs-country-js' ), TSFS_VERSION, true );

        $form_id      = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $current_form = $form_id ? TSFS_DB::get_form( $form_id ) : null;

        wp_localize_script( 'tsfs-builder-js', 'tsfsBuilderData', array(
            'ajax_url'       => admin_url( 'admin-ajax.php' ),
            'nonce'          => wp_create_nonce( 'tsfs_admin_nonce' ),
            'countries'      => TSFS_Form::get_countries(),
            'field_types'    => TSFS_Form::get_supported_field_types(),
            'core_types'     => TSFS_Form::get_core_field_types(),
            'form'           => $current_form,
            'forms_list_url' => admin_url( 'admin.php?page=tsfs-forms' ),
            'max_upload_mb'  => max( 1, (int) floor( wp_max_upload_size() / MB_IN_BYTES ) ),
            'i18n'           => array(
                'preview_title'       => __( 'Form preview', 'techsolution-form-studio' ),
                'preview_description' => __( 'Test your form before publishing. Preview submissions are not saved.', 'techsolution-form-studio' ),
                'preview_fields'      => __( 'fields', 'techsolution-form-studio' ),
                /* translators: %shown% and %total% are replaced in the browser with the number of matching and total fields. Keep both tokens. */
                'outline_count'       => __( '%shown% of %total% fields', 'techsolution-form-studio' ),
                'outline_empty'       => __( 'Your form has no fields. Choose Add fields to get started.', 'techsolution-form-studio' ),
                'outline_no_matches'  => __( 'No fields match. Try a different label or field type.', 'techsolution-form-studio' ),
                'outline_required'    => __( 'Required', 'techsolution-form-studio' ),
                'outline_conditions'  => __( 'Conditional', 'techsolution-form-studio' ),
                'outline_untitled'    => __( 'Untitled field', 'techsolution-form-studio' ),
                'field_position'      => __( 'Field %position% of %total%', 'techsolution-form-studio' ),
                'country_label'       => __( 'Country', 'techsolution-form-studio' ),
                'nationality_label'   => __( 'Nationality (country)', 'techsolution-form-studio' ),
                'country_placeholder' => __( 'Select a country', 'techsolution-form-studio' ),
                'country_find'        => __( 'Find a country', 'techsolution-form-studio' ),
                'country_search'      => __( 'Type a country name…', 'techsolution-form-studio' ),
                'country_info'        => __( 'The country list is included automatically. Change the label to ask for country of residence or nationality. Answers are saved as country names.', 'techsolution-form-studio' ),
                'unavailable_type'    => __( 'This field type is provided by an add-on that is not active. It is kept in the form but not shown to visitors.', 'techsolution-form-studio' ),
                'status_saved'        => __( 'All changes saved', 'techsolution-form-studio' ),
                'status_unsaved'      => __( 'Unsaved changes', 'techsolution-form-studio' ),
                'status_saving'       => __( 'Saving…', 'techsolution-form-studio' ),
                'status_error'        => __( 'Save failed — try again', 'techsolution-form-studio' ),
                'save_success'        => __( 'Form saved successfully!', 'techsolution-form-studio' ),
                'save_error'          => __( 'Error saving form.', 'techsolution-form-studio' ),
                'confirm_del'         => __( 'Are you sure you want to remove this field?', 'techsolution-form-studio' ),
                'copied'              => __( 'Copied to clipboard!', 'techsolution-form-studio' ),
            ),
        ) );

        /**
         * Fires after the builder scripts are enqueued, so add-ons can extend the builder.
         */
        do_action( 'tsfs_enqueue_builder_assets' );
    }

    /**
     * Render the plugin home dashboard.
     */
    public function render_dashboard_page() {
        $forms = TSFS_DB::get_forms();
        include TSFS_DIR . 'admin/views/dashboard.php';
    }

    /**
     * Render the Forms List page.
     */
    public function render_forms_list_page() {
        $forms = TSFS_DB::get_forms();
        include TSFS_DIR . 'admin/views/forms-list.php';
    }

    /**
     * Render the Visual Form Builder page.
     */
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- the screens below only read GET values to choose what to display; every change goes through nonce-checked AJAX actions.
    public function render_form_builder_page() {
        $form_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        $form    = $form_id ? TSFS_DB::get_form( $form_id ) : null;
        include TSFS_DIR . 'admin/views/form-builder.php';
    }

    /**
     * Render the Submissions / Entries page.
     */
    public function render_entries_page() {
        $selected_form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;
        $status           = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
        $date_from        = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
        $date_to          = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';
        $search           = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

        $paged            = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
        $per_page         = isset( $_GET['per_page'] ) ? absint( $_GET['per_page'] ) : 20;
        if ( ! in_array( $per_page, array( 10, 20, 50, 100 ), true ) ) {
            $per_page = 20;
        }

        $filters = array(
            'form_id'   => $selected_form_id,
            'status'    => $status,
            'date_from' => $date_from,
            'date_to'   => $date_to,
            'search'    => $search,
        );

        $total_entries = TSFS_DB::get_entries_count( $filters );
        $total_pages   = max( 1, (int) ceil( $total_entries / $per_page ) );

        if ( $paged > $total_pages && $total_entries > 0 ) {
            $paged = $total_pages;
        }

        $offset = ( $paged - 1 ) * $per_page;

        $filters['limit']  = $per_page;
        $filters['offset'] = $offset;

        $forms         = TSFS_DB::get_forms();
        $entries       = TSFS_DB::get_entries( $filters );
        $status_counts = TSFS_DB::get_status_counts( $selected_form_id );

        include TSFS_DIR . 'admin/views/entries-list.php';
    }
    // phpcs:enable WordPress.Security.NonceVerification.Recommended

    /**
     * Render the settings page.
     */
    public function render_settings_page() {
        include TSFS_DIR . 'admin/views/settings.php';
    }

    /**
     * Add dedicated body class on builder screen.
     */
    public function filter_admin_body_class( $classes ) {
        if ( isset( $_GET['page'] ) && 'tsfs-builder' === sanitize_key( wp_unslash( $_GET['page'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $classes .= ' tsfs-builder-view';
        }
        return $classes;
    }
}
