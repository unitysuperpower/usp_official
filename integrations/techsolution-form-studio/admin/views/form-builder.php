<?php
/**
 * View: Visual Form Builder Studio with Templates, Conditional Logic, and Advanced Inputs.
 *
 * @package    TSFS_Plugin
 * @subpackage TSFS_Plugin/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Included from a TSFS_Admin method, so these variables are local to that method, not globals.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$is_edit         = ! empty( $form && ! empty( $form['id'] ) );
$current_form_id = $is_edit ? (int) $form['id'] : 0;
$form_title      = $is_edit ? $form['title'] : __( 'New Custom Form', 'techsolution-form-studio' );
$shortcode       = $is_edit ? '[tsfs_form id="' . $current_form_id . '"]' : '';
?>

<div class="wrap tsfs-builder-wrap">
    <!-- Top Bar -->
    <header class="tsfs-builder-header">
        <div class="tsfs-header-left">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=tsfs-forms' ) ); ?>" class="tsfs-btn-back" title="<?php esc_attr_e( 'Back to All Forms', 'techsolution-form-studio' ); ?>">
                <span class="dashicons dashicons-arrow-left-alt2"></span>
            </a>
            <div class="tsfs-title-editor-wrap">
                <input type="text" id="tsfs-form-title-input" class="tsfs-form-title-input" value="<?php echo esc_attr( $form_title ); ?>" placeholder="<?php esc_attr_e( 'Enter form title...', 'techsolution-form-studio' ); ?>" />
                <span class="dashicons dashicons-edit tsfs-title-edit-icon" title="<?php esc_attr_e( 'Click to edit title', 'techsolution-form-studio' ); ?>"></span>
            </div>
            <?php if ( $is_edit ) : ?>
                <div class="tsfs-shortcode-tag" title="<?php esc_attr_e( 'Click to copy shortcode', 'techsolution-form-studio' ); ?>" data-shortcode="<?php echo esc_attr( $shortcode ); ?>">
                    <code><?php echo esc_html( $shortcode ); ?></code>
                    <span class="dashicons dashicons-admin-page"></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Center: Responsive Preview Device Switcher -->
        <div class="tsfs-header-center">
            <div class="tsfs-device-switcher">
                <button type="button" class="tsfs-device-btn active" data-device="desktop" title="<?php esc_attr_e( 'Desktop View (100%)', 'techsolution-form-studio' ); ?>">
                    <span class="dashicons dashicons-desktop"></span>
                    <span><?php esc_html_e( 'Desktop', 'techsolution-form-studio' ); ?></span>
                </button>
                <button type="button" class="tsfs-device-btn" data-device="tablet" title="<?php esc_attr_e( 'Tablet View (480px)', 'techsolution-form-studio' ); ?>">
                    <span class="dashicons dashicons-tablet"></span>
                    <span><?php esc_html_e( 'Tablet', 'techsolution-form-studio' ); ?></span>
                </button>
                <button type="button" class="tsfs-device-btn" data-device="mobile" title="<?php esc_attr_e( 'Mobile View (360px)', 'techsolution-form-studio' ); ?>">
                    <span class="dashicons dashicons-smartphone"></span>
                    <span><?php esc_html_e( 'Mobile', 'techsolution-form-studio' ); ?></span>
                </button>
            </div>
        </div>

        <div class="tsfs-header-right">
            <span id="tsfs-save-status" class="tsfs-save-status" role="status" aria-live="polite"></span>
            <!-- Undo / Redo Controls -->
            <button type="button" class="tsfs-header-btn tsfs-btn-outline" id="tsfs-btn-undo" title="<?php esc_attr_e( 'Undo (Ctrl+Z)', 'techsolution-form-studio' ); ?>" disabled>
                <span class="dashicons dashicons-undo"></span>
            </button>
            <button type="button" class="tsfs-header-btn tsfs-btn-outline" id="tsfs-btn-redo" title="<?php esc_attr_e( 'Redo (Ctrl+Y)', 'techsolution-form-studio' ); ?>" disabled>
                <span class="dashicons dashicons-redo"></span>
            </button>

            <!-- In-Studio Live Preview Button -->
            <button type="button" class="tsfs-header-btn tsfs-btn-outline" id="tsfs-open-preview-btn" title="<?php esc_attr_e( 'Test Interactive Live Preview', 'techsolution-form-studio' ); ?>">
                <span class="dashicons dashicons-visibility"></span>
                <span><?php esc_html_e( 'Live Preview', 'techsolution-form-studio' ); ?></span>
            </button>

            <!-- 1-Click Templates Button -->
            <button type="button" class="tsfs-header-btn tsfs-btn-outline" id="tsfs-open-templates-btn">
                <span class="dashicons dashicons-layout"></span>
                <span><?php esc_html_e( 'Templates', 'techsolution-form-studio' ); ?></span>
            </button>

            <!-- Embed Modal Button -->
            <button type="button" class="tsfs-header-btn tsfs-btn-outline" id="tsfs-open-embed-btn">
                <span class="dashicons dashicons-embed-post"></span>
                <span><?php esc_html_e( 'Embed', 'techsolution-form-studio' ); ?></span>
            </button>

            <!-- Export JSON Button -->
            <button type="button" class="tsfs-header-btn tsfs-btn-outline" id="tsfs-export-json-btn" title="<?php esc_attr_e( 'Download Form Definition as JSON', 'techsolution-form-studio' ); ?>">
                <span class="dashicons dashicons-download"></span>
                <span><?php esc_html_e( 'Export', 'techsolution-form-studio' ); ?></span>
            </button>

            <!-- Save Form Button -->
            <button type="button" class="tsfs-header-btn tsfs-btn-primary" id="tsfs-save-form-btn" title="<?php esc_attr_e( 'Save form (Ctrl+S / Cmd+S)', 'techsolution-form-studio' ); ?>">
                <span class="dashicons dashicons-saved"></span> 
                <span class="tsfs-btn-label"><?php esc_html_e( 'Save Form', 'techsolution-form-studio' ); ?></span>
                <span class="spinner" style="float:none; margin:0 0 0 5px;"></span>
            </button>
        </div>
    </header>

    <!-- Main Studio Layout -->
    <div class="tsfs-builder-studio">
        <!-- 1. Left Sidebar: Field Palette -->
        <aside class="tsfs-palette-sidebar">
            <div class="tsfs-sidebar-header">
                <h3><?php esc_html_e( 'Form tools', 'techsolution-form-studio' ); ?></h3>
                <p class="description"><?php esc_html_e( 'Add new fields or find fields already in your form.', 'techsolution-form-studio' ); ?></p>
                <div class="tsfs-palette-navigation" role="group" aria-label="<?php esc_attr_e( 'Builder tools', 'techsolution-form-studio' ); ?>">
                    <button type="button" class="tsfs-palette-panel-btn active" data-panel="add" aria-pressed="true" aria-controls="tsfs-add-fields-panel"><?php esc_html_e( 'Add fields', 'techsolution-form-studio' ); ?></button>
                    <button type="button" class="tsfs-palette-panel-btn" data-panel="outline" aria-pressed="false" aria-controls="tsfs-outline-panel"><?php esc_html_e( 'Form outline', 'techsolution-form-studio' ); ?></button>
                </div>
                <div class="tsfs-palette-search-wrap">
                    <span class="dashicons dashicons-search"></span>
                    <input type="search" id="tsfs-palette-search-input" aria-label="<?php esc_attr_e( 'Search available fields', 'techsolution-form-studio' ); ?>" placeholder="<?php esc_attr_e( 'Search fields...', 'techsolution-form-studio' ); ?>" autocomplete="off" />
                </div>
            </div>

            <div id="tsfs-outline-panel" class="tsfs-outline-panel" hidden>
                <div class="tsfs-outline-search-wrap">
                    <label for="tsfs-outline-search"><?php esc_html_e( 'Find a field in your form', 'techsolution-form-studio' ); ?></label>
                    <input type="search" id="tsfs-outline-search" placeholder="<?php esc_attr_e( 'Search label, type, or key…', 'techsolution-form-studio' ); ?>" autocomplete="off">
                    <p id="tsfs-outline-count" role="status" aria-live="polite"></p>
                </div>
                <ol id="tsfs-outline-list" class="tsfs-outline-list"></ol>
                <p id="tsfs-outline-empty" class="tsfs-outline-empty" role="status" hidden></p>
            </div>
            <div class="tsfs-palette-scrollable" id="tsfs-add-fields-panel">
                <p id="tsfs-palette-no-results" class="tsfs-palette-no-results" role="status" hidden><?php esc_html_e( 'No matching fields. Try “email”, “choice”, or “upload”.', 'techsolution-form-studio' ); ?></p>
                <div class="tsfs-fields-group">
                    <h4><?php esc_html_e( 'Standard Fields', 'techsolution-form-studio' ); ?></h4>
                    <div class="tsfs-palette-grid">
                        <button type="button" class="tsfs-palette-item" data-type="text">
                            <span class="tsfs-palette-icon is-standard"><span class="dashicons dashicons-editor-textcolor"></span></span>
                            <span class="title"><?php esc_html_e( 'Single Line', 'techsolution-form-studio' ); ?></span>
                        </button>
                        <button type="button" class="tsfs-palette-item" data-type="email">
                            <span class="tsfs-palette-icon is-standard"><span class="dashicons dashicons-email-alt"></span></span>
                            <span class="title"><?php esc_html_e( 'Email', 'techsolution-form-studio' ); ?></span>
                        </button>
                        <button type="button" class="tsfs-palette-item" data-type="textarea">
                            <span class="tsfs-palette-icon is-standard"><span class="dashicons dashicons-editor-paragraph"></span></span>
                            <span class="title"><?php esc_html_e( 'Paragraph', 'techsolution-form-studio' ); ?></span>
                        </button>
                        <button type="button" class="tsfs-palette-item" data-type="number">
                            <span class="tsfs-palette-icon is-standard"><span class="dashicons dashicons-calculator"></span></span>
                            <span class="title"><?php esc_html_e( 'Number', 'techsolution-form-studio' ); ?></span>
                        </button>
                        <button type="button" class="tsfs-palette-item" data-type="tel">
                            <span class="tsfs-palette-icon is-standard"><span class="dashicons dashicons-phone"></span></span>
                            <span class="title"><?php esc_html_e( 'Phone', 'techsolution-form-studio' ); ?></span>
                        </button>
                        <button type="button" class="tsfs-palette-item" data-type="date">
                            <span class="tsfs-palette-icon is-standard"><span class="dashicons dashicons-calendar-alt"></span></span>
                            <span class="title"><?php esc_html_e( 'Date', 'techsolution-form-studio' ); ?></span>
                        </button>
                        <button type="button" class="tsfs-palette-item" data-type="time">
                            <span class="tsfs-palette-icon is-standard"><span class="dashicons dashicons-clock"></span></span>
                            <span class="title"><?php esc_html_e( 'Time', 'techsolution-form-studio' ); ?></span>
                        </button>
                        <button type="button" class="tsfs-palette-item" data-type="url">
                            <span class="tsfs-palette-icon is-standard"><span class="dashicons dashicons-admin-links"></span></span>
                            <span class="title"><?php esc_html_e( 'Website URL', 'techsolution-form-studio' ); ?></span>
                        </button>
                    </div>
                </div>

                <div class="tsfs-fields-group">
                    <h4><?php esc_html_e( 'Advanced & Uploads', 'techsolution-form-studio' ); ?></h4>
                    <div class="tsfs-palette-grid">
                        <button type="button" class="tsfs-palette-item" data-type="hidden">
                            <span class="tsfs-palette-icon is-advanced"><span class="dashicons dashicons-hidden"></span></span>
                            <span class="title"><?php esc_html_e( 'Hidden Field', 'techsolution-form-studio' ); ?></span>
                        </button>
                    </div>
                </div>

                <div class="tsfs-fields-group">
                    <h4><?php esc_html_e( 'Choice Fields', 'techsolution-form-studio' ); ?></h4>
                    <div class="tsfs-palette-grid">
                        <button type="button" class="tsfs-palette-item" data-type="select">
                            <span class="tsfs-palette-icon is-choice"><span class="dashicons dashicons-arrow-down-alt2"></span></span>
                            <span class="title"><?php esc_html_e( 'Dropdown', 'techsolution-form-studio' ); ?></span>
                        </button>
                        <button type="button" class="tsfs-palette-item" data-type="radio">
                            <span class="tsfs-palette-icon is-choice"><span class="dashicons dashicons-marker"></span></span>
                            <span class="title"><?php esc_html_e( 'Radio', 'techsolution-form-studio' ); ?></span>
                        </button>
                        <button type="button" class="tsfs-palette-item" data-type="checkbox">
                            <span class="tsfs-palette-icon is-choice"><span class="dashicons dashicons-yes-alt"></span></span>
                            <span class="title"><?php esc_html_e( 'Checkboxes', 'techsolution-form-studio' ); ?></span>
                        </button>
                    </div>
                </div>

                <div class="tsfs-fields-group">
                    <h4><?php esc_html_e( 'Structure', 'techsolution-form-studio' ); ?></h4>
                    <div class="tsfs-palette-grid">
                        <button type="button" class="tsfs-palette-item" data-type="cnic">
                            <span class="tsfs-palette-icon is-standard"><span class="dashicons dashicons-id"></span></span>
                            <span class="title"><?php esc_html_e( 'CNIC / Form-B', 'techsolution-form-studio' ); ?></span>
                        </button>
                        <button type="button" class="tsfs-palette-item" data-type="content_heading">
                            <span class="tsfs-palette-icon is-standard"><span class="dashicons dashicons-heading"></span></span>
                            <span class="title"><?php esc_html_e( 'Heading', 'techsolution-form-studio' ); ?></span>
                        </button>
                        <button type="button" class="tsfs-palette-item" data-type="content_paragraph">
                            <span class="tsfs-palette-icon is-standard"><span class="dashicons dashicons-editor-paragraph"></span></span>
                            <span class="title"><?php esc_html_e( 'Paragraph', 'techsolution-form-studio' ); ?></span>
                        </button>
                        <button type="button" class="tsfs-palette-item" data-type="content_bullets">
                            <span class="tsfs-palette-icon is-standard"><span class="dashicons dashicons-editor-ul"></span></span>
                            <span class="title"><?php esc_html_e( 'Bulleted List', 'techsolution-form-studio' ); ?></span>
                        </button>
                        <button type="button" class="tsfs-palette-item" data-type="content_numbered">
                            <span class="tsfs-palette-icon is-standard"><span class="dashicons dashicons-editor-ol"></span></span>
                            <span class="title"><?php esc_html_e( 'Numbered List', 'techsolution-form-studio' ); ?></span>
                        </button>
                        <button type="button" class="tsfs-palette-item" data-type="html">
                            <span class="tsfs-palette-icon is-structure"><span class="dashicons dashicons-heading"></span></span>
                            <span class="title"><?php esc_html_e( 'Section Note', 'techsolution-form-studio' ); ?></span>
                        </button>
                    </div>
                </div>

                <div class="tsfs-fields-group">
                    <h4><?php esc_html_e( 'Advanced Fields', 'techsolution-form-studio' ); ?></h4>
                    <div class="tsfs-palette-grid">
                        <button type="button" class="tsfs-palette-item" data-type="file" data-keywords="upload attachment document">
                            <span class="tsfs-palette-icon is-advanced"><span class="dashicons dashicons-upload"></span></span>
                            <span class="title"><?php esc_html_e( 'File Upload', 'techsolution-form-studio' ); ?></span>
                        </button>
                        <button type="button" class="tsfs-palette-item" data-type="rating">
                            <span class="tsfs-palette-icon is-advanced"><span class="dashicons dashicons-star-filled"></span></span>
                            <span class="title"><?php esc_html_e( 'Star Rating', 'techsolution-form-studio' ); ?></span>
                        </button>
                        <button type="button" class="tsfs-palette-item" data-type="consent" data-keywords="gdpr terms privacy agreement consent">
                            <span class="tsfs-palette-icon is-advanced"><span class="dashicons dashicons-shield"></span></span>
                            <span class="title"><?php esc_html_e( 'Terms / GDPR Consent', 'techsolution-form-studio' ); ?></span>
                        </button>
                        <button type="button" class="tsfs-palette-item tsfs-country-palette-item" data-type="country" data-keywords="country countries citizenship location residence">
                            <span class="tsfs-palette-icon is-advanced"><span class="dashicons dashicons-admin-site-alt3"></span></span>
                            <span class="title"><?php esc_html_e( 'Country', 'techsolution-form-studio' ); ?></span>
                        </button>
                        <button type="button" class="tsfs-palette-item tsfs-country-palette-item" data-type="nationality" data-keywords="nationality citizenship country countries">
                            <span class="tsfs-palette-icon is-advanced"><span class="dashicons dashicons-id-alt"></span></span>
                            <span class="title"><?php esc_html_e( 'Nationality', 'techsolution-form-studio' ); ?></span>
                        </button>
                        <button type="button" class="tsfs-palette-item" data-type="range">
                            <span class="tsfs-palette-icon is-advanced"><span class="dashicons dashicons-leftright"></span></span>
                            <span class="title"><?php esc_html_e( 'Rating Slider', 'techsolution-form-studio' ); ?></span>
                        </button>
                        <button type="button" class="tsfs-palette-item" data-type="page_break">
                            <span class="tsfs-palette-icon is-advanced"><span class="dashicons dashicons-editor-break"></span></span>
                            <span class="title"><?php esc_html_e( 'Multi-Step Forms', 'techsolution-form-studio' ); ?></span>
                        </button>
                    </div>
                </div>

                <?php
                /**
                 * Add-ons print palette groups for their field types here.
                 */
                do_action( 'tsfs_builder_palette' );
                ?>

                <?php if ( ! defined( 'TSFS_PRO_VERSION' ) ) : ?>
                    <p class="tsfs-palette-addon-note">
                        <?php esc_html_e( 'Need address, international phone, signature or calculation fields? They are available in the separate Pro add-on.', 'techsolution-form-studio' ); ?>
                        <a href="<?php echo esc_url( TSFS_PRO_URL ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Learn more', 'techsolution-form-studio' ); ?></a>
                    </p>
                <?php endif; ?>
            </div>
        </aside>

        <!-- 2. Center: Form Canvas -->
        <main class="tsfs-canvas-area">
            <div class="tsfs-canvas-container" id="tsfs-canvas-container">
                <div class="tsfs-canvas-form-header">
                    <div class="tsfs-canvas-status-pill">
                        <span class="tsfs-status-dot"></span> <?php esc_html_e( 'Live Preview', 'techsolution-form-studio' ); ?>
                    </div>
                    <h2 id="tsfs-canvas-title-preview"><?php echo esc_html( $form_title ); ?></h2>
                </div>

                <!-- Dropzone / Fields List -->
                <div class="tsfs-canvas-fields-list" id="tsfs-canvas-fields-list">
                    <!-- Dynamic fields rendered here by JS -->
                </div>

                <!-- Empty State within Canvas -->
                <div class="tsfs-canvas-empty" id="tsfs-canvas-empty" style="display:none;">
                    <div class="tsfs-canvas-empty-content">
                        <span class="dashicons dashicons-move"></span>
                        <h3><?php esc_html_e( 'Your form is empty', 'techsolution-form-studio' ); ?></h3>
                        <p><?php esc_html_e( 'Click on any field from the left sidebar or load a pre-built template.', 'techsolution-form-studio' ); ?></p>
                    </div>
                </div>

                <!-- Canvas Submit Button Preview -->
                <div class="tsfs-canvas-footer">
                    <button type="button" class="tsfs-canvas-submit-btn" id="tsfs-canvas-submit-preview">
                        <?php 
                        $btn_txt = ! empty( $form['settings']['submit_button_text'] ) ? $form['settings']['submit_button_text'] : __( 'Submit', 'techsolution-form-studio' );
                        echo esc_html( $btn_txt ); 
                        ?>
                    </button>
                </div>
            </div>
        </main>

        <!-- 3. Right Sidebar: Inspector (Field, Settings, Style) -->
        <aside class="tsfs-inspector-sidebar">
            <div class="tsfs-inspector-tabs">
                <button type="button" class="tsfs-tab-btn active" data-tab="field-settings" title="<?php esc_attr_e( 'Field Configuration', 'techsolution-form-studio' ); ?>">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <span class="tsfs-tab-text"><?php esc_html_e( 'Field', 'techsolution-form-studio' ); ?></span>
                </button>
                <button type="button" class="tsfs-tab-btn" data-tab="form-settings" title="<?php esc_attr_e( 'Form Settings & Notifications', 'techsolution-form-studio' ); ?>">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <span class="tsfs-tab-text"><?php esc_html_e( 'Settings', 'techsolution-form-studio' ); ?></span>
                </button>
                <button type="button" class="tsfs-tab-btn" data-tab="theme-style" title="<?php esc_attr_e( 'Theme & Appearance', 'techsolution-form-studio' ); ?>">
                    <span class="dashicons dashicons-art"></span>
                    <span class="tsfs-tab-text"><?php esc_html_e( 'Style', 'techsolution-form-studio' ); ?></span>
                </button>
            </div>

            <!-- Field Settings Tab Panel -->
            <div class="tsfs-tab-panel active" id="tsfs-panel-field-settings">
                <div id="tsfs-field-settings-content">
                    <div class="tsfs-empty-inspector">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        <p><?php esc_html_e( 'Select a field on the canvas to configure its properties & conditional logic.', 'techsolution-form-studio' ); ?></p>
                    </div>
                </div>
            </div>

            <!-- Form Settings Tab Panel -->
            <div class="tsfs-tab-panel" id="tsfs-panel-form-settings">
                <div class="tsfs-settings-form-group">
                    <label for="tsfs-setting-btn-text"><?php esc_html_e( 'Submit Button Label', 'techsolution-form-studio' ); ?></label>
                    <input type="text" id="tsfs-setting-btn-text" value="<?php echo esc_attr( ! empty( $form['settings']['submit_button_text'] ) ? $form['settings']['submit_button_text'] : __( 'Submit', 'techsolution-form-studio' ) ); ?>">
                </div>

                <div class="tsfs-settings-form-group">
                    <label for="tsfs-setting-success-msg"><?php esc_html_e( 'Success Confirmation Message', 'techsolution-form-studio' ); ?></label>
                    <textarea id="tsfs-setting-success-msg" rows="3"><?php echo esc_textarea( ! empty( $form['settings']['success_message'] ) ? $form['settings']['success_message'] : __( 'Thank you! Your submission has been received.', 'techsolution-form-studio' ) ); ?></textarea>
                </div>

                <div class="tsfs-settings-form-group">
                    <label for="tsfs-setting-redirect-url"><?php esc_html_e( 'Redirect URL (Optional)', 'techsolution-form-studio' ); ?></label>
                    <input type="url" id="tsfs-setting-redirect-url" placeholder="https://example.com/thank-you" value="<?php echo esc_attr( ! empty( $form['settings']['redirect_url'] ) ? $form['settings']['redirect_url'] : '' ); ?>">
                    <p class="description"><?php esc_html_e( 'Leave empty to display the success message on the current page.', 'techsolution-form-studio' ); ?></p>
                </div>

                <div class="tsfs-toggle-row" style="margin-top:10px;">
                    <div class="tsfs-toggle-info">
                        <span class="tsfs-toggle-title"><?php esc_html_e( 'Forward Data via Query String', 'techsolution-form-studio' ); ?></span>
                        <span class="tsfs-toggle-desc"><?php esc_html_e( 'Pass entry_id, form_id, and fields to the redirect URL', 'techsolution-form-studio' ); ?></span>
                    </div>
                    <label class="tsfs-toggle-switch">
                        <input type="checkbox" id="tsfs-setting-redirect-pass-vars" <?php checked( ! empty( $form['settings']['redirect_pass_vars'] ) ); ?>>
                        <span class="tsfs-toggle-slider"></span>
                    </label>
                </div>

                <hr style="margin: 22px 0; border: 0; border-top: 1px solid var(--tsfs-adm-slate-200);">

                <h4 style="margin:0 0 14px 0; font-size:13px; font-weight:700; color:var(--tsfs-adm-slate-900); text-transform:uppercase; letter-spacing:0.5px;"><?php esc_html_e( 'Admin Email Notifications', 'techsolution-form-studio' ); ?></h4>

                <div class="tsfs-toggle-row">
                    <div class="tsfs-toggle-info">
                        <span class="tsfs-toggle-title"><?php esc_html_e( 'Admin Notification Email', 'techsolution-form-studio' ); ?></span>
                        <span class="tsfs-toggle-desc"><?php esc_html_e( 'Send entry alerts to your designated email', 'techsolution-form-studio' ); ?></span>
                    </div>
                    <label class="tsfs-toggle-switch">
                        <input type="checkbox" id="tsfs-setting-send-email" <?php checked( ! empty( $form['settings']['send_email'] ) || ! $is_edit ); ?>>
                        <span class="tsfs-toggle-slider"></span>
                    </label>
                </div>

                <div class="tsfs-settings-form-group" id="tsfs-email-target-wrap" style="margin-top:14px;">
                    <label for="tsfs-setting-admin-email"><?php esc_html_e( 'Recipient Email Address(es)', 'techsolution-form-studio' ); ?></label>
                    <input type="text" id="tsfs-setting-admin-email" value="<?php echo esc_attr( ! empty( $form['settings']['notification_email'] ) ? $form['settings']['notification_email'] : get_option( 'admin_email' ) ); ?>">
                    <p class="description"><?php esc_html_e( 'Separate multiple email recipients with commas, e.g. admin@site.com, alerts@site.com', 'techsolution-form-studio' ); ?></p>
                </div>

                <div class="tsfs-settings-form-group">
                    <label for="tsfs-setting-email-subject"><?php esc_html_e( 'Email Subject', 'techsolution-form-studio' ); ?></label>
                    <input type="text" id="tsfs-setting-email-subject" placeholder="New submission for {form_title} [#{entry_id}]" value="<?php echo esc_attr( ! empty( $form['settings']['email_subject'] ) ? $form['settings']['email_subject'] : '' ); ?>">
                    <p class="description"><?php esc_html_e( 'Available tags: {form_title}, {entry_id}, {user_name}, {user_email}, {date}', 'techsolution-form-studio' ); ?></p>
                </div>

                <hr style="margin: 22px 0; border: 0; border-top: 1px solid var(--tsfs-adm-slate-200);">

                <h4 style="margin:0 0 14px 0; font-size:13px; font-weight:700; color:var(--tsfs-adm-slate-900); text-transform:uppercase; letter-spacing:0.5px;"><?php esc_html_e( 'User Auto-Responder', 'techsolution-form-studio' ); ?></h4>

                <div class="tsfs-toggle-row">
                    <div class="tsfs-toggle-info">
                        <span class="tsfs-toggle-title"><?php esc_html_e( 'Send Auto-Reply to Submitter', 'techsolution-form-studio' ); ?></span>
                        <span class="tsfs-toggle-desc"><?php esc_html_e( 'Immediately acknowledge receipt of form', 'techsolution-form-studio' ); ?></span>
                    </div>
                    <label class="tsfs-toggle-switch">
                        <input type="checkbox" id="tsfs-setting-user-autoreply" <?php checked( ! empty( $form['settings']['send_user_auto_reply'] ) ); ?>>
                        <span class="tsfs-toggle-slider"></span>
                    </label>
                </div>

                <div class="tsfs-settings-form-group" id="tsfs-autoreply-fields-wrap" style="margin-top:14px;">
                    <label for="tsfs-setting-autoreply-subj"><?php esc_html_e( 'Auto-Reply Subject', 'techsolution-form-studio' ); ?></label>
                    <input type="text" id="tsfs-setting-autoreply-subj" value="<?php echo esc_attr( ! empty( $form['settings']['auto_reply_subject'] ) ? $form['settings']['auto_reply_subject'] : __( 'Thank you for your submission!', 'techsolution-form-studio' ) ); ?>">
                    
                    <label for="tsfs-setting-autoreply-msg" style="margin-top:10px;"><?php esc_html_e( 'Auto-Reply Message', 'techsolution-form-studio' ); ?></label>
                    <textarea id="tsfs-setting-autoreply-msg" rows="3"><?php echo esc_textarea( ! empty( $form['settings']['auto_reply_message'] ) ? $form['settings']['auto_reply_message'] : __( "Hello,\n\nWe have received your submission and will get back to you shortly.\n\nBest regards,\nSupport Team", 'techsolution-form-studio' ) ); ?></textarea>
                </div>

                <hr style="margin: 22px 0; border: 0; border-top: 1px solid var(--tsfs-adm-slate-200);">

                <h4 style="margin:0 0 14px 0; font-size:13px; font-weight:700; color:var(--tsfs-adm-slate-900); text-transform:uppercase; letter-spacing:0.5px;"><?php esc_html_e( 'Restrictions & Scheduling', 'techsolution-form-studio' ); ?></h4>

                <div class="tsfs-toggle-row">
                    <div class="tsfs-toggle-info">
                        <span class="tsfs-toggle-title"><?php esc_html_e( 'Limit Total Submissions', 'techsolution-form-studio' ); ?></span>
                        <span class="tsfs-toggle-desc"><?php esc_html_e( 'Automatically close after maximum cap', 'techsolution-form-studio' ); ?></span>
                    </div>
                    <label class="tsfs-toggle-switch">
                        <input type="checkbox" id="tsfs-setting-limit-submissions" <?php checked( ! empty( $form['settings']['limit_submissions'] ) ); ?>>
                        <span class="tsfs-toggle-slider"></span>
                    </label>
                </div>

                <div class="tsfs-settings-form-group" id="tsfs-limit-entries-wrap" style="<?php echo empty( $form['settings']['limit_submissions'] ) ? 'display:none;' : ''; ?> margin-top:14px;">
                    <label for="tsfs-setting-max-entries"><?php esc_html_e( 'Maximum Submissions Allowed', 'techsolution-form-studio' ); ?></label>
                    <input type="number" id="tsfs-setting-max-entries" min="1" value="<?php echo esc_attr( ! empty( $form['settings']['max_entries'] ) ? $form['settings']['max_entries'] : 100 ); ?>">
                    <p class="description"><?php esc_html_e( 'The form will stop accepting submissions once this cap is met.', 'techsolution-form-studio' ); ?></p>
                </div>

                <div class="tsfs-toggle-row">
                    <div class="tsfs-toggle-info">
                        <span class="tsfs-toggle-title"><?php esc_html_e( 'Schedule Expiration Date', 'techsolution-form-studio' ); ?></span>
                        <span class="tsfs-toggle-desc"><?php esc_html_e( 'Set an automatic closing deadline', 'techsolution-form-studio' ); ?></span>
                    </div>
                    <label class="tsfs-toggle-switch">
                        <input type="checkbox" id="tsfs-setting-close-date-toggle" <?php checked( ! empty( $form['settings']['close_after_date'] ) ); ?>>
                        <span class="tsfs-toggle-slider"></span>
                    </label>
                </div>

                <div class="tsfs-settings-form-group" id="tsfs-close-date-wrap" style="<?php echo empty( $form['settings']['close_after_date'] ) ? 'display:none;' : ''; ?> margin-top:14px;">
                    <label for="tsfs-setting-close-date"><?php esc_html_e( 'Close Form At', 'techsolution-form-studio' ); ?></label>
                    <input type="datetime-local" id="tsfs-setting-close-date" value="<?php echo esc_attr( ! empty( $form['settings']['close_date'] ) ? $form['settings']['close_date'] : '' ); ?>">
                    
                    <label for="tsfs-setting-closed-msg" style="margin-top:10px;"><?php esc_html_e( 'Closed Form Message', 'techsolution-form-studio' ); ?></label>
                    <textarea id="tsfs-setting-closed-msg" rows="2"><?php echo esc_textarea( ! empty( $form['settings']['closed_message'] ) ? $form['settings']['closed_message'] : __( 'This form is currently closed for submissions.', 'techsolution-form-studio' ) ); ?></textarea>
                </div>

                <div class="tsfs-toggle-row">
                    <div class="tsfs-toggle-info">
                        <span class="tsfs-toggle-title"><?php esc_html_e( 'Require User Login', 'techsolution-form-studio' ); ?></span>
                        <span class="tsfs-toggle-desc"><?php esc_html_e( 'Only logged-in members can submit', 'techsolution-form-studio' ); ?></span>
                    </div>
                    <label class="tsfs-toggle-switch">
                        <input type="checkbox" id="tsfs-setting-require-login" <?php checked( ! empty( $form['settings']['require_login'] ) ); ?>>
                        <span class="tsfs-toggle-slider"></span>
                    </label>
                </div>

                <div class="tsfs-settings-form-group" id="tsfs-login-msg-wrap" style="<?php echo empty( $form['settings']['require_login'] ) ? 'display:none;' : ''; ?> margin-top:14px;">
                    <label for="tsfs-setting-login-msg"><?php esc_html_e( 'Login Required Message', 'techsolution-form-studio' ); ?></label>
                    <textarea id="tsfs-setting-login-msg" rows="2"><?php echo esc_textarea( ! empty( $form['settings']['login_message'] ) ? $form['settings']['login_message'] : __( 'You must be logged in to submit this form.', 'techsolution-form-studio' ) ); ?></textarea>
                </div>

                <hr style="margin: 22px 0; border: 0; border-top: 1px solid var(--tsfs-adm-slate-200);">

                <h4 style="margin:0 0 14px 0; font-size:13px; font-weight:700; color:var(--tsfs-adm-slate-900); text-transform:uppercase; letter-spacing:0.5px; display:flex; align-items:center; justify-content:space-between;">
                    <span><?php esc_html_e( 'Webhooks & Integrations', 'techsolution-form-studio' ); ?></span>
                </h4>

                <div class="tsfs-toggle-row">
                    <div class="tsfs-toggle-info">
                        <span class="tsfs-toggle-title"><?php esc_html_e( 'Enable Webhook Dispatch', 'techsolution-form-studio' ); ?></span>
                        <span class="tsfs-toggle-desc"><?php esc_html_e( 'Send real-time JSON submission payloads to external apps', 'techsolution-form-studio' ); ?></span>
                    </div>
                    <label class="tsfs-toggle-switch">
                        <input type="checkbox" id="tsfs-setting-enable-webhook" <?php checked( ! empty( $form['settings']['enable_webhook'] ) ); ?>>
                        <span class="tsfs-toggle-slider"></span>
                    </label>
                </div>

                <div class="tsfs-settings-form-group" id="tsfs-webhook-fields-wrap" style="<?php echo empty( $form['settings']['enable_webhook'] ) ? 'display:none;' : ''; ?> margin-top:14px;">
                    <label for="tsfs-setting-webhook-url"><?php esc_html_e( 'Webhook Endpoint URL', 'techsolution-form-studio' ); ?></label>
                    <input type="url" id="tsfs-setting-webhook-url" placeholder="https://hooks.zapier.com/hooks/catch/..." value="<?php echo esc_attr( ! empty( $form['settings']['webhook_url'] ) ? $form['settings']['webhook_url'] : '' ); ?>">
                    <p class="description"><?php esc_html_e( 'Connect with Zapier, Make, n8n, Slack, or any REST API endpoint.', 'techsolution-form-studio' ); ?></p>

                    <label for="tsfs-setting-webhook-secret" style="margin-top:10px;"><?php esc_html_e( 'Secret Key / HMAC Token (Optional)', 'techsolution-form-studio' ); ?></label>
                    <input type="text" id="tsfs-setting-webhook-secret" placeholder="tsfs_secret_..." value="<?php echo esc_attr( ! empty( $form['settings']['webhook_secret'] ) ? $form['settings']['webhook_secret'] : '' ); ?>">
                    <p class="description"><?php esc_html_e( 'Transmitted in the X-TSFS-Signature (SHA-256) and Authorization headers.', 'techsolution-form-studio' ); ?></p>

                    <div style="margin-top:14px; display:flex; align-items:center; gap:8px;">
                        <button type="button" class="button button-secondary" id="tsfs-test-webhook-btn" style="border-radius:6px; font-weight:600;">
                            <span class="dashicons dashicons-networking" style="vertical-align:middle; margin-top:-2px;"></span> <?php esc_html_e( 'Send Test Payload', 'techsolution-form-studio' ); ?>
                        </button>
                        <span id="tsfs-test-webhook-status" style="font-size:12px; font-weight:600;"></span>
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
            ?>
            <div class="tsfs-tab-panel" id="tsfs-panel-theme-style">
                <h4 style="margin:0 0 4px 0; font-size:14px;"><?php esc_html_e( 'Theme Presets', 'techsolution-form-studio' ); ?></h4>
                <p class="description" style="margin-bottom:12px;"><?php esc_html_e( 'Select a pre-designed theme aesthetic for your form.', 'techsolution-form-studio' ); ?></p>
                
                <div class="tsfs-theme-cards-grid">
                    <div class="tsfs-theme-card <?php echo 'default' === $theme_preset ? 'active' : ''; ?>" data-preset="default" data-color="#4f46e5" data-bg="#ffffff" data-btn="#ffffff">
                        <span class="tsfs-theme-swatch" style="background:#4f46e5;"></span>
                        <div class="tsfs-theme-card-info">
                            <div><?php esc_html_e( 'Modern Indigo', 'techsolution-form-studio' ); ?></div>
                            <small style="color:var(--tsfs-adm-slate-400); font-weight:normal;"><?php esc_html_e( 'Default SaaS', 'techsolution-form-studio' ); ?></small>
                        </div>
                    </div>

                    <div class="tsfs-theme-card <?php echo 'minimal' === $theme_preset ? 'active' : ''; ?>" data-preset="minimal" data-color="#0f172a" data-bg="#ffffff" data-btn="#ffffff">
                        <span class="tsfs-theme-swatch" style="background:#0f172a;"></span>
                        <div class="tsfs-theme-card-info">
                            <div><?php esc_html_e( 'Minimalist', 'techsolution-form-studio' ); ?></div>
                            <small style="color:var(--tsfs-adm-slate-400); font-weight:normal;"><?php esc_html_e( 'Monochrome', 'techsolution-form-studio' ); ?></small>
                        </div>
                    </div>

                    <div class="tsfs-theme-card <?php echo 'dark' === $theme_preset ? 'active' : ''; ?>" data-preset="dark" data-color="#38bdf8" data-bg="#0f172a" data-btn="#0f172a">
                        <span class="tsfs-theme-swatch" style="background:#0f172a; border-color:#38bdf8;"></span>
                        <div class="tsfs-theme-card-info">
                            <div><?php esc_html_e( 'Midnight Dark', 'techsolution-form-studio' ); ?></div>
                            <small style="color:var(--tsfs-adm-slate-400); font-weight:normal;"><?php esc_html_e( 'High contrast', 'techsolution-form-studio' ); ?></small>
                        </div>
                    </div>

                    <div class="tsfs-theme-card <?php echo 'emerald' === $theme_preset ? 'active' : ''; ?>" data-preset="emerald" data-color="#059669" data-bg="#ffffff" data-btn="#ffffff">
                        <span class="tsfs-theme-swatch" style="background:#059669;"></span>
                        <div class="tsfs-theme-card-info">
                            <div><?php esc_html_e( 'Emerald Eco', 'techsolution-form-studio' ); ?></div>
                            <small style="color:var(--tsfs-adm-slate-400); font-weight:normal;"><?php esc_html_e( 'Fresh Green', 'techsolution-form-studio' ); ?></small>
                        </div>
                    </div>

                    <div class="tsfs-theme-card <?php echo 'sunset' === $theme_preset ? 'active' : ''; ?>" data-preset="sunset" data-color="#e11d48" data-bg="#ffffff" data-btn="#ffffff">
                        <span class="tsfs-theme-swatch" style="background:#e11d48;"></span>
                        <div class="tsfs-theme-card-info">
                            <div><?php esc_html_e( 'Sunset Rose', 'techsolution-form-studio' ); ?></div>
                            <small style="color:var(--tsfs-adm-slate-400); font-weight:normal;"><?php esc_html_e( 'Crimson Warm', 'techsolution-form-studio' ); ?></small>
                        </div>
                    </div>

                    <div class="tsfs-theme-card <?php echo 'violet' === $theme_preset ? 'active' : ''; ?>" data-preset="violet" data-color="#7c3aed" data-bg="#ffffff" data-btn="#ffffff">
                        <span class="tsfs-theme-swatch" style="background:#7c3aed;"></span>
                        <div class="tsfs-theme-card-info">
                            <div><?php esc_html_e( 'Royal Violet', 'techsolution-form-studio' ); ?></div>
                            <small style="color:var(--tsfs-adm-slate-400); font-weight:normal;"><?php esc_html_e( 'Luxury Purple', 'techsolution-form-studio' ); ?></small>
                        </div>
                    </div>
                </div>

                <input type="hidden" id="tsfs-theme-preset-val" value="<?php echo esc_attr( $theme_preset ); ?>">

                <hr style="margin: 20px 0; border: 0; border-top: 1px solid var(--tsfs-adm-slate-200);">

                <h4 style="margin:0 0 12px 0; font-size:14px;"><?php esc_html_e( 'Color Customization', 'techsolution-form-studio' ); ?></h4>

                <div class="tsfs-settings-form-group">
                    <label for="tsfs-theme-primary-color"><?php esc_html_e( 'Accent & Button Color', 'techsolution-form-studio' ); ?></label>
                    <div class="tsfs-color-row">
                        <input type="color" id="tsfs-theme-primary-picker" value="<?php echo esc_attr( $primary_color ); ?>">
                        <input type="text" id="tsfs-theme-primary-color" value="<?php echo esc_attr( $primary_color ); ?>" placeholder="#4f46e5">
                    </div>
                </div>

                <div class="tsfs-settings-form-group">
                    <label for="tsfs-theme-btn-color"><?php esc_html_e( 'Button Text Color', 'techsolution-form-studio' ); ?></label>
                    <div class="tsfs-color-row">
                        <input type="color" id="tsfs-theme-btn-picker" value="<?php echo esc_attr( $btn_text_color ); ?>">
                        <input type="text" id="tsfs-theme-btn-color" value="<?php echo esc_attr( $btn_text_color ); ?>" placeholder="#ffffff">
                    </div>
                </div>

                <div class="tsfs-settings-form-group">
                    <label for="tsfs-theme-bg-color"><?php esc_html_e( 'Form Background Color', 'techsolution-form-studio' ); ?></label>
                    <div class="tsfs-color-row">
                        <input type="color" id="tsfs-theme-bg-picker" value="<?php echo esc_attr( $container_bg ); ?>">
                        <input type="text" id="tsfs-theme-bg-color" value="<?php echo esc_attr( $container_bg ); ?>" placeholder="#ffffff">
                    </div>
                </div>

                <hr style="margin: 20px 0; border: 0; border-top: 1px solid var(--tsfs-adm-slate-200);">

                <h4>Form size &amp; spacing</h4>
                <p class="description">Sizes are in pixels. The form stays within the screen width and grows to fit its content.</p>
                <?php $layout_values = TSFS_Form::layout_settings( $form['settings'] ?? array() ); ?>
                <div class="tsfs-settings-form-group">
                    <label for="tsfs-layout-form_max_width">Form maximum width</label>
                    <input type="number" id="tsfs-layout-form_max_width" data-layout-setting="form_max_width" min="280" max="1600" step="1" value="<?php echo esc_attr( $layout_values['form_max_width'] ); ?>">
                </div>
                <div class="tsfs-settings-form-group">
                    <label for="tsfs-layout-form_min_height">Form minimum height (0 = automatic)</label>
                    <input type="number" id="tsfs-layout-form_min_height" data-layout-setting="form_min_height" min="0" max="1600" step="1" value="<?php echo esc_attr( $layout_values['form_min_height'] ); ?>">
                </div>
                <div class="tsfs-settings-form-group">
                    <label for="tsfs-layout-form_padding">Form inner padding</label>
                    <input type="number" id="tsfs-layout-form_padding" data-layout-setting="form_padding" min="0" max="80" step="1" value="<?php echo esc_attr( $layout_values['form_padding'] ); ?>">
                </div>
                <div class="tsfs-settings-form-group">
                    <label for="tsfs-layout-field_gap">Space between fields</label>
                    <input type="number" id="tsfs-layout-field_gap" data-layout-setting="field_gap" min="0" max="64" step="1" value="<?php echo esc_attr( $layout_values['field_gap'] ); ?>">
                </div>
                <div class="tsfs-settings-form-group">
                    <label for="tsfs-layout-input_height">Input minimum height</label>
                    <input type="number" id="tsfs-layout-input_height" data-layout-setting="input_height" min="32" max="100" step="1" value="<?php echo esc_attr( $layout_values['input_height'] ); ?>">
                </div>
                <div class="tsfs-settings-form-group">
                    <label for="tsfs-layout-button_width">Button width (0 = automatic)</label>
                    <input type="number" id="tsfs-layout-button_width" data-layout-setting="button_width" min="0" max="1000" step="1" value="<?php echo esc_attr( $layout_values['button_width'] ); ?>">
                </div>
                <div class="tsfs-settings-form-group">
                    <label for="tsfs-layout-button_height">Button minimum height</label>
                    <input type="number" id="tsfs-layout-button_height" data-layout-setting="button_height" min="32" max="120" step="1" value="<?php echo esc_attr( $layout_values['button_height'] ); ?>">
                </div>
                <div class="tsfs-settings-form-group">
                    <label for="tsfs-layout-button_padding">Button horizontal padding</label>
                    <input type="number" id="tsfs-layout-button_padding" data-layout-setting="button_padding" min="0" max="80" step="1" value="<?php echo esc_attr( $layout_values['button_padding'] ); ?>">
                </div>

                <h4>Typography, button &amp; consent appearance</h4>
                <p class="description">Change the button and privacy statement without editing CSS. Agreement text, link and help text are editable by selecting the consent field.</p>
                <div class="tsfs-settings-form-group"><label for="tsfs-layout-text_size">Body text size (px)</label><input type="number" id="tsfs-layout-text_size" data-layout-setting="text_size" min="12" max="24" value="<?php echo esc_attr( $layout_values['text_size'] ); ?>"></div>
                <div class="tsfs-settings-form-group"><label for="tsfs-layout-label_size">Label text size (px)</label><input type="number" id="tsfs-layout-label_size" data-layout-setting="label_size" min="12" max="26" value="<?php echo esc_attr( $layout_values['label_size'] ); ?>"></div>
                <div class="tsfs-settings-form-group"><label for="tsfs-layout-button_font_size">Button text size (px)</label><input type="number" id="tsfs-layout-button_font_size" data-layout-setting="button_font_size" min="12" max="30" value="<?php echo esc_attr( $layout_values['button_font_size'] ); ?>"></div>
                <div class="tsfs-settings-form-group"><label for="tsfs-layout-button_radius">Button corner radius (px)</label><input type="number" id="tsfs-layout-button_radius" data-layout-setting="button_radius" min="0" max="60" value="<?php echo esc_attr( $layout_values['button_radius'] ); ?>"></div>
                <div class="tsfs-settings-form-group"><label for="tsfs-layout-button_vertical_padding">Button vertical padding (px)</label><input type="number" id="tsfs-layout-button_vertical_padding" data-layout-setting="button_vertical_padding" min="4" max="40" value="<?php echo esc_attr( $layout_values['button_vertical_padding'] ); ?>"></div>
                <div class="tsfs-settings-form-group"><label for="tsfs-layout-consent_size">Consent text size (px)</label><input type="number" id="tsfs-layout-consent_size" data-layout-setting="consent_size" min="12" max="24" value="<?php echo esc_attr( $layout_values['consent_size'] ); ?>"></div>
                <div class="tsfs-settings-form-group"><label for="tsfs-layout-consent_padding">Consent box padding (px)</label><input type="number" id="tsfs-layout-consent_padding" data-layout-setting="consent_padding" min="0" max="40" value="<?php echo esc_attr( $layout_values['consent_padding'] ); ?>"></div>
                <div class="tsfs-settings-form-group"><label for="tsfs-layout-checkbox_size">Consent checkbox size (px)</label><input type="number" id="tsfs-layout-checkbox_size" data-layout-setting="checkbox_size" min="16" max="32" value="<?php echo esc_attr( $layout_values['checkbox_size'] ); ?>"></div>
                <?php $appearance_colors = TSFS_Form::color_settings( $form['settings'] ?? array() ); ?>
                <div class="tsfs-settings-form-group"><label for="tsfs-color-button_bg">Button background</label><input type="color" id="tsfs-color-button_bg" data-layout-setting="button_bg" value="<?php echo esc_attr( $appearance_colors['button_bg'] ); ?>"></div>
                <div class="tsfs-settings-form-group"><label for="tsfs-color-button_hover">Button hover background</label><input type="color" id="tsfs-color-button_hover" data-layout-setting="button_hover" value="<?php echo esc_attr( $appearance_colors['button_hover'] ); ?>"></div>
                <div class="tsfs-settings-form-group"><label for="tsfs-color-consent_bg">Consent background</label><input type="color" id="tsfs-color-consent_bg" data-layout-setting="consent_bg" value="<?php echo esc_attr( $appearance_colors['consent_bg'] ); ?>"></div>
                <div class="tsfs-settings-form-group"><label for="tsfs-color-consent_border">Consent border</label><input type="color" id="tsfs-color-consent_border" data-layout-setting="consent_border" value="<?php echo esc_attr( $appearance_colors['consent_border'] ); ?>"></div>
                <div class="tsfs-settings-form-group"><label for="tsfs-color-consent_color">Consent text color</label><input type="color" id="tsfs-color-consent_color" data-layout-setting="consent_color" value="<?php echo esc_attr( $appearance_colors['consent_color'] ); ?>"></div>
                <h4 style="margin:0 0 12px 0; font-size:14px;"><?php esc_html_e( 'Form Shape & Corners', 'techsolution-form-studio' ); ?></h4>

                <div class="tsfs-settings-form-group">
                    <label><?php esc_html_e( 'Corner Radius', 'techsolution-form-studio' ); ?></label>
                    <div class="tsfs-width-options" style="display:grid; grid-template-columns: repeat(4, 1fr); gap:6px;">
                        <button type="button" class="tsfs-radius-opt <?php echo 'sharp' === $border_radius ? 'active' : ''; ?>" data-radius="sharp" style="padding:7px 4px; font-size:11.5px; border-radius:3px;">
                            <?php esc_html_e( 'Sharp', 'techsolution-form-studio' ); ?>
                        </button>
                        <button type="button" class="tsfs-radius-opt <?php echo 'slight' === $border_radius ? 'active' : ''; ?>" data-radius="slight" style="padding:7px 4px; font-size:11.5px; border-radius:6px;">
                            <?php esc_html_e( 'Slight', 'techsolution-form-studio' ); ?>
                        </button>
                        <button type="button" class="tsfs-radius-opt <?php echo 'rounded' === $border_radius ? 'active' : ''; ?>" data-radius="rounded" style="padding:7px 4px; font-size:11.5px; border-radius:8px;">
                            <?php esc_html_e( 'Round', 'techsolution-form-studio' ); ?>
                        </button>
                        <button type="button" class="tsfs-radius-opt <?php echo 'pill' === $border_radius ? 'active' : ''; ?>" data-radius="pill" style="padding:7px 4px; font-size:11.5px; border-radius:14px;">
                            <?php esc_html_e( 'Pill', 'techsolution-form-studio' ); ?>
                        </button>
                    </div>
                    <input type="hidden" id="tsfs-theme-radius-val" value="<?php echo esc_attr( $border_radius ); ?>">
                </div>

                <div class="tsfs-settings-form-group">
                    <label><?php esc_html_e( 'Submit Button Alignment', 'techsolution-form-studio' ); ?></label>
                    <div class="tsfs-width-options" style="display:grid; grid-template-columns: repeat(4, 1fr); gap:6px;">
                        <button type="button" class="tsfs-btn-align-opt <?php echo 'left' === $button_align ? 'active' : ''; ?>" data-align="left" style="padding:7px 4px; font-size:11.5px;">
                            <span class="dashicons dashicons-editor-alignleft" style="font-size:15px; width:15px; height:15px; vertical-align:middle;"></span>
                        </button>
                        <button type="button" class="tsfs-btn-align-opt <?php echo 'center' === $button_align ? 'active' : ''; ?>" data-align="center" style="padding:7px 4px; font-size:11.5px;">
                            <span class="dashicons dashicons-editor-aligncenter" style="font-size:15px; width:15px; height:15px; vertical-align:middle;"></span>
                        </button>
                        <button type="button" class="tsfs-btn-align-opt <?php echo 'right' === $button_align ? 'active' : ''; ?>" data-align="right" style="padding:7px 4px; font-size:11.5px;">
                            <span class="dashicons dashicons-editor-alignright" style="font-size:15px; width:15px; height:15px; vertical-align:middle;"></span>
                        </button>
                        <button type="button" class="tsfs-btn-align-opt <?php echo 'full' === $button_align ? 'active' : ''; ?>" data-align="full" style="padding:7px 4px; font-size:11.5px;">
                            <span class="dashicons dashicons-editor-justify" style="font-size:15px; width:15px; height:15px; vertical-align:middle;"></span>
                        </button>
                    </div>
                    <input type="hidden" id="tsfs-theme-button-align-val" value="<?php echo esc_attr( $button_align ); ?>">
                </div>

                <div class="tsfs-settings-form-group">
                    <label for="tsfs-theme-shadow-val"><?php esc_html_e( 'Container Shadow', 'techsolution-form-studio' ); ?></label>
                    <select id="tsfs-theme-shadow-val">
                        <option value="none" <?php selected( 'none', $shadow_style ); ?>><?php esc_html_e( 'None (Flat)', 'techsolution-form-studio' ); ?></option>
                        <option value="subtle" <?php selected( 'subtle', $shadow_style ); ?>><?php esc_html_e( 'Subtle Border Shadow', 'techsolution-form-studio' ); ?></option>
                        <option value="medium" <?php selected( 'medium', $shadow_style ); ?>><?php esc_html_e( 'Medium Clean (Default)', 'techsolution-form-studio' ); ?></option>
                        <option value="elevated" <?php selected( 'elevated', $shadow_style ); ?>><?php esc_html_e( 'Elevated Floating 3D', 'techsolution-form-studio' ); ?></option>
                        <option value="outline" <?php selected( 'outline', $shadow_style ); ?>><?php esc_html_e( 'Distinct 2px Outline', 'techsolution-form-studio' ); ?></option>
                    </select>
                </div>

            </div>
        </aside>
    </div>
