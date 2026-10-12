<?php
/**
 * Run with: php tests/edd-license-client.php.
 * Exercises the EDD license client using a local HTTP API stub.
 */

define( 'DAY_IN_SECONDS', 86400 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'ABSPATH', __DIR__ . '/' );

class WP_Error {
    private $message;

    public function __construct( $code, $message ) {
        $this->message = $message;
    }

    public function get_error_message() {
        return $this->message;
    }
}

$test_options   = array();
$test_transients = array();
$test_requests  = array();
$test_api_reply = array(
    'success'         => true,
    'license'         => 'valid',
    'expires'         => '2030-01-01 23:59:59',
    'customer_email'  => 'buyer@example.test',
    'site_count'      => 1,
    'license_limit'   => 3,
);

function __( $value, $domain = '' ) { return $value; }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_email( $value ) { return filter_var( $value, FILTER_SANITIZE_EMAIL ); }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) ); }
function absint( $value ) { return abs( (int) $value ); }
function wp_parse_args( $args, $defaults = array() ) { return array_merge( $defaults, (array) $args ); }
function get_option( $key, $default = false ) {
    global $test_options;
    return array_key_exists( $key, $test_options ) ? $test_options[ $key ] : $default;
}
function update_option( $key, $value ) {
    global $test_options;
    $test_options[ $key ] = $value;
    return true;
}
function home_url() { return 'https://customer.example.test'; }
function add_query_arg( $args, $url ) { return $url . '?' . http_build_query( $args ); }
function wp_safe_remote_get( $url, $args = array() ) {
    global $test_requests, $test_api_reply;
    $test_requests[] = $url;
    return array(
        'response' => array( 'code' => 200 ),
        'body'     => json_encode( $test_api_reply ),
    );
}
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function wp_remote_retrieve_response_code( $response ) { return $response['response']['code']; }
function wp_remote_retrieve_body( $response ) { return $response['body']; }
function set_transient( $key, $value, $expiration ) {
    global $test_transients;
    $test_transients[ $key ] = $value;
}
function get_transient( $key ) {
    global $test_transients;
    return isset( $test_transients[ $key ] ) ? $test_transients[ $key ] : false;
}
function delete_transient( $key ) {
    global $test_transients;
    unset( $test_transients[ $key ] );
}

require __DIR__ . '/../includes/class-usp-security.php';

function license_check( $condition, $message ) {
    if ( ! $condition ) {
        throw new RuntimeException( $message );
    }
}

USP_Security::save_settings( array( 'edd_item_id' => 456 ) );
$activation = USP_Security::process_license_action( 'TEST-KEY-123456', 'activate' );
license_check( ! empty( $activation['success'] ), 'Valid EDD response should activate the license.' );
license_check( USP_Security::is_pro_active(), 'Activated license should unlock Pro.' );
license_check( count( $test_requests ) === 1, 'Freshly activated license should use its cached validation.' );
parse_str( (string) parse_url( $test_requests[0], PHP_URL_QUERY ), $request_args );
license_check( 'activate_license' === $request_args['edd_action'], 'Activation must call the EDD activation action.' );
license_check( '456' === $request_args['item_id'], 'Activation must use the configured EDD Download ID.' );
license_check( 'TEST-KEY-123456' === $request_args['license'], 'Activation must send the submitted key.' );
license_check( 'https://customer.example.test' === $request_args['url'], 'Activation must bind the license to this site URL.' );

$settings = USP_Security::get_settings();
$settings['license_last_checked'] = time() - DAY_IN_SECONDS - 1;
USP_Security::save_settings( $settings );
$test_api_reply = array(
    'success' => false,
    'license' => 'invalid',
    'error'   => 'expired',
);
license_check( ! USP_Security::is_pro_active(), 'An expired EDD license must disable Pro.' );
license_check( 'expired' === USP_Security::get_settings()['license_status'], 'Expired status should be retained for the dashboard.' );

$deactivation = USP_Security::process_license_action( '', 'deactivate' );
license_check( ! empty( $deactivation['success'] ), 'Local deactivation should complete.' );
license_check( ! USP_Security::is_pro_active(), 'Deactivation should disable Pro.' );

USP_Security::save_settings( array( 'edd_item_id' => 0 ) );
$missing_product = USP_Security::process_license_action( 'TEST-KEY-123456', 'activate' );
license_check( empty( $missing_product['success'] ), 'Activation must fail without a configured EDD product ID.' );
license_check( false !== strpos( $missing_product['message'], 'Download ID' ), 'Missing product configuration should explain the required setting.' );

$invalid_key = USP_Security::process_license_action( 'bad key', 'activate' );
license_check( empty( $invalid_key['success'] ), 'Malformed keys must be rejected.' );

echo "Passed EDD license activation, validation, expiry, deactivation, and configuration checks.\n";
