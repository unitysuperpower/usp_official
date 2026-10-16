<?php
/**
 * Plugin Name:       TechSolution Form Studio Pro
 * Plugin URI:        https://usp.com.pk/products/techsolution-form-studio-pro
 * Description:       Pro add-on for TechSolution Form Studio: complete address, international phone, digital signature, calculation, payment details, admission application fields and a rich content editor block.
 * Version:           2.2.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Requires Plugins:  techsolution-form-studio
 * Author:            usptechsolution
 * Author URI:        https://usptechsolution.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://usp.com.pk/products/techsolution-form-studio-pro
 * Text Domain:       techsolution-form-studio-pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'TSFS_PRO_VERSION', '2.2.0' );
define( 'TSFS_PRO_FILE', __FILE__ );
define( 'TSFS_PRO_DIR', plugin_dir_path( __FILE__ ) );
define( 'TSFS_PRO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TSFS_PRO_BASENAME', plugin_basename( __FILE__ ) );
define( 'TSFS_PRO_PRODUCT_SLUG', 'techsolution-form-studio-pro' );
define( 'TSFS_PRO_STORE_API', 'https://usp.com.pk/api/licenses/v1' );

require_once TSFS_PRO_DIR . 'includes/class-tsfs-pro-license.php';
require_once TSFS_PRO_DIR . 'includes/class-tsfs-pro-updater.php';

// Licensing and updates work even if the core plugin is inactive, so customers can always update.
TSFS_Pro_License::init();
TSFS_Pro_Updater::init();

add_action( 'plugins_loaded', 'tsfs_pro_bootstrap', 20 );

/**
 * Load the field types once the core plugin is available.
 */
function tsfs_pro_bootstrap() {
    if ( ! class_exists( 'TSFS_Plugin' ) || ! defined( 'TSFS_VERSION' ) || version_compare( TSFS_VERSION, '2.0.0', '<' ) ) {
        add_action( 'admin_notices', 'tsfs_pro_missing_core_notice' );
        return;
    }
    require_once TSFS_PRO_DIR . 'includes/class-tsfs-pro-calculator.php';
    require_once TSFS_PRO_DIR . 'includes/class-tsfs-pro-address.php';
    require_once TSFS_PRO_DIR . 'includes/class-tsfs-pro-fields.php';
    TSFS_Pro_Fields::init();

    // Keep shortcodes from the plugin's former name (USP Form Builder) working on existing pages.
    if ( ! shortcode_exists( 'usp_form' ) ) {
        add_shortcode( 'usp_form', 'tsfs_pro_legacy_shortcode' );
    }
}

/**
 * [usp_form id="1"] → same output as [tsfs_form id="1"].
 */
function tsfs_pro_legacy_shortcode( $atts ) {
    $atts = shortcode_atts( array( 'id' => 0, 'title' => true ), $atts, 'usp_form' );
    return TSFS_Form::render_form_html( absint( $atts['id'] ), filter_var( $atts['title'], FILTER_VALIDATE_BOOLEAN ) );
}

function tsfs_pro_missing_core_notice() {
    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }
    echo '<div class="notice notice-error"><p>' .
        esc_html__( 'TechSolution Form Studio Pro needs TechSolution Form Studio 2.0.0 or later. Install and activate it from Plugins → Add New.', 'techsolution-form-studio-pro' ) .
        '</p></div>';
}