</div>

<!-- Templates Picker Modal -->
<div id="tsfs-templates-modal" class="tsfs-modal-backdrop" style="display:none;">
    <div class="tsfs-modal-dialog" style="max-width: 750px;">
        <div class="tsfs-modal-header">
            <h3><span class="dashicons dashicons-layout" style="vertical-align:middle; color:var(--tsfs-adm-primary);"></span> <?php esc_html_e( 'Choose a Pre-Built Form Template', 'techsolution-form-studio' ); ?></h3>
            <button type="button" class="tsfs-modal-close" id="tsfs-templates-close-btn">&times;</button>
        </div>
        <div class="tsfs-modal-body" style="padding: 24px;">
            <p style="color:var(--tsfs-adm-slate-600); margin-top:0;"><?php esc_html_e( 'Select a template below to jumpstart your form design. You can fully customize fields after loading.', 'techsolution-form-studio' ); ?></p>
            
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 16px; margin-top: 16px;">
                <!-- Template 1 -->
                <div class="tsfs-template-card" data-template="contact" style="background:var(--tsfs-adm-slate-50); border:1.5px solid var(--tsfs-adm-slate-200); border-radius:8px; padding:18px; cursor:pointer; transition:all 0.15s ease;">
                    <div style="font-size:24px; color:var(--tsfs-adm-primary); margin-bottom:8px;"><span class="dashicons dashicons-email"></span></div>
                    <h4 style="margin:0 0 6px 0; font-size:15px; font-weight:700;"><?php esc_html_e( 'Simple Contact Form', 'techsolution-form-studio' ); ?></h4>
                    <p style="margin:0; font-size:12px; color:var(--tsfs-adm-slate-500);"><?php esc_html_e( 'Name, Email, Subject, and Message fields.', 'techsolution-form-studio' ); ?></p>
                </div>

                <!-- Template 2 -->
                <div class="tsfs-template-card" data-template="job_app" style="background:var(--tsfs-adm-slate-50); border:1.5px solid var(--tsfs-adm-slate-200); border-radius:8px; padding:18px; cursor:pointer; transition:all 0.15s ease;">
                    <div style="font-size:24px; color:#059669; margin-bottom:8px;"><span class="dashicons dashicons-id-alt"></span></div>
                    <h4 style="margin:0 0 6px 0; font-size:15px; font-weight:700;"><?php esc_html_e( 'Job Application & CV', 'techsolution-form-studio' ); ?></h4>
                    <p style="margin:0; font-size:12px; color:var(--tsfs-adm-slate-500);"><?php esc_html_e( 'Includes resume/CV file upload & portfolio URL.', 'techsolution-form-studio' ); ?></p>
                </div>

                <!-- Template 3 -->
                <div class="tsfs-template-card" data-template="feedback" style="background:var(--tsfs-adm-slate-50); border:1.5px solid var(--tsfs-adm-slate-200); border-radius:8px; padding:18px; cursor:pointer; transition:all 0.15s ease;">
                    <div style="font-size:24px; color:#2563eb; margin-bottom:8px;"><span class="dashicons dashicons-star-filled"></span></div>
                    <h4 style="margin:0 0 6px 0; font-size:15px; font-weight:700;"><?php esc_html_e( 'Feedback & Rating', 'techsolution-form-studio' ); ?></h4>
                    <p style="margin:0; font-size:12px; color:var(--tsfs-adm-slate-500);"><?php esc_html_e( 'Satisfaction rating slider, questions, suggestions.', 'techsolution-form-studio' ); ?></p>
                </div>

                <!-- Template 4 -->
                <div class="tsfs-template-card" data-template="event_rsvp" style="background:var(--tsfs-adm-slate-50); border:1.5px solid var(--tsfs-adm-slate-200); border-radius:8px; padding:18px; cursor:pointer; transition:all 0.15s ease;">
                    <div style="font-size:24px; color:#7c3aed; margin-bottom:8px;"><span class="dashicons dashicons-calendar"></span></div>
                    <h4 style="margin:0 0 6px 0; font-size:15px; font-weight:700;"><?php esc_html_e( 'Event RSVP (Conditional)', 'techsolution-form-studio' ); ?></h4>
                    <p style="margin:0; font-size:12px; color:var(--tsfs-adm-slate-500);"><?php esc_html_e( 'Conditional dietary fields triggered by meal selection.', 'techsolution-form-studio' ); ?></p>
                </div>

                <?php
                /**
                 * Add-ons print additional template cards here (data-template must match a key they register in TSFSBuilder.templates).
                 */
                do_action( 'tsfs_builder_templates' );
                ?>
            </div>
        </div>
        <div class="tsfs-modal-footer">
            <button type="button" class="button button-secondary" id="tsfs-templates-cancel-btn"><?php esc_html_e( 'Cancel', 'techsolution-form-studio' ); ?></button>
        </div>
    </div>
