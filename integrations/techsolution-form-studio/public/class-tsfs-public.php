<?php
/**
 * Frontend Public Area Class: shortcode, block, and asset loading.
 *
 * @package    TSFS_Plugin
 * @subpackage TSFS_Plugin/public
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TSFS_Public {

    public function __construct() {
        add_shortcode( 'tsfs_form', array( $this, 'render_form_shortcode' ) );
        add_action( 'init', array( $this, 'register_block' ) );
        add_action( 'enqueue_block_editor_assets', array( $this, 'block_editor_data' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ), 5 );
        add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ) );
    }

    /**
     * Register frontend styles and scripts. They are enqueued only on pages that display a form.
     */
    public static function register_assets() {
        wp_register_style( 'tsfs-public-css', TSFS_URL . 'public/assets/css/tsfs-public.css', array( 'dashicons' ), TSFS_VERSION );
        wp_register_style( 'tsfs-country-css', TSFS_URL . 'assets/css/tsfs-country.css', array(), TSFS_VERSION );
        wp_register_script( 'tsfs-country-js', TSFS_URL . 'assets/js/tsfs-country.js', array( 'jquery' ), TSFS_VERSION, true );
        wp_localize_script( 'tsfs-country-js', 'tsfsCountryData', array(
            /* translators: %d: number of matching countries */
            'matches' => __( '%d countries found', 'techsolution-form-studio' ),
            'search'  => __( 'Search countries…', 'techsolution-form-studio' ),
            'empty'   => __( 'No matching countries. Try another name.', 'techsolution-form-studio' ),
        ) );
        wp_register_script( 'tsfs-cnic-js', TSFS_URL . 'public/assets/js/tsfs-cnic.js', array(), TSFS_VERSION, true );
        wp_register_script( 'tsfs-public-js', TSFS_URL . 'public/assets/js/tsfs-public.js', array( 'jquery', 'tsfs-country-js', 'tsfs-cnic-js' ), TSFS_VERSION, true );

        $security = TSFS_Security::get_settings();
        wp_localize_script( 'tsfs-public-js', 'tsfsPublicData', array(
            'ajax_url'          => admin_url( 'admin-ajax.php' ),
            'recaptcha_v3_key'  => 'recaptcha_v3' === $security['anti_spam_provider'] ? $security['recaptcha_site_key'] : '',
            'i18n'              => array(
                'submitting'       => __( 'Submitting...', 'techsolution-form-studio' ),
                'generic_error'    => __( 'Something went wrong. Please try again.', 'techsolution-form-studio' ),
                'network_error'    => __( 'A network error occurred. Please try again.', 'techsolution-form-studio' ),
                'required_field'   => __( 'This field is required.', 'techsolution-form-studio' ),
                'required_file'    => __( 'Please select a file to upload.', 'techsolution-form-studio' ),
                'consent_required' => __( 'Please check the consent box to continue.', 'techsolution-form-studio' ),
                'invalid_email'    => __( 'Please enter a valid email address.', 'techsolution-form-studio' ),
                /* translators: %d: number of digits entered */
                'invalid_phone'    => __( 'Please enter a valid phone number (between 7 and 15 digits; you entered %d).', 'techsolution-form-studio' ),
                'fix_errors'       => __( 'Please correct the highlighted errors before submitting.', 'techsolution-form-studio' ),
                'captcha_v2'       => __( 'Please check the "I am not a robot" box.', 'techsolution-form-studio' ),
                'captcha_turnstile'=> __( 'Please complete the security verification.', 'techsolution-form-studio' ),
                'success'          => __( 'Thank you! Your submission has been received.', 'techsolution-form-studio' ),
                /* translators: 1: selected stars, 2: maximum stars */
                'rating_value'     => __( '%1$s of %2$s stars', 'techsolution-form-studio' ),
            ),
        ) );
    }

    /**
     * Enqueue form assets early on singular content that contains a form shortcode or block.
     */
    public function maybe_enqueue_assets() {
        $post = get_post();
        if ( is_singular() && $post && ( has_shortcode( $post->post_content, 'tsfs_form' ) || has_block( 'techsolution-form-studio/form', $post ) ) ) {
            self::enqueue_form_assets();
        }
    }

    /**
     * Enqueue the shared frontend form assets. Safe to call more than once.
     */
    public static function enqueue_form_assets() {
        if ( ! wp_script_is( 'tsfs-public-js', 'registered' ) ) {
            self::register_assets();
        }
        wp_enqueue_style( 'tsfs-public-css' );
        wp_enqueue_style( 'tsfs-country-css' );
        wp_enqueue_script( 'tsfs-public-js' );
    }

    /**
     * Enqueue the configured CAPTCHA provider's script (Google reCAPTCHA or Cloudflare Turnstile).
     * See the "External services" section of readme.txt.
     */
    public static function enqueue_captcha_script( $settings ) {
        $provider = isset( $settings['anti_spam_provider'] ) ? $settings['anti_spam_provider'] : 'none';
        if ( 'recaptcha_v2' === $provider && ! empty( $settings['recaptcha_site_key'] ) ) {
            wp_enqueue_script( 'tsfs-recaptcha', 'https://www.google.com/recaptcha/api.js', array(), null, array( 'in_footer' => true, 'strategy' => 'async' ) );
        } elseif ( 'recaptcha_v3' === $provider && ! empty( $settings['recaptcha_site_key'] ) ) {
            wp_enqueue_script( 'tsfs-recaptcha', add_query_arg( 'render', rawurlencode( $settings['recaptcha_site_key'] ), 'https://www.google.com/recaptcha/api.js' ), array(), null, array( 'in_footer' => true, 'strategy' => 'async' ) );
        } elseif ( 'turnstile' === $provider && ! empty( $settings['turnstile_site_key'] ) ) {
            wp_enqueue_script( 'tsfs-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null, array( 'in_footer' => true, 'strategy' => 'async' ) );
        }
    }

    /**
     * Register the "Form Studio Form" block. It renders on the server with the same output as the shortcode.
     */
    public function register_block() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }
        wp_register_script(
            'tsfs-block-editor',
            TSFS_URL . 'admin/assets/js/tsfs-block.js',
            array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render', 'wp-i18n' ),
            TSFS_VERSION,
            true
        );
        register_block_type( 'techsolution-form-studio/form', array(
            'api_version'     => 3,
            'title'           => __( 'Form Studio Form', 'techsolution-form-studio' ),
            'category'        => 'widgets',
            'icon'            => 'feedback',
            'editor_script'   => 'tsfs-block-editor',
            'render_callback' => array( $this, 'render_block' ),
            'attributes'      => array(
                'formId'    => array( 'type' => 'number', 'default' => 0 ),
                'showTitle' => array( 'type' => 'boolean', 'default' => true ),
            ),
            'supports'        => array( 'html' => false, 'align' => array( 'wide', 'full' ) ),
        ) );
    }

    /**
     * Form list for the block's form picker (loaded only in the block editor).
     */
    public function block_editor_data() {
        $options = array();
        foreach ( TSFS_DB::get_forms() as $form ) {
            $options[] = array( 'value' => (int) $form['id'], 'label' => $form['title'] . ' (#' . (int) $form['id'] . ')' );
        }
        wp_localize_script( 'tsfs-block-editor', 'tsfsBlockData', array( 'forms' => $options ) );
    }

    /**
     * Server-side render callback for the block.
     */
    public function render_block( $attributes ) {
        $form_id = isset( $attributes['formId'] ) ? absint( $attributes['formId'] ) : 0;
        if ( ! $form_id ) {
            return '';
        }
        return TSFS_Form::render_form_html( $form_id, ! isset( $attributes['showTitle'] ) || ! empty( $attributes['showTitle'] ) );
    }

    /**
     * Shortcode handler: [tsfs_form id="1" title="true|false"]
     */
    public function render_form_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'id'    => 0,
                'title' => true,
            ),
            $atts,
            'tsfs_form'
        );

        $form_id = absint( $atts['id'] );
        if ( ! $form_id ) {
            return '<div class="tsfs-error-box">' . esc_html__( 'Please specify a valid form ID: [tsfs_form id="123"]', 'techsolution-form-studio' ) . '</div>';
        }

        $display_title = filter_var( $atts['title'], FILTER_VALIDATE_BOOLEAN );

        return TSFS_Form::render_form_html( $form_id, $display_title );
    }
}
