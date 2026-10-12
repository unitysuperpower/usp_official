<?php
/**
 * Run with: php tests/store-license-client.php.
 * Exercises the USP product-store license client using a local HTTP API stub.
 */

define( 'DAY_IN_SECONDS', 86400 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'ABSPATH', __DIR__ . '/' );
define( 'USP_FORM_BUILDER_PRODUCT_SLUG', 'usp-form-builder' );

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
    'expires'         => '2030-01-01T23:59:59+00:00',
    'site_count'      => 1,
    'license_limit'   => 3,
    'activation_token'=> 'activation-token-1',
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
function wp_json_encode( $data ) { return json_encode( $data ); }
function wp_safe_remote_post( $url, $args = array() ) {
    global $test_requests, $test_api_reply;
    $test_requests[] = array( 'url' => $url, 'args' => $args );
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

$activation = USP_Security::process_license_action( 'TEST-KEY-123456', 'activate' );
license_check( ! empty( $activation['success'] ), 'Valid store response should activate the license.' );
license_check( USP_Security::is_pro_active(), 'Activated license should unlock Pro.' );
license_check( count( $test_requests ) === 1, 'Freshly activated license should use its cached validation.' );
$request = $test_requests[0];
$request_args = json_decode( $request['args']['body'], true );
license_check( 'https://usp.com.pk/api/licenses/v1' === $request['url'], 'License requests must use the fixed HTTPS store endpoint.' );
license_check( 'application/json' === $request['args']['headers']['Content-Type'], 'License requests must use JSON.' );
license_check( 'activate_license' === $request_args['action'], 'Activation must use the store activation action.' );
license_check( 'usp-form-builder' === $request_args['product'], 'Activation must be scoped to the configured product slug.' );
license_check( 'TEST-KEY-123456' === $request_args['license'], 'Activation must send the submitted key.' );
license_check( 'https://customer.example.test' === $request_args['url'], 'Activation must bind the license to this site URL.' );
license_check( 'activation-token-1' === USP_Security::get_settings()['license_activation_token'], 'Activation token must be retained for authenticated follow-up checks.' );

$settings = USP_Security::get_settings();
$settings['license_last_checked'] = time() - DAY_IN_SECONDS - 1;
USP_Security::save_settings( $settings );
$test_api_reply = array(
    'success' => false,
    'license' => 'invalid',
    'error'   => 'expired',
);
license_check( ! USP_Security::is_pro_active(), 'An expired store license must disable Pro.' );
license_check( 'expired' === USP_Security::get_settings()['license_status'], 'Expired status should be retained for the dashboard.' );
$request_args = json_decode( end( $test_requests )['args']['body'], true );
license_check( 'activation-token-1' === $request_args['token'], 'Status checks must prove control of the activation token.' );

$deactivation = USP_Security::process_license_action( '', 'deactivate' );
license_check( ! empty( $deactivation['success'] ), 'Local deactivation should complete.' );
license_check( ! USP_Security::is_pro_active(), 'Deactivation should disable Pro.' );
$request_args = json_decode( end( $test_requests )['args']['body'], true );
license_check( 'deactivate_license' === $request_args['action'], 'Deactivation must use the store deactivation action.' );
license_check( 'activation-token-1' === $request_args['token'], 'Deactivation must prove control of the current activation.' );
license_check( '' === USP_Security::get_settings()['license_activation_token'], 'Deactivation must erase the local activation token.' );

$invalid_key = USP_Security::process_license_action( 'bad key', 'activate' );
license_check( empty( $invalid_key['success'] ), 'Malformed keys must be rejected.' );

echo "Passed store license activation, product scoping, token validation, expiry, deactivation, and configuration checks.\n";
