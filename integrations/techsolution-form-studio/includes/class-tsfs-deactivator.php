<?php
/**
 * Fired during plugin deactivation.
 *
 * @package    TSFS_Plugin
 * @subpackage TSFS_Plugin/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TSFS_Deactivator {

    /**
     * Actions to perform upon plugin deactivation.
     */
    public static function deactivate() {
        wp_clear_scheduled_hook( TSFS_Privacy::CRON_HOOK );
    }
}
