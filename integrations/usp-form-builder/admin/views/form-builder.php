<?php
/**
 * View: Visual Form Builder Studio with Templates, Conditional Logic, and Advanced Inputs.
 *
 * @package    USP_Form_Builder
 * @subpackage USP_Form_Builder/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$is_edit         = ! empty( $form && ! empty( $form['id'] ) );
$current_form_id = $is_edit ? (int) $form['id'] : 0;
$form_title      = $is_edit ? $form['title'] : __( 'New Custom Form', 'usp-form-builder' );
$shortcode       = $is_edit ? '[usp_form id="' . $current_form_id . '"]' : '';
?>

<script>
(function() {
    document.documentElement.classList.add('usp-builder-view');
    document.body.classList.add('usp-builder-view');
})();
</script>

<div class="wrap usp-builder-wrap">
    <!-- Top Bar -->
    <header class="usp-builder-header">
        <div class="usp-header-left">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=usp-form-builder' ) ); ?>" class="usp-btn-back" title="<?php esc_attr_e( 'Back to All Forms', 'usp-form-builder' ); ?>">
                <span class="dashicons dashicons-arrow-left-alt2"></span>
            </a>
            <div class="usp-title-editor-wrap">
                <input type="text" id="usp-form-title-input" class="usp-form-title-input" value="<?php echo esc_attr( $form_title ); ?>" placeholder="<?php esc_attr_e( 'Enter form title...', 'usp-form-builder' ); ?>" />
                <span class="dashicons dashicons-edit usp-title-edit-icon" title="<?php esc_attr_e( 'Click to edit title', 'usp-form-builder' ); ?>"></span>
            </div>
            <?php if ( $is_edit ) : ?>
                <div class="usp-shortcode-tag" title="<?php esc_attr_e( 'Click to copy shortcode', 'usp-form-builder' ); ?>" data-shortcode="<?php echo esc_attr( $shortcode ); ?>">
                    <code><?php echo esc_html( $shortcode ); ?></code>
                    <span class="dashicons dashicons-admin-page"></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Center: Responsive Preview Device Switcher -->
        <div class="usp-header-center">
            <div class="usp-device-switcher">
                <button type="button" class="usp-device-btn active" data-device="desktop" title="<?php esc_attr_e( 'Desktop View (100%)', 'usp-form-builder' ); ?>">
                    <span class="dashicons dashicons-desktop"></span>
                    <span><?php esc_html_e( 'Desktop', 'usp-form-builder' ); ?></span>
                </button>
                <button type="button" class="usp-device-btn" data-device="tablet" title="<?php esc_attr_e( 'Tablet View (480px)', 'usp-form-builder' ); ?>">
                    <span class="dashicons dashicons-tablet"></span>
                    <span><?php esc_html_e( 'Tablet', 'usp-form-builder' ); ?></span>
                </button>
                <button type="button" class="usp-device-btn" data-device="mobile" title="<?php esc_attr_e( 'Mobile View (360px)', 'usp-form-builder' ); ?>">
                    <span class="dashicons dashicons-smartphone"></span>
                    <span><?php esc_html_e( 'Mobile', 'usp-form-builder' ); ?></span>
                </button>
            </div>
        </div>

        <div class="usp-header-right">
            <span id="usp-save-status" class="usp-save-status" role="status" aria-live="polite"></span>
            <!-- Undo / Redo Controls -->
            <button type="button" class="usp-header-btn usp-btn-outline" id="usp-btn-undo" title="<?php esc_attr_e( 'Undo (Ctrl+Z)', 'usp-form-builder' ); ?>" disabled>
                <span class="dashicons dashicons-undo"></span>
            </button>
            <button type="button" class="usp-header-btn usp-btn-outline" id="usp-btn-redo" title="<?php esc_attr_e( 'Redo (Ctrl+Y)', 'usp-form-builder' ); ?>" disabled>
                <span class="dashicons dashicons-redo"></span>
            </button>

            <!-- In-Studio Live Preview Button -->
            <button type="button" class="usp-header-btn usp-btn-outline" id="usp-open-preview-btn" title="<?php esc_attr_e( 'Test Interactive Live Preview', 'usp-form-builder' ); ?>">
                <span class="dashicons dashicons-visibility"></span>
                <span><?php esc_html_e( 'Live Preview', 'usp-form-builder' ); ?></span>
            </button>

            <!-- 1-Click Templates Button -->
            <button type="button" class="usp-header-btn usp-btn-outline" id="usp-open-templates-btn">
                <span class="dashicons dashicons-layout"></span>
                <span><?php esc_html_e( 'Templates', 'usp-form-builder' ); ?></span>
            </button>

            <!-- Embed Modal Button -->
            <button type="button" class="usp-header-btn usp-btn-outline" id="usp-open-embed-btn">
                <span class="dashicons dashicons-embed-post"></span>
                <span><?php esc_html_e( 'Embed', 'usp-form-builder' ); ?></span>
            </button>

            <!-- Export JSON Button -->
            <button type="button" class="usp-header-btn usp-btn-outline" id="usp-export-json-btn" title="<?php esc_attr_e( 'Download Form Definition as JSON', 'usp-form-builder' ); ?>">
                <span class="dashicons dashicons-download"></span>
                <span><?php esc_html_e( 'Export', 'usp-form-builder' ); ?></span>
            </button>

            <!-- Save Form Button -->
            <button type="button" class="usp-header-btn usp-btn-primary" id="usp-save-form-btn" title="<?php esc_attr_e( 'Save form (Ctrl+S / Cmd+S)', 'usp-form-builder' ); ?>">
                <span class="dashicons dashicons-saved"></span> 
                <span class="usp-btn-label"><?php esc_html_e( 'Save Form', 'usp-form-builder' ); ?></span>
                <span class="spinner" style="float:none; margin:0 0 0 5px;"></span>
            </button>
        </div>
    </header>

    <!-- Main Studio Layout -->
    <div class="usp-builder-studio">
        <!-- 1. Left Sidebar: Field Palette -->
        <aside class="usp-palette-sidebar">
            <div class="usp-sidebar-header">
                <h3><?php esc_html_e( 'Form tools', 'usp-form-builder' ); ?></h3>
                <p class="description"><?php esc_html_e( 'Add new fields or find fields already in your form.', 'usp-form-builder' ); ?></p>
                <div class="usp-palette-navigation" role="group" aria-label="<?php esc_attr_e( 'Builder tools', 'usp-form-builder' ); ?>">
                    <button type="button" class="usp-palette-panel-btn active" data-panel="add" aria-pressed="true" aria-controls="usp-add-fields-panel"><?php esc_html_e( 'Add fields', 'usp-form-builder' ); ?></button>
                    <button type="button" class="usp-palette-panel-btn" data-panel="outline" aria-pressed="false" aria-controls="usp-outline-panel"><?php esc_html_e( 'Form outline', 'usp-form-builder' ); ?></button>
                </div>
                <div class="usp-palette-search-wrap">
                    <span class="dashicons dashicons-search"></span>
                    <input type="search" id="usp-palette-search-input" aria-label="<?php esc_attr_e( 'Search available fields', 'usp-form-builder' ); ?>" placeholder="<?php esc_attr_e( 'Search fields...', 'usp-form-builder' ); ?>" autocomplete="off" />
                </div>
            </div>

            <div id="usp-outline-panel" class="usp-outline-panel" hidden>
                <div class="usp-outline-search-wrap">
                    <label for="usp-outline-search"><?php esc_html_e( 'Find a field in your form', 'usp-form-builder' ); ?></label>
                    <input type="search" id="usp-outline-search" placeholder="<?php esc_attr_e( 'Search label, type, or key…', 'usp-form-builder' ); ?>" autocomplete="off">
                    <p id="usp-outline-count" role="status" aria-live="polite"></p>
                </div>
                <ol id="usp-outline-list" class="usp-outline-list"></ol>
                <p id="usp-outline-empty" class="usp-outline-empty" role="status" hidden></p>
            </div>
            <div class="usp-palette-scrollable" id="usp-add-fields-panel">
                <p id="usp-palette-no-results" class="usp-palette-no-results" role="status" hidden><?php esc_html_e( 'No matching fields. Try “email”, “choice”, or “upload”.', 'usp-form-builder' ); ?></p>
                <div class="usp-fields-group">
                    <h4><?php esc_html_e( 'Standard Fields', 'usp-form-builder' ); ?></h4>
                    <div class="usp-palette-grid">
                        <button type="button" class="usp-palette-item" data-type="text">
                            <span class="usp-palette-icon is-standard"><span class="dashicons dashicons-editor-textcolor"></span></span>
                            <span class="title"><?php esc_html_e( 'Single Line', 'usp-form-builder' ); ?></span>
                        </button>
                        <button type="button" class="usp-palette-item" data-type="email">
                            <span class="usp-palette-icon is-standard"><span class="dashicons dashicons-email-alt"></span></span>
                            <span class="title"><?php esc_html_e( 'Email', 'usp-form-builder' ); ?></span>
                        </button>
                        <button type="button" class="usp-palette-item" data-type="textarea">
                            <span class="usp-palette-icon is-standard"><span class="dashicons dashicons-editor-paragraph"></span></span>
                            <span class="title"><?php esc_html_e( 'Paragraph', 'usp-form-builder' ); ?></span>
                        </button>
                        <button type="button" class="usp-palette-item" data-type="number">
                            <span class="usp-palette-icon is-standard"><span class="dashicons dashicons-calculator"></span></span>
                            <span class="title"><?php esc_html_e( 'Number', 'usp-form-builder' ); ?></span>
                        </button>
                        <button type="button" class="usp-palette-item" data-type="tel">
                            <span class="usp-palette-icon is-standard"><span class="dashicons dashicons-phone"></span></span>
                            <span class="title"><?php esc_html_e( 'Phone', 'usp-form-builder' ); ?></span>
                        </button>
                        <button type="button" class="usp-palette-item" data-type="date">
                            <span class="usp-palette-icon is-standard"><span class="dashicons dashicons-calendar-alt"></span></span>
                            <span class="title"><?php esc_html_e( 'Date', 'usp-form-builder' ); ?></span>
                        </button>
                        <button type="button" class="usp-palette-item" data-type="time">
                            <span class="usp-palette-icon is-standard"><span class="dashicons dashicons-clock"></span></span>
                            <span class="title"><?php esc_html_e( 'Time', 'usp-form-builder' ); ?></span>
                        </button>
                        <button type="button" class="usp-palette-item" data-type="url">
                            <span class="usp-palette-icon is-standard"><span class="dashicons dashicons-admin-links"></span></span>
                            <span class="title"><?php esc_html_e( 'Website URL', 'usp-form-builder' ); ?></span>
                        </button>
                    </div>
                </div>

                <div class="usp-fields-group">
                    <h4><?php esc_html_e( 'Advanced & Uploads', 'usp-form-builder' ); ?></h4>
                    <div class="usp-palette-grid">
                        <button type="button" class="usp-palette-item" data-type="hidden">
                            <span class="usp-palette-icon is-advanced"><span class="dashicons dashicons-hidden"></span></span>
                            <span class="title"><?php esc_html_e( 'Hidden Field', 'usp-form-builder' ); ?></span>
                        </button>
                    </div>
                </div>

                <div class="usp-fields-group">
                    <h4><?php esc_html_e( 'Choice Fields', 'usp-form-builder' ); ?></h4>
                    <div class="usp-palette-grid">
                        <button type="button" class="usp-palette-item" data-type="select">
                            <span class="usp-palette-icon is-choice"><span class="dashicons dashicons-arrow-down-alt2"></span></span>
                            <span class="title"><?php esc_html_e( 'Dropdown', 'usp-form-builder' ); ?></span>
                        </button>
                        <button type="button" class="usp-palette-item" data-type="radio">
                            <span class="usp-palette-icon is-choice"><span class="dashicons dashicons-marker"></span></span>
                            <span class="title"><?php esc_html_e( 'Radio', 'usp-form-builder' ); ?></span>
                        </button>
                        <button type="button" class="usp-palette-item" data-type="checkbox">
                            <span class="usp-palette-icon is-choice"><span class="dashicons dashicons-yes-alt"></span></span>
                            <span class="title"><?php esc_html_e( 'Checkboxes', 'usp-form-builder' ); ?></span>
                        </button>
                    </div>
                </div>

                <div class="usp-fields-group">
                    <h4><?php esc_html_e( 'Structure', 'usp-form-builder' ); ?></h4>
                    <div class="usp-palette-grid">
                        <button type="button" class="usp-palette-item" data-type="cnic">
                            <span class="usp-palette-icon is-standard"><span class="dashicons dashicons-id"></span></span>
                            <span class="title"><?php esc_html_e( 'CNIC / Form-B', 'usp-form-builder' ); ?></span>
                        </button>
                        <button type="button" class="usp-palette-item" data-type="content_heading">
                            <span class="usp-palette-icon is-standard"><span class="dashicons dashicons-heading"></span></span>
                            <span class="title"><?php esc_html_e( 'Heading', 'usp-form-builder' ); ?></span>
                        </button>
                        <button type="button" class="usp-palette-item" data-type="content_paragraph">
                            <span class="usp-palette-icon is-standard"><span class="dashicons dashicons-editor-paragraph"></span></span>
                            <span class="title"><?php esc_html_e( 'Paragraph', 'usp-form-builder' ); ?></span>
                        </button>
                        <button type="button" class="usp-palette-item" data-type="content_bullets">
                            <span class="usp-palette-icon is-standard"><span class="dashicons dashicons-editor-ul"></span></span>
                            <span class="title"><?php esc_html_e( 'Bulleted List', 'usp-form-builder' ); ?></span>
                        </button>
                        <button type="button" class="usp-palette-item" data-type="content_numbered">
                            <span class="usp-palette-icon is-standard"><span class="dashicons dashicons-editor-ol"></span></span>
                            <span class="title"><?php esc_html_e( 'Numbered List', 'usp-form-builder' ); ?></span>
                        </button>
                        <button type="button" class="usp-palette-item" data-type="html">
                            <span class="usp-palette-icon is-structure"><span class="dashicons dashicons-heading"></span></span>
                            <span class="title"><?php esc_html_e( 'Section Note', 'usp-form-builder' ); ?></span>
                        </button>
                    </div>
                </div>

                <div class="usp-fields-group usp-pro-features-teaser">
                    <h4>
                        <span class="dashicons dashicons-star-filled" style="color: #d97706; font-size:15px;"></span> 
                        <?php esc_html_e( 'Pro Fields', 'usp-form-builder' ); ?>
                        <?php if ( USP_Security::is_pro_active() ) : ?>
                            <span style="font-size:10px; background:#059669; color:#fff; font-weight:700; padding:2px 6px; border-radius:10px; margin-left:6px;"><?php esc_html_e( 'ACTIVE', 'usp-form-builder' ); ?></span>
                        <?php endif; ?>
                    </h4>
                    <?php $is_builder_pro = USP_Security::is_pro_active(); ?>
                    <div class="usp-palette-grid <?php echo $is_builder_pro ? '' : 'usp-disabled-grid'; ?>">
                        <button type="button" class="usp-palette-item <?php echo $is_builder_pro ? '' : 'usp-pro-locked'; ?>" data-type="file" data-keywords="upload attachment document">
                            <span class="usp-palette-icon is-pro"><span class="dashicons dashicons-upload"></span></span>
                            <span class="title"><?php esc_html_e( 'File Upload', 'usp-form-builder' ); ?></span>
                        </button>
                        <button type="button" class="usp-palette-item <?php echo $is_builder_pro ? '' : 'usp-pro-locked'; ?>" data-type="rating">
                            <span class="usp-palette-icon is-pro"><span class="dashicons dashicons-star-filled"></span></span>
                            <span class="title"><?php esc_html_e( 'Star Rating', 'usp-form-builder' ); ?></span>
                        </button>
                        <button type="button" class="usp-palette-item <?php echo $is_builder_pro ? '' : 'usp-pro-locked'; ?>" data-type="consent" data-keywords="gdpr terms privacy agreement consent">
                            <span class="usp-palette-icon is-pro"><span class="dashicons dashicons-shield"></span></span>
                            <span class="title"><?php esc_html_e( 'Terms / GDPR Consent', 'usp-form-builder' ); ?></span>
                        </button>
                        <button type="button" class="usp-palette-item <?php echo $is_builder_pro ? '' : 'usp-pro-locked'; ?> usp-country-palette-item" data-type="country" data-keywords="country countries citizenship location residence">
                            <span class="usp-palette-icon is-pro"><span class="dashicons dashicons-admin-site-alt3"></span></span>
                            <span class="title"><?php esc_html_e( 'Country', 'usp-form-builder' ); ?></span>
                        </button>
                        <button type="button" class="usp-palette-item <?php echo $is_builder_pro ? '' : 'usp-pro-locked'; ?> usp-country-palette-item" data-type="nationality" data-keywords="nationality citizenship country countries">
                            <span class="usp-palette-icon is-pro"><span class="dashicons dashicons-id-alt"></span></span>
                            <span class="title"><?php esc_html_e( 'Nationality', 'usp-form-builder' ); ?></span>
                        </button>
                        <button type="button" class="usp-palette-item <?php echo $is_builder_pro ? '' : 'usp-pro-locked'; ?>" data-type="range">
                            <span class="usp-palette-icon is-pro"><span class="dashicons dashicons-leftright"></span></span>
                            <span class="title"><?php esc_html_e( 'Rating Slider', 'usp-form-builder' ); ?></span>
                        </button>
                        <button type="button" class="usp-palette-item <?php echo $is_builder_pro ? '' : 'usp-pro-locked'; ?>" data-type="page_break">
                            <span class="usp-palette-icon is-pro"><span class="dashicons dashicons-editor-break"></span></span>
                            <span class="title"><?php esc_html_e( 'Multi-Step Forms', 'usp-form-builder' ); ?></span>
                        </button>
                        <button type="button" class="usp-palette-item <?php echo $is_builder_pro ? '' : 'usp-pro-locked'; ?>" data-type="signature">
                            <span class="usp-palette-icon is-pro"><span class="dashicons dashicons-edit"></span></span>
                            <span class="title"><?php esc_html_e( 'Digital Signature', 'usp-form-builder' ); ?></span>
                        </button>
                        <button type="button" class="usp-palette-item <?php echo $is_builder_pro ? '' : 'usp-pro-locked'; ?>" data-type="calculation">
                            <span class="usp-palette-icon is-pro"><span class="dashicons dashicons-calculator"></span></span>
                            <span class="title"><?php esc_html_e( 'Calculated Field', 'usp-form-builder' ); ?></span>
                        </button>
                        <button type="button" class="usp-palette-item <?php echo $is_builder_pro ? '' : 'usp-pro-locked'; ?>" data-type="address" data-keywords="complete address country province state city postal zip street" title="<?php esc_attr_e( 'Pro: linked country, province, and city with street address', 'usp-form-builder' ); ?>">
                            <span class="usp-palette-icon is-pro"><span class="dashicons dashicons-location-alt"></span></span>
                            <span class="title"><?php esc_html_e( 'Complete Address', 'usp-form-builder' ); ?></span>
                        </button>
                        <button type="button" class="usp-palette-item <?php echo $is_builder_pro ? '' : 'usp-pro-locked'; ?>" data-type="admission" title="<?php echo $is_builder_pro ? esc_attr__( 'Add University Admission Form', 'usp-form-builder' ) : esc_attr__( 'Available in USP Form Builder Pro', 'usp-form-builder' ); ?>">
                            <span class="usp-palette-icon is-pro"><span class="dashicons dashicons-welcome-learn-more"></span></span>
                            <span class="title"><?php esc_html_e( 'Univ. Admission', 'usp-form-builder' ); ?></span>
                        </button>
                        <button type="button" class="usp-palette-item <?php echo $is_builder_pro ? '' : 'usp-pro-locked'; ?>" data-type="intl_tel" title="<?php echo $is_builder_pro ? esc_attr__( 'Add Global Phone with Flags', 'usp-form-builder' ) : esc_attr__( 'Available in USP Form Builder Pro', 'usp-form-builder' ); ?>">
                            <span class="usp-palette-icon is-pro"><span class="dashicons dashicons-translation"></span></span>
                            <span class="title"><?php esc_html_e( 'Global Phone', 'usp-form-builder' ); ?></span>
                        </button>
                        <button type="button" class="usp-palette-item <?php echo $is_builder_pro ? '' : 'usp-pro-locked'; ?>" data-type="payment" title="<?php echo $is_builder_pro ? esc_attr__( 'Add a payment details field; transactions are not processed', 'usp-form-builder' ) : esc_attr__( 'Available in USP Form Builder Pro', 'usp-form-builder' ); ?>">
                            <span class="usp-palette-icon is-pro"><span class="dashicons dashicons-cart"></span></span>
                            <span class="title"><?php esc_html_e( 'Payment Details', 'usp-form-builder' ); ?></span>
                        </button>
                    </div>
                </div>
            </div>
        </aside>

        <!-- 2. Center: Form Canvas -->
        <main class="usp-canvas-area">
            <div class="usp-canvas-container" id="usp-canvas-container">
                <div class="usp-canvas-form-header">
                    <div class="usp-canvas-status-pill">
                        <span class="usp-status-dot"></span> <?php esc_html_e( 'Live Preview', 'usp-form-builder' ); ?>
                    </div>
                    <h2 id="usp-canvas-title-preview"><?php echo esc_html( $form_title ); ?></h2>
                </div>

                <!-- Dropzone / Fields List -->
                <div class="usp-canvas-fields-list" id="usp-canvas-fields-list">
                    <!-- Dynamic fields rendered here by JS -->
                </div>

                <!-- Empty State within Canvas -->
                <div class="usp-canvas-empty" id="usp-canvas-empty" style="display:none;">
                    <div class="usp-canvas-empty-content">
                        <span class="dashicons dashicons-move"></span>
                        <h3><?php esc_html_e( 'Your form is empty', 'usp-form-builder' ); ?></h3>
                        <p><?php esc_html_e( 'Click on any field from the left sidebar or load a pre-built template.', 'usp-form-builder' ); ?></p>
                    </div>
                </div>

                <!-- Canvas Submit Button Preview -->
                <div class="usp-canvas-footer">
                    <button type="button" class="usp-canvas-submit-btn" id="usp-canvas-submit-preview">
                        <?php 
                        $btn_txt = ! empty( $form['settings']['submit_button_text'] ) ? $form['settings']['submit_button_text'] : __( 'Submit', 'usp-form-builder' );
                        echo esc_html( $btn_txt ); 
                        ?>
                    </button>
                </div>
            </div>
        </main>

        <!-- 3. Right Sidebar: Inspector (Field, Settings, Style) -->
        <aside class="usp-inspector-sidebar">
            <div class="usp-inspector-tabs">
                <button type="button" class="usp-tab-btn active" data-tab="field-settings" title="<?php esc_attr_e( 'Field Configuration', 'usp-form-builder' ); ?>">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <span class="usp-tab-text"><?php esc_html_e( 'Field', 'usp-form-builder' ); ?></span>
                </button>
                <button type="button" class="usp-tab-btn" data-tab="form-settings" title="<?php esc_attr_e( 'Form Settings & Notifications', 'usp-form-builder' ); ?>">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <span class="usp-tab-text"><?php esc_html_e( 'Settings', 'usp-form-builder' ); ?></span>
                </button>
                <button type="button" class="usp-tab-btn" data-tab="theme-style" title="<?php esc_attr_e( 'Theme & Appearance', 'usp-form-builder' ); ?>">
                    <span class="dashicons dashicons-art"></span>
                    <span class="usp-tab-text"><?php esc_html_e( 'Style', 'usp-form-builder' ); ?></span>
                </button>
            </div>

            <!-- Field Settings Tab Panel -->
            <div class="usp-tab-panel active" id="usp-panel-field-settings">
                <div id="usp-field-settings-content">
                    <div class="usp-empty-inspector">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        <p><?php esc_html_e( 'Select a field on the canvas to configure its properties & conditional logic.', 'usp-form-builder' ); ?></p>
                    </div>
                </div>
            </div>

            <!-- Form Settings Tab Panel -->
            <div class="usp-tab-panel" id="usp-panel-form-settings">
                <div class="usp-settings-form-group">
                    <label for="usp-setting-btn-text"><?php esc_html_e( 'Submit Button Label', 'usp-form-builder' ); ?></label>
                    <input type="text" id="usp-setting-btn-text" value="<?php echo esc_attr( ! empty( $form['settings']['submit_button_text'] ) ? $form['settings']['submit_button_text'] : __( 'Submit', 'usp-form-builder' ) ); ?>">
                </div>

                <div class="usp-settings-form-group">
                    <label for="usp-setting-success-msg"><?php esc_html_e( 'Success Confirmation Message', 'usp-form-builder' ); ?></label>
                    <textarea id="usp-setting-success-msg" rows="3"><?php echo esc_textarea( ! empty( $form['settings']['success_message'] ) ? $form['settings']['success_message'] : __( 'Thank you! Your submission has been received.', 'usp-form-builder' ) ); ?></textarea>
                </div>

                <div class="usp-settings-form-group">
                    <label for="usp-setting-redirect-url"><?php esc_html_e( 'Redirect URL (Optional)', 'usp-form-builder' ); ?></label>
                    <input type="url" id="usp-setting-redirect-url" placeholder="https://example.com/thank-you" value="<?php echo esc_attr( ! empty( $form['settings']['redirect_url'] ) ? $form['settings']['redirect_url'] : '' ); ?>">
                    <p class="description"><?php esc_html_e( 'Leave empty to display the success message on the current page.', 'usp-form-builder' ); ?></p>
                </div>

                <div class="usp-toggle-row" style="margin-top:10px;">
                    <div class="usp-toggle-info">
                        <span class="usp-toggle-title"><?php esc_html_e( 'Forward Data via Query String', 'usp-form-builder' ); ?></span>
                        <span class="usp-toggle-desc"><?php esc_html_e( 'Pass entry_id, form_id, and fields to the redirect URL', 'usp-form-builder' ); ?></span>
                    </div>
                    <label class="usp-toggle-switch">
                        <input type="checkbox" id="usp-setting-redirect-pass-vars" <?php checked( ! empty( $form['settings']['redirect_pass_vars'] ) ); ?>>
                        <span class="usp-toggle-slider"></span>
                    </label>
                </div>

                <hr style="margin: 22px 0; border: 0; border-top: 1px solid var(--usp-adm-slate-200);">

                <h4 style="margin:0 0 14px 0; font-size:13px; font-weight:700; color:var(--usp-adm-slate-900); text-transform:uppercase; letter-spacing:0.5px;"><?php esc_html_e( 'Admin Email Notifications', 'usp-form-builder' ); ?></h4>

                <div class="usp-toggle-row">
                    <div class="usp-toggle-info">
                        <span class="usp-toggle-title"><?php esc_html_e( 'Admin Notification Email', 'usp-form-builder' ); ?></span>
                        <span class="usp-toggle-desc"><?php esc_html_e( 'Send entry alerts to your designated email', 'usp-form-builder' ); ?></span>
                    </div>
                    <label class="usp-toggle-switch">
                        <input type="checkbox" id="usp-setting-send-email" <?php checked( ! empty( $form['settings']['send_email'] ) || ! $is_edit ); ?>>
                        <span class="usp-toggle-slider"></span>
                    </label>
                </div>

                <div class="usp-settings-form-group" id="usp-email-target-wrap" style="margin-top:14px;">
                    <label for="usp-setting-admin-email"><?php esc_html_e( 'Recipient Email Address(es)', 'usp-form-builder' ); ?></label>
                    <input type="text" id="usp-setting-admin-email" value="<?php echo esc_attr( ! empty( $form['settings']['notification_email'] ) ? $form['settings']['notification_email'] : get_option( 'admin_email' ) ); ?>">
                    <p class="description"><?php esc_html_e( 'Separate multiple email recipients with commas, e.g. admin@site.com, alerts@site.com', 'usp-form-builder' ); ?></p>
                </div>

                <div class="usp-settings-form-group">
                    <label for="usp-setting-email-subject"><?php esc_html_e( 'Email Subject', 'usp-form-builder' ); ?></label>
                    <input type="text" id="usp-setting-email-subject" placeholder="New submission for {form_title} [#{entry_id}]" value="<?php echo esc_attr( ! empty( $form['settings']['email_subject'] ) ? $form['settings']['email_subject'] : '' ); ?>">
                    <p class="description"><?php esc_html_e( 'Available tags: {form_title}, {entry_id}, {user_name}, {user_email}, {date}', 'usp-form-builder' ); ?></p>
                </div>

                <hr style="margin: 22px 0; border: 0; border-top: 1px solid var(--usp-adm-slate-200);">

                <h4 style="margin:0 0 14px 0; font-size:13px; font-weight:700; color:var(--usp-adm-slate-900); text-transform:uppercase; letter-spacing:0.5px;"><?php esc_html_e( 'User Auto-Responder', 'usp-form-builder' ); ?></h4>

                <div class="usp-toggle-row">
                    <div class="usp-toggle-info">
                        <span class="usp-toggle-title"><?php esc_html_e( 'Send Auto-Reply to Submitter', 'usp-form-builder' ); ?></span>
                        <span class="usp-toggle-desc"><?php esc_html_e( 'Immediately acknowledge receipt of form', 'usp-form-builder' ); ?></span>
                    </div>
                    <label class="usp-toggle-switch">
                        <input type="checkbox" id="usp-setting-user-autoreply" <?php checked( ! empty( $form['settings']['send_user_auto_reply'] ) ); ?>>
                        <span class="usp-toggle-slider"></span>
                    </label>
                </div>

                <div class="usp-settings-form-group" id="usp-autoreply-fields-wrap" style="margin-top:14px;">
                    <label for="usp-setting-autoreply-subj"><?php esc_html_e( 'Auto-Reply Subject', 'usp-form-builder' ); ?></label>
                    <input type="text" id="usp-setting-autoreply-subj" value="<?php echo esc_attr( ! empty( $form['settings']['auto_reply_subject'] ) ? $form['settings']['auto_reply_subject'] : __( 'Thank you for your submission!', 'usp-form-builder' ) ); ?>">
                    
                    <label for="usp-setting-autoreply-msg" style="margin-top:10px;"><?php esc_html_e( 'Auto-Reply Message', 'usp-form-builder' ); ?></label>
                    <textarea id="usp-setting-autoreply-msg" rows="3"><?php echo esc_textarea( ! empty( $form['settings']['auto_reply_message'] ) ? $form['settings']['auto_reply_message'] : __( "Hello,\n\nWe have received your submission and will get back to you shortly.\n\nBest regards,\nSupport Team", 'usp-form-builder' ) ); ?></textarea>
                </div>

                <hr style="margin: 22px 0; border: 0; border-top: 1px solid var(--usp-adm-slate-200);">

                <h4 style="margin:0 0 14px 0; font-size:13px; font-weight:700; color:var(--usp-adm-slate-900); text-transform:uppercase; letter-spacing:0.5px;"><?php esc_html_e( 'Restrictions & Scheduling', 'usp-form-builder' ); ?></h4>

                <div class="usp-toggle-row">
                    <div class="usp-toggle-info">
                        <span class="usp-toggle-title"><?php esc_html_e( 'Limit Total Submissions', 'usp-form-builder' ); ?></span>
                        <span class="usp-toggle-desc"><?php esc_html_e( 'Automatically close after maximum cap', 'usp-form-builder' ); ?></span>
                    </div>
                    <label class="usp-toggle-switch">
                        <input type="checkbox" id="usp-setting-limit-submissions" <?php checked( ! empty( $form['settings']['limit_submissions'] ) ); ?>>
                        <span class="usp-toggle-slider"></span>
                    </label>
                </div>

                <div class="usp-settings-form-group" id="usp-limit-entries-wrap" style="<?php echo empty( $form['settings']['limit_submissions'] ) ? 'display:none;' : ''; ?> margin-top:14px;">
                    <label for="usp-setting-max-entries"><?php esc_html_e( 'Maximum Submissions Allowed', 'usp-form-builder' ); ?></label>
                    <input type="number" id="usp-setting-max-entries" min="1" value="<?php echo esc_attr( ! empty( $form['settings']['max_entries'] ) ? $form['settings']['max_entries'] : 100 ); ?>">
                    <p class="description"><?php esc_html_e( 'The form will stop accepting submissions once this cap is met.', 'usp-form-builder' ); ?></p>
                </div>

                <div class="usp-toggle-row">
                    <div class="usp-toggle-info">
                        <span class="usp-toggle-title"><?php esc_html_e( 'Schedule Expiration Date', 'usp-form-builder' ); ?></span>
                        <span class="usp-toggle-desc"><?php esc_html_e( 'Set an automatic closing deadline', 'usp-form-builder' ); ?></span>
                    </div>
                    <label class="usp-toggle-switch">
                        <input type="checkbox" id="usp-setting-close-date-toggle" <?php checked( ! empty( $form['settings']['close_after_date'] ) ); ?>>
                        <span class="usp-toggle-slider"></span>
                    </label>
                </div>

                <div class="usp-settings-form-group" id="usp-close-date-wrap" style="<?php echo empty( $form['settings']['close_after_date'] ) ? 'display:none;' : ''; ?> margin-top:14px;">
                    <label for="usp-setting-close-date"><?php esc_html_e( 'Close Form At', 'usp-form-builder' ); ?></label>
                    <input type="datetime-local" id="usp-setting-close-date" value="<?php echo esc_attr( ! empty( $form['settings']['close_date'] ) ? $form['settings']['close_date'] : '' ); ?>">
                    
                    <label for="usp-setting-closed-msg" style="margin-top:10px;"><?php esc_html_e( 'Closed Form Message', 'usp-form-builder' ); ?></label>
                    <textarea id="usp-setting-closed-msg" rows="2"><?php echo esc_textarea( ! empty( $form['settings']['closed_message'] ) ? $form['settings']['closed_message'] : __( 'This form is currently closed for submissions.', 'usp-form-builder' ) ); ?></textarea>
                </div>

                <div class="usp-toggle-row">
                    <div class="usp-toggle-info">
                        <span class="usp-toggle-title"><?php esc_html_e( 'Require User Login', 'usp-form-builder' ); ?></span>
                        <span class="usp-toggle-desc"><?php esc_html_e( 'Only logged-in members can submit', 'usp-form-builder' ); ?></span>
                    </div>
                    <label class="usp-toggle-switch">
                        <input type="checkbox" id="usp-setting-require-login" <?php checked( ! empty( $form['settings']['require_login'] ) ); ?>>
                        <span class="usp-toggle-slider"></span>
                    </label>
                </div>

                <div class="usp-settings-form-group" id="usp-login-msg-wrap" style="<?php echo empty( $form['settings']['require_login'] ) ? 'display:none;' : ''; ?> margin-top:14px;">
                    <label for="usp-setting-login-msg"><?php esc_html_e( 'Login Required Message', 'usp-form-builder' ); ?></label>
                    <textarea id="usp-setting-login-msg" rows="2"><?php echo esc_textarea( ! empty( $form['settings']['login_message'] ) ? $form['settings']['login_message'] : __( 'You must be logged in to submit this form.', 'usp-form-builder' ) ); ?></textarea>
                </div>

                <hr style="margin: 22px 0; border: 0; border-top: 1px solid var(--usp-adm-slate-200);">

                <h4 style="margin:0 0 14px 0; font-size:13px; font-weight:700; color:var(--usp-adm-slate-900); text-transform:uppercase; letter-spacing:0.5px; display:flex; align-items:center; justify-content:space-between;">
                    <span><?php esc_html_e( 'Webhooks & Integrations', 'usp-form-builder' ); ?></span>
                    <span style="font-size:10px; background:linear-gradient(135deg, #10b981 0%, #059669 100%); color:#ffffff; font-weight:700; padding:2px 7px; border-radius:10px;">PRO</span>
                </h4>

                <div class="usp-toggle-row">
                    <div class="usp-toggle-info">
                        <span class="usp-toggle-title"><?php esc_html_e( 'Enable Webhook Dispatch', 'usp-form-builder' ); ?></span>
                        <span class="usp-toggle-desc"><?php esc_html_e( 'Send real-time JSON submission payloads to external apps', 'usp-form-builder' ); ?></span>
                    </div>
                    <label class="usp-toggle-switch">
                        <input type="checkbox" id="usp-setting-enable-webhook" <?php checked( ! empty( $form['settings']['enable_webhook'] ) ); ?>>
                        <span class="usp-toggle-slider"></span>
                    </label>
                </div>

                <div class="usp-settings-form-group" id="usp-webhook-fields-wrap" style="<?php echo empty( $form['settings']['enable_webhook'] ) ? 'display:none;' : ''; ?> margin-top:14px;">
                    <label for="usp-setting-webhook-url"><?php esc_html_e( 'Webhook Endpoint URL', 'usp-form-builder' ); ?></label>
                    <input type="url" id="usp-setting-webhook-url" placeholder="https://hooks.zapier.com/hooks/catch/..." value="<?php echo esc_attr( ! empty( $form['settings']['webhook_url'] ) ? $form['settings']['webhook_url'] : '' ); ?>">
                    <p class="description"><?php esc_html_e( 'Connect with Zapier, Make, n8n, Slack, or any REST API endpoint.', 'usp-form-builder' ); ?></p>

                    <label for="usp-setting-webhook-secret" style="margin-top:10px;"><?php esc_html_e( 'Secret Key / HMAC Token (Optional)', 'usp-form-builder' ); ?></label>
                    <input type="text" id="usp-setting-webhook-secret" placeholder="usp_secret_..." value="<?php echo esc_attr( ! empty( $form['settings']['webhook_secret'] ) ? $form['settings']['webhook_secret'] : '' ); ?>">
                    <p class="description"><?php esc_html_e( 'Transmitted in the X-USP-Signature (SHA-256) and Authorization headers.', 'usp-form-builder' ); ?></p>

                    <div style="margin-top:14px; display:flex; align-items:center; gap:8px;">
                        <button type="button" class="button button-secondary" id="usp-test-webhook-btn" style="border-radius:6px; font-weight:600;">
                            <span class="dashicons dashicons-networking" style="vertical-align:middle; margin-top:-2px;"></span> <?php esc_html_e( 'Send Test Payload', 'usp-form-builder' ); ?>
                        </button>
                        <span id="usp-test-webhook-status" style="font-size:12px; font-weight:600;"></span>
                    </div>
                </div>
            </div>

            <!-- 3. Theme & Styling Tab Panel -->
            <?php
            $f_settings     = ! empty( $form['settings'] ) && is_array( $form['settings'] ) ? $form['settings'] : array();
            $theme_preset   = ! empty( $f_settings['theme_preset'] ) ? $f_settings['theme_preset'] : 'default';
            $primary_color  = ! empty( $f_settings['primary_color'] ) ? $f_settings['primary_color'] : '#4f46e5';
            $btn_text_color = ! empty( $f_settings['btn_text_color'] ) ? $f_settings['btn_text_color'] : '#ffffff';
            $container_bg   = ! empty( $f_settings['container_bg'] ) ? $f_settings['container_bg'] : '#ffffff';
            $border_radius  = ! empty( $f_settings['border_radius'] ) ? $f_settings['border_radius'] : 'rounded';
            $button_align   = ! empty( $f_settings['button_align'] ) ? $f_settings['button_align'] : 'left';
            $shadow_style   = ! empty( $f_settings['shadow_style'] ) ? $f_settings['shadow_style'] : 'medium';
            $custom_css     = ! empty( $f_settings['custom_css'] ) ? $f_settings['custom_css'] : '';
            ?>
            <div class="usp-tab-panel" id="usp-panel-theme-style">
                <h4 style="margin:0 0 4px 0; font-size:14px;"><?php esc_html_e( 'Theme Presets', 'usp-form-builder' ); ?></h4>
                <p class="description" style="margin-bottom:12px;"><?php esc_html_e( 'Select a pre-designed theme aesthetic for your form.', 'usp-form-builder' ); ?></p>
                
                <div class="usp-theme-cards-grid">
                    <div class="usp-theme-card <?php echo 'default' === $theme_preset ? 'active' : ''; ?>" data-preset="default" data-color="#4f46e5" data-bg="#ffffff" data-btn="#ffffff">
                        <span class="usp-theme-swatch" style="background:#4f46e5;"></span>
                        <div class="usp-theme-card-info">
                            <div><?php esc_html_e( 'Modern Indigo', 'usp-form-builder' ); ?></div>
                            <small style="color:var(--usp-adm-slate-400); font-weight:normal;"><?php esc_html_e( 'Default SaaS', 'usp-form-builder' ); ?></small>
                        </div>
                    </div>

                    <div class="usp-theme-card <?php echo 'minimal' === $theme_preset ? 'active' : ''; ?>" data-preset="minimal" data-color="#0f172a" data-bg="#ffffff" data-btn="#ffffff">
                        <span class="usp-theme-swatch" style="background:#0f172a;"></span>
                        <div class="usp-theme-card-info">
                            <div><?php esc_html_e( 'Minimalist', 'usp-form-builder' ); ?></div>
                            <small style="color:var(--usp-adm-slate-400); font-weight:normal;"><?php esc_html_e( 'Monochrome', 'usp-form-builder' ); ?></small>
                        </div>
                    </div>

                    <div class="usp-theme-card <?php echo 'dark' === $theme_preset ? 'active' : ''; ?>" data-preset="dark" data-color="#38bdf8" data-bg="#0f172a" data-btn="#0f172a">
                        <span class="usp-theme-swatch" style="background:#0f172a; border-color:#38bdf8;"></span>
                        <div class="usp-theme-card-info">
                            <div><?php esc_html_e( 'Midnight Dark', 'usp-form-builder' ); ?></div>
                            <small style="color:var(--usp-adm-slate-400); font-weight:normal;"><?php esc_html_e( 'High contrast', 'usp-form-builder' ); ?></small>
                        </div>
                    </div>

                    <div class="usp-theme-card <?php echo 'emerald' === $theme_preset ? 'active' : ''; ?>" data-preset="emerald" data-color="#059669" data-bg="#ffffff" data-btn="#ffffff">
                        <span class="usp-theme-swatch" style="background:#059669;"></span>
                        <div class="usp-theme-card-info">
                            <div><?php esc_html_e( 'Emerald Eco', 'usp-form-builder' ); ?></div>
                            <small style="color:var(--usp-adm-slate-400); font-weight:normal;"><?php esc_html_e( 'Fresh Green', 'usp-form-builder' ); ?></small>
                        </div>
                    </div>

                    <div class="usp-theme-card <?php echo 'sunset' === $theme_preset ? 'active' : ''; ?>" data-preset="sunset" data-color="#e11d48" data-bg="#ffffff" data-btn="#ffffff">
                        <span class="usp-theme-swatch" style="background:#e11d48;"></span>
                        <div class="usp-theme-card-info">
                            <div><?php esc_html_e( 'Sunset Rose', 'usp-form-builder' ); ?></div>
                            <small style="color:var(--usp-adm-slate-400); font-weight:normal;"><?php esc_html_e( 'Crimson Warm', 'usp-form-builder' ); ?></small>
                        </div>
                    </div>

                    <div class="usp-theme-card <?php echo 'violet' === $theme_preset ? 'active' : ''; ?>" data-preset="violet" data-color="#7c3aed" data-bg="#ffffff" data-btn="#ffffff">
                        <span class="usp-theme-swatch" style="background:#7c3aed;"></span>
                        <div class="usp-theme-card-info">
                            <div><?php esc_html_e( 'Royal Violet', 'usp-form-builder' ); ?></div>
                            <small style="color:var(--usp-adm-slate-400); font-weight:normal;"><?php esc_html_e( 'Luxury Purple', 'usp-form-builder' ); ?></small>
                        </div>
                    </div>
                </div>

                <input type="hidden" id="usp-theme-preset-val" value="<?php echo esc_attr( $theme_preset ); ?>">

                <hr style="margin: 20px 0; border: 0; border-top: 1px solid var(--usp-adm-slate-200);">

                <h4 style="margin:0 0 12px 0; font-size:14px;"><?php esc_html_e( 'Color Customization', 'usp-form-builder' ); ?></h4>

                <div class="usp-settings-form-group">
                    <label for="usp-theme-primary-color"><?php esc_html_e( 'Accent & Button Color', 'usp-form-builder' ); ?></label>
                    <div class="usp-color-row">
                        <input type="color" id="usp-theme-primary-picker" value="<?php echo esc_attr( $primary_color ); ?>">
                        <input type="text" id="usp-theme-primary-color" value="<?php echo esc_attr( $primary_color ); ?>" placeholder="#4f46e5">
                    </div>
                </div>

                <div class="usp-settings-form-group">
                    <label for="usp-theme-btn-color"><?php esc_html_e( 'Button Text Color', 'usp-form-builder' ); ?></label>
                    <div class="usp-color-row">
                        <input type="color" id="usp-theme-btn-picker" value="<?php echo esc_attr( $btn_text_color ); ?>">
                        <input type="text" id="usp-theme-btn-color" value="<?php echo esc_attr( $btn_text_color ); ?>" placeholder="#ffffff">
                    </div>
                </div>

                <div class="usp-settings-form-group">
                    <label for="usp-theme-bg-color"><?php esc_html_e( 'Form Background Color', 'usp-form-builder' ); ?></label>
                    <div class="usp-color-row">
                        <input type="color" id="usp-theme-bg-picker" value="<?php echo esc_attr( $container_bg ); ?>">
                        <input type="text" id="usp-theme-bg-color" value="<?php echo esc_attr( $container_bg ); ?>" placeholder="#ffffff">
                    </div>
                </div>

                <hr style="margin: 20px 0; border: 0; border-top: 1px solid var(--usp-adm-slate-200);">

                <h4>Form size &amp; spacing</h4>
                <p class="description">Sizes are in pixels. The form stays within the screen width and grows to fit its content.</p>
                <?php $layout_values = USP_Form::layout_settings( $form['settings'] ?? array() ); ?>
                <div class="usp-settings-form-group">
                    <label for="usp-layout-form_max_width">Form maximum width</label>
                    <input type="number" id="usp-layout-form_max_width" data-layout-setting="form_max_width" min="280" max="1600" step="1" value="<?php echo esc_attr( $layout_values['form_max_width'] ); ?>">
                </div>
                <div class="usp-settings-form-group">
                    <label for="usp-layout-form_min_height">Form minimum height (0 = automatic)</label>
                    <input type="number" id="usp-layout-form_min_height" data-layout-setting="form_min_height" min="0" max="1600" step="1" value="<?php echo esc_attr( $layout_values['form_min_height'] ); ?>">
                </div>
                <div class="usp-settings-form-group">
                    <label for="usp-layout-form_padding">Form inner padding</label>
                    <input type="number" id="usp-layout-form_padding" data-layout-setting="form_padding" min="0" max="80" step="1" value="<?php echo esc_attr( $layout_values['form_padding'] ); ?>">
                </div>
                <div class="usp-settings-form-group">
                    <label for="usp-layout-field_gap">Space between fields</label>
                    <input type="number" id="usp-layout-field_gap" data-layout-setting="field_gap" min="0" max="64" step="1" value="<?php echo esc_attr( $layout_values['field_gap'] ); ?>">
                </div>
                <div class="usp-settings-form-group">
                    <label for="usp-layout-input_height">Input minimum height</label>
                    <input type="number" id="usp-layout-input_height" data-layout-setting="input_height" min="32" max="100" step="1" value="<?php echo esc_attr( $layout_values['input_height'] ); ?>">
                </div>
                <div class="usp-settings-form-group">
                    <label for="usp-layout-button_width">Button width (0 = automatic)</label>
                    <input type="number" id="usp-layout-button_width" data-layout-setting="button_width" min="0" max="1000" step="1" value="<?php echo esc_attr( $layout_values['button_width'] ); ?>">
                </div>
                <div class="usp-settings-form-group">
                    <label for="usp-layout-button_height">Button minimum height</label>
                    <input type="number" id="usp-layout-button_height" data-layout-setting="button_height" min="32" max="120" step="1" value="<?php echo esc_attr( $layout_values['button_height'] ); ?>">
                </div>
                <div class="usp-settings-form-group">
                    <label for="usp-layout-button_padding">Button horizontal padding</label>
                    <input type="number" id="usp-layout-button_padding" data-layout-setting="button_padding" min="0" max="80" step="1" value="<?php echo esc_attr( $layout_values['button_padding'] ); ?>">
                </div>

                <h4 style="margin:0 0 12px 0; font-size:14px;"><?php esc_html_e( 'Form Shape & Corners', 'usp-form-builder' ); ?></h4>

                <div class="usp-settings-form-group">
                    <label><?php esc_html_e( 'Corner Radius', 'usp-form-builder' ); ?></label>
                    <div class="usp-width-options" style="display:grid; grid-template-columns: repeat(4, 1fr); gap:6px;">
                        <button type="button" class="usp-radius-opt <?php echo 'sharp' === $border_radius ? 'active' : ''; ?>" data-radius="sharp" style="padding:7px 4px; font-size:11.5px; border-radius:3px;">
                            <?php esc_html_e( 'Sharp', 'usp-form-builder' ); ?>
                        </button>
                        <button type="button" class="usp-radius-opt <?php echo 'slight' === $border_radius ? 'active' : ''; ?>" data-radius="slight" style="padding:7px 4px; font-size:11.5px; border-radius:6px;">
                            <?php esc_html_e( 'Slight', 'usp-form-builder' ); ?>
                        </button>
                        <button type="button" class="usp-radius-opt <?php echo 'rounded' === $border_radius ? 'active' : ''; ?>" data-radius="rounded" style="padding:7px 4px; font-size:11.5px; border-radius:8px;">
                            <?php esc_html_e( 'Round', 'usp-form-builder' ); ?>
                        </button>
                        <button type="button" class="usp-radius-opt <?php echo 'pill' === $border_radius ? 'active' : ''; ?>" data-radius="pill" style="padding:7px 4px; font-size:11.5px; border-radius:14px;">
                            <?php esc_html_e( 'Pill', 'usp-form-builder' ); ?>
                        </button>
                    </div>
                    <input type="hidden" id="usp-theme-radius-val" value="<?php echo esc_attr( $border_radius ); ?>">
                </div>

                <div class="usp-settings-form-group">
                    <label><?php esc_html_e( 'Submit Button Alignment', 'usp-form-builder' ); ?></label>
                    <div class="usp-width-options" style="display:grid; grid-template-columns: repeat(4, 1fr); gap:6px;">
                        <button type="button" class="usp-btn-align-opt <?php echo 'left' === $button_align ? 'active' : ''; ?>" data-align="left" style="padding:7px 4px; font-size:11.5px;">
                            <span class="dashicons dashicons-editor-alignleft" style="font-size:15px; width:15px; height:15px; vertical-align:middle;"></span>
                        </button>
                        <button type="button" class="usp-btn-align-opt <?php echo 'center' === $button_align ? 'active' : ''; ?>" data-align="center" style="padding:7px 4px; font-size:11.5px;">
                            <span class="dashicons dashicons-editor-aligncenter" style="font-size:15px; width:15px; height:15px; vertical-align:middle;"></span>
                        </button>
                        <button type="button" class="usp-btn-align-opt <?php echo 'right' === $button_align ? 'active' : ''; ?>" data-align="right" style="padding:7px 4px; font-size:11.5px;">
                            <span class="dashicons dashicons-editor-alignright" style="font-size:15px; width:15px; height:15px; vertical-align:middle;"></span>
                        </button>
                        <button type="button" class="usp-btn-align-opt <?php echo 'full' === $button_align ? 'active' : ''; ?>" data-align="full" style="padding:7px 4px; font-size:11.5px;">
                            <span class="dashicons dashicons-editor-justify" style="font-size:15px; width:15px; height:15px; vertical-align:middle;"></span>
                        </button>
                    </div>
                    <input type="hidden" id="usp-theme-button-align-val" value="<?php echo esc_attr( $button_align ); ?>">
                </div>

                <div class="usp-settings-form-group">
                    <label for="usp-theme-shadow-val"><?php esc_html_e( 'Container Shadow', 'usp-form-builder' ); ?></label>
                    <select id="usp-theme-shadow-val">
                        <option value="none" <?php selected( 'none', $shadow_style ); ?>><?php esc_html_e( 'None (Flat)', 'usp-form-builder' ); ?></option>
                        <option value="subtle" <?php selected( 'subtle', $shadow_style ); ?>><?php esc_html_e( 'Subtle Border Shadow', 'usp-form-builder' ); ?></option>
                        <option value="medium" <?php selected( 'medium', $shadow_style ); ?>><?php esc_html_e( 'Medium Clean (Default)', 'usp-form-builder' ); ?></option>
                        <option value="elevated" <?php selected( 'elevated', $shadow_style ); ?>><?php esc_html_e( 'Elevated Floating 3D', 'usp-form-builder' ); ?></option>
                        <option value="outline" <?php selected( 'outline', $shadow_style ); ?>><?php esc_html_e( 'Distinct 2px Outline', 'usp-form-builder' ); ?></option>
                    </select>
                </div>

                <hr style="margin: 20px 0; border: 0; border-top: 1px solid var(--usp-adm-slate-200);">

                <div class="usp-settings-form-group">
                    <label for="usp-theme-custom-css"><?php esc_html_e( 'Scoped Custom CSS', 'usp-form-builder' ); ?></label>
                    <textarea id="usp-theme-custom-css" rows="5" style="font-family:monospace; font-size:12px; line-height:1.4;" placeholder=".usp-form-container {&#10;    /* your styles */&#10;}"><?php echo esc_textarea( $custom_css ); ?></textarea>
                    <p class="description" style="margin-top:6px;"><?php esc_html_e( 'CSS written here is scoped strictly to this specific form ID.', 'usp-form-builder' ); ?></p>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- Templates Picker Modal -->
