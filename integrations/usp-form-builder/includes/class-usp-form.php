<?php
/**
 * Advanced Form definitions, conditional logic, validation, and rendering engine.
 *
 * @package    USP_Form_Builder
 * @subpackage USP_Form_Builder/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class USP_Form {

    /**
     * Get the bundled alphabetical country and territory list.
     */
    public static function get_countries() {
        static $countries = null;
        if ( null === $countries ) {
            $countries = require __DIR__ . '/countries.php';
        }
        return $countries;
    }

    public static function is_valid_country_value( $value ) {
        return null === $value || '' === $value ||
            ( is_string( $value ) && in_array( $value, self::get_countries(), true ) );
    }

    /**
     * Get field types that require an active Pro license.
     */
    public static function get_pro_field_types() {
        $pro_types = array();
        foreach ( self::get_supported_field_types() as $type => $definition ) {
            if ( isset( $definition['category'] ) && 'pro' === $definition['category'] ) {
                $pro_types[] = $type;
            }
        }
        return $pro_types;
    }

    /**
     * Check whether a field type is restricted to Pro.
     */
    public static function is_pro_field_type( $type ) {
        return in_array( $type, self::get_pro_field_types(), true );
    }

    /** Get supported field types and their default configurations. */
    public static function get_supported_field_types() {
        return array(
            'country' => array(
                'label' => __( 'Country / Nationality', 'usp-form-builder' ),
                'icon' => 'dashicons-admin-site-alt3',
                'category' => 'pro',
                'defaults' => array(
                    'label' => __( 'Country', 'usp-form-builder' ),
                    'placeholder' => __( 'Select a country', 'usp-form-builder' ),
                    'required' => false,
                    'help_text' => '',
                    'width' => '100',
                ),
            ),
            'text' => array(
                'label'       => __( 'Single Line Text', 'usp-form-builder' ),
                'icon'        => 'dashicons-editor-textcolor',
                'category'    => 'standard',
                'defaults'    => array(
                    'label'       => __( 'Text Field', 'usp-form-builder' ),
                    'placeholder' => '',
                    'required'    => false,
                    'help_text'   => '',
                    'width'       => '100',
                ),
            ),
            'email' => array(
                'label'       => __( 'Email Address', 'usp-form-builder' ),
                'icon'        => 'dashicons-email-alt',
                'category'    => 'standard',
                'defaults'    => array(
                    'label'       => __( 'Email Address', 'usp-form-builder' ),
                    'placeholder' => 'example@domain.com',
                    'required'    => true,
                    'help_text'   => '',
                    'width'       => '100',
                ),
            ),
            'textarea' => array(
                'label'       => __( 'Multi-line Paragraph', 'usp-form-builder' ),
                'icon'        => 'dashicons-editor-paragraph',
                'category'    => 'standard',
                'defaults'    => array(
                    'label'       => __( 'Paragraph / Message', 'usp-form-builder' ),
                    'placeholder' => '',
                    'required'    => false,
                    'help_text'   => '',
                    'rows'        => '4',
                    'width'       => '100',
                ),
            ),
            'number' => array(
                'label'       => __( 'Number', 'usp-form-builder' ),
                'icon'        => 'dashicons-calculator',
                'category'    => 'standard',
                'defaults'    => array(
                    'label'       => __( 'Number', 'usp-form-builder' ),
                    'placeholder' => '',
                    'required'    => false,
                    'min'         => '',
                    'max'         => '',
                    'width'       => '100',
                ),
            ),
            'tel' => array(
                'label'       => __( 'Phone Number', 'usp-form-builder' ),
                'icon'        => 'dashicons-phone',
                'category'    => 'standard',
                'defaults'    => array(
                    'label'       => __( 'Phone Number', 'usp-form-builder' ),
                    'placeholder' => '+1 (555) 000-0000',
                    'required'    => false,
                    'width'       => '100',
                ),
            ),
            'date' => array(
                'label'       => __( 'Date Picker', 'usp-form-builder' ),
                'icon'        => 'dashicons-calendar-alt',
                'category'    => 'standard',
                'defaults'    => array(
                    'label'       => __( 'Select Date', 'usp-form-builder' ),
                    'required'    => false,
                    'width'       => '100',
                ),
            ),
            'time' => array(
                'label'       => __( 'Time Picker', 'usp-form-builder' ),
                'icon'        => 'dashicons-clock',
                'category'    => 'standard',
                'defaults'    => array(
                    'label'       => __( 'Select Time', 'usp-form-builder' ),
                    'required'    => false,
                    'width'       => '100',
                ),
            ),
            'url' => array(
                'label'       => __( 'Website URL', 'usp-form-builder' ),
                'icon'        => 'dashicons-admin-links',
                'category'    => 'standard',
                'defaults'    => array(
                    'label'       => __( 'Website / Portfolio', 'usp-form-builder' ),
                    'placeholder' => 'https://example.com',
                    'required'    => false,
                    'width'       => '100',
                ),
            ),
            'file' => array(
                'label'       => __( 'File Upload', 'usp-form-builder' ),
                'icon'        => 'dashicons-upload',
                'category'    => 'pro',
                'defaults'    => array(
                    'label'       => __( 'Attach File / Document', 'usp-form-builder' ),
                    'required'    => false,
                    'allowed_ext' => 'pdf,doc,docx,jpg,jpeg,png',
                    'max_size'    => '5', // MB
                    'help_text'   => __( 'Allowed: PDF, DOC, DOCX, JPG, PNG (Max 5MB)', 'usp-form-builder' ),
                    'width'       => '100',
                ),
            ),
            'range' => array(
                'label'       => __( 'Range / Rating Slider', 'usp-form-builder' ),
                'icon'        => 'dashicons-leftright',
                'category'    => 'pro',
                'defaults'    => array(
                    'label'       => __( 'Satisfaction Rating', 'usp-form-builder' ),
                    'required'    => false,
                    'min'         => '1',
                    'max'         => '10',
                    'step'        => '1',
                    'default_val' => '5',
                    'width'       => '100',
                ),
            ),
            'hidden' => array(
                'label'       => __( 'Hidden Field', 'usp-form-builder' ),
                'icon'        => 'dashicons-hidden',
                'category'    => 'advanced',
                'defaults'    => array(
                    'label'       => __( 'Tracking Code', 'usp-form-builder' ),
                    'default_val' => '',
                ),
            ),
            'rating' => array(
                'label'       => __( 'Star Rating (1-5)', 'usp-form-builder' ),
                'icon'        => 'dashicons-star-filled',
                'category'    => 'pro',
                'defaults'    => array(
                    'label'       => __( 'Rate Your Experience', 'usp-form-builder' ),
                    'required'    => false,
                    'default_val' => '5',
                    'help_text'   => __( 'Click a star to rate from 1 to 5.', 'usp-form-builder' ),
                    'width'       => '100',
                ),
            ),
            'consent' => array(
                'label'       => __( 'GDPR / Terms Consent', 'usp-form-builder' ),
                'icon'        => 'dashicons-shield',
                'category'    => 'pro',
                'defaults'    => array(
                    'label'       => __( 'Privacy & Terms Agreement', 'usp-form-builder' ),
                    'required'    => true,
                    'terms_text'  => __( 'I agree to the privacy policy and terms of service.', 'usp-form-builder' ),
                    'terms_url'   => '#',
                    'help_text'   => '',
                    'width'       => '100',
                ),
            ),
            'select' => array(
                'label'       => __( 'Dropdown Menu', 'usp-form-builder' ),
                'icon'        => 'dashicons-arrow-down-alt2',
                'category'    => 'choice',
                'defaults'    => array(
                    'label'       => __( 'Dropdown Choice', 'usp-form-builder' ),
                    'required'    => false,
                    'options'     => array( 'Option 1', 'Option 2', 'Option 3' ),
                    'width'       => '100',
                ),
            ),
            'radio' => array(
                'label'       => __( 'Radio Buttons', 'usp-form-builder' ),
                'icon'        => 'dashicons-marker',
                'category'    => 'choice',
                'defaults'    => array(
                    'label'       => __( 'Radio Selection', 'usp-form-builder' ),
                    'required'    => false,
                    'options'     => array( 'Choice 1', 'Choice 2' ),
                    'width'       => '100',
                ),
            ),
            'checkbox' => array(
                'label'       => __( 'Checkboxes', 'usp-form-builder' ),
                'icon'        => 'dashicons-yes-alt',
                'category'    => 'choice',
                'defaults'    => array(
                    'label'       => __( 'Checkbox Options', 'usp-form-builder' ),
                    'required'    => false,
                    'options'     => array( 'Option A', 'Option B' ),
                    'width'       => '100',
                ),
            ),
            'html' => array(
                'label'       => __( 'Section Header / Note', 'usp-form-builder' ),
                'icon'        => 'dashicons-heading',
                'category'    => 'layout',
                'defaults'    => array(
                    'label'       => __( 'Section Title', 'usp-form-builder' ),
                    'content'     => __( 'Fill out the form below.', 'usp-form-builder' ),
                    'tag'         => 'h3',
                ),
            ),
            'address' => array(
                'label' => __( 'Complete Address', 'usp-form-builder' ),
                'icon' => 'dashicons-location-alt',
                'category' => 'pro',
                'defaults' => array( 'label' => __( 'Address', 'usp-form-builder' ), 'required' => false, 'width' => '100', 'address_show_street' => true, 'address_show_postal' => true ),
            ),
            'admission' => array(
                'label'       => __( 'University Admission', 'usp-form-builder' ),
                'icon'        => 'dashicons-welcome-learn-more',
                'category'    => 'pro',
                'defaults'    => array(
                    'label'       => __( 'Student Admission Application', 'usp-form-builder' ),
                    'required'    => true,
                    'help_text'   => __( 'Complete applicant academic and personal verification.', 'usp-form-builder' ),
                    'width'       => '100',
                ),
            ),
            'intl_tel' => array(
                'label'       => __( 'Global Phone (Flags)', 'usp-form-builder' ),
                'icon'        => 'dashicons-translation',
                'category'    => 'pro',
                'defaults'    => array(
                    'label'       => __( 'International Telephone', 'usp-form-builder' ),
                    'placeholder' => '300 1234567',
                    'required'    => true,
                    'help_text'   => __( 'Select country code with instant formatting.', 'usp-form-builder' ),
                    'width'       => '100',
                ),
            ),
            'payment' => array(
                'label'       => __( 'Payment Details (Non-processing)', 'usp-form-builder' ),
                'icon'        => 'dashicons-cart',
                'category'    => 'pro',
                'defaults'    => array(
                    'label'            => __( 'Application Fee Checkout', 'usp-form-builder' ),
                    'required'         => true,
                    'payment_amount'   => '50.00',
                    'payment_currency' => 'USD',
                    'help_text'        => __( 'Secured via 256-bit SSL encryption. Accepts all major debit & credit cards.', 'usp-form-builder' ),
                    'width'            => '100',
                ),
            ),
            'page_break' => array(
                'label'       => __( 'Page Break / Multi-Step', 'usp-form-builder' ),
                'icon'        => 'dashicons-editor-break',
                'category'    => 'pro',
                'defaults'    => array(
                    'label'         => __( 'Next Step', 'usp-form-builder' ),
                    'step_title'    => __( 'Step 2', 'usp-form-builder' ),
                    'btn_prev_text' => __( 'Previous', 'usp-form-builder' ),
                    'btn_next_text' => __( 'Next Step', 'usp-form-builder' ),
                    'width'         => '100',
                ),
            ),
            'signature' => array(
                'label'       => __( 'Digital Signature', 'usp-form-builder' ),
                'icon'        => 'dashicons-edit',
                'category'    => 'pro',
                'defaults'    => array(
                    'label'         => __( 'Digital Signature', 'usp-form-builder' ),
                    'required'      => true,
                    'canvas_height' => '160',
                    'pen_color'     => '#0f172a',
                    'help_text'     => __( 'Sign using your finger, mouse, or stylus within the signature pad.', 'usp-form-builder' ),
                    'width'         => '100',
                ),
            ),
            'calculation' => array(
                'label'       => __( 'Calculated Formula', 'usp-form-builder' ),
                'icon'        => 'dashicons-calculator',
                'category'    => 'pro',
                'defaults'    => array(
                    'label'       => __( 'Estimated Total Calculation', 'usp-form-builder' ),
                    'formula'     => '',
                    'calc_prefix' => '$',
                    'calc_suffix' => '',
                    'decimals'    => '2',
                    'help_text'   => __( 'Live calculation computed dynamically based on your inputs.', 'usp-form-builder' ),
                    'width'       => '100',
                ),
            ),
        );
    }

    /**
     * Comprehensive International Phone Number Rules (E.164 National Significant Numbers).
     * Provides ISO codes, dial codes, emojis, min/max national digits, masks, and placeholders.
     */
    public static function get_country_phone_rules() {
        return array(
            'PK' => array(
                'code'        => 'PK',
                'name'        => 'Pakistan',
                'dial'        => '+92',
                'flag'        => '🇵🇰',
                'min_digits'  => 10,
                'max_digits'  => 10,
                'mask'        => 'XXX XXXXXXX',
                'placeholder' => '300 1234567',
            ),
            'US' => array(
                'code'        => 'US',
                'name'        => 'United States',
                'dial'        => '+1',
                'flag'        => '🇺🇸',
                'min_digits'  => 10,
                'max_digits'  => 10,
                'mask'        => '(XXX) XXX-XXXX',
                'placeholder' => '(555) 000-0000',
            ),
            'GB' => array(
                'code'        => 'GB',
                'name'        => 'United Kingdom',
                'dial'        => '+44',
                'flag'        => '🇬🇧',
                'min_digits'  => 10,
                'max_digits'  => 11,
                'mask'        => 'XXXX XXXXXX',
                'placeholder' => '7123 456789',
            ),
            'CA' => array(
                'code'        => 'CA',
                'name'        => 'Canada',
                'dial'        => '+1',
                'flag'        => '🇨🇦',
                'min_digits'  => 10,
                'max_digits'  => 10,
                'mask'        => '(XXX) XXX-XXXX',
                'placeholder' => '(555) 000-0000',
            ),
            'AE' => array(
                'code'        => 'AE',
                'name'        => 'United Arab Emirates',
                'dial'        => '+971',
                'flag'        => '🇦🇪',
                'min_digits'  => 9,
                'max_digits'  => 9,
                'mask'        => 'XX XXX XXXX',
                'placeholder' => '50 123 4567',
            ),
            'SA' => array(
                'code'        => 'SA',
                'name'        => 'Saudi Arabia',
                'dial'        => '+966',
                'flag'        => '🇸🇦',
                'min_digits'  => 9,
                'max_digits'  => 9,
                'mask'        => 'XX XXX XXXX',
                'placeholder' => '50 123 4567',
            ),
            'IN' => array(
                'code'        => 'IN',
                'name'        => 'India',
                'dial'        => '+91',
                'flag'        => '🇮🇳',
                'min_digits'  => 10,
                'max_digits'  => 10,
                'mask'        => 'XXXXX XXXXX',
                'placeholder' => '98765 43210',
            ),
            'DE' => array(
                'code'        => 'DE',
                'name'        => 'Germany',
                'dial'        => '+49',
                'flag'        => '🇩🇪',
                'min_digits'  => 10,
                'max_digits'  => 11,
                'mask'        => 'XXX XXXXXXXX',
                'placeholder' => '151 12345678',
            ),
            'FR' => array(
                'code'        => 'FR',
                'name'        => 'France',
                'dial'        => '+33',
                'flag'        => '🇫🇷',
                'min_digits'  => 9,
                'max_digits'  => 9,
                'mask'        => 'X XX XX XX XX',
                'placeholder' => '6 12 34 56 78',
            ),
            'AU' => array(
                'code'        => 'AU',
                'name'        => 'Australia',
                'dial'        => '+61',
                'flag'        => '🇦🇺',
                'min_digits'  => 9,
                'max_digits'  => 9,
                'mask'        => 'XXX XXX XXX',
                'placeholder' => '412 345 678',
            ),
            'CN' => array(
                'code'        => 'CN',
                'name'        => 'China',
                'dial'        => '+86',
                'flag'        => '🇨🇳',
                'min_digits'  => 11,
                'max_digits'  => 11,
                'mask'        => 'XXX XXXX XXXX',
                'placeholder' => '138 0013 8000',
            ),
            'TR' => array(
                'code'        => 'TR',
                'name'        => 'Turkey',
                'dial'        => '+90',
                'flag'        => '🇹🇷',
                'min_digits'  => 10,
                'max_digits'  => 10,
                'mask'        => 'XXX XXX XX XX',
                'placeholder' => '532 123 45 67',
            ),
            'QA' => array(
                'code'        => 'QA',
                'name'        => 'Qatar',
                'dial'        => '+974',
                'flag'        => '🇶🇦',
                'min_digits'  => 8,
                'max_digits'  => 8,
                'mask'        => 'XXXX XXXX',
                'placeholder' => '3312 3456',
            ),
            'OM' => array(
                'code'        => 'OM',
                'name'        => 'Oman',
                'dial'        => '+968',
                'flag'        => '🇴🇲',
                'min_digits'  => 8,
                'max_digits'  => 8,
                'mask'        => 'XXXX XXXX',
                'placeholder' => '9123 4567',
            ),
            'KW' => array(
                'code'        => 'KW',
                'name'        => 'Kuwait',
                'dial'        => '+965',
                'flag'        => '🇰🇼',
                'min_digits'  => 8,
                'max_digits'  => 8,
                'mask'        => 'XXXX XXXX',
                'placeholder' => '9123 4567',
            ),
            'BH' => array(
                'code'        => 'BH',
                'name'        => 'Bahrain',
                'dial'        => '+973',
                'flag'        => '🇧🇭',
                'min_digits'  => 8,
                'max_digits'  => 8,
                'mask'        => 'XXXX XXXX',
                'placeholder' => '3612 3456',
            ),
            'BD' => array(
                'code'        => 'BD',
                'name'        => 'Bangladesh',
                'dial'        => '+880',
                'flag'        => '🇧🇩',
                'min_digits'  => 10,
                'max_digits'  => 10,
                'mask'        => 'XXXX XXXXXX',
                'placeholder' => '1712 345678',
            ),
            'MY' => array(
                'code'        => 'MY',
                'name'        => 'Malaysia',
                'dial'        => '+60',
                'flag'        => '🇲🇾',
                'min_digits'  => 9,
                'max_digits'  => 10,
                'mask'        => 'XX XXX XXXX',
                'placeholder' => '12 345 6789',
            ),
            'SG' => array(
                'code'        => 'SG',
                'name'        => 'Singapore',
                'dial'        => '+65',
                'flag'        => '🇸🇬',
                'min_digits'  => 8,
                'max_digits'  => 8,
                'mask'        => 'XXXX XXXX',
                'placeholder' => '8123 4567',
            ),
            'JP' => array(
                'code'        => 'JP',
                'name'        => 'Japan',
                'dial'        => '+81',
                'flag'        => '🇯🇵',
                'min_digits'  => 10,
                'max_digits'  => 11,
                'mask'        => 'XX XXXX XXXX',
                'placeholder' => '90 1234 5678',
            ),
            'BR' => array(
                'code'        => 'BR',
                'name'        => 'Brazil',
                'dial'        => '+55',
                'flag'        => '🇧🇷',
                'min_digits'  => 10,
                'max_digits'  => 11,
                'mask'        => '(XX) XXXXX-XXXX',
                'placeholder' => '(11) 98765-4321',
            ),
            'ZA' => array(
                'code'        => 'ZA',
                'name'        => 'South Africa',
                'dial'        => '+27',
                'flag'        => '🇿🇦',
                'min_digits'  => 9,
                'max_digits'  => 9,
                'mask'        => 'XX XXX XXXX',
                'placeholder' => '82 123 4567',
            ),
            'ES' => array(
                'code'        => 'ES',
                'name'        => 'Spain',
                'dial'        => '+34',
                'flag'        => '🇪🇸',
                'min_digits'  => 9,
                'max_digits'  => 9,
                'mask'        => 'XXX XX XX XX',
                'placeholder' => '612 34 56 78',
            ),
            'IT' => array(
                'code'        => 'IT',
                'name'        => 'Italy',
                'dial'        => '+39',
                'flag'        => '🇮🇹',
                'min_digits'  => 9,
                'max_digits'  => 10,
                'mask'        => 'XXX XXXXXXX',
                'placeholder' => '320 1234567',
            ),
            'NL' => array(
                'code'        => 'NL',
                'name'        => 'Netherlands',
                'dial'        => '+31',
                'flag'        => '🇳🇱',
                'min_digits'  => 9,
                'max_digits'  => 9,
                'mask'        => 'X XX XX XX XX',
                'placeholder' => '6 12 34 56 78',
            ),
            'RU' => array(
                'code'        => 'RU',
                'name'        => 'Russia',
                'dial'        => '+7',
                'flag'        => '🇷🇺',
                'min_digits'  => 10,
                'max_digits'  => 10,
                'mask'        => '(XXX) XXX-XX-XX',
                'placeholder' => '(912) 345-67-89',
            ),
            'EG' => array(
                'code'        => 'EG',
                'name'        => 'Egypt',
                'dial'        => '+20',
                'flag'        => '🇪🇬',
                'min_digits'  => 10,
                'max_digits'  => 10,
                'mask'        => 'XX XXXX XXXX',
                'placeholder' => '10 1234 5678',
            ),
            'NG' => array(
                'code'        => 'NG',
                'name'        => 'Nigeria',
                'dial'        => '+234',
                'flag'        => '🇳🇬',
                'min_digits'  => 10,
                'max_digits'  => 10,
                'mask'        => 'XXX XXX XXXX',
                'placeholder' => '802 123 4567',
            ),
            'ID' => array(
                'code'        => 'ID',
                'name'        => 'Indonesia',
                'dial'        => '+62',
                'flag'        => '🇮🇩',
                'min_digits'  => 9,
                'max_digits'  => 12,
                'mask'        => 'XXX XXXX XXXX',
                'placeholder' => '812 3456 7890',
            ),
            'PH' => array(
                'code'        => 'PH',
                'name'        => 'Philippines',
                'dial'        => '+63',
                'flag'        => '🇵🇭',
                'min_digits'  => 10,
                'max_digits'  => 10,
                'mask'        => 'XXX XXX XXXX',
                'placeholder' => '917 123 4567',
            ),
            'KR' => array(
                'code'        => 'KR',
                'name'        => 'South Korea',
                'dial'        => '+82',
                'flag'        => '🇰🇷',
                'min_digits'  => 9,
                'max_digits'  => 10,
                'mask'        => 'XX XXXX XXXX',
                'placeholder' => '10 1234 5678',
            ),
            'MX' => array(
                'code'        => 'MX',
                'name'        => 'Mexico',
                'dial'        => '+52',
                'flag'        => '🇲🇽',
                'min_digits'  => 10,
                'max_digits'  => 10,
                'mask'        => 'XXX XXX XXXX',
                'placeholder' => '55 1234 5678',
            ),
        );
    }

    /**
     * Resolve country phone rule by dial code and optional country ISO code.
     */
    public static function get_country_rule_by_dial( $dial_code, $country_code = '' ) {
        $rules = self::get_country_phone_rules();
        $code  = strtoupper( trim( $country_code ) );

        if ( $code && isset( $rules[ $code ] ) ) {
            return $rules[ $code ];
        }

        $clean_dial = trim( $dial_code );
        if ( '' !== $clean_dial && '+' !== substr( $clean_dial, 0, 1 ) ) {
            $clean_dial = '+' . $clean_dial;
        }

        foreach ( $rules as $iso => $rule ) {
            if ( $rule['dial'] === $clean_dial ) {
                return $rule;
            }
        }

        // Generic fallback E.164 compliance
        return array(
            'code'        => $code ? $code : 'INTL',
            'name'        => 'International',
            'dial'        => $clean_dial ? $clean_dial : '+',
            'flag'        => '🌐',
            'min_digits'  => 7,
            'max_digits'  => 15,
            'mask'        => 'XXXXXXXXXXXXXXX',
            'placeholder' => 'Phone number',
        );
    }

    /**
     * Render the frontend HTML for a form.
     */
    public static function render_form_html( $form_id, $display_title = true ) {
        $form = USP_DB::get_form( $form_id );

        if ( ! $form || 'publish' !== $form['status'] ) {
            return '<!-- USP Form: Form not found or unpublished. -->';
        }

        $fields   = ! empty( $form['fields_data'] ) ? $form['fields_data'] : array();
        $settings = ! empty( $form['settings'] ) ? $form['settings'] : array();

        foreach ( $fields as $field ) {
            $field_type = isset( $field['type'] ) ? $field['type'] : '';
            if ( self::is_pro_field_type( $field_type ) && ! USP_Security::is_pro_active() ) {
                return '<div class="usp-form-container usp-form-notice-box" role="status" style="text-align:center; padding:32px 20px;">' .
                    '<p style="margin:0; font-weight:600; color:#64748b;">' .
                    esc_html__( 'This form includes Pro fields and is temporarily unavailable because its Pro license is inactive.', 'usp-form-builder' ) .
                    '</p></div>';
            }
        }

        // 0. Track Form Impression (Analytics)
        if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
            USP_DB::increment_views( $form_id );
        }

        // 1. Check Login Restriction
        if ( ! empty( $settings['require_login'] ) && ! is_user_logged_in() ) {
            $login_msg = ! empty( $settings['login_message'] ) ? $settings['login_message'] : __( 'You must be logged in to view and submit this form.', 'usp-form-builder' );
            $login_url = wp_login_url( get_permalink() );
            return '<div class="usp-form-container usp-form-notice-box" style="text-align:center; padding:32px 20px;">' .
                   '<p style="margin-bottom:14px; font-weight:600; color:#334155;">' . esc_html( $login_msg ) . '</p>' .
                   '<a href="' . esc_url( $login_url ) . '" class="button button-primary" style="display:inline-block; padding:8px 20px; text-decoration:none; background:#4f46e5; color:#fff; border-radius:6px; font-weight:600;">' . esc_html__( 'Log In', 'usp-form-builder' ) . '</a>' .
                   '</div>';
        }

        // 2. Check Expiration Date
        if ( ! empty( $settings['close_after_date'] ) && ! empty( $settings['close_date'] ) ) {
            if ( current_time( 'timestamp' ) > strtotime( $settings['close_date'] . ' 23:59:59' ) ) {
                $closed_msg = ! empty( $settings['closed_message'] ) ? $settings['closed_message'] : __( 'This form is currently closed for submissions.', 'usp-form-builder' );
                return '<div class="usp-form-container usp-form-notice-box" style="text-align:center; padding:32px 20px;">' .
                       '<p style="margin:0; font-weight:600; color:#64748b;">' . esc_html( $closed_msg ) . '</p>' .
                       '</div>';
            }
        }

        // 3. Check Submission Limit
        if ( ! empty( $settings['limit_submissions'] ) && ! empty( $settings['max_entries'] ) ) {
            $entry_counts = USP_DB::get_status_counts( $form_id );
            if ( (int) $entry_counts['all'] >= (int) $settings['max_entries'] ) {
                $closed_msg = ! empty( $settings['closed_message'] ) ? $settings['closed_message'] : __( 'This form has reached its maximum submission limit.', 'usp-form-builder' );
                return '<div class="usp-form-container usp-form-notice-box" style="text-align:center; padding:32px 20px;">' .
                       '<p style="margin:0; font-weight:600; color:#64748b;">' . esc_html( $closed_msg ) . '</p>' .
                       '</div>';
            }
        }

        $submit_btn_text = ! empty( $settings['submit_button_text'] ) ? $settings['submit_button_text'] : __( 'Submit', 'usp-form-builder' );
        $form_wrapper_id = 'usp-form-' . esc_attr( $form_id );

        // Theme Customization Attributes
        $theme_preset   = ! empty( $settings['theme_preset'] ) ? sanitize_key( $settings['theme_preset'] ) : 'default';
        $border_radius  = ! empty( $settings['border_radius'] ) ? sanitize_key( $settings['border_radius'] ) : 'rounded';
        $button_align   = ! empty( $settings['button_align'] ) ? sanitize_key( $settings['button_align'] ) : 'left';
        $shadow_style   = ! empty( $settings['shadow_style'] ) ? sanitize_key( $settings['shadow_style'] ) : 'medium';
        $primary_color  = ! empty( $settings['primary_color'] ) ? sanitize_hex_color( $settings['primary_color'] ) : '';
        $btn_text_color = ! empty( $settings['btn_text_color'] ) ? sanitize_hex_color( $settings['btn_text_color'] ) : '';
        $container_bg   = ! empty( $settings['container_bg'] ) ? sanitize_text_field( $settings['container_bg'] ) : '';
        $custom_css     = ! empty( $settings['custom_css'] ) ? wp_strip_all_tags( $settings['custom_css'] ) : '';

        $css_vars = array();
        if ( $primary_color ) {
            $css_vars[] = '--usp-primary: ' . $primary_color . ';';
            $css_vars[] = '--usp-primary-hover: ' . $primary_color . ';';
            $css_vars[] = '--usp-border-focus: ' . $primary_color . ';';
        }
        if ( $btn_text_color ) {
            $css_vars[] = '--usp-btn-color: ' . $btn_text_color . ';';
        }
        if ( $container_bg && 'transparent' !== strtolower( $container_bg ) ) {
            $css_vars[] = '--usp-bg-card: ' . $container_bg . ';';
        } elseif ( 'transparent' === strtolower( $container_bg ) ) {
            $css_vars[] = '--usp-bg-card: transparent;';
        }
        $inline_style = ! empty( $css_vars ) ? 'style="' . esc_attr( implode( ' ', $css_vars ) ) . '"' : '';

        // Multi-Step Wizard Partitioning
        $has_steps = false;
        foreach ( $fields as $f ) {
            if ( isset( $f['type'] ) && 'page_break' === $f['type'] ) {
                $has_steps = true;
                break;
            }
        }

        $steps = array();
        if ( $has_steps ) {
            $current_step = 1;
            $steps[1] = array(
                'title'     => __( 'Step 1', 'usp-form-builder' ),
                'prev_text' => __( 'Previous', 'usp-form-builder' ),
                'next_text' => __( 'Next Step', 'usp-form-builder' ),
                'fields'    => array(),
            );

            foreach ( $fields as $index => $field ) {
                if ( isset( $field['type'] ) && 'page_break' === $field['type'] ) {
                    $current_step++;
                    $step_title = ! empty( $field['step_title'] ) ? $field['step_title'] : ( ! empty( $field['label'] ) ? $field['label'] : sprintf( __( 'Step %d', 'usp-form-builder' ), $current_step ) );
                    $steps[ $current_step ] = array(
                        'title'     => $step_title,
                        'prev_text' => ! empty( $field['btn_prev_text'] ) ? $field['btn_prev_text'] : __( 'Previous', 'usp-form-builder' ),
                        'next_text' => ! empty( $field['btn_next_text'] ) ? $field['btn_next_text'] : __( 'Next Step', 'usp-form-builder' ),
                        'fields'    => array(),
                    );
                } else {
                    $steps[ $current_step ]['fields'][] = array( 'field' => $field, 'index' => $index );
                }
            }
            $total_steps = count( $steps );
        }

        $container_classes = array(
            'usp-form-container',
            'usp-theme-' . $theme_preset,
            'usp-radius-' . $border_radius,
            'usp-shadow-' . $shadow_style,
        );
        if ( $has_steps ) {
            $container_classes[] = 'usp-has-multistep';
        }

        $sec_settings = USP_Security::get_settings();

        ob_start();
        ?>
        <?php if ( ! empty( $custom_css ) ) : 
            $scoped_css = str_replace( '.usp-form-container', '#' . $form_wrapper_id, $custom_css );
        ?>
            <style id="<?php echo esc_attr( $form_wrapper_id ); ?>-custom-css">
                <?php echo wp_strip_all_tags( $scoped_css ); ?>
            </style>
        <?php endif; ?>
        <div class="<?php echo esc_attr( implode( ' ', $container_classes ) ); ?>" id="<?php echo esc_attr( $form_wrapper_id ); ?>" <?php echo $inline_style; ?>>
            <?php if ( $display_title && ! empty( $form['title'] ) ) : ?>
                <h3 class="usp-form-heading"><?php echo esc_html( $form['title'] ); ?></h3>
            <?php endif; ?>

            <form class="usp-form" method="post" action="" enctype="multipart/form-data" data-form-id="<?php echo esc_attr( $form_id ); ?>" novalidate>
                <?php wp_nonce_field( 'usp_form_submit_' . $form_id, 'usp_nonce' ); ?>
                <input type="hidden" name="action" value="usp_submit_form">
                <input type="hidden" name="form_id" value="<?php echo esc_attr( $form_id ); ?>">

                <!-- Anti-spam Honeypot Field -->
                <div class="usp-hp-field" style="display:none !important;" aria-hidden="true">
                    <label for="usp_hp_<?php echo esc_attr( $form_id ); ?>">Leave this field blank</label>
                    <input type="text" name="usp_hp_field" id="usp_hp_<?php echo esc_attr( $form_id ); ?>" tabindex="-1" autocomplete="off">
                </div>

                <!-- Anti-spam Time-Gate Bot Trap Token -->
                <?php $time_token = USP_Security::generate_time_token( $form_id ); ?>
                <input type="hidden" name="usp_time_token" value="<?php echo esc_attr( $time_token ); ?>">

                <?php if ( $has_steps ) : ?>
                    <!-- Multi-Step Wizard Progress Bar & Stepper -->
                    <div class="usp-steps-wrapper" data-total-steps="<?php echo esc_attr( $total_steps ); ?>">
                        <div class="usp-steps-progress-container">
                            <div class="usp-steps-progress-bar" style="width: <?php echo esc_attr( round( ( 1 / $total_steps ) * 100 ) ); ?>%;"></div>
                        </div>
                        <div class="usp-steps-indicators-bar">
                            <?php foreach ( $steps as $s_num => $s_data ) : 
                                $step_cls = ( 1 === $s_num ) ? 'is-active is-current' : '';
                            ?>
                                <div class="usp-step-indicator-item <?php echo esc_attr( $step_cls ); ?>" data-step="<?php echo esc_attr( $s_num ); ?>">
                                    <span class="usp-step-indicator-dot">
                                        <span class="usp-step-num-text"><?php echo esc_html( $s_num ); ?></span>
                                        <span class="usp-step-check-icon">✓</span>
                                    </span>
                                    <span class="usp-step-indicator-title"><?php echo esc_html( $s_data['title'] ); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="usp-multistep-container">
                        <?php foreach ( $steps as $s_num => $s_data ) : 
                            $is_first = ( 1 === $s_num );
                            $is_last  = ( $total_steps === $s_num );
                        ?>
                            <div class="usp-form-step <?php echo $is_first ? 'is-active' : ''; ?>" data-step="<?php echo esc_attr( $s_num ); ?>" style="<?php echo $is_first ? '' : 'display:none;'; ?>">
                                <div class="usp-form-fields-grid">
                                    <?php
                                    foreach ( $s_data['fields'] as $f_item ) {
                                        self::render_field( $f_item['field'], $form_id, $f_item['index'] );
                                    }
                                    ?>
                                </div>

                                <?php if ( $is_last ) : ?>
                                    <!-- Dynamic Anti-Spam CAPTCHA Shield on Final Step -->
                                    <?php self::render_captcha_widget( $sec_settings, $form_id ); ?>
                                <?php endif; ?>

                                <div class="usp-step-nav-actions usp-btn-align-<?php echo esc_attr( $button_align ); ?>">
                                    <?php if ( ! $is_first ) : ?>
                                        <button type="button" class="usp-step-nav-btn usp-btn-step-prev" data-step-nav="prev">
                                            <span class="dashicons dashicons-arrow-left-alt2"></span>
                                            <span><?php echo esc_html( $s_data['prev_text'] ); ?></span>
                                        </button>
                                    <?php endif; ?>

                                    <?php if ( ! $is_last ) : ?>
                                        <button type="button" class="usp-step-nav-btn usp-btn-step-next" data-step-nav="next">
                                            <span><?php echo esc_html( $s_data['next_text'] ); ?></span>
                                            <span class="dashicons dashicons-arrow-right-alt2"></span>
                                        </button>
                                    <?php else : ?>
                                        <button type="submit" class="usp-btn-submit">
                                            <span class="usp-btn-text"><?php echo esc_html( $submit_btn_text ); ?></span>
                                            <span class="usp-spinner" style="display:none;" aria-hidden="true"></span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <!-- Single-Page Standard Fields Grid -->
                    <div class="usp-form-fields-grid">
                        <?php
                        foreach ( $fields as $index => $field ) {
                            self::render_field( $field, $form_id, $index );
                        }
                        ?>
                    </div>

                    <!-- Dynamic Anti-Spam CAPTCHA Shield -->
                    <?php self::render_captcha_widget( $sec_settings, $form_id ); ?>

                    <div class="usp-form-actions usp-btn-align-<?php echo esc_attr( $button_align ); ?>">
                        <button type="submit" class="usp-btn-submit">
                            <span class="usp-btn-text"><?php echo esc_html( $submit_btn_text ); ?></span>
                            <span class="usp-spinner" style="display:none;" aria-hidden="true"></span>
                        </button>
                    </div>
                <?php endif; ?>

                <div class="usp-form-messages" style="display:none;"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render anti-spam CAPTCHA widget (Google reCAPTCHA v2/v3, Cloudflare Turnstile).
     */
    private static function render_captcha_widget( $sec_settings, $form_id ) {
        $anti_spam_provider = ! empty( $sec_settings['anti_spam_provider'] ) ? $sec_settings['anti_spam_provider'] : 'none';
        if ( 'recaptcha_v2' === $anti_spam_provider && ! empty( $sec_settings['recaptcha_site_key'] ) ) : ?>
            <div class="usp-captcha-box" style="margin: 16px 0;">
                <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                <div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $sec_settings['recaptcha_site_key'] ); ?>"></div>
            </div>
        <?php elseif ( 'turnstile' === $anti_spam_provider && ! empty( $sec_settings['turnstile_site_key'] ) ) : ?>
            <div class="usp-captcha-box" style="margin: 16px 0;">
                <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
                <div class="cf-turnstile" data-sitekey="<?php echo esc_attr( $sec_settings['turnstile_site_key'] ); ?>"></div>
            </div>
        <?php elseif ( 'recaptcha_v3' === $anti_spam_provider && ! empty( $sec_settings['recaptcha_site_key'] ) ) : ?>
            <script src="https://www.google.com/recaptcha/api.js?render=<?php echo esc_attr( $sec_settings['recaptcha_site_key'] ); ?>"></script>
            <input type="hidden" name="g-recaptcha-response" class="usp-recaptcha-v3-token" id="usp_recaptcha_v3_<?php echo esc_attr( $form_id ); ?>">
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof grecaptcha !== 'undefined') {
                    grecaptcha.ready(function() {
                        grecaptcha.execute('<?php echo esc_js( $sec_settings['recaptcha_site_key'] ); ?>', {action: 'submit'}).then(function(token) {
                            var el = document.getElementById('usp_recaptcha_v3_<?php echo esc_js( $form_id ); ?>');
                            if (el) el.value = token;
                        });
                    });
                }
            });
            </script>
        <?php endif;
    }

    /**
     * Render a single field element with conditional logic attributes.
     */
    private static function render_field( $field, $form_id, $index ) {
        $type        = isset( $field['type'] ) ? $field['type'] : 'text';
        $label       = isset( $field['label'] ) ? $field['label'] : '';
        $name        = ! empty( $field['name'] ) ? sanitize_key( $field['name'] ) : 'field_' . $index;
        $id          = 'usp_' . $form_id . '_' . $name;
        $required    = ! empty( $field['required'] );
        $placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
        $help_text   = isset( $field['help_text'] ) ? $field['help_text'] : '';
        $width       = isset( $field['width'] ) ? $field['width'] : '100';

        if ( self::is_pro_field_type( $type ) && ! USP_Security::is_pro_active() ) {
            echo '<div class="usp-field-wrap usp-pro-field-locked" role="status">' .
                esc_html__( 'This field requires an active USP Form Builder Pro license.', 'usp-form-builder' ) .
                '</div>';
            return;
        }

        // Hidden input
        if ( 'hidden' === $type ) {
            $def_val = isset( $field['default_val'] ) ? esc_attr( $field['default_val'] ) : '';
            echo '<input type="hidden" name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '" value="' . $def_val . '">';
            return;
        }

        // Conditional Logic Attributes
        $cond_attr = '';
        if ( ! empty( $field['enable_conditions'] ) && ! empty( $field['conditions'] ) ) {
            $cond_data = array(
                'action' => isset( $field['condition_action'] ) ? $field['condition_action'] : 'show',
                'match'  => isset( $field['condition_match'] ) ? $field['condition_match'] : 'all',
                'rules'  => $field['conditions'],
            );
            $cond_attr = ' data-conditions="' . esc_attr( wp_json_encode( $cond_data ) ) . '"';
        }

        // Section note / HTML
        if ( 'html' === $type ) {
            $tag     = ! empty( $field['tag'] ) ? sanitize_key( $field['tag'] ) : 'h3';
            $content = ! empty( $field['content'] ) ? wp_kses_post( $field['content'] ) : '';
            ?>
            <div class="usp-field-wrap usp-field-html usp-width-100" data-field-name="<?php echo esc_attr( $name ); ?>"<?php echo $cond_attr; ?>>
                <<?php echo esc_html( $tag ); ?> class="usp-html-title"><?php echo esc_html( $label ); ?></<?php echo esc_html( $tag ); ?>>
                <?php if ( $content ) : ?>
                    <div class="usp-html-desc"><?php echo wp_kses_post( $content ); ?></div>
                <?php endif; ?>
            </div>
            <?php
            return;
        }

        // Page break divider (if rendered standalone)
        if ( 'page_break' === $type ) {
            $step_title = ! empty( $field['step_title'] ) ? $field['step_title'] : ( ! empty( $label ) ? $label : __( 'Next Step', 'usp-form-builder' ) );
            ?>
            <div class="usp-field-wrap usp-field-page_break usp-width-100" data-field-name="<?php echo esc_attr( $name ); ?>"<?php echo $cond_attr; ?>>
                <div class="usp-page-break-divider">
                    <span class="usp-page-break-line"></span>
                    <span class="usp-page-break-badge">
                        <span class="dashicons dashicons-editor-break"></span> <?php echo esc_html( $step_title ); ?>
                    </span>
                    <span class="usp-page-break-line"></span>
                </div>
            </div>
            <?php
            return;
        }

        $width_class = 'usp-width-' . esc_attr( $width );
        ?>
        <div class="usp-field-wrap usp-field-<?php echo esc_attr( $type ); ?> <?php echo esc_attr( $width_class ); ?>" data-field-name="<?php echo esc_attr( $name ); ?>"<?php echo $cond_attr; ?>>
            <label for="<?php echo esc_attr( $id ); ?>" class="usp-field-label">
                <?php echo esc_html( $label ); ?>
                <?php if ( $required ) : ?>
                    <span class="usp-required-mark" aria-hidden="true">*</span>
                <?php endif; ?>
            </label>

            <div class="usp-field-control">
                <?php
                switch ( $type ) {
                    case 'textarea':
                        $rows = ! empty( $field['rows'] ) ? absint( $field['rows'] ) : 4;
                        ?>
                        <textarea 
                            name="<?php echo esc_attr( $name ); ?>" 
                            id="<?php echo esc_attr( $id ); ?>" 
                            rows="<?php echo esc_attr( $rows ); ?>"
                            class="usp-input usp-textarea"
                            placeholder="<?php echo esc_attr( $placeholder ); ?>"
                            <?php echo $required ? 'required' : ''; ?>
                        ></textarea>
                        <?php
                        break;

                    case 'file':
                        $allowed = ! empty( $field['allowed_ext'] ) ? $field['allowed_ext'] : 'pdf,doc,docx,jpg,jpeg,png';
                        $accept  = '.' . str_replace( array( ' ', ',' ), array( '', ',.' ), $allowed );
                        ?>
                        <div class="usp-file-control-wrap">
                            <input 
                                type="file" 
                                name="<?php echo esc_attr( $name ); ?>" 
                                id="<?php echo esc_attr( $id ); ?>" 
                                class="usp-input usp-input-file"
                                accept="<?php echo esc_attr( $accept ); ?>"
                                <?php echo $required ? 'required' : ''; ?>
                            >
                        </div>
                        <?php
                        break;

                    case 'range':
                        $min  = isset( $field['min'] ) ? (int) $field['min'] : 1;
                        $max  = isset( $field['max'] ) ? (int) $field['max'] : 10;
                        $step = isset( $field['step'] ) ? (int) $field['step'] : 1;
                        $def  = isset( $field['default_val'] ) ? (int) $field['default_val'] : round( ( $min + $max ) / 2 );
                        ?>
                        <div class="usp-range-control-wrap" style="display:flex; align-items:center; gap:14px;">
                            <input 
                                type="range" 
                                name="<?php echo esc_attr( $name ); ?>" 
                                id="<?php echo esc_attr( $id ); ?>" 
                                class="usp-input-range"
                                min="<?php echo esc_attr( $min ); ?>"
                                max="<?php echo esc_attr( $max ); ?>"
                                step="<?php echo esc_attr( $step ); ?>"
                                value="<?php echo esc_attr( $def ); ?>"
                                oninput="document.getElementById('<?php echo esc_attr( $id ); ?>_val').textContent = this.value"
                            >
                            <span class="usp-range-val-badge" id="<?php echo esc_attr( $id ); ?>_val" style="background:var(--usp-primary-light); color:var(--usp-primary); padding:4px 10px; border-radius:12px; font-weight:700; font-size:13px; min-width:28px; text-align:center;">
                                <?php echo esc_html( $def ); ?>
                            </span>
                        </div>
                        <?php
                        break;

                    case 'address':
                        if ( USP_Security::is_pro_active() ) {
                            USP_Address::render( $name, $id, $required, $field );
                        } else {
                            echo '<p>' . esc_html__( 'This address field is currently unavailable.', 'usp-form-builder' ) . '</p>';
                        }
                        break;

                    case 'country':
                        ?>
                        <div class="usp-country-control">
                            <select name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>" class="usp-input usp-select usp-country-select" <?php echo $required ? 'required' : ''; ?>>
                                <option value=""><?php echo esc_html( $placeholder ? $placeholder : __( 'Select a country', 'usp-form-builder' ) ); ?></option>
                                <?php foreach ( self::get_countries() as $country_name ) : ?>
                                    <option value="<?php echo esc_attr( $country_name ); ?>"><?php echo esc_html( $country_name ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php
                        break;

                    case 'select':
                        $options = ! empty( $field['options'] ) ? (array) $field['options'] : array();
                        ?>
                        <select 
                            name="<?php echo esc_attr( $name ); ?>" 
                            id="<?php echo esc_attr( $id ); ?>" 
                            class="usp-input usp-select"
                            <?php echo $required ? 'required' : ''; ?>
                        >
                            <option value=""><?php echo esc_html( ! empty( $placeholder ) ? $placeholder : __( 'Select an option', 'usp-form-builder' ) ); ?></option>
                            <?php foreach ( $options as $opt ) : ?>
                                <option value="<?php echo esc_attr( $opt ); ?>"><?php echo esc_html( $opt ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php
                        break;

                    case 'radio':
                        $options = ! empty( $field['options'] ) ? (array) $field['options'] : array();
                        ?>
                        <div class="usp-choice-group">
                            <?php foreach ( $options as $opt_idx => $opt ) : 
                                $opt_id = $id . '_' . $opt_idx;
                            ?>
                                <label class="usp-choice-label" for="<?php echo esc_attr( $opt_id ); ?>">
                                    <input 
                                        type="radio" 
                                        name="<?php echo esc_attr( $name ); ?>" 
                                        id="<?php echo esc_attr( $opt_id ); ?>" 
                                        value="<?php echo esc_attr( $opt ); ?>"
                                        <?php echo ( $required && 0 === $opt_idx ) ? 'required' : ''; ?>
                                    >
                                    <span><?php echo esc_html( $opt ); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <?php
                        break;

                    case 'checkbox':
                        $options = ! empty( $field['options'] ) ? (array) $field['options'] : array();
                        ?>
                        <div class="usp-choice-group">
                            <?php foreach ( $options as $opt_idx => $opt ) : 
                                $opt_id = $id . '_' . $opt_idx;
                            ?>
                                <label class="usp-choice-label" for="<?php echo esc_attr( $opt_id ); ?>">
                                    <input 
                                        type="checkbox" 
                                        name="<?php echo esc_attr( $name ); ?>[]" 
                                        id="<?php echo esc_attr( $opt_id ); ?>" 
                                        value="<?php echo esc_attr( $opt ); ?>"
                                    >
                                    <span><?php echo esc_html( $opt ); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <?php
                        break;

                    case 'number':
                        $min = isset( $field['min'] ) && '' !== $field['min'] ? 'min="' . esc_attr( $field['min'] ) . '"' : '';
                        $max = isset( $field['max'] ) && '' !== $field['max'] ? 'max="' . esc_attr( $field['max'] ) . '"' : '';
                        ?>
                        <input 
                            type="number" 
                            name="<?php echo esc_attr( $name ); ?>" 
                            id="<?php echo esc_attr( $id ); ?>" 
                            class="usp-input" 
                            placeholder="<?php echo esc_attr( $placeholder ); ?>"
                            <?php echo $min; ?>
                            <?php echo $max; ?>
                            <?php echo $required ? 'required' : ''; ?>
                        >
                        <?php
                        break;

                    case 'admission':
                        ?>
                        <div class="usp-pro-admission-panel" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px; margin-top:6px;">
                            <div class="usp-adm-row" style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:12px;">
                                <div>
                                    <label class="usp-sublabel" style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:4px;"><?php esc_html_e( 'Applicant Full Name', 'usp-form-builder' ); ?> *</label>
                                    <input type="text" name="<?php echo esc_attr( $name ); ?>[student_name]" class="usp-input" placeholder="e.g. Muhammad Ali" required>
                                </div>
                                <div>
                                    <label class="usp-sublabel" style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:4px;"><?php esc_html_e( "Father's / Guardian's Name", 'usp-form-builder' ); ?></label>
                                    <input type="text" name="<?php echo esc_attr( $name ); ?>[father_name]" class="usp-input" placeholder="e.g. Tariq Mehmood">
                                </div>
                            </div>
                            <div class="usp-adm-row" style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:12px;">
                                <div>
                                    <label class="usp-sublabel" style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:4px;"><?php esc_html_e( 'CNIC / Form-B / National ID', 'usp-form-builder' ); ?></label>
                                    <input type="text" name="<?php echo esc_attr( $name ); ?>[cnic]" class="usp-input" placeholder="35201-XXXXXXX-X">
                                </div>
                                <div>
                                    <label class="usp-sublabel" style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:4px;"><?php esc_html_e( 'Desired Degree Program', 'usp-form-builder' ); ?></label>
                                    <select name="<?php echo esc_attr( $name ); ?>[program]" class="usp-input usp-select">
                                        <option value="BS Computer Science">BS Computer Science</option>
                                        <option value="BS Software Engineering">BS Software Engineering</option>
                                        <option value="BBA / Business Administration">BBA / Business Administration</option>
                                        <option value="BS Data Science & AI">BS Data Science & AI</option>
                                        <option value="MS / MPhil Program">MS / MPhil Program</option>
                                    </select>
                                </div>
                            </div>
                            <div class="usp-adm-row" style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                                <div>
                                    <label class="usp-sublabel" style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:4px;"><?php esc_html_e( 'Previous Education (Matric / FSc / A-Levels)', 'usp-form-builder' ); ?></label>
                                    <input type="text" name="<?php echo esc_attr( $name ); ?>[prev_degree]" class="usp-input" placeholder="e.g. Pre-Engineering (Grade A)">
                                </div>
                                <div>
                                    <label class="usp-sublabel" style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:4px;"><?php esc_html_e( 'Obtained Marks / Percentage', 'usp-form-builder' ); ?></label>
                                    <input type="text" name="<?php echo esc_attr( $name ); ?>[marks]" class="usp-input" placeholder="e.g. 88.5%">
                                </div>
                            </div>
                        </div>
                        <?php
                        break;

                    case 'intl_tel':
                        $countries   = self::get_country_phone_rules();
                        $default_iso = 'PK';
                        $default_cfg = isset( $countries[ $default_iso ] ) ? $countries[ $default_iso ] : reset( $countries );
                        $mask_len    = strlen( $default_cfg['mask'] );
                        ?>
                        <div class="usp-intl-tel-control" data-intl-tel-wrap="1">
                            <div class="usp-dial-select-wrap">
                                <select 
                                    name="<?php echo esc_attr( $name ); ?>_dial_code" 
                                    class="usp-input usp-dial-select" 
                                    aria-label="<?php esc_attr_e( 'Country dial code', 'usp-form-builder' ); ?>"
                                >
                                    <?php foreach ( $countries as $iso => $info ) : 
                                        $selected = ( $iso === $default_iso ) ? 'selected' : '';
                                    ?>
                                        <option 
                                            value="<?php echo esc_attr( $info['dial'] ); ?>" 
                                            data-country="<?php echo esc_attr( $iso ); ?>"
                                            data-name="<?php echo esc_attr( $info['name'] ); ?>"
                                            data-min="<?php echo esc_attr( $info['min_digits'] ); ?>"
                                            data-max="<?php echo esc_attr( $info['max_digits'] ); ?>"
                                            data-mask="<?php echo esc_attr( $info['mask'] ); ?>"
                                            data-placeholder="<?php echo esc_attr( $info['placeholder'] ); ?>"
                                            <?php echo $selected; ?>
                                        >
                                            <?php echo esc_html( $info['flag'] . ' ' . $info['dial'] ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <input type="hidden" name="<?php echo esc_attr( $name ); ?>_country" class="usp-country-code-input" value="<?php echo esc_attr( $default_iso ); ?>">
                            <div class="usp-tel-input-col" style="flex:1; min-width:0;">
                                <div class="usp-tel-input-box" style="position:relative; width:100%;">
                                    <input 
                                        type="tel" 
                                        name="<?php echo esc_attr( $name ); ?>" 
                                        id="<?php echo esc_attr( $id ); ?>" 
                                        class="usp-input usp-tel-masked-input" 
                                        placeholder="<?php echo esc_attr( $default_cfg['placeholder'] ); ?>"
                                        maxlength="<?php echo esc_attr( $mask_len ); ?>"
                                        data-min-digits="<?php echo esc_attr( $default_cfg['min_digits'] ); ?>"
                                        data-max-digits="<?php echo esc_attr( $default_cfg['max_digits'] ); ?>"
                                        data-mask="<?php echo esc_attr( $default_cfg['mask'] ); ?>"
                                        autocomplete="tel-national"
                                        <?php echo $required ? 'required' : ''; ?>
                                    >
                                    <span class="usp-tel-status-icon" aria-hidden="true"></span>
                                </div>
                                <div class="usp-phone-helper-row" style="display:flex; justify-content:space-between; align-items:center; font-size:11px; color:#64748b; margin-top:5px; gap:4px;">
                                    <span class="usp-phone-format-hint" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Format: <strong class="usp-hint-mask"><?php echo esc_html( $default_cfg['mask'] ); ?></strong></span>
                                    <span class="usp-phone-digits-counter" data-max="<?php echo esc_attr( $default_cfg['max_digits'] ); ?>" data-min="<?php echo esc_attr( $default_cfg['min_digits'] ); ?>" style="white-space:nowrap; flex-shrink:0;">
                                        <span class="usp-count-num">0</span>/<span class="usp-count-max"><?php echo esc_html( $default_cfg['max_digits'] ); ?></span> digits
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php
                        break;

                    case 'payment':
                        $pay_amount = ! empty( $field['payment_amount'] ) ? esc_attr( $field['payment_amount'] ) : '50.00';
                        $currency   = ! empty( $field['payment_currency'] ) ? esc_attr( $field['payment_currency'] ) : 'USD';
                        ?>
                        <div class="usp-payment-card-panel" data-payment-panel="1">
                            <div class="usp-pay-header">
                                <div class="usp-pay-amount-box">
                                    <span class="usp-pay-label"><?php esc_html_e( 'Total Amount to Pay:', 'usp-form-builder' ); ?></span>
                                    <span class="usp-pay-amount"><?php echo esc_html( $currency . ' ' . $pay_amount ); ?></span>
                                </div>
                                <div class="usp-pay-secure-badge">
                                    <svg viewBox="0 0 20 20" class="usp-secure-lock-svg"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path></svg>
                                    <span><?php esc_html_e( '256-bit Encrypted', 'usp-form-builder' ); ?></span>
                                </div>
                            </div>

                            <!-- Accepted Brand Badges Bar -->
                            <div class="usp-accepted-cards-row">
                                <span class="usp-accepted-title"><?php esc_html_e( 'Accepted Cards:', 'usp-form-builder' ); ?></span>
                                <div class="usp-card-badges-list">
                                    <span class="usp-brand-badge usp-badge-visa" data-brand="visa" title="Visa">
                                        <svg viewBox="0 0 36 22" class="usp-brand-svg"><rect width="36" height="22" rx="3" fill="#1434CB"/><path d="M14.5 15.5l2.4-9h2.5l-2.4 9h-2.5zm8.9-8.8c-.5-.2-1.3-.4-2.3-.4-2.5 0-4.3 1.3-4.3 3.3 0 1.4 1.3 2.2 2.2 2.7.9.5 1.3.8 1.3 1.2 0 .6-.8.9-1.5.9-.9 0-1.5-.2-2.3-.5l-.3-.1-.3 2c.6.3 1.7.5 2.8.5 2.7 0 4.5-1.3 4.5-3.4 0-1.1-.7-2-2.3-2.7-.9-.5-1.5-.8-1.5-1.3 0-.4.5-.9 1.5-.9.8 0 1.4.2 1.9.4l.2.1.3-1.8zm6.6 5.8l1-4.8-1.5 4.8h.5zm2.3-6h-1.9c-.6 0-1.1.2-1.3.7l-3.8 8.3h2.6l.5-1.3h3.2l.3 1.3h2.3l-1.9-9zm-15.6 0l-2.4 6-.3-1.2c-.5-1.5-2-3.1-3.7-3.9l2.4 7.9h2.6l3.9-8.8h-2.5z" fill="#fff"/></svg>
                                    </span>
                                    <span class="usp-brand-badge usp-badge-mastercard" data-brand="mastercard" title="Mastercard">
                                        <svg viewBox="0 0 36 22" class="usp-brand-svg"><rect width="36" height="22" rx="3" fill="#252525"/><circle cx="14" cy="11" r="6.5" fill="#EB001B"/><circle cx="22" cy="11" r="6.5" fill="#F79E1B"/><path d="M18 6.5a6.47 6.47 0 00-2.4 4.5 6.47 6.47 0 002.4 4.5 6.47 6.47 0 002.4-4.5A6.47 6.47 0 0018 6.5z" fill="#FF5F00"/></svg>
                                    </span>
                                    <span class="usp-brand-badge usp-badge-amex" data-brand="amex" title="American Express">
                                        <svg viewBox="0 0 36 22" class="usp-brand-svg"><rect width="36" height="22" rx="3" fill="#016FD0"/><path d="M5 14l1.8-6h1.8l-1.8 6H5zm5.5 0l2.2-6h1.8l-2.2 6h-1.8zm8.5-6h-3.6l-1.8 6h2.2l.3-.9h2.1l.3.9h2.2l-1.7-6zm-2.4 3.2l.6-1.8.6 1.8h-1.2zm7.4-3.2h-3.6l-1.8 6h4v-1.3h-2.2v-.9h2v-1.1h-2v-.7h2.2V8z" fill="#fff"/></svg>
                                    </span>
                                    <span class="usp-brand-badge usp-badge-discover" data-brand="discover" title="Discover">
                                        <svg viewBox="0 0 36 22" class="usp-brand-svg"><rect width="36" height="22" rx="3" fill="#fff" stroke="#e2e8f0"/><path d="M5 14l1.3-6h3c1.5 0 2.6 1 2.6 2.7s-1.1 2.7-2.6 2.7H5zm1.8-1.8h1.2c.8 0 1.2-.4 1.2-.9s-.4-.9-1.2-.9H6.8v1.8z" fill="#1e293b"/><circle cx="16.5" cy="11" r="3.5" fill="#FF6000"/><path d="M22 14l1.3-6h1.8l-1.3 6H22zm2.7 0l1.8-6h1.8l-1.8 6h-1.8z" fill="#1e293b"/></svg>
                                    </span>
                                </div>
                            </div>

                            <div class="usp-pay-field-group">
                                <label class="usp-sublabel"><?php esc_html_e( 'Cardholder Name', 'usp-form-builder' ); ?> *</label>
                                <input type="text" name="<?php echo esc_attr( $name ); ?>_holder" class="usp-input usp-card-holder-input" placeholder="e.g. John Doe" <?php echo $required ? 'required' : ''; ?>>
                            </div>

                            <div class="usp-pay-field-group">
                                <label class="usp-sublabel"><?php esc_html_e( 'Card Number', 'usp-form-builder' ); ?> *</label>
                                <div class="usp-card-number-wrapper">
                                    <span class="usp-card-live-brand-icon">
                                        <svg viewBox="0 0 36 22" class="usp-brand-svg usp-svg-generic"><rect x="1" y="2" width="34" height="18" rx="3" fill="#f8fafc" stroke="#94a3b8" stroke-width="1.5"/><rect x="1" y="6" width="34" height="3" fill="#cbd5e1"/><circle cx="6" cy="14" r="1.5" fill="#94a3b8"/><rect x="11" y="13.5" width="8" height="1.5" rx="0.5" fill="#94a3b8"/></svg>
                                    </span>
                                    <input 
                                        type="text" 
                                        name="<?php echo esc_attr( $name ); ?>_card" 
                                        class="usp-input usp-card-number-input" 
                                        placeholder="0000 0000 0000 0000" 
                                        maxlength="19" 
                                        autocomplete="cc-number" 
                                        inputmode="numeric"
                                        <?php echo $required ? 'required' : ''; ?>
                                    >
                                    <span class="usp-card-brand-detected-pill"><?php esc_html_e( 'Card', 'usp-form-builder' ); ?></span>
                                    <input type="hidden" name="<?php echo esc_attr( $name ); ?>_brand" class="usp-card-brand-hidden" value="unknown">
                                </div>
                            </div>

                            <div class="usp-pay-split-row">
                                <div class="usp-pay-field-group">
                                    <label class="usp-sublabel"><?php esc_html_e( 'Expiry Date', 'usp-form-builder' ); ?> *</label>
                                    <input 
                                        type="text" 
                                        name="<?php echo esc_attr( $name ); ?>_exp" 
                                        class="usp-input usp-card-exp-input" 
                                        placeholder="MM / YY" 
                                        maxlength="7" 
                                        autocomplete="cc-exp" 
                                        inputmode="numeric"
                                        <?php echo $required ? 'required' : ''; ?>
                                    >
                                </div>
                                <div class="usp-pay-field-group">
                                    <label class="usp-sublabel"><?php esc_html_e( 'Security Code (CVC)', 'usp-form-builder' ); ?> *</label>
                                    <div class="usp-cvc-input-wrapper">
                                        <input 
                                            type="password" 
                                            name="<?php echo esc_attr( $name ); ?>_cvc" 
                                            class="usp-input usp-card-cvc-input" 
                                            placeholder="CVC" 
                                            maxlength="4" 
                                            autocomplete="cc-csc" 
                                            inputmode="numeric"
                                            <?php echo $required ? 'required' : ''; ?>
                                        >
                                        <span class="usp-cvc-len-tag">3 digits</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                        break;

                    case 'signature':
                        $canvas_h  = ! empty( $field['canvas_height'] ) ? absint( $field['canvas_height'] ) : 160;
                        $pen_color = ! empty( $field['pen_color'] ) ? sanitize_hex_color( $field['pen_color'] ) : '#0f172a';
                        if ( ! $pen_color ) {
                            $pen_color = '#0f172a';
                        }
                        ?>
                        <div class="usp-signature-pad-wrap" data-signature-wrap="1">
                            <div class="usp-signature-canvas-box" style="height:<?php echo esc_attr( $canvas_h ); ?>px;">
                                <canvas class="usp-signature-canvas" data-pen-color="<?php echo esc_attr( $pen_color ); ?>" style="touch-action:none;"></canvas>
                                <div class="usp-signature-guide-line"><span><?php esc_html_e( 'Sign on the line above', 'usp-form-builder' ); ?></span></div>
                            </div>
                            <div class="usp-signature-toolbar">
                                <button type="button" class="usp-sig-clear-btn">
                                    <svg viewBox="0 0 20 20" class="usp-sig-clear-svg" style="width:14px; height:14px; fill:currentColor; vertical-align:middle; margin-right:4px;"><path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/></svg>
                                    <?php esc_html_e( 'Clear Signature', 'usp-form-builder' ); ?>
                                </button>
                                <span class="usp-sig-status-badge usp-sig-empty"><?php esc_html_e( 'Awaiting signature', 'usp-form-builder' ); ?></span>
                            </div>
                            <input type="hidden" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>" class="usp-signature-data" <?php echo $required ? 'required' : ''; ?>>
                        </div>
                        <?php
                        break;

                    case 'calculation':
                        $formula   = ! empty( $field['formula'] ) ? esc_attr( $field['formula'] ) : '';
                        $prefix    = isset( $field['calc_prefix'] ) ? esc_attr( $field['calc_prefix'] ) : '$';
                        $suffix    = isset( $field['calc_suffix'] ) ? esc_attr( $field['calc_suffix'] ) : '';
                        $decimals  = isset( $field['decimals'] ) ? absint( $field['decimals'] ) : 2;
                        ?>
                        <div class="usp-calculation-wrap" data-formula="<?php echo $formula; ?>" data-prefix="<?php echo $prefix; ?>" data-suffix="<?php echo $suffix; ?>" data-decimals="<?php echo esc_attr( $decimals ); ?>">
                            <div class="usp-calculation-box">
                                <span class="usp-calc-sigma-icon">∑</span>
                                <span class="usp-calc-display-val"><?php echo esc_html( $prefix . '0.00' . $suffix ); ?></span>
                            </div>
                            <input type="hidden" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>" class="usp-calculation-hidden" value="0.00">
                        </div>
                        <?php
                        break;

                    case 'rating':
                        $def_stars = isset( $field['default_val'] ) ? absint( $field['default_val'] ) : 5;
                        ?>
                        <div class="usp-star-rating-control" data-rating-wrap="1" style="display:inline-flex; align-items:center; gap:8px;">
                            <div class="usp-stars-list" role="radiogroup" aria-label="<?php echo esc_attr( $label ); ?>" style="display:inline-flex; gap:4px;">
                                <?php for ( $s = 1; $s <= 5; $s++ ) : ?>
                                    <button type="button" class="usp-star-btn <?php echo ( $s <= $def_stars ) ? 'active' : ''; ?>" data-val="<?php echo esc_attr( $s ); ?>" style="background:transparent; border:none; cursor:pointer; padding:2px; font-size:24px; color:<?php echo ( $s <= $def_stars ) ? '#f59e0b' : '#cbd5e1'; ?>;" title="<?php printf( esc_attr__( '%d of 5 stars', 'usp-form-builder' ), $s ); ?>">
                                        <span class="dashicons dashicons-star-filled" style="font-size:24px; width:24px; height:24px;"></span>
                                    </button>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>" class="usp-rating-hidden" value="<?php echo esc_attr( $def_stars ); ?>" <?php echo $required ? 'required' : ''; ?>>
                            <span class="usp-rating-badge" style="background:#fef3c7; color:#b45309; padding:2px 8px; border-radius:10px; font-size:12px; font-weight:700;"><strong class="usp-rating-num"><?php echo esc_html( $def_stars ); ?></strong> / 5</span>
                        </div>
                        <?php
                        break;

                    case 'consent':
                        $terms_text = ! empty( $field['consent_text'] ) ? $field['consent_text'] : ( ! empty( $field['terms_text'] ) ? $field['terms_text'] : __( 'I agree to the privacy policy and terms of service.', 'usp-form-builder' ) );
                        $terms_url  = ! empty( $field['terms_url'] ) ? esc_url( $field['terms_url'] ) : '';
                        $terms_label = ! empty( $field['terms_label'] ) ? $field['terms_label'] : __( 'View Terms', 'usp-form-builder' );
                        ?>
                        <div class="usp-consent-control" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px 14px;">
                            <label class="usp-consent-label" for="<?php echo esc_attr( $id ); ?>" style="display:flex; align-items:flex-start; gap:10px; cursor:pointer; font-size:13px; color:#334155; line-height:1.4;">
                                <input 
                                    type="checkbox" 
                                    name="<?php echo esc_attr( $name ); ?>" 
                                    id="<?php echo esc_attr( $id ); ?>" 
                                    value="1"
                                    class="usp-consent-checkbox"
                                    style="margin-top:2px; flex-shrink:0;"
                                    <?php echo $required ? 'required' : ''; ?>
                                >
                                <span class="usp-consent-text">
                                    <?php echo esc_html( $terms_text ); ?>
                                    <?php if ( ! empty( $terms_url ) && '#' !== $terms_url ) : ?>
                                        <a href="<?php echo esc_url( $terms_url ); ?>" target="_blank" rel="noopener noreferrer" style="color:var(--usp-adm-primary, #4f46e5); font-weight:600; text-decoration:underline; margin-left:4px;"><?php echo esc_html( $terms_label ); ?> &rarr;</a>
                                    <?php endif; ?>
                                </span>
                            </label>
                        </div>
                        <?php
                        break;

                    case 'url':
                    case 'time':
                    case 'tel':
                    case 'date':
                    case 'email':
                    case 'text':
                    default:
                        $input_type = in_array( $type, array( 'url', 'time', 'tel', 'date', 'email' ), true ) ? $type : 'text';
                        $maxlength  = ( 'tel' === $type ) ? 'maxlength="18"' : '';
                        $pattern    = ( 'tel' === $type ) ? 'pattern="[0-9\+\-\s\(\)]{7,18}"' : '';
                        $extra_cls  = ( 'tel' === $type ) ? 'usp-input-tel-std' : '';
                        ?>
                        <input 
                            type="<?php echo esc_attr( $input_type ); ?>" 
                            name="<?php echo esc_attr( $name ); ?>" 
                            id="<?php echo esc_attr( $id ); ?>" 
                            class="usp-input <?php echo esc_attr( $extra_cls ); ?>" 
                            placeholder="<?php echo esc_attr( $placeholder ); ?>"
                            <?php echo $maxlength; ?>
                            <?php echo $pattern; ?>
                            <?php echo $required ? 'required' : ''; ?>
                        >
                        <?php
                        break;
                }
                ?>
            </div>

            <?php if ( ! empty( $help_text ) ) : ?>
                <div class="usp-field-help"><?php echo esc_html( $help_text ); ?></div>
            <?php endif; ?>

            <div class="usp-field-error" style="display:none;" role="alert"></div>
        </div>
        <?php
    }
}
