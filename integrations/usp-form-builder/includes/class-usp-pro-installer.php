<?php
/** Private, licensed Pro upgrades through the WordPress upgrader. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class USP_Pro_Installer {
    public static function init() {
        add_action( 'wp_ajax_usp_install_pro', array( __CLASS__, 'install' ) );
    }

    public static function package() {
        $settings = USP_Security::get_settings();
        if ( empty( $settings['license_key'] ) ) {
            return new WP_Error( 'license_required', __( 'Activate your purchased license first.', 'usp-form-builder' ) );
        }
        // Always contact the store: cached/offline access must never authorize a download.
        $response = USP_Security::request_license_api( 'update', $settings['license_key'] );
        if ( is_wp_error( $response ) ) { return $response; }
        if ( empty( $response['success'] ) || empty( $response['update'] ) ) {
            return new WP_Error( 'package_unavailable', __( 'No Pro download is available for this license. Check its expiry and website activation, or contact USP support.', 'usp-form-builder' ) );
        }
        $package = $response['update'];
        $parts = parse_url( $package['package'] ?? '' );
        if ( ! $parts || 'https' !== ( $parts['scheme'] ?? '' ) || 'usp.com.pk' !== ( $parts['host'] ?? '' ) ||
            isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['port'] ) ||
            0 !== strpos( $parts['path'] ?? '', '/api/licenses/packages/' ) ||
            'usp-form-builder' !== ( $package['slug'] ?? '' ) || 'pro' !== ( $package['edition'] ?? '' ) ||
            ! is_string( $package['sha256'] ?? null ) || ! preg_match( '/^[a-f0-9]{64}$/', $package['sha256'] ) ||
            ! is_string( $package['version'] ?? null ) || version_compare( $package['version'], USP_FORM_BUILDER_VERSION, '<' ) ) {
            return new WP_Error( 'untrusted_package', __( 'The store returned an invalid upgrade package. Nothing was installed.', 'usp-form-builder' ) );
        }
        return $package;
    }

    public static function install() {
        check_ajax_referer( 'usp_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'update_plugins' ) || ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'An administrator with plugin update permission must install Pro.', 'usp-form-builder' ) ), 403 );
        }
        if ( version_compare( get_bloginfo( 'version' ), '6.3', '<' ) ) {
            wp_send_json_error( array( 'message' => __( 'Update WordPress to version 6.3 or later before installing Pro.', 'usp-form-builder' ) ) );
        }
        if ( is_multisite() ) {
            wp_send_json_error( array( 'message' => __( 'For multisite, ask your network administrator to install the Pro ZIP from your USP account. Each licensed site must activate separately.', 'usp-form-builder' ) ) );
        }
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        if ( 'direct' !== get_filesystem_method() ) {
            wp_send_json_error( array( 'message' => __( 'Your hosting requires filesystem credentials. Download the Pro ZIP from your USP account, then use Plugins → Add New → Upload Plugin to replace this plugin.', 'usp-form-builder' ) ) );
        }
        if ( ! add_option( 'usp_pro_install_lock', time(), '', false ) ) {
            $started = (int) get_option( 'usp_pro_install_lock' );
            if ( $started < time() - 600 ) { delete_option( 'usp_pro_install_lock' ); }
            wp_send_json_error( array( 'message' => __( 'Another upgrade is running. Wait a moment and try again.', 'usp-form-builder' ) ) );
        }
        $temp = null;
        $filter = null;
        $result = null;
        try {
            $package = self::package();
            if ( is_wp_error( $package ) ) { throw new RuntimeException( $package->get_error_message() ); }
            $temp = wp_tempnam( 'usp-form-builder-pro.zip' );
            if ( ! $temp ) { throw new RuntimeException( 'Could not create a temporary upgrade file.' ); }
            $download = wp_safe_remote_get( $package['package'], array(
                'timeout' => 90, 'redirection' => 0, 'stream' => true, 'filename' => $temp,
                'limit_response_size' => 20 * 1024 * 1024 + 1,
            ) );
            if ( is_wp_error( $download ) || 200 !== wp_remote_retrieve_response_code( $download ) ||
                filesize( $temp ) > 20 * 1024 * 1024 || ! hash_equals( $package['sha256'], hash_file( 'sha256', $temp ) ) ) {
                throw new RuntimeException( 'The Pro download could not be verified. Your existing plugin was not replaced.' );
            }
            $filter = static function( $updates ) use ( $temp, $package ) {
                if ( ! is_object( $updates ) ) { $updates = new stdClass(); }
                $updates->response[ USP_FORM_BUILDER_BASENAME ] = (object) array(
                    'slug' => 'usp-form-builder', 'plugin' => USP_FORM_BUILDER_BASENAME,
                    'new_version' => $package['version'], 'package' => $temp,
                );
                return $updates;
            };
            add_filter( 'site_transient_update_plugins', $filter );
            $was_active = is_plugin_active( USP_FORM_BUILDER_BASENAME );
            $skin = new Automatic_Upgrader_Skin();
            $upgrader = new Plugin_Upgrader( $skin );
            $result = $upgrader->upgrade( USP_FORM_BUILDER_BASENAME );
            if ( is_wp_error( $result ) || true !== $result ) {
                throw new RuntimeException( is_wp_error( $result ) ? $result->get_error_message() : 'WordPress could not finish the upgrade. Review plugin status and retry, or install the Pro ZIP from your account.' );
            }
            if ( $was_active ) {
                $activation = activate_plugin( USP_FORM_BUILDER_BASENAME, '', false, true );
                if ( is_wp_error( $activation ) ) { throw new RuntimeException( 'Pro files were installed, but WordPress could not reactivate the plugin: ' . $activation->get_error_message() ); }
            }
        } catch ( Throwable $error ) {
            $result = new WP_Error( 'upgrade_failed', $error->getMessage() );
        } finally {
            if ( $filter ) { remove_filter( 'site_transient_update_plugins', $filter ); delete_site_transient( 'update_plugins' ); }
            if ( $temp && file_exists( $temp ) ) { wp_delete_file( $temp ); }
            delete_option( 'usp_pro_install_lock' );
        }
        if ( is_wp_error( $result ) ) { wp_send_json_error( array( 'message' => $result->get_error_message() ) ); }
        wp_send_json_success( array( 'message' => __( 'Pro installed. Reloading your dashboard; your forms and settings are preserved.', 'usp-form-builder' ) ) );
    }
}