<div id="usp-templates-modal" class="usp-modal-backdrop" style="display:none;">
    <div class="usp-modal-dialog" style="max-width: 750px;">
        <div class="usp-modal-header">
            <h3><span class="dashicons dashicons-layout" style="vertical-align:middle; color:var(--usp-adm-primary);"></span> <?php esc_html_e( 'Choose a Pre-Built Form Template', 'usp-form-builder' ); ?></h3>
            <button type="button" class="usp-modal-close" id="usp-templates-close-btn">&times;</button>
        </div>
        <div class="usp-modal-body" style="padding: 24px;">
            <p style="color:var(--usp-adm-slate-600); margin-top:0;"><?php esc_html_e( 'Select a template below to jumpstart your form design. You can fully customize fields after loading.', 'usp-form-builder' ); ?></p>
            
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 16px; margin-top: 16px;">
                <!-- Template 1 -->
                <div class="usp-template-card" data-template="contact" style="background:var(--usp-adm-slate-50); border:1.5px solid var(--usp-adm-slate-200); border-radius:8px; padding:18px; cursor:pointer; transition:all 0.15s ease;">
                    <div style="font-size:24px; color:var(--usp-adm-primary); margin-bottom:8px;"><span class="dashicons dashicons-email"></span></div>
                    <h4 style="margin:0 0 6px 0; font-size:15px; font-weight:700;"><?php esc_html_e( 'Simple Contact Form', 'usp-form-builder' ); ?></h4>
                    <p style="margin:0; font-size:12px; color:var(--usp-adm-slate-500);"><?php esc_html_e( 'Name, Email, Subject, and Message fields.', 'usp-form-builder' ); ?></p>
                </div>

                <!-- Template 2 -->
                <div class="usp-template-card" data-template="job_app" style="background:var(--usp-adm-slate-50); border:1.5px solid var(--usp-adm-slate-200); border-radius:8px; padding:18px; cursor:pointer; transition:all 0.15s ease;">
                    <div style="font-size:24px; color:#059669; margin-bottom:8px;"><span class="dashicons dashicons-id-alt"></span></div>
                    <h4 style="margin:0 0 6px 0; font-size:15px; font-weight:700;"><?php esc_html_e( 'Job Application & CV', 'usp-form-builder' ); ?></h4>
                    <p style="margin:0; font-size:12px; color:var(--usp-adm-slate-500);"><?php esc_html_e( 'Includes resume/CV file upload & portfolio URL.', 'usp-form-builder' ); ?></p>
                </div>

                <!-- Template 3 -->
                <div class="usp-template-card" data-template="feedback" style="background:var(--usp-adm-slate-50); border:1.5px solid var(--usp-adm-slate-200); border-radius:8px; padding:18px; cursor:pointer; transition:all 0.15s ease;">
                    <div style="font-size:24px; color:#2563eb; margin-bottom:8px;"><span class="dashicons dashicons-star-filled"></span></div>
                    <h4 style="margin:0 0 6px 0; font-size:15px; font-weight:700;"><?php esc_html_e( 'Feedback & Rating', 'usp-form-builder' ); ?></h4>
                    <p style="margin:0; font-size:12px; color:var(--usp-adm-slate-500);"><?php esc_html_e( 'Satisfaction rating slider, questions, suggestions.', 'usp-form-builder' ); ?></p>
                </div>

                <!-- Template 4 -->
                <div class="usp-template-card" data-template="event_rsvp" style="background:var(--usp-adm-slate-50); border:1.5px solid var(--usp-adm-slate-200); border-radius:8px; padding:18px; cursor:pointer; transition:all 0.15s ease;">
                    <div style="font-size:24px; color:#7c3aed; margin-bottom:8px;"><span class="dashicons dashicons-calendar"></span></div>
                    <h4 style="margin:0 0 6px 0; font-size:15px; font-weight:700;"><?php esc_html_e( 'Event RSVP (Conditional)', 'usp-form-builder' ); ?></h4>
                    <p style="margin:0; font-size:12px; color:var(--usp-adm-slate-500);"><?php esc_html_e( 'Conditional dietary fields triggered by meal selection.', 'usp-form-builder' ); ?></p>
                </div>

                <!-- Template 5: Pro University Admission -->
                <div class="usp-template-card" data-template="admission_pro" style="background:#f0fdf4; border:1.5px solid #86efac; border-radius:8px; padding:18px; cursor:pointer; transition:all 0.15s ease; position:relative;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div style="font-size:24px; color:#15803d; margin-bottom:8px;"><span class="dashicons dashicons-welcome-learn-more"></span></div>
                        <span style="background:#15803d; color:#fff; font-size:10px; font-weight:800; padding:2px 7px; border-radius:10px; letter-spacing:0.5px;">PRO</span>
                    </div>
                    <h4 style="margin:0 0 6px 0; font-size:15px; font-weight:700; color:#166534;"><?php esc_html_e( 'University Admissions', 'usp-form-builder' ); ?></h4>
                    <p style="margin:0; font-size:12px; color:#15803d;"><?php esc_html_e( 'Full student academic profile, global phone, transcript file upload & fee checkout.', 'usp-form-builder' ); ?></p>
                </div>

                <!-- Template 6: Pro Payment & Ticket Checkout -->
                <div class="usp-template-card" data-template="checkout_payment" style="background:#eef2ff; border:1.5px solid #a5b4fc; border-radius:8px; padding:18px; cursor:pointer; transition:all 0.15s ease; position:relative;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div style="font-size:24px; color:var(--usp-adm-primary); margin-bottom:8px;"><span class="dashicons dashicons-cart"></span></div>
                        <span style="background:var(--usp-adm-primary); color:#fff; font-size:10px; font-weight:800; padding:2px 7px; border-radius:10px; letter-spacing:0.5px;">PRO</span>
                    </div>
                    <h4 style="margin:0 0 6px 0; font-size:15px; font-weight:700; color:#3730a3;"><?php esc_html_e( 'Ticket & Fee Checkout', 'usp-form-builder' ); ?></h4>
                    <p style="margin:0; font-size:12px; color:#4338ca;"><?php esc_html_e( 'Attendee info, tier selection, global telephone, and instant credit card payment.', 'usp-form-builder' ); ?></p>
                </div>
            </div>
        </div>
        <div class="usp-modal-footer">
            <button type="button" class="button button-secondary" id="usp-templates-cancel-btn"><?php esc_html_e( 'Cancel', 'usp-form-builder' ); ?></button>
        </div>
    </div>
