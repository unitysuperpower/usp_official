<?php
/**
 * View: Pro Edition & Security Hub.
 * Comprehensive License Manager, Bot Shields (reCAPTCHA/Turnstile), Rate Limiter, and Hardened File Sandboxing.
 *
 * @package    USP_Form_Builder
 * @subpackage USP_Form_Builder/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$sec_settings = USP_Security::get_settings();
$is_pro       = USP_Security::is_pro_active();
$upload_dir   = wp_upload_dir();
$sandbox_dir  = $upload_dir['basedir'] . '/usp-form-builder';
$has_htaccess = file_exists( $sandbox_dir . '/.htaccess' );
$has_index    = file_exists( $sandbox_dir . '/index.php' );
?>

<div class="wrap usp-admin-wrap usp-security-hub-wrap">
    <!-- Header Hero Banner -->
    <div class="usp-page-header usp-security-header-banner" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:24px 28px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.05); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div style="display:flex; align-items:center; gap:16px;">
            <div style="width:48px; height:48px; border-radius:12px; background:linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); display:flex; align-items:center; justify-content:center; box-shadow:0 4px 10px rgba(79, 70, 229, 0.25); flex-shrink:0;">
                <span class="dashicons dashicons-shield-alt" style="color:#ffffff; font-size:26px; width:26px; height:26px;"></span>
            </div>
            <div>
                <h1 style="margin:0; font-size:22px; font-weight:800; color:#0f172a; line-height:1.2;">
                    <?php esc_html_e( 'Pro & Security Hub', 'usp-form-builder' ); ?>
                </h1>
                <p style="margin:4px 0 0 0; font-size:13.5px; color:#64748b;">
                    <?php esc_html_e( 'Manage Pro license activation, enterprise bot armor, IP rate limiting, and upload sandboxing.', 'usp-form-builder' ); ?>
                </p>
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            <?php if ( $is_pro ) : ?>
                <span class="usp-badge-pro is-active" style="background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; padding:6px 16px; border-radius:20px; font-weight:700; font-size:13px; display:inline-flex; align-items:center; gap:6px; box-shadow:0 1px 2px rgba(6,95,70,0.05);">
                    <span class="dashicons dashicons-awards" style="color:#059669; font-size:16px; width:16px; height:16px;"></span>
                    <?php echo ! empty( $sec_settings['license_error'] ) ? esc_html__( 'Pro Active · Verification Delayed', 'usp-form-builder' ) : esc_html__( 'Pro Edition Active ★', 'usp-form-builder' ); ?>
                </span>
            <?php else : ?>
                <span class="usp-badge-pro is-inactive" style="background:#fef3c7; color:#92400e; border:1px solid #fde68a; padding:6px 16px; border-radius:20px; font-weight:700; font-size:13px; display:inline-flex; align-items:center; gap:6px;">
                    <span class="dashicons dashicons-lock" style="color:#d97706; font-size:16px; width:16px; height:16px;"></span>
                    <?php esc_html_e( 'Free Edition (Pro Inactive)', 'usp-form-builder' ); ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Security & Pro KPI Status Cards -->
    <div class="usp-security-metrics-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(230px, 1fr)); gap:16px; margin-bottom:24px;">
        <!-- Card 1: License -->
        <div class="usp-sec-metric-card" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:10px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.04); position:relative; overflow:hidden;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                <span style="font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#64748b;">
                    <?php esc_html_e( 'Pro License', 'usp-form-builder' ); ?>
                </span>
                <span class="dashicons <?php echo $is_pro ? 'dashicons-awards' : 'dashicons-lock'; ?>" style="color:<?php echo $is_pro ? '#10b981' : '#f59e0b'; ?>; font-size:20px; width:20px; height:20px;"></span>
            </div>
            <div style="font-size:20px; font-weight:800; color:<?php echo $is_pro ? '#059669' : '#d97706'; ?>; line-height:1.2;">
                <?php echo $is_pro ? esc_html__( 'Active & Verified', 'usp-form-builder' ) : esc_html__( 'Not Activated', 'usp-form-builder' ); ?>
            </div>
            <div style="font-size:12px; color:#64748b; margin-top:6px;">
                <?php echo $is_pro ? esc_html__( 'All Pro Fields & Advanced Controls Unlocked', 'usp-form-builder' ) : esc_html__( 'Enter your key to unlock Pro features', 'usp-form-builder' ); ?>
            </div>
            <?php if ( ! empty( $sec_settings['license_error'] ) ) : ?>
                <div style="font-size:11px; color:#92400e; margin-top:6px;">
                    <?php echo esc_html( $sec_settings['license_error'] ); ?>
                </div>
            <?php endif; ?>
            <div style="position:absolute; bottom:0; left:0; right:0; height:3px; background:<?php echo $is_pro ? '#10b981' : '#f59e0b'; ?>;"></div>
        </div>

        <!-- Card 2: Bot Armor -->
        <div class="usp-sec-metric-card" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:10px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.04); position:relative; overflow:hidden;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                <span style="font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#64748b;">
                    <?php esc_html_e( 'Bot & Spam Armor', 'usp-form-builder' ); ?>
                </span>
                <span class="dashicons dashicons-shield" style="color:#2563eb; font-size:20px; width:20px; height:20px;"></span>
            </div>
            <div style="font-size:20px; font-weight:800; color:#1e293b; line-height:1.2;">
                <?php
                if ( 'none' !== $sec_settings['anti_spam_provider'] ) {
                    echo esc_html( ucfirst( str_replace( '_', ' ', $sec_settings['anti_spam_provider'] ) ) );
                } else {
                    echo esc_html__( 'Honeypot Active', 'usp-form-builder' );
                }
                ?>
            </div>
            <div style="font-size:12px; color:#64748b; margin-top:6px;">
                <?php echo ! empty( $sec_settings['enable_time_gate'] ) ? esc_html__( 'Time-Gate Trap Active (' . absint( $sec_settings['time_gate_seconds'] ) . 's threshold)', 'usp-form-builder' ) : esc_html__( 'Zero-config honeypot trap enabled', 'usp-form-builder' ); ?>
            </div>
            <div style="position:absolute; bottom:0; left:0; right:0; height:3px; background:#2563eb;"></div>
        </div>

        <!-- Card 3: Rate Limiting -->
        <div class="usp-sec-metric-card" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:10px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.04); position:relative; overflow:hidden;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                <span style="font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#64748b;">
                    <?php esc_html_e( 'DDoS Rate Limiter', 'usp-form-builder' ); ?>
                </span>
                <span class="dashicons dashicons-dashboard" style="color:<?php echo ! empty( $sec_settings['enable_rate_limiting'] ) ? '#10b981' : '#94a3b8'; ?>; font-size:20px; width:20px; height:20px;"></span>
            </div>
            <div style="font-size:20px; font-weight:800; color:<?php echo ! empty( $sec_settings['enable_rate_limiting'] ) ? '#059669' : '#64748b'; ?>; line-height:1.2;">
                <?php echo ! empty( $sec_settings['enable_rate_limiting'] ) ? esc_html__( 'Armed & Throttling', 'usp-form-builder' ) : esc_html__( 'Disabled', 'usp-form-builder' ); ?>
            </div>
            <div style="font-size:12px; color:#64748b; margin-top:6px;">
                <?php printf( esc_html__( 'Max %d submissions per %d mins per IP', 'usp-form-builder' ), absint( $sec_settings['rate_limit_max'] ), absint( $sec_settings['rate_limit_window'] ) ); ?>
            </div>
            <div style="position:absolute; bottom:0; left:0; right:0; height:3px; background:<?php echo ! empty( $sec_settings['enable_rate_limiting'] ) ? '#10b981' : '#cbd5e1'; ?>;"></div>
        </div>

        <!-- Card 4: Upload Sandbox -->
        <div class="usp-sec-metric-card" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:10px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.04); position:relative; overflow:hidden;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                <span style="font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#64748b;">
                    <?php esc_html_e( 'Upload Sandboxing', 'usp-form-builder' ); ?>
                </span>
                <span class="dashicons dashicons-lock" style="color:<?php echo $has_htaccess ? '#10b981' : '#ef4444'; ?>; font-size:20px; width:20px; height:20px;"></span>
            </div>
            <div style="font-size:20px; font-weight:800; color:<?php echo $has_htaccess ? '#059669' : '#dc2626'; ?>; line-height:1.2;">
                <?php echo $has_htaccess ? esc_html__( '.htaccess Protected', 'usp-form-builder' ) : esc_html__( 'Unprotected', 'usp-form-builder' ); ?>
            </div>
            <div style="font-size:12px; color:#64748b; margin-top:6px;">
                <?php esc_html_e( 'PHP script execution blocked in uploads dir', 'usp-form-builder' ); ?>
            </div>
            <div style="position:absolute; bottom:0; left:0; right:0; height:3px; background:<?php echo $has_htaccess ? '#10b981' : '#ef4444'; ?>;"></div>
        </div>
    </div>

    <!-- Hub Container -->
    <div class="usp-settings-card" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,0.05); overflow:hidden;">
        <!-- Tab Navigation Bar -->
        <div class="usp-hub-tabs" style="display:flex; border-bottom:1px solid #e2e8f0; background:#f8fafc; overflow-x:auto;">
            <button type="button" class="usp-hub-tab active" data-tab="license" style="padding:16px 24px; font-size:13.5px; font-weight:700; color:#4f46e5; border:none; background:transparent; border-bottom:2px solid #4f46e5; cursor:pointer; display:flex; align-items:center; gap:8px; white-space:nowrap;">
                <span class="dashicons dashicons-star-filled" style="font-size:17px; width:17px; height:17px;"></span>
                <?php esc_html_e( 'Pro License & Activation', 'usp-form-builder' ); ?>
            </button>
            <button type="button" class="usp-hub-tab" data-tab="antispam" style="padding:16px 24px; font-size:13.5px; font-weight:700; color:#64748b; border:none; background:transparent; border-bottom:2px solid transparent; cursor:pointer; display:flex; align-items:center; gap:8px; white-space:nowrap;">
                <span class="dashicons dashicons-shield" style="font-size:17px; width:17px; height:17px;"></span>
                <?php esc_html_e( 'Anti-Spam & Bot Shield', 'usp-form-builder' ); ?>
            </button>
            <button type="button" class="usp-hub-tab" data-tab="ratelimit" style="padding:16px 24px; font-size:13.5px; font-weight:700; color:#64748b; border:none; background:transparent; border-bottom:2px solid transparent; cursor:pointer; display:flex; align-items:center; gap:8px; white-space:nowrap;">
                <span class="dashicons dashicons-dashboard" style="font-size:17px; width:17px; height:17px;"></span>
                <?php esc_html_e( 'Rate Limiting & DDoS', 'usp-form-builder' ); ?>
            </button>
            <button type="button" class="usp-hub-tab" data-tab="sandboxing" style="padding:16px 24px; font-size:13.5px; font-weight:700; color:#64748b; border:none; background:transparent; border-bottom:2px solid transparent; cursor:pointer; display:flex; align-items:center; gap:8px; white-space:nowrap;">
                <span class="dashicons dashicons-lock" style="font-size:17px; width:17px; height:17px;"></span>
                <?php esc_html_e( 'File & Data Hardening', 'usp-form-builder' ); ?>
            </button>
        </div>

        <div class="usp-hub-tab-contents" style="padding:32px;">
            <!-- Tab 1: Pro License -->
            <div class="usp-hub-panel active" id="usp-panel-license">
                <div style="max-width:820px;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:18px;">
                        <div>
                            <h3 style="font-size:18px; font-weight:800; margin:0 0 6px 0; color:#0f172a;">
                                <?php esc_html_e( 'USP Form Builder Pro License Activation', 'usp-form-builder' ); ?>
                            </h3>
                            <p style="color:#64748b; font-size:13.5px; margin:0;">
                                <?php esc_html_e( 'Activate your store-issued license to unlock Pro fields and advanced workflows on this WordPress site.', 'usp-form-builder' ); ?>
                            </p>
                        </div>
                    </div>

                    <!-- License Form Box -->
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:24px; margin-bottom:30px;">
                        <div style="margin-bottom:20px; padding-bottom:18px; border-bottom:1px solid #e2e8f0;">
                            <label for="usp_edd_item_id" style="display:block; font-size:13px; font-weight:700; color:#334155; margin-bottom:6px;">
                                <?php esc_html_e( 'EDD Pro Download ID', 'usp-form-builder' ); ?>
                            </label>
                            <div style="display:flex; gap:10px; align-items:center;">
                                <input type="number" min="1" step="1" id="usp_edd_item_id" value="<?php echo esc_attr( absint( $sec_settings['edd_item_id'] ) ); ?>" style="max-width:220px; height:38px;">
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:12px;">
                                <div>
                                    <label for="usp_edd_pricing_url" style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;"><?php esc_html_e( 'Pricing Page URL', 'usp-form-builder' ); ?></label>
                                    <input type="url" id="usp_edd_pricing_url" value="<?php echo esc_attr( $sec_settings['edd_pricing_url'] ); ?>" style="width:100%;">
                                </div>
                                <div>
                                    <label for="usp_edd_account_url" style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;"><?php esc_html_e( 'Customer Account URL', 'usp-form-builder' ); ?></label>
                                    <input type="url" id="usp_edd_account_url" value="<?php echo esc_attr( $sec_settings['edd_account_url'] ); ?>" placeholder="https://usp.com.pk/your-account/" style="width:100%;">
                                </div>
                            </div>
                            <button type="button" class="button button-secondary" id="usp-save-edd-item-id-btn" style="margin-top:10px;"><?php esc_html_e( 'Save Store Settings', 'usp-form-builder' ); ?></button>
                            <p style="font-size:12px; color:#64748b; margin:6px 0 0;">
                                <?php esc_html_e( 'Use the numeric Download ID of the Pro product configured with EDD Software Licensing on usp.com.pk. The URLs connect the plugin dashboard to your published pricing and customer account pages.', 'usp-form-builder' ); ?>
                            </p>
                        </div>
                        <label for="usp_license_key_input" style="display:block; font-size:13px; font-weight:700; color:#334155; margin-bottom:8px;">
                            <?php esc_html_e( 'Pro License Key', 'usp-form-builder' ); ?>
                        </label>
                        <div style="display:flex; gap:10px;">
                            <input 
                                type="text" 
                                id="usp_license_key_input" 
                                value="<?php echo esc_attr( $sec_settings['license_key'] ); ?>" 
                                placeholder="<?php esc_attr_e( 'Enter your purchased license key', 'usp-form-builder' ); ?>"
                                style="flex:1; height:42px; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size:13.5px; padding:8px 14px; border-radius:6px; border:1.5px solid #cbd5e1; background:#ffffff;"
                            >
                            <?php if ( $is_pro ) : ?>
                                <button type="button" class="button button-secondary" id="usp-check-license-btn" style="height:42px; padding:0 16px; font-weight:600; border-radius:6px;">
                                    <?php esc_html_e( 'Verify', 'usp-form-builder' ); ?>
                                </button>
                                <button type="button" class="button button-secondary" id="usp-deactivate-license-btn" style="height:42px; padding:0 20px; font-weight:600; color:#dc2626; border-color:#fca5a5; border-radius:6px;">
                                    <?php esc_html_e( 'Deactivate', 'usp-form-builder' ); ?>
                                </button>
                            <?php else : ?>
                                <button type="button" class="button button-primary" id="usp-activate-license-btn" style="height:42px; padding:0 24px; font-weight:700; background:#4f46e5; border-color:#4338ca; border-radius:6px;">
                                    <?php esc_html_e( 'Activate License', 'usp-form-builder' ); ?>
                                </button>
                            <?php endif; ?>
                        </div>

                        <p style="font-size:12px; color:#64748b; margin:10px 0 0;">
                            <?php esc_html_e( 'License activation and status are verified by the EDD Software Licensing service at usp.com.pk.', 'usp-form-builder' ); ?>
                        </p>
                    </div>

                    <!-- Unlocked Feature Matrix -->
                    <h4 style="font-size:15px; font-weight:700; color:#0f172a; margin:0 0 16px 0;">
                        <?php esc_html_e( 'Pro Features Comparison & Capabilities', 'usp-form-builder' ); ?>
                    </h4>
                    <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:16px;">
                        <div style="padding:16px; border:1px solid #e2e8f0; border-radius:10px; display:flex; align-items:flex-start; gap:14px; background:#ffffff; transition:all 0.15s ease;" class="usp-feature-item-card">
                            <div style="width:38px; height:38px; border-radius:8px; background:#eef2ff; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                <span class="dashicons dashicons-welcome-learn-more" style="color:#4f46e5; font-size:20px; width:20px; height:20px;"></span>
                            </div>
                            <div>
                                <strong style="display:block; font-size:14px; color:#0f172a; margin-bottom:2px;"><?php esc_html_e( 'University Admission System', 'usp-form-builder' ); ?></strong>
                                <span style="font-size:12.5px; color:#64748b; line-height:1.4; display:block;"><?php esc_html_e( 'Comprehensive student applications with academic majors, GPA inputs, and automated high-school/college metadata.', 'usp-form-builder' ); ?></span>
                            </div>
                        </div>

                        <div style="padding:16px; border:1px solid #e2e8f0; border-radius:10px; display:flex; align-items:flex-start; gap:14px; background:#ffffff; transition:all 0.15s ease;" class="usp-feature-item-card">
                            <div style="width:38px; height:38px; border-radius:8px; background:#ecfdf5; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                <span class="dashicons dashicons-translation" style="color:#059669; font-size:20px; width:20px; height:20px;"></span>
                            </div>
                            <div>
                                <strong style="display:block; font-size:14px; color:#0f172a; margin-bottom:2px;"><?php esc_html_e( 'Global Phone Validation', 'usp-form-builder' ); ?></strong>
                                <span style="font-size:12.5px; color:#64748b; line-height:1.4; display:block;"><?php esc_html_e( 'International country code picker with automatic emoji flags and strict digit pattern validation.', 'usp-form-builder' ); ?></span>
                            </div>
                        </div>

                        <div style="padding:16px; border:1px solid #e2e8f0; border-radius:10px; display:flex; align-items:flex-start; gap:14px; background:#ffffff; transition:all 0.15s ease;" class="usp-feature-item-card">
                            <div style="width:38px; height:38px; border-radius:8px; background:#fef3c7; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                <span class="dashicons dashicons-cart" style="color:#d97706; font-size:20px; width:20px; height:20px;"></span>
                            </div>
                            <div>
                                <strong style="display:block; font-size:14px; color:#0f172a; margin-bottom:2px;"><?php esc_html_e( 'Payment Details Field', 'usp-form-builder' ); ?></strong>
                                <span style="font-size:12.5px; color:#64748b; line-height:1.4; display:block;"><?php esc_html_e( 'A payment-form field for collecting payment-related details. It does not process or verify transactions; connect a payment provider separately.', 'usp-form-builder' ); ?></span>
                            </div>
                        </div>

                        <div style="padding:16px; border:1px solid #e2e8f0; border-radius:10px; display:flex; align-items:flex-start; gap:14px; background:#ffffff; transition:all 0.15s ease;" class="usp-feature-item-card">
                            <div style="width:38px; height:38px; border-radius:8px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                <span class="dashicons dashicons-shield" style="color:#0f172a; font-size:20px; width:20px; height:20px;"></span>
                            </div>
                            <div>
                                <strong style="display:block; font-size:14px; color:#0f172a; margin-bottom:2px;"><?php esc_html_e( 'Enterprise Security Armor', 'usp-form-builder' ); ?></strong>
                                <span style="font-size:12.5px; color:#64748b; line-height:1.4; display:block;"><?php esc_html_e( 'DDoS rate limiting, Turnstile / reCAPTCHA v3 anti-bot traps, and CSV formula injection protection.', 'usp-form-builder' ); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Anti-Spam & Bot Shield -->
            <div class="usp-hub-panel" id="usp-panel-antispam" style="display:none;">
                <div style="max-width:820px;">
                    <h3 style="font-size:18px; font-weight:800; margin:0 0 6px 0; color:#0f172a;">
                        <?php esc_html_e( 'Anti-Spam & Automated Bot Armor', 'usp-form-builder' ); ?>
                    </h3>
                    <p style="color:#64748b; font-size:13.5px; margin-bottom:24px;">
                        <?php esc_html_e( 'Safeguard your website against spambots, automated form bombing, and malicious payload injections.', 'usp-form-builder' ); ?>
                    </p>

                    <!-- Provider Selection -->
                    <div style="margin-bottom:24px;">
                        <label style="display:block; font-size:13px; font-weight:700; color:#334155; margin-bottom:8px;">
                            <?php esc_html_e( 'Select Active Anti-Spam Provider', 'usp-form-builder' ); ?>
                        </label>
                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px;">
                            <label class="usp-provider-card" style="border:1.5px solid <?php echo 'none' === $sec_settings['anti_spam_provider'] ? '#4f46e5' : '#cbd5e1'; ?>; padding:14px; border-radius:8px; cursor:pointer; background:<?php echo 'none' === $sec_settings['anti_spam_provider'] ? '#eef2ff' : '#fff'; ?>;">
                                <input type="radio" name="anti_spam_provider" value="none" <?php checked( $sec_settings['anti_spam_provider'], 'none' ); ?> style="margin-right:8px;">
                                <div>
                                    <strong style="display:block; font-size:13px; color:#0f172a;"><?php esc_html_e( 'None (Honeypot Only)', 'usp-form-builder' ); ?></strong>
                                    <span style="font-size:11px; color:#64748b;"><?php esc_html_e( 'Zero setup required', 'usp-form-builder' ); ?></span>
                                </div>
                            </label>
                            <label class="usp-provider-card" style="border:1.5px solid <?php echo 'recaptcha_v2' === $sec_settings['anti_spam_provider'] ? '#4f46e5' : '#cbd5e1'; ?>; padding:14px; border-radius:8px; cursor:pointer; background:<?php echo 'recaptcha_v2' === $sec_settings['anti_spam_provider'] ? '#eef2ff' : '#fff'; ?>;">
                                <input type="radio" name="anti_spam_provider" value="recaptcha_v2" <?php checked( $sec_settings['anti_spam_provider'], 'recaptcha_v2' ); ?> style="margin-right:8px;">
                                <div>
                                    <strong style="display:block; font-size:13px; color:#0f172a;"><?php esc_html_e( 'Google reCAPTCHA v2', 'usp-form-builder' ); ?></strong>
                                    <span style="font-size:11px; color:#64748b;"><?php esc_html_e( '"I\'m not a robot" checkbox', 'usp-form-builder' ); ?></span>
                                </div>
                            </label>
                            <label class="usp-provider-card" style="border:1.5px solid <?php echo 'recaptcha_v3' === $sec_settings['anti_spam_provider'] ? '#4f46e5' : '#cbd5e1'; ?>; padding:14px; border-radius:8px; cursor:pointer; background:<?php echo 'recaptcha_v3' === $sec_settings['anti_spam_provider'] ? '#eef2ff' : '#fff'; ?>;">
                                <input type="radio" name="anti_spam_provider" value="recaptcha_v3" <?php checked( $sec_settings['anti_spam_provider'], 'recaptcha_v3' ); ?> style="margin-right:8px;">
                                <div>
                                    <strong style="display:block; font-size:13px; color:#0f172a;"><?php esc_html_e( 'reCAPTCHA v3', 'usp-form-builder' ); ?></strong>
                                    <span style="font-size:11px; color:#64748b;"><?php esc_html_e( 'Invisible background score', 'usp-form-builder' ); ?></span>
                                </div>
                            </label>
                            <label class="usp-provider-card" style="border:1.5px solid <?php echo 'turnstile' === $sec_settings['anti_spam_provider'] ? '#4f46e5' : '#cbd5e1'; ?>; padding:14px; border-radius:8px; cursor:pointer; background:<?php echo 'turnstile' === $sec_settings['anti_spam_provider'] ? '#eef2ff' : '#fff'; ?>;">
                                <input type="radio" name="anti_spam_provider" value="turnstile" <?php checked( $sec_settings['anti_spam_provider'], 'turnstile' ); ?> style="margin-right:8px;">
                                <div>
                                    <strong style="display:block; font-size:13px; color:#0f172a;"><?php esc_html_e( 'Cloudflare Turnstile', 'usp-form-builder' ); ?></strong>
                                    <span style="font-size:11px; color:#64748b;"><?php esc_html_e( 'Privacy-first smart challenge', 'usp-form-builder' ); ?></span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Google reCAPTCHA Keys -->
                    <div id="usp-recaptcha-keys-wrap" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:22px; margin-bottom:20px; <?php echo in_array( $sec_settings['anti_spam_provider'], array( 'recaptcha_v2', 'recaptcha_v3' ), true ) ? '' : 'display:none;'; ?>">
                        <h4 style="margin:0 0 14px 0; font-size:14px; font-weight:700; color:#1e293b;"><?php esc_html_e( 'Google reCAPTCHA API Keys', 'usp-form-builder' ); ?></h4>
                        <div style="margin-bottom:14px;">
                            <label for="usp_recaptcha_site_key" style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:4px;"><?php esc_html_e( 'reCAPTCHA Site Key', 'usp-form-builder' ); ?></label>
                            <input type="text" id="usp_recaptcha_site_key" value="<?php echo esc_attr( $sec_settings['recaptcha_site_key'] ); ?>" class="regular-text" style="width:100%; height:38px; border-radius:6px; border:1px solid #cbd5e1; font-family:monospace;">
                        </div>
                        <div style="margin-bottom:14px;">
                            <label for="usp_recaptcha_secret_key" style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:4px;"><?php esc_html_e( 'reCAPTCHA Secret Key', 'usp-form-builder' ); ?></label>
                            <input type="password" id="usp_recaptcha_secret_key" value="<?php echo esc_attr( $sec_settings['recaptcha_secret_key'] ); ?>" class="regular-text" style="width:100%; height:38px; border-radius:6px; border:1px solid #cbd5e1; font-family:monospace;">
                        </div>
                        <div id="usp-recaptcha-v3-score-wrap" style="<?php echo 'recaptcha_v3' === $sec_settings['anti_spam_provider'] ? '' : 'display:none;'; ?>">
                            <label for="usp_recaptcha_v3_score" style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:4px;"><?php esc_html_e( 'v3 Minimum Score Threshold (0.1 to 1.0, Default: 0.5)', 'usp-form-builder' ); ?></label>
                            <input type="number" step="0.1" min="0.1" max="1.0" id="usp_recaptcha_v3_score" value="<?php echo esc_attr( $sec_settings['recaptcha_v3_score'] ); ?>" style="width:120px; height:38px; border-radius:6px; border:1px solid #cbd5e1;">
                        </div>
                    </div>

                    <!-- Cloudflare Turnstile Keys -->
                    <div id="usp-turnstile-keys-wrap" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:22px; margin-bottom:20px; <?php echo 'turnstile' === $sec_settings['anti_spam_provider'] ? '' : 'display:none;'; ?>">
                        <h4 style="margin:0 0 14px 0; font-size:14px; font-weight:700; color:#1e293b;"><?php esc_html_e( 'Cloudflare Turnstile API Keys', 'usp-form-builder' ); ?></h4>
                        <div style="margin-bottom:14px;">
                            <label for="usp_turnstile_site_key" style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:4px;"><?php esc_html_e( 'Turnstile Site Key', 'usp-form-builder' ); ?></label>
                            <input type="text" id="usp_turnstile_site_key" value="<?php echo esc_attr( $sec_settings['turnstile_site_key'] ); ?>" class="regular-text" style="width:100%; height:38px; border-radius:6px; border:1px solid #cbd5e1; font-family:monospace;">
                        </div>
                        <div>
                            <label for="usp_turnstile_secret_key" style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:4px;"><?php esc_html_e( 'Turnstile Secret Key', 'usp-form-builder' ); ?></label>
                            <input type="password" id="usp_turnstile_secret_key" value="<?php echo esc_attr( $sec_settings['turnstile_secret_key'] ); ?>" class="regular-text" style="width:100%; height:38px; border-radius:6px; border:1px solid #cbd5e1; font-family:monospace;">
                        </div>
                    </div>

                    <!-- Time-Gate Bot Trap -->
                    <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:10px; padding:20px; margin-bottom:20px;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <strong style="font-size:14px; color:#1e293b; display:block;"><?php esc_html_e( 'Time-Gate Bot Trap', 'usp-form-builder' ); ?></strong>
                                <span style="font-size:12.5px; color:#64748b;"><?php esc_html_e( 'Rejects automated bot submissions that complete the entire form in less than humanly possible time.', 'usp-form-builder' ); ?></span>
                            </div>
                            <label class="usp-toggle-switch">
                                <input type="checkbox" id="usp_enable_time_gate" <?php checked( ! empty( $sec_settings['enable_time_gate'] ) ); ?>>
                                <span class="usp-slider"></span>
                            </label>
                        </div>
                        <div style="margin-top:14px; padding-top:14px; border-top:1px solid #f1f5f9; display:flex; align-items:center; gap:10px;">
                            <label for="usp_time_gate_seconds" style="font-size:13px; color:#475569; font-weight:600;"><?php esc_html_e( 'Minimum Seconds Required:', 'usp-form-builder' ); ?></label>
                            <input type="number" id="usp_time_gate_seconds" min="1" max="10" value="<?php echo esc_attr( $sec_settings['time_gate_seconds'] ); ?>" style="width:75px; height:36px; border-radius:6px; border:1px solid #cbd5e1;">
                            <span style="font-size:12px; color:#94a3b8;"><?php esc_html_e( 'seconds (recommended: 2s)', 'usp-form-builder' ); ?></span>
                        </div>
                    </div>

                    <!-- Invisible Honeypot Notice -->
                    <div style="padding:14px 18px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; display:flex; align-items:center; gap:12px;">
                        <span class="dashicons dashicons-yes-alt" style="color:#16a34a; font-size:20px; width:20px; height:20px;"></span>
                        <span style="font-size:13px; color:#15803d; font-weight:600;">
                            <?php esc_html_e( 'Invisible Anti-Spam Honeypot is actively armed on all public forms automatically.', 'usp-form-builder' ); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Rate Limiting & DDoS Defense -->
            <div class="usp-hub-panel" id="usp-panel-ratelimit" style="display:none;">
                <div style="max-width:820px;">
                    <h3 style="font-size:18px; font-weight:800; margin:0 0 6px 0; color:#0f172a;">
                        <?php esc_html_e( 'IP Rate Limiting & Anti-Flood Shield', 'usp-form-builder' ); ?>
                    </h3>
                    <p style="color:#64748b; font-size:13.5px; margin-bottom:24px;">
                        <?php esc_html_e( 'Prevent brute-force form submissions, automated spam bombing, and server resource exhaustion.', 'usp-form-builder' ); ?>
                    </p>

                    <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:10px; padding:20px; margin-bottom:24px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                            <div>
                                <strong style="font-size:14px; color:#1e293b; display:block;"><?php esc_html_e( 'Enable Client IP Rate Limiting', 'usp-form-builder' ); ?></strong>
                                <span style="font-size:12.5px; color:#64748b;"><?php esc_html_e( 'Throttle repeat submissions from the same IP address across forms.', 'usp-form-builder' ); ?></span>
                            </div>
                            <label class="usp-toggle-switch">
                                <input type="checkbox" id="usp_enable_rate_limiting" <?php checked( ! empty( $sec_settings['enable_rate_limiting'] ) ); ?>>
                                <span class="usp-slider"></span>
                            </label>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; padding-top:16px; border-top:1px solid #f1f5f9;">
                            <div>
                                <label for="usp_rate_limit_max" style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:4px;">
                                    <?php esc_html_e( 'Maximum Submissions Allowed', 'usp-form-builder' ); ?>
                                </label>
                                <input type="number" id="usp_rate_limit_max" min="1" max="100" value="<?php echo esc_attr( $sec_settings['rate_limit_max'] ); ?>" style="width:100%; height:38px; border-radius:6px; border:1px solid #cbd5e1;">
                            </div>
                            <div>
                                <label for="usp_rate_limit_window" style="display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:4px;">
                                    <?php esc_html_e( 'Time Window (Minutes)', 'usp-form-builder' ); ?>
                                </label>
                                <input type="number" id="usp_rate_limit_window" min="1" max="60" value="<?php echo esc_attr( $sec_settings['rate_limit_window'] ); ?>" style="width:100%; height:38px; border-radius:6px; border:1px solid #cbd5e1;">
                            </div>
                        </div>
                    </div>

                    <!-- Blacklisted IPs -->
                    <div style="margin-bottom:24px;">
                        <label for="usp_blacklisted_ips" style="display:block; font-size:13px; font-weight:700; color:#334155; margin-bottom:4px;">
                            <?php esc_html_e( 'Blacklisted IP Addresses / CIDR Ranges', 'usp-form-builder' ); ?>
                        </label>
                        <p style="font-size:12px; color:#64748b; margin-bottom:8px;">
                            <?php esc_html_e( 'Enter one IP or subnet per line (e.g. 192.168.1.50 or 10.0.0.0/24). Submissions from these IPs will be rejected immediately.', 'usp-form-builder' ); ?>
                        </p>
                        <textarea id="usp_blacklisted_ips" rows="4" style="width:100%; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size:13px; border-radius:6px; border:1.5px solid #cbd5e1; padding:8px 12px;"><?php echo esc_textarea( $sec_settings['blacklisted_ips'] ); ?></textarea>
                    </div>

                    <!-- Blocked Keywords -->
                    <div style="margin-bottom:24px;">
                        <label for="usp_blocked_keywords" style="display:block; font-size:13px; font-weight:700; color:#334155; margin-bottom:4px;">
                            <?php esc_html_e( 'Blocked Spam Keywords & Phishing Patterns', 'usp-form-builder' ); ?>
                        </label>
                        <p style="font-size:12px; color:#64748b; margin-bottom:8px;">
                            <?php esc_html_e( 'Enter words, domains, or phrases (one per line) that trigger automatic submission rejection (e.g. casino, crypto-airdrop, phishing).', 'usp-form-builder' ); ?>
                        </p>
                        <textarea id="usp_blocked_keywords" rows="4" style="width:100%; font-size:13px; border-radius:6px; border:1.5px solid #cbd5e1; padding:8px 12px;"><?php echo esc_textarea( $sec_settings['blocked_keywords'] ); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Tab 4: File Sandboxing & Data Hardening -->
            <div class="usp-hub-panel" id="usp-panel-sandboxing" style="display:none;">
                <div style="max-width:820px;">
                    <h3 style="font-size:18px; font-weight:800; margin:0 0 6px 0; color:#0f172a;">
                        <?php esc_html_e( 'Upload Sandboxing & Data Security Armor', 'usp-form-builder' ); ?>
                    </h3>
                    <p style="color:#64748b; font-size:13.5px; margin-bottom:24px;">
                        <?php esc_html_e( 'Prevent server-side code execution, malicious file uploads, and spreadsheet formula injection exploits.', 'usp-form-builder' ); ?>
                    </p>

                    <!-- Upload Directory Shield Card -->
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:22px; margin-bottom:24px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; flex-wrap:wrap; gap:12px;">
                            <div>
                                <strong style="font-size:14px; color:#1e293b; display:block;"><?php esc_html_e( 'Upload Directory Sandbox (.htaccess)', 'usp-form-builder' ); ?></strong>
                                <span style="font-size:12px; color:#64748b;">
                                    <?php printf( esc_html__( 'Target Folder: %s', 'usp-form-builder' ), '<code style="background:#e2e8f0; padding:2px 6px; border-radius:4px;">wp-content/uploads/usp-form-builder/</code>' ); ?>
                                </span>
                            </div>
                            <button type="button" class="button button-secondary" id="usp-redeploy-shields-btn" style="font-weight:600; border-radius:6px; height:34px; display:inline-flex; align-items:center; gap:5px;">
                                <span class="dashicons dashicons-update" style="font-size:15px; width:15px; height:15px;"></span>
                                <?php esc_html_e( 'Re-deploy Shields', 'usp-form-builder' ); ?>
                            </button>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-top:14px;">
                            <div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:14px; display:flex; align-items:center; gap:10px;">
                                <span class="dashicons <?php echo $has_htaccess ? 'dashicons-yes' : 'dashicons-no'; ?>" style="color:<?php echo $has_htaccess ? '#16a34a' : '#dc2626'; ?>; font-size:22px; width:22px; height:22px;"></span>
                                <div>
                                    <strong style="font-size:13px; color:#0f172a; display:block;"><?php echo $has_htaccess ? esc_html__( '.htaccess Script Blocker', 'usp-form-builder' ) : esc_html__( '.htaccess Missing', 'usp-form-builder' ); ?></strong>
                                    <span style="font-size:11.5px; color:#64748b;"><?php echo $has_htaccess ? esc_html__( 'Active & Blocking script execution', 'usp-form-builder' ) : esc_html__( 'Click Re-deploy to generate', 'usp-form-builder' ); ?></span>
                                </div>
                            </div>
                            <div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:14px; display:flex; align-items:center; gap:10px;">
                                <span class="dashicons <?php echo $has_index ? 'dashicons-yes' : 'dashicons-no'; ?>" style="color:<?php echo $has_index ? '#16a34a' : '#dc2626'; ?>; font-size:22px; width:22px; height:22px;"></span>
                                <div>
                                    <strong style="font-size:13px; color:#0f172a; display:block;"><?php echo $has_index ? esc_html__( 'Directory Indexing Blocker', 'usp-form-builder' ) : esc_html__( 'index.php Missing', 'usp-form-builder' ); ?></strong>
                                    <span style="font-size:11.5px; color:#64748b;"><?php echo $has_index ? esc_html__( 'index.php silencer active', 'usp-form-builder' ) : esc_html__( 'Click Re-deploy to generate', 'usp-form-builder' ); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MIME Sniffing -->
                    <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:10px; padding:20px; margin-bottom:16px;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <strong style="font-size:14px; color:#1e293b; display:block;"><?php esc_html_e( 'Strict MIME Type Verification (finfo_file)', 'usp-form-builder' ); ?></strong>
                                <span style="font-size:12.5px; color:#64748b;"><?php esc_html_e( 'Inspects real binary contents of uploaded files to reject disguised PHP/shell scripts.', 'usp-form-builder' ); ?></span>
                            </div>
                            <label class="usp-toggle-switch">
                                <input type="checkbox" id="usp_enable_mime_sniffing" <?php checked( ! empty( $sec_settings['enable_mime_sniffing'] ) ); ?>>
                                <span class="usp-slider"></span>
                            </label>
                        </div>
                    </div>

                    <!-- CSV Formula Injection Shield -->
                    <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:10px; padding:20px; margin-bottom:16px;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <strong style="font-size:14px; color:#1e293b; display:block;"><?php esc_html_e( 'CSV / Excel Formula Injection Shield', 'usp-form-builder' ); ?></strong>
                                <span style="font-size:12.5px; color:#64748b;"><?php esc_html_e( 'Neutralizes cells starting with =, +, -, or @ when exporting submissions to prevent spreadsheet DDE execution.', 'usp-form-builder' ); ?></span>
                            </div>
                            <label class="usp-toggle-switch">
                                <input type="checkbox" id="usp_enable_csv_injection_shield" <?php checked( ! empty( $sec_settings['enable_csv_injection_shield'] ) ); ?>>
                                <span class="usp-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sticky Save Footer Bar -->
        <div style="padding:18px 32px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
            <div style="font-size:13px; color:#64748b; display:flex; align-items:center; gap:6px;">
                <span class="dashicons dashicons-shield-alt" style="color:#059669; font-size:18px; width:18px; height:18px;"></span>
                <span><?php esc_html_e( 'Security policies apply immediately across all public forms and endpoints.', 'usp-form-builder' ); ?></span>
            </div>
            <button type="button" class="button button-primary button-hero" id="usp-save-security-settings-btn" style="background:#4f46e5; border-color:#4338ca; font-weight:700; padding:0 28px; height:44px; display:inline-flex; align-items:center; gap:8px; border-radius:8px;">
                <span class="dashicons dashicons-saved" style="font-size:18px; width:18px; height:18px;"></span>
                <span class="usp-btn-text"><?php esc_html_e( 'Save Security Configuration', 'usp-form-builder' ); ?></span>
                <span class="spinner" style="float:none; margin:0;" aria-hidden="true"></span>
            </button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    'use strict';

    // Tab Switching
    $('.usp-hub-tab').on('click', function() {
        var tab = $(this).data('tab');
        $('.usp-hub-tab').removeClass('active').css({
            'color': '#64748b',
            'border-bottom-color': 'transparent'
        });
        $(this).addClass('active').css({
            'color': '#4f46e5',
            'border-bottom-color': '#4f46e5'
        });
        $('.usp-hub-panel').hide();
        $('#usp-panel-' + tab).show();
    });

    // Provider Selector Change
    $('input[name="anti_spam_provider"]').on('change', function() {
        var val = $(this).val();
        $('.usp-provider-card').css({
            'border-color': '#cbd5e1',
            'background': '#fff'
        });
        $(this).closest('.usp-provider-card').css({
            'border-color': '#4f46e5',
            'background': '#eef2ff'
        });

        if (val === 'recaptcha_v2' || val === 'recaptcha_v3') {
            $('#usp-recaptcha-keys-wrap').slideDown(150);
            $('#usp-turnstile-keys-wrap').slideUp(150);
            if (val === 'recaptcha_v3') {
                $('#usp-recaptcha-v3-score-wrap').slideDown(150);
            } else {
                $('#usp-recaptcha-v3-score-wrap').slideUp(150);
            }
        } else if (val === 'turnstile') {
            $('#usp-turnstile-keys-wrap').slideDown(150);
            $('#usp-recaptcha-keys-wrap').slideUp(150);
        } else {
            $('#usp-recaptcha-keys-wrap').slideUp(150);
            $('#usp-turnstile-keys-wrap').slideUp(150);
        }
    });

    // Save Security Settings AJAX
    $('#usp-save-security-settings-btn').on('click', function() {
        var $btn = $(this);
        var $spinner = $btn.find('.spinner');
        $btn.prop('disabled', true);
        $spinner.addClass('is-active');

        var data = {
            action: 'usp_save_security_settings',
            nonce: uspAdminData.nonce,
            anti_spam_provider: $('input[name="anti_spam_provider"]:checked').val() || 'none',
            recaptcha_site_key: $('#usp_recaptcha_site_key').val(),
            recaptcha_secret_key: $('#usp_recaptcha_secret_key').val(),
            recaptcha_v3_score: $('#usp_recaptcha_v3_score').val(),
            turnstile_site_key: $('#usp_turnstile_site_key').val(),
            turnstile_secret_key: $('#usp_turnstile_secret_key').val(),
            enable_time_gate: $('#usp_enable_time_gate').is(':checked') ? 1 : 0,
            time_gate_seconds: $('#usp_time_gate_seconds').val(),
            enable_rate_limiting: $('#usp_enable_rate_limiting').is(':checked') ? 1 : 0,
            rate_limit_max: $('#usp_rate_limit_max').val(),
            rate_limit_window: $('#usp_rate_limit_window').val(),
            blacklisted_ips: $('#usp_blacklisted_ips').val(),
            blocked_keywords: $('#usp_blocked_keywords').val(),
            enable_upload_hardening: 1,
            enable_mime_sniffing: $('#usp_enable_mime_sniffing').is(':checked') ? 1 : 0,
            enable_csv_injection_shield: $('#usp_enable_csv_injection_shield').is(':checked') ? 1 : 0
        };

        $.post(uspAdminData.ajax_url, data, function(response) {
            $btn.prop('disabled', false);
            $spinner.removeClass('is-active');

            if (response.success) {
                USPModal.toast(response.data.message || 'Settings saved successfully!', 'success');
            } else {
                USPModal.toast(response.data.message || 'Error saving settings.', 'error');
            }
        }).fail(function() {
            $btn.prop('disabled', false);
            $spinner.removeClass('is-active');
            USPModal.toast('Network error saving settings.', 'error');
        });
    });

    // License Activation
    $('#usp-activate-license-btn').on('click', function() {
        var key = $('#usp_license_key_input').val();
        if (!key) {
            USPModal.toast('Please enter a license key.', 'warning');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('Verifying...');

        $.post(uspAdminData.ajax_url, {
            action: 'usp_verify_license',
            nonce: uspAdminData.nonce,
            license_key: key,
            license_action: 'activate'
        }, function(response) {
            $btn.prop('disabled', false).text('Activate License');
            if (response.success) {
                USPModal.toast(response.data.message, 'success');
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            } else {
                USPModal.alert({
                    title: 'License Activation Failed',
                    message: response.data && response.data.message ? response.data.message : 'The license server rejected the activation.',
                    type: 'danger'
                });
            }
        }).fail(function() {
            $btn.prop('disabled', false).text('Activate License');
            USPModal.toast('Could not reach the license service. Please try again.', 'error');
        });
    });

    // Save the EDD Software Licensing product ID used by usp.com.pk.
    $('#usp-save-edd-item-id-btn').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true);
        $.post(uspAdminData.ajax_url, {
            action: 'usp_save_edd_item_id',
            nonce: uspAdminData.nonce,
            edd_item_id: $('#usp_edd_item_id').val(),
            edd_pricing_url: $('#usp_edd_pricing_url').val(),
            edd_account_url: $('#usp_edd_account_url').val()
        }, function(response) {
            $btn.prop('disabled', false);
            if (response.success) {
                USPModal.toast(response.data.message, 'success');
            } else {
                USPModal.toast(response.data && response.data.message ? response.data.message : 'Could not save the EDD product ID.', 'error');
            }
        }).fail(function() {
            $btn.prop('disabled', false);
            USPModal.toast('Network error while saving the EDD product ID.', 'error');
        });
    });

    // Recheck the current key against the EDD license service.
    $('#usp-check-license-btn').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true);
        $.post(uspAdminData.ajax_url, {
            action: 'usp_verify_license',
            nonce: uspAdminData.nonce,
            license_key: '',
            license_action: 'check'
        }, function(response) {
            $btn.prop('disabled', false);
            if (response.success) {
                USPModal.toast(response.data.message, 'success');
                window.location.reload();
            } else {
                USPModal.toast(response.data && response.data.message ? response.data.message : 'License verification failed.', 'error');
            }
        }).fail(function() {
            $btn.prop('disabled', false);
            USPModal.toast('Network error while verifying the license.', 'error');
        });
    });

    // License Deactivation
    $('#usp-deactivate-license-btn').on('click', function() {
        USPModal.confirm({
            title: 'Deactivate Pro License?',
            message: 'Deactivating will lock Pro fields in the form builder. Are you sure you want to deactivate?',
            type: 'warning',
            confirmText: 'Yes, Deactivate'
        }).then(function(confirmed) {
            if (confirmed) {
                $.post(uspAdminData.ajax_url, {
                    action: 'usp_verify_license',
                    nonce: uspAdminData.nonce,
                    license_key: '',
                    license_action: 'deactivate'
                }, function(response) {
                    if (response.success) {
                        USPModal.toast(response.data.message, 'success');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    }
                });
            }
        });
    });

    // Re-deploy Shields
    $('#usp-redeploy-shields-btn').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true);

        $.post(uspAdminData.ajax_url, {
            action: 'usp_redeploy_shields',
            nonce: uspAdminData.nonce
        }, function(response) {
            $btn.prop('disabled', false);
            if (response.success) {
                USPModal.toast(response.data.message, 'success');
                window.location.reload();
            } else {
                USPModal.toast(response.data && response.data.message ? response.data.message : 'Could not redeploy upload protection.', 'error');
            }
        }).fail(function() {
            $btn.prop('disabled', false);
            USPModal.toast('Network error while redeploying upload protection.', 'error');
        });
    });
});
</script>