</div>

<!-- Embed Form Modal -->
<div id="tsfs-embed-modal" class="tsfs-modal-backdrop" style="display:none;">
    <div class="tsfs-modal-dialog" style="max-width: 580px;">
        <div class="tsfs-modal-header">
            <h3><span class="dashicons dashicons-embed-post" style="vertical-align:middle; color:var(--tsfs-adm-primary);"></span> <?php esc_html_e( 'Embed This Form', 'techsolution-form-studio' ); ?></h3>
            <button type="button" class="tsfs-modal-close" id="tsfs-embed-close-btn">&times;</button>
        </div>
        <div class="tsfs-modal-body" style="padding: 24px;">
            <p style="color:var(--tsfs-adm-slate-600); margin-top:0;">
                <?php esc_html_e( 'Copy the shortcode below and paste it anywhere into WordPress pages, posts, or page builders like Elementor, Gutenberg, & Divi.', 'techsolution-form-studio' ); ?>
            </p>

            <div style="background:var(--tsfs-adm-slate-50); border:1.5px solid var(--tsfs-adm-slate-200); border-radius:10px; padding:16px; margin:16px 0;">
                <label style="display:block; font-size:11px; font-weight:700; color:var(--tsfs-adm-slate-500); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:8px;">
                    <?php esc_html_e( 'Standard Shortcode', 'techsolution-form-studio' ); ?>
                </label>
                <div style="display:flex; gap:8px;">
                    <input type="text" readonly id="tsfs-embed-shortcode-input" value="<?php echo esc_attr( $shortcode ? $shortcode : '[tsfs_form id="' . $current_form_id . '"]' ); ?>" style="flex:1; font-family:monospace; font-weight:600; font-size:14px; background:#ffffff; border:1.5px solid var(--tsfs-adm-slate-300); border-radius:6px; padding:6px 12px;">
                    <button type="button" class="button button-primary" id="tsfs-embed-copy-btn" style="border-radius:6px; font-weight:600;">
                        <span class="dashicons dashicons-admin-page" style="vertical-align:middle; margin-top:-2px;"></span> <?php esc_html_e( 'Copy', 'techsolution-form-studio' ); ?>
                    </button>
                </div>
            </div>

            <div style="background:#f8fafc; border-left:4px solid var(--tsfs-adm-primary); padding:14px 16px; border-radius:0 8px 8px 0; font-size:13px; color:var(--tsfs-adm-slate-600);">
                <strong style="color:var(--tsfs-adm-slate-800);"><?php esc_html_e( 'PHP Template Tag:', 'techsolution-form-studio' ); ?></strong><br>
                <code style="display:block; margin-top:6px; background:#ffffff; padding:6px 10px; border-radius:4px; border:1px solid #e2e8f0;">&lt;?php echo do_shortcode( '<?php echo esc_html( $shortcode ? $shortcode : '[tsfs_form id="' . $current_form_id . '"]' ); ?>' ); ?&gt;</code>
            </div>
        </div>
        <div class="tsfs-modal-footer">
            <button type="button" class="button button-secondary" id="tsfs-embed-done-btn"><?php esc_html_e( 'Done', 'techsolution-form-studio' ); ?></button>
        </div>
    </div>
