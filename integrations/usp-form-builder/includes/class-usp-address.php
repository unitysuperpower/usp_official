<?php
/** Linked Pro address field backed by bundled, local reference data. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
class USP_Address {
    public static function enqueue() {
        wp_enqueue_style( 'usp-address-css', USP_FORM_BUILDER_URL . 'assets/css/usp-address.css', array( 'usp-country-css' ), USP_FORM_BUILDER_VERSION );
        wp_enqueue_script( 'usp-address-js', USP_FORM_BUILDER_URL . 'assets/js/usp-address.js', array( 'usp-country-js' ), USP_FORM_BUILDER_VERSION, true );
        wp_localize_script( 'usp-address-js', 'uspAddressData', array(
            'data_url' => USP_FORM_BUILDER_URL . 'assets/locations/',
            'i18n' => array(
                'loading' => __( 'Loading regions…', 'usp-form-builder' ),
                'country_first' => __( 'Select a country first', 'usp-form-builder' ),
                'state_first' => __( 'Select a province first', 'usp-form-builder' ),
                'select_state' => __( 'Select a province, state, or region', 'usp-form-builder' ),
                'select_city' => __( 'Select a city or town', 'usp-form-builder' ),
                'other_state' => __( 'Region not listed? Enter manually', 'usp-form-builder' ),
                'other_city' => __( 'City not listed? Enter manually', 'usp-form-builder' ),
                'state_help' => __( 'Select a province or state to see its cities.', 'usp-form-builder' ),
                'city_help' => __( 'Choose a city, or enter it manually if it is not listed.', 'usp-form-builder' ),
                'city_manual' => __( 'No city list is available for this region. Enter your city or town.', 'usp-form-builder' ),
                'region_manual' => __( 'No region list is available. Enter your region and city manually.', 'usp-form-builder' ),
                'error' => __( 'Locations could not be loaded. Please retry.', 'usp-form-builder' ),
                'retry_hint' => __( 'Retry loading regions', 'usp-form-builder' ),
            ),
        ) );
    }
    public static function preview_template() {
        ob_start();
        self::render( '__USP_NAME__', '__USP_ID__', false );
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
        <div class="usp-address-control" data-show-street="<?php echo $show_street ? 'true' : 'false'; ?>" data-show-postal="<?php echo $show_postal ? 'true' : 'false'; ?>" data-required="<?php echo $required ? 'true' : 'false'; ?>">
            <div class="usp-address-grid">
                <?php if ( $show_street ) : ?>
                <div class="usp-address-part usp-address-wide" data-address-part="street">
                    <label for="<?php echo esc_attr( $id . '_street1' ); ?>"><?php esc_html_e( 'Street address', 'usp-form-builder' ); ?></label>
                    <input class="usp-input <?php echo $required ? 'usp-address-required' : ''; ?>" type="text" name="<?php echo esc_attr( $name . '[street1]' ); ?>" id="<?php echo esc_attr( $id . '_street1' ); ?>" autocomplete="address-line1" <?php echo $required ? 'required' : ''; ?>>
                </div>
                <div class="usp-address-part usp-address-wide" data-address-part="street">
                    <label for="<?php echo esc_attr( $id . '_street2' ); ?>"><?php esc_html_e( 'Apartment, suite, etc. (optional)', 'usp-form-builder' ); ?></label>
                    <input class="usp-input" type="text" name="<?php echo esc_attr( $name . '[street2]' ); ?>" id="<?php echo esc_attr( $id . '_street2' ); ?>" autocomplete="address-line2">
                </div>
                <?php endif; ?>
                <div class="usp-address-part">
                    <label for="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Country', 'usp-form-builder' ); ?></label>
                    <div class="usp-country-control">
                        <select class="usp-input usp-country-select usp-address-country <?php echo $required ? 'usp-address-required' : ''; ?>" name="<?php echo esc_attr( $name . '[country]' ); ?>" id="<?php echo esc_attr( $id ); ?>" autocomplete="country" <?php echo $required ? 'required' : ''; ?>>
                            <option value=""><?php esc_html_e( 'Select a country', 'usp-form-builder' ); ?></option>
                            <?php foreach ( self::countries() as $code => $country ) : ?>
                                <option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $country ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="usp-address-part">
                    <label for="<?php echo esc_attr( $id . '_state' ); ?>"><?php esc_html_e( 'Province / State / Region', 'usp-form-builder' ); ?></label>
                    <select class="usp-input usp-address-state" name="<?php echo esc_attr( $name . '[state]' ); ?>" id="<?php echo esc_attr( $id . '_state' ); ?>" autocomplete="address-level1" disabled>
                        <option value=""><?php esc_html_e( 'Select a country first', 'usp-form-builder' ); ?></option>
                    </select>
                    <input class="usp-input usp-address-state-other" type="text" name="<?php echo esc_attr( $name . '[state_other]' ); ?>" aria-label="<?php esc_attr_e( 'Province or region name', 'usp-form-builder' ); ?>" placeholder="<?php esc_attr_e( 'Enter province or region', 'usp-form-builder' ); ?>" hidden disabled>
                </div>
                <div class="usp-address-part">
                    <label for="<?php echo esc_attr( $id . '_city' ); ?>"><?php esc_html_e( 'City / Town', 'usp-form-builder' ); ?></label>
                    <select class="usp-input usp-address-city" name="<?php echo esc_attr( $name . '[city]' ); ?>" id="<?php echo esc_attr( $id . '_city' ); ?>" autocomplete="address-level2" disabled>
                        <option value=""><?php esc_html_e( 'Select a province first', 'usp-form-builder' ); ?></option>
                    </select>
                    <input class="usp-input usp-address-city-other" type="text" name="<?php echo esc_attr( $name . '[city_other]' ); ?>" aria-label="<?php esc_attr_e( 'City or town name', 'usp-form-builder' ); ?>" placeholder="<?php esc_attr_e( 'Enter city or town', 'usp-form-builder' ); ?>" hidden disabled>
                </div>
                <?php if ( $show_postal ) : ?>
                <div class="usp-address-part" data-address-part="postal">
                    <label for="<?php echo esc_attr( $id . '_postal' ); ?>"><?php esc_html_e( 'Postal / ZIP code (optional)', 'usp-form-builder' ); ?></label>
                    <input class="usp-input" type="text" name="<?php echo esc_attr( $name . '[postal]' ); ?>" id="<?php echo esc_attr( $id . '_postal' ); ?>" autocomplete="postal-code">
                </div>
                <?php endif; ?>
            </div>
            <p class="usp-address-status" role="status" aria-live="polite"></p>
            <button type="button" class="usp-address-retry" hidden><?php esc_html_e( 'Retry loading locations', 'usp-form-builder' ); ?></button>
            <small class="usp-address-attribution">Location data: <a href="https://github.com/dr5hn/countries-states-cities-database" target="_blank" rel="noopener noreferrer">Countries States Cities Database</a> · ODbL</small>
        </div>
        <?php
    }
    public static function validate( $raw, $required, $field = array() ) {
        if ( null === $raw ) { $raw = array(); }
        if ( ! is_array( $raw ) ) { return new WP_Error( 'address_invalid', __( 'Please enter a valid address.', 'usp-form-builder' ) ); }
        $clean = array();
        foreach ( array( 'street1', 'street2', 'country', 'state', 'state_other', 'city', 'city_other', 'postal' ) as $key ) {
            if ( isset( $raw[ $key ] ) && ! is_scalar( $raw[ $key ] ) ) { return new WP_Error( 'address_invalid', __( 'Please enter a valid address.', 'usp-form-builder' ) ); }
            $clean[ $key ] = isset( $raw[ $key ] ) ? sanitize_text_field( $raw[ $key ] ) : '';
        }
        $show_street = ! isset( $field['address_show_street'] ) || ! empty( $field['address_show_street'] );
        $show_postal = ! isset( $field['address_show_postal'] ) || ! empty( $field['address_show_postal'] );
        if ( ! $show_street ) { $clean['street1'] = $clean['street2'] = ''; }
        if ( ! $show_postal ) { $clean['postal'] = ''; }
        if ( ! implode( '', $clean ) && ! $required ) { return array(); }
        if ( ! $clean['country'] || ( $required && $show_street && ! $clean['street1'] ) ) {
            return new WP_Error( 'address_required', __( 'Please complete the country and required street address.', 'usp-form-builder' ) );
        }
        $data = self::country_data( $clean['country'] );
        if ( ! $data ) { return new WP_Error( 'address_country', __( 'Please select a valid country.', 'usp-form-builder' ) ); }
        $regions = array_column( $data['states'], null, 'id' );
        if ( '__other' === $clean['state'] ) {
            if ( ! $clean['state_other'] ) { return new WP_Error( 'address_region', __( 'Please enter the province or region name.', 'usp-form-builder' ) ); }
            $region_name = $clean['state_other'];
            $cities = array();
        } elseif ( isset( $regions[ $clean['state'] ] ) ) {
            $region_name = $regions[ $clean['state'] ]['name'];
            $cities = $regions[ $clean['state'] ]['cities'];
        } elseif ( ! $regions && '' === $clean['state'] ) {
            $region_name = '';
            $cities = array();
        } else { return new WP_Error( 'address_region', __( 'Select a province or region belonging to this country.', 'usp-form-builder' ) ); }
        if ( '__other' === $clean['city'] ) {
            $city_name = $clean['city_other'];
            if ( ! $city_name ) { return new WP_Error( 'address_city', __( 'Please enter the city or town name.', 'usp-form-builder' ) ); }
        } elseif ( in_array( $clean['city'], $cities, true ) ) { $city_name = $clean['city']; }
        else { return new WP_Error( 'address_city', __( 'Select a city in this province, or enter an unlisted city manually.', 'usp-form-builder' ) ); }
        return array( 'street1' => $clean['street1'], 'street2' => $clean['street2'], 'city' => $city_name, 'state' => $region_name, 'postal' => $clean['postal'], 'country' => $data['name'] );
    }
}
