<?php
/**
 * Hardened Security & Pro Licensing Engine for USP Form Builder.
 * Multi-layer Bot Shield, Rate Limiting, File Sandboxing, and Anti-Spam protection.
 *
 * @package    USP_Form_Builder
 * @subpackage USP_Form_Builder/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class USP_Security {

    const OPTION_KEY = 'usp_security_settings';

    /**
     * Dangerous executable file extensions that must NEVER be uploaded under any circumstances.
     */
    private static $blacklisted_extensions = array(
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar', 'phps',
        'cgi', 'pl', 'py', 'asp', 'aspx', 'jsp', 'sh', 'bash', 'bin', 'exe',
        'bat', 'cmd', 'vbs', 'vbe', 'js', 'jse', 'wsf', 'wsh', 'com', 'scr',
        'msi', 'hta', 'cpl', 'jar', 'htaccess', 'htpasswd', 'ini', 'sql'
    );

    /**
     * Get security & Pro settings with comprehensive defaults.
     */
    public static function get_settings() {
        $defaults = array(
            // Pro Licensing
            'license_key'                => '',
            'license_status'             => 'inactive', // 'valid', 'inactive', 'expired'
            'license_expires'            => '',
            'pro_enabled'                => false,
            'store_pricing_url'           => 'https://usp.com.pk/products/usp-form-builder',
            'store_account_url'            => 'https://usp.com.pk/account/products',
            'license_site'               => '',
            'license_last_checked'        => 0,
            'license_customer_email'      => '',
            'license_site_count'          => 0,
            'license_limit'               => 0,
            'license_error'               => '',
            'license_activation_token'    => '',

            // Anti-Spam & Bot Shield
            'anti_spam_provider'         => 'none', // 'none', 'recaptcha_v2', 'recaptcha_v3', 'turnstile'
            'recaptcha_site_key'         => '',
            'recaptcha_secret_key'       => '',
            'recaptcha_v3_score'         => '0.5',
            'turnstile_site_key'         => '',
            'turnstile_secret_key'       => '',
            'enable_time_gate'           => true,
            'time_gate_seconds'          => 2,

            // Rate Limiting & Anti-Flood
            'enable_rate_limiting'       => true,
            'rate_limit_max'             => 5, // max submissions
            'rate_limit_window'          => 5, // window in minutes
            'blacklisted_ips'            => '',
            'blocked_keywords'           => '',

            // Storage & File Sandboxing
            'enable_upload_hardening'    => true,
            'enable_mime_sniffing'       => true,
            'enable_csv_injection_shield'=> true,
        );

        $saved = get_option( self::OPTION_KEY, array() );
        if ( ! is_array( $saved ) ) {
            $saved = array();
        }
        if ( ! isset( $saved['store_pricing_url'] ) && isset( $saved['edd_pricing_url'] ) ) {
            $saved['store_pricing_url'] = $saved['edd_pricing_url'];
        }
        if ( ! isset( $saved['store_account_url'] ) && isset( $saved['edd_account_url'] ) ) {
            $saved['store_account_url'] = $saved['edd_account_url'];
        }

        return wp_parse_args( $saved, $defaults );
    }

    /**
     * Save settings to options.
     */
    public static function save_settings( $settings ) {
        $current = self::get_settings();
        $updated = array_merge( $current, $settings );
        return update_option( self::OPTION_KEY, $updated, false );
    }

    /**
     * Check if Pro version is active and licensed.
     */
    public static function is_pro_active() {
        return defined( 'USP_FORM_BUILDER_EDITION' ) && 'pro' === USP_FORM_BUILDER_EDITION && self::license_valid();
    }

    public static function site_identity() {
        $parts = parse_url( home_url() );
        return strtolower( rtrim( $parts['host'] ?? '', '.' ) ) .
            ( ! empty( $parts['port'] ) && ! in_array( $parts['port'], array( 80, 443 ), true ) ? ':' . $parts['port'] : '' ) .
            rtrim( $parts['path'] ?? '', '/' );
    }

    public static function cached_license_valid( $settings ) {
        return 'valid' === $settings['license_status'] && ! empty( $settings['license_activation_token'] ) &&
            ! empty( $settings['license_expires'] ) && strtotime( $settings['license_expires'] ) > time() &&
            hash_equals( (string) $settings['license_site'], self::site_identity() );
    }

    public static function license_valid() {
        $settings = self::get_settings();
        if ( empty( $settings['license_key'] ) || empty( $settings['pro_enabled'] ) ) {
            return false;
        }

        $last_checked = absint( $settings['license_last_checked'] );
        if ( self::cached_license_valid( $settings ) && $last_checked > time() - DAY_IN_SECONDS ) {
            return true;
        }

        if ( get_transient( 'usp_form_builder_license_check_lock' ) ) {
            return self::cached_license_valid( $settings ) && $last_checked > time() - ( 3 * DAY_IN_SECONDS );
        }
        set_transient( 'usp_form_builder_license_check_lock', 1, HOUR_IN_SECONDS );

        $response = self::request_license_api( 'check_license', $settings['license_key'] );
        if ( is_wp_error( $response ) ) {
            $settings['license_error'] = $response->get_error_message();
            self::save_settings( $settings );
            return self::cached_license_valid( $settings ) && $last_checked > time() - ( 3 * DAY_IN_SECONDS );
        }

        $settings['license_last_checked']   = time();
        $settings['license_error']          = '';
        $settings['license_status']         = ! empty( $response['success'] ) && isset( $response['license'] ) && 'valid' === $response['license']
            ? 'valid'
            : ( ! empty( $response['error'] ) ? sanitize_key( $response['error'] ) : 'invalid' );
        $settings['pro_enabled']            = 'valid' === $settings['license_status'];
        $settings['license_expires']        = ! empty( $response['expires'] ) ? sanitize_text_field( $response['expires'] ) : '';
        $settings['license_customer_email'] = ! empty( $response['customer_email'] ) ? sanitize_email( $response['customer_email'] ) : '';
        $settings['license_site_count']     = isset( $response['site_count'] ) ? absint( $response['site_count'] ) : 0;
        $settings['license_limit']          = isset( $response['license_limit'] ) ? absint( $response['license_limit'] ) : 0;
        if ( 'valid' === $settings['license_status'] && ! empty( $response['activation_token'] ) ) {
            $settings['license_activation_token'] = sanitize_text_field( $response['activation_token'] );
            $settings['license_site'] = self::site_identity();
        }
        self::save_settings( $settings );

        return self::cached_license_valid( $settings );
    }

    /**
     * Send a product-scoped license request to the USP product store.
     *
     * @param string $action  Store license action.
     * @param string $license License key.
     * @return array|WP_Error
     */
    public static function request_license_api( $action, $license ) {
        $settings = self::get_settings();
        $product_slug = defined( 'USP_FORM_BUILDER_PRODUCT_SLUG' )
            ? (string) USP_FORM_BUILDER_PRODUCT_SLUG
            : 'usp-form-builder';
        if ( strlen( $product_slug ) > 100 || ! preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $product_slug ) ) {
            return new WP_Error(
                'usp_license_product_not_configured',
                __( 'Configure a valid product slug in the Pro & Security Hub first.', 'usp-form-builder' )
            );
        }

        $payload = array(
            'action'  => sanitize_key( $action ),
            'product' => $product_slug,
            'license' => $license,
            'url'     => home_url(),
        );
        if ( ! empty( $settings['license_activation_token'] ) ) {
            $payload['token'] = $settings['license_activation_token'];
        }

        $response = wp_safe_remote_post( 'https://usp.com.pk/api/licenses/v1', array(
            'timeout'             => 10,
            'redirection'         => 0,
            'limit_response_size'  => 16384,
            'reject_unsafe_urls'  => true,
            'headers'             => array( 'Accept' => 'application/json', 'Content-Type' => 'application/json' ),
            'body'                => wp_json_encode( $payload ),
        ) );

        if ( is_wp_error( $response ) ) {
            return new WP_Error(
                'usp_license_connection_failed',
                sprintf(
                    /* translators: %s: HTTP API error */
                    __( 'Could not contact the license server: %s', 'usp-form-builder' ),
                    $response->get_error_message()
                )
            );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $status_code ) {
            return new WP_Error(
                'usp_license_server_error',
                sprintf(
                    /* translators: %d: HTTP status code */
                    __( 'The license server returned HTTP %d. Try again later.', 'usp-form-builder' ),
                    absint( $status_code )
                )
            );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $data ) || ! array_key_exists( 'success', $data ) ) {
            return new WP_Error(
                'usp_license_invalid_response',
                __( 'The license server returned an invalid response. Check the product store configuration on usp.com.pk.', 'usp-form-builder' )
            );
        }

        if ( ! empty( $data['success'] ) && 'deactivate_license' !== $action &&
            ( 'valid' !== ( $data['license'] ?? '' ) || empty( $data['expires'] ) || strtotime( $data['expires'] ) <= time() ||
              ! preg_match( '/^[a-f0-9]{64}$/', $data['activation_token'] ?? '' ) ) ) {
            return new WP_Error( 'usp_invalid_entitlement', __( 'The store returned an invalid or expired license entitlement.', 'usp-form-builder' ) );
        }
        return $data;
    }

    /**
     * Activate or Deactivate Pro license.
     */
    public static function process_license_action( $key, $action = 'activate' ) {
        $settings = self::get_settings();
        $key      = trim( sanitize_text_field( $key ) );

        if ( 'deactivate' === $action ) {
            $remote_result = true;
            if ( ! empty( $settings['license_key'] ) ) {
                $remote_result = self::request_license_api( 'deactivate_license', $settings['license_key'] );
            }
            $settings['license_key']    = '';
            $settings['license_status'] = 'inactive';
            $settings['pro_enabled']    = false;
            $settings['license_expires'] = '';
            $settings['license_last_checked'] = 0;
            $settings['license_customer_email'] = '';
            $settings['license_error'] = '';
            $settings['license_activation_token'] = '';
            self::save_settings( $settings );

            if ( is_wp_error( $remote_result ) || ( is_array( $remote_result ) && empty( $remote_result['success'] ) ) ) {
                return array(
                    'success' => true,
                    'message' => __( 'Pro was disabled on this site, but the license server could not confirm deactivation. The key may still count this site toward its activation limit.', 'usp-form-builder' ),
                );
            }

            return array(
                'success' => true,
                'message' => __( 'License deactivated successfully.', 'usp-form-builder' ),
            );
        }

        if ( 'check' === $action ) {
            if ( empty( $settings['license_key'] ) ) {
                return array( 'success' => false, 'message' => __( 'No license key is currently activated.', 'usp-form-builder' ) );
            }
            $response = self::request_license_api( 'check_license', $settings['license_key'] );
            if ( is_wp_error( $response ) ) {
                $settings['license_error'] = $response->get_error_message();
                self::save_settings( $settings );
                return array( 'success' => false, 'message' => $response->get_error_message() );
            }

            $settings['license_last_checked']   = time();
            $settings['license_error']          = '';
            $settings['license_status']         = ! empty( $response['success'] ) && isset( $response['license'] ) && 'valid' === $response['license']
                ? 'valid'
                : ( ! empty( $response['error'] ) ? sanitize_key( $response['error'] ) : 'invalid' );
            $settings['pro_enabled']            = 'valid' === $settings['license_status'];
            $settings['license_expires']        = ! empty( $response['expires'] ) ? sanitize_text_field( $response['expires'] ) : '';
            $settings['license_customer_email'] = ! empty( $response['customer_email'] ) ? sanitize_email( $response['customer_email'] ) : '';
            $settings['license_site_count']     = isset( $response['site_count'] ) ? absint( $response['site_count'] ) : 0;
            $settings['license_limit']          = isset( $response['license_limit'] ) ? absint( $response['license_limit'] ) : 0;
            if ( 'valid' === $settings['license_status'] && ! empty( $response['activation_token'] ) ) {
                $settings['license_activation_token'] = sanitize_text_field( $response['activation_token'] );
            $settings['license_site'] = self::site_identity();
            }
            self::save_settings( $settings );
            delete_transient( 'usp_form_builder_license_check_lock' );

            $active = 'valid' === $settings['license_status'];
            return array(
                'success' => $active,
                'message' => $active
                    ? __( 'License verified with usp.com.pk.', 'usp-form-builder' )
                    : sprintf(
                        /* translators: %s: license status */
                        __( 'The license is not active for this site (%s).', 'usp-form-builder' ),
                        $settings['license_status']
                    ),
            );
        }

        if ( empty( $key ) ) {
            return array(
                'success' => false,
                'message' => __( 'Please enter a valid license key.', 'usp-form-builder' ),
            );
        }

        if ( ! preg_match( '/^[A-Za-z0-9_-]{1,256}$/', $key ) ) {
            return array(
                'success' => false,
                'message' => __( 'The license key contains unsupported characters.', 'usp-form-builder' ),
            );
        }

        $response = self::request_license_api( 'activate_license', $key );
        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'message' => $response->get_error_message() );
        }
        if ( empty( $response['success'] ) || ! isset( $response['license'] ) || 'valid' !== $response['license'] ) {
            $error = ! empty( $response['error'] ) ? sanitize_key( $response['error'] ) : 'invalid';
            return array(
                'success' => false,
                'message' => sprintf(
                    /* translators: %s: store license error code */
                    __( 'License activation was rejected by usp.com.pk (%s). Check the key, product, expiry, and activation limit.', 'usp-form-builder' ),
                    $error
                ),
            );
        }

        $settings['license_key']             = $key;
        $settings['license_status']          = 'valid';
        $settings['license_expires']         = ! empty( $response['expires'] ) ? sanitize_text_field( $response['expires'] ) : '';
        $settings['license_customer_email']  = ! empty( $response['customer_email'] ) ? sanitize_email( $response['customer_email'] ) : '';
        $settings['license_site_count']      = isset( $response['site_count'] ) ? absint( $response['site_count'] ) : 0;
        $settings['license_limit']           = isset( $response['license_limit'] ) ? absint( $response['license_limit'] ) : 0;
        $settings['license_activation_token'] = ! empty( $response['activation_token'] ) ? sanitize_text_field( $response['activation_token'] ) : '';
        $settings['license_site']            = self::site_identity();
        $settings['license_last_checked']    = time();
        $settings['license_error']           = '';
        $settings['pro_enabled']              = true;
        self::save_settings( $settings );
        delete_transient( 'usp_form_builder_license_check_lock' );

        return array(
            'success' => true,
            'message' => __( 'USP Form Builder Pro license activated and verified with usp.com.pk.', 'usp-form-builder' ),
            'expires' => $settings['license_expires'],
        );
    }

    /**
     * Generate encrypted submission time token for Time-Gate bot trap.
     */
    public static function generate_time_token( $form_id ) {
        $timestamp = time();
        $salt      = wp_salt( 'nonce' );
        $signature = hash_hmac( 'sha256', $form_id . '|' . $timestamp, $salt );
        return base64_encode( $form_id . '|' . $timestamp . '|' . $signature );
    }

    /**
     * Verify submission time token.
     * Rejects submissions that happen too quickly (< X seconds) or token replay (> 2 hours).
     */
    public static function verify_time_token( $token, $form_id ) {
        $settings = self::get_settings();
        if ( empty( $settings['enable_time_gate'] ) ) {
            return true; // Time-gate disabled
        }

        $min_seconds = ! empty( $settings['time_gate_seconds'] ) ? absint( $settings['time_gate_seconds'] ) : 2;

        if ( empty( $token ) ) {
            return new WP_Error( 'usp_bot_detected', __( 'Automated submission detected (Missing verification token).', 'usp-form-builder' ) );
        }

        $decoded = base64_decode( $token, true );
        if ( ! $decoded ) {
            return new WP_Error( 'usp_bot_detected', __( 'Invalid security token.', 'usp-form-builder' ) );
        }

        $parts = explode( '|', $decoded );
        if ( 3 !== count( $parts ) ) {
            return new WP_Error( 'usp_bot_detected', __( 'Corrupted security token.', 'usp-form-builder' ) );
        }

        list( $tok_form_id, $timestamp, $signature ) = $parts;

        if ( (int) $tok_form_id !== (int) $form_id ) {
            return new WP_Error( 'usp_bot_detected', __( 'Security token mismatch.', 'usp-form-builder' ) );
        }

        $salt             = wp_salt( 'nonce' );
        $expected_sig     = hash_hmac( 'sha256', $tok_form_id . '|' . $timestamp, $salt );
        if ( ! hash_equals( $expected_sig, $signature ) ) {
            return new WP_Error( 'usp_bot_detected', __( 'Tampered security token.', 'usp-form-builder' ) );
        }

        $now     = time();
        $elapsed = $now - (int) $timestamp;

        // Too fast = Bot
        if ( $elapsed < $min_seconds ) {
            return new WP_Error( 'usp_bot_too_fast', sprintf( __( 'Submission rejected: Form was completed unnaturally fast (%d sec). Please take a moment and submit again.', 'usp-form-builder' ), $elapsed ) );
        }

        // Expired (older than 2 hours)
        if ( $elapsed > 7200 ) {
            return new WP_Error( 'usp_token_expired', __( 'Form session expired. Please refresh the page and try again.', 'usp-form-builder' ) );
        }

        return true;
    }

    /**
     * Check IP Rate Limiting to prevent brute-force and DDoS flooding.
     */
    public static function check_rate_limit( $ip, $form_id ) {
        $settings = self::get_settings();
        if ( empty( $settings['enable_rate_limiting'] ) ) {
            return true;
        }

        $max_submissions = ! empty( $settings['rate_limit_max'] ) ? absint( $settings['rate_limit_max'] ) : 5;
        $window_minutes  = ! empty( $settings['rate_limit_window'] ) ? absint( $settings['rate_limit_window'] ) : 5;
        $window_seconds  = $window_minutes * MINUTE_IN_SECONDS;

        $cache_key = 'usp_rl_' . md5( $ip . '_' . $form_id );
        $current   = get_transient( $cache_key );

        if ( false === $current ) {
            set_transient( $cache_key, 1, $window_seconds );
            return true;
        }

        if ( (int) $current >= $max_submissions ) {
            return new WP_Error(
                'usp_rate_limited',
                sprintf(
                    __( 'Too many submissions from your network. Please wait %d minutes before trying again.', 'usp-form-builder' ),
                    $window_minutes
                )
            );
        }

        set_transient( $cache_key, (int) $current + 1, $window_seconds );
        return true;
    }

    /**
     * Check if client IP is blacklisted.
     */
    public static function is_ip_blacklisted( $ip ) {
        $settings = self::get_settings();
        if ( empty( $settings['blacklisted_ips'] ) ) {
            return false;
        }

        $lines = explode( "\n", str_replace( "\r", "", $settings['blacklisted_ips'] ) );
        $ip    = trim( $ip );

        foreach ( $lines as $line ) {
            $line = trim( $line );
            if ( empty( $line ) || 0 === strpos( $line, '#' ) ) {
                continue;
            }

            // Check exact IP
            if ( $line === $ip ) {
                return true;
            }

            // Check subnet CIDR
            if ( false !== strpos( $line, '/' ) ) {
                if ( self::ip_in_range( $ip, $line ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * CIDR Range matcher helper.
     */
    private static function ip_in_range( $ip, $range ) {
        if ( false === strpos( $range, '/' ) ) {
            return $ip === $range;
        }
        list( $subnet, $bits ) = explode( '/', $range );
        $ip_long     = ip2long( $ip );
        $subnet_long = ip2long( $subnet );
        if ( false === $ip_long || false === $subnet_long ) {
            return false;
        }
        $mask = -1 << ( 32 - (int) $bits );
        return ( ( $ip_long & $mask ) === ( $subnet_long & $mask ) );
    }

    /**
     * Check submitted entries for blocked spam keywords / phishing.
     */
    public static function check_spam_keywords( $submitted_entry ) {
        $settings = self::get_settings();
        if ( empty( $settings['blocked_keywords'] ) ) {
            return true;
        }

        $keywords = array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", "", strtolower( $settings['blocked_keywords'] ) ) ) ) );
        if ( empty( $keywords ) ) {
            return true;
        }

        $all_text = '';
        foreach ( $submitted_entry as $field ) {
            if ( is_string( $field['value'] ) ) {
                $all_text .= ' ' . strtolower( $field['value'] );
            } elseif ( is_array( $field['value'] ) ) {
                $all_text .= ' ' . strtolower( implode( ' ', array_values( $field['value'] ) ) );
            }
        }

        foreach ( $keywords as $kw ) {
            if ( '' !== $kw && false !== strpos( $all_text, $kw ) ) {
                return new WP_Error( 'usp_spam_keyword', sprintf( __( 'Your submission was flagged by security filter for blocked content ("%s").', 'usp-form-builder' ), esc_html( $kw ) ) );
            }
        }

        return true;
    }

    /**
     * Verify CAPTCHA (reCAPTCHA v2/v3, Turnstile).
     */
    public static function verify_captcha( $token, $ip = '' ) {
        $settings = self::get_settings();
        $provider = ! empty( $settings['anti_spam_provider'] ) ? $settings['anti_spam_provider'] : 'none';

        if ( 'none' === $provider ) {
            return true;
        }

        if ( empty( $token ) ) {
            return new WP_Error( 'usp_captcha_missing', __( 'Please complete the anti-spam verification before submitting.', 'usp-form-builder' ) );
        }

        if ( 'turnstile' === $provider ) {
            $secret = ! empty( $settings['turnstile_secret_key'] ) ? trim( $settings['turnstile_secret_key'] ) : '';
            if ( empty( $secret ) ) {
                return true; // Bypass if not configured
            }

            $response = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', array(
                'timeout' => 10,
                'body'    => array(
                    'secret'   => $secret,
                    'response' => $token,
                    'remoteip' => $ip,
                ),
            ) );

            if ( is_wp_error( $response ) ) {
                return new WP_Error( 'usp_captcha_failed', __( 'Could not connect to Cloudflare Turnstile service.', 'usp-form-builder' ) );
            }

            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( empty( $data['success'] ) ) {
                return new WP_Error( 'usp_captcha_invalid', __( 'Cloudflare Turnstile verification failed. Please try again.', 'usp-form-builder' ) );
            }

            return true;
        }

        // Google reCAPTCHA v2 / v3
        if ( in_array( $provider, array( 'recaptcha_v2', 'recaptcha_v3' ), true ) ) {
            $secret = ! empty( $settings['recaptcha_secret_key'] ) ? trim( $settings['recaptcha_secret_key'] ) : '';
            if ( empty( $secret ) ) {
                return true; // Bypass if not configured
            }

            $response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', array(
                'timeout' => 10,
                'body'    => array(
                    'secret'   => $secret,
                    'response' => $token,
                    'remoteip' => $ip,
                ),
            ) );

            if ( is_wp_error( $response ) ) {
                return new WP_Error( 'usp_captcha_failed', __( 'Could not connect to Google reCAPTCHA service.', 'usp-form-builder' ) );
            }

            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( empty( $data['success'] ) ) {
                return new WP_Error( 'usp_captcha_invalid', __( 'Google reCAPTCHA verification failed. Please try again.', 'usp-form-builder' ) );
            }

            // For v3, verify score threshold
            if ( 'recaptcha_v3' === $provider && isset( $data['score'] ) ) {
                $min_score = (float) ( ! empty( $settings['recaptcha_v3_score'] ) ? $settings['recaptcha_v3_score'] : 0.5 );
                if ( (float) $data['score'] < $min_score ) {
                    return new WP_Error( 'usp_captcha_low_score', __( 'Suspicious activity detected. Submission blocked by reCAPTCHA v3.', 'usp-form-builder' ) );
                }
            }

            return true;
        }

        return true;
    }

    /**
     * Protect upload directory by auto-generating an .htaccess file that disables PHP script execution.
     */
    public static function protect_upload_directory( $force = false ) {
        $upload_dir = wp_upload_dir();
        if ( ! empty( $upload_dir['error'] ) || empty( $upload_dir['basedir'] ) ) {
            return new WP_Error(
                'usp_upload_directory_unavailable',
                __( 'The WordPress uploads directory is unavailable.', 'usp-form-builder' )
            );
        }

        $target_dir = trailingslashit( $upload_dir['basedir'] ) . 'usp-form-builder';
        if ( ! wp_mkdir_p( $target_dir ) && ! is_dir( $target_dir ) ) {
            return new WP_Error(
                'usp_sandbox_directory_unavailable',
                __( 'Could not create the USP Form Builder upload sandbox. Check the uploads directory permissions.', 'usp-form-builder' )
            );
        }

        $files = array(
            $target_dir . '/.htaccess' => "# USP Form Builder - Hardened Uploads Sandbox\n" .
                "# Block execution of server-side and other executable files.\n" .
                "<FilesMatch \"(?i)\.(php[0-9]?|phtml|phar|cgi|pl|py|sh|bash|asp|aspx|jsp|exe|dll|com|bat|cmd|vbs|jar)$\">\n" .
                "    <IfModule mod_authz_core.c>\n" .
                "        Require all denied\n" .
                "    </IfModule>\n" .
                "    <IfModule !mod_authz_core.c>\n" .
                "        Order allow,deny\n" .
                "        Deny from all\n" .
                "    </IfModule>\n" .
                "</FilesMatch>\n" .
                "# Serve the silent index file instead of listing directory contents.\n" .
                "DirectoryIndex index.php\n",
            $target_dir . '/index.php' => "<?php\nhttp_response_code( 403 );\nexit;\n",
        );

        foreach ( $files as $file_path => $contents ) {
            if ( file_exists( $file_path ) && ! $force ) {
                continue;
            }

            if ( is_file( $file_path ) && is_readable( $file_path ) && $contents === file_get_contents( $file_path ) ) {
                continue;
            }

            $written = @file_put_contents( $file_path, $contents, LOCK_EX );
            if ( false === $written || $written !== strlen( $contents ) ) {
                return new WP_Error(
                    'usp_sandbox_file_write_failed',
                    sprintf(
                        /* translators: %s: filename */
                        __( 'Could not write the upload protection file %s. Check the uploads directory permissions.', 'usp-form-builder' ),
                        basename( $file_path )
                    )
                );
            }
        }

        return true;
    }

    /**
     * Validate and harden uploaded file.
     * Prevents dangerous executable uploads, verifies real MIME, and enforces size limits.
     */
    public static function validate_file_upload( $file, $allowed_extensions = array(), $max_mb = 5 ) {
        if ( ! isset( $file['name'] ) || ! isset( $file['tmp_name'] ) ) {
            return new WP_Error( 'usp_file_invalid', __( 'Invalid file upload data.', 'usp-form-builder' ) );
        }

        $file_name = sanitize_file_name( $file['name'] );
        $file_ext  = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );

        // 1. Strict Dangerous Extension Blacklist check
        if ( in_array( $file_ext, self::$blacklisted_extensions, true ) ) {
            return new WP_Error( 'usp_file_executable', __( 'Security Alert: Executable scripts or system files cannot be uploaded.', 'usp-form-builder' ) );
        }

        // 2. Check against declared allowed extensions
        if ( ! empty( $allowed_extensions ) && ! in_array( $file_ext, $allowed_extensions, true ) ) {
            return new WP_Error( 'usp_file_type', sprintf( __( 'File type .%s is not permitted.', 'usp-form-builder' ), esc_html( $file_ext ) ) );
        }

        // 3. File size check
        if ( $file['size'] > ( $max_mb * 1024 * 1024 ) ) {
            return new WP_Error( 'usp_file_size', sprintf( __( 'File exceeds the maximum limit of %sMB.', 'usp-form-builder' ), $max_mb ) );
        }

        // 4. Real MIME Sniffing verification using finfo if available
        $settings = self::get_settings();
        if ( ! empty( $settings['enable_mime_sniffing'] ) && function_exists( 'finfo_open' ) && ! empty( $file['tmp_name'] ) && file_exists( $file['tmp_name'] ) ) {
            $finfo     = finfo_open( FILEINFO_MIME_TYPE );
            $mime_type = finfo_file( $finfo, $file['tmp_name'] );
            finfo_close( $finfo );

            $dangerous_mimes = array(
                'application/x-php',
                'text/x-php',
                'application/php',
                'application/x-httpd-php',
                'application/x-httpd-php-source',
                'application/x-sh',
                'application/x-csh',
                'application/x-executable',
                'application/x-msdownload',
                'application/x-sharedlib',
            );

            if ( in_array( $mime_type, $dangerous_mimes, true ) ) {
                return new WP_Error( 'usp_mime_mismatch', __( 'Security Alert: Uploaded file contains dangerous binary/script content.', 'usp-form-builder' ) );
            }
        }

        return true;
    }

    /**
     * CSV Formula Injection Shield.
     * Prevents Excel DDE formula injection (cells starting with =, +, -, @, tab, or carriage return).
     */
    public static function sanitize_csv_cell( $cell ) {
        $settings = self::get_settings();
        if ( empty( $settings['enable_csv_injection_shield'] ) ) {
            return $cell;
        }

        if ( ! is_string( $cell ) ) {
            return $cell;
        }

        $trigger_chars = array( '=', '+', '-', '@', "\t", "\r" );
        if ( '' !== $cell && in_array( $cell[0], $trigger_chars, true ) ) {
            // Prepend single quote to neutralize formula execution in spreadsheet software
            return "'" . $cell;
        }

        return $cell;
    }

    /**
     * Dispatch Outgoing Webhook Payload (Zapier, Make, n8n, Slack, Custom Endpoint).
     *
     * @param string $webhook_url Target URL.
     * @param array  $payload     Data payload.
     * @param string $secret      Optional HMAC signature secret / token.
     * @return array|WP_Error
     */
    public static function dispatch_webhook( $webhook_url, $payload, $secret = '' ) {
        if ( empty( $webhook_url ) || ! filter_var( $webhook_url, FILTER_VALIDATE_URL ) ) {
            return new WP_Error( 'invalid_url', __( 'Invalid Webhook URL.', 'usp-form-builder' ) );
        }

        $json_body = wp_json_encode( $payload );
        $headers   = array(
            'Content-Type' => 'application/json; charset=UTF-8',
            'User-Agent'   => 'USP-Form-Builder-Pro/' . USP_FORM_BUILDER_VERSION . '; ' . home_url(),
        );

        if ( ! empty( $secret ) ) {
            $signature = hash_hmac( 'sha256', $json_body, $secret );
            $headers['X-USP-Signature'] = $signature;
            $headers['Authorization']   = 'Bearer ' . $secret;
        }

        $response = wp_remote_post( $webhook_url, array(
            'method'      => 'POST',
            'timeout'     => 10,
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking'    => true,
            'headers'     => $headers,
            'body'        => $json_body,
            'sslverify'   => false, // Permit local sandbox self-signed certs
        ) );

        return $response;
    }
}
