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

// This class reads and writes the plugin's own custom tables, so direct queries are required.
// Every query is prepared with %i for table names; results that change on every submission are not cached.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

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
        if ( (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $forms_table ) ) > 0 ) {
            return;
        }

        $wpdb->query( $wpdb->prepare( 'INSERT INTO %i (id, title, fields_data, settings, views, status, created_at, updated_at) SELECT id, title, fields_data, settings, views, status, created_at, updated_at FROM %i', $forms_table, $legacy_forms ) );
        if ( $legacy_entries === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $legacy_entries ) ) ) ) {
            $wpdb->query( $wpdb->prepare( 'INSERT INTO %i (id, form_id, fields_data, user_ip, user_agent, status, notes, created_at) SELECT id, form_id, fields_data, user_ip, user_agent, status, notes, created_at FROM %i', $entries_table, $legacy_entries ) );
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
        $wpdb->query( $wpdb->prepare( 'UPDATE %i SET views = views + 1 WHERE id = %d', $forms_table, $form_id ) );
    }

    /**
     * Get total impressions across all forms.
     */
    public static function get_total_views() {
        global $wpdb;
        self::maybe_upgrade_schema();
        $forms_table = self::forms_table();
        return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT SUM(views) FROM %i', $forms_table ) );
    }

    /**
     * Retrieve all forms, optionally including entry counts.
     */
    public static function get_forms( $args = array() ) {
        global $wpdb;

        self::maybe_upgrade_schema();

        $forms_table   = self::forms_table();
        $entries_table = self::entries_table();

        return $wpdb->get_results(
            $wpdb->prepare( 'SELECT f.*, COUNT(e.id) AS entry_count FROM %i f LEFT JOIN %i e ON f.id = e.form_id GROUP BY f.id ORDER BY f.id DESC', $forms_table, $entries_table ),
            ARRAY_A
        );
    }

    /**
     * Retrieve a single form by its ID.
     */
    public static function get_form( $id ) {
        global $wpdb;

        $forms_table = self::forms_table();
        $id = absint( $id );

        $form = $wpdb->get_row(
            $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $forms_table, $id ),
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
        foreach ( (array) $wpdb->get_col( $wpdb->prepare( 'SELECT fields_data FROM %i WHERE form_id = %d', $entries_table, $id ) ) as $json ) {
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

        $f      = self::filter_values( $params );
        $limit  = $params['limit'] > 0 ? absint( $params['limit'] ) : PHP_INT_MAX;
        $offset = absint( $params['offset'] );
        $by_date = 'created_at' === $params['orderby'];
        $asc     = 'ASC' === strtoupper( (string) $params['order'] );
        $entries = self::entries_table();
        $forms   = self::forms_table();

        // Each sort order is its own literal query so no SQL is assembled from variables.
        if ( $by_date && $asc ) {
            $rows = $wpdb->get_results( $wpdb->prepare( "SELECT e.*, f.title AS form_title FROM %i e LEFT JOIN %i f ON e.form_id = f.id WHERE (%d = 0 OR e.form_id = %d) AND (%s = '' OR e.status = %s) AND e.created_at >= %s AND e.created_at <= %s AND (%s = '' OR e.fields_data LIKE %s OR e.user_ip LIKE %s) ORDER BY e.created_at ASC LIMIT %d OFFSET %d", $entries, $forms, $f['form_id'], $f['form_id'], $f['status'], $f['status'], $f['from'], $f['to'], $f['like'], $f['like'], $f['like'], $limit, $offset ), ARRAY_A );
        } elseif ( $by_date ) {
            $rows = $wpdb->get_results( $wpdb->prepare( "SELECT e.*, f.title AS form_title FROM %i e LEFT JOIN %i f ON e.form_id = f.id WHERE (%d = 0 OR e.form_id = %d) AND (%s = '' OR e.status = %s) AND e.created_at >= %s AND e.created_at <= %s AND (%s = '' OR e.fields_data LIKE %s OR e.user_ip LIKE %s) ORDER BY e.created_at DESC LIMIT %d OFFSET %d", $entries, $forms, $f['form_id'], $f['form_id'], $f['status'], $f['status'], $f['from'], $f['to'], $f['like'], $f['like'], $f['like'], $limit, $offset ), ARRAY_A );
        } elseif ( $asc ) {
            $rows = $wpdb->get_results( $wpdb->prepare( "SELECT e.*, f.title AS form_title FROM %i e LEFT JOIN %i f ON e.form_id = f.id WHERE (%d = 0 OR e.form_id = %d) AND (%s = '' OR e.status = %s) AND e.created_at >= %s AND e.created_at <= %s AND (%s = '' OR e.fields_data LIKE %s OR e.user_ip LIKE %s) ORDER BY e.id ASC LIMIT %d OFFSET %d", $entries, $forms, $f['form_id'], $f['form_id'], $f['status'], $f['status'], $f['from'], $f['to'], $f['like'], $f['like'], $f['like'], $limit, $offset ), ARRAY_A );
        } else {
            $rows = $wpdb->get_results( $wpdb->prepare( "SELECT e.*, f.title AS form_title FROM %i e LEFT JOIN %i f ON e.form_id = f.id WHERE (%d = 0 OR e.form_id = %d) AND (%s = '' OR e.status = %s) AND e.created_at >= %s AND e.created_at <= %s AND (%s = '' OR e.fields_data LIKE %s OR e.user_ip LIKE %s) ORDER BY e.id DESC LIMIT %d OFFSET %d", $entries, $forms, $f['form_id'], $f['form_id'], $f['status'], $f['status'], $f['from'], $f['to'], $f['like'], $f['like'], $f['like'], $limit, $offset ), ARRAY_A );
        }

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

        $f = self::filter_values( $params );

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM %i WHERE (%d = 0 OR form_id = %d) AND (%s = '' OR status = %s) AND created_at >= %s AND created_at <= %s AND (%s = '' OR fields_data LIKE %s OR user_ip LIKE %s)",
                self::entries_table(), $f['form_id'], $f['form_id'], $f['status'], $f['status'], $f['from'], $f['to'], $f['like'], $f['like'], $f['like']
            )
        );
    }

    /**
     * Normalised filter values for the entry queries. Empty values disable that filter.
     */
    private static function filter_values( $params ) {
        global $wpdb;
        $search = ! empty( $params['search'] ) ? sanitize_text_field( $params['search'] ) : '';

        return array(
            'form_id' => absint( $params['form_id'] ),
            'status'  => in_array( $params['status'], array( 'unread', 'read', 'starred' ), true ) ? $params['status'] : '',
            // Without a date filter the range covers every possible DATETIME.
            'from'    => ! empty( $params['date_from'] ) ? sanitize_text_field( $params['date_from'] ) . ' 00:00:00' : '1000-01-01 00:00:00',
            'to'      => ! empty( $params['date_to'] ) ? sanitize_text_field( $params['date_to'] ) . ' 23:59:59' : '9999-12-31 23:59:59',
            'like'    => '' !== $search ? '%' . $wpdb->esc_like( $search ) . '%' : '',
        );
    }

    /**
     * Get breakdown of entry status counts (All, Unread, Read).
     */
    public static function get_status_counts( $form_id = 0 ) {
        global $wpdb;

        self::maybe_upgrade_schema();

        $form_id = absint( $form_id );
        $row     = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT COUNT(*) AS all_count, SUM(status = 'unread') AS unread_count, SUM(status = 'read') AS read_count, SUM(status = 'starred') AS starred_count, SUM(created_at >= %s) AS today_count FROM %i WHERE (%d = 0 OR form_id = %d)",
                current_time( 'Y-m-d' ) . ' 00:00:00',
                self::entries_table(),
                $form_id,
                $form_id
            ),
            ARRAY_A
        );

        return array(
            'all'     => (int) ( $row['all_count'] ?? 0 ),
            'unread'  => (int) ( $row['unread_count'] ?? 0 ),
            'read'    => (int) ( $row['read_count'] ?? 0 ),
            'starred' => (int) ( $row['starred_count'] ?? 0 ),
            'today'   => (int) ( $row['today_count'] ?? 0 ),
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

        $status  = in_array( $status, array( 'unread', 'read', 'starred' ), true ) ? $status : 'read';
        $updated = 0;
        foreach ( $sanitized_ids as $id ) {
            $updated += (int) $wpdb->update( self::entries_table(), array( 'status' => $status ), array( 'id' => $id ), array( '%s' ), array( '%d' ) );
        }

        return $updated;
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

        $deleted = 0;
        foreach ( $sanitized_ids as $id ) {
            $deleted += (int) self::delete_entry( $id );
        }

        return $deleted;
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
            $wpdb->prepare( 'SELECT e.*, f.title AS form_title FROM %i e LEFT JOIN %i f ON e.form_id = f.id WHERE e.id = %d', $entries_table, $forms_table, $id ),
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
                'SELECT * FROM %i WHERE fields_data LIKE %s ORDER BY id ASC LIMIT %d OFFSET %d',
                $entries_table,
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
        foreach ( (array) $wpdb->get_col( $wpdb->prepare( 'SELECT fields_data FROM %i WHERE created_at < %s', $entries_table, $cutoff ) ) as $json ) {
            TSFS_Security::delete_entry_files( json_decode( $json, true ) );
        }
        return (int) $wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE created_at < %s', $entries_table, $cutoff ) );
    }

    /**
     * Count total entries (all or per form).
     */
    public static function get_entry_count( $form_id = 0 ) {
        global $wpdb;

        $entries_table = self::entries_table();
        $form_id       = absint( $form_id );

        if ( $form_id > 0 ) {
            return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE form_id = %d', $entries_table, $form_id ) );
        }

        return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $entries_table ) );
    }
}
