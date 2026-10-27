<?php
/**
 * Plugin Name:       TechSolution Form Studio
 * Description:       Drag-and-drop form builder with conditional logic, multi-step forms, file uploads, ratings, entries management, exports, email notifications and spam protection.
 * Version:           2.3.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            usptechsolution
 * Author URI:        https://usptechsolution.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       techsolution-form-studio
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin Constants
define( 'TSFS_VERSION', '2.3.0' );
define( 'TSFS_FILE', __FILE__ );
define( 'TSFS_DIR', plugin_dir_path( __FILE__ ) );
define( 'TSFS_URL', plugin_dir_url( __FILE__ ) );
define( 'TSFS_BASENAME', plugin_basename( __FILE__ ) );
define( 'TSFS_PRO_URL', 'https://usp.com.pk/products/techsolution-form-studio-pro' );

// Require Core Files
require_once TSFS_DIR . 'includes/class-tsfs-activator.php';
require_once TSFS_DIR . 'includes/class-tsfs-deactivator.php';
require_once TSFS_DIR . 'includes/class-tsfs-db.php';
require_once TSFS_DIR . 'includes/class-tsfs-security.php';
require_once TSFS_DIR . 'includes/class-tsfs-form.php';
require_once TSFS_DIR . 'includes/class-tsfs-ajax.php';
require_once TSFS_DIR . 'includes/class-tsfs-privacy.php';
require_once TSFS_DIR . 'admin/class-tsfs-admin.php';
require_once TSFS_DIR . 'public/class-tsfs-public.php';

// Activation and Deactivation Hooks
register_activation_hook( __FILE__, array( 'TSFS_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'TSFS_Deactivator', 'deactivate' ) );

/**
 * Main Plugin Orchestrator Class
 */
final class TSFS_Plugin {

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
        add_filter( 'plugin_action_links_' . TSFS_BASENAME, array( $this, 'plugin_action_links' ) );
    }

    /**
     * Add action links on plugins.php page.
     */
    public function plugin_action_links( $links ) {
        $custom_links = array(
            '<a href="' . esc_url( admin_url( 'admin.php?page=tsfs-dashboard' ) ) . '">' . esc_html__( 'Dashboard', 'techsolution-form-studio' ) . '</a>',
            '<a href="' . esc_url( admin_url( 'admin.php?page=tsfs-builder' ) ) . '">' . esc_html__( 'Add New', 'techsolution-form-studio' ) . '</a>',
        );
        return array_merge( $custom_links, $links );
    }

    /**
     * Initialize plugin functional sub-systems.
     */
    private function init_components() {
        // Upgrade DB schema if needed
        TSFS_DB::maybe_upgrade_schema();

        // Admin & Public handlers
        if ( is_admin() ) {
            $this->admin = new TSFS_Admin();
        }
        $this->public = new TSFS_Public();
        $this->ajax   = new TSFS_AJAX();
        TSFS_Privacy::init();

        /**
         * Fires after the core plugin is loaded. Add-ons register field types from here.
         */
        do_action( 'tsfs_loaded' );
    }
}

/**
 * Initialize the plugin.
 */
function tsfs_plugin() {
    return TSFS_Plugin::instance();
}

// Kick off the plugin
tsfs_plugin();
