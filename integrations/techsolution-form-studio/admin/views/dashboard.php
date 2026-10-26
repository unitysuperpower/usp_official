<?php
/**
 * View: TechSolution Form Studio home dashboard.
 *
 * @package    TSFS_Plugin
 * @subpackage TSFS_Plugin/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Included from a TSFS_Admin method, so these variables are local to that method, not globals.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$forms              = is_array( $forms ) ? $forms : array();
$total_forms        = count( $forms );
$total_entries      = TSFS_DB::get_entry_count();
$total_views        = TSFS_DB::get_total_views();
$conversion_rate    = $total_views > 0 ? round( ( $total_entries / $total_views ) * 100, 1 ) : 0;
$has_pro            = defined( 'TSFS_PRO_VERSION' );
$security_settings  = TSFS_Security::get_settings();
$current_user       = wp_get_current_user();
$published_forms    = 0;

foreach ( $forms as $form ) {
    if ( empty( $form['status'] ) || 'publish' === $form['status'] ) {
        $published_forms++;
    }
}
?>

<div class="wrap tsfs-admin-wrap tsfs-dashboard-wrap">
    <header class="tsfs-dashboard-hero">
        <div>
            <span class="tsfs-dashboard-eyebrow"><?php esc_html_e( 'FORM BUILDER WORKSPACE', 'techsolution-form-studio' ); ?></span>
            <h1><?php esc_html_e( 'Welcome to TechSolution Form Studio', 'techsolution-form-studio' ); ?></h1>
            <p class="tsfs-dashboard-version">
                <?php esc_html_e( 'Installed version:', 'techsolution-form-studio' ); ?>
                <strong><?php echo esc_html( TSFS_VERSION ); ?></strong>
                <?php if ( $has_pro ) : ?>
                    &middot; <?php esc_html_e( 'Pro add-on active', 'techsolution-form-studio' ); ?>
                <?php endif; ?>
            </p>
            <p><?php esc_html_e( 'Build forms, review submissions, and manage spam protection and privacy from one place.', 'techsolution-form-studio' ); ?></p>
        </div>
        <div class="tsfs-dashboard-hero-actions">
            <a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=tsfs-builder' ) ); ?>">
                <span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
                <?php esc_html_e( 'Create a Form', 'techsolution-form-studio' ); ?>
            </a>
            <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=tsfs-forms' ) ); ?>">
                <?php esc_html_e( 'View All Forms', 'techsolution-form-studio' ); ?>
            </a>
        </div>
    </header>

    <section class="tsfs-dashboard-stats" aria-label="<?php esc_attr_e( 'Form statistics', 'techsolution-form-studio' ); ?>">
        <a class="tsfs-dashboard-stat" href="<?php echo esc_url( admin_url( 'admin.php?page=tsfs-forms' ) ); ?>">
            <span class="dashicons dashicons-feedback" aria-hidden="true"></span>
            <span class="tsfs-dashboard-stat-label"><?php esc_html_e( 'Forms', 'techsolution-form-studio' ); ?></span>
            <strong><?php echo esc_html( number_format_i18n( $total_forms ) ); ?></strong>
            <small><?php
            /* translators: %s: number of published forms */
            echo esc_html( sprintf( __( '%s published', 'techsolution-form-studio' ), number_format_i18n( $published_forms ) ) );
            ?></small>
        </a>
        <a class="tsfs-dashboard-stat" href="<?php echo esc_url( admin_url( 'admin.php?page=tsfs-entries' ) ); ?>">
            <span class="dashicons dashicons-email-alt" aria-hidden="true"></span>
            <span class="tsfs-dashboard-stat-label"><?php esc_html_e( 'Submissions', 'techsolution-form-studio' ); ?></span>
            <strong><?php echo esc_html( number_format_i18n( $total_entries ) ); ?></strong>
            <small><?php esc_html_e( 'Entries received', 'techsolution-form-studio' ); ?></small>
        </a>
        <div class="tsfs-dashboard-stat">
            <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
            <span class="tsfs-dashboard-stat-label"><?php esc_html_e( 'Form Views', 'techsolution-form-studio' ); ?></span>
            <strong><?php echo esc_html( number_format_i18n( $total_views ) ); ?></strong>
            <small><?php esc_html_e( 'Total impressions', 'techsolution-form-studio' ); ?></small>
        </div>
        <div class="tsfs-dashboard-stat">
            <span class="dashicons dashicons-chart-area" aria-hidden="true"></span>
            <span class="tsfs-dashboard-stat-label"><?php esc_html_e( 'Conversion Rate', 'techsolution-form-studio' ); ?></span>
            <strong><?php echo esc_html( $conversion_rate ); ?>%</strong>
            <small><?php esc_html_e( 'Submissions divided by views', 'techsolution-form-studio' ); ?></small>
        </div>
    </section>

    <div class="tsfs-dashboard-columns">
        <main class="tsfs-dashboard-main">
            <section class="tsfs-dashboard-card">
                <div class="tsfs-dashboard-section-heading">
                    <div>
                        <h2><?php esc_html_e( 'Your form toolkit', 'techsolution-form-studio' ); ?></h2>
                        <p><?php esc_html_e( 'A quick guide to what you can build.', 'techsolution-form-studio' ); ?></p>
                    </div>
                </div>
                <div class="tsfs-dashboard-feature-grid">
                    <article>
                        <span class="dashicons dashicons-edit" aria-hidden="true"></span>
                        <h3><?php esc_html_e( 'Visual Form Builder', 'techsolution-form-studio' ); ?></h3>
                        <p><?php esc_html_e( 'Create and style forms with templates, conditional logic, responsive layouts, and live preview.', 'techsolution-form-studio' ); ?></p>
                        <span class="tsfs-dashboard-feature-badge is-free"><?php esc_html_e( 'Available', 'techsolution-form-studio' ); ?></span>
                    </article>
                    <article>
                        <span class="dashicons dashicons-clipboard" aria-hidden="true"></span>
                        <h3><?php esc_html_e( 'Entries & Notifications', 'techsolution-form-studio' ); ?></h3>
                        <p><?php esc_html_e( 'Review submissions, export entries, configure email notifications, and connect webhooks.', 'techsolution-form-studio' ); ?></p>
                        <span class="tsfs-dashboard-feature-badge is-free"><?php esc_html_e( 'Available', 'techsolution-form-studio' ); ?></span>
                    </article>
                    <article>
                        <span class="dashicons dashicons-upload" aria-hidden="true"></span>
                        <h3><?php esc_html_e( 'Advanced Fields', 'techsolution-form-studio' ); ?></h3>
                        <p><?php esc_html_e( 'Protected file uploads, star ratings, sliders, consent checkboxes, and country or nationality lists.', 'techsolution-form-studio' ); ?></p>
                        <span class="tsfs-dashboard-feature-badge is-free"><?php esc_html_e( 'Available', 'techsolution-form-studio' ); ?></span>
                    </article>
                    <article>
                        <span class="dashicons dashicons-editor-break" aria-hidden="true"></span>
                        <h3><?php esc_html_e( 'Multi-Step & Logic', 'techsolution-form-studio' ); ?></h3>
                        <p><?php esc_html_e( 'Split long forms into steps with a progress bar, and show or hide fields with conditional rules.', 'techsolution-form-studio' ); ?></p>
                        <span class="tsfs-dashboard-feature-badge is-free"><?php esc_html_e( 'Available', 'techsolution-form-studio' ); ?></span>
                    </article>
                    <article>
                        <span class="dashicons dashicons-shield" aria-hidden="true"></span>
                        <h3><?php esc_html_e( 'Spam & Upload Security', 'techsolution-form-studio' ); ?></h3>
                        <p><?php esc_html_e( 'Configure spam defenses, submission limits, file checks, and the upload sandbox.', 'techsolution-form-studio' ); ?></p>
                        <span class="tsfs-dashboard-feature-badge <?php echo ! empty( $security_settings['enable_rate_limiting'] ) ? 'is-active' : 'is-free'; ?>">
                            <?php echo ! empty( $security_settings['enable_rate_limiting'] ) ? esc_html__( 'Rate limiting on', 'techsolution-form-studio' ) : esc_html__( 'Configure', 'techsolution-form-studio' ); ?>
                        </span>
                    </article>
                    <article>
                        <span class="dashicons dashicons-chart-bar" aria-hidden="true"></span>
                        <h3><?php esc_html_e( 'Analytics & Export', 'techsolution-form-studio' ); ?></h3>
                        <p><?php esc_html_e( 'Track form views and submissions, inspect conversion rates, and export form or entry data.', 'techsolution-form-studio' ); ?></p>
                        <span class="tsfs-dashboard-feature-badge is-free"><?php esc_html_e( 'Available', 'techsolution-form-studio' ); ?></span>
                    </article>
                </div>
            </section>

            <section class="tsfs-dashboard-card">
                <div class="tsfs-dashboard-section-heading">
                    <div>
                        <h2><?php esc_html_e( 'Quick actions', 'techsolution-form-studio' ); ?></h2>
                        <p><?php esc_html_e( 'Jump directly to common tasks.', 'techsolution-form-studio' ); ?></p>
                    </div>
                </div>
                <div class="tsfs-dashboard-quick-links">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=tsfs-builder' ) ); ?>"><span class="dashicons dashicons-plus-alt2"></span><?php esc_html_e( 'Create a form', 'techsolution-form-studio' ); ?></a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=tsfs-forms' ) ); ?>"><span class="dashicons dashicons-list-view"></span><?php esc_html_e( 'Manage forms', 'techsolution-form-studio' ); ?></a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=tsfs-entries' ) ); ?>"><span class="dashicons dashicons-email-alt"></span><?php esc_html_e( 'Review submissions', 'techsolution-form-studio' ); ?></a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=tsfs-settings' ) ); ?>"><span class="dashicons dashicons-admin-settings"></span><?php esc_html_e( 'Spam & privacy settings', 'techsolution-form-studio' ); ?></a>
                </div>
            </section>
        </main>

        <aside class="tsfs-dashboard-sidebar">
            <section class="tsfs-dashboard-card tsfs-dashboard-license-card">
                <span class="dashicons dashicons-embed-post" aria-hidden="true"></span>
                <h2><?php esc_html_e( 'Add a form to a page', 'techsolution-form-studio' ); ?></h2>
                <p><?php esc_html_e( 'In the block editor, add the “Form Studio Form” block and choose your form. In other editors, paste the shortcode shown in All Forms, for example [tsfs_form id="1"].', 'techsolution-form-studio' ); ?></p>
            </section>

            <section class="tsfs-dashboard-card tsfs-dashboard-account-card">
                <h2><?php esc_html_e( 'Your profile', 'techsolution-form-studio' ); ?></h2>
                <div class="tsfs-dashboard-profile">
                    <?php echo get_avatar( $current_user->ID, 48 ); ?>
                    <div>
                        <strong><?php echo esc_html( $current_user->display_name ); ?></strong>
                        <span><?php echo esc_html( $current_user->user_email ); ?></span>
                    </div>
                </div>
                <a href="<?php echo esc_url( admin_url( 'profile.php' ) ); ?>"><?php esc_html_e( 'Manage WordPress profile', 'techsolution-form-studio' ); ?> &rarr;</a>
            </section>

            <?php if ( ! $has_pro ) : ?>
            <section class="tsfs-dashboard-card tsfs-dashboard-pricing-card">
                <span class="dashicons dashicons-admin-plugins" aria-hidden="true"></span>
                <h2><?php esc_html_e( 'Pro add-on', 'techsolution-form-studio' ); ?></h2>
                <p><?php esc_html_e( 'A separate plugin with address, international phone, signature, calculation, payment details and admission fields.', 'techsolution-form-studio' ); ?></p>
                <a class="button" href="<?php echo esc_url( TSFS_PRO_URL ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Learn more', 'techsolution-form-studio' ); ?></a>
            </section>
            <?php endif; ?>
        </aside>
    </div>
</div>
