<?php
/**
 * Security engine for TechSolution Form Studio.
 * Multi-layer Bot Shield, Rate Limiting, File Sandboxing, and Anti-Spam protection.
 *
 * @package    TSFS_Plugin
 * @subpackage TSFS_Plugin/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TSFS_Security {

    const OPTION_KEY = 'tsfs_settings';

    /**
     * Dangerous executable file extensions that must NEVER be uploaded under any circumstances.
     */
    private static $blacklisted_extensions = array(
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar', 'phps',
        'cgi', 'pl', 'py', 'asp', 'aspx', 'jsp', 'sh', 'bash', 'bin', 'exe',
        'bat', 'cmd', 'vbs', 'vbe', 'js', 'jse', 'wsf', 'wsh', 'com', 'scr',
        'msi', 'hta', 'cpl', 'jar', 'htaccess', 'htpasswd', 'ini', 'sql',
        'htm', 'html', 'xhtml', 'shtml', 'svg', 'svgz', 'xml', 'swf'
    );

    /**
     * Extensions that are never accepted, whatever a form allows.
     */
    public static function blocked_extensions() {
        return self::$blacklisted_extensions;
    }

    /**
     * Default security, anti-spam and privacy settings.
     */
    public static function default_settings() {
        return array(
            // Anti-Spam & Bot Shield
            'anti_spam_provider'          => 'none', // 'none', 'recaptcha_v2', 'recaptcha_v3', 'turnstile'
            'recaptcha_site_key'          => '',
            'recaptcha_secret_key'        => '',
            'recaptcha_v3_score'          => '0.5',
            'turnstile_site_key'          => '',
            'turnstile_secret_key'        => '',
            'enable_time_gate'            => true,
            'time_gate_seconds'           => 2,

            // Rate Limiting & Anti-Flood
            'enable_rate_limiting'        => true,
            'rate_limit_max'              => 5, // max submissions
            'rate_limit_window'           => 5, // window in minutes
            'blacklisted_ips'             => '',
            'blocked_keywords'            => '',

            // Storage & File Sandboxing
            'enable_upload_hardening'     => true,
            'enable_mime_sniffing'        => true,
            'enable_csv_injection_shield' => true,

            // Privacy
            'store_ip_address'            => true,
            'store_user_agent'            => true,
            'entry_retention_days'        => 0, // 0 keeps entries until deleted manually
            'delete_data_on_uninstall'    => false,
        );
    }

    /**
     * Get settings merged with defaults.
     */
    public static function get_settings() {
        $saved = get_option( self::OPTION_KEY, array() );
        if ( ! is_array( $saved ) ) {
            $saved = array();
        }
        return wp_parse_args( $saved, self::default_settings() );
    }

    /**
     * Save settings to options.
     */
    public static function save_settings( $settings ) {
        $current = self::get_settings();
        $updated = array_intersect_key( array_merge( $current, $settings ), self::default_settings() );
        return update_option( self::OPTION_KEY, $updated, false );
    }

    /**
     * Generate encrypted submission time token for Time-Gate bot trap.
     */
    public static function generate_time_token( $form_id ) {
        $timestamp = time();
        $salt      = wp_salt( 'nonce' );
        $signature = hash_hmac( 'sha256', $form_id . '|' . $timestamp, $salt );
        return $form_id . '|' . $timestamp . '|' . $signature;
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
            return new WP_Error( 'tsfs_bot_detected', __( 'Automated submission detected (Missing verification token).', 'techsolution-form-studio' ) );
        }

        $decoded = (string) $token;

        $parts = explode( '|', $decoded );
        if ( 3 !== count( $parts ) ) {
            return new WP_Error( 'tsfs_bot_detected', __( 'Corrupted security token.', 'techsolution-form-studio' ) );
        }

        list( $tok_form_id, $timestamp, $signature ) = $parts;

        if ( (int) $tok_form_id !== (int) $form_id ) {
            return new WP_Error( 'tsfs_bot_detected', __( 'Security token mismatch.', 'techsolution-form-studio' ) );
        }

        $salt             = wp_salt( 'nonce' );
        $expected_sig     = hash_hmac( 'sha256', $tok_form_id . '|' . $timestamp, $salt );
        if ( ! hash_equals( $expected_sig, $signature ) ) {
            return new WP_Error( 'tsfs_bot_detected', __( 'Tampered security token.', 'techsolution-form-studio' ) );
        }

        $now     = time();
        $elapsed = $now - (int) $timestamp;

        // Too fast = Bot
        if ( $elapsed < $min_seconds ) {
            /* translators: placeholders are values inserted into the message: value inserted into the message. */
            return new WP_Error( 'tsfs_bot_too_fast', sprintf( __( 'Submission rejected: Form was completed unnaturally fast (%d sec). Please take a moment and submit again.', 'techsolution-form-studio' ), $elapsed ) );
        }

        // Expired (older than 2 hours)
        if ( $elapsed > 7200 ) {
            return new WP_Error( 'tsfs_token_expired', __( 'Form session expired. Please refresh the page and try again.', 'techsolution-form-studio' ) );
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

        $cache_key = 'tsfs_rl_' . md5( $ip . '_' . $form_id );
        $current   = get_transient( $cache_key );

        if ( false === $current ) {
            set_transient( $cache_key, 1, $window_seconds );
            return true;
        }

        if ( (int) $current >= $max_submissions ) {
            return new WP_Error(
                'tsfs_rate_limited',
                sprintf(
                    /* translators: %d: number of minutes to wait */
                    __( 'Too many submissions from your network. Please wait %d minutes before trying again.', 'techsolution-form-studio' ),
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
                /* translators: placeholders are values inserted into the message: value inserted into the message. */
                return new WP_Error( 'tsfs_spam_keyword', sprintf( __( 'Your submission was flagged by security filter for blocked content ("%s").', 'techsolution-form-studio' ), esc_html( $kw ) ) );
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
            return new WP_Error( 'tsfs_captcha_missing', __( 'Please complete the anti-spam verification before submitting.', 'techsolution-form-studio' ) );
        }

        if ( 'turnstile' === $provider ) {
            $secret = ! empty( $settings['turnstile_secret_key'] ) ? trim( $settings['turnstile_secret_key'] ) : '';
            if ( empty( $secret ) ) {
                return true; // Bypass if not configured
            }

            // Server-side verification API call for the optional Turnstile service (disclosed in readme.txt), not offloaded content.
            $response = wp_safe_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', array( // phpcs:ignore PluginCheck.CodeAnalysis.Offloading.OffloadedContent
                'timeout' => 10,
                'body'    => array(
                    'secret'   => $secret,
                    'response' => $token,
                    'remoteip' => $ip,
                ),
            ) );

            if ( is_wp_error( $response ) ) {
                return new WP_Error( 'tsfs_captcha_failed', __( 'Could not connect to Cloudflare Turnstile service.', 'techsolution-form-studio' ) );
            }

            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( empty( $data['success'] ) ) {
                return new WP_Error( 'tsfs_captcha_invalid', __( 'Cloudflare Turnstile verification failed. Please try again.', 'techsolution-form-studio' ) );
            }

            return true;
        }

        // Google reCAPTCHA v2 / v3
        if ( in_array( $provider, array( 'recaptcha_v2', 'recaptcha_v3' ), true ) ) {
            $secret = ! empty( $settings['recaptcha_secret_key'] ) ? trim( $settings['recaptcha_secret_key'] ) : '';
            if ( empty( $secret ) ) {
                return true; // Bypass if not configured
            }

            $response = wp_safe_remote_post( 'https://www.google.com/recaptcha/api/siteverify', array(
                'timeout' => 10,
                'body'    => array(
                    'secret'   => $secret,
                    'response' => $token,
                    'remoteip' => $ip,
                ),
            ) );

            if ( is_wp_error( $response ) ) {
                return new WP_Error( 'tsfs_captcha_failed', __( 'Could not connect to Google reCAPTCHA service.', 'techsolution-form-studio' ) );
            }

            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( empty( $data['success'] ) ) {
                return new WP_Error( 'tsfs_captcha_invalid', __( 'Google reCAPTCHA verification failed. Please try again.', 'techsolution-form-studio' ) );
            }

            // For v3, verify score threshold
            if ( 'recaptcha_v3' === $provider && isset( $data['score'] ) ) {
                $min_score = (float) ( ! empty( $settings['recaptcha_v3_score'] ) ? $settings['recaptcha_v3_score'] : 0.5 );
                if ( (float) $data['score'] < $min_score ) {
                    return new WP_Error( 'tsfs_captcha_low_score', __( 'Suspicious activity detected. Submission blocked by reCAPTCHA v3.', 'techsolution-form-studio' ) );
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
                'tsfs_upload_directory_unavailable',
                __( 'The WordPress uploads directory is unavailable.', 'techsolution-form-studio' )
            );
        }

        $target_dir = trailingslashit( $upload_dir['basedir'] ) . 'techsolution-form-studio';
        if ( ! wp_mkdir_p( $target_dir ) && ! is_dir( $target_dir ) ) {
            return new WP_Error(
                'tsfs_sandbox_directory_unavailable',
                __( 'Could not create the TechSolution Form Studio upload sandbox. Check the uploads directory permissions.', 'techsolution-form-studio' )
            );
        }

        $files = array(
            $target_dir . '/.htaccess' => "# TechSolution Form Studio - uploaded files are only available through the admin download link.\n" .
                "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n" .
                "<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n",
            $target_dir . '/index.php' => "<?php\nhttp_response_code( 403 );\nexit;\n",
        );

        require_once ABSPATH . 'wp-admin/includes/file.php';
        global $wp_filesystem;
        if ( ! WP_Filesystem() || ! $wp_filesystem ) {
            return new WP_Error( 'tsfs_filesystem_unavailable', __( 'WordPress could not access the filesystem to write the upload protection files.', 'techsolution-form-studio' ) );
        }

        foreach ( $files as $file_path => $contents ) {
            if ( $wp_filesystem->exists( $file_path ) && $contents === $wp_filesystem->get_contents( $file_path ) ) {
                continue;
            }
            if ( ! $wp_filesystem->put_contents( $file_path, $contents, FS_CHMOD_FILE ) ) {
                return new WP_Error(
                    'tsfs_sandbox_file_write_failed',
                    sprintf(
                        /* translators: %s: filename */
                        __( 'Could not write the upload protection file %s. Check the uploads directory permissions.', 'techsolution-form-studio' ),
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
            return new WP_Error( 'tsfs_file_invalid', __( 'Invalid file upload data.', 'techsolution-form-studio' ) );
        }

        $file_name = sanitize_file_name( $file['name'] );
        $file_ext  = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );

        // 1. Strict Dangerous Extension Blacklist check
        if ( in_array( $file_ext, self::$blacklisted_extensions, true ) ) {
            return new WP_Error( 'tsfs_file_executable', __( 'Security Alert: Executable scripts or system files cannot be uploaded.', 'techsolution-form-studio' ) );
        }

        // 2. Check against declared allowed extensions
        if ( ! empty( $allowed_extensions ) && ! in_array( $file_ext, $allowed_extensions, true ) ) {
            /* translators: placeholders are values inserted into the message: value inserted into the message. */
            return new WP_Error( 'tsfs_file_type', sprintf( __( 'File type .%s is not permitted.', 'techsolution-form-studio' ), esc_html( $file_ext ) ) );
        }

        // 3. File size check
        if ( $file['size'] > ( $max_mb * 1024 * 1024 ) ) {
            /* translators: placeholders are values inserted into the message: value inserted into the message. */
            return new WP_Error( 'tsfs_file_size', sprintf( __( 'File exceeds the maximum limit of %sMB.', 'techsolution-form-studio' ), $max_mb ) );
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
                return new WP_Error( 'tsfs_mime_mismatch', __( 'Security Alert: Uploaded file contains dangerous binary/script content.', 'techsolution-form-studio' ) );
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
            return new WP_Error( 'invalid_url', __( 'Invalid Webhook URL.', 'techsolution-form-studio' ) );
        }

        $json_body = wp_json_encode( $payload );
        $headers   = array(
            'Content-Type' => 'application/json; charset=UTF-8',
            'User-Agent'   => 'TechSolution-Form-Studio/' . TSFS_VERSION . '; ' . home_url(),
        );

        if ( ! empty( $secret ) ) {
            $signature = hash_hmac( 'sha256', $json_body, $secret );
            $headers['X-TSFS-Signature'] = $signature;
            $headers['Authorization']   = 'Bearer ' . $secret;
        }

        $response = wp_safe_remote_post( $webhook_url, array(
            'timeout'     => 10,
            'redirection' => 3,
            'headers'     => $headers,
            'body'        => $json_body,
        ) );

        return $response;
    }

    /**
     * Absolute path of the upload sandbox.
     */
    public static function upload_dir() {
        $uploads = wp_upload_dir();
        return trailingslashit( $uploads['basedir'] ) . 'techsolution-form-studio';
    }

    /**
     * Admin-only download link for a stored upload.
     */
    public static function file_download_url( $stored_name ) {
        return add_query_arg( array( 'action' => 'tsfs_download_file', 'file' => rawurlencode( $stored_name ) ), admin_url( 'admin-post.php' ) );
    }

    /**
     * Delete the uploaded files referenced by an entry's fields.
     */
    public static function delete_entry_files( $fields_data ) {
        foreach ( (array) $fields_data as $field ) {
            if ( isset( $field['value']['stored'] ) && is_string( $field['value']['stored'] ) ) {
                $name = sanitize_file_name( basename( $field['value']['stored'] ) );
                $path = trailingslashit( self::upload_dir() ) . $name;
                if ( '' !== $name && is_file( $path ) ) {
                    wp_delete_file( $path );
                }
            }
        }
    }
}
