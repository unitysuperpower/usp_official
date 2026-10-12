<?php
/**
 * View: USP Form Builder home dashboard.
 *
 * @package    USP_Form_Builder
 * @subpackage USP_Form_Builder/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$forms              = is_array( $forms ) ? $forms : array();
$total_forms        = count( $forms );
$total_entries      = USP_DB::get_entry_count();
$total_views        = USP_DB::get_total_views();
$conversion_rate    = $total_views > 0 ? round( ( $total_entries / $total_views ) * 100, 1 ) : 0;
$is_pro             = USP_Security::is_pro_active();
$security_settings  = USP_Security::get_settings();
$current_user       = wp_get_current_user();
$published_forms    = 0;

foreach ( $forms as $form ) {
    if ( empty( $form['status'] ) || 'publish' === $form['status'] ) {
        $published_forms++;
    }
}
?>

<div class="wrap usp-admin-wrap usp-dashboard-wrap">
    <header class="usp-dashboard-hero">
        <div>
            <span class="usp-dashboard-eyebrow"><?php esc_html_e( 'FORM BUILDER WORKSPACE', 'usp-form-builder' ); ?></span>
            <h1><?php esc_html_e( 'Welcome to USP Form Builder', 'usp-form-builder' ); ?></h1>
            <p><?php esc_html_e( 'Build forms, review submissions, and manage your license and security from one place.', 'usp-form-builder' ); ?></p>
        </div>
        <div class="usp-dashboard-hero-actions">
            <a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=usp-form-builder-new' ) ); ?>">
                <span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
                <?php esc_html_e( 'Create a Form', 'usp-form-builder' ); ?>
            </a>
            <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=usp-form-builder' ) ); ?>">
                <?php esc_html_e( 'View All Forms', 'usp-form-builder' ); ?>
            </a>
        </div>
    </header>

    <section class="usp-dashboard-stats" aria-label="<?php esc_attr_e( 'Form statistics', 'usp-form-builder' ); ?>">
        <a class="usp-dashboard-stat" href="<?php echo esc_url( admin_url( 'admin.php?page=usp-form-builder' ) ); ?>">
            <span class="dashicons dashicons-feedback" aria-hidden="true"></span>
            <span class="usp-dashboard-stat-label"><?php esc_html_e( 'Forms', 'usp-form-builder' ); ?></span>
            <strong><?php echo esc_html( number_format_i18n( $total_forms ) ); ?></strong>
            <small><?php echo esc_html( sprintf( __( '%s published', 'usp-form-builder' ), number_format_i18n( $published_forms ) ) ); ?></small>
        </a>
        <a class="usp-dashboard-stat" href="<?php echo esc_url( admin_url( 'admin.php?page=usp-form-entries' ) ); ?>">
            <span class="dashicons dashicons-email-alt" aria-hidden="true"></span>
            <span class="usp-dashboard-stat-label"><?php esc_html_e( 'Submissions', 'usp-form-builder' ); ?></span>
            <strong><?php echo esc_html( number_format_i18n( $total_entries ) ); ?></strong>
            <small><?php esc_html_e( 'Entries received', 'usp-form-builder' ); ?></small>
        </a>
        <div class="usp-dashboard-stat">
            <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
            <span class="usp-dashboard-stat-label"><?php esc_html_e( 'Form Views', 'usp-form-builder' ); ?></span>
            <strong><?php echo esc_html( number_format_i18n( $total_views ) ); ?></strong>
            <small><?php esc_html_e( 'Total impressions', 'usp-form-builder' ); ?></small>
        </div>
        <div class="usp-dashboard-stat">
            <span class="dashicons dashicons-chart-area" aria-hidden="true"></span>
            <span class="usp-dashboard-stat-label"><?php esc_html_e( 'Conversion Rate', 'usp-form-builder' ); ?></span>
            <strong><?php echo esc_html( $conversion_rate ); ?>%</strong>
            <small><?php esc_html_e( 'Submissions divided by views', 'usp-form-builder' ); ?></small>
        </div>
    </section>

    <div class="usp-dashboard-columns">
        <main class="usp-dashboard-main">
            <section class="usp-dashboard-card">
                <div class="usp-dashboard-section-heading">
                    <div>
                        <h2><?php esc_html_e( 'Your form toolkit', 'usp-form-builder' ); ?></h2>
                        <p><?php esc_html_e( 'A quick guide to the features available in your edition.', 'usp-form-builder' ); ?></p>
                    </div>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=usp-form-pro' ) ); ?>"><?php esc_html_e( 'Compare editions', 'usp-form-builder' ); ?></a>
                </div>
                <div class="usp-dashboard-feature-grid">
                    <article>
                        <span class="dashicons dashicons-edit" aria-hidden="true"></span>
                        <h3><?php esc_html_e( 'Visual Form Builder', 'usp-form-builder' ); ?></h3>
                        <p><?php esc_html_e( 'Create and style forms with templates, conditional logic, responsive layouts, and live preview.', 'usp-form-builder' ); ?></p>
                        <span class="usp-dashboard-feature-badge is-free"><?php esc_html_e( 'Available', 'usp-form-builder' ); ?></span>
                    </article>
                    <article>
                        <span class="dashicons dashicons-clipboard" aria-hidden="true"></span>
                        <h3><?php esc_html_e( 'Entries & Notifications', 'usp-form-builder' ); ?></h3>
                        <p><?php esc_html_e( 'Review submissions, export entries, configure email notifications, and connect webhooks.', 'usp-form-builder' ); ?></p>
                        <span class="usp-dashboard-feature-badge is-free"><?php esc_html_e( 'Available', 'usp-form-builder' ); ?></span>
                    </article>
                    <article>
                        <span class="dashicons dashicons-upload" aria-hidden="true"></span>
                        <h3><?php esc_html_e( 'Pro Field Collection', 'usp-form-builder' ); ?></h3>
                        <p><?php esc_html_e( 'Use protected file uploads, ratings, consent, country and nationality, addresses, and global phone fields.', 'usp-form-builder' ); ?></p>
                        <span class="usp-dashboard-feature-badge <?php echo $is_pro ? 'is-active' : 'is-pro'; ?>"><?php echo $is_pro ? esc_html__( 'Pro active', 'usp-form-builder' ) : esc_html__( 'Pro', 'usp-form-builder' ); ?></span>
                    </article>
                    <article>
                        <span class="dashicons dashicons-editor-break" aria-hidden="true"></span>
                        <h3><?php esc_html_e( 'Advanced Workflows', 'usp-form-builder' ); ?></h3>
                        <p><?php esc_html_e( 'Build multi-step forms, digital signatures, and calculated fields. Payment fields are not a payment processor.', 'usp-form-builder' ); ?></p>
                        <span class="usp-dashboard-feature-badge <?php echo $is_pro ? 'is-active' : 'is-pro'; ?>"><?php echo $is_pro ? esc_html__( 'Pro active', 'usp-form-builder' ) : esc_html__( 'Pro', 'usp-form-builder' ); ?></span>
                    </article>
                    <article>
                        <span class="dashicons dashicons-shield" aria-hidden="true"></span>
                        <h3><?php esc_html_e( 'Spam & Upload Security', 'usp-form-builder' ); ?></h3>
                        <p><?php esc_html_e( 'Configure spam defenses, submission limits, file checks, and the upload sandbox.', 'usp-form-builder' ); ?></p>
                        <span class="usp-dashboard-feature-badge <?php echo ! empty( $security_settings['enable_rate_limiting'] ) ? 'is-active' : 'is-free'; ?>">
                            <?php echo ! empty( $security_settings['enable_rate_limiting'] ) ? esc_html__( 'Rate limiting on', 'usp-form-builder' ) : esc_html__( 'Configure', 'usp-form-builder' ); ?>
                        </span>
                    </article>
                    <article>
                        <span class="dashicons dashicons-chart-bar" aria-hidden="true"></span>
                        <h3><?php esc_html_e( 'Analytics & Export', 'usp-form-builder' ); ?></h3>
                        <p><?php esc_html_e( 'Track form views and submissions, inspect conversion rates, and export form or entry data.', 'usp-form-builder' ); ?></p>
                        <span class="usp-dashboard-feature-badge is-free"><?php esc_html_e( 'Available', 'usp-form-builder' ); ?></span>
                    </article>
                </div>
            </section>

            <section class="usp-dashboard-card">
                <div class="usp-dashboard-section-heading">
                    <div>
                        <h2><?php esc_html_e( 'Quick actions', 'usp-form-builder' ); ?></h2>
                        <p><?php esc_html_e( 'Jump directly to common tasks.', 'usp-form-builder' ); ?></p>
                    </div>
                </div>
                <div class="usp-dashboard-quick-links">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=usp-form-builder-new' ) ); ?>"><span class="dashicons dashicons-plus-alt2"></span><?php esc_html_e( 'Create a form', 'usp-form-builder' ); ?></a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=usp-form-builder' ) ); ?>"><span class="dashicons dashicons-list-view"></span><?php esc_html_e( 'Manage forms', 'usp-form-builder' ); ?></a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=usp-form-entries' ) ); ?>"><span class="dashicons dashicons-email-alt"></span><?php esc_html_e( 'Review submissions', 'usp-form-builder' ); ?></a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=usp-form-pro' ) ); ?>"><span class="dashicons dashicons-admin-settings"></span><?php esc_html_e( 'License & security', 'usp-form-builder' ); ?></a>
                </div>
            </section>
        </main>

        <aside class="usp-dashboard-sidebar">
            <section class="usp-dashboard-card usp-dashboard-license-card">
                <span class="dashicons <?php echo $is_pro ? 'dashicons-awards' : 'dashicons-lock'; ?>" aria-hidden="true"></span>
                <h2><?php echo $is_pro ? esc_html__( 'USP Form Builder Pro', 'usp-form-builder' ) : esc_html__( 'Free Edition', 'usp-form-builder' ); ?></h2>
                <p><?php echo $is_pro ? ( ! empty( $security_settings['license_error'] ) ? esc_html__( 'License is active using recent verification; the license server could not be reached on its last check.', 'usp-form-builder' ) : esc_html__( 'Your Pro license is active on this site.', 'usp-form-builder' ) ) : esc_html__( 'Upgrade to unlock Pro fields and advanced workflows.', 'usp-form-builder' ); ?></p>
                <?php if ( ! empty( $security_settings['license_expires'] ) ) : ?>
                    <p class="usp-dashboard-license-meta"><?php printf( esc_html__( 'License expiry: %s', 'usp-form-builder' ), esc_html( $security_settings['license_expires'] ) ); ?></p>
                <?php endif; ?>
                <a class="button <?php echo $is_pro ? 'button-secondary' : 'button-primary'; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=usp-form-pro' ) ); ?>">
                    <?php echo $is_pro ? esc_html__( 'Manage License', 'usp-form-builder' ) : esc_html__( 'View Pro & Activate', 'usp-form-builder' ); ?>
                </a>
            </section>

            <section class="usp-dashboard-card usp-dashboard-account-card">
                <h2><?php esc_html_e( 'Your profile', 'usp-form-builder' ); ?></h2>
                <div class="usp-dashboard-profile">
                    <?php echo get_avatar( $current_user->ID, 48 ); ?>
                    <div>
                        <strong><?php echo esc_html( $current_user->display_name ); ?></strong>
                        <span><?php echo esc_html( $current_user->user_email ); ?></span>
                    </div>
                </div>
                <a href="<?php echo esc_url( admin_url( 'profile.php' ) ); ?>"><?php esc_html_e( 'Manage WordPress profile', 'usp-form-builder' ); ?> &rarr;</a>
                <?php if ( ! empty( $security_settings['edd_account_url'] ) ) : ?>
                    <p><a href="<?php echo esc_url( $security_settings['edd_account_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open USP customer account', 'usp-form-builder' ); ?> &rarr;</a></p>
                <?php endif; ?>
            </section>

            <section class="usp-dashboard-card usp-dashboard-pricing-card">
                <span class="dashicons dashicons-star-filled" aria-hidden="true"></span>
                <h2><?php esc_html_e( 'Plans & pricing', 'usp-form-builder' ); ?></h2>
                <p><?php esc_html_e( 'Visit the USP website for current Pro plan details, pricing, and purchase options.', 'usp-form-builder' ); ?></p>
                <a class="button" href="<?php echo esc_url( ! empty( $security_settings['edd_pricing_url'] ) ? $security_settings['edd_pricing_url'] : 'https://usp.com.pk/' ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View Pro plans', 'usp-form-builder' ); ?></a>
            </section>
        </aside>
    </div>
</div>
