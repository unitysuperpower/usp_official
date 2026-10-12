<?php
/**
 * Plugin Name:       USP Form Builder (Free)
 * Plugin URI:        https://usptechsolution.com/
 * Description:       A powerful drag-and-drop form builder with basic inputs, templates, and secure submissions.
 * Version:           1.0.7
 * Author:            Waseem
 * Author URI:        https://usptechsolution.com/
 * License:           GPL v2 or later
 * Text Domain:       usp-form-builder
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin Constants
define( 'USP_FORM_BUILDER_VERSION', '1.0.7' );
define( 'USP_FORM_BUILDER_DIR', plugin_dir_path( __FILE__ ) );
define( 'USP_FORM_BUILDER_URL', plugin_dir_url( __FILE__ ) );
define( 'USP_FORM_BUILDER_BASENAME', plugin_basename( __FILE__ ) );
define( 'USP_FORM_BUILDER_PRODUCT_SLUG', 'usp-form-builder' );

// Require Core Files
require_once USP_FORM_BUILDER_DIR . 'includes/class-usp-activator.php';
require_once USP_FORM_BUILDER_DIR . 'includes/class-usp-deactivator.php';
require_once USP_FORM_BUILDER_DIR . 'includes/class-usp-db.php';
require_once USP_FORM_BUILDER_DIR . 'includes/class-usp-security.php';
require_once USP_FORM_BUILDER_DIR . 'includes/class-usp-address.php';
require_once USP_FORM_BUILDER_DIR . 'includes/class-usp-form.php';
require_once USP_FORM_BUILDER_DIR . 'includes/class-usp-ajax.php';
require_once USP_FORM_BUILDER_DIR . 'admin/class-usp-admin.php';
require_once USP_FORM_BUILDER_DIR . 'public/class-usp-public.php';

// Activation and Deactivation Hooks
register_activation_hook( __FILE__, array( 'USP_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'USP_Deactivator', 'deactivate' ) );

/**
 * Main Plugin Orchestrator Class
 */
final class USP_Form_Builder {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Admin class instance
     */
    public $admin = null;

    /**
     * Public class instance
     */
    public $public = null;

    /**
     * AJAX class instance
     */
    public $ajax = null;

    /**
     * Get singleton instance.
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->init_hooks();
        $this->init_components();
    }

    /**
     * Initialize core WordPress hooks.
     */
    private function init_hooks() {
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        add_filter( 'plugin_action_links_' . USP_FORM_BUILDER_BASENAME, array( $this, 'plugin_action_links' ) );
    }

    /**
     * Load localization domain.
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'usp-form-builder',
            false,
            dirname( plugin_basename( __FILE__ ) ) . '/languages/'
        );
    }

    /**
     * Add action links on plugins.php page.
     */
    public function plugin_action_links( $links ) {
        $custom_links = array(
            '<a href="' . esc_url( admin_url( 'admin.php?page=usp-form-builder-home' ) ) . '">' . __( 'Dashboard', 'usp-form-builder' ) . '</a>',
            '<a href="' . esc_url( admin_url( 'admin.php?page=usp-form-builder-new' ) ) . '">' . __( 'Add New', 'usp-form-builder' ) . '</a>',
            '<a href="' . esc_url( admin_url( 'admin.php?page=usp-form-pro' ) ) . '" style="color:#d97706; font-weight:600;">' . __( 'Go Pro', 'usp-form-builder' ) . '</a>',
        );
        return array_merge( $custom_links, $links );
    }

    /**
     * Initialize plugin functional sub-systems.
     */
    private function init_components() {
        // Upgrade DB schema if needed
        USP_DB::maybe_upgrade_schema();

        // Admin & Public handlers
        if ( is_admin() ) {
            $this->admin = new USP_Admin();
        }
        $this->public = new USP_Public();
        $this->ajax   = new USP_AJAX();
    }
}

/**
 * Initialize the plugin.
 */
function usp_form_builder() {
    return USP_Form_Builder::instance();
}

// Kick off the plugin
usp_form_builder();