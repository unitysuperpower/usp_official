<?php
/**
 * Removes plugin data on uninstall, only when the site owner enabled "Delete all data when the plugin is deleted".
 *
 * @package TechSolution_Form_Studio
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$tsfs_settings = get_option( 'tsfs_settings', array() );
if ( empty( $tsfs_settings['delete_data_on_uninstall'] ) ) {
    return;
}

global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}tsfs_entries" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}tsfs_forms" );
delete_option( 'tsfs_settings' );
delete_option( 'tsfs_db_version' );
wp_clear_scheduled_hook( 'tsfs_daily_cleanup' );

// Uploaded files are deleted only together with the rest of the data.
$tsfs_uploads = wp_upload_dir();
$tsfs_dir     = trailingslashit( $tsfs_uploads['basedir'] ) . 'techsolution-form-studio';
if ( is_dir( $tsfs_dir ) ) {
    foreach ( (array) array_merge( (array) glob( $tsfs_dir . '/*' ), array( $tsfs_dir . '/.htaccess' ) ) as $tsfs_file ) {
        if ( is_file( $tsfs_file ) ) {
            wp_delete_file( $tsfs_file );
        }
    }
    rmdir( $tsfs_dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
}
