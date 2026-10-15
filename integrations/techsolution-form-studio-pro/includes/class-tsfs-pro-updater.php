<?php
/**
 * Automatic updates for TechSolution Form Studio Pro from usp.com.pk (licensed sites only).
 * Uses the plugin's "Update URI" header and the update_plugins_{hostname} filter.
 *
 * @package TechSolution_Form_Studio_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TSFS_Pro_Updater {

    const CACHE_KEY = 'tsfs_pro_update_info';

    public static function init() {
        add_filter( 'update_plugins_usp.com.pk', array( __CLASS__, 'check' ), 10, 3 );
        add_filter( 'plugins_api', array( __CLASS__, 'details' ), 10, 3 );
        add_filter( 'upgrader_pre_download', array( __CLASS__, 'download' ), 10, 4 );
    }

    /**
     * Ask the store for the latest release. Cached for six hours.
     *
     * @return array|null Release data, or null when no licensed update is available.
     */
    private static function release( $force = false ) {
        if ( ! TSFS_Pro_License::is_valid() ) {
            return null;
        }
        $cached = get_site_transient( self::CACHE_KEY );
        if ( ! $force && is_array( $cached ) ) {
            return empty( $cached['version'] ) ? null : $cached;
        }
        $license = TSFS_Pro_License::get();
        $data    = TSFS_Pro_License::request( 'update', $license['key'] );
        $release = ( is_array( $data ) && ! empty( $data['success'] ) && ! empty( $data['update'] ) && is_array( $data['update'] ) ) ? self::validate( $data['update'] ) : null;
        set_site_transient( self::CACHE_KEY, $release ? $release : array(), 6 * HOUR_IN_SECONDS );
        return $release;
    }

    /**
     * Accept only HTTPS packages for this product from the store host, with a SHA-256 checksum.
     */
    private static function validate( $update ) {
        $parts = wp_parse_url( isset( $update['package'] ) ? $update['package'] : '' );
        if ( ! $parts || 'https' !== ( isset( $parts['scheme'] ) ? $parts['scheme'] : '' ) || 'usp.com.pk' !== ( isset( $parts['host'] ) ? $parts['host'] : '' ) ||
            isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['port'] ) ||
            TSFS_PRO_PRODUCT_SLUG !== ( isset( $update['slug'] ) ? $update['slug'] : '' ) ||
            ! is_string( isset( $update['version'] ) ? $update['version'] : null ) || ! preg_match( '/^\d+\.\d+\.\d+/', $update['version'] ) ||
            ! is_string( isset( $update['sha256'] ) ? $update['sha256'] : null ) || ! preg_match( '/^[a-f0-9]{64}$/', $update['sha256'] ) ) {
            return null;
        }
        return array(
            'version'   => $update['version'],
            'package'   => $update['package'],
            'sha256'    => $update['sha256'],
            'url'       => ! empty( $update['url'] ) ? esc_url_raw( $update['url'] ) : 'https://usp.com.pk/products/' . TSFS_PRO_PRODUCT_SLUG,
            'changelog' => isset( $update['changelog'] ) ? wp_kses_post( $update['changelog'] ) : '',
        );
    }

    public static function check( $update, $plugin_data, $plugin_file ) {
        if ( TSFS_PRO_BASENAME !== $plugin_file ) {
            return $update;
        }
        $release = self::release();
        if ( ! $release || version_compare( $release['version'], TSFS_PRO_VERSION, '<=' ) ) {
            return $update;
        }
        return array(
            'id'      => $release['url'],
            'slug'    => TSFS_PRO_PRODUCT_SLUG,
            'version' => $release['version'],
            'url'     => $release['url'],
            'package' => $release['package'],
        );
    }

    public static function details( $result, $action, $args ) {
        if ( 'plugin_information' !== $action || empty( $args->slug ) || TSFS_PRO_PRODUCT_SLUG !== $args->slug ) {
            return $result;
        }
        $release = self::release();
        return (object) array(
            'name'          => 'TechSolution Form Studio Pro',
            'slug'          => TSFS_PRO_PRODUCT_SLUG,
            'version'       => $release ? $release['version'] : TSFS_PRO_VERSION,
            'author'        => 'usptechsolution',
            'homepage'      => 'https://usp.com.pk/products/' . TSFS_PRO_PRODUCT_SLUG,
            'requires'      => '6.5',
            'requires_php'  => '7.4',
            'download_link' => $release ? $release['package'] : '',
            'sections'      => array(
                'description' => esc_html__( 'Pro add-on for TechSolution Form Studio: complete address, international phone, digital signature, calculation, payment details and admission application fields.', 'techsolution-form-studio-pro' ),
                'changelog'   => $release && $release['changelog'] ? wpautop( $release['changelog'] ) : '',
            ),
        );
    }

    /**
     * Download links from the store expire after 30 minutes, so request a fresh one and verify its checksum.
     */
    public static function download( $reply, $package, $upgrader, $hook_extra = array() ) {
        if ( false !== $reply || empty( $hook_extra['plugin'] ) || TSFS_PRO_BASENAME !== $hook_extra['plugin'] ) {
            return $reply;
        }
        $release = self::release( true );
        if ( ! $release ) {
            return new WP_Error( 'tsfs_pro_no_license', __( 'Activate your TechSolution Form Studio Pro license to download updates.', 'techsolution-form-studio-pro' ) );
        }
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $file = download_url( $release['package'], 120 );
        if ( is_wp_error( $file ) ) {
            return $file;
        }
        if ( ! hash_equals( $release['sha256'], hash_file( 'sha256', $file ) ) ) {
            wp_delete_file( $file );
            return new WP_Error( 'tsfs_pro_checksum', __( 'The downloaded update did not match its checksum and was not installed.', 'techsolution-form-studio-pro' ) );
        }
        delete_site_transient( self::CACHE_KEY );
        return $file;
    }
}
