<?php
/**
 * Pro field types for TechSolution Form Studio, registered through the core plugin's extension hooks:
 * address, admission, intl_tel, payment, signature, calculation and rich_text (formatted content block).
 *
 * @package TechSolution_Form_Studio_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TSFS_Pro_Fields {

    const TYPES = array( 'address', 'admission', 'intl_tel', 'payment', 'signature', 'calculation', 'rich_text' );

    public static function init() {
        add_filter( 'tsfs_field_types', array( __CLASS__, 'register_types' ) );
        add_filter( 'tsfs_sanitize_field_config', array( __CLASS__, 'sanitize_config' ), 10, 3 );
        foreach ( self::TYPES as $type ) {
            add_action( 'tsfs_render_field_' . $type, array( __CLASS__, 'render_' . $type ), 10, 5 );
            add_filter( 'tsfs_process_field_' . $type, array( __CLASS__, 'process_' . $type ), 10, 3 );
        }
        add_action( 'tsfs_before_render_form', array( __CLASS__, 'enqueue_public_assets' ) );
        add_action( 'tsfs_enqueue_builder_assets', array( __CLASS__, 'enqueue_builder_assets' ) );
        add_action( 'tsfs_builder_palette', array( __CLASS__, 'render_palette' ) );
        add_action( 'tsfs_builder_templates', array( __CLASS__, 'render_templates' ) );
    }

    /* ------------------------------------------------------------------ */
    /* Registration                                                        */
    /* ------------------------------------------------------------------ */

    public static function default_programs() {
        return array( 'BS Computer Science', 'BS Software Engineering', 'BBA / Business Administration', 'BS Data Science & AI', 'MS / MPhil Program' );
    }

    public static function register_types( $types ) {
        $types['address'] = array(
            'label'    => __( 'Complete Address', 'techsolution-form-studio-pro' ),
            'icon'     => 'dashicons-location-alt',
            'category' => 'pro',
            'defaults' => array( 'label' => __( 'Address', 'techsolution-form-studio-pro' ), 'required' => false, 'width' => '100', 'address_show_street' => true, 'address_show_postal' => true ),
        );
        $types['admission'] = array(
            'label'    => __( 'Admission Application', 'techsolution-form-studio-pro' ),
            'icon'     => 'dashicons-welcome-learn-more',
            'category' => 'pro',
            'defaults' => array( 'label' => __( 'Student Admission Application', 'techsolution-form-studio-pro' ), 'required' => true, 'width' => '100', 'programs' => self::default_programs() ),
        );
        $types['intl_tel'] = array(
            'label'    => __( 'International Phone', 'techsolution-form-studio-pro' ),
            'icon'     => 'dashicons-translation',
            'category' => 'pro',
            'defaults' => array( 'label' => __( 'Mobile Number', 'techsolution-form-studio-pro' ), 'required' => true, 'width' => '100', 'default_country' => 'PK' ),
        );
        $types['payment'] = array(
            'label'    => __( 'Payment Details (not processed)', 'techsolution-form-studio-pro' ),
            'icon'     => 'dashicons-cart',
            'category' => 'pro',
            'defaults' => array( 'label' => __( 'Payment Details', 'techsolution-form-studio-pro' ), 'required' => true, 'width' => '100', 'payment_amount' => '50.00', 'payment_currency' => 'USD' ),
        );
        $types['signature'] = array(
            'label'    => __( 'Digital Signature', 'techsolution-form-studio-pro' ),
            'icon'     => 'dashicons-edit',
            'category' => 'pro',
            'defaults' => array( 'label' => __( 'Signature', 'techsolution-form-studio-pro' ), 'required' => true, 'width' => '100', 'canvas_height' => '160', 'pen_color' => '#0f172a' ),
        );
        $types['calculation'] = array(
            'label'    => __( 'Calculation', 'techsolution-form-studio-pro' ),
            'icon'     => 'dashicons-calculator',
            'category' => 'pro',
            'defaults' => array( 'label' => __( 'Total', 'techsolution-form-studio-pro' ), 'width' => '100', 'formula' => '', 'calc_prefix' => '$', 'calc_suffix' => '', 'decimals' => '2' ),
        );
        $types['rich_text'] = array(
            'label'    => __( 'Rich Content', 'techsolution-form-studio-pro' ),
            'icon'     => 'dashicons-editor-paste-text',
            'category' => 'pro',
            'defaults' => array(
                'label'      => __( 'Content block', 'techsolution-form-studio-pro' ),
                'content'    => '<p>' . __( 'Write your content here.', 'techsolution-form-studio-pro' ) . '</p>',
                'width'      => '100',
                'font_size'  => '16',
                'line_height'=> '1.6',
                'text_color' => '',
                'bg_color'   => '',
                'border_color' => '',
                'padding'    => '0',
                'radius'     => '0',
                'ul_style'   => 'disc',
                'ol_style'   => 'decimal',
            ),
        );
        return $types;
    }

    /** Widths available to the Rich Content block, in percent of the form row. */
    public static function rich_widths() {
        return array( '100', '75', '66', '50', '33', '25' );
    }

    public static function list_styles() {
        return array(
            'ul' => array( 'disc', 'circle', 'square', 'none' ),
            'ol' => array( 'decimal', 'decimal-leading-zero', 'lower-alpha', 'upper-alpha', 'lower-roman', 'upper-roman', 'lower-greek', 'none' ),
        );
    }

    /**
     * HTML allowed in Rich Content blocks. Inline styles are filtered by WordPress (safecss_filter_attr),
     * which keeps formatting such as color, background-color, font-size and text-align and drops anything else.
     */
    public static function rich_allowed_html() {
        $common = array( 'style' => true, 'class' => true );
        $allowed = array();
        foreach ( array( 'p', 'div', 'span', 'strong', 'b', 'em', 'i', 'u', 's', 'del', 'ins', 'sub', 'sup', 'small', 'mark', 'code', 'blockquote', 'pre', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'li' ) as $tag ) {
            $allowed[ $tag ] = $common;
        }
        $allowed['ol'] = $common + array( 'start' => true, 'type' => true, 'reversed' => true );
        $allowed['a']  = $common + array( 'href' => true, 'target' => true, 'rel' => true, 'title' => true );
        $allowed['br'] = array();
        $allowed['hr'] = $common;
        return $allowed;
    }

    public static function sanitize_rich_html( $html ) {
        return wp_kses( (string) $html, self::rich_allowed_html(), array( 'http', 'https', 'mailto', 'tel' ) );
    }

    /**
     * Sanitize the type-specific keys saved by the builder or an imported form.
     */
    public static function sanitize_config( $item, $raw, $type ) {
        switch ( $type ) {
            case 'address':
                $item['address_show_street'] = ! isset( $raw['address_show_street'] ) || ! empty( $raw['address_show_street'] );
                $item['address_show_postal'] = ! isset( $raw['address_show_postal'] ) || ! empty( $raw['address_show_postal'] );
                break;
            case 'admission':
                $programs         = isset( $raw['programs'] ) && is_array( $raw['programs'] ) ? $raw['programs'] : self::default_programs();
                $item['programs'] = array_values( array_filter( array_map( 'sanitize_text_field', array_filter( array_slice( $programs, 0, 100 ), 'is_scalar' ) ), 'strlen' ) );
                break;
            case 'intl_tel':
                $rules                   = self::phone_rules();
                $country                 = isset( $raw['default_country'] ) ? strtoupper( sanitize_key( $raw['default_country'] ) ) : 'PK';
                $item['default_country'] = isset( $rules[ $country ] ) ? $country : 'PK';
                break;
            case 'payment':
                $amount                   = isset( $raw['payment_amount'] ) ? preg_replace( '/[^0-9.]/', '', (string) $raw['payment_amount'] ) : '';
                $currency                 = isset( $raw['payment_currency'] ) ? strtoupper( sanitize_key( $raw['payment_currency'] ) ) : 'USD';
                $item['payment_amount']   = is_numeric( $amount ) ? number_format( (float) $amount, 2, '.', '' ) : '0.00';
                $item['payment_currency'] = preg_match( '/^[A-Z]{3}$/', $currency ) ? $currency : 'USD';
                break;
            case 'signature':
                $color                 = isset( $raw['pen_color'] ) ? sanitize_hex_color( $raw['pen_color'] ) : '';
                $item['canvas_height'] = isset( $raw['canvas_height'] ) ? (string) max( 100, min( 400, absint( $raw['canvas_height'] ) ) ) : '160';
                $item['pen_color']     = $color ? $color : '#0f172a';
                break;
            case 'rich_text':
                $styles              = self::list_styles();
                $width               = isset( $raw['width'] ) ? (string) $raw['width'] : '100';
                $number              = function ( $key, $min, $max, $default ) use ( $raw ) {
                    return isset( $raw[ $key ] ) && is_numeric( $raw[ $key ] ) ? (string) max( $min, min( $max, 0 + $raw[ $key ] ) ) : $default;
                };
                $color               = function ( $key ) use ( $raw ) {
                    $value = isset( $raw[ $key ] ) && is_string( $raw[ $key ] ) ? sanitize_hex_color( $raw[ $key ] ) : '';
                    return $value ? $value : '';
                };
                $item['content']      = self::sanitize_rich_html( isset( $raw['content'] ) ? $raw['content'] : '' );
                $item['width']        = in_array( $width, self::rich_widths(), true ) ? $width : '100';
                $item['font_size']    = $number( 'font_size', 10, 48, '16' );
                $item['line_height']  = $number( 'line_height', 1, 3, '1.6' );
                $item['padding']      = $number( 'padding', 0, 80, '0' );
                $item['radius']       = $number( 'radius', 0, 40, '0' );
                $item['text_color']   = $color( 'text_color' );
                $item['bg_color']     = $color( 'bg_color' );
                $item['border_color'] = $color( 'border_color' );
                $item['ul_style']     = isset( $raw['ul_style'] ) && in_array( $raw['ul_style'], $styles['ul'], true ) ? $raw['ul_style'] : 'disc';
                $item['ol_style']     = isset( $raw['ol_style'] ) && in_array( $raw['ol_style'], $styles['ol'], true ) ? $raw['ol_style'] : 'decimal';
                $item['required']     = false;
                $item['help_text']    = '';
                $item['placeholder']  = '';
                break;
            case 'calculation':
                $formula             = isset( $raw['formula'] ) ? (string) $raw['formula'] : '';
                $item['formula']     = preg_replace( '/[^0-9a-z_\[\]{}+\-*\/().\s]/i', '', $formula );
                $item['calc_prefix'] = isset( $raw['calc_prefix'] ) ? sanitize_text_field( $raw['calc_prefix'] ) : '';
                $item['calc_suffix'] = isset( $raw['calc_suffix'] ) ? sanitize_text_field( $raw['calc_suffix'] ) : '';
                $item['decimals']    = isset( $raw['decimals'] ) ? (string) min( 6, absint( $raw['decimals'] ) ) : '2';
                $item['required']    = false;
                break;
        }
        return $item;
    }

    /* ------------------------------------------------------------------ */
    /* Assets                                                              */
    /* ------------------------------------------------------------------ */

    public static function enqueue_public_assets( $form ) {
        $types = wp_list_pluck( isset( $form['fields_data'] ) ? (array) $form['fields_data'] : array(), 'type' );
        if ( ! array_intersect( self::TYPES, $types ) ) {
            return;
        }
        wp_enqueue_style( 'tsfs-pro-public', TSFS_PRO_PLUGIN_URL . 'assets/css/tsfs-pro-public.css', array( 'tsfs-public-css' ), TSFS_PRO_VERSION );
        wp_enqueue_script( 'tsfs-pro-public', TSFS_PRO_PLUGIN_URL . 'assets/js/tsfs-pro-public.js', array( 'jquery', 'wp-i18n', 'tsfs-public-js' ), TSFS_PRO_VERSION, true );
        wp_set_script_translations( 'tsfs-pro-public', 'techsolution-form-studio-pro' );
        if ( in_array( 'address', $types, true ) ) {
            TSFS_Pro_Address::enqueue();
        }
    }

    public static function enqueue_builder_assets() {
        wp_enqueue_editor();
        wp_enqueue_style( 'tsfs-pro-admin', TSFS_PRO_PLUGIN_URL . 'assets/css/tsfs-pro-admin.css', array( 'tsfs-admin-css' ), TSFS_PRO_VERSION );
        TSFS_Pro_Address::enqueue();
        wp_enqueue_style( 'tsfs-pro-public', TSFS_PRO_PLUGIN_URL . 'assets/css/tsfs-pro-public.css', array(), TSFS_PRO_VERSION );
        wp_enqueue_script( 'tsfs-pro-builder', TSFS_PRO_PLUGIN_URL . 'assets/js/tsfs-pro-builder.js', array( 'jquery', 'tsfs-builder-js', 'tsfs-pro-address-js' ), TSFS_PRO_VERSION, true );
        $countries = array();
        foreach ( self::phone_rules() as $code => $rule ) {
            $countries[] = array( 'code' => $code, 'label' => $rule['flag'] . ' ' . $rule['name'] . ' (' . $rule['dial'] . ')' );
        }
        wp_localize_script( 'tsfs-pro-builder', 'tsfsProBuilderData', array(
            'address_template' => TSFS_Pro_Address::preview_template(),
            'phone_countries'  => $countries,
            'list_styles'      => self::list_styles(),
            'rich_widths'      => self::rich_widths(),
        ) );
    }

    public static function render_palette() {
        $items = array(
            'address'     => array( 'dashicons-location-alt', __( 'Complete Address', 'techsolution-form-studio-pro' ), 'address country province state city postal zip street' ),
            'intl_tel'    => array( 'dashicons-translation', __( 'International Phone', 'techsolution-form-studio-pro' ), 'phone mobile international dial code' ),
            'signature'   => array( 'dashicons-edit', __( 'Digital Signature', 'techsolution-form-studio-pro' ), 'signature sign draw' ),
            'calculation' => array( 'dashicons-calculator', __( 'Calculation', 'techsolution-form-studio-pro' ), 'calculation formula total price sum' ),
            'payment'     => array( 'dashicons-cart', __( 'Payment Details', 'techsolution-form-studio-pro' ), 'payment card fee checkout' ),
            'admission'   => array( 'dashicons-welcome-learn-more', __( 'Admission', 'techsolution-form-studio-pro' ), 'admission student university application' ),
            'rich_text'   => array( 'dashicons-editor-paste-text', __( 'Rich Content', 'techsolution-form-studio-pro' ), 'rich text editor paragraph content bold color list heading formatted' ),
        );
        ?>
        <div class="tsfs-fields-group">
            <h4><?php esc_html_e( 'Pro Fields', 'techsolution-form-studio-pro' ); ?></h4>
            <div class="tsfs-palette-grid">
                <?php foreach ( $items as $type => $item ) : ?>
                    <button type="button" class="tsfs-palette-item" data-type="<?php echo esc_attr( $type ); ?>" data-keywords="<?php echo esc_attr( $item[2] ); ?>">
                        <span class="tsfs-palette-icon is-advanced"><span class="dashicons <?php echo esc_attr( $item[0] ); ?>"></span></span>
                        <span class="title"><?php echo esc_html( $item[1] ); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    public static function render_templates() {
        $templates = array(
            'tsfs_pro_admission' => array( 'dashicons-welcome-learn-more', __( 'University Admissions', 'techsolution-form-studio-pro' ), __( 'Applicant profile, international phone, transcript upload and fee details.', 'techsolution-form-studio-pro' ) ),
            'tsfs_pro_order'     => array( 'dashicons-cart', __( 'Order with Calculated Total', 'techsolution-form-studio-pro' ), __( 'Quantity and price choices with a live total, delivery address and signature.', 'techsolution-form-studio-pro' ) ),
        );
        foreach ( $templates as $key => $template ) :
            ?>
            <div class="tsfs-template-card" data-template="<?php echo esc_attr( $key ); ?>" style="background:var(--tsfs-adm-slate-50); border:1.5px solid var(--tsfs-adm-slate-200); border-radius:8px; padding:18px; cursor:pointer;">
                <div style="font-size:24px; color:#4f46e5; margin-bottom:8px;"><span class="dashicons <?php echo esc_attr( $template[0] ); ?>"></span></div>
                <h4 style="margin:0 0 6px 0; font-size:15px; font-weight:700;"><?php echo esc_html( $template[1] ); ?></h4>
                <p style="margin:0; font-size:12px; color:var(--tsfs-adm-slate-500);"><?php echo esc_html( $template[2] ); ?></p>
            </div>
            <?php
        endforeach;
    }

    /* ------------------------------------------------------------------ */
    /* Rendering (inside the core label / help / error wrapper)            */
    /* ------------------------------------------------------------------ */

    public static function render_address( $field, $name, $id, $required ) {
        TSFS_Pro_Address::render( $name, $id, $required, $field );
    }

    public static function render_admission( $field, $name, $id, $required ) {
        $programs = ! empty( $field['programs'] ) && is_array( $field['programs'] ) ? $field['programs'] : self::default_programs();
        $parts    = array(
            'student_name' => array( __( 'Applicant full name', 'techsolution-form-studio-pro' ), 'text', $required ),
            'father_name'  => array( __( "Father's / guardian's name", 'techsolution-form-studio-pro' ), 'text', false ),
            'cnic'         => array( __( 'CNIC / Form-B / national ID', 'techsolution-form-studio-pro' ), 'cnic', false ),
            'program'      => array( __( 'Program', 'techsolution-form-studio-pro' ), 'program', false ),
            'prev_degree'  => array( __( 'Previous education', 'techsolution-form-studio-pro' ), 'text', false ),
            'marks'        => array( __( 'Marks / percentage', 'techsolution-form-studio-pro' ), 'text', false ),
        );
        echo '<div class="tsfs-pro-admission-panel"><div class="tsfs-adm-row">';
        foreach ( $parts as $key => $part ) {
            $part_id = 'student_name' === $key ? $id : $id . '_' . $key;
            echo '<div><label class="tsfs-sublabel" for="' . esc_attr( $part_id ) . '">' . esc_html( $part[0] ) . ( $part[2] ? ' *' : '' ) . '</label>';
            if ( 'program' === $part[1] ) {
                echo '<select id="' . esc_attr( $part_id ) . '" name="' . esc_attr( $name . '[' . $key . ']' ) . '" class="tsfs-input tsfs-select">';
                foreach ( $programs as $program ) {
                    echo '<option value="' . esc_attr( $program ) . '">' . esc_html( $program ) . '</option>';
                }
                echo '</select>';
            } elseif ( 'cnic' === $part[1] ) {
                echo '<input type="text" id="' . esc_attr( $part_id ) . '" name="' . esc_attr( $name . '[' . $key . ']' ) . '" class="tsfs-input tsfs-input-cnic" inputmode="numeric" maxlength="15" pattern="[0-9]{5}-[0-9]{7}-[0-9]" placeholder="12345-1234567-1">';
            } else {
                echo '<input type="text" id="' . esc_attr( $part_id ) . '" name="' . esc_attr( $name . '[' . $key . ']' ) . '" class="tsfs-input"' . ( $part[2] ? ' required' : '' ) . '>';
            }
            echo '</div>';
        }
        echo '</div></div>';
    }

    public static function render_intl_tel( $field, $name, $id, $required ) {
        $rules   = self::phone_rules();
        $default = ! empty( $field['default_country'] ) && isset( $rules[ $field['default_country'] ] ) ? $field['default_country'] : 'PK';
        $rule    = $rules[ $default ];
        ?>
        <div class="tsfs-intl-tel-control" data-intl-tel-wrap="1">
            <div class="tsfs-dial-select-wrap">
                <select name="<?php echo esc_attr( $name ); ?>_country" class="tsfs-input tsfs-dial-select" aria-label="<?php esc_attr_e( 'Country dial code', 'techsolution-form-studio-pro' ); ?>">
                    <?php foreach ( $rules as $iso => $info ) : ?>
                        <option value="<?php echo esc_attr( $iso ); ?>" data-dial="<?php echo esc_attr( $info['dial'] ); ?>" data-name="<?php echo esc_attr( $info['name'] ); ?>" data-min="<?php echo esc_attr( $info['min_digits'] ); ?>" data-max="<?php echo esc_attr( $info['max_digits'] ); ?>" data-mask="<?php echo esc_attr( $info['mask'] ); ?>" data-placeholder="<?php echo esc_attr( $info['placeholder'] ); ?>" <?php selected( $iso, $default ); ?>>
                            <?php echo esc_html( $info['flag'] . ' ' . $info['dial'] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="tsfs-tel-input-col">
                <div class="tsfs-tel-input-box">
                    <input type="tel" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>" class="tsfs-input tsfs-tel-masked-input" placeholder="<?php echo esc_attr( $rule['placeholder'] ); ?>" maxlength="<?php echo esc_attr( strlen( $rule['mask'] ) ); ?>" data-min-digits="<?php echo esc_attr( $rule['min_digits'] ); ?>" data-max-digits="<?php echo esc_attr( $rule['max_digits'] ); ?>" data-mask="<?php echo esc_attr( $rule['mask'] ); ?>" autocomplete="tel-national" <?php echo $required ? 'required' : ''; ?>>
                    <span class="tsfs-tel-status-icon" aria-hidden="true"></span>
                </div>
                <div class="tsfs-phone-helper-row">
                    <span class="tsfs-phone-format-hint"><?php esc_html_e( 'Format:', 'techsolution-form-studio-pro' ); ?> <strong class="tsfs-hint-mask"><?php echo esc_html( $rule['mask'] ); ?></strong></span>
                    <span class="tsfs-phone-digits-counter" data-max="<?php echo esc_attr( $rule['max_digits'] ); ?>" data-min="<?php echo esc_attr( $rule['min_digits'] ); ?>">
                        <span class="tsfs-count-num">0</span>/<span class="tsfs-count-max"><?php echo esc_html( $rule['max_digits'] ); ?></span>
                    </span>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Payment details. The card number and security code have no name attribute, so browsers never send them to
     * this site; only the cardholder, expiry, detected brand and last four digits are submitted. Nothing is charged.
     */
    public static function render_payment( $field, $name, $id, $required ) {
        $amount   = ! empty( $field['payment_amount'] ) ? $field['payment_amount'] : '0.00';
        $currency = ! empty( $field['payment_currency'] ) ? $field['payment_currency'] : 'USD';
        ?>
        <div class="tsfs-payment-card-panel" data-payment-panel="1">
            <div class="tsfs-pay-header">
                <div class="tsfs-pay-amount-box">
                    <span class="tsfs-pay-label"><?php esc_html_e( 'Amount:', 'techsolution-form-studio-pro' ); ?></span>
                    <span class="tsfs-pay-amount"><?php echo esc_html( $currency . ' ' . $amount ); ?></span>
                </div>
            </div>
            <p class="tsfs-pay-notice"><?php esc_html_e( 'Your card is not charged by this form. Only the cardholder name, expiry and last four digits are sent.', 'techsolution-form-studio-pro' ); ?></p>
            <div class="tsfs-pay-field-group">
                <label class="tsfs-sublabel" for="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Cardholder name', 'techsolution-form-studio-pro' ); ?></label>
                <input type="text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>_holder" class="tsfs-input tsfs-card-holder-input" autocomplete="cc-name" <?php echo $required ? 'required' : ''; ?>>
            </div>
            <div class="tsfs-pay-field-group">
                <label class="tsfs-sublabel" for="<?php echo esc_attr( $id ); ?>_card"><?php esc_html_e( 'Card number', 'techsolution-form-studio-pro' ); ?></label>
                <div class="tsfs-card-number-wrapper">
                    <input type="text" id="<?php echo esc_attr( $id ); ?>_card" class="tsfs-input tsfs-card-number-input" placeholder="0000 0000 0000 0000" maxlength="19" autocomplete="cc-number" inputmode="numeric">
                    <span class="tsfs-card-brand-detected-pill"><?php esc_html_e( 'Card', 'techsolution-form-studio-pro' ); ?></span>
                </div>
                <input type="hidden" name="<?php echo esc_attr( $name ); ?>_brand" class="tsfs-card-brand-hidden" value="">
                <input type="hidden" name="<?php echo esc_attr( $name ); ?>_last4" class="tsfs-card-last4-hidden" value="">
            </div>
            <div class="tsfs-pay-split-row">
                <div class="tsfs-pay-field-group">
                    <label class="tsfs-sublabel" for="<?php echo esc_attr( $id ); ?>_exp"><?php esc_html_e( 'Expiry (MM / YY)', 'techsolution-form-studio-pro' ); ?></label>
                    <input type="text" id="<?php echo esc_attr( $id ); ?>_exp" name="<?php echo esc_attr( $name ); ?>_exp" class="tsfs-input tsfs-card-exp-input" placeholder="MM / YY" maxlength="7" autocomplete="cc-exp" inputmode="numeric" <?php echo $required ? 'required' : ''; ?>>
                </div>
                <div class="tsfs-pay-field-group">
                    <label class="tsfs-sublabel" for="<?php echo esc_attr( $id ); ?>_cvc"><?php esc_html_e( 'Security code', 'techsolution-form-studio-pro' ); ?></label>
                    <input type="password" id="<?php echo esc_attr( $id ); ?>_cvc" class="tsfs-input tsfs-card-cvc-input" placeholder="CVC" maxlength="4" autocomplete="cc-csc" inputmode="numeric">
                </div>
            </div>
        </div>
        <?php
    }

    public static function render_signature( $field, $name, $id, $required ) {
        $height = ! empty( $field['canvas_height'] ) ? absint( $field['canvas_height'] ) : 160;
        $color  = ! empty( $field['pen_color'] ) ? sanitize_hex_color( $field['pen_color'] ) : '';
        ?>
        <div class="tsfs-signature-pad-wrap" data-signature-wrap="1">
            <div class="tsfs-signature-canvas-box" style="height:<?php echo esc_attr( $height ); ?>px;">
                <canvas class="tsfs-signature-canvas" data-pen-color="<?php echo esc_attr( $color ? $color : '#0f172a' ); ?>" role="img" aria-label="<?php esc_attr_e( 'Signature pad', 'techsolution-form-studio-pro' ); ?>"></canvas>
                <div class="tsfs-signature-guide-line"><span><?php esc_html_e( 'Sign above the line', 'techsolution-form-studio-pro' ); ?></span></div>
            </div>
            <div class="tsfs-signature-toolbar">
                <button type="button" class="tsfs-sig-clear-btn"><?php esc_html_e( 'Clear signature', 'techsolution-form-studio-pro' ); ?></button>
                <span class="tsfs-sig-status-badge tsfs-sig-empty" aria-live="polite"><?php esc_html_e( 'Awaiting signature', 'techsolution-form-studio-pro' ); ?></span>
            </div>
            <input type="hidden" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>" class="tsfs-signature-data">
        </div>
        <?php
    }

    public static function render_calculation( $field, $name, $id ) {
        $decimals = isset( $field['decimals'] ) ? absint( $field['decimals'] ) : 2;
        $prefix   = isset( $field['calc_prefix'] ) ? $field['calc_prefix'] : '';
        $suffix   = isset( $field['calc_suffix'] ) ? $field['calc_suffix'] : '';
        ?>
        <div class="tsfs-calculation-wrap" data-formula="<?php echo esc_attr( isset( $field['formula'] ) ? $field['formula'] : '' ); ?>" data-prefix="<?php echo esc_attr( $prefix ); ?>" data-suffix="<?php echo esc_attr( $suffix ); ?>" data-decimals="<?php echo esc_attr( $decimals ); ?>">
            <div class="tsfs-calculation-box">
                <span class="tsfs-calc-sigma-icon" aria-hidden="true">∑</span>
                <output class="tsfs-calc-display-val" for="<?php echo esc_attr( $id ); ?>" aria-live="polite"><?php echo esc_html( $prefix . number_format( 0, $decimals ) . ( $suffix ? ' ' . $suffix : '' ) ); ?></output>
            </div>
            <input type="hidden" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>" class="tsfs-calculation-hidden" value="0">
        </div>
        <?php
    }

    /**
     * Formatted content block. The core label is hidden by CSS; the content itself is the block.
     */
    public static function render_rich_text( $field ) {
        $style = array(
            '--tsfs-rich-font-size:' . ( isset( $field['font_size'] ) ? (float) $field['font_size'] : 16 ) . 'px',
            '--tsfs-rich-line-height:' . ( isset( $field['line_height'] ) ? (float) $field['line_height'] : 1.6 ),
            '--tsfs-rich-padding:' . ( isset( $field['padding'] ) ? absint( $field['padding'] ) : 0 ) . 'px',
            '--tsfs-rich-radius:' . ( isset( $field['radius'] ) ? absint( $field['radius'] ) : 0 ) . 'px',
            '--tsfs-rich-ul:' . ( ! empty( $field['ul_style'] ) ? $field['ul_style'] : 'disc' ),
            '--tsfs-rich-ol:' . ( ! empty( $field['ol_style'] ) ? $field['ol_style'] : 'decimal' ),
        );
        foreach ( array( 'text_color' => '--tsfs-rich-color', 'bg_color' => '--tsfs-rich-bg', 'border_color' => '--tsfs-rich-border' ) as $key => $var ) {
            $color = ! empty( $field[ $key ] ) ? sanitize_hex_color( $field[ $key ] ) : '';
            if ( $color ) {
                $style[] = $var . ':' . $color;
            }
        }
        $html = self::replace_smart_codes( self::sanitize_rich_html( isset( $field['content'] ) ? $field['content'] : '' ) );
        echo '<div class="tsfs-rich-content" style="' . esc_attr( implode( ';', $style ) ) . '">' . $html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- filtered by wp_kses; SmartCode values are escaped in replace_smart_codes().
    }

    /**
     * Replace SmartCodes in Rich Content. Every value is escaped. {field:key} becomes a placeholder that the
     * frontend script fills with the visitor's answer as they type.
     */
    public static function replace_smart_codes( $html ) {
        if ( false === strpos( $html, '{' ) ) {
            return $html;
        }
        $user   = wp_get_current_user();
        $values = array(
            '{site_name}'    => esc_html( get_bloginfo( 'name' ) ),
            '{site_url}'     => esc_html( home_url( '/' ) ),
            '{page_title}'   => esc_html( (string) get_the_title() ),
            '{current_date}' => esc_html( wp_date( get_option( 'date_format' ) ) ),
            '{user_name}'    => $user && $user->exists() ? esc_html( $user->display_name ) : '',
            '{user_email}'   => $user && $user->exists() ? esc_html( $user->user_email ) : '',
        );
        $html = strtr( $html, $values );
        return preg_replace_callback(
            '/\{field:([a-z0-9_\-]+)\}/i',
            function ( $m ) {
                return '<span class="tsfs-smart-field" data-field="' . esc_attr( sanitize_key( $m[1] ) ) . '"></span>';
            },
            $html
        );
    }

    /**
     * Content blocks collect no input.
     */
    public static function process_rich_text( $result ) {
        return $result;
    }

    /* ------------------------------------------------------------------ */
    /* Submission processing (nonce already verified by the core plugin)   */
    /* ------------------------------------------------------------------ */

    private static function posted( $key ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in TSFS_AJAX::ajax_submit_form().
        return isset( $_POST[ $key ] ) && is_scalar( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
    }

    private static function required_error( $label ) {
        /* translators: %s: field label */
        return new WP_Error( 'required', sprintf( __( '%s is required.', 'techsolution-form-studio-pro' ), $label ) );
    }

    public static function process_address( $result, $field, $ctx ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- every part is sanitized in TSFS_Pro_Address::validate().
        $raw     = isset( $_POST[ $ctx['name'] ] ) ? wp_unslash( $_POST[ $ctx['name'] ] ) : null;
        $address = TSFS_Pro_Address::validate( $raw, $ctx['required'], $field );
        return is_wp_error( $address ) ? $address : array( 'value' => $address );
    }

    public static function process_admission( $result, $field, $ctx ) {
        $clean = array();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $raw = isset( $_POST[ $ctx['name'] ] ) && is_array( $_POST[ $ctx['name'] ] ) ? wp_unslash( $_POST[ $ctx['name'] ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per key below.
        foreach ( array( 'student_name', 'father_name', 'cnic', 'program', 'prev_degree', 'marks' ) as $key ) {
            $clean[ $key ] = isset( $raw[ $key ] ) && is_scalar( $raw[ $key ] ) ? sanitize_text_field( $raw[ $key ] ) : '';
        }
        if ( $ctx['required'] && '' === $clean['student_name'] ) {
            /* translators: %s: field label */
            return new WP_Error( 'required', sprintf( __( '%s: applicant full name is required.', 'techsolution-form-studio-pro' ), $ctx['label'] ) );
        }
        $cnic = TSFS_Form::normalize_cnic( $clean['cnic'] );
        if ( false === $cnic ) {
            return new WP_Error( 'invalid', __( 'Enter CNIC / Form-B as 12345-1234567-1 (13 digits).', 'techsolution-form-studio-pro' ) );
        }
        $clean['cnic'] = $cnic;
        $programs      = ! empty( $field['programs'] ) ? (array) $field['programs'] : self::default_programs();
        if ( '' !== $clean['program'] && ! in_array( $clean['program'], $programs, true ) ) {
            return new WP_Error( 'invalid', __( 'Please choose a program from the list.', 'techsolution-form-studio-pro' ) );
        }
        if ( ! array_filter( $clean ) ) {
            return array( 'value' => '' );
        }
        return array( 'value' => $clean, 'user_name' => $clean['student_name'] );
    }

    public static function process_intl_tel( $result, $field, $ctx ) {
        $rules   = self::phone_rules();
        $country = strtoupper( self::posted( $ctx['name'] . '_country' ) );
        $phone   = self::posted( $ctx['name'] );
        $digits  = preg_replace( '/\D/', '', $phone );

        if ( '' === $digits ) {
            return $ctx['required'] ? self::required_error( $ctx['label'] ) : array( 'value' => '' );
        }
        if ( ! isset( $rules[ $country ] ) ) {
            return new WP_Error( 'invalid', __( 'Please choose a country code.', 'techsolution-form-studio-pro' ) );
        }
        $rule = $rules[ $country ];
        $len  = strlen( $digits );
        if ( $len < $rule['min_digits'] || $len > $rule['max_digits'] ) {
            $expected = $rule['min_digits'] === $rule['max_digits']
                /* translators: %d: number of digits */
                ? sprintf( _n( '%d digit', '%d digits', $rule['min_digits'], 'techsolution-form-studio-pro' ), $rule['min_digits'] )
                /* translators: 1: minimum digits, 2: maximum digits */
                : sprintf( __( '%1$d to %2$d digits', 'techsolution-form-studio-pro' ), $rule['min_digits'], $rule['max_digits'] );
            /* translators: 1: country name, 2: dial code, 3: expected digits, 4: digits entered */
            return new WP_Error( 'invalid', sprintf( __( 'A %1$s (%2$s) number has %3$s. You entered %4$d.', 'techsolution-form-studio-pro' ), $rule['name'], $rule['dial'], $expected, $len ) );
        }
        return array( 'value' => $rule['dial'] . ' ' . $phone );
    }

    public static function process_payment( $result, $field, $ctx ) {
        $holder = self::posted( $ctx['name'] . '_holder' );
        $exp    = preg_replace( '/\D/', '', self::posted( $ctx['name'] . '_exp' ) );
        $last4  = preg_replace( '/\D/', '', self::posted( $ctx['name'] . '_last4' ) );
        $brand  = sanitize_key( self::posted( $ctx['name'] . '_brand' ) );

        if ( '' === $holder && '' === $last4 && '' === $exp ) {
            return $ctx['required'] ? self::required_error( $ctx['label'] ) : array( 'value' => '' );
        }
        if ( '' === $holder || 4 !== strlen( $last4 ) || 4 !== strlen( $exp ) ) {
            return new WP_Error( 'invalid', __( 'Please enter the cardholder name, a valid card number and the expiry date.', 'techsolution-form-studio-pro' ) );
        }
        $month = (int) substr( $exp, 0, 2 );
        $year  = 2000 + (int) substr( $exp, 2, 2 );
        if ( $month < 1 || $month > 12 || mktime( 0, 0, 0, $month + 1, 1, $year ) <= time() ) {
            return new WP_Error( 'invalid', __( 'The card expiry date is not valid.', 'techsolution-form-studio-pro' ) );
        }
        $currency = ! empty( $field['payment_currency'] ) ? $field['payment_currency'] : 'USD';
        $amount   = ! empty( $field['payment_amount'] ) ? $field['payment_amount'] : '0.00';
        return array(
            'value' => array(
                'amount'      => $currency . ' ' . $amount,
                'cardholder'  => $holder,
                'card'        => strtoupper( in_array( $brand, array( 'visa', 'mastercard', 'amex', 'discover' ), true ) ? $brand : 'card' ) . ' •••• ' . $last4,
                'expiry'      => sprintf( '%02d/%02d', $month, $year % 100 ),
                'status'      => __( 'Not charged', 'techsolution-form-studio-pro' ),
            ),
        );
    }

    public static function process_signature( $result, $field, $ctx ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $data = isset( $_POST[ $ctx['name'] ] ) && is_string( $_POST[ $ctx['name'] ] ) ? trim( wp_unslash( $_POST[ $ctx['name'] ] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- validated as a base64 PNG data URL below.
        if ( '' === $data ) {
            return $ctx['required'] ? new WP_Error( 'required', __( 'Please sign before submitting.', 'techsolution-form-studio-pro' ) ) : array( 'value' => '' );
        }
        if ( strlen( $data ) > 400000 || ! preg_match( '#^data:image/png;base64,([A-Za-z0-9+/=]+)$#', $data, $m ) ) {
            return new WP_Error( 'invalid', __( 'The signature could not be read. Please clear it and sign again.', 'techsolution-form-studio-pro' ) );
        }
        $binary = base64_decode( $m[1], true );
        if ( false === $binary || 0 !== strpos( $binary, "\x89PNG\r\n\x1a\n" ) ) {
            return new WP_Error( 'invalid', __( 'The signature could not be read. Please clear it and sign again.', 'techsolution-form-studio-pro' ) );
        }
        return array( 'value' => $data );
    }

    /**
     * Recompute the total on the server from the submitted values, so a modified browser value is never stored.
     */
    public static function process_calculation( $result, $field, $ctx ) {
        $values = array();
        foreach ( TSFS_Pro_Calculator::references( isset( $field['formula'] ) ? $field['formula'] : '' ) as $key ) {
            $key = sanitize_key( $key );
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $raw = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- reduced to numbers below.
            if ( is_array( $raw ) ) {
                $sum = 0.0;
                foreach ( $raw as $item ) {
                    $number = is_scalar( $item ) ? self::to_number( $item ) : null;
                    $sum   += null === $number ? 1 : $number;
                }
                $values[ $key ] = $sum;
            } else {
                $number         = self::to_number( $raw );
                $values[ $key ] = null === $number ? 0.0 : $number;
            }
        }
        $total    = TSFS_Pro_Calculator::evaluate( isset( $field['formula'] ) ? $field['formula'] : '', $values );
        $decimals = isset( $field['decimals'] ) ? absint( $field['decimals'] ) : 2;
        $prefix   = isset( $field['calc_prefix'] ) ? $field['calc_prefix'] : '';
        $suffix   = isset( $field['calc_suffix'] ) ? $field['calc_suffix'] : '';
        return array( 'value' => $prefix . number_format( null === $total ? 0 : $total, $decimals, '.', '' ) . ( $suffix ? ' ' . $suffix : '' ) );
    }

    private static function to_number( $value ) {
        $clean = preg_replace( '/[^0-9.\-]/', '', sanitize_text_field( (string) $value ) );
        return is_numeric( $clean ) ? (float) $clean : null;
    }

    public static function phone_rules() {
        static $rules = null;
        if ( null === $rules ) {
            $rules = require TSFS_PRO_DIR . 'includes/phone-rules.php';
        }
        return $rules;
    }
}
