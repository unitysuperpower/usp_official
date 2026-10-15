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

$sec_settings = TSFS_Security::get_settings();
$has_pro      = defined( 'TSFS_PRO_VERSION' );
$upload_dir   = wp_upload_dir();
$sandbox_dir  = $upload_dir['basedir'] . '/techsolution-form-studio';
$has_htaccess = file_exists( $sandbox_dir . '/.htaccess' );
$has_index    = file_exists( $sandbox_dir . '/index.php' );
?>

<div class="wrap tsfs-admin-wrap tsfs-security-hub-wrap">
    <!-- Header Hero Banner -->
    <div class="tsfs-page-header tsfs-security-header-banner" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:24px 28px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.05); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div style="display:flex; align-items:center; gap:16px;">
            <div style="width:48px; height:48px; border-radius:12px; background:linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); display:flex; align-items:center; justify-content:center; box-shadow:0 4px 10px rgba(79, 70, 229, 0.25); flex-shrink:0;">
                <span class="dashicons dashicons-shield-alt" style="color:#ffffff; font-size:26px; width:26px; height:26px;"></span>
            </div>
            <div>
                <h1 style="margin:0; font-size:22px; font-weight:800; color:#0f172a; line-height:1.2;">
                    <?php esc_html_e( 'Settings', 'techsolution-form-studio' ); ?>
                </h1>
                <p style="margin:4px 0 0 0; font-size:13.5px; color:#64748b;">
                    <?php esc_html_e( 'Configure spam protection, rate limiting, upload hardening and privacy for all forms.', 'techsolution-form-studio' ); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Security & Pro KPI Status Cards -->
    <div class="tsfs-security-metrics-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(230px, 1fr)); gap:16px; margin-bottom:24px;">
        <!-- Card 1: Privacy -->
        <div class="tsfs-sec-metric-card" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:10px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.04); position:relative; overflow:hidden;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                <span style="font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#64748b;">
                    <?php esc_html_e( 'Privacy', 'techsolution-form-studio' ); ?>
                </span>
                <span class="dashicons dashicons-privacy" style="color:#7c3aed; font-size:20px; width:20px; height:20px;"></span>
            </div>
            <div style="font-size:20px; font-weight:800; color:#1e293b; line-height:1.2;">
                <?php
                echo $sec_settings['entry_retention_days']
                    /* translators: %d: number of days */
                    ? esc_html( sprintf( __( 'Entries kept %d days', 'techsolution-form-studio' ), absint( $sec_settings['entry_retention_days'] ) ) )
                    : esc_html__( 'Entries kept until deleted', 'techsolution-form-studio' );
                ?>
            </div>
            <div style="font-size:12px; color:#64748b; margin-top:6px;">
                <?php echo ! empty( $sec_settings['store_ip_address'] ) ? esc_html__( 'Submitter IP addresses are stored', 'techsolution-form-studio' ) : esc_html__( 'Submitter IP addresses are not stored', 'techsolution-form-studio' ); ?>
            </div>
            <div style="position:absolute; bottom:0; left:0; right:0; height:3px; background:#7c3aed;"></div>
        </div>

        <!-- Card 2: Bot Armor -->
        <div class="tsfs-sec-metric-card" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:10px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.04); position:relative; overflow:hidden;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                <span style="font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#64748b;">
                    <?php esc_html_e( 'Bot & Spam Armor', 'techsolution-form-studio' ); ?>
                </span>
                <span class="dashicons dashicons-shield" style="color:#2563eb; font-size:20px; width:20px; height:20px;"></span>
            </div>
            <div style="font-size:20px; font-weight:800; color:#1e293b; line-height:1.2;">
                <?php
                if ( 'none' !== $sec_settings['anti_spam_provider'] ) {
                    echo esc_html( ucfirst( str_replace( '_', ' ', $sec_settings['anti_spam_provider'] ) ) );
                } else {
                    echo esc_html__( 'Honeypot Active', 'techsolution-form-studio' );
                }
                ?>
            </div>
            <div style="font-size:12px; color:#64748b; margin-top:6px;">
                <?php
                /* translators: %d: seconds */
                echo ! empty( $sec_settings['enable_time_gate'] ) ? esc_html( sprintf( __( 'Time-gate active (%ds minimum)', 'techsolution-form-studio' ), absint( $sec_settings['time_gate_seconds'] ) ) ) : esc_html__( 'Zero-config honeypot trap enabled', 'techsolution-form-studio' );
                ?>
            </div>
            <div style="position:absolute; bottom:0; left:0; right:0; height:3px; background:#2563eb;"></div>
        </div>

        <!-- Card 3: Rate Limiting -->
        <div class="tsfs-sec-metric-card" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:10px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.04); position:relative; overflow:hidden;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                <span style="font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#64748b;">
                    <?php esc_html_e( 'DDoS Rate Limiter', 'techsolution-form-studio' ); ?>
                </span>
                <span class="dashicons dashicons-dashboard" style="color:<?php echo ! empty( $sec_settings['enable_rate_limiting'] ) ? '#10b981' : '#94a3b8'; ?>; font-size:20px; width:20px; height:20px;"></span>
            </div>
            <div style="font-size:20px; font-weight:800; color:<?php echo ! empty( $sec_settings['enable_rate_limiting'] ) ? '#059669' : '#64748b'; ?>; line-height:1.2;">
                <?php echo ! empty( $sec_settings['enable_rate_limiting'] ) ? esc_html__( 'Armed & Throttling', 'techsolution-form-studio' ) : esc_html__( 'Disabled', 'techsolution-form-studio' ); ?>
            </div>
            <div style="font-size:12px; color:#64748b; margin-top:6px;">
                <?php
                /* translators: 1: maximum submissions, 2: minutes */
                printf( esc_html__( 'Max %1$d submissions per %2$d mins per IP', 'techsolution-form-studio' ), absint( $sec_settings['rate_limit_max'] ), absint( $sec_settings['rate_limit_window'] ) );
                ?>
            </div>
            <div style="position:absolute; bottom:0; left:0; right:0; height:3px; background:<?php echo ! empty( $sec_settings['enable_rate_limiting'] ) ? '#10b981' : '#cbd5e1'; ?>;"></div>
        </div>

        <!-- Card 4: Upload Sandbox -->
        <div class="tsfs-sec-metric-card" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:10px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.04); position:relative; overflow:hidden;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                <span style="font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#64748b;">
                    <?php esc_html_e( 'Upload Sandboxing', 'techsolution-form-studio' ); ?>
                </span>
                <span class="dashicons dashicons-lock" style="color:<?php echo $has_htaccess ? '#10b981' : '#ef4444'; ?>; font-size:20px; width:20px; height:20px;"></span>
            </div>
            <div style="font-size:20px; font-weight:800; color:<?php echo $has_htaccess ? '#059669' : '#dc2626'; ?>; line-height:1.2;">
                <?php echo $has_htaccess ? esc_html__( '.htaccess Protected', 'techsolution-form-studio' ) : esc_html__( 'Unprotected', 'techsolution-form-studio' ); ?>
            </div>
            <div style="font-size:12px; color:#64748b; margin-top:6px;">
                <?php esc_html_e( 'PHP script execution blocked in uploads dir', 'techsolution-form-studio' ); ?>
            </div>
            <div style="position:absolute; bottom:0; left:0; right:0; height:3px; background:<?php echo $has_htaccess ? '#10b981' : '#ef4444'; ?>;"></div>
        </div>
    </div>

    <!-- Hub Container -->
    <div class="tsfs-settings-card" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,0.05); overflow:hidden;">
        <!-- Tab Navigation Bar -->
        <div class="tsfs-hub-tabs" style="display:flex; border-bottom:1px solid #e2e8f0; background:#f8fafc; overflow-x:auto;">
            <button type="button" class="tsfs-hub-tab active" data-tab="antispam" style="padding:16px 24px; font-size:13.5px; font-weight:700; color:#4f46e5; border:none; background:transparent; border-bottom:2px solid #4f46e5; cursor:pointer; display:flex; align-items:center; gap:8px; white-space:nowrap;">
                <span class="dashicons dashicons-shield" style="font-size:17px; width:17px; height:17px;"></span>
                <?php esc_html_e( 'Anti-Spam & Bot Shield', 'techsolution-form-studio' ); ?>
            </button>
            <button type="button" class="tsfs-hub-tab" data-tab="ratelimit" style="padding:16px 24px; font-size:13.5px; font-weight:700; color:#64748b; border:none; background:transparent; border-bottom:2px solid transparent; cursor:pointer; display:flex; align-items:center; gap:8px; white-space:nowrap;">
                <span class="dashicons dashicons-dashboard" style="font-size:17px; width:17px; height:17px;"></span>
                <?php esc_html_e( 'Rate Limiting & DDoS', 'techsolution-form-studio' ); ?>
            </button>
            <button type="button" class="tsfs-hub-tab" data-tab="sandboxing" style="padding:16px 24px; font-size:13.5px; font-weight:700; color:#64748b; border:none; background:transparent; border-bottom:2px solid transparent; cursor:pointer; display:flex; align-items:center; gap:8px; white-space:nowrap;">
                <span class="dashicons dashicons-lock" style="font-size:17px; width:17px; height:17px;"></span>
                <?php esc_html_e( 'File & Data Hardening', 'techsolution-form-studio' ); ?>
            </button>
            <button type="button" class="tsfs-hub-tab" data-tab="privacy" style="padding:16px 24px; font-size:13.5px; font-weight:700; color:#64748b; border:none; background:transparent; border-bottom:2px solid transparent; cursor:pointer; display:flex; align-items:center; gap:8px; white-space:nowrap;">
                <span class="dashicons dashicons-privacy" style="font-size:17px; width:17px; height:17px;"></span>
                <?php esc_html_e( 'Privacy & Data', 'techsolution-form-studio' ); ?>
            </button>
        </div>

        <div class="tsfs-hub-tab-contents" style="padding:32px;">
            <!-- Tab 2: Anti-Spam & Bot Shield -->
            <div class="tsfs-hub-panel active" id="tsfs-panel-antispam">
                <div style="max-width:820px;">
                    <h3 style="font-size:18px; font-weight:800; margin:0 0 6px 0; color:#0f172a;">
                        <?php esc_html_e( 'Anti-Spam & Automated Bot Armor', 'techsolution-form-studio' ); ?>
                    </h3>
                    <p style="color:#64748b; font-size:13.5px; margin-bottom:24px;">
                        <?php esc_html_e( 'Safeguard your website against spambots, automated form bombing, and malicious payload injections.', 'techsolution-form-studio' ); ?>
                    </p>

                    <!-- Provider Selection -->
                    <div style="margin-bottom:24px;">
                        <label style="display:block; font-size:13px; font-weight:700; color:#334155; margin-bottom:8px;">
                            <?php esc_html_e( 'Select Active Anti-Spam Provider', 'techsolution-form-studio' ); ?>
                        </label>
                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px;">
                            <label class="tsfs-provider-card" style="border:1.5px solid <?php echo 'none' === $sec_settings['anti_spam_provider'] ? '#4f46e5' : '#cbd5e1'; ?>; padding:14px; border-radius:8px; cursor:pointer; background:<?php echo 'none' === $sec_settings['anti_spam_provider'] ? '#eef2ff' : '#fff'; ?>;">
                                <input type="radio" name="anti_spam_provider" value="none" <?php checked( $sec_settings['anti_spam_provider'], 'none' ); ?> style="margin-right:8px;">
                                <div>
                                    <strong style="display:block; font-size:13px; color:#0f172a;"><?php esc_html_e( 'None (Honeypot Only)', 'techsolution-form-studio' ); ?></strong>
                                    <span style="font-size:11px; color:#64748b;"><?php esc_html_e( 'Zero setup required', 'techsolution-form-studio' ); ?></span>
                                </div>
                            </label>
                            <label class="tsfs-provider-card" style="border:1.5px solid <?php echo 'recaptcha_v2' === $sec_settings['anti_spam_provider'] ? '#4f46e5' : '#cbd5e1'; ?>; padding:14px; border-radius:8px; cursor:pointer; background:<?php echo 'recaptcha_v2' === $sec_settings['anti_spam_provider'] ? '#eef2ff' : '#fff'; ?>;">
                                <input type="radio" name="anti_spam_provider" value="recaptcha_v2" <?php checked( $sec_settings['anti_spam_provider'], 'recaptcha_v2' ); ?> style="margin-right:8px;">
                                <div>
                                    <strong style="display:block; font-size:13px; color:#0f172a;"><?php esc_html_e( 'Google reCAPTCHA v2', 'techsolution-form-studio' ); ?></strong>
                                    <span style="font-size:11px; color:#64748b;"><?php esc_html_e( '"I\'m not a robot" checkbox', 'techsolution-form-studio' ); ?></span>
                                </div>
                            </label>
                            <label class="tsfs-provider-card" style="border:1.5px solid <?php echo 'recaptcha_v3' === $sec_settings['anti_spam_provider'] ? '#4f46e5' : '#cbd5e1'; ?>; padding:14px; border-radius:8px; cursor:pointer; background:<?php echo 'recaptcha_v3' === $sec_settings['anti_spam_provider'] ? '#eef2ff' : '#fff'; ?>;">
                                <input type="radio" name="anti_spam_provider" value="recaptcha_v3" <?php checked( $sec_settings['anti_spam_provider'], 'recaptcha_v3' ); ?> style="margin-right:8px;">
                                <div>
                                    <strong style="display:block; font-size:13px; color:#0f172a;"><?php esc_html_e( 'reCAPTCHA v3', 'techsolution-form-studio' ); ?></strong>
                                    <span style="font-size:11px; color:#64748b;"><?php esc_html_e( 'Invisible background score', 'techsolution-form-studio' ); ?></span>
                                </div>
                            </label>
                            <label class="tsfs-provider-card" style="border:1.5px solid <?php echo 'turnstile' === $sec_settings['anti_spam_provider'] ? '#4f46e5' : '#cbd5e1'; ?>; padding:14px; border-radius:8px; cursor:pointer; background:<?php echo 'turnstile' === $sec_settings['anti_spam_provider'] ? '#eef2ff' : '#fff'; ?>;">
                                <input type="radio" name="anti_spam_provider" value="turnstile" <?php checked( $sec_settings['anti_spam_provider'], 'turnstile' ); ?> style="margin-right:8px;">
                                <div>
                                    <strong style="display:block; font-size:13px; color:#0f172a;"><?php esc_html_e( 'Cloudflare Turnstile', 'techsolution-form-studio' ); ?></strong>
                                    <span style="font-size:11px; color:#64748b;"><?php esc_html_e( 'Privacy-first smart challenge', 'techsolution-form-studio' ); ?></span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Google reCAPTCHA Keys -->
                    <div id="tsfs-recaptcha-keys-wrap" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:22px; margin-bottom:20px; <?php echo in_array( $sec_settings['anti_spam_provider'], array( 'recaptcha_v2', 'recaptcha_v3' ), true ) ? '' : 'display:none;'; ?>">
                        <h4 style="margin:0 0 14px 0; font-size:14px; font-weight:700; color:#1e293b;"><?php esc_html_e( 'Google reCAPTCHA API Keys', 'techsolution-form-studio' ); ?></h4>
                        <div style="margin-bottom:14px;">
                            <label for="tsfs_recaptcha_site_key" style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:4px;"><?php esc_html_e( 'reCAPTCHA Site Key', 'techsolution-form-studio' ); ?></label>
                            <input type="text" id="tsfs_recaptcha_site_key" value="<?php echo esc_attr( $sec_settings['recaptcha_site_key'] ); ?>" class="regular-text" style="width:100%; height:38px; border-radius:6px; border:1px solid #cbd5e1; font-family:monospace;">
                        </div>
                        <div style="margin-bottom:14px;">
                            <label for="tsfs_recaptcha_secret_key" style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:4px;"><?php esc_html_e( 'reCAPTCHA Secret Key', 'techsolution-form-studio' ); ?></label>
                            <input type="password" id="tsfs_recaptcha_secret_key" value="<?php echo esc_attr( $sec_settings['recaptcha_secret_key'] ); ?>" class="regular-text" style="width:100%; height:38px; border-radius:6px; border:1px solid #cbd5e1; font-family:monospace;">
                        </div>
                        <div id="tsfs-recaptcha-v3-score-wrap" style="<?php echo 'recaptcha_v3' === $sec_settings['anti_spam_provider'] ? '' : 'display:none;'; ?>">
                            <label for="tsfs_recaptcha_v3_score" style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:4px;"><?php esc_html_e( 'v3 Minimum Score Threshold (0.1 to 1.0, Default: 0.5)', 'techsolution-form-studio' ); ?></label>
                            <input type="number" step="0.1" min="0.1" max="1.0" id="tsfs_recaptcha_v3_score" value="<?php echo esc_attr( $sec_settings['recaptcha_v3_score'] ); ?>" style="width:120px; height:38px; border-radius:6px; border:1px solid #cbd5e1;">
                        </div>
                    </div>

                    <!-- Cloudflare Turnstile Keys -->
                    <div id="tsfs-turnstile-keys-wrap" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:22px; margin-bottom:20px; <?php echo 'turnstile' === $sec_settings['anti_spam_provider'] ? '' : 'display:none;'; ?>">
                        <h4 style="margin:0 0 14px 0; font-size:14px; font-weight:700; color:#1e293b;"><?php esc_html_e( 'Cloudflare Turnstile API Keys', 'techsolution-form-studio' ); ?></h4>
                        <div style="margin-bottom:14px;">
                            <label for="tsfs_turnstile_site_key" style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:4px;"><?php esc_html_e( 'Turnstile Site Key', 'techsolution-form-studio' ); ?></label>
                            <input type="text" id="tsfs_turnstile_site_key" value="<?php echo esc_attr( $sec_settings['turnstile_site_key'] ); ?>" class="regular-text" style="width:100%; height:38px; border-radius:6px; border:1px solid #cbd5e1; font-family:monospace;">
                        </div>
                        <div>
                            <label for="tsfs_turnstile_secret_key" style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:4px;"><?php esc_html_e( 'Turnstile Secret Key', 'techsolution-form-studio' ); ?></label>
                            <input type="password" id="tsfs_turnstile_secret_key" value="<?php echo esc_attr( $sec_settings['turnstile_secret_key'] ); ?>" class="regular-text" style="width:100%; height:38px; border-radius:6px; border:1px solid #cbd5e1; font-family:monospace;">
                        </div>
                    </div>

                    <!-- Time-Gate Bot Trap -->
                    <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:10px; padding:20px; margin-bottom:20px;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <strong style="font-size:14px; color:#1e293b; display:block;"><?php esc_html_e( 'Time-Gate Bot Trap', 'techsolution-form-studio' ); ?></strong>
                                <span style="font-size:12.5px; color:#64748b;"><?php esc_html_e( 'Rejects automated bot submissions that complete the entire form in less than humanly possible time.', 'techsolution-form-studio' ); ?></span>
                            </div>
                            <label class="tsfs-toggle-switch">
                                <input type="checkbox" id="tsfs_enable_time_gate" <?php checked( ! empty( $sec_settings['enable_time_gate'] ) ); ?>>
                                <span class="tsfs-slider"></span>
                            </label>
                        </div>
                        <div style="margin-top:14px; padding-top:14px; border-top:1px solid #f1f5f9; display:flex; align-items:center; gap:10px;">
                            <label for="tsfs_time_gate_seconds" style="font-size:13px; color:#475569; font-weight:600;"><?php esc_html_e( 'Minimum Seconds Required:', 'techsolution-form-studio' ); ?></label>
                            <input type="number" id="tsfs_time_gate_seconds" min="1" max="10" value="<?php echo esc_attr( $sec_settings['time_gate_seconds'] ); ?>" style="width:75px; height:36px; border-radius:6px; border:1px solid #cbd5e1;">
                            <span style="font-size:12px; color:#94a3b8;"><?php esc_html_e( 'seconds (recommended: 2s)', 'techsolution-form-studio' ); ?></span>
                        </div>
                    </div>

                    <p class="description" style="margin-bottom:20px;">
                        <?php
                        printf(
                            /* translators: 1: Google privacy policy URL, 2: Cloudflare privacy policy URL */
                            wp_kses( __( 'reCAPTCHA and Turnstile are external services. When enabled, visitors load the provider\'s script and their verification response and IP address are sent to the provider. See the <a href="%1$s" target="_blank" rel="noopener noreferrer">Google Privacy Policy</a> and the <a href="%2$s" target="_blank" rel="noopener noreferrer">Cloudflare Privacy Policy</a>.', 'techsolution-form-studio' ), array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) ) ),
                            'https://policies.google.com/privacy',
                            'https://www.cloudflare.com/privacypolicy/'
                        );
                        ?>
                    </p>

                    <!-- Invisible Honeypot Notice -->
                    <div style="padding:14px 18px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; display:flex; align-items:center; gap:12px;">
                        <span class="dashicons dashicons-yes-alt" style="color:#16a34a; font-size:20px; width:20px; height:20px;"></span>
                        <span style="font-size:13px; color:#15803d; font-weight:600;">
                            <?php esc_html_e( 'Invisible Anti-Spam Honeypot is actively armed on all public forms automatically.', 'techsolution-form-studio' ); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Rate Limiting & DDoS Defense -->
            <div class="tsfs-hub-panel" id="tsfs-panel-ratelimit" style="display:none;">
                <div style="max-width:820px;">
                    <h3 style="font-size:18px; font-weight:800; margin:0 0 6px 0; color:#0f172a;">
                        <?php esc_html_e( 'IP Rate Limiting & Anti-Flood Shield', 'techsolution-form-studio' ); ?>
                    </h3>
                    <p style="color:#64748b; font-size:13.5px; margin-bottom:24px;">
                        <?php esc_html_e( 'Prevent brute-force form submissions, automated spam bombing, and server resource exhaustion.', 'techsolution-form-studio' ); ?>
                    </p>

                    <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:10px; padding:20px; margin-bottom:24px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                            <div>
                                <strong style="font-size:14px; color:#1e293b; display:block;"><?php esc_html_e( 'Enable Client IP Rate Limiting', 'techsolution-form-studio' ); ?></strong>
                                <span style="font-size:12.5px; color:#64748b;"><?php esc_html_e( 'Throttle repeat submissions from the same IP address across forms.', 'techsolution-form-studio' ); ?></span>
                            </div>
                            <label class="tsfs-toggle-switch">
                                <input type="checkbox" id="tsfs_enable_rate_limiting" <?php checked( ! empty( $sec_settings['enable_rate_limiting'] ) ); ?>>
                                <span class="tsfs-slider"></span>
                            </label>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; padding-top:16px; border-top:1px solid #f1f5f9;">
                            <div>
                                <label for="tsfs_rate_limit_max" style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:4px;">
                                    <?php esc_html_e( 'Maximum Submissions Allowed', 'techsolution-form-studio' ); ?>
                                </label>
                                <input type="number" id="tsfs_rate_limit_max" min="1" max="100" value="<?php echo esc_attr( $sec_settings['rate_limit_max'] ); ?>" style="width:100%; height:38px; border-radius:6px; border:1px solid #cbd5e1;">
                            </div>
                            <div>
                                <label for="tsfs_rate_limit_window" style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:4px;">
                                    <?php esc_html_e( 'Time Window (Minutes)', 'techsolution-form-studio' ); ?>
                                </label>
                                <input type="number" id="tsfs_rate_limit_window" min="1" max="60" value="<?php echo esc_attr( $sec_settings['rate_limit_window'] ); ?>" style="width:100%; height:38px; border-radius:6px; border:1px solid #cbd5e1;">
                            </div>
                        </div>
                    </div>

                    <!-- Blacklisted IPs -->
                    <div style="margin-bottom:24px;">
                        <label for="tsfs_blacklisted_ips" style="display:block; font-size:13px; font-weight:700; color:#334155; margin-bottom:4px;">
                            <?php esc_html_e( 'Blacklisted IP Addresses / CIDR Ranges', 'techsolution-form-studio' ); ?>
                        </label>
                        <p style="font-size:12px; color:#64748b; margin-bottom:8px;">
                            <?php esc_html_e( 'Enter one IP or subnet per line (e.g. 192.168.1.50 or 10.0.0.0/24). Submissions from these IPs will be rejected immediately.', 'techsolution-form-studio' ); ?>
                        </p>
                        <textarea id="tsfs_blacklisted_ips" rows="4" style="width:100%; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size:13px; border-radius:6px; border:1.5px solid #cbd5e1; padding:8px 12px;"><?php echo esc_textarea( $sec_settings['blacklisted_ips'] ); ?></textarea>
                    </div>

                    <!-- Blocked Keywords -->
                    <div style="margin-bottom:24px;">
                        <label for="tsfs_blocked_keywords" style="display:block; font-size:13px; font-weight:700; color:#334155; margin-bottom:4px;">
                            <?php esc_html_e( 'Blocked Spam Keywords & Phishing Patterns', 'techsolution-form-studio' ); ?>
                        </label>
                        <p style="font-size:12px; color:#64748b; margin-bottom:8px;">
                            <?php esc_html_e( 'Enter words, domains, or phrases (one per line) that trigger automatic submission rejection (e.g. casino, crypto-airdrop, phishing).', 'techsolution-form-studio' ); ?>
                        </p>
                        <textarea id="tsfs_blocked_keywords" rows="4" style="width:100%; font-size:13px; border-radius:6px; border:1.5px solid #cbd5e1; padding:8px 12px;"><?php echo esc_textarea( $sec_settings['blocked_keywords'] ); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Tab 4: File Sandboxing & Data Hardening -->
            <div class="tsfs-hub-panel" id="tsfs-panel-sandboxing" style="display:none;">
                <div style="max-width:820px;">
                    <h3 style="font-size:18px; font-weight:800; margin:0 0 6px 0; color:#0f172a;">
                        <?php esc_html_e( 'Upload Sandboxing & Data Security Armor', 'techsolution-form-studio' ); ?>
                    </h3>
                    <p style="color:#64748b; font-size:13.5px; margin-bottom:24px;">
                        <?php esc_html_e( 'Prevent server-side code execution, malicious file uploads, and spreadsheet formula injection exploits.', 'techsolution-form-studio' ); ?>
                    </p>

                    <!-- Upload Directory Shield Card -->
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:22px; margin-bottom:24px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; flex-wrap:wrap; gap:12px;">
                            <div>
                                <strong style="font-size:14px; color:#1e293b; display:block;"><?php esc_html_e( 'Upload Directory Sandbox (.htaccess)', 'techsolution-form-studio' ); ?></strong>
                                <span style="font-size:12px; color:#64748b;">
                                    <?php printf( esc_html__( 'Target Folder: %s', 'techsolution-form-studio' ), '<code style="background:#e2e8f0; padding:2px 6px; border-radius:4px;">wp-content/uploads/techsolution-form-studio/</code>' ); ?>
                                </span>
                            </div>
                            <button type="button" class="button button-secondary" id="tsfs-redeploy-shields-btn" style="font-weight:600; border-radius:6px; height:34px; display:inline-flex; align-items:center; gap:5px;">
                                <span class="dashicons dashicons-update" style="font-size:15px; width:15px; height:15px;"></span>
                                <?php esc_html_e( 'Re-deploy Shields', 'techsolution-form-studio' ); ?>
                            </button>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-top:14px;">
                            <div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:14px; display:flex; align-items:center; gap:10px;">
                                <span class="dashicons <?php echo $has_htaccess ? 'dashicons-yes' : 'dashicons-no'; ?>" style="color:<?php echo $has_htaccess ? '#16a34a' : '#dc2626'; ?>; font-size:22px; width:22px; height:22px;"></span>
                                <div>
                                    <strong style="font-size:13px; color:#0f172a; display:block;"><?php echo $has_htaccess ? esc_html__( '.htaccess Script Blocker', 'techsolution-form-studio' ) : esc_html__( '.htaccess Missing', 'techsolution-form-studio' ); ?></strong>
                                    <span style="font-size:11.5px; color:#64748b;"><?php echo $has_htaccess ? esc_html__( 'Active & Blocking script execution', 'techsolution-form-studio' ) : esc_html__( 'Click Re-deploy to generate', 'techsolution-form-studio' ); ?></span>
                                </div>
                            </div>
                            <div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:14px; display:flex; align-items:center; gap:10px;">
                                <span class="dashicons <?php echo $has_index ? 'dashicons-yes' : 'dashicons-no'; ?>" style="color:<?php echo $has_index ? '#16a34a' : '#dc2626'; ?>; font-size:22px; width:22px; height:22px;"></span>
                                <div>
                                    <strong style="font-size:13px; color:#0f172a; display:block;"><?php echo $has_index ? esc_html__( 'Directory Indexing Blocker', 'techsolution-form-studio' ) : esc_html__( 'index.php Missing', 'techsolution-form-studio' ); ?></strong>
                                    <span style="font-size:11.5px; color:#64748b;"><?php echo $has_index ? esc_html__( 'index.php silencer active', 'techsolution-form-studio' ) : esc_html__( 'Click Re-deploy to generate', 'techsolution-form-studio' ); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MIME Sniffing -->
                    <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:10px; padding:20px; margin-bottom:16px;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <strong style="font-size:14px; color:#1e293b; display:block;"><?php esc_html_e( 'Strict MIME Type Verification (finfo_file)', 'techsolution-form-studio' ); ?></strong>
                                <span style="font-size:12.5px; color:#64748b;"><?php esc_html_e( 'Inspects real binary contents of uploaded files to reject disguised PHP/shell scripts.', 'techsolution-form-studio' ); ?></span>
                            </div>
                            <label class="tsfs-toggle-switch">
                                <input type="checkbox" id="tsfs_enable_mime_sniffing" <?php checked( ! empty( $sec_settings['enable_mime_sniffing'] ) ); ?>>
                                <span class="tsfs-slider"></span>
                            </label>
                        </div>
                    </div>

                    <!-- CSV Formula Injection Shield -->
                    <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:10px; padding:20px; margin-bottom:16px;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <strong style="font-size:14px; color:#1e293b; display:block;"><?php esc_html_e( 'CSV / Excel Formula Injection Shield', 'techsolution-form-studio' ); ?></strong>
                                <span style="font-size:12.5px; color:#64748b;"><?php esc_html_e( 'Neutralizes cells starting with =, +, -, or @ when exporting submissions to prevent spreadsheet DDE execution.', 'techsolution-form-studio' ); ?></span>
                            </div>
                            <label class="tsfs-toggle-switch">
                                <input type="checkbox" id="tsfs_enable_csv_injection_shield" <?php checked( ! empty( $sec_settings['enable_csv_injection_shield'] ) ); ?>>
                                <span class="tsfs-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 5: Privacy & Data -->
            <div class="tsfs-hub-panel" id="tsfs-panel-privacy" style="display:none;">
                <div style="max-width:820px;">
                    <h3 style="font-size:18px; font-weight:800; margin:0 0 6px 0; color:#0f172a;">
                        <?php esc_html_e( 'Privacy & Data Retention', 'techsolution-form-studio' ); ?>
                    </h3>
                    <p style="color:#64748b; font-size:13.5px; margin-bottom:24px;">
                        <?php esc_html_e( 'Control what is stored with each submission. Submissions are included in the WordPress personal data export and erasure tools (Tools → Export / Erase Personal Data), matched by email address.', 'techsolution-form-studio' ); ?>
                    </p>

                    <?php
                    $tsfs_privacy_toggles = array(
                        'store_ip_address'         => array( __( 'Store submitter IP address', 'techsolution-form-studio' ), __( 'Used for spam review and shown in entries. Rate limiting works without storing it.', 'techsolution-form-studio' ) ),
                        'store_user_agent'         => array( __( 'Store browser user agent', 'techsolution-form-studio' ), __( 'Records the browser and device that sent each submission.', 'techsolution-form-studio' ) ),
                        'delete_data_on_uninstall' => array( __( 'Delete all data when the plugin is deleted', 'techsolution-form-studio' ), __( 'Removes forms, entries, uploaded files and settings when you delete the plugin from the Plugins screen.', 'techsolution-form-studio' ) ),
                    );
                    foreach ( $tsfs_privacy_toggles as $tsfs_key => $tsfs_toggle ) :
                        ?>
                        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:10px; padding:20px; margin-bottom:16px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; gap:16px;">
                                <div>
                                    <strong style="font-size:14px; color:#1e293b; display:block;"><?php echo esc_html( $tsfs_toggle[0] ); ?></strong>
                                    <span style="font-size:12.5px; color:#64748b;"><?php echo esc_html( $tsfs_toggle[1] ); ?></span>
                                </div>
                                <label class="tsfs-toggle-switch">
                                    <input type="checkbox" id="tsfs_<?php echo esc_attr( $tsfs_key ); ?>" <?php checked( ! empty( $sec_settings[ $tsfs_key ] ) ); ?>>
                                    <span class="tsfs-slider"></span>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:10px; padding:20px; margin-bottom:16px;">
                        <label for="tsfs_entry_retention_days" style="display:block; font-size:14px; font-weight:700; color:#1e293b;"><?php esc_html_e( 'Automatically delete entries older than', 'techsolution-form-studio' ); ?></label>
                        <span style="font-size:12.5px; color:#64748b; display:block; margin-bottom:10px;"><?php esc_html_e( 'Runs once a day. Enter 0 to keep entries until you delete them.', 'techsolution-form-studio' ); ?></span>
                        <input type="number" id="tsfs_entry_retention_days" min="0" max="3650" value="<?php echo esc_attr( $sec_settings['entry_retention_days'] ); ?>" style="width:110px; height:38px; border-radius:6px; border:1px solid #cbd5e1;">
                        <span style="font-size:12px; color:#94a3b8;"><?php esc_html_e( 'days', 'techsolution-form-studio' ); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sticky Save Footer Bar -->
        <div style="padding:18px 32px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
            <div style="font-size:13px; color:#64748b; display:flex; align-items:center; gap:6px;">
                <span class="dashicons dashicons-shield-alt" style="color:#059669; font-size:18px; width:18px; height:18px;"></span>
                <span><?php esc_html_e( 'Settings apply immediately to all forms.', 'techsolution-form-studio' ); ?></span>
            </div>
            <button type="button" class="button button-primary button-hero" id="tsfs-save-security-settings-btn" style="background:#4f46e5; border-color:#4338ca; font-weight:700; padding:0 28px; height:44px; display:inline-flex; align-items:center; gap:8px; border-radius:8px;">
                <span class="dashicons dashicons-saved" style="font-size:18px; width:18px; height:18px;"></span>
                <span class="tsfs-btn-text"><?php esc_html_e( 'Save Settings', 'techsolution-form-studio' ); ?></span>
                <span class="spinner" style="float:none; margin:0;" aria-hidden="true"></span>
            </button>
        </div>
    </div>
<?php if ( ! $has_pro ) : ?>
    <div class="tsfs-settings-card" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:24px 28px; margin-top:20px;">
        <h2 style="margin:0 0 6px; font-size:16px;"><?php esc_html_e( 'Need more field types?', 'techsolution-form-studio' ); ?></h2>
        <p style="margin:0 0 12px; color:#64748b;"><?php esc_html_e( 'TechSolution Form Studio Pro is a separate add-on plugin that adds complete address (country, province and city lists), international phone, digital signature, calculation, payment details and admission application fields. Everything in this plugin works without it.', 'techsolution-form-studio' ); ?></p>
        <a class="button" href="<?php echo esc_url( TSFS_PRO_URL ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Learn about the Pro add-on', 'techsolution-form-studio' ); ?></a>
    </div>
<?php endif; ?>
</div>
