<?php
/**
 * Database abstraction layer for TechSolution Form Studio.
 *
 * @package    TSFS_Plugin
 * @subpackage TSFS_Plugin/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TSFS_DB {

    /**
     * Get the forms table name.
     */
    public static function forms_table() {
        global $wpdb;
        return $wpdb->prefix . 'tsfs_forms';
    }

    /**
     * Get the entries table name.
     */
    public static function entries_table() {
        global $wpdb;
        return $wpdb->prefix . 'tsfs_entries';
    }

    /**
     * Create or update tables once per plugin version (also covers updates that skip activation).
     */
    public static function maybe_upgrade_schema() {
        if ( get_option( 'tsfs_db_version' ) === TSFS_VERSION ) {
            return;
        }
        TSFS_Activator::create_tables();
        self::migrate_legacy_data();
        update_option( 'tsfs_db_version', TSFS_VERSION );
    }

    /**
     * Copy forms, entries and security settings from the plugin's former name (USP Form Builder).
     * Runs only while the new tables are empty, so it never overwrites data.
     */
    public static function migrate_legacy_data() {
        global $wpdb;
        $legacy_forms   = $wpdb->prefix . 'usp_forms';
        $legacy_entries = $wpdb->prefix . 'usp_entries';
        $forms_table    = self::forms_table();
        $entries_table  = self::entries_table();

        if ( $legacy_forms !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $legacy_forms ) ) ) ) {
            return;
        }
        if ( (int) $wpdb->get_var( "SELECT COUNT(*) FROM $forms_table" ) > 0 ) {
            return;
        }

        $wpdb->query( "INSERT INTO $forms_table (id, title, fields_data, settings, views, status, created_at, updated_at) SELECT id, title, fields_data, settings, views, status, created_at, updated_at FROM $legacy_forms" );
        if ( $legacy_entries === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $legacy_entries ) ) ) ) {
            $wpdb->query( "INSERT INTO $entries_table (id, form_id, fields_data, user_ip, user_agent, status, notes, created_at) SELECT id, form_id, fields_data, user_ip, user_agent, status, notes, created_at FROM $legacy_entries" );
        }

        $legacy_settings = get_option( 'usp_security_settings' );
        if ( is_array( $legacy_settings ) && false === get_option( TSFS_Security::OPTION_KEY ) ) {
            TSFS_Security::save_settings( array_intersect_key( $legacy_settings, TSFS_Security::default_settings() ) );
        }
    }

    /**
     * Increment views count for a given form.
     */
    public static function increment_views( $form_id ) {
        global $wpdb;
        $forms_table = self::forms_table();
        $form_id     = absint( $form_id );
        if ( ! $form_id ) {
            return;
        }
        self::maybe_upgrade_schema();
        $wpdb->query( $wpdb->prepare( "UPDATE $forms_table SET views = views + 1 WHERE id = %d", $form_id ) );
    }

    /**
     * Get total impressions across all forms.
     */
    public static function get_total_views() {
        global $wpdb;
        self::maybe_upgrade_schema();
        $forms_table = self::forms_table();
        return (int) $wpdb->get_var( "SELECT SUM(views) FROM $forms_table" );
    }

    /**
     * Retrieve all forms, optionally including entry counts.
     */
    public static function get_forms( $args = array() ) {
        global $wpdb;

        self::maybe_upgrade_schema();

        $forms_table   = self::forms_table();
        $entries_table = self::entries_table();

        $sql = "SELECT f.*, COUNT(e.id) as entry_count 
                FROM $forms_table f 
                LEFT JOIN $entries_table e ON f.id = e.form_id 
                GROUP BY f.id 
                ORDER BY f.id DESC";

        return $wpdb->get_results( $sql, ARRAY_A );
    }

    /**
     * Retrieve a single form by its ID.
     */
    public static function get_form( $id ) {
        global $wpdb;

        $forms_table = self::forms_table();
        $id = absint( $id );

        $form = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $forms_table WHERE id = %d", $id ),
            ARRAY_A
        );

        if ( $form ) {
            $form['fields_data'] = ! empty( $form['fields_data'] ) ? json_decode( $form['fields_data'], true ) : array();
            $form['settings']    = ! empty( $form['settings'] ) ? json_decode( $form['settings'], true ) : array();
        }

        return $form;
    }

    /**
     * Insert or update a form.
     */
    public static function save_form( $data, $id = 0 ) {
        global $wpdb;

        $forms_table = self::forms_table();
        $id          = absint( $id );

        $title       = isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : 'Untitled Form';
        $fields_data = isset( $data['fields_data'] ) ? wp_json_encode( $data['fields_data'] ) : '[]';
        $settings    = isset( $data['settings'] ) ? wp_json_encode( $data['settings'] ) : '{}';
        $status      = isset( $data['status'] ) ? sanitize_text_field( $data['status'] ) : 'publish';

        $form_record = array(
            'title'       => $title,
            'fields_data' => $fields_data,
            'settings'    => $settings,
            'status'      => $status,
            'updated_at'  => current_time( 'mysql' ),
        );

        $formats = array( '%s', '%s', '%s', '%s', '%s' );

        if ( $id > 0 ) {
            $updated = $wpdb->update(
                $forms_table,
                $form_record,
                array( 'id' => $id ),
                $formats,
                array( '%d' )
            );
            return ( false !== $updated ) ? $id : false;
        } else {
            $form_record['created_at'] = current_time( 'mysql' );
            $formats[] = '%s';
            $inserted = $wpdb->insert( $forms_table, $form_record, $formats );
            return $inserted ? $wpdb->insert_id : false;
        }
    }

    /**
     * Delete a form and its entries.
     */
    public static function delete_form( $id ) {
        global $wpdb;

        $id = absint( $id );
        if ( ! $id ) {
            return false;
        }

        // Delete associated entries (and their uploaded files) first
        $entries_table = self::entries_table();
        foreach ( (array) $wpdb->get_col( $wpdb->prepare( "SELECT fields_data FROM $entries_table WHERE form_id = %d", $id ) ) as $json ) {
            TSFS_Security::delete_entry_files( json_decode( $json, true ) );
        }
        $wpdb->delete( $entries_table, array( 'form_id' => $id ), array( '%d' ) );

        // Delete the form
        return $wpdb->delete( self::forms_table(), array( 'id' => $id ), array( '%d' ) );
    }

    /**
     * Duplicate an existing form.
     */
    public static function duplicate_form( $id ) {
        $original = self::get_form( $id );
        if ( ! $original ) {
            return false;
        }

        $duplicate_data = array(
            'title'       => $original['title'] . ' (Copy)',
            'fields_data' => $original['fields_data'],
            'settings'    => $original['settings'],
            'status'      => $original['status'],
        );

        return self::save_form( $duplicate_data, 0 );
    }

    /**
     * Toggle form publish status (publish, draft, closed).
     */
    public static function toggle_form_status( $id, $status ) {
        global $wpdb;
        $id = absint( $id );
        $status = in_array( $status, array( 'publish', 'draft', 'closed' ), true ) ? $status : 'publish';
        return $wpdb->update(
            self::forms_table(),
            array( 'status' => $status ),
            array( 'id' => $id ),
            array( '%s' ),
            array( '%d' )
        );
    }

    /**
     * Retrieve entries / submissions with advanced multi-criteria filtering.
     * Supports: form_id, status (read/unread), date_from, date_to, search query.
     */
    public static function get_entries( $args = array(), $limit = 50, $offset = 0 ) {
        global $wpdb;

        if ( ! is_array( $args ) ) {
            $args = array(
                'form_id' => absint( $args ),
                'limit'   => $limit,
                'offset'  => $offset,
            );
        }

        $defaults = array(
            'form_id'   => 0,
            'status'    => '',
            'date_from' => '',
            'date_to'   => '',
            'search'    => '',
            'limit'     => 50,
            'offset'    => 0,
            'orderby'   => 'id',
            'order'     => 'DESC',
        );

        $params = wp_parse_args( $args, $defaults );

        $entries_table = self::entries_table();
        $forms_table   = self::forms_table();

        $where = array( '1=1' );
        $query_params = array();

        if ( ! empty( $params['form_id'] ) ) {
            $where[] = 'e.form_id = %d';
            $query_params[] = absint( $params['form_id'] );
        }

        if ( ! empty( $params['status'] ) && in_array( $params['status'], array( 'unread', 'read', 'starred' ), true ) ) {
            $where[] = 'e.status = %s';
            $query_params[] = $params['status'];
        }

        if ( ! empty( $params['date_from'] ) ) {
            $where[] = 'e.created_at >= %s';
            $query_params[] = sanitize_text_field( $params['date_from'] ) . ' 00:00:00';
        }

        if ( ! empty( $params['date_to'] ) ) {
            $where[] = 'e.created_at <= %s';
            $query_params[] = sanitize_text_field( $params['date_to'] ) . ' 23:59:59';
        }

        if ( ! empty( $params['search'] ) ) {
            $where[] = '(e.fields_data LIKE %s OR e.user_ip LIKE %s)';
            $like = '%' . $wpdb->esc_like( sanitize_text_field( $params['search'] ) ) . '%';
            $query_params[] = $like;
            $query_params[] = $like;
        }

        $where_sql = implode( ' AND ', $where );
        $order = ( 'ASC' === strtoupper( $params['order'] ) ) ? 'ASC' : 'DESC';
        $orderby = ( 'created_at' === $params['orderby'] ) ? 'e.created_at' : 'e.id';

        $limit_clause = '';
        if ( $params['limit'] > 0 ) {
            $limit_clause = $wpdb->prepare( ' LIMIT %d OFFSET %d', absint( $params['limit'] ), absint( $params['offset'] ) );
        }

        $sql = "SELECT e.*, f.title as form_title 
                FROM $entries_table e 
                LEFT JOIN $forms_table f ON e.form_id = f.id 
                WHERE $where_sql 
                ORDER BY $orderby $order $limit_clause";

        if ( ! empty( $query_params ) ) {
            $sql = $wpdb->prepare( $sql, $query_params );
        }

        $rows = $wpdb->get_results( $sql, ARRAY_A );

        foreach ( $rows as &$row ) {
            $row['fields_data'] = ! empty( $row['fields_data'] ) ? json_decode( $row['fields_data'], true ) : array();
        }

        return $rows;
    }

    /**
     * Get count of entries matching specific filter arguments.
     */
    public static function get_entries_count( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'form_id'   => 0,
            'status'    => '',
            'date_from' => '',
            'date_to'   => '',
            'search'    => '',
        );

        $params = wp_parse_args( $args, $defaults );

        $entries_table = self::entries_table();
        $where = array( '1=1' );
        $query_params = array();

        if ( ! empty( $params['form_id'] ) ) {
            $where[] = 'form_id = %d';
            $query_params[] = absint( $params['form_id'] );
        }

        if ( ! empty( $params['status'] ) && in_array( $params['status'], array( 'unread', 'read', 'starred' ), true ) ) {
            $where[] = 'status = %s';
            $query_params[] = $params['status'];
        }

        if ( ! empty( $params['date_from'] ) ) {
            $where[] = 'created_at >= %s';
            $query_params[] = sanitize_text_field( $params['date_from'] ) . ' 00:00:00';
        }

        if ( ! empty( $params['date_to'] ) ) {
            $where[] = 'created_at <= %s';
            $query_params[] = sanitize_text_field( $params['date_to'] ) . ' 23:59:59';
        }

        if ( ! empty( $params['search'] ) ) {
            $where[] = '(fields_data LIKE %s OR user_ip LIKE %s)';
            $like = '%' . $wpdb->esc_like( sanitize_text_field( $params['search'] ) ) . '%';
            $query_params[] = $like;
            $query_params[] = $like;
        }

        $where_sql = implode( ' AND ', $where );
        $sql = "SELECT COUNT(*) FROM $entries_table WHERE $where_sql";

        if ( ! empty( $query_params ) ) {
            $sql = $wpdb->prepare( $sql, $query_params );
        }

        return (int) $wpdb->get_var( $sql );
    }

    /**
     * Get breakdown of entry status counts (All, Unread, Read).
     */
    public static function get_status_counts( $form_id = 0 ) {
        global $wpdb;

        self::maybe_upgrade_schema();

        $entries_table = self::entries_table();
        $form_id       = absint( $form_id );

        $where = '';
        $params = array();
        if ( $form_id > 0 ) {
            $where = 'WHERE form_id = %d';
            $params[] = $form_id;
        }

        $today_start = current_time( 'Y-m-d' ) . ' 00:00:00';

        $sql_all     = "SELECT COUNT(*) FROM $entries_table $where";
        $sql_unread  = "SELECT COUNT(*) FROM $entries_table " . ( $where ? "$where AND status = 'unread'" : "WHERE status = 'unread'" );
        $sql_read    = "SELECT COUNT(*) FROM $entries_table " . ( $where ? "$where AND status = 'read'" : "WHERE status = 'read'" );
        $sql_starred = "SELECT COUNT(*) FROM $entries_table " . ( $where ? "$where AND status = 'starred'" : "WHERE status = 'starred'" );
        $sql_today   = "SELECT COUNT(*) FROM $entries_table " . ( $where ? "$where AND created_at >= %s" : "WHERE created_at >= %s" );

        if ( $form_id > 0 ) {
            $sql_all     = $wpdb->prepare( $sql_all, $params );
            $sql_unread  = $wpdb->prepare( $sql_unread, $params );
            $sql_read    = $wpdb->prepare( $sql_read, $params );
            $sql_starred = $wpdb->prepare( $sql_starred, $params );
            $sql_today   = $wpdb->prepare( $sql_today, array( $form_id, $today_start ) );
        } else {
            $sql_today   = $wpdb->prepare( $sql_today, array( $today_start ) );
        }

        return array(
            'all'     => (int) $wpdb->get_var( $sql_all ),
            'unread'  => (int) $wpdb->get_var( $sql_unread ),
            'read'    => (int) $wpdb->get_var( $sql_read ),
            'starred' => (int) $wpdb->get_var( $sql_starred ),
            'today'   => (int) $wpdb->get_var( $sql_today ),
        );
    }

    /**
     * Update admin internal notes for an entry.
     */
    public static function update_entry_notes( $id, $notes ) {
        global $wpdb;

        self::maybe_upgrade_schema();

        $id = absint( $id );
        return $wpdb->update(
            self::entries_table(),
            array( 'notes' => wp_kses_post( $notes ) ),
            array( 'id' => $id ),
            array( '%s' ),
            array( '%d' )
        );
    }

    /**
     * Update entry status (read / unread / starred).
     */
    public static function update_entry_status( $id, $status ) {
        global $wpdb;

        $id = absint( $id );
        $status = in_array( $status, array( 'unread', 'read', 'starred' ), true ) ? $status : 'read';

        return $wpdb->update(
            self::entries_table(),
            array( 'status' => $status ),
            array( 'id' => $id ),
            array( '%s' ),
            array( '%d' )
        );
    }

    /**
     * Bulk update statuses for multiple entries.
     */
    public static function bulk_update_status( $ids, $status ) {
        global $wpdb;

        $sanitized_ids = array_map( 'absint', (array) $ids );
        $sanitized_ids = array_filter( $sanitized_ids );
        if ( empty( $sanitized_ids ) ) {
            return false;
        }

        $status = in_array( $status, array( 'unread', 'read', 'starred' ), true ) ? $status : 'read';
        $entries_table = self::entries_table();
        $placeholders  = implode( ',', array_fill( 0, count( $sanitized_ids ), '%d' ) );

        $sql = $wpdb->prepare(
            "UPDATE $entries_table SET status = %s WHERE id IN ($placeholders)",
            array_merge( array( $status ), $sanitized_ids )
        );

        return $wpdb->query( $sql );
    }

    /**
     * Bulk delete multiple entries.
     */
    public static function bulk_delete_entries( $ids ) {
        global $wpdb;

        $sanitized_ids = array_map( 'absint', (array) $ids );
        $sanitized_ids = array_filter( $sanitized_ids );
        if ( empty( $sanitized_ids ) ) {
            return false;
        }

        $entries_table = self::entries_table();
        $placeholders  = implode( ',', array_fill( 0, count( $sanitized_ids ), '%d' ) );

        foreach ( (array) $wpdb->get_col( $wpdb->prepare( "SELECT fields_data FROM $entries_table WHERE id IN ($placeholders)", $sanitized_ids ) ) as $json ) {
            TSFS_Security::delete_entry_files( json_decode( $json, true ) );
        }

        $sql = $wpdb->prepare(
            "DELETE FROM $entries_table WHERE id IN ($placeholders)",
            $sanitized_ids
        );

        return $wpdb->query( $sql );
    }

    /**
     * Get a single entry by ID.
     */
    public static function get_entry( $id ) {
        global $wpdb;

        $entries_table = self::entries_table();
        $forms_table   = self::forms_table();
        $id            = absint( $id );

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT e.*, f.title as form_title 
                 FROM $entries_table e 
                 LEFT JOIN $forms_table f ON e.form_id = f.id 
                 WHERE e.id = %d",
                $id
            ),
            ARRAY_A
        );

        if ( $row ) {
            $row['fields_data'] = ! empty( $row['fields_data'] ) ? json_decode( $row['fields_data'], true ) : array();
        }

        return $row;
    }

    /**
     * Insert a new entry submission.
     */
    public static function save_entry( $form_id, $fields_data, $user_ip = '', $user_agent = '' ) {
        global $wpdb;

        $entries_table = self::entries_table();

        $inserted = $wpdb->insert(
            $entries_table,
            array(
                'form_id'     => absint( $form_id ),
                'fields_data' => wp_json_encode( $fields_data ),
                'user_ip'     => sanitize_text_field( $user_ip ),
                'user_agent'  => sanitize_text_field( substr( $user_agent, 0, 500 ) ),
                'status'      => 'unread',
                'created_at'  => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s' )
        );

        return $inserted ? $wpdb->insert_id : false;
    }

    /**
     * Delete an entry submission.
     */
    public static function delete_entry( $id ) {
        global $wpdb;

        $id    = absint( $id );
        $entry = self::get_entry( $id );
        if ( $entry ) {
            TSFS_Security::delete_entry_files( $entry['fields_data'] );
        }
        return $wpdb->delete( self::entries_table(), array( 'id' => $id ), array( '%d' ) );
    }

    /**
     * Find entries where any submitted value equals the given email address (privacy exports and erasure).
     */
    public static function get_entries_by_email( $email, $limit = 100, $offset = 0 ) {
        global $wpdb;
        $email = sanitize_email( $email );
        if ( ! is_email( $email ) ) {
            return array();
        }
        $entries_table = self::entries_table();
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $entries_table WHERE fields_data LIKE %s ORDER BY id ASC LIMIT %d OFFSET %d",
                '%' . $wpdb->esc_like( $email ) . '%',
                absint( $limit ),
                absint( $offset )
            ),
            ARRAY_A
        );
        $matches = array();
        foreach ( $rows as $row ) {
            $row['fields_data'] = json_decode( $row['fields_data'], true );
            foreach ( (array) $row['fields_data'] as $field ) {
                if ( isset( $field['value'] ) && is_string( $field['value'] ) && 0 === strcasecmp( trim( $field['value'] ), $email ) ) {
                    $matches[] = $row;
                    break;
                }
            }
        }
        return $matches;
    }

    /**
     * Delete entries older than the given number of days. Returns the number of deleted rows.
     */
    public static function delete_entries_older_than( $days ) {
        global $wpdb;
        $days = absint( $days );
        if ( ! $days ) {
            return 0;
        }
        $cutoff = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - $days * DAY_IN_SECONDS );
        $entries_table = self::entries_table();
        foreach ( (array) $wpdb->get_col( $wpdb->prepare( "SELECT fields_data FROM $entries_table WHERE created_at < %s", $cutoff ) ) as $json ) {
            TSFS_Security::delete_entry_files( json_decode( $json, true ) );
        }
        return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM $entries_table WHERE created_at < %s", $cutoff ) );
    }

    /**
     * Count total entries (all or per form).
     */
    public static function get_entry_count( $form_id = 0 ) {
        global $wpdb;

        $entries_table = self::entries_table();
        $form_id       = absint( $form_id );

        if ( $form_id > 0 ) {
            return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $entries_table WHERE form_id = %d", $form_id ) );
        }

        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $entries_table" );
    }
}
