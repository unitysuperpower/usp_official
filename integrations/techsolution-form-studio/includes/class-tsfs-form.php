<?php
/**
 * Advanced Form definitions, conditional logic, validation, and rendering engine.
 *
 * @package    TSFS_Plugin
 * @subpackage TSFS_Plugin/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TSFS_Form {
    /** Bounded pixel values, including imported forms. Height is a minimum to avoid clipping. */
    public static function layout_settings( $settings ) {
        $result = array();
        foreach ( array('form_max_width'=>array(680,280,1600),'form_min_height'=>array(0,0,1600),'form_padding'=>array(32,0,80),'field_gap'=>array(18,0,64),'input_height'=>array(44,32,100),'button_width'=>array(0,0,1000),'button_height'=>array(46,32,120),'button_padding'=>array(30,0,80),'text_size'=>array(16,12,24),'label_size'=>array(15,12,26),'button_font_size'=>array(16,12,30),'button_radius'=>array(8,0,60),'button_vertical_padding'=>array(12,4,40),'consent_size'=>array(14,12,24),'consent_padding'=>array(14,0,40),'checkbox_size'=>array(20,16,32)) as $key => $rule ) {
            $value = $settings[$key] ?? $rule[0];
            $result[$key] = is_numeric( $value ) ? max( $rule[1], min( $rule[2], (int) $value ) ) : $rule[0];
        }
        return $result;
    }

    public static function color_settings( $settings ) {
        $defaults = array('button_bg'=>'#4f46e5','button_hover'=>'#4338ca','consent_bg'=>'#f8fafc','consent_border'=>'#e2e8f0','consent_color'=>'#334155');
        if ( is_string( $settings['primary_color'] ?? null ) && preg_match( '/^#[a-f0-9]{6}$/iD', $settings['primary_color'] ) ) { $defaults['button_bg'] = $settings['primary_color']; }
        $result = array();
        foreach ( $defaults as $key => $default ) {
            $value = $settings[$key] ?? $default;
            $result[$key] = is_string( $value ) && preg_match( '/^#[a-f0-9]{6}$/iD', $value ) ? $value : $default;
        }
        return $result;
    }

    /**
     * Sanitize a list of field configurations from the builder or an imported JSON file.
     * Every key is cleaned with the most restrictive function for its expected content.
     *
     * @param mixed $fields Decoded field list.
     * @return array
     */
    public static function sanitize_fields( $fields ) {
        $clean      = array();
        $seen_names = array();
        if ( ! is_array( $fields ) ) {
            return $clean;
        }

        foreach ( array_slice( $fields, 0, 200 ) as $f ) {
            if ( ! is_array( $f ) ) {
                continue;
            }
            $type = isset( $f['type'] ) ? sanitize_key( $f['type'] ) : 'text';
            if ( '' === $type ) {
                $type = 'text';
            }

            $name = isset( $f['name'] ) ? sanitize_key( $f['name'] ) : '';
            if ( '' === $name ) {
                $name = 'field_' . wp_generate_password( 8, false, false );
            }
            $base = $name;
            for ( $i = 2; isset( $seen_names[ $name ] ); $i++ ) {
                $name = $base . '_' . $i;
            }
            $seen_names[ $name ] = true;

            $width = isset( $f['width'] ) ? (string) $f['width'] : '100';

            $item = array(
                'id'          => isset( $f['id'] ) ? sanitize_key( $f['id'] ) : 'field_' . wp_generate_password( 8, false, false ),
                'type'        => $type,
                'label'       => isset( $f['label'] ) ? sanitize_text_field( $f['label'] ) : '',
                'name'        => $name,
                'placeholder' => isset( $f['placeholder'] ) ? sanitize_text_field( $f['placeholder'] ) : '',
                'required'    => ! empty( $f['required'] ),
                'help_text'   => isset( $f['help_text'] ) ? sanitize_text_field( $f['help_text'] ) : '',
                'width'       => in_array( $width, array( '100', '50', '33' ), true ) ? $width : '100',
            );

            // Conditional Logic rules
            $item['enable_conditions'] = false;
            if ( ! empty( $f['enable_conditions'] ) && ! empty( $f['conditions'] ) && is_array( $f['conditions'] ) ) {
                $item['enable_conditions'] = true;
                $item['condition_action']  = isset( $f['condition_action'] ) && 'hide' === $f['condition_action'] ? 'hide' : 'show';
                $item['condition_match']   = isset( $f['condition_match'] ) && 'any' === $f['condition_match'] ? 'any' : 'all';
                $item['conditions']        = array();
                foreach ( array_slice( $f['conditions'], 0, 20 ) as $cond ) {
                    if ( ! is_array( $cond ) ) {
                        continue;
                    }
                    $operator = isset( $cond['operator'] ) ? (string) $cond['operator'] : '==';
                    $item['conditions'][] = array(
                        'field'    => isset( $cond['field'] ) ? sanitize_key( $cond['field'] ) : '',
                        'operator' => in_array( $operator, array( '==', '!=', 'contains', 'empty', 'not_empty' ), true ) ? $operator : '==',
                        'value'    => isset( $cond['value'] ) ? sanitize_text_field( $cond['value'] ) : '',
                    );
                }
            }

            switch ( $type ) {
                case 'select':
                case 'radio':
                case 'checkbox':
                    $options = isset( $f['options'] ) && is_array( $f['options'] ) ? $f['options'] : array();
                    $item['options'] = array_values( array_filter( array_map( 'sanitize_text_field', array_filter( array_slice( $options, 0, 200 ), 'is_scalar' ) ), 'strlen' ) );
                    break;

                case 'textarea':
                    $item['rows'] = isset( $f['rows'] ) ? max( 2, min( 15, absint( $f['rows'] ) ) ) : 4;
                    break;

                case 'number':
                case 'range':
                    foreach ( array( 'min', 'max', 'step', 'default_val' ) as $key ) {
                        $item[ $key ] = isset( $f[ $key ] ) && is_numeric( $f[ $key ] ) ? (string) ( 0 + $f[ $key ] ) : '';
                    }
                    break;

                case 'file':
                    $item['allowed_ext'] = self::sanitize_extension_list( isset( $f['allowed_ext'] ) ? $f['allowed_ext'] : '' );
                    $max_upload_mb       = max( 1, (int) floor( wp_max_upload_size() / MB_IN_BYTES ) );
                    $item['max_size']    = isset( $f['max_size'] ) ? (string) max( 1, min( $max_upload_mb, absint( $f['max_size'] ) ) ) : '5';
                    break;

                case 'rating':
                    $item['max_stars']   = (string) self::rating_max( $f );
                    $item['default_val'] = isset( $f['default_val'] ) ? (string) min( (int) $item['max_stars'], absint( $f['default_val'] ) ) : $item['max_stars'];
                    break;

                case 'consent':
                    $item['consent_text'] = isset( $f['consent_text'] ) ? sanitize_textarea_field( $f['consent_text'] ) : ( isset( $f['terms_text'] ) ? sanitize_textarea_field( $f['terms_text'] ) : '' );
                    $item['terms_text']   = $item['consent_text'];
                    $item['terms_url']    = isset( $f['terms_url'] ) ? esc_url_raw( $f['terms_url'], array( 'http', 'https' ) ) : '';
                    $item['terms_label']  = isset( $f['terms_label'] ) ? sanitize_text_field( $f['terms_label'] ) : __( 'Terms & Conditions', 'techsolution-form-studio' );
                    break;

                case 'hidden':
                    $item['default_val'] = isset( $f['default_val'] ) ? sanitize_text_field( $f['default_val'] ) : '';
                    break;

                case 'html':
                    $mode                 = isset( $f['content_mode'] ) ? (string) $f['content_mode'] : 'section';
                    $tag                  = isset( $f['tag'] ) ? sanitize_key( $f['tag'] ) : 'h3';
                    $item['content_mode'] = in_array( $mode, array( 'section', 'heading', 'paragraph', 'bullets', 'numbered' ), true ) ? $mode : 'section';
                    $item['content']      = isset( $f['content'] ) ? wp_kses_post( $f['content'] ) : '';
                    $item['tag']          = in_array( $tag, array( 'h2', 'h3', 'h4', 'h5', 'h6' ), true ) ? $tag : 'h3';
                    break;

                case 'page_break':
                    $item['step_title']    = isset( $f['step_title'] ) ? sanitize_text_field( $f['step_title'] ) : '';
                    $item['btn_prev_text'] = isset( $f['btn_prev_text'] ) ? sanitize_text_field( $f['btn_prev_text'] ) : __( 'Previous', 'techsolution-form-studio' );
                    $item['btn_next_text'] = isset( $f['btn_next_text'] ) ? sanitize_text_field( $f['btn_next_text'] ) : __( 'Next Step', 'techsolution-form-studio' );
                    break;
            }

            if ( ! in_array( $type, self::get_core_field_types(), true ) ) {
                if ( self::is_known_field_type( $type ) ) {
                    /**
                     * Sanitize add-on specific keys of a field configuration.
                     *
                     * @param array  $item Field with core keys already sanitized.
                     * @param array  $raw  Unsanitized field configuration.
                     * @param string $type Field type.
                     */
                    $item = apply_filters( 'tsfs_sanitize_field_config', $item, $f, $type );
                } else {
                    // The add-on that provides this type is inactive: keep its settings as plain text so they survive a save.
                    foreach ( $f as $key => $value ) {
                        $key = sanitize_key( $key );
                        if ( '' === $key || isset( $item[ $key ] ) ) {
                            continue;
                        }
                        if ( is_scalar( $value ) ) {
                            $item[ $key ] = is_bool( $value ) ? $value : sanitize_text_field( (string) $value );
                        } elseif ( is_array( $value ) ) {
                            $item[ $key ] = array_map( 'sanitize_text_field', array_filter( array_slice( $value, 0, 200 ), 'is_scalar' ) );
                        }
                    }
                }
            }

            $clean[] = $item;
        }

        return $clean;
    }

    /**
     * Sanitize a comma-separated list of upload extensions, dropping executable types.
     */
    public static function sanitize_extension_list( $value ) {
        $parts = array_filter( array_map( 'sanitize_key', explode( ',', strtolower( (string) $value ) ) ) );
        $parts = array_diff( $parts, TSFS_Security::blocked_extensions() );
        return $parts ? implode( ',', array_unique( $parts ) ) : 'pdf,doc,docx,jpg,jpeg,png';
    }

    /**
     * Sanitize form settings from the builder or an imported JSON file.
     *
     * @param mixed $settings Decoded settings.
     * @return array
     */
    public static function sanitize_settings( $settings ) {
        if ( ! is_array( $settings ) ) {
            $settings = array();
        }
        $text = function ( $key, $default = '' ) use ( $settings ) {
            return isset( $settings[ $key ] ) && is_scalar( $settings[ $key ] ) ? sanitize_text_field( (string) $settings[ $key ] ) : $default;
        };
        $textarea = function ( $key, $default = '' ) use ( $settings ) {
            return isset( $settings[ $key ] ) && is_scalar( $settings[ $key ] ) ? sanitize_textarea_field( (string) $settings[ $key ] ) : $default;
        };
        $choice = function ( $key, $allowed, $default ) use ( $settings ) {
            return isset( $settings[ $key ] ) && in_array( $settings[ $key ], $allowed, true ) ? $settings[ $key ] : $default;
        };
        $color = function ( $key, $default ) use ( $settings ) {
            $value = isset( $settings[ $key ] ) && is_string( $settings[ $key ] ) ? sanitize_hex_color( $settings[ $key ] ) : '';
            return $value ? $value : $default;
        };

        $recipients = array();
        foreach ( explode( ',', $text( 'notification_email' ) ) as $email ) {
            $email = sanitize_email( trim( $email ) );
            if ( is_email( $email ) ) {
                $recipients[] = $email;
            }
        }

        $container_bg = $text( 'container_bg', '#ffffff' );
        $close_date   = $text( 'close_date' );

        return self::layout_settings( $settings ) + self::color_settings( $settings ) + array(
            'submit_button_text'   => $text( 'submit_button_text', __( 'Submit', 'techsolution-form-studio' ) ),
            'success_message'      => $textarea( 'success_message', __( 'Thank you! Your submission has been received.', 'techsolution-form-studio' ) ),
            'redirect_url'         => isset( $settings['redirect_url'] ) && is_string( $settings['redirect_url'] ) ? esc_url_raw( $settings['redirect_url'], array( 'http', 'https' ) ) : '',
            'pass_query_string'    => ! empty( $settings['pass_query_string'] ) || ! empty( $settings['redirect_pass_vars'] ),
            'send_email'           => ! empty( $settings['send_email'] ),
            'notification_email'   => $recipients ? implode( ',', $recipients ) : get_option( 'admin_email' ),
            'email_subject'        => $text( 'email_subject' ),
            'send_user_auto_reply' => ! empty( $settings['send_user_auto_reply'] ),
            'auto_reply_subject'   => $text( 'auto_reply_subject', __( 'Thank you for contacting us!', 'techsolution-form-studio' ) ),
            'auto_reply_message'   => $textarea( 'auto_reply_message', __( "Hello,\n\nWe have received your submission and will get back to you shortly.\n\nBest regards,\nSupport Team", 'techsolution-form-studio' ) ),

            // Theme & Style Options
            'theme_preset'         => $choice( 'theme_preset', array( 'default', 'minimal', 'dark', 'emerald', 'sunset', 'violet' ), 'default' ),
            'primary_color'        => $color( 'primary_color', '#4f46e5' ),
            'btn_text_color'       => $color( 'btn_text_color', '#ffffff' ),
            'container_bg'         => 'transparent' === $container_bg ? 'transparent' : ( sanitize_hex_color( $container_bg ) ? sanitize_hex_color( $container_bg ) : '#ffffff' ),
            'border_radius'        => $choice( 'border_radius', array( 'sharp', 'slight', 'rounded', 'pill' ), 'rounded' ),
            'button_align'         => $choice( 'button_align', array( 'left', 'center', 'right', 'full' ), 'left' ),
            'shadow_style'         => $choice( 'shadow_style', array( 'none', 'subtle', 'medium', 'elevated', 'outline' ), 'medium' ),

            // Scheduling & Submission Restrictions
            'limit_submissions'    => ! empty( $settings['limit_submissions'] ),
            'max_entries'          => isset( $settings['max_entries'] ) ? absint( $settings['max_entries'] ) : 0,
            'close_after_date'     => ! empty( $settings['close_after_date'] ),
            'close_date'           => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $close_date ) ? $close_date : '',
            'closed_message'       => $textarea( 'closed_message', __( 'This form is currently closed for submissions.', 'techsolution-form-studio' ) ),
            'require_login'        => ! empty( $settings['require_login'] ),
            'login_message'        => $textarea( 'login_message', __( 'You must be logged in to submit this form.', 'techsolution-form-studio' ) ),

            // Webhooks & API Integrations
            'enable_webhook'       => ! empty( $settings['enable_webhook'] ),
            'webhook_url'          => isset( $settings['webhook_url'] ) && is_string( $settings['webhook_url'] ) ? esc_url_raw( trim( $settings['webhook_url'] ), array( 'https', 'http' ) ) : '',
            'webhook_secret'       => $text( 'webhook_secret' ),
        );
    }

    /**
     * Convert a stored field value (string, list or keyed array) to readable plain text.
     */
    public static function format_value_text( $value, $separator = ', ' ) {
        if ( ! is_array( $value ) ) {
            return (string) $value;
        }
        if ( isset( $value['url'] ) ) {
            return ( isset( $value['file'] ) ? $value['file'] . ' ' : '' ) . '(' . $value['url'] . ')';
        }
        $parts = array();
        foreach ( $value as $key => $item ) {
            if ( is_array( $item ) || '' === trim( (string) $item ) ) {
                continue;
            }
            $parts[] = is_int( $key ) ? (string) $item : ucwords( str_replace( '_', ' ', $key ) ) . ': ' . $item;
        }
        return implode( $separator, $parts );
    }

    /** Maximum stars for a rating field (3, 5, 7 or 10). */
    public static function rating_max( $field ) {
        $max = isset( $field['max_stars'] ) ? absint( $field['max_stars'] ) : 5;
        return in_array( $max, array( 3, 5, 7, 10 ), true ) ? $max : 5;
    }

    public static function consent_accepted( $value ) {
        return '1' === $value;
    }


    /** Normalize a CNIC's format only; this does not verify identity with NADRA. */
    public static function normalize_cnic( $value ) {
        if ( ! is_string( $value ) ) { return false; }
        $value = trim( $value );
        if ( '' === $value ) { return ''; }
        if ( ! preg_match( '/^(?:[0-9]{13}|[0-9]{5}-[0-9]{7}-[0-9])$/D', $value ) ) { return false; }
        $digits = str_replace( '-', '', $value );
        return substr( $digits, 0, 5 ) . '-' . substr( $digits, 5, 7 ) . '-' . substr( $digits, 12, 1 );
    }


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
     * Whether a field type is registered (core or by an add-on).
     */
    public static function is_known_field_type( $type ) {
        return array_key_exists( $type, self::get_supported_field_types() );
    }

    /**
     * Field types provided by core. Add-on types render and validate through filters.
     */
    public static function get_core_field_types() {
        return array( 'country', 'text', 'cnic', 'email', 'textarea', 'number', 'tel', 'date', 'time', 'url', 'file', 'range', 'hidden', 'rating', 'consent', 'select', 'radio', 'checkbox', 'html', 'page_break' );
    }

    /** Get supported field types and their default configurations. */
    public static function get_supported_field_types() {
        $types = array(
            'country' => array(
                'label' => __( 'Country / Nationality', 'techsolution-form-studio' ),
                'icon' => 'dashicons-admin-site-alt3',
                'category' => 'choice',
                'defaults' => array(
                    'label' => __( 'Country', 'techsolution-form-studio' ),
                    'placeholder' => __( 'Select a country', 'techsolution-form-studio' ),
                    'required' => false,
                    'help_text' => '',
                    'width' => '100',
                ),
            ),
            'text' => array(
                'label'       => __( 'Single Line Text', 'techsolution-form-studio' ),
                'icon'        => 'dashicons-editor-textcolor',
                'category'    => 'standard',
                'defaults'    => array(
                    'label'       => __( 'Text Field', 'techsolution-form-studio' ),
                    'placeholder' => '',
                    'required'    => false,
                    'help_text'   => '',
                    'width'       => '100',
                ),
            ),
            'cnic' => array(
                'label' => __( 'CNIC / Form-B', 'techsolution-form-studio' ),
                'icon' => 'dashicons-id',
                'category' => 'standard',
                'defaults' => array( 'label' => __( 'CNIC / Form-B', 'techsolution-form-studio' ), 'placeholder' => '12345-1234567-1', 'required' => false, 'width' => '100' ),
            ),
            'email' => array(
                'label'       => __( 'Email Address', 'techsolution-form-studio' ),
                'icon'        => 'dashicons-email-alt',
                'category'    => 'standard',
                'defaults'    => array(
                    'label'       => __( 'Email Address', 'techsolution-form-studio' ),
                    'placeholder' => 'example@domain.com',
                    'required'    => true,
                    'help_text'   => '',
                    'width'       => '100',
                ),
            ),
            'textarea' => array(
                'label'       => __( 'Multi-line Paragraph', 'techsolution-form-studio' ),
                'icon'        => 'dashicons-editor-paragraph',
                'category'    => 'standard',
                'defaults'    => array(
                    'label'       => __( 'Paragraph / Message', 'techsolution-form-studio' ),
                    'placeholder' => '',
                    'required'    => false,
                    'help_text'   => '',
                    'rows'        => '4',
                    'width'       => '100',
                ),
            ),
            'number' => array(
                'label'       => __( 'Number', 'techsolution-form-studio' ),
                'icon'        => 'dashicons-calculator',
                'category'    => 'standard',
                'defaults'    => array(
                    'label'       => __( 'Number', 'techsolution-form-studio' ),
                    'placeholder' => '',
                    'required'    => false,
                    'min'         => '',
                    'max'         => '',
                    'width'       => '100',
                ),
            ),
            'tel' => array(
                'label'       => __( 'Phone Number', 'techsolution-form-studio' ),
                'icon'        => 'dashicons-phone',
                'category'    => 'standard',
                'defaults'    => array(
                    'label'       => __( 'Phone Number', 'techsolution-form-studio' ),
                    'placeholder' => '+1 (555) 000-0000',
                    'required'    => false,
                    'width'       => '100',
                ),
            ),
            'date' => array(
                'label'       => __( 'Date Picker', 'techsolution-form-studio' ),
                'icon'        => 'dashicons-calendar-alt',
                'category'    => 'standard',
                'defaults'    => array(
                    'label'       => __( 'Select Date', 'techsolution-form-studio' ),
                    'required'    => false,
                    'width'       => '100',
                ),
            ),
            'time' => array(
                'label'       => __( 'Time Picker', 'techsolution-form-studio' ),
                'icon'        => 'dashicons-clock',
                'category'    => 'standard',
                'defaults'    => array(
                    'label'       => __( 'Select Time', 'techsolution-form-studio' ),
                    'required'    => false,
                    'width'       => '100',
                ),
            ),
            'url' => array(
                'label'       => __( 'Website URL', 'techsolution-form-studio' ),
                'icon'        => 'dashicons-admin-links',
                'category'    => 'standard',
                'defaults'    => array(
                    'label'       => __( 'Website / Portfolio', 'techsolution-form-studio' ),
                    'placeholder' => 'https://example.com',
                    'required'    => false,
                    'width'       => '100',
                ),
            ),
            'file' => array(
                'label'       => __( 'File Upload', 'techsolution-form-studio' ),
                'icon'        => 'dashicons-upload',
                'category'    => 'advanced',
                'defaults'    => array(
                    'label'       => __( 'Attach File / Document', 'techsolution-form-studio' ),
                    'required'    => false,
                    'allowed_ext' => 'pdf,doc,docx,jpg,jpeg,png',
                    'max_size'    => '5', // MB
                    'help_text'   => __( 'Allowed: PDF, DOC, DOCX, JPG, PNG (Max 5MB)', 'techsolution-form-studio' ),
                    'width'       => '100',
                ),
            ),
            'range' => array(
                'label'       => __( 'Range / Rating Slider', 'techsolution-form-studio' ),
                'icon'        => 'dashicons-leftright',
                'category'    => 'advanced',
                'defaults'    => array(
                    'label'       => __( 'Satisfaction Rating', 'techsolution-form-studio' ),
                    'required'    => false,
                    'min'         => '1',
                    'max'         => '10',
                    'step'        => '1',
                    'default_val' => '5',
                    'width'       => '100',
                ),
            ),
            'hidden' => array(
                'label'       => __( 'Hidden Field', 'techsolution-form-studio' ),
                'icon'        => 'dashicons-hidden',
                'category'    => 'advanced',
                'defaults'    => array(
                    'label'       => __( 'Tracking Code', 'techsolution-form-studio' ),
                    'default_val' => '',
                ),
            ),
            'rating' => array(
                'label'       => __( 'Star Rating', 'techsolution-form-studio' ),
                'icon'        => 'dashicons-star-filled',
                'category'    => 'advanced',
                'defaults'    => array(
                    'label'       => __( 'Rate Your Experience', 'techsolution-form-studio' ),
                    'required'    => false,
                    'default_val' => '5',
                    'max_stars'   => '5',
                    'help_text'   => __( 'Click a star to rate.', 'techsolution-form-studio' ),
                    'width'       => '100',
                ),
            ),
            'consent' => array(
                'label'       => __( 'GDPR / Terms Consent', 'techsolution-form-studio' ),
                'icon'        => 'dashicons-shield',
                'category'    => 'advanced',
                'defaults'    => array(
                    'label'       => __( 'Privacy & Terms Agreement', 'techsolution-form-studio' ),
                    'required'    => true,
                    'terms_text'  => __( 'I agree to the privacy policy and terms of service.', 'techsolution-form-studio' ),
                    'terms_url'   => '#',
                    'help_text'   => '',
                    'width'       => '100',
                ),
            ),
            'select' => array(
                'label'       => __( 'Dropdown Menu', 'techsolution-form-studio' ),
                'icon'        => 'dashicons-arrow-down-alt2',
                'category'    => 'choice',
                'defaults'    => array(
                    'label'       => __( 'Dropdown Choice', 'techsolution-form-studio' ),
                    'required'    => false,
                    'options'     => array( 'Option 1', 'Option 2', 'Option 3' ),
                    'width'       => '100',
                ),
            ),
            'radio' => array(
                'label'       => __( 'Radio Buttons', 'techsolution-form-studio' ),
                'icon'        => 'dashicons-marker',
                'category'    => 'choice',
                'defaults'    => array(
                    'label'       => __( 'Radio Selection', 'techsolution-form-studio' ),
                    'required'    => false,
                    'options'     => array( 'Choice 1', 'Choice 2' ),
                    'width'       => '100',
                ),
            ),
            'checkbox' => array(
                'label'       => __( 'Checkboxes', 'techsolution-form-studio' ),
                'icon'        => 'dashicons-yes-alt',
                'category'    => 'choice',
                'defaults'    => array(
                    'label'       => __( 'Checkbox Options', 'techsolution-form-studio' ),
                    'required'    => false,
                    'options'     => array( 'Option A', 'Option B' ),
                    'width'       => '100',
                ),
            ),
            'html' => array(
                'label'       => __( 'Content: Heading, Paragraph & List', 'techsolution-form-studio' ),
                'icon'        => 'dashicons-heading',
                'category'    => 'layout',
                'defaults'    => array(
                    'label'       => __( 'Section Title', 'techsolution-form-studio' ),
                    'content'     => __( 'Fill out the form below.', 'techsolution-form-studio' ),
                    'tag'         => 'h3',
                ),
            ),
            'page_break' => array(
                'label'       => __( 'Page Break / Multi-Step', 'techsolution-form-studio' ),
                'icon'        => 'dashicons-editor-break',
                'category'    => 'layout',
                'defaults'    => array(
                    'label'         => __( 'Next Step', 'techsolution-form-studio' ),
                    'step_title'    => __( 'Step 2', 'techsolution-form-studio' ),
                    'btn_prev_text' => __( 'Previous', 'techsolution-form-studio' ),
                    'btn_next_text' => __( 'Next Step', 'techsolution-form-studio' ),
                    'width'         => '100',
                ),
            ),
        );

        /**
         * Filter the available field types. Add-ons append definitions with label, icon, category and defaults.
         *
         * @param array $types Field type definitions keyed by type.
         */
        $types = apply_filters( 'tsfs_field_types', $types );
        return $types;
    }

    /**
     * Render the frontend HTML for a form.
     */
    public static function render_form_html( $form_id, $display_title = true ) {
        $form = TSFS_DB::get_form( $form_id );

        if ( ! $form || 'publish' !== $form['status'] ) {
            return '<!-- Form Studio: Form not found or unpublished. -->';
        }

        $fields   = ! empty( $form['fields_data'] ) ? $form['fields_data'] : array();
        $settings = ! empty( $form['settings'] ) ? $form['settings'] : array();

        TSFS_Public::enqueue_form_assets();
        /**
         * Fires before a form is rendered, so add-ons can enqueue assets for the field types it uses.
         *
         * @param array $form Form record.
         */
        do_action( 'tsfs_before_render_form', $form );

        // 1. Check Login Restriction
        if ( ! empty( $settings['require_login'] ) && ! is_user_logged_in() ) {
            $login_msg = ! empty( $settings['login_message'] ) ? $settings['login_message'] : __( 'You must be logged in to view and submit this form.', 'techsolution-form-studio' );
            $login_url = wp_login_url( get_permalink() );
            return '<div class="tsfs-form-container tsfs-form-notice-box" style="text-align:center; padding:32px 20px;">' .
                   '<p style="margin-bottom:14px; font-weight:600; color:#334155;">' . esc_html( $login_msg ) . '</p>' .
                   '<a href="' . esc_url( $login_url ) . '" class="button button-primary" style="display:inline-block; padding:8px 20px; text-decoration:none; background:#4f46e5; color:#fff; border-radius:6px; font-weight:600;">' . esc_html__( 'Log In', 'techsolution-form-studio' ) . '</a>' .
                   '</div>';
        }

        // 2. Check Expiration Date
        if ( ! empty( $settings['close_after_date'] ) && ! empty( $settings['close_date'] ) ) {
            if ( current_time( 'timestamp' ) > strtotime( $settings['close_date'] . ' 23:59:59' ) ) {
                $closed_msg = ! empty( $settings['closed_message'] ) ? $settings['closed_message'] : __( 'This form is currently closed for submissions.', 'techsolution-form-studio' );
                return '<div class="tsfs-form-container tsfs-form-notice-box" style="text-align:center; padding:32px 20px;">' .
                       '<p style="margin:0; font-weight:600; color:#64748b;">' . esc_html( $closed_msg ) . '</p>' .
                       '</div>';
            }
        }

        // 3. Check Submission Limit
        if ( ! empty( $settings['limit_submissions'] ) && ! empty( $settings['max_entries'] ) ) {
            $entry_counts = TSFS_DB::get_status_counts( $form_id );
            if ( (int) $entry_counts['all'] >= (int) $settings['max_entries'] ) {
                $closed_msg = ! empty( $settings['closed_message'] ) ? $settings['closed_message'] : __( 'This form has reached its maximum submission limit.', 'techsolution-form-studio' );
                return '<div class="tsfs-form-container tsfs-form-notice-box" style="text-align:center; padding:32px 20px;">' .
                       '<p style="margin:0; font-weight:600; color:#64748b;">' . esc_html( $closed_msg ) . '</p>' .
                       '</div>';
            }
        }

        $submit_btn_text = ! empty( $settings['submit_button_text'] ) ? $settings['submit_button_text'] : __( 'Submit', 'techsolution-form-studio' );
        $form_wrapper_id = 'tsfs-form-' . esc_attr( $form_id );

        // Theme Customization Attributes
        $theme_preset   = ! empty( $settings['theme_preset'] ) ? sanitize_key( $settings['theme_preset'] ) : 'default';
        $border_radius  = ! empty( $settings['border_radius'] ) ? sanitize_key( $settings['border_radius'] ) : 'rounded';
        $button_align   = ! empty( $settings['button_align'] ) ? sanitize_key( $settings['button_align'] ) : 'left';
        $shadow_style   = ! empty( $settings['shadow_style'] ) ? sanitize_key( $settings['shadow_style'] ) : 'medium';
        $primary_color  = ! empty( $settings['primary_color'] ) ? sanitize_hex_color( $settings['primary_color'] ) : '';
        $btn_text_color = ! empty( $settings['btn_text_color'] ) ? sanitize_hex_color( $settings['btn_text_color'] ) : '';
        $container_bg   = ! empty( $settings['container_bg'] ) && 'transparent' === $settings['container_bg'] ? 'transparent' : ( ! empty( $settings['container_bg'] ) ? (string) sanitize_hex_color( $settings['container_bg'] ) : '' );

        $css_vars = array();
        if ( $primary_color ) {
            $css_vars[] = '--tsfs-primary: ' . $primary_color . ';';
            $css_vars[] = '--tsfs-primary-hover: ' . $primary_color . ';';
            $css_vars[] = '--tsfs-border-focus: ' . $primary_color . ';';
        }
        if ( $btn_text_color ) {
            $css_vars[] = '--tsfs-btn-color: ' . $btn_text_color . ';';
        }
        if ( $container_bg && 'transparent' !== strtolower( $container_bg ) ) {
            $css_vars[] = '--tsfs-bg-card: ' . $container_bg . ';';
        } elseif ( 'transparent' === strtolower( $container_bg ) ) {
            $css_vars[] = '--tsfs-bg-card: transparent;';
        }
        foreach ( self::layout_settings( $settings ) as $key => $value ) {
            $css_vars[] = '--tsfs-' . str_replace( '_', '-', $key ) . ': ' . ( 'button_width' === $key && 0 === $value ? 'auto' : $value . 'px' ) . ';';
        }
        foreach ( self::color_settings( $settings ) as $key => $value ) {
            $css_vars[] = '--tsfs-' . str_replace( '_', '-', $key ) . ': ' . $value . ';';
        }
        $inline_style = implode( ' ', $css_vars );

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
                'title'     => __( 'Step 1', 'techsolution-form-studio' ),
                'prev_text' => __( 'Previous', 'techsolution-form-studio' ),
                'next_text' => __( 'Next Step', 'techsolution-form-studio' ),
                'fields'    => array(),
            );

            foreach ( $fields as $index => $field ) {
                if ( isset( $field['type'] ) && 'page_break' === $field['type'] ) {
                    $current_step++;
                    /* translators: placeholders are values inserted into the message: value inserted into the message. */
                    $step_title = ! empty( $field['step_title'] ) ? $field['step_title'] : ( ! empty( $field['label'] ) ? $field['label'] : sprintf( __( 'Step %d', 'techsolution-form-studio' ), $current_step ) );
                    $steps[ $current_step ] = array(
                        'title'     => $step_title,
                        'prev_text' => ! empty( $field['btn_prev_text'] ) ? $field['btn_prev_text'] : __( 'Previous', 'techsolution-form-studio' ),
                        'next_text' => ! empty( $field['btn_next_text'] ) ? $field['btn_next_text'] : __( 'Next Step', 'techsolution-form-studio' ),
                        'fields'    => array(),
                    );
                } else {
                    $steps[ $current_step ]['fields'][] = array( 'field' => $field, 'index' => $index );
                }
            }
            $total_steps = count( $steps );
        }

        $container_classes = array(
            'tsfs-form-container',
            'tsfs-theme-' . $theme_preset,
            'tsfs-radius-' . $border_radius,
            'tsfs-shadow-' . $shadow_style,
        );
        if ( $has_steps ) {
            $container_classes[] = 'tsfs-has-multistep';
        }

        $sec_settings = TSFS_Security::get_settings();

        ob_start();
        ?>
        <div class="<?php echo esc_attr( implode( ' ', $container_classes ) ); ?>" id="<?php echo esc_attr( $form_wrapper_id ); ?>"<?php if ( $inline_style ) : ?> style="<?php echo esc_attr( $inline_style ); ?>"<?php endif; ?>>
            <?php if ( $display_title && ! empty( $form['title'] ) ) : ?>
                <h3 class="tsfs-form-heading"><?php echo esc_html( $form['title'] ); ?></h3>
            <?php endif; ?>

            <form class="tsfs-form" method="post" action="" enctype="multipart/form-data" data-form-id="<?php echo esc_attr( $form_id ); ?>" novalidate>
                <?php wp_nonce_field( 'tsfs_form_submit_' . $form_id, 'tsfs_nonce' ); ?>
                <input type="hidden" name="action" value="tsfs_submit_form">
                <input type="hidden" name="form_id" value="<?php echo esc_attr( $form_id ); ?>">

                <!-- Anti-spam Honeypot Field -->
                <div class="tsfs-hp-field" style="display:none !important;" aria-hidden="true">
                    <label for="tsfs_hp_<?php echo esc_attr( $form_id ); ?>">Leave this field blank</label>
                    <input type="text" name="tsfs_hp_field" id="tsfs_hp_<?php echo esc_attr( $form_id ); ?>" tabindex="-1" autocomplete="off">
                </div>

                <!-- Anti-spam Time-Gate Bot Trap Token -->
                <?php $time_token = TSFS_Security::generate_time_token( $form_id ); ?>
                <input type="hidden" name="tsfs_time_token" value="<?php echo esc_attr( $time_token ); ?>">

                <?php if ( $has_steps ) : ?>
                    <!-- Multi-Step Wizard Progress Bar & Stepper -->
                    <div class="tsfs-steps-wrapper" data-total-steps="<?php echo esc_attr( $total_steps ); ?>">
                        <div class="tsfs-steps-progress-container">
                            <div class="tsfs-steps-progress-bar" style="width: <?php echo esc_attr( round( ( 1 / $total_steps ) * 100 ) ); ?>%;"></div>
                        </div>
                        <div class="tsfs-steps-indicators-bar">
                            <?php foreach ( $steps as $s_num => $s_data ) : 
                                $step_cls = ( 1 === $s_num ) ? 'is-active is-current' : '';
                            ?>
                                <div class="tsfs-step-indicator-item <?php echo esc_attr( $step_cls ); ?>" data-step="<?php echo esc_attr( $s_num ); ?>">
                                    <span class="tsfs-step-indicator-dot">
                                        <span class="tsfs-step-num-text"><?php echo esc_html( $s_num ); ?></span>
                                        <span class="tsfs-step-check-icon">✓</span>
                                    </span>
                                    <span class="tsfs-step-indicator-title"><?php echo esc_html( $s_data['title'] ); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="tsfs-multistep-container">
                        <?php foreach ( $steps as $s_num => $s_data ) : 
                            $is_first = ( 1 === $s_num );
                            $is_last  = ( $total_steps === $s_num );
                        ?>
                            <div class="tsfs-form-step <?php echo $is_first ? 'is-active' : ''; ?>" data-step="<?php echo esc_attr( $s_num ); ?>" style="<?php echo $is_first ? '' : 'display:none;'; ?>">
                                <div class="tsfs-form-fields-grid">
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

                                <div class="tsfs-step-nav-actions tsfs-btn-align-<?php echo esc_attr( $button_align ); ?>">
                                    <?php if ( ! $is_first ) : ?>
                                        <button type="button" class="tsfs-step-nav-btn tsfs-btn-step-prev" data-step-nav="prev">
                                            <span class="dashicons dashicons-arrow-left-alt2"></span>
                                            <span><?php echo esc_html( $s_data['prev_text'] ); ?></span>
                                        </button>
                                    <?php endif; ?>

                                    <?php if ( ! $is_last ) : ?>
                                        <button type="button" class="tsfs-step-nav-btn tsfs-btn-step-next" data-step-nav="next">
                                            <span><?php echo esc_html( $s_data['next_text'] ); ?></span>
                                            <span class="dashicons dashicons-arrow-right-alt2"></span>
                                        </button>
                                    <?php else : ?>
                                        <button type="submit" class="tsfs-btn-submit">
                                            <span class="tsfs-btn-text"><?php echo esc_html( $submit_btn_text ); ?></span>
                                            <span class="tsfs-spinner" style="display:none;" aria-hidden="true"></span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <!-- Single-Page Standard Fields Grid -->
                    <div class="tsfs-form-fields-grid">
                        <?php
                        foreach ( $fields as $index => $field ) {
                            self::render_field( $field, $form_id, $index );
                        }
                        ?>
                    </div>

                    <!-- Dynamic Anti-Spam CAPTCHA Shield -->
                    <?php self::render_captcha_widget( $sec_settings, $form_id ); ?>

                    <div class="tsfs-form-actions tsfs-btn-align-<?php echo esc_attr( $button_align ); ?>">
                        <button type="submit" class="tsfs-btn-submit">
                            <span class="tsfs-btn-text"><?php echo esc_html( $submit_btn_text ); ?></span>
                            <span class="tsfs-spinner" style="display:none;" aria-hidden="true"></span>
                        </button>
                    </div>
                <?php endif; ?>

                <div class="tsfs-form-messages" style="display:none;"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render anti-spam CAPTCHA widget (Google reCAPTCHA v2/v3, Cloudflare Turnstile).
     * Provider scripts are enqueued through WordPress and loaded only on pages that show a form.
     */
    private static function render_captcha_widget( $sec_settings, $form_id ) {
        $provider = ! empty( $sec_settings['anti_spam_provider'] ) ? $sec_settings['anti_spam_provider'] : 'none';
        TSFS_Public::enqueue_captcha_script( $sec_settings );

        if ( 'recaptcha_v2' === $provider && ! empty( $sec_settings['recaptcha_site_key'] ) ) : ?>
            <div class="tsfs-captcha-box">
                <div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $sec_settings['recaptcha_site_key'] ); ?>"></div>
            </div>
        <?php elseif ( 'turnstile' === $provider && ! empty( $sec_settings['turnstile_site_key'] ) ) : ?>
            <div class="tsfs-captcha-box">
                <div class="cf-turnstile" data-sitekey="<?php echo esc_attr( $sec_settings['turnstile_site_key'] ); ?>"></div>
            </div>
        <?php elseif ( 'recaptcha_v3' === $provider && ! empty( $sec_settings['recaptcha_site_key'] ) ) : ?>
            <input type="hidden" name="g-recaptcha-response" class="tsfs-recaptcha-v3-token" id="tsfs_recaptcha_v3_<?php echo esc_attr( $form_id ); ?>">
        <?php endif;
    }

    /**
     * Render a single field element with conditional logic attributes.
     */
    private static function render_field( $field, $form_id, $index ) {
        $type        = isset( $field['type'] ) ? $field['type'] : 'text';
        $label       = isset( $field['label'] ) ? $field['label'] : '';
        $name        = ! empty( $field['name'] ) ? sanitize_key( $field['name'] ) : 'field_' . $index;
        $id          = 'tsfs_' . $form_id . '_' . $name;
        $required    = ! empty( $field['required'] );
        $placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
        $help_text   = isset( $field['help_text'] ) ? $field['help_text'] : '';
        $width       = isset( $field['width'] ) ? $field['width'] : '100';

        // Types registered by an add-on are rendered by that add-on. Unknown types are skipped.
        $is_core_type = in_array( $type, self::get_core_field_types(), true );
        if ( ! $is_core_type && ( ! self::is_known_field_type( $type ) || ! has_action( 'tsfs_render_field_' . $type ) ) ) {
            return;
        }

        // Hidden input
        if ( 'hidden' === $type ) {
            echo '<input type="hidden" name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '" value="' . esc_attr( isset( $field['default_val'] ) ? $field['default_val'] : '' ) . '">';
            return;
        }

        // Conditional Logic Attributes
        $cond_json = '';
        if ( ! empty( $field['enable_conditions'] ) && ! empty( $field['conditions'] ) ) {
            $cond_data = array(
                'action' => isset( $field['condition_action'] ) ? $field['condition_action'] : 'show',
                'match'  => isset( $field['condition_match'] ) ? $field['condition_match'] : 'all',
                'rules'  => $field['conditions'],
            );
            $cond_json = wp_json_encode( $cond_data );
        }

        // Section note / HTML
        if ( 'html' === $type ) {
            $tag     = ! empty( $field['tag'] ) ? sanitize_key( $field['tag'] ) : 'h3';
            $tag = in_array( $tag, array( 'h2', 'h3', 'h4', 'h5', 'h6' ), true ) ? $tag : 'h3';
            $mode = $field['content_mode'] ?? 'section';
            if ( in_array( $mode, array( 'heading', 'paragraph', 'bullets', 'numbered' ), true ) ) {
                echo '<div class="tsfs-field-wrap tsfs-field-html tsfs-width-100" data-field-name="' . esc_attr( $name ) . '"' . ( $cond_json ? ' data-conditions="' . esc_attr( $cond_json ) . '"' : '' ) . '>';
                if ( 'heading' === $mode ) {
                    echo '<' . tag_escape( $tag ) . ' class="tsfs-html-title">' . esc_html( $label ) . '</' . tag_escape( $tag ) . '>';
                } elseif ( 'paragraph' === $mode ) {
                    foreach ( preg_split( '/\R\s*\R/', (string) ( $field['content'] ?? '' ) ) as $paragraph ) {
                        if ( '' !== trim( $paragraph ) ) { echo '<p style="white-space:pre-line">' . esc_html( $paragraph ) . '</p>'; }
                    }
                } else {
                    $list_tag = 'numbered' === $mode ? 'ol' : 'ul';
                    echo '<' . tag_escape( $list_tag ) . ' style="padding-left:1.5em;list-style-type:' . ( 'numbered' === $mode ? 'decimal' : 'disc' ) . '">';
                    foreach ( preg_split( '/\R/', (string) ( $field['content'] ?? '' ) ) as $item ) {
                        if ( '' !== trim( $item ) ) { echo '<li>' . esc_html( trim( $item ) ) . '</li>'; }
                    }
                    echo '</' . tag_escape( $list_tag ) . '>';
                }
                echo '</div>';
                return;
            }
            $content = ! empty( $field['content'] ) ? wp_kses_post( $field['content'] ) : '';
            ?>
            <div class="tsfs-field-wrap tsfs-field-html tsfs-width-100" data-field-name="<?php echo esc_attr( $name ); ?>"<?php if ( $cond_json ) : ?> data-conditions="<?php echo esc_attr( $cond_json ); ?>"<?php endif; ?>>
                <<?php echo esc_html( $tag ); ?> class="tsfs-html-title"><?php echo esc_html( $label ); ?></<?php echo esc_html( $tag ); ?>>
                <?php if ( $content ) : ?>
                    <div class="tsfs-html-desc"><?php echo wp_kses_post( $content ); ?></div>
                <?php endif; ?>
            </div>
            <?php
            return;
        }

        // Page break divider (if rendered standalone)
        if ( 'page_break' === $type ) {
            $step_title = ! empty( $field['step_title'] ) ? $field['step_title'] : ( ! empty( $label ) ? $label : __( 'Next Step', 'techsolution-form-studio' ) );
            ?>
            <div class="tsfs-field-wrap tsfs-field-page_break tsfs-width-100" data-field-name="<?php echo esc_attr( $name ); ?>"<?php if ( $cond_json ) : ?> data-conditions="<?php echo esc_attr( $cond_json ); ?>"<?php endif; ?>>
                <div class="tsfs-page-break-divider">
                    <span class="tsfs-page-break-line"></span>
                    <span class="tsfs-page-break-badge">
                        <span class="dashicons dashicons-editor-break"></span> <?php echo esc_html( $step_title ); ?>
                    </span>
                    <span class="tsfs-page-break-line"></span>
                </div>
            </div>
            <?php
            return;
        }

        $width_class = 'tsfs-width-' . esc_attr( $width );
        ?>
        <div class="tsfs-field-wrap tsfs-field-<?php echo esc_attr( $type ); ?> <?php echo esc_attr( $width_class ); ?>" data-field-name="<?php echo esc_attr( $name ); ?>"<?php if ( $cond_json ) : ?> data-conditions="<?php echo esc_attr( $cond_json ); ?>"<?php endif; ?>>
            <label for="<?php echo esc_attr( $id ); ?>" class="tsfs-field-label">
                <?php echo esc_html( $label ); ?>
                <?php if ( $required ) : ?>
                    <span class="tsfs-required-mark" aria-hidden="true">*</span>
                <?php endif; ?>
            </label>

            <div class="tsfs-field-control">
                <?php
                if ( ! $is_core_type ) {
                    /**
                     * Render the control for an add-on field type inside the standard label/help/error wrapper.
                     *
                     * @param array  $field    Saved field configuration.
                     * @param string $name     Input name attribute.
                     * @param string $id       Input id attribute (the label points here).
                     * @param bool   $required Whether the field is required.
                     * @param int    $form_id  Form ID.
                     */
                    do_action( 'tsfs_render_field_' . $type, $field, $name, $id, $required, $form_id );
                }
                switch ( $is_core_type ? $type : '' ) {
                    case '':
                        break;

                    case 'textarea':
                        $rows = ! empty( $field['rows'] ) ? absint( $field['rows'] ) : 4;
                        ?>
                        <textarea 
                            name="<?php echo esc_attr( $name ); ?>" 
                            id="<?php echo esc_attr( $id ); ?>" 
                            rows="<?php echo esc_attr( $rows ); ?>"
                            class="tsfs-input tsfs-textarea"
                            placeholder="<?php echo esc_attr( $placeholder ); ?>"
                            <?php echo $required ? 'required' : ''; ?>
                        ></textarea>
                        <?php
                        break;

                    case 'file':
                        $allowed = ! empty( $field['allowed_ext'] ) ? $field['allowed_ext'] : 'pdf,doc,docx,jpg,jpeg,png';
                        $accept  = '.' . str_replace( array( ' ', ',' ), array( '', ',.' ), $allowed );
                        ?>
                        <div class="tsfs-file-control-wrap">
                            <input 
                                type="file" 
                                name="<?php echo esc_attr( $name ); ?>" 
                                id="<?php echo esc_attr( $id ); ?>" 
                                class="tsfs-input tsfs-input-file"
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
                        <div class="tsfs-range-control-wrap" style="display:flex; align-items:center; gap:14px;">
                            <input 
                                type="range" 
                                name="<?php echo esc_attr( $name ); ?>" 
                                id="<?php echo esc_attr( $id ); ?>" 
                                class="tsfs-input-range"
                                min="<?php echo esc_attr( $min ); ?>"
                                max="<?php echo esc_attr( $max ); ?>"
                                step="<?php echo esc_attr( $step ); ?>"
                                value="<?php echo esc_attr( $def ); ?>"
                                data-value-target="<?php echo esc_attr( $id ); ?>_val"
                            >
                            <span class="tsfs-range-val-badge" id="<?php echo esc_attr( $id ); ?>_val" style="background:var(--tsfs-primary-light); color:var(--tsfs-primary); padding:4px 10px; border-radius:12px; font-weight:700; font-size:13px; min-width:28px; text-align:center;">
                                <?php echo esc_html( $def ); ?>
                            </span>
                        </div>
                        <?php
                        break;

                    case 'country':
                        ?>
                        <div class="tsfs-country-control">
                            <select name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>" class="tsfs-input tsfs-select tsfs-country-select" <?php echo $required ? 'required' : ''; ?>>
                                <option value=""><?php echo esc_html( $placeholder ? $placeholder : __( 'Select a country', 'techsolution-form-studio' ) ); ?></option>
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
                            class="tsfs-input tsfs-select"
                            <?php echo $required ? 'required' : ''; ?>
                        >
                            <option value=""><?php echo esc_html( ! empty( $placeholder ) ? $placeholder : __( 'Select an option', 'techsolution-form-studio' ) ); ?></option>
                            <?php foreach ( $options as $opt ) : ?>
                                <option value="<?php echo esc_attr( $opt ); ?>"><?php echo esc_html( $opt ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php
                        break;

                    case 'radio':
                        $options = ! empty( $field['options'] ) ? (array) $field['options'] : array();
                        ?>
                        <div class="tsfs-choice-group">
                            <?php foreach ( $options as $opt_idx => $opt ) : 
                                $opt_id = $id . '_' . $opt_idx;
                            ?>
                                <label class="tsfs-choice-label" for="<?php echo esc_attr( $opt_id ); ?>">
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
                        <div class="tsfs-choice-group">
                            <?php foreach ( $options as $opt_idx => $opt ) : 
                                $opt_id = $id . '_' . $opt_idx;
                            ?>
                                <label class="tsfs-choice-label" for="<?php echo esc_attr( $opt_id ); ?>">
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
                        $number_attrs = array();
                        foreach ( array( 'min', 'max' ) as $bound ) {
                            if ( isset( $field[ $bound ] ) && '' !== $field[ $bound ] ) {
                                $number_attrs[ $bound ] = $field[ $bound ];
                            }
                        }
                        ?>
                        <input 
                            type="number" 
                            name="<?php echo esc_attr( $name ); ?>" 
                            id="<?php echo esc_attr( $id ); ?>" 
                            class="tsfs-input" 
                            placeholder="<?php echo esc_attr( $placeholder ); ?>"
                            <?php foreach ( $number_attrs as $attr_name => $attr_value ) { echo ' ' . esc_attr( $attr_name ) . '="' . esc_attr( $attr_value ) . '"'; } ?>
                            <?php echo $required ? 'required' : ''; ?>
                        >
                        <?php
                        break;

                    case 'rating':
                        $max_stars = self::rating_max( $field );
                        $def_stars = isset( $field['default_val'] ) ? min( $max_stars, absint( $field['default_val'] ) ) : $max_stars;
                        ?>
                        <div class="tsfs-star-rating-control" data-rating-wrap="1" style="display:inline-flex; align-items:center; gap:8px;">
                            <div class="tsfs-stars-list" role="radiogroup" aria-label="<?php echo esc_attr( $label ); ?>" style="display:inline-flex; gap:4px;">
                                <?php for ( $s = 1; $s <= $max_stars; $s++ ) : ?>
                                    <?php /* translators: 1: selected stars, 2: maximum stars */ ?>
                                    <button type="button" class="tsfs-star-btn <?php echo ( $s <= $def_stars ) ? 'active' : ''; ?>" data-val="<?php echo esc_attr( $s ); ?>" style="background:transparent; border:none; cursor:pointer; padding:2px; font-size:24px; color:<?php echo ( $s <= $def_stars ) ? '#f59e0b' : '#cbd5e1'; ?>;" aria-label="<?php echo esc_attr( sprintf( __( '%1$d of %2$d stars', 'techsolution-form-studio' ), $s, $max_stars ) ); ?>">
                                        <span class="dashicons dashicons-star-filled" style="font-size:24px; width:24px; height:24px;"></span>
                                    </button>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>" class="tsfs-rating-hidden" value="<?php echo esc_attr( $def_stars ); ?>" <?php echo $required ? 'required' : ''; ?>>
                            <span class="tsfs-rating-badge" style="background:#fef3c7; color:#b45309; padding:2px 8px; border-radius:10px; font-size:12px; font-weight:700;"><strong class="tsfs-rating-num"><?php echo esc_html( $def_stars ); ?></strong> / <?php echo esc_html( $max_stars ); ?></span>
                        </div>
                        <?php
                        break;

                    case 'consent':
                        $terms_text = ! empty( $field['consent_text'] ) ? $field['consent_text'] : ( ! empty( $field['terms_text'] ) ? $field['terms_text'] : __( 'I agree to the privacy policy and terms of service.', 'techsolution-form-studio' ) );
                        $terms_url  = ! empty( $field['terms_url'] ) ? esc_url( $field['terms_url'] ) : '';
                        $terms_label = ! empty( $field['terms_label'] ) ? $field['terms_label'] : __( 'View Terms', 'techsolution-form-studio' );
                        ?>
                        <div class="tsfs-consent-control">
                            <label class="tsfs-consent-label" for="<?php echo esc_attr( $id ); ?>" >
                                <input 
                                    type="checkbox" 
                                    name="<?php echo esc_attr( $name ); ?>" 
                                    id="<?php echo esc_attr( $id ); ?>" 
                                    value="1"
                                    class="tsfs-consent-checkbox"
                                    style="margin-top:2px; flex-shrink:0;"
                                    <?php echo $required ? 'required' : ''; ?>
                                >
                                <span class="tsfs-consent-text">
                                    <?php echo esc_html( $terms_text ); ?>
                                    <?php if ( ! empty( $terms_url ) && '#' !== $terms_url ) : ?>
                                        <a href="<?php echo esc_url( $terms_url ); ?>" target="_blank" rel="noopener noreferrer" style="color:var(--tsfs-adm-primary, #4f46e5); font-weight:600; text-decoration:underline; margin-left:4px;"><?php echo esc_html( $terms_label ); ?> &rarr;</a>
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
                        $input_attrs = ( 'tel' === $type ) ? array( 'maxlength' => '18', 'pattern' => '[0-9\+\-\s\(\)]{7,18}' ) : array();
                        $extra_cls  = ( 'tel' === $type ) ? 'tsfs-input-tel-std' : '';
                        if ( 'cnic' === $type ) {
                            $input_attrs = array( 'maxlength' => '15', 'inputmode' => 'numeric', 'pattern' => '[0-9]{5}-[0-9]{7}-[0-9]', 'title' => __( 'Use 12345-1234567-1', 'techsolution-form-studio' ) );
                            $extra_cls = 'tsfs-input-cnic';
                            $placeholder = $placeholder ?: '12345-1234567-1';
                        }
                        ?>
                        <input 
                            type="<?php echo esc_attr( $input_type ); ?>" 
                            name="<?php echo esc_attr( $name ); ?>" 
                            id="<?php echo esc_attr( $id ); ?>" 
                            class="tsfs-input <?php echo esc_attr( $extra_cls ); ?>" 
                            placeholder="<?php echo esc_attr( $placeholder ); ?>"
                            <?php foreach ( $input_attrs as $attr_name => $attr_value ) { echo ' ' . esc_attr( $attr_name ) . '="' . esc_attr( $attr_value ) . '"'; } ?>
                            <?php echo $required ? 'required' : ''; ?>
                        >
                        <?php
                        break;
                }
                ?>
            </div>

            <?php if ( ! empty( $help_text ) ) : ?>
                <div class="tsfs-field-help"><?php echo esc_html( $help_text ); ?></div>
            <?php endif; ?>

            <div class="tsfs-field-error" style="display:none;" role="alert"></div>
        </div>
        <?php
    }
}