</div>

<!-- Embed Form Modal -->
<div id="usp-embed-modal" class="usp-modal-backdrop" style="display:none;">
    <div class="usp-modal-dialog" style="max-width: 580px;">
        <div class="usp-modal-header">
            <h3><span class="dashicons dashicons-embed-post" style="vertical-align:middle; color:var(--usp-adm-primary);"></span> <?php esc_html_e( 'Embed This Form', 'usp-form-builder' ); ?></h3>
            <button type="button" class="usp-modal-close" id="usp-embed-close-btn">&times;</button>
        </div>
        <div class="usp-modal-body" style="padding: 24px;">
            <p style="color:var(--usp-adm-slate-600); margin-top:0;">
                <?php esc_html_e( 'Copy the shortcode below and paste it anywhere into WordPress pages, posts, or page builders like Elementor, Gutenberg, & Divi.', 'usp-form-builder' ); ?>
            </p>

            <div style="background:var(--usp-adm-slate-50); border:1.5px solid var(--usp-adm-slate-200); border-radius:10px; padding:16px; margin:16px 0;">
                <label style="display:block; font-size:11px; font-weight:700; color:var(--usp-adm-slate-500); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:8px;">
                    <?php esc_html_e( 'Standard Shortcode', 'usp-form-builder' ); ?>
                </label>
                <div style="display:flex; gap:8px;">
                    <input type="text" readonly id="usp-embed-shortcode-input" value="<?php echo esc_attr( $shortcode ? $shortcode : '[usp_form id="' . $current_form_id . '"]' ); ?>" style="flex:1; font-family:monospace; font-weight:600; font-size:14px; background:#ffffff; border:1.5px solid var(--usp-adm-slate-300); border-radius:6px; padding:6px 12px;">
                    <button type="button" class="button button-primary" id="usp-embed-copy-btn" style="border-radius:6px; font-weight:600;">
                        <span class="dashicons dashicons-admin-page" style="vertical-align:middle; margin-top:-2px;"></span> <?php esc_html_e( 'Copy', 'usp-form-builder' ); ?>
                    </button>
                </div>
            </div>

            <div style="background:#f8fafc; border-left:4px solid var(--usp-adm-primary); padding:14px 16px; border-radius:0 8px 8px 0; font-size:13px; color:var(--usp-adm-slate-600);">
                <strong style="color:var(--usp-adm-slate-800);"><?php esc_html_e( 'PHP Template Tag:', 'usp-form-builder' ); ?></strong><br>
                <code style="display:block; margin-top:6px; background:#ffffff; padding:6px 10px; border-radius:4px; border:1px solid #e2e8f0;">&lt;?php echo do_shortcode( '<?php echo esc_html( $shortcode ? $shortcode : '[usp_form id="' . $current_form_id . '"]' ); ?>' ); ?&gt;</code>
            </div>
        </div>
        <div class="usp-modal-footer">
            <button type="button" class="button button-secondary" id="usp-embed-done-btn"><?php esc_html_e( 'Done', 'usp-form-builder' ); ?></button>
        </div>
    </div>
