<?php
/**
 * Fired during plugin activation.
 *
 * @package    USP_Form_Builder
 * @subpackage USP_Form_Builder/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class USP_Activator {

    /**
     * Set up database tables and default data upon plugin activation.
     */
    public static function activate() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        // 1. Table for Forms
        $table_forms = $wpdb->prefix . 'usp_forms';
        $sql_forms = "CREATE TABLE $table_forms (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            fields_data longtext NOT NULL,
            settings longtext NULL,
            views bigint(20) unsigned NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'publish',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta( $sql_forms );

        // 2. Table for Form Entries / Submissions
        $table_entries = $wpdb->prefix . 'usp_entries';
        $sql_entries = "CREATE TABLE $table_entries (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            form_id bigint(20) unsigned NOT NULL,
            fields_data longtext NOT NULL,
            user_ip varchar(100) DEFAULT '',
            user_agent text DEFAULT '',
            status varchar(20) NOT NULL DEFAULT 'unread',
            notes text NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY form_id (form_id)
        ) $charset_collate;";
        dbDelta( $sql_entries );

        // 3. Seed starter "Contact Us" form if no forms exist
        $existing_forms = $wpdb->get_var( "SELECT COUNT(*) FROM $table_forms" );
        if ( 0 == $existing_forms ) {
            self::seed_default_form( $table_forms );
        }

        update_option( 'usp_form_builder_version', USP_FORM_BUILDER_VERSION );
    }

    /**
     * Seeds an initial starter form so user can immediately test.
     */
    private static function seed_default_form( $table_forms ) {
        global $wpdb;

        $default_fields = array(
            array(
                'id'          => 'field_' . uniqid(),
                'type'        => 'text',
                'label'       => 'Your Name',
                'placeholder' => 'Enter your full name',
                'name'        => 'your_name',
                'required'    => true,
                'help_text'   => '',
                'width'       => '100',
            ),
            array(
                'id'          => 'field_' . uniqid(),
                'type'        => 'email',
                'label'       => 'Your Email Address',
                'placeholder' => 'name@example.com',
                'name'        => 'your_email',
                'required'    => true,
                'help_text'   => '',
                'width'       => '100',
            ),
            array(
                'id'          => 'field_' . uniqid(),
                'type'        => 'text',
                'label'       => 'Subject',
                'placeholder' => 'How can we help you?',
                'name'        => 'subject',
                'required'    => false,
                'help_text'   => '',
                'width'       => '100',
            ),
            array(
                'id'          => 'field_' . uniqid(),
                'type'        => 'textarea',
                'label'       => 'Your Message',
                'placeholder' => 'Write your message here...',
                'name'        => 'your_message',
                'required'    => true,
                'help_text'   => '',
                'width'       => '100',
            ),
        );

        $default_settings = array(
            'submit_button_text' => 'Send Message',
            'success_message'    => 'Thank you for contacting us! We will get back to you shortly.',
            'redirect_url'       => '',
            'send_email'         => true,
            'notification_email' => get_option( 'admin_email' ),
        );

        $wpdb->insert(
            $table_forms,
            array(
                'title'       => 'Simple Contact Form',
                'fields_data' => wp_json_encode( $default_fields ),
                'settings'    => wp_json_encode( $default_settings ),
                'status'      => 'publish',
                'created_at'  => current_time( 'mysql' ),
                'updated_at'  => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s' )
        );
    }
}
