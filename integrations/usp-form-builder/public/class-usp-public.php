<?php
/**
 * Frontend Public Area Class.
 *
 * @package    USP_Form_Builder
 * @subpackage USP_Form_Builder/public
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class USP_Public {

    public function __construct() {
        add_shortcode( 'usp_form', array( $this, 'render_form_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
    }

    /**
     * Enqueue styles and scripts for the frontend forms.
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'usp-public-css',
            USP_FORM_BUILDER_URL . 'public/assets/css/usp-public.css',
            array(),
            USP_FORM_BUILDER_VERSION
        );

        wp_enqueue_style( 'usp-country-css', USP_FORM_BUILDER_URL . 'assets/css/usp-country.css', array(), USP_FORM_BUILDER_VERSION );
        wp_enqueue_script( 'usp-country-js', USP_FORM_BUILDER_URL . 'assets/js/usp-country.js', array( 'jquery' ), USP_FORM_BUILDER_VERSION, true );
        wp_localize_script( 'usp-country-js', 'uspCountryData', array(
            'search' => __( 'Search countries…', 'usp-form-builder' ),
            'matches' => __( '%d countries found', 'usp-form-builder' ),
            'empty' => __( 'No matching countries. Try another name.', 'usp-form-builder' ),
        ) );

        USP_Address::enqueue();

        wp_enqueue_script(
            'usp-public-js',
            USP_FORM_BUILDER_URL . 'public/assets/js/usp-public.js',
            array( 'jquery', 'usp-country-js', 'usp-address-js' ),
            USP_FORM_BUILDER_VERSION,
            true
        );

        wp_localize_script( 'usp-public-js', 'uspPublicData', array(
            'ajax_url'    => admin_url( 'admin-ajax.php' ),
            'phone_rules' => USP_Form::get_country_phone_rules(),
            'i18n'        => array(
                'submitting'     => __( 'Submitting...', 'usp-form-builder' ),
                'generic_error'  => __( 'Something went wrong. Please try again.', 'usp-form-builder' ),
                'required_field' => __( 'This field is required.', 'usp-form-builder' ),
                'invalid_email'  => __( 'Please enter a valid email address.', 'usp-form-builder' ),
                'invalid_phone'  => __( 'Please enter a valid phone number.', 'usp-form-builder' ),
            ),
        ) );
    }

    /**
     * Shortcode handler: [usp_form id="1" title="true|false"]
     */
    public function render_form_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'id'    => 0,
                'title' => true,
            ),
            $atts,
            'usp_form'
        );

        $form_id = absint( $atts['id'] );
        if ( ! $form_id ) {
            return '<div class="usp-error-box">' . esc_html__( 'Please specify a valid form ID: [usp_form id="123"]', 'usp-form-builder' ) . '</div>';
        }

        $display_title = filter_var( $atts['title'], FILTER_VALIDATE_BOOLEAN );

        return USP_Form::render_form_html( $form_id, $display_title );
    }
}
