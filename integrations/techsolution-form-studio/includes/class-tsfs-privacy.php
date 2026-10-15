<?php
/**
 * Privacy tools: WordPress personal data export/erasure, privacy policy text, and entry retention.
 *
 * @package    TSFS_Plugin
 * @subpackage TSFS_Plugin/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TSFS_Privacy {

    const CRON_HOOK = 'tsfs_daily_cleanup';

    public static function init() {
        add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'register_exporter' ) );
        add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'register_eraser' ) );
        add_action( 'admin_init', array( __CLASS__, 'add_policy_content' ) );
        add_action( self::CRON_HOOK, array( __CLASS__, 'run_cleanup' ) );
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            self::schedule_cleanup();
        }
    }

    public static function schedule_cleanup() {
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
        }
    }

    /**
     * Delete entries older than the configured retention period.
     */
    public static function run_cleanup() {
        $settings = TSFS_Security::get_settings();
        if ( ! empty( $settings['entry_retention_days'] ) ) {
            TSFS_DB::delete_entries_older_than( $settings['entry_retention_days'] );
        }
    }

    public static function register_exporter( $exporters ) {
        $exporters['techsolution-form-studio'] = array(
            'exporter_friendly_name' => __( 'TechSolution Form Studio submissions', 'techsolution-form-studio' ),
            'callback'               => array( __CLASS__, 'export_personal_data' ),
        );
        return $exporters;
    }

    public static function register_eraser( $erasers ) {
        $erasers['techsolution-form-studio'] = array(
            'eraser_friendly_name' => __( 'TechSolution Form Studio submissions', 'techsolution-form-studio' ),
            'callback'             => array( __CLASS__, 'erase_personal_data' ),
        );
        return $erasers;
    }

    /**
     * Export every submission that contains the requester's email address.
     */
    public static function export_personal_data( $email, $page = 1 ) {
        $per_page = 50;
        $page     = max( 1, absint( $page ) );
        $entries  = TSFS_DB::get_entries_by_email( $email, $per_page, ( $page - 1 ) * $per_page );
        $items    = array();

        foreach ( $entries as $entry ) {
            $data = array(
                array( 'name' => __( 'Submitted at', 'techsolution-form-studio' ), 'value' => $entry['created_at'] ),
            );
            if ( ! empty( $entry['user_ip'] ) ) {
                $data[] = array( 'name' => __( 'IP address', 'techsolution-form-studio' ), 'value' => $entry['user_ip'] );
            }
            foreach ( (array) $entry['fields_data'] as $field ) {
                $data[] = array(
                    'name'  => isset( $field['label'] ) ? $field['label'] : '',
                    'value' => TSFS_Form::format_value_text( isset( $field['value'] ) ? $field['value'] : '' ),
                );
            }
            $items[] = array(
                'group_id'    => 'tsfs-submissions',
                'group_label' => __( 'Form submissions', 'techsolution-form-studio' ),
                'item_id'     => 'tsfs-entry-' . $entry['id'],
                'data'        => $data,
            );
        }

        return array(
            'data' => $items,
            'done' => count( $entries ) < $per_page,
        );
    }

    /**
     * Delete every submission that contains the requester's email address.
     */
    public static function erase_personal_data( $email, $page = 1 ) {
        $entries = TSFS_DB::get_entries_by_email( $email, 50, 0 );
        $removed = 0;
        foreach ( $entries as $entry ) {
            if ( TSFS_DB::delete_entry( $entry['id'] ) ) {
                $removed++;
            }
        }
        return array(
            'items_removed'  => $removed > 0,
            'items_retained' => false,
            'messages'       => array(),
            'done'           => count( $entries ) < 50,
        );
    }

    /**
     * Suggested privacy policy text shown under Settings → Privacy.
     */
    public static function add_policy_content() {
        if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
            return;
        }
        $content  = '<p>' . esc_html__( 'When you submit a form on this site, the information you enter is stored in this site\'s database so that site staff can review it. Depending on the form, it may be emailed to site staff and to you.', 'techsolution-form-studio' ) . '</p>';
        $content .= '<p>' . esc_html__( 'The site may also store your IP address and browser user agent with the submission to help prevent spam and abuse.', 'techsolution-form-studio' ) . '</p>';
        $content .= '<p>' . esc_html__( 'If the site enables Google reCAPTCHA or Cloudflare Turnstile, the form loads a script from that provider, and your verification response and IP address are sent to the provider to check that you are not a bot.', 'techsolution-form-studio' ) . '</p>';
        $content .= '<p>' . esc_html__( 'If the site enables a webhook for a form, your submission is sent to the address configured by the site owner.', 'techsolution-form-studio' ) . '</p>';
        wp_add_privacy_policy_content( 'TechSolution Form Studio', wp_kses_post( $content ) );
    }
}