</div>

<!-- In-Studio Live Interactive Preview Modal -->
<div id="usp-live-preview-modal" class="usp-modal-backdrop" style="display:none;">
    <div class="usp-modal-dialog usp-preview-modal-dialog" style="max-width: 960px; width:95%; height:90vh; display:flex; flex-direction:column; padding:0; overflow:hidden; border-radius:12px; box-shadow:0 25px 50px -12px rgba(0, 0, 0, 0.25);">
        <div class="usp-modal-header" style="padding:14px 20px; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; justify-content:space-between; background:#ffffff; flex-shrink:0;">
            <div style="display:flex; align-items:center; gap:12px;">
                <h3 style="margin:0; font-size:16px; font-weight:700; color:#1e293b; display:flex; align-items:center; gap:8px;">
                    <span class="dashicons dashicons-visibility" style="color:var(--usp-adm-primary);"></span>
                    <?php esc_html_e( 'Interactive Form Preview', 'usp-form-builder' ); ?>
                </h3>
                <span style="font-size:11px; background:#e0e7ff; color:#3730a3; padding:2px 8px; border-radius:12px; font-weight:700;"><?php esc_html_e( 'SIMULATOR', 'usp-form-builder' ); ?></span>
            </div>
            <div class="usp-modal-preview-devices" style="display:flex; background:#f1f5f9; padding:3px; border-radius:8px; gap:4px;">
                <button type="button" class="usp-preview-device-btn active" data-device="desktop" style="border:none; background:#ffffff; padding:5px 12px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:700; display:flex; align-items:center; gap:5px; color:#1e293b; box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                    <span class="dashicons dashicons-desktop" style="font-size:15px; width:15px; height:15px;"></span> <?php esc_html_e( 'Desktop', 'usp-form-builder' ); ?>
                </button>
                <button type="button" class="usp-preview-device-btn" data-device="tablet" style="border:none; background:transparent; padding:5px 12px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600; display:flex; align-items:center; gap:5px; color:#64748b;">
                    <span class="dashicons dashicons-tablet" style="font-size:15px; width:15px; height:15px;"></span> <?php esc_html_e( 'Tablet (768px)', 'usp-form-builder' ); ?>
                </button>
                <button type="button" class="usp-preview-device-btn" data-device="mobile" style="border:none; background:transparent; padding:5px 12px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600; display:flex; align-items:center; gap:5px; color:#64748b;">
                    <span class="dashicons dashicons-smartphone" style="font-size:15px; width:15px; height:15px;"></span> <?php esc_html_e( 'Mobile (380px)', 'usp-form-builder' ); ?>
                </button>
            </div>
            <button type="button" class="usp-modal-close" id="usp-live-preview-close-btn">&times;</button>
        </div>
        <div class="usp-modal-body usp-live-preview-stage" style="flex:1; background:#f8fafc; padding:32px 20px; overflow-y:auto; display:flex; justify-content:center; align-items:flex-start;">
            <div id="usp-live-preview-viewport" class="usp-preview-viewport device-desktop" style="width:100%; max-width:760px; background:#ffffff; border-radius:12px; box-shadow:0 10px 25px -5px rgba(0,0,0,0.08), 0 8px 10px -6px rgba(0,0,0,0.05); padding:32px; transition:max-width 0.22s cubic-bezier(0.4, 0, 0.2, 1); box-sizing:border-box;">
                <!-- Dynamically rendered interactive form elements will appear here -->
            </div>
        </div>
    </div>
</div>

<!-- Toast notification box -->
<div id="usp-toast-box" class="usp-toast-box" style="display:none;"></div>
