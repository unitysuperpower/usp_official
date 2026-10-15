<?php
/**
 * License activation for TechSolution Form Studio Pro against the usp.com.pk store.
 * A license enables automatic updates and support. All Pro fields work with or without one.
 *
 * @package TechSolution_Form_Studio_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TSFS_Pro_License {

    const OPTION_KEY = 'tsfs_pro_license';
    const PAGE_SLUG  = 'tsfs-pro-license';

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_page' ), 20 );
        add_action( 'wp_ajax_tsfs_pro_license', array( __CLASS__, 'ajax_license' ) );
        add_filter( 'plugin_action_links_' . TSFS_PRO_BASENAME, array( __CLASS__, 'action_links' ) );
        add_action( 'tsfs_pro_daily_license_check', array( __CLASS__, 'refresh' ) );
        add_action( 'init', array( __CLASS__, 'schedule' ) );
        register_deactivation_hook( TSFS_PRO_FILE, array( __CLASS__, 'deactivate' ) );
    }

    public static function schedule() {
        if ( ! wp_next_scheduled( 'tsfs_pro_daily_license_check' ) ) {
            wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'tsfs_pro_daily_license_check' );
        }
    }

    public static function deactivate() {
        wp_clear_scheduled_hook( 'tsfs_pro_daily_license_check' );
    }

    public static function defaults() {
        return array(
            'key'            => '',
            'status'         => 'inactive',
            'expires'        => '',
            'token'          => '',
            'site'           => '',
            'customer_email' => '',
            'site_count'     => 0,
            'limit'          => 0,
            'last_checked'   => 0,
            'error'          => '',
        );
    }

    public static function get() {
        $saved = get_option( self::OPTION_KEY, array() );
        return wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
    }

    private static function save( $data ) {
        update_option( self::OPTION_KEY, array_intersect_key( wp_parse_args( $data, self::defaults() ), self::defaults() ), false );
    }

    public static function site_identity() {
        $parts = wp_parse_url( home_url() );
        return strtolower( rtrim( isset( $parts['host'] ) ? $parts['host'] : '', '.' ) ) .
            ( ! empty( $parts['port'] ) && ! in_array( (int) $parts['port'], array( 80, 443 ), true ) ? ':' . $parts['port'] : '' ) .
            rtrim( isset( $parts['path'] ) ? $parts['path'] : '', '/' );
    }

    /**
     * Whether this site has an active, unexpired license (used to offer updates).
     */
    public static function is_valid() {
        $license = self::get();
        return 'valid' === $license['status'] && ! empty( $license['key'] ) && ! empty( $license['token'] ) &&
            ! empty( $license['expires'] ) && strtotime( $license['expires'] ) > time() &&
            hash_equals( (string) $license['site'], self::site_identity() );
    }

    /**
     * Send a product-scoped request to the store's license API.
     *
     * @return array|WP_Error
     */
    public static function request( $action, $key ) {
        $license = self::get();
        $payload = array(
            'action'  => $action,
            'product' => TSFS_PRO_PRODUCT_SLUG,
            'license' => $key,
            'url'     => home_url(),
        );
        if ( ! empty( $license['token'] ) ) {
            $payload['token'] = $license['token'];
        }

        $response = wp_safe_remote_post( TSFS_PRO_STORE_API, array(
            'timeout'             => 15,
            'redirection'         => 0,
            'limit_response_size' => 65536,
            'headers'             => array( 'Accept' => 'application/json', 'Content-Type' => 'application/json' ),
            'body'                => wp_json_encode( $payload ),
        ) );

        if ( is_wp_error( $response ) ) {
            /* translators: %s: HTTP error message */
            return new WP_Error( 'connection', sprintf( __( 'Could not contact the license server: %s', 'techsolution-form-studio-pro' ), $response->get_error_message() ) );
        }
        $code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            /* translators: %d: HTTP status code */
            return new WP_Error( 'http', sprintf( __( 'The license server returned HTTP %d. Try again later.', 'techsolution-form-studio-pro' ), absint( $code ) ) );
        }
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $data ) || ! array_key_exists( 'success', $data ) ) {
            return new WP_Error( 'response', __( 'The license server returned an invalid response.', 'techsolution-form-studio-pro' ) );
        }
        if ( ! empty( $data['success'] ) && 'deactivate_license' !== $action &&
            ( 'valid' !== ( isset( $data['license'] ) ? $data['license'] : '' ) || empty( $data['expires'] ) || strtotime( $data['expires'] ) <= time() ||
              ! preg_match( '/^[a-f0-9]{64}$/', isset( $data['activation_token'] ) ? $data['activation_token'] : '' ) ) ) {
            return new WP_Error( 'entitlement', __( 'The store returned an invalid or expired license.', 'techsolution-form-studio-pro' ) );
        }
        return $data;
    }

    private static function store_response( $key, $data ) {
        $license                   = self::get();
        $license['key']            = $key;
        $license['status']         = ! empty( $data['success'] ) && 'valid' === ( isset( $data['license'] ) ? $data['license'] : '' ) ? 'valid' : ( ! empty( $data['error'] ) ? sanitize_key( $data['error'] ) : 'invalid' );
        $license['expires']        = ! empty( $data['expires'] ) ? sanitize_text_field( $data['expires'] ) : '';
        $license['customer_email'] = ! empty( $data['customer_email'] ) ? sanitize_email( $data['customer_email'] ) : '';
        $license['site_count']     = isset( $data['site_count'] ) ? absint( $data['site_count'] ) : 0;
        $license['limit']          = isset( $data['license_limit'] ) ? absint( $data['license_limit'] ) : 0;
        $license['last_checked']   = time();
        $license['error']          = '';
        if ( 'valid' === $license['status'] && ! empty( $data['activation_token'] ) ) {
            $license['token'] = sanitize_text_field( $data['activation_token'] );
            $license['site']  = self::site_identity();
        }
        self::save( $license );
        return $license;
    }

    /**
     * Daily re-check so expiry and revocation are reflected in the update offer.
     */
    public static function refresh() {
        $license = self::get();
        if ( empty( $license['key'] ) ) {
            return;
        }
        $data = self::request( 'check_license', $license['key'] );
        if ( is_wp_error( $data ) ) {
            $license['error'] = $data->get_error_message();
            self::save( $license );
            return;
        }
        self::store_response( $license['key'], $data );
    }

    public static function ajax_license() {
        check_ajax_referer( 'tsfs_pro_license', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'techsolution-form-studio-pro' ) ), 403 );
        }
        $action = isset( $_POST['license_action'] ) ? sanitize_key( wp_unslash( $_POST['license_action'] ) ) : '';
        $key    = isset( $_POST['license_key'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) ) : '';

        if ( 'deactivate' === $action ) {
            $license = self::get();
            $remote  = empty( $license['key'] ) ? true : self::request( 'deactivate_license', $license['key'] );
            self::save( self::defaults() );
            delete_site_transient( 'update_plugins' );
            if ( is_wp_error( $remote ) || ( is_array( $remote ) && empty( $remote['success'] ) ) ) {
                wp_send_json_success( array( 'message' => __( 'The license was removed from this site, but the store could not confirm it. Release the site from your usp.com.pk account if it still counts toward your limit.', 'techsolution-form-studio-pro' ) ) );
            }
            wp_send_json_success( array( 'message' => __( 'License deactivated.', 'techsolution-form-studio-pro' ) ) );
        }

        if ( 'check' === $action ) {
            self::refresh();
            $license = self::get();
            if ( ! empty( $license['error'] ) ) {
                wp_send_json_error( array( 'message' => $license['error'] ) );
            }
            wp_send_json_success( array( 'message' => self::is_valid() ? __( 'License verified.', 'techsolution-form-studio-pro' ) : __( 'This license is not active for this site.', 'techsolution-form-studio-pro' ) ) );
        }

        if ( ! preg_match( '/^[A-Za-z0-9_-]{1,256}$/', $key ) ) {
            wp_send_json_error( array( 'message' => __( 'Enter the license key from your usp.com.pk account.', 'techsolution-form-studio-pro' ) ) );
        }
        $data = self::request( 'activate_license', $key );
        if ( is_wp_error( $data ) ) {
            wp_send_json_error( array( 'message' => $data->get_error_message() ) );
        }
        if ( empty( $data['success'] ) ) {
            $error = ! empty( $data['error'] ) ? sanitize_key( $data['error'] ) : 'invalid';
            /* translators: %s: error code from the store */
            wp_send_json_error( array( 'message' => sprintf( __( 'Activation was rejected (%s). Check the key, its expiry and the number of sites it allows.', 'techsolution-form-studio-pro' ), $error ) ) );
        }
        self::store_response( $key, $data );
        delete_site_transient( 'update_plugins' );
        wp_send_json_success( array( 'message' => __( 'License activated. Updates are now enabled.', 'techsolution-form-studio-pro' ) ) );
    }

    public static function register_page() {
        $parent = class_exists( 'TSFS_Plugin' ) ? 'tsfs-dashboard' : 'options-general.php';
        $hook   = add_submenu_page( $parent, __( 'Pro License', 'techsolution-form-studio-pro' ), __( 'Pro License', 'techsolution-form-studio-pro' ), 'manage_options', self::PAGE_SLUG, array( __CLASS__, 'render_page' ) );
        add_action( 'admin_print_scripts-' . $hook, array( __CLASS__, 'enqueue' ) );
    }

    public static function enqueue() {
        wp_enqueue_script( 'tsfs-pro-license', TSFS_PRO_PLUGIN_URL . 'assets/js/tsfs-pro-license.js', array( 'jquery', 'wp-i18n' ), TSFS_PRO_VERSION, true );
        wp_set_script_translations( 'tsfs-pro-license', 'techsolution-form-studio-pro' );
        wp_localize_script( 'tsfs-pro-license', 'tsfsProLicense', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'tsfs_pro_license' ),
        ) );
    }

    public static function action_links( $links ) {
        array_unshift( $links, '<a href="' . esc_url( menu_page_url( self::PAGE_SLUG, false ) ) . '">' . esc_html__( 'License', 'techsolution-form-studio-pro' ) . '</a>' );
        return $links;
    }

    public static function render_page() {
        $license = self::get();
        $valid   = self::is_valid();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'TechSolution Form Studio Pro — License', 'techsolution-form-studio-pro' ); ?></h1>
            <p><?php esc_html_e( 'All Pro fields work without a license. Activate your license to receive automatic updates and support.', 'techsolution-form-studio-pro' ); ?></p>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Status', 'techsolution-form-studio-pro' ); ?></th>
                    <td>
                        <strong><?php echo $valid ? esc_html__( 'Active — updates enabled', 'techsolution-form-studio-pro' ) : esc_html__( 'Not active', 'techsolution-form-studio-pro' ); ?></strong>
                        <?php if ( $license['expires'] ) : ?>
                            <?php /* translators: %s: expiry date */ ?>
                            <p class="description"><?php echo esc_html( sprintf( __( 'Expires: %s', 'techsolution-form-studio-pro' ), $license['expires'] ) ); ?></p>
                        <?php endif; ?>
                        <?php if ( $license['limit'] ) : ?>
                            <?php /* translators: 1: sites in use, 2: site limit */ ?>
                            <p class="description"><?php echo esc_html( sprintf( __( 'Sites: %1$d of %2$d', 'techsolution-form-studio-pro' ), $license['site_count'], $license['limit'] ) ); ?></p>
                        <?php endif; ?>
                        <?php if ( $license['error'] ) : ?>
                            <p class="description"><?php echo esc_html( $license['error'] ); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="tsfs-pro-license-key"><?php esc_html_e( 'License key', 'techsolution-form-studio-pro' ); ?></label></th>
                    <td>
                        <input type="password" id="tsfs-pro-license-key" class="regular-text code" autocomplete="off" value="<?php echo esc_attr( $license['key'] ); ?>">
                        <p class="description">
                            <?php esc_html_e( 'Find your key in your account at usp.com.pk.', 'techsolution-form-studio-pro' ); ?>
                            <a href="https://usp.com.pk/account/products" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'My products & licenses', 'techsolution-form-studio-pro' ); ?></a>
                        </p>
                    </td>
                </tr>
            </table>
            <p>
                <button type="button" class="button button-primary" data-license-action="activate"><?php esc_html_e( 'Activate', 'techsolution-form-studio-pro' ); ?></button>
                <?php if ( $license['key'] ) : ?>
                    <button type="button" class="button" data-license-action="check"><?php esc_html_e( 'Check now', 'techsolution-form-studio-pro' ); ?></button>
                    <button type="button" class="button button-link-delete" data-license-action="deactivate"><?php esc_html_e( 'Deactivate', 'techsolution-form-studio-pro' ); ?></button>
                <?php endif; ?>
                <span class="tsfs-pro-license-message" role="status" aria-live="polite"></span>
            </p>
            <p class="description"><?php esc_html_e( 'Activation, checks and updates send the license key, this site\'s address and the plugin version to usp.com.pk.', 'techsolution-form-studio-pro' ); ?></p>
        </div>
        <?php
    }
}