</div>

<!-- In-Studio Live Interactive Preview Modal -->
<div id="tsfs-live-preview-modal" class="tsfs-modal-backdrop" style="display:none;">
    <div class="tsfs-modal-dialog tsfs-preview-modal-dialog" style="max-width: 960px; width:95%; height:90vh; display:flex; flex-direction:column; padding:0; overflow:hidden; border-radius:12px; box-shadow:0 25px 50px -12px rgba(0, 0, 0, 0.25);">
        <div class="tsfs-modal-header" style="padding:14px 20px; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; justify-content:space-between; background:#ffffff; flex-shrink:0;">
            <div style="display:flex; align-items:center; gap:12px;">
                <h3 style="margin:0; font-size:16px; font-weight:700; color:#1e293b; display:flex; align-items:center; gap:8px;">
                    <span class="dashicons dashicons-visibility" style="color:var(--tsfs-adm-primary);"></span>
                    <?php esc_html_e( 'Interactive Form Preview', 'techsolution-form-studio' ); ?>
                </h3>
                <span style="font-size:11px; background:#e0e7ff; color:#3730a3; padding:2px 8px; border-radius:12px; font-weight:700;"><?php esc_html_e( 'SIMULATOR', 'techsolution-form-studio' ); ?></span>
            </div>
            <div class="tsfs-modal-preview-devices" style="display:flex; background:#f1f5f9; padding:3px; border-radius:8px; gap:4px;">
                <button type="button" class="tsfs-preview-device-btn active" data-device="desktop" style="border:none; background:#ffffff; padding:5px 12px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:700; display:flex; align-items:center; gap:5px; color:#1e293b; box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                    <span class="dashicons dashicons-desktop" style="font-size:15px; width:15px; height:15px;"></span> <?php esc_html_e( 'Desktop', 'techsolution-form-studio' ); ?>
                </button>
                <button type="button" class="tsfs-preview-device-btn" data-device="tablet" style="border:none; background:transparent; padding:5px 12px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600; display:flex; align-items:center; gap:5px; color:#64748b;">
                    <span class="dashicons dashicons-tablet" style="font-size:15px; width:15px; height:15px;"></span> <?php esc_html_e( 'Tablet (768px)', 'techsolution-form-studio' ); ?>
                </button>
                <button type="button" class="tsfs-preview-device-btn" data-device="mobile" style="border:none; background:transparent; padding:5px 12px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600; display:flex; align-items:center; gap:5px; color:#64748b;">
                    <span class="dashicons dashicons-smartphone" style="font-size:15px; width:15px; height:15px;"></span> <?php esc_html_e( 'Mobile (380px)', 'techsolution-form-studio' ); ?>
                </button>
            </div>
            <button type="button" class="tsfs-modal-close" id="tsfs-live-preview-close-btn">&times;</button>
        </div>
        <div class="tsfs-modal-body tsfs-live-preview-stage" style="flex:1; background:#f8fafc; padding:32px 20px; overflow-y:auto; display:flex; justify-content:center; align-items:flex-start;">
            <div id="tsfs-live-preview-viewport" class="tsfs-preview-viewport device-desktop" style="width:100%; max-width:760px; background:#ffffff; border-radius:12px; box-shadow:0 10px 25px -5px rgba(0,0,0,0.08), 0 8px 10px -6px rgba(0,0,0,0.05); padding:32px; transition:max-width 0.22s cubic-bezier(0.4, 0, 0.2, 1); box-sizing:border-box;">
                <!-- Dynamically rendered interactive form elements will appear here -->
            </div>
        </div>
    </div>
</div>

<!-- Toast notification box -->
<div id="tsfs-toast-box" class="tsfs-toast-box" style="display:none;"></div>
