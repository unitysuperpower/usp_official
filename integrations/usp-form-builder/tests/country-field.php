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
class USP_Security {
    public static $pro_active = false;
    public static function is_pro_active() { return self::$pro_active; }
}
require __DIR__ . '/../includes/class-usp-form.php';
function check( $condition, $message ) {
    if ( ! $condition ) { throw new RuntimeException( $message ); }
}
$countries = USP_Form::get_countries();
check( count( $countries ) === 249, 'Country dataset should include 249 territories.' );
check( $countries['PK'] === 'Pakistan', 'Pakistan missing.' );
check( $countries['US'] === 'United States', 'United States missing.' );
check( count( array_unique( $countries ) ) === count( $countries ), 'Names must be unique.' );
foreach ( array( 'Pakistan', 'United States', '', null ) as $value ) {
    check( USP_Form::is_valid_country_value( $value ), 'Valid answer rejected.' );
}
foreach ( array( 'Invented country', '<script>alert(1)</script>', array( 'Pakistan' ), 123 ) as $value ) {
    check( ! USP_Form::is_valid_country_value( $value ), 'Invalid answer accepted.' );
}
check( isset( USP_Form::get_supported_field_types()['country'] ), 'Country must be supported.' );
foreach ( array( 'file', 'rating', 'consent', 'country', 'range', 'page_break', 'signature', 'calculation' ) as $pro_type ) {
    check( USP_Form::is_pro_field_type( $pro_type ), $pro_type . ' should require Pro.' );
}
check( ! USP_Form::is_pro_field_type( 'text' ), 'Standard text must remain available in Free.' );
$render = new ReflectionMethod( 'USP_Form', 'render_field' );
$render->setAccessible( true );
USP_Security::$pro_active = true;
ob_start();
$render->invoke( null, array(
    'type' => 'country', 'name' => 'nationality', 'label' => 'Nationality',
    'required' => true, 'placeholder' => 'Choose your country',
), 7, 0 );
$html = ob_get_clean();
check( substr_count( $html, '<option ' ) === 250, 'All choices plus placeholder must render.' );
check( strpos( $html, 'name="nationality"' ) !== false, 'Submission name missing.' );
check( strpos( $html, 'usp-country-search' ) === false, 'Search must not render outside the enhanced dropdown.' );
check( strpos( $html, 'id="usp_7_nationality"' ) !== false, 'Native fallback identifier missing.' );
check( strpos( $html, 'value="Pakistan"' ) !== false, 'Country values must be readable names.' );
check( strpos( $html, 'required' ) !== false, 'Required field missing.' );
USP_Security::$pro_active = false;
ob_start();
$render->invoke( null, array( 'type' => 'country', 'name' => 'country', 'label' => 'Country' ), 7, 0 );
$locked_html = ob_get_clean();
check( strpos( $locked_html, 'requires an active USP Form Builder Pro license' ) !== false, 'Country fields must be locked without Pro.' );
echo "Passed country dataset, validation, and rendering checks.\n";
