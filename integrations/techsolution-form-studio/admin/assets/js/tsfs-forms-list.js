/**
 * All Forms screen: status toggles, shortcode copy, delete, duplicate and import.
 *
 * @package TechSolution_Form_Studio
 */
(function () {
    'use strict';
    var __ = wp.i18n.__;

jQuery(document).ready(function($) {
    var adminNonce = tsfsAdminData.nonce;
    var activeFilter = 'all';

    // Filter engine: Combines Status Filter Tabs + Search Keyword
    function applyFormsFilter() {
        var query = $('#tsfs-forms-search-box').val().toLowerCase().trim();
        var visibleCount = 0;

        $('.tsfs-form-row').each(function() {
            var $row = $(this);
            var title = String($row.data('title') || '');
            var id = String($row.data('id') || '');
            var shortcode = String($row.data('shortcode') || '');
            var status = String($row.data('status') || '');

            var matchesSearch = (query === '') || (title.indexOf(query) !== -1 || id.indexOf(query) !== -1 || shortcode.indexOf(query) !== -1);
            var matchesStatus = (activeFilter === 'all') || (status === activeFilter);

            if (matchesSearch && matchesStatus) {
                $row.show();
                visibleCount++;
            } else {
                $row.hide();
            }
        });

        $('#tsfs-forms-count-visible').text(visibleCount);

        if (visibleCount === 0) {
            $('#tsfs-no-forms-match-row').show();
        } else {
            $('#tsfs-no-forms-match-row').hide();
        }

        if (query.length > 0) {
            $('#tsfs-forms-search-clear').show();
        } else {
            $('#tsfs-forms-search-clear').hide();
        }
    }

    // Status Tab Click
    $('.tsfs-status-pill-btn').on('click', function(e) {
        e.preventDefault();
        $('.tsfs-status-pill-btn').removeClass('active');
        $(this).addClass('active');
        activeFilter = $(this).data('filter');
        applyFormsFilter();
    });

    // Search Input Event
    $('#tsfs-forms-search-box').on('input', function() {
        applyFormsFilter();
    });

    // Clear Search Button
    $('#tsfs-forms-search-clear').on('click', function() {
        $('#tsfs-forms-search-box').val('').trigger('input').focus();
    });

    // Reset Filters Button in Empty Row
    $('#tsfs-reset-filter-btn').on('click', function() {
        $('#tsfs-forms-search-box').val('');
        $('.tsfs-status-pill-btn[data-filter="all"]').trigger('click');
    });

    // Recalculate status counts on tabs
    function recalculateTabCounts() {
        var total = $('.tsfs-form-row').length;
        var live = $('.tsfs-form-row[data-status="publish"]').length;
        var draft = $('.tsfs-form-row[data-status="draft"]').length;
        var closed = $('.tsfs-form-row[data-status="closed"]').length;

        $('#tsfs-count-tab-all').text(total);
        $('#tsfs-count-tab-live').text(live);
        $('#tsfs-count-tab-draft').text(draft);
        $('#tsfs-count-tab-closed').text(closed);
    }

    // Form Status Toggle (Live -> Draft -> Closed -> Live)
    $(document).on('click', '.tsfs-form-status-toggle', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var $row = $btn.closest('.tsfs-form-row');
        var formId = $btn.data('id');
        var currentStatus = $btn.data('status');
        var nextStatus = 'publish';

        if (currentStatus === 'publish') {
            nextStatus = 'draft';
        } else if (currentStatus === 'draft') {
            nextStatus = 'closed';
        } else {
            nextStatus = 'publish';
        }

        $btn.css('opacity', '0.5');

        $.post(ajaxurl, {
            action: 'tsfs_toggle_form_status',
            form_id: formId,
            status: nextStatus,
            nonce: adminNonce
        }, function(res) {
            $btn.css('opacity', '1');
            if (res.success) {
                $btn.data('status', nextStatus);
                $row.attr('data-status', nextStatus).data('status', nextStatus);

                if (nextStatus === 'draft') {
                    $btn.html('<span class="tsfs-status-pill is-draft"><span class="dashicons dashicons-edit"></span> ' + __( 'Draft', 'techsolution-form-studio' ) + '</span>');
                } else if (nextStatus === 'closed') {
                    $btn.html('<span class="tsfs-status-pill is-closed"><span class="dashicons dashicons-lock"></span> ' + __( 'Closed', 'techsolution-form-studio' ) + '</span>');
                } else {
                    $btn.html('<span class="tsfs-status-pill is-live"><span class="dashicons dashicons-yes"></span> ' + __( 'Live', 'techsolution-form-studio' ) + '</span>');
                }

                recalculateTabCounts();
                applyFormsFilter();

                if (window.TSFSModal && TSFSModal.toast) {
                    TSFSModal.toast(res.data.message || 'Status updated', 'success');
                }
            } else {
                if (window.TSFSModal && TSFSModal.alert) {
                    TSFSModal.alert({
                        title: 'Error',
                        message: res.data ? res.data.message : 'Could not update status.',
                        type: 'error'
                    });
                }
            }
        }).fail(function() {
            $btn.css('opacity', '1');
        });
    });

    // Copy shortcode
    $('.tsfs-copy-btn').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var text = $btn.data('clipboard');
        navigator.clipboard.writeText(text).then(function() {
            var orig = $btn.html();
            $btn.html('<span class="dashicons dashicons-yes" style="color:#059669; font-size:14px; width:14px; height:14px; vertical-align:middle;"></span>');
            if (window.TSFSModal && TSFSModal.toast) {
                TSFSModal.toast(__( 'Shortcode copied to clipboard!', 'techsolution-form-studio' ), 'success');
            }
            setTimeout(function() { $btn.html(orig); }, 1800);
        });
    });

    // Delete form with custom modal dialog
    $('.tsfs-delete-form-btn').on('click', function(e) {
        e.preventDefault();
        var formId = $(this).data('id');

        TSFSModal.confirm({
            title: __( 'Delete Form?', 'techsolution-form-studio' ),
            message: __( 'Are you sure you want to delete this form and all its submissions? This action cannot be undone.', 'techsolution-form-studio' ),
            type: 'danger',
            confirmText: __( 'Yes, Delete Form', 'techsolution-form-studio' ),
            cancelText: __( 'Cancel', 'techsolution-form-studio' )
        }).then(function(confirmed) {
            if (!confirmed) return;

            $.post(ajaxurl, {
                action: 'tsfs_delete_form',
                form_id: formId,
                nonce: adminNonce
            }, function(res) {
                if (res.success) {
                    $('#tsfs-form-row-' + formId).fadeOut(200, function() { 
                        $(this).remove(); 
                        recalculateTabCounts();
                        applyFormsFilter();
                    });
                    TSFSModal.toast(__( 'Form deleted successfully.', 'techsolution-form-studio' ), 'success');
                } else {
                    TSFSModal.alert({
                        title: __( 'Delete Failed', 'techsolution-form-studio' ),
                        message: res.data.message || __( 'Error deleting form.', 'techsolution-form-studio' ),
                        type: 'error'
                    });
                }
            });
        });
    });

    // Duplicate form with instant feedback
    $(document).on('click', '.tsfs-duplicate-form-btn', function(e) {
        e.preventDefault();
        var formId = $(this).data('id');
        var $btn = $(this);
        $btn.addClass('disabled');

        $.post(ajaxurl, {
            action: 'tsfs_duplicate_form',
            form_id: formId,
            nonce: adminNonce
        }, function(res) {
            $btn.removeClass('disabled');
            if (res.success && res.data.edit_url) {
                if (window.TSFSModal && TSFSModal.toast) {
                    TSFSModal.toast('Form duplicated successfully! Opening...', 'success');
                }
                setTimeout(function() {
                    window.location.href = res.data.edit_url;
                }, 400);
            } else {
                TSFSModal.alert({
                    title: __( 'Duplicate Failed', 'techsolution-form-studio' ),
                    message: res.data.message || __( 'Error duplicating form.', 'techsolution-form-studio' ),
                    type: 'error'
                });
            }
        }).fail(function() {
            $btn.removeClass('disabled');
            TSFSModal.alert({
                title: 'Network Error',
                message: 'Failed to duplicate form due to connection error.',
                type: 'error'
            });
        });
    });

    // Import Form Modal Controls
    $('#tsfs-open-import-modal').on('click', function(e) {
        e.preventDefault();
        $('#tsfs-import-file-input').val('');
        $('#tsfs-import-json-textarea').val('');
        $('#tsfs-import-modal').fadeIn(150);
    });

    $('#tsfs-import-close-btn, #tsfs-import-cancel-btn').on('click', function(e) {
        e.preventDefault();
        $('#tsfs-import-modal').fadeOut(150);
    });

    $('#tsfs-import-submit-btn').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var fileInput = document.getElementById('tsfs-import-file-input');
        var jsonText = $('#tsfs-import-json-textarea').val().trim();

        if (!fileInput.files.length && !jsonText) {
            TSFSModal.alert({
                title: 'No Data Provided',
                message: 'Please choose a .json file or paste form JSON code to import.',
                type: 'warning'
            });
            return;
        }

        var formData = new FormData();
        formData.append('action', 'tsfs_import_form_json');
        formData.append('nonce', adminNonce);

        if (fileInput.files.length) {
            formData.append('import_file', fileInput.files[0]);
        } else {
            formData.append('import_json', jsonText);
        }

        $btn.prop('disabled', true).text('Importing...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-upload" style="vertical-align:middle; margin-top:-2px;"></span> Import & Open Builder');
                if (res.success && res.data.redirect_url) {
                    window.location.href = res.data.redirect_url;
                } else {
                    TSFSModal.alert({
                        title: 'Import Failed',
                        message: res.data ? res.data.message : 'Invalid JSON file.',
                        type: 'error'
                    });
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-upload" style="vertical-align:middle; margin-top:-2px;"></span> Import & Open Builder');
                TSFSModal.alert({
                    title: 'Network Error',
                    message: 'A network error occurred while importing the form.',
                    type: 'error'
                });
            }
        });
    });
});
})();
