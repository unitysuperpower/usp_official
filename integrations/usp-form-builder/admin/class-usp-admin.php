<?php
/**
 * Admin area management class.
 *
 * @package    USP_Form_Builder
 * @subpackage USP_Form_Builder/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class USP_Admin {

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
            __( 'USP Form Builder', 'usp-form-builder' ),
            __( 'USP Forms', 'usp-form-builder' ),
            'manage_options',
            'usp-form-builder-home',
            array( $this, 'render_dashboard_page' ),
            'dashicons-feedback',
            30
        );

        // Submenu: Dashboard
        add_submenu_page(
            'usp-form-builder-home',
            __( 'Dashboard', 'usp-form-builder' ),
            __( 'Dashboard', 'usp-form-builder' ),
            'manage_options',
            'usp-form-builder-home',
            array( $this, 'render_dashboard_page' )
        );

        // Submenu: All Forms
        add_submenu_page(
            'usp-form-builder-home',
            __( 'All Forms', 'usp-form-builder' ),
            __( 'All Forms', 'usp-form-builder' ),
            'manage_options',
            'usp-form-builder',
            array( $this, 'render_forms_list_page' )
        );

        // Submenu: Add New Form (Form Builder)
        add_submenu_page(
            'usp-form-builder-home',
            __( 'Add New Form', 'usp-form-builder' ),
            __( 'Add New', 'usp-form-builder' ),
            'manage_options',
            'usp-form-builder-new',
            array( $this, 'render_form_builder_page' )
        );

        // Submenu: Submissions / Entries
        add_submenu_page(
            'usp-form-builder-home',
            __( 'Form Submissions', 'usp-form-builder' ),
            __( 'Submissions', 'usp-form-builder' ),
            'manage_options',
            'usp-form-entries',
            array( $this, 'render_entries_page' )
        );

        // Submenu: Pro & Security Hub
        add_submenu_page(
            'usp-form-builder-home',
            __( 'Pro & Security Hub', 'usp-form-builder' ),
            __( 'Pro & Security ★', 'usp-form-builder' ),
            'manage_options',
            'usp-form-pro',
            array( $this, 'render_pro_features_page' )
        );
    }

    /**
     * Enqueue Admin Styles and Scripts.
     */
    public function enqueue_admin_assets( $hook ) {
        // Only load on USP pages
        if ( false === strpos( $hook, 'usp-form' ) ) {
            return;
        }

        // Admin CSS
        wp_enqueue_style(
            'usp-admin-css',
            USP_FORM_BUILDER_URL . 'admin/assets/css/usp-admin.css',
            array( 'dashicons' ),
            USP_FORM_BUILDER_VERSION
        );

        // Global USP Admin JS (Custom action dialogs, alerts, toasts)
        wp_enqueue_script(
            'usp-admin-js',
            USP_FORM_BUILDER_URL . 'admin/assets/js/usp-admin.js',
            array( 'jquery' ),
            USP_FORM_BUILDER_VERSION,
            true
        );

        wp_localize_script( 'usp-admin-js', 'uspAdminData', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'usp_admin_nonce' ),
            'is_pro'   => USP_Security::is_pro_active(),
        ) );

        // Form Builder JavaScript
        if ( false !== strpos( $hook, 'usp-form-builder-new' ) ) {
            wp_enqueue_style( 'usp-country-css', USP_FORM_BUILDER_URL . 'assets/css/usp-country.css', array(), USP_FORM_BUILDER_VERSION );
            wp_enqueue_script( 'usp-country-js', USP_FORM_BUILDER_URL . 'assets/js/usp-country.js', array( 'jquery' ), USP_FORM_BUILDER_VERSION, true );
            wp_localize_script( 'usp-country-js', 'uspCountryData', array(
                'search' => __( 'Search countries…', 'usp-form-builder' ),
                'matches' => __( '%d countries found', 'usp-form-builder' ),
                'empty' => __( 'No matching countries. Try another name.', 'usp-form-builder' ),
            ) );

            USP_Address::enqueue();

            wp_enqueue_script(
                'usp-builder-js',
                USP_FORM_BUILDER_URL . 'admin/assets/js/usp-builder.js',
                array( 'jquery', 'usp-admin-js', 'usp-country-js', 'usp-address-js' ),
                USP_FORM_BUILDER_VERSION,
                true
            );

            $form_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
            $current_form = $form_id ? USP_DB::get_form( $form_id ) : null;

            wp_localize_script( 'usp-builder-js', 'uspBuilderData', array(
                'ajax_url'         => admin_url( 'admin-ajax.php' ),
                'nonce'            => wp_create_nonce( 'usp_admin_nonce' ),
                'is_pro'           => USP_Security::is_pro_active(),
                'pro_field_types'  => USP_Form::get_pro_field_types(),
                'countries'        => USP_Form::get_countries(),
                'address_template' => USP_Address::preview_template(),
                'field_types'      => USP_Form::get_supported_field_types(),
                'form'             => $current_form,
                'forms_list_url'   => admin_url( 'admin.php?page=usp-form-builder' ),
                'i18n'             => array(
                    'preview_title' => __( 'Form preview', 'usp-form-builder' ),
                    'preview_description' => __( 'Test your form before publishing. Preview submissions are not saved.', 'usp-form-builder' ),
                    'preview_fields' => __( 'fields', 'usp-form-builder' ),
                    'outline_count' => __( '%shown% of %total% fields', 'usp-form-builder' ),
                    'outline_empty' => __( 'Your form has no fields. Choose Add fields to get started.', 'usp-form-builder' ),
                    'outline_no_matches' => __( 'No fields match. Try a different label or field type.', 'usp-form-builder' ),
                    'outline_required' => __( 'Required', 'usp-form-builder' ),
                    'outline_conditions' => __( 'Conditional', 'usp-form-builder' ),
                    'outline_untitled' => __( 'Untitled field', 'usp-form-builder' ),
                    'field_position' => __( 'Field %position% of %total%', 'usp-form-builder' ),
                    'country_label' => __( 'Country', 'usp-form-builder' ),
                    'nationality_label' => __( 'Nationality (country)', 'usp-form-builder' ),
                    'country_placeholder' => __( 'Select a country', 'usp-form-builder' ),
                    'country_find' => __( 'Find a country', 'usp-form-builder' ),
                    'country_search' => __( 'Type a country name…', 'usp-form-builder' ),
                    'country_info' => __( 'The country list is included automatically. Change the label to ask for country of residence or nationality. Answers are saved as country names.', 'usp-form-builder' ),
                    'status_saved' => __( 'All changes saved', 'usp-form-builder' ),
                    'status_unsaved' => __( 'Unsaved changes', 'usp-form-builder' ),
                    'status_saving' => __( 'Saving…', 'usp-form-builder' ),
                    'status_error' => __( 'Save failed — try again', 'usp-form-builder' ),
                    'save_success' => __( 'Form saved successfully!', 'usp-form-builder' ),
                    'save_error'   => __( 'Error saving form.', 'usp-form-builder' ),
                    'confirm_del'  => __( 'Are you sure you want to remove this field?', 'usp-form-builder' ),
                    'copied'       => __( 'Copied to clipboard!', 'usp-form-builder' ),
                ),
            ) );
        }
    }

    /**
     * Render the plugin home dashboard.
     */
    public function render_dashboard_page() {
        $forms = USP_DB::get_forms();
        include USP_FORM_BUILDER_DIR . 'admin/views/dashboard.php';
    }

    /**
     * Render the Forms List page.
     */
    public function render_forms_list_page() {
        $forms = USP_DB::get_forms();
        include USP_FORM_BUILDER_DIR . 'admin/views/forms-list.php';
    }

    /**
     * Render the Visual Form Builder page.
     */
    public function render_form_builder_page() {
        $form_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        $form    = $form_id ? USP_DB::get_form( $form_id ) : null;
        include USP_FORM_BUILDER_DIR . 'admin/views/form-builder.php';
    }

    /**
     * Render the Submissions / Entries page.
     */
    public function render_entries_page() {
        $selected_form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;
        $status           = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
        $date_from        = isset( $_GET['date_from'] ) ? sanitize_text_field( $_GET['date_from'] ) : '';
        $date_to          = isset( $_GET['date_to'] ) ? sanitize_text_field( $_GET['date_to'] ) : '';
        $search           = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

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

        $total_entries = USP_DB::get_entries_count( $filters );
        $total_pages   = max( 1, (int) ceil( $total_entries / $per_page ) );

        if ( $paged > $total_pages && $total_entries > 0 ) {
            $paged = $total_pages;
        }

        $offset = ( $paged - 1 ) * $per_page;

        $filters['limit']  = $per_page;
        $filters['offset'] = $offset;

        $forms         = USP_DB::get_forms();
        $entries       = USP_DB::get_entries( $filters );
        $status_counts = USP_DB::get_status_counts( $selected_form_id );

        include USP_FORM_BUILDER_DIR . 'admin/views/entries-list.php';
    }

    /**
     * Render the Pro features page.
     */
    public function render_pro_features_page() {
        include USP_FORM_BUILDER_DIR . 'admin/views/pro-features.php';
    }

    /**
     * Add dedicated body class on builder screen.
     */
    public function filter_admin_body_class( $classes ) {
        if ( isset( $_GET['page'] ) && 'usp-form-builder-new' === $_GET['page'] ) {
            $classes .= ' usp-builder-view';
        }
        return $classes;
    }
}
