<?php
// Run with: php tests/license-updater.php. Exercises license activation storage and update package checks with WordPress stubs.
define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'TSFS_PRO_VERSION', '2.0.0' );
define( 'TSFS_PRO_FILE', __FILE__ );
define( 'TSFS_PRO_BASENAME', 'techsolution-form-studio-pro/techsolution-form-studio-pro.php' );
define( 'TSFS_PRO_PLUGIN_URL', 'https://example.test/' );
define( 'TSFS_PRO_PRODUCT_SLUG', 'techsolution-form-studio-pro' );
define( 'TSFS_PRO_STORE_API', 'https://usp.com.pk/api/licenses/v1' );

class WP_Error {
    private $message;
    public function __construct( $code, $message = '' ) { $this->message = $message; }
    public function get_error_message() { return $this->message; }
}
$options    = array();
$transients = array();
$reply      = array();
function __( $v ) { return $v; }
function esc_html__( $v ) { return $v; }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
function get_option( $k, $d = false ) { global $options; return isset( $options[ $k ] ) ? $options[ $k ] : $d; }
function update_option( $k, $v ) { global $options; $options[ $k ] = $v; return true; }
function get_site_transient( $k ) { global $transients; return isset( $transients[ $k ] ) ? $transients[ $k ] : false; }
function set_site_transient( $k, $v ) { global $transients; $transients[ $k ] = $v; }
function delete_site_transient( $k ) { global $transients; unset( $transients[ $k ] ); }
function wp_parse_args( $a, $d ) { return array_merge( $d, (array) $a ); }
function wp_parse_url( $u ) { return parse_url( $u ); }
function home_url() { return 'https://client.example/shop'; }
function wp_json_encode( $v ) { return json_encode( $v ); }
function wp_safe_remote_post( $url, $args ) { global $reply; return array( 'code' => 200, 'body' => json_encode( $reply ) ); }
function wp_remote_retrieve_response_code( $r ) { return $r['code']; }
function wp_remote_retrieve_body( $r ) { return $r['body']; }
function sanitize_text_field( $v ) { return trim( strip_tags( (string) $v ) ); }
function sanitize_email( $v ) { return filter_var( $v, FILTER_SANITIZE_EMAIL ); }
function sanitize_key( $v ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $v ) ); }
function absint( $v ) { return abs( (int) $v ); }
function esc_url_raw( $v ) { return $v; }
function wp_kses_post( $v ) { return $v; }
function add_action() {}
function add_filter() {}
function register_deactivation_hook() {}
require __DIR__ . '/../includes/class-tsfs-pro-license.php';
require __DIR__ . '/../includes/class-tsfs-pro-updater.php';

function check( $ok, $msg ) {
    if ( ! $ok ) {
        fwrite( STDERR, "FAIL: $msg\n" );
        exit( 1 );
    }
}
function call_private( $class, $method, ...$args ) {
    $m = new ReflectionMethod( $class, $method );
    $m->setAccessible( true );
    return $m->invoke( null, ...$args );
}

// Expired entitlement is rejected.
$reply = array( 'success' => true, 'license' => 'valid', 'expires' => '2001-01-01', 'activation_token' => str_repeat( 'a', 64 ) );
check( is_wp_error( TSFS_Pro_License::request( 'activate_license', 'KEY' ) ), 'Expired entitlement must be rejected.' );

// Valid activation is stored and bound to this site.
$reply = array( 'success' => true, 'license' => 'valid', 'expires' => '2099-01-01', 'activation_token' => str_repeat( 'b', 64 ), 'site_count' => 1, 'license_limit' => 3 );
$data  = TSFS_Pro_License::request( 'activate_license', 'KEY' );
check( is_array( $data ), 'Valid activation must succeed.' );
call_private( 'TSFS_Pro_License', 'store_response', 'KEY', $data );
check( TSFS_Pro_License::is_valid(), 'Stored license must be valid for this site.' );
check( 'client.example/shop' === TSFS_Pro_License::get()['site'], 'Site identity must include the path.' );

// Update packages must come from the store host for this product, with a checksum.
$good = array( 'package' => 'https://usp.com.pk/api/licenses/packages/1?signature=x', 'slug' => 'techsolution-form-studio-pro', 'version' => '2.1.0', 'sha256' => str_repeat( 'c', 64 ) );
check( is_array( call_private( 'TSFS_Pro_Updater', 'validate', $good ) ), 'Store package must be accepted.' );
foreach ( array( array( 'package' => 'https://evil.example/p.zip' ), array( 'package' => 'http://usp.com.pk/p.zip' ), array( 'slug' => 'other-plugin' ), array( 'sha256' => 'abc' ) ) as $bad ) {
    check( null === call_private( 'TSFS_Pro_Updater', 'validate', array_merge( $good, $bad ) ), 'Untrusted package accepted: ' . json_encode( $bad ) );
}

// Updates are offered only for this plugin and only for newer versions.
$reply = array( 'success' => true, 'license' => 'valid', 'expires' => '2099-01-01', 'activation_token' => str_repeat( 'b', 64 ), 'update' => $good );
$offer = TSFS_Pro_Updater::check( false, array(), TSFS_PRO_BASENAME );
check( is_array( $offer ) && '2.1.0' === $offer['version'], 'Newer release must be offered.' );
check( false === TSFS_Pro_Updater::check( false, array(), 'other/plugin.php' ), 'Other plugins must be ignored.' );

echo "Passed license storage, entitlement checks, package validation and update offers.\n";
