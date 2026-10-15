<?php
/** Complete address field backed by bundled, local reference data. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
class TSFS_Pro_Address {
    public static function enqueue() {
        wp_enqueue_style( 'tsfs-pro-address-css', TSFS_PRO_PLUGIN_URL . 'assets/css/tsfs-pro-address.css', array( 'tsfs-country-css' ), TSFS_PRO_VERSION );
        wp_enqueue_script( 'tsfs-pro-address-js', TSFS_PRO_PLUGIN_URL . 'assets/js/tsfs-pro-address.js', array( 'tsfs-country-js' ), TSFS_PRO_VERSION, true );
        wp_localize_script( 'tsfs-pro-address-js', 'tsfsAddressData', array(
            'data_url' => TSFS_PRO_PLUGIN_URL . 'assets/locations/',
            'i18n' => array(
                'loading' => __( 'Loading regions…', 'techsolution-form-studio-pro' ),
                'country_first' => __( 'Select a country first', 'techsolution-form-studio-pro' ),
                'state_first' => __( 'Select a province first', 'techsolution-form-studio-pro' ),
                'select_state' => __( 'Select a province, state, or region', 'techsolution-form-studio-pro' ),
                'select_city' => __( 'Select a city or town', 'techsolution-form-studio-pro' ),
                'other_state' => __( 'Region not listed? Enter manually', 'techsolution-form-studio-pro' ),
                'other_city' => __( 'City not listed? Enter manually', 'techsolution-form-studio-pro' ),
                'state_help' => __( 'Select a province or state to see its cities.', 'techsolution-form-studio-pro' ),
                'city_help' => __( 'Choose a city, or enter it manually if it is not listed.', 'techsolution-form-studio-pro' ),
                'city_manual' => __( 'No city list is available for this region. Enter your city or town.', 'techsolution-form-studio-pro' ),
                'region_manual' => __( 'No region list is available. Enter your region and city manually.', 'techsolution-form-studio-pro' ),
                'error' => __( 'Locations could not be loaded. Please retry.', 'techsolution-form-studio-pro' ),
                'retry_hint' => __( 'Retry loading regions', 'techsolution-form-studio-pro' ),
            ),
        ) );
    }
    public static function preview_template() {
        ob_start();
        self::render( '__TSFS_NAME__', '__TSFS_ID__', false );
        return ob_get_clean();
    }

    public static function countries() {
        return json_decode( file_get_contents( __DIR__ . '/../assets/locations/countries.json' ), true );
    }
    public static function country_data( $code ) {
        if ( ! is_string( $code ) || ! preg_match( '/^[A-Z]{2}$/', $code ) ) { return null; }
        $file = __DIR__ . '/../assets/locations/' . $code . '.json';
        return is_file( $file ) ? json_decode( file_get_contents( $file ), true ) : null;
    }
    public static function render( $name, $id, $required, $field = array() ) {
        $show_street = ! isset( $field['address_show_street'] ) || ! empty( $field['address_show_street'] );
        $show_postal = ! isset( $field['address_show_postal'] ) || ! empty( $field['address_show_postal'] );
        ?>
        <div class="tsfs-address-control" data-show-street="<?php echo $show_street ? 'true' : 'false'; ?>" data-show-postal="<?php echo $show_postal ? 'true' : 'false'; ?>" data-required="<?php echo $required ? 'true' : 'false'; ?>">
            <div class="tsfs-address-grid">
                <?php if ( $show_street ) : ?>
                <div class="tsfs-address-part tsfs-address-wide" data-address-part="street">
                    <label for="<?php echo esc_attr( $id . '_street1' ); ?>"><?php esc_html_e( 'Street address', 'techsolution-form-studio-pro' ); ?></label>
                    <input class="tsfs-input <?php echo $required ? 'tsfs-address-required' : ''; ?>" type="text" name="<?php echo esc_attr( $name . '[street1]' ); ?>" id="<?php echo esc_attr( $id . '_street1' ); ?>" autocomplete="address-line1" <?php echo $required ? 'required' : ''; ?>>
                </div>
                <div class="tsfs-address-part tsfs-address-wide" data-address-part="street">
                    <label for="<?php echo esc_attr( $id . '_street2' ); ?>"><?php esc_html_e( 'Apartment, suite, etc. (optional)', 'techsolution-form-studio-pro' ); ?></label>
                    <input class="tsfs-input" type="text" name="<?php echo esc_attr( $name . '[street2]' ); ?>" id="<?php echo esc_attr( $id . '_street2' ); ?>" autocomplete="address-line2">
                </div>
                <?php endif; ?>
                <div class="tsfs-address-part">
                    <label for="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Country', 'techsolution-form-studio-pro' ); ?></label>
                    <div class="tsfs-country-control">
                        <select class="tsfs-input tsfs-country-select tsfs-address-country <?php echo $required ? 'tsfs-address-required' : ''; ?>" name="<?php echo esc_attr( $name . '[country]' ); ?>" id="<?php echo esc_attr( $id ); ?>" autocomplete="country" <?php echo $required ? 'required' : ''; ?>>
                            <option value=""><?php esc_html_e( 'Select a country', 'techsolution-form-studio-pro' ); ?></option>
                            <?php foreach ( self::countries() as $code => $country ) : ?>
                                <option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $country ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="tsfs-address-part">
                    <label for="<?php echo esc_attr( $id . '_state' ); ?>"><?php esc_html_e( 'Province / State / Region', 'techsolution-form-studio-pro' ); ?></label>
                    <select class="tsfs-input tsfs-address-state" name="<?php echo esc_attr( $name . '[state]' ); ?>" id="<?php echo esc_attr( $id . '_state' ); ?>" autocomplete="address-level1" disabled>
                        <option value=""><?php esc_html_e( 'Select a country first', 'techsolution-form-studio-pro' ); ?></option>
                    </select>
                    <input class="tsfs-input tsfs-address-state-other" type="text" name="<?php echo esc_attr( $name . '[state_other]' ); ?>" aria-label="<?php esc_attr_e( 'Province or region name', 'techsolution-form-studio-pro' ); ?>" placeholder="<?php esc_attr_e( 'Enter province or region', 'techsolution-form-studio-pro' ); ?>" hidden disabled>
                </div>
                <div class="tsfs-address-part">
                    <label for="<?php echo esc_attr( $id . '_city' ); ?>"><?php esc_html_e( 'City / Town', 'techsolution-form-studio-pro' ); ?></label>
                    <select class="tsfs-input tsfs-address-city" name="<?php echo esc_attr( $name . '[city]' ); ?>" id="<?php echo esc_attr( $id . '_city' ); ?>" autocomplete="address-level2" disabled>
                        <option value=""><?php esc_html_e( 'Select a province first', 'techsolution-form-studio-pro' ); ?></option>
                    </select>
                    <input class="tsfs-input tsfs-address-city-other" type="text" name="<?php echo esc_attr( $name . '[city_other]' ); ?>" aria-label="<?php esc_attr_e( 'City or town name', 'techsolution-form-studio-pro' ); ?>" placeholder="<?php esc_attr_e( 'Enter city or town', 'techsolution-form-studio-pro' ); ?>" hidden disabled>
                </div>
                <?php if ( $show_postal ) : ?>
                <div class="tsfs-address-part" data-address-part="postal">
                    <label for="<?php echo esc_attr( $id . '_postal' ); ?>"><?php esc_html_e( 'Postal / ZIP code (optional)', 'techsolution-form-studio-pro' ); ?></label>
                    <input class="tsfs-input" type="text" name="<?php echo esc_attr( $name . '[postal]' ); ?>" id="<?php echo esc_attr( $id . '_postal' ); ?>" autocomplete="postal-code">
                </div>
                <?php endif; ?>
            </div>
            <p class="tsfs-address-status" role="status" aria-live="polite"></p>
            <button type="button" class="tsfs-address-retry" hidden><?php esc_html_e( 'Retry loading locations', 'techsolution-form-studio-pro' ); ?></button>
            <small class="tsfs-address-attribution">Location data: <a href="https://github.com/dr5hn/countries-states-cities-database" target="_blank" rel="noopener noreferrer">Countries States Cities Database</a> · ODbL</small>
        </div>
        <?php
    }
    public static function validate( $raw, $required, $field = array() ) {
        if ( null === $raw ) { $raw = array(); }
        if ( ! is_array( $raw ) ) { return new WP_Error( 'address_invalid', __( 'Please enter a valid address.', 'techsolution-form-studio-pro' ) ); }
        $clean = array();
        foreach ( array( 'street1', 'street2', 'country', 'state', 'state_other', 'city', 'city_other', 'postal' ) as $key ) {
            if ( isset( $raw[ $key ] ) && ! is_scalar( $raw[ $key ] ) ) { return new WP_Error( 'address_invalid', __( 'Please enter a valid address.', 'techsolution-form-studio-pro' ) ); }
            $clean[ $key ] = isset( $raw[ $key ] ) ? sanitize_text_field( $raw[ $key ] ) : '';
        }
        $show_street = ! isset( $field['address_show_street'] ) || ! empty( $field['address_show_street'] );
        $show_postal = ! isset( $field['address_show_postal'] ) || ! empty( $field['address_show_postal'] );
        if ( ! $show_street ) { $clean['street1'] = $clean['street2'] = ''; }
        if ( ! $show_postal ) { $clean['postal'] = ''; }
        if ( ! implode( '', $clean ) && ! $required ) { return array(); }
        if ( ! $clean['country'] || ( $required && $show_street && ! $clean['street1'] ) ) {
            return new WP_Error( 'address_required', __( 'Please complete the country and required street address.', 'techsolution-form-studio-pro' ) );
        }
        $data = self::country_data( $clean['country'] );
        if ( ! $data ) { return new WP_Error( 'address_country', __( 'Please select a valid country.', 'techsolution-form-studio-pro' ) ); }
        $regions = array_column( $data['states'], null, 'id' );
        if ( '__other' === $clean['state'] ) {
            if ( ! $clean['state_other'] ) { return new WP_Error( 'address_region', __( 'Please enter the province or region name.', 'techsolution-form-studio-pro' ) ); }
            $region_name = $clean['state_other'];
            $cities = array();
        } elseif ( isset( $regions[ $clean['state'] ] ) ) {
            $region_name = $regions[ $clean['state'] ]['name'];
            $cities = $regions[ $clean['state'] ]['cities'];
        } elseif ( ! $regions && '' === $clean['state'] ) {
            $region_name = '';
            $cities = array();
        } else { return new WP_Error( 'address_region', __( 'Select a province or region belonging to this country.', 'techsolution-form-studio-pro' ) ); }
        if ( '__other' === $clean['city'] ) {
            $city_name = $clean['city_other'];
            if ( ! $city_name ) { return new WP_Error( 'address_city', __( 'Please enter the city or town name.', 'techsolution-form-studio-pro' ) ); }
        } elseif ( in_array( $clean['city'], $cities, true ) ) { $city_name = $clean['city']; }
        else { return new WP_Error( 'address_city', __( 'Select a city in this province, or enter an unlisted city manually.', 'techsolution-form-studio-pro' ) ); }
        return array( 'street1' => $clean['street1'], 'street2' => $clean['street2'], 'city' => $city_name, 'state' => $region_name, 'postal' => $clean['postal'], 'country' => $data['name'] );
    }
}
