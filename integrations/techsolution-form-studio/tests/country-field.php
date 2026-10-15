<?php
// Run with: php tests/country-field.php. Small WordPress shims isolate rendering.
define( 'ABSPATH', __DIR__ );
function __( $value, $domain = '' ) { return $value; }
function esc_attr( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_html( $value ) { return esc_attr( $value ); }
function esc_attr_e( $value, $domain = '' ) { echo esc_attr( $value ); }
function esc_html_e( $value, $domain = '' ) { echo esc_html( $value ); }
function esc_html__( $value, $domain = '' ) { return $value; }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_]/', '', strtolower( $value ) ); }
function apply_filters( $hook, $value ) { return $value; }
function has_action( $hook ) { return false; }
function do_action( $hook ) {}
require __DIR__ . '/../includes/class-tsfs-form.php';
function check( $condition, $message ) {
    if ( ! $condition ) { throw new RuntimeException( $message ); }
}
$countries = TSFS_Form::get_countries();
check( count( $countries ) === 249, 'Country dataset should include 249 territories.' );
check( $countries['PK'] === 'Pakistan', 'Pakistan missing.' );
check( $countries['US'] === 'United States', 'United States missing.' );
check( count( array_unique( $countries ) ) === count( $countries ), 'Names must be unique.' );
foreach ( array( 'Pakistan', 'United States', '', null ) as $value ) {
    check( TSFS_Form::is_valid_country_value( $value ), 'Valid answer rejected.' );
}
foreach ( array( 'Invented country', '<script>alert(1)</script>', array( 'Pakistan' ), 123 ) as $value ) {
    check( ! TSFS_Form::is_valid_country_value( $value ), 'Invalid answer accepted.' );
}
check( isset( TSFS_Form::get_supported_field_types()['country'] ), 'Country must be supported.' );
foreach ( array( 'file', 'rating', 'consent', 'country', 'range', 'page_break' ) as $type ) {
    check( in_array( $type, TSFS_Form::get_core_field_types(), true ), $type . ' must be a core (free) field.' );
}
foreach ( array( 'signature', 'calculation', 'address', 'intl_tel', 'payment', 'admission' ) as $type ) {
    check( ! TSFS_Form::is_known_field_type( $type ), $type . ' must only exist when the Pro add-on registers it.' );
}
$render = new ReflectionMethod( 'TSFS_Form', 'render_field' );
$render->setAccessible( true );
ob_start();
$render->invoke( null, array(
    'type' => 'country', 'name' => 'nationality', 'label' => 'Nationality',
    'required' => true, 'placeholder' => 'Choose your country',
), 7, 0 );
$html = ob_get_clean();
check( substr_count( $html, '<option ' ) === 250, 'All choices plus placeholder must render.' );
check( strpos( $html, 'name="nationality"' ) !== false, 'Submission name missing.' );
check( strpos( $html, 'tsfs-country-search' ) === false, 'Search must not render outside the enhanced dropdown.' );
check( strpos( $html, 'id="tsfs_7_nationality"' ) !== false, 'Native fallback identifier missing.' );
check( strpos( $html, 'value="Pakistan"' ) !== false, 'Country values must be readable names.' );
check( strpos( $html, 'required' ) !== false, 'Required field missing.' );
ob_start();
$render->invoke( null, array( 'type' => 'signature', 'name' => 'sig', 'label' => 'Sign' ), 7, 0 );
check( '' === trim( ob_get_clean() ), 'Unregistered add-on fields must render nothing.' );
echo "Passed country dataset, validation, and rendering checks.\n";
