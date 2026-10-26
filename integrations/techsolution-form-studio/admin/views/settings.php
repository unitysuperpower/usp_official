<?php
/**
 * View: Settings — anti-spam (reCAPTCHA/Turnstile), rate limiting, upload hardening and privacy.
 *
 * @package    TSFS_Plugin
 * @subpackage TSFS_Plugin/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Included from a TSFS_Admin method, so these variables are local to that method, not globals.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$sec_settings = TSFS_Security::get_settings();
$has_pro      = defined( 'TSFS_PRO_VERSION' );
$upload_dir   = wp_upload_dir();
$sandbox_dir  = $upload_dir['basedir'] . '/techsolution-form-studio';
$has_htaccess = file_exists( $sandbox_dir . '/.htaccess' );
$has_index    = file_exists( $sandbox_dir . '/index.php' );
$provider     = $sec_settings['anti_spam_provider'];

$tsfs_providers = array(
    'none'         => array( __( 'Built-in only', 'techsolution-form-studio' ), __( 'Honeypot and timing checks. No external service.', 'techsolution-form-studio' ) ),
    'recaptcha_v2' => array( __( 'reCAPTCHA v2', 'techsolution-form-studio' ), __( '“I’m not a robot” checkbox by Google.', 'techsolution-form-studio' ) ),
    'recaptcha_v3' => array( __( 'reCAPTCHA v3', 'techsolution-form-studio' ), __( 'Invisible score-based check by Google.', 'techsolution-form-studio' ) ),
    'turnstile'    => array( __( 'Cloudflare Turnstile', 'techsolution-form-studio' ), __( 'Privacy-friendly challenge by Cloudflare.', 'techsolution-form-studio' ) ),
);
$tsfs_sections  = array(
    'antispam'   => array( 'dashicons-shield', __( 'Spam protection', 'techsolution-form-studio' ) ),
    'ratelimit'  => array( 'dashicons-backup', __( 'Rate limiting & blocking', 'techsolution-form-studio' ) ),
    'sandboxing' => array( 'dashicons-lock', __( 'Uploads & exports', 'techsolution-form-studio' ) ),
    'privacy'    => array( 'dashicons-privacy', __( 'Privacy & data', 'techsolution-form-studio' ) ),
);
?>

<div class="wrap tsfs-admin-wrap tsfs-settings-page">
    <header class="tsfs-page-header">
        <div class="tsfs-header-title-group">
            <span class="tsfs-header-icon-box"><span class="dashicons dashicons-admin-settings" aria-hidden="true"></span></span>
            <div>
                <h1 class="tsfs-main-heading"><?php esc_html_e( 'Settings', 'techsolution-form-studio' ); ?></h1>
                <p class="tsfs-main-subheading"><?php esc_html_e( 'Spam protection, submission limits, upload safety and privacy for every form on this site.', 'techsolution-form-studio' ); ?></p>
            </div>
        </div>
    </header>

    <div class="tsfs-status-strip">
        <div class="tsfs-status-item">
            <span class="tsfs-status-dot is-on" aria-hidden="true"></span>
            <div>
                <span class="tsfs-status-label"><?php esc_html_e( 'Spam protection', 'techsolution-form-studio' ); ?></span>
                <strong><?php echo esc_html( $tsfs_providers[ $provider ][0] ?? $tsfs_providers['none'][0] ); ?></strong>
            </div>
        </div>
        <div class="tsfs-status-item">
            <span class="tsfs-status-dot <?php echo esc_attr( ! empty( $sec_settings['enable_rate_limiting'] ) ? 'is-on' : 'is-off' ); ?>" aria-hidden="true"></span>
            <div>
                <span class="tsfs-status-label"><?php esc_html_e( 'Rate limiting', 'techsolution-form-studio' ); ?></span>
                <strong>
                    <?php
                    echo ! empty( $sec_settings['enable_rate_limiting'] )
                        /* translators: 1: maximum submissions, 2: minutes */
                        ? esc_html( sprintf( __( '%1$d per %2$d min per IP', 'techsolution-form-studio' ), absint( $sec_settings['rate_limit_max'] ), absint( $sec_settings['rate_limit_window'] ) ) )
                        : esc_html__( 'Off', 'techsolution-form-studio' );
                    ?>
                </strong>
            </div>
        </div>
        <div class="tsfs-status-item">
            <span class="tsfs-status-dot <?php echo esc_attr( $has_htaccess && $has_index ? 'is-on' : 'is-warn' ); ?>" aria-hidden="true"></span>
            <div>
                <span class="tsfs-status-label"><?php esc_html_e( 'Upload folder', 'techsolution-form-studio' ); ?></span>
                <strong><?php echo $has_htaccess && $has_index ? esc_html__( 'Protected', 'techsolution-form-studio' ) : esc_html__( 'Needs attention', 'techsolution-form-studio' ); ?></strong>
            </div>
        </div>
        <div class="tsfs-status-item">
            <span class="tsfs-status-dot is-on" aria-hidden="true"></span>
            <div>
                <span class="tsfs-status-label"><?php esc_html_e( 'Entry retention', 'techsolution-form-studio' ); ?></span>
                <strong>
                    <?php
                    echo $sec_settings['entry_retention_days']
                        /* translators: %d: number of days */
                        ? esc_html( sprintf( _n( '%d day', '%d days', absint( $sec_settings['entry_retention_days'] ), 'techsolution-form-studio' ), absint( $sec_settings['entry_retention_days'] ) ) )
                        : esc_html__( 'Until deleted', 'techsolution-form-studio' );
                    ?>
                </strong>
            </div>
        </div>
    </div>

    <div class="tsfs-settings-layout">
        <nav class="tsfs-settings-nav" aria-label="<?php esc_attr_e( 'Settings sections', 'techsolution-form-studio' ); ?>">
            <?php $tsfs_first = true; ?>
            <?php foreach ( $tsfs_sections as $tsfs_key => $tsfs_section ) : ?>
                <button type="button" class="tsfs-hub-tab<?php echo $tsfs_first ? ' active' : ''; ?>" data-tab="<?php echo esc_attr( $tsfs_key ); ?>" aria-controls="tsfs-panel-<?php echo esc_attr( $tsfs_key ); ?>" aria-selected="<?php echo $tsfs_first ? 'true' : 'false'; ?>">
                    <span class="dashicons <?php echo esc_attr( $tsfs_section[0] ); ?>" aria-hidden="true"></span>
                    <?php echo esc_html( $tsfs_section[1] ); ?>
                </button>
                <?php $tsfs_first = false; ?>
            <?php endforeach; ?>
        </nav>

        <div class="tsfs-settings-main">
            <!-- Spam protection -->
            <section class="tsfs-hub-panel tsfs-settings-panel active" id="tsfs-panel-antispam">
                <header class="tsfs-panel-head">
                    <h2><?php esc_html_e( 'Spam protection', 'techsolution-form-studio' ); ?></h2>
                    <p><?php esc_html_e( 'Every form includes a hidden honeypot field automatically. Add a CAPTCHA service for extra protection on busy sites.', 'techsolution-form-studio' ); ?></p>
                </header>

                <div class="tsfs-setting-row is-stacked">
                    <div class="tsfs-setting-copy">
                        <strong><?php esc_html_e( 'CAPTCHA provider', 'techsolution-form-studio' ); ?></strong>
                        <span><?php esc_html_e( 'Applies to all forms.', 'techsolution-form-studio' ); ?></span>
                    </div>
                    <div class="tsfs-provider-grid" role="radiogroup" aria-label="<?php esc_attr_e( 'CAPTCHA provider', 'techsolution-form-studio' ); ?>">
                        <?php foreach ( $tsfs_providers as $tsfs_value => $tsfs_provider ) : ?>
                            <label class="tsfs-provider-card<?php echo $provider === $tsfs_value ? ' is-selected' : ''; ?>">
                                <input type="radio" name="anti_spam_provider" value="<?php echo esc_attr( $tsfs_value ); ?>" <?php checked( $provider, $tsfs_value ); ?>>
                                <span>
                                    <strong><?php echo esc_html( $tsfs_provider[0] ); ?></strong>
                                    <small><?php echo esc_html( $tsfs_provider[1] ); ?></small>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div id="tsfs-recaptcha-keys-wrap" class="tsfs-setting-subcard"<?php echo in_array( $provider, array( 'recaptcha_v2', 'recaptcha_v3' ), true ) ? '' : ' hidden'; ?>>
                    <h3><?php esc_html_e( 'Google reCAPTCHA keys', 'techsolution-form-studio' ); ?></h3>
                    <div class="tsfs-field-grid">
                        <label class="tsfs-field">
                            <span><?php esc_html_e( 'Site key', 'techsolution-form-studio' ); ?></span>
                            <input type="text" id="tsfs_recaptcha_site_key" value="<?php echo esc_attr( $sec_settings['recaptcha_site_key'] ); ?>" autocomplete="off" spellcheck="false">
                        </label>
                        <label class="tsfs-field">
                            <span><?php esc_html_e( 'Secret key', 'techsolution-form-studio' ); ?></span>
                            <input type="password" id="tsfs_recaptcha_secret_key" value="<?php echo esc_attr( $sec_settings['recaptcha_secret_key'] ); ?>" autocomplete="new-password">
                        </label>
                    </div>
                    <label class="tsfs-field tsfs-field-narrow" id="tsfs-recaptcha-v3-score-wrap"<?php echo 'recaptcha_v3' === $provider ? '' : ' hidden'; ?>>
                        <span><?php esc_html_e( 'Minimum score (0.1–1.0)', 'techsolution-form-studio' ); ?></span>
                        <input type="number" step="0.1" min="0.1" max="1.0" id="tsfs_recaptcha_v3_score" value="<?php echo esc_attr( $sec_settings['recaptcha_v3_score'] ); ?>">
                        <small><?php esc_html_e( 'Submissions scoring below this are rejected. 0.5 is a good start.', 'techsolution-form-studio' ); ?></small>
                    </label>
                </div>

                <div id="tsfs-turnstile-keys-wrap" class="tsfs-setting-subcard"<?php echo 'turnstile' === $provider ? '' : ' hidden'; ?>>
                    <h3><?php esc_html_e( 'Cloudflare Turnstile keys', 'techsolution-form-studio' ); ?></h3>
                    <div class="tsfs-field-grid">
                        <label class="tsfs-field">
                            <span><?php esc_html_e( 'Site key', 'techsolution-form-studio' ); ?></span>
                            <input type="text" id="tsfs_turnstile_site_key" value="<?php echo esc_attr( $sec_settings['turnstile_site_key'] ); ?>" autocomplete="off" spellcheck="false">
                        </label>
                        <label class="tsfs-field">
                            <span><?php esc_html_e( 'Secret key', 'techsolution-form-studio' ); ?></span>
                            <input type="password" id="tsfs_turnstile_secret_key" value="<?php echo esc_attr( $sec_settings['turnstile_secret_key'] ); ?>" autocomplete="new-password">
                        </label>
                    </div>
                </div>

                <div class="tsfs-setting-row">
                    <div class="tsfs-setting-copy">
                        <strong><label for="tsfs_enable_time_gate"><?php esc_html_e( 'Reject instant submissions', 'techsolution-form-studio' ); ?></label></strong>
                        <span><?php esc_html_e( 'Bots often submit a form the moment it loads. People need at least a couple of seconds.', 'techsolution-form-studio' ); ?></span>
                        <span class="tsfs-inline-input">
                            <label for="tsfs_time_gate_seconds"><?php esc_html_e( 'Minimum time', 'techsolution-form-studio' ); ?></label>
                            <input type="number" id="tsfs_time_gate_seconds" min="1" max="10" value="<?php echo esc_attr( $sec_settings['time_gate_seconds'] ); ?>">
                            <?php esc_html_e( 'seconds', 'techsolution-form-studio' ); ?>
                        </span>
                    </div>
                    <label class="tsfs-toggle-switch">
                        <input type="checkbox" id="tsfs_enable_time_gate" <?php checked( ! empty( $sec_settings['enable_time_gate'] ) ); ?>>
                        <span class="tsfs-slider"></span>
                    </label>
                </div>

                <p class="tsfs-setting-note">
                    <?php esc_html_e( 'reCAPTCHA (by Google) and Turnstile (by Cloudflare) are external services. When one is turned on, visitors load the provider\'s script, and their verification response and IP address are sent to that provider under its privacy policy. Mention this in your site\'s privacy policy.', 'techsolution-form-studio' ); ?>
                </p>
            </section>

            <!-- Rate limiting & blocking -->
            <section class="tsfs-hub-panel tsfs-settings-panel" id="tsfs-panel-ratelimit" hidden>
                <header class="tsfs-panel-head">
                    <h2><?php esc_html_e( 'Rate limiting & blocking', 'techsolution-form-studio' ); ?></h2>
                    <p><?php esc_html_e( 'Limit how often one visitor can submit, and reject submissions from addresses or with words you choose.', 'techsolution-form-studio' ); ?></p>
                </header>

                <div class="tsfs-setting-row">
                    <div class="tsfs-setting-copy">
                        <strong><label for="tsfs_enable_rate_limiting"><?php esc_html_e( 'Limit submissions per IP address', 'techsolution-form-studio' ); ?></label></strong>
                        <span><?php esc_html_e( 'Counts submissions across all forms. Works even when IP addresses are not stored with entries.', 'techsolution-form-studio' ); ?></span>
                        <span class="tsfs-inline-input">
                            <label for="tsfs_rate_limit_max"><?php esc_html_e( 'Allow', 'techsolution-form-studio' ); ?></label>
                            <input type="number" id="tsfs_rate_limit_max" min="1" max="100" value="<?php echo esc_attr( $sec_settings['rate_limit_max'] ); ?>">
                            <label for="tsfs_rate_limit_window"><?php esc_html_e( 'submissions every', 'techsolution-form-studio' ); ?></label>
                            <input type="number" id="tsfs_rate_limit_window" min="1" max="60" value="<?php echo esc_attr( $sec_settings['rate_limit_window'] ); ?>">
                            <?php esc_html_e( 'minutes', 'techsolution-form-studio' ); ?>
                        </span>
                    </div>
                    <label class="tsfs-toggle-switch">
                        <input type="checkbox" id="tsfs_enable_rate_limiting" <?php checked( ! empty( $sec_settings['enable_rate_limiting'] ) ); ?>>
                        <span class="tsfs-slider"></span>
                    </label>
                </div>

                <div class="tsfs-setting-row is-stacked">
                    <div class="tsfs-setting-copy">
                        <strong><label for="tsfs_blacklisted_ips"><?php esc_html_e( 'Blocked IP addresses', 'techsolution-form-studio' ); ?></label></strong>
                        <span><?php esc_html_e( 'One address or range per line, for example 203.0.113.7 or 10.0.0.0/24.', 'techsolution-form-studio' ); ?></span>
                    </div>
                    <textarea id="tsfs_blacklisted_ips" class="tsfs-code-input" rows="5" spellcheck="false"><?php echo esc_textarea( $sec_settings['blacklisted_ips'] ); ?></textarea>
                </div>

                <div class="tsfs-setting-row is-stacked">
                    <div class="tsfs-setting-copy">
                        <strong><label for="tsfs_blocked_keywords"><?php esc_html_e( 'Blocked words and phrases', 'techsolution-form-studio' ); ?></label></strong>
                        <span><?php esc_html_e( 'One per line. Submissions containing any of them are rejected.', 'techsolution-form-studio' ); ?></span>
                    </div>
                    <textarea id="tsfs_blocked_keywords" rows="5"><?php echo esc_textarea( $sec_settings['blocked_keywords'] ); ?></textarea>
                </div>
            </section>

            <!-- Uploads & exports -->
            <section class="tsfs-hub-panel tsfs-settings-panel" id="tsfs-panel-sandboxing" hidden>
                <header class="tsfs-panel-head">
                    <h2><?php esc_html_e( 'Uploads & exports', 'techsolution-form-studio' ); ?></h2>
                    <p><?php esc_html_e( 'Uploaded files are stored with random names in a folder that browsers cannot list or run scripts from.', 'techsolution-form-studio' ); ?></p>
                </header>

                <div class="tsfs-setting-row is-stacked">
                    <div class="tsfs-setting-copy">
                        <strong><?php esc_html_e( 'Upload folder protection', 'techsolution-form-studio' ); ?></strong>
                        <span><code>wp-content/uploads/techsolution-form-studio/</code></span>
                    </div>
                    <div class="tsfs-check-list">
                        <div class="tsfs-check-item <?php echo esc_attr( $has_htaccess ? 'is-ok' : 'is-missing' ); ?>">
                            <span class="dashicons <?php echo esc_attr( $has_htaccess ? 'dashicons-yes-alt' : 'dashicons-warning' ); ?>" aria-hidden="true"></span>
                            <div>
                                <strong>.htaccess</strong>
                                <span><?php echo $has_htaccess ? esc_html__( 'Blocks script execution and direct access (Apache and LiteSpeed).', 'techsolution-form-studio' ) : esc_html__( 'Missing — restore it below.', 'techsolution-form-studio' ); ?></span>
                            </div>
                        </div>
                        <div class="tsfs-check-item <?php echo esc_attr( $has_index ? 'is-ok' : 'is-missing' ); ?>">
                            <span class="dashicons <?php echo esc_attr( $has_index ? 'dashicons-yes-alt' : 'dashicons-warning' ); ?>" aria-hidden="true"></span>
                            <div>
                                <strong>index.php</strong>
                                <span><?php echo $has_index ? esc_html__( 'Prevents folder listing.', 'techsolution-form-studio' ) : esc_html__( 'Missing — restore it below.', 'techsolution-form-studio' ); ?></span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <button type="button" class="button" id="tsfs-redeploy-shields-btn">
                            <span class="dashicons dashicons-update" aria-hidden="true"></span>
                            <?php esc_html_e( 'Restore protection files', 'techsolution-form-studio' ); ?>
                        </button>
                    </div>
                </div>

                <div class="tsfs-setting-row">
                    <div class="tsfs-setting-copy">
                        <strong><label for="tsfs_enable_mime_sniffing"><?php esc_html_e( 'Check real file type', 'techsolution-form-studio' ); ?></label></strong>
                        <span><?php esc_html_e( 'Reads the contents of each upload and rejects files whose type does not match their extension.', 'techsolution-form-studio' ); ?></span>
                    </div>
                    <label class="tsfs-toggle-switch">
                        <input type="checkbox" id="tsfs_enable_mime_sniffing" <?php checked( ! empty( $sec_settings['enable_mime_sniffing'] ) ); ?>>
                        <span class="tsfs-slider"></span>
                    </label>
                </div>

                <div class="tsfs-setting-row">
                    <div class="tsfs-setting-copy">
                        <strong><label for="tsfs_enable_csv_injection_shield"><?php esc_html_e( 'Safe spreadsheet exports', 'techsolution-form-studio' ); ?></label></strong>
                        <span><?php esc_html_e( 'Prefixes cells that start with =, +, - or @ so spreadsheet apps do not run them as formulas.', 'techsolution-form-studio' ); ?></span>
                    </div>
                    <label class="tsfs-toggle-switch">
                        <input type="checkbox" id="tsfs_enable_csv_injection_shield" <?php checked( ! empty( $sec_settings['enable_csv_injection_shield'] ) ); ?>>
                        <span class="tsfs-slider"></span>
                    </label>
                </div>
            </section>

            <!-- Privacy & data -->
            <section class="tsfs-hub-panel tsfs-settings-panel" id="tsfs-panel-privacy" hidden>
                <header class="tsfs-panel-head">
                    <h2><?php esc_html_e( 'Privacy & data', 'techsolution-form-studio' ); ?></h2>
                    <p><?php esc_html_e( 'Choose what is stored with each submission. Entries are included in Tools → Export / Erase Personal Data, matched by email address.', 'techsolution-form-studio' ); ?></p>
                </header>

                <?php
                $tsfs_privacy_toggles = array(
                    'store_ip_address'         => array( __( 'Store submitter IP address', 'techsolution-form-studio' ), __( 'Shown in entries for spam review. Rate limiting works without it.', 'techsolution-form-studio' ) ),
                    'store_user_agent'         => array( __( 'Store browser and device', 'techsolution-form-studio' ), __( 'Records the browser user agent that sent each submission.', 'techsolution-form-studio' ) ),
                    'delete_data_on_uninstall' => array( __( 'Delete all data when the plugin is deleted', 'techsolution-form-studio' ), __( 'Removes forms, entries, uploaded files and settings when you delete the plugin from the Plugins screen.', 'techsolution-form-studio' ) ),
                );
                foreach ( $tsfs_privacy_toggles as $tsfs_key => $tsfs_toggle ) :
                    ?>
                    <div class="tsfs-setting-row">
                        <div class="tsfs-setting-copy">
                            <strong><label for="tsfs_<?php echo esc_attr( $tsfs_key ); ?>"><?php echo esc_html( $tsfs_toggle[0] ); ?></label></strong>
                            <span><?php echo esc_html( $tsfs_toggle[1] ); ?></span>
                        </div>
                        <label class="tsfs-toggle-switch">
                            <input type="checkbox" id="tsfs_<?php echo esc_attr( $tsfs_key ); ?>" <?php checked( ! empty( $sec_settings[ $tsfs_key ] ) ); ?>>
                            <span class="tsfs-slider"></span>
                        </label>
                    </div>
                <?php endforeach; ?>

                <div class="tsfs-setting-row">
                    <div class="tsfs-setting-copy">
                        <strong><label for="tsfs_entry_retention_days"><?php esc_html_e( 'Delete old entries automatically', 'techsolution-form-studio' ); ?></label></strong>
                        <span><?php esc_html_e( 'Runs once a day. Enter 0 to keep entries until you delete them.', 'techsolution-form-studio' ); ?></span>
                    </div>
                    <span class="tsfs-inline-input">
                        <input type="number" id="tsfs_entry_retention_days" min="0" max="3650" value="<?php echo esc_attr( $sec_settings['entry_retention_days'] ); ?>">
                        <?php esc_html_e( 'days', 'techsolution-form-studio' ); ?>
                    </span>
                </div>
            </section>

            <div class="tsfs-save-bar">
                <span><?php esc_html_e( 'Changes apply to all forms as soon as you save.', 'techsolution-form-studio' ); ?></span>
                <button type="button" class="button button-primary" id="tsfs-save-security-settings-btn">
                    <span class="tsfs-btn-text"><?php esc_html_e( 'Save settings', 'techsolution-form-studio' ); ?></span>
                    <span class="spinner" aria-hidden="true"></span>
                </button>
            </div>

            <?php if ( ! $has_pro ) : ?>
                <aside class="tsfs-addon-note">
                    <div>
                        <strong><?php esc_html_e( 'Need more field types?', 'techsolution-form-studio' ); ?></strong>
                        <p><?php esc_html_e( 'TechSolution Form Studio Pro is a separate add-on plugin that adds complete address (country, province and city lists), international phone, digital signature, calculation, payment details and admission application fields. Everything in this plugin works without it.', 'techsolution-form-studio' ); ?></p>
                    </div>
                    <a class="button" href="<?php echo esc_url( TSFS_PRO_URL ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Learn about the Pro add-on', 'techsolution-form-studio' ); ?></a>
                </aside>
            <?php endif; ?>
        </div>
    </div>
</div>
