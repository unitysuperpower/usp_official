<?php
/**
 * Fired during plugin deactivation.
 *
 * @package    USP_Form_Builder
 * @subpackage USP_Form_Builder/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class USP_Deactivator {

    /**
     * Actions to perform upon plugin deactivation.
     */
    public static function deactivate() {
        // Placeholder for any temporary transients cleanup if needed.
    }
}
