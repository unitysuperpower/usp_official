/**
 * Submissions screen: filters, bulk actions, entry viewer, notes and exports.
 *
 * @package TechSolution_Form_Studio
 */
(function () {
    'use strict';
    var __ = wp.i18n.__;
    function esc(value) {
        return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

jQuery(document).ready(function($) {
    var adminNonce          = tsfsAdminData.nonce;
    var paginationBaseUrl   = window.tsfsEntriesPage.pagination_base_url;
    var visibleEntryIds     = [];
    var currentEntryIndex   = -1;
    var currentEntryData    = null;

    // Build visible entries array for next/previous traversal
    function refreshVisibleEntries() {
        visibleEntryIds = [];
        $('.tsfs-view-entry-btn').each(function() {
            visibleEntryIds.push(parseInt($(this).data('id'), 10));
        });
    }
    refreshVisibleEntries();

    // ==========================================
    // 1. Bulk Selection Management
    // ==========================================
    function updateSelectedCount() {
        var count = $('.tsfs-entry-cb:checked').length;
        if (count > 0) {
            $('#tsfs-bulk-selected-count').text(count + ' selected');
        } else {
            $('#tsfs-bulk-selected-count').text('');
        }
    }

    $('#tsfs-cb-select-all').on('change', function() {
        var isChecked = $(this).is(':checked');
        $('.tsfs-entry-cb').prop('checked', isChecked);
        updateSelectedCount();
    });

    $(document).on('change', '.tsfs-entry-cb', function() {
        updateSelectedCount();
        var allChecked = ($('.tsfs-entry-cb:checked').length === $('.tsfs-entry-cb').length && $('.tsfs-entry-cb').length > 0);
        $('#tsfs-cb-select-all').prop('checked', allChecked);
    });

    // Bulk Apply Button
    $('#tsfs-bulk-apply-btn').on('click', function(e) {
        e.preventDefault();
        var action = $('#tsfs-bulk-action-selector').val();
        if (action === '-1') {
            if (window.TSFSModal && TSFSModal.alert) {
                TSFSModal.alert({
                    title: 'Action Required',
                    message: 'Please select an action from the bulk actions dropdown.',
                    type: 'warning',
                    buttonText: 'Got it'
                });
            } else {
                alert('Please select an action from the bulk actions dropdown.');
            }
            return;
        }

        var selectedIds = [];
        $('.tsfs-entry-cb:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            if (window.TSFSModal && TSFSModal.alert) {
                TSFSModal.alert({
                    title: 'No Entries Selected',
                    message: 'Please select at least one submission entry using the row checkboxes.',
                    type: 'warning',
                    buttonText: 'Understood'
                });
            } else {
                alert('Please select at least one submission entry using the row checkboxes.');
            }
            return;
        }

        if (action === 'export_selected') {
            openExportModal({
                selectedIds: selectedIds,
                count: selectedIds.length
            });
            return;
        }

        function executeBulkAction() {
            var $btn = $('#tsfs-bulk-apply-btn');
            $btn.prop('disabled', true).text('Applying...');

            $.post(ajaxurl, {
                action: 'tsfs_bulk_entries',
                nonce: adminNonce,
                bulk_action: action,
                entry_ids: selectedIds
            }, function(res) {
                $btn.prop('disabled', false).text('Apply');
                if (res.success) {
                    window.location.reload();
                } else {
                    if (window.TSFSModal && TSFSModal.alert) {
                        TSFSModal.alert({
                            title: 'Bulk Action Failed',
                            message: res.data.message || 'Error performing bulk action.',
                            type: 'error'
                        });
                    } else {
                        alert(res.data.message || 'Error performing bulk action.');
                    }
                }
            }).fail(function() {
                $btn.prop('disabled', false).text('Apply');
                if (window.TSFSModal && TSFSModal.alert) {
                    TSFSModal.alert({
                        title: 'Network Error',
                        message: 'A network error occurred while processing your request.',
                        type: 'error'
                    });
                }
            });
        }

        if (action === 'delete') {
            if (window.TSFSModal && TSFSModal.confirm) {
                TSFSModal.confirm({
                    title: 'Delete Submissions?',
                    message: 'Are you sure you want to permanently delete ' + selectedIds.length + ' submission(s)? This action cannot be undone.',
                    type: 'danger',
                    confirmText: 'Yes, Delete ' + selectedIds.length + ' Submissions',
                    cancelText: 'Cancel'
                }).then(function(confirmed) {
                    if (confirmed) {
                        executeBulkAction();
                    }
                });
            } else if (confirm('Are you sure you want to permanently delete ' + selectedIds.length + ' submissions?')) {
                executeBulkAction();
            }
        } else {
            executeBulkAction();
        }
    });

    // ==========================================
    // 2. Star & Status Row Toggles
    // ==========================================
    function updateRowStarState(entryId, isStarred) {
        var $btn = $('#tsfs-entry-row-' + entryId).find('.tsfs-star-toggle-btn');
        $btn.data('starred', isStarred ? '1' : '0');
        var $icon = $btn.find('.dashicons');
        if (isStarred) {
            $icon.removeClass('dashicons-star-empty').addClass('dashicons-star-filled').css('color', '#f59e0b');
            $btn.attr('title', 'Starred - Click to unstar');
        } else {
            $icon.removeClass('dashicons-star-filled').addClass('dashicons-star-empty').css('color', '#cbd5e1');
            $btn.attr('title', 'Click to star');
        }
    }

    function updateRowStatusState(entryId, status) {
        var $btn = $('#tsfs-entry-row-' + entryId).find('.tsfs-status-toggle-btn');
        var $row = $('#tsfs-entry-row-' + entryId);
        $btn.data('status', status);

        if (status === 'unread') {
            $btn.html('<span style="display:inline-flex; align-items:center; gap:4px; background:#eff6ff; color:#1d4ed8; padding:3px 9px; border-radius:12px; font-size:11px; font-weight:700; border:1px solid #bfdbfe;"><span style="width:6px; height:6px; border-radius:50%; background:#2563eb;"></span> Unread</span>');
            $row.addClass('tsfs-row-unread').css('background', '#f8fafc');
        } else if (status === 'starred') {
            $btn.html('<span style="display:inline-flex; align-items:center; gap:3px; background:#fef3c7; color:#b45309; padding:3px 9px; border-radius:12px; font-size:11px; font-weight:700; border:1px solid #fde68a;"><span class="dashicons dashicons-star-filled" style="font-size:11px; width:11px; height:11px;"></span> Starred</span>');
            $row.removeClass('tsfs-row-unread').css('background', '#ffffff');
        } else {
            $btn.html('<span style="display:inline-flex; align-items:center; gap:4px; background:#f1f5f9; color:#475569; padding:3px 9px; border-radius:12px; font-size:11px; font-weight:600; border:1px solid #e2e8f0;"><span class="dashicons dashicons-yes" style="font-size:11px; width:11px; height:11px; color:#10b981;"></span> Read</span>');
            $row.removeClass('tsfs-row-unread').css('background', '#ffffff');
        }
    }

    // Row Star Toggle
    $(document).on('click', '.tsfs-star-toggle-btn', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var entryId = $btn.data('id');
        var isStarred = $btn.data('starred') == '1';
        var nextStatus = isStarred ? 'read' : 'starred';

        $.post(ajaxurl, {
            action: 'tsfs_toggle_entry_status',
            entry_id: entryId,
            status: nextStatus,
            nonce: adminNonce
        }, function(res) {
            if (res.success) {
                updateRowStarState(entryId, !isStarred);
                if (window.TSFSModal && TSFSModal.toast) {
                    TSFSModal.toast(!isStarred ? 'Submission starred' : 'Submission unstarred', !isStarred ? 'success' : 'info');
                }
            }
        });
    });

    // Row Status Toggle
    $(document).on('click', '.tsfs-status-toggle-btn', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var entryId = $btn.data('id');
        var currentStatus = $btn.data('status');
        var nextStatus = (currentStatus === 'unread') ? 'read' : 'unread';

        $.post(ajaxurl, {
            action: 'tsfs_toggle_entry_status',
            entry_id: entryId,
            status: nextStatus,
            nonce: adminNonce
        }, function(res) {
            if (res.success) {
                updateRowStatusState(entryId, nextStatus);
                if (window.TSFSModal && TSFSModal.toast) {
                    TSFSModal.toast(nextStatus === 'unread' ? 'Marked as unread' : 'Marked as read', 'info');
                }
            }
        });
    });

    // Single Delete
    $('.tsfs-delete-entry-btn').on('click', function(e) {
        e.preventDefault();
        var entryId = $(this).data('id');

        if (window.TSFSModal && TSFSModal.confirm) {
            TSFSModal.confirm({
                title: 'Delete Submission?',
                message: 'Are you sure you want to permanently delete this submission record? This action cannot be undone.',
                type: 'danger',
                confirmText: 'Yes, Delete Record',
                cancelText: 'Cancel'
            }).then(function(confirmed) {
                if (!confirmed) return;
                doDelete(entryId);
            });
        } else if (confirm('Are you sure you want to permanently delete this submission record?')) {
            doDelete(entryId);
        }

        function doDelete(id) {
            $.post(ajaxurl, {
                action: 'tsfs_delete_entry',
                entry_id: id,
                nonce: adminNonce
            }, function(res) {
                if (res.success) {
                    $('#tsfs-entry-row-' + id).fadeOut(200, function() { 
                        $(this).remove(); 
                        refreshVisibleEntries();
                    });
                    if (window.TSFSModal && TSFSModal.toast) {
                        TSFSModal.toast('Submission record deleted.', 'success');
                    }
                } else {
                    if (window.TSFSModal && TSFSModal.alert) {
                        TSFSModal.alert({
                            title: 'Delete Failed',
                            message: res.data.message || 'Error deleting entry',
                            type: 'error'
                        });
                    }
                }
            });
        }
    });

    // ==========================================
    // 3. Quick Date Range Presets Auto-Apply
    // ==========================================
    $('.tsfs-date-preset-btn').on('click', function(e) {
        e.preventDefault();
        var from = $(this).data('from');
        var to   = $(this).data('to');

        $('#filter-date-from').val(from);
        $('#filter-date-to').val(to);

        // Submit filter form immediately for fast UX
        $('#tsfs-entries-filter-form').submit();
    });

    // ==========================================
    // 4. Custom Pagination & Per-Page Controls
    // ==========================================
    $('.tsfs-per-page-select').on('change', function() {
        var perPage = $(this).val();
        var url = new URL(window.location.href);
        url.searchParams.set('per_page', perPage);
        url.searchParams.set('paged', '1');
        window.location.href = url.toString();
    });

    $('#tsfs-page-jump-input').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var targetPage = parseInt($(this).val(), 10);
            var maxPage    = parseInt($(this).attr('max'), 10) || 1;
            if (isNaN(targetPage) || targetPage < 1) targetPage = 1;
            if (targetPage > maxPage) targetPage = maxPage;

            var url = new URL(window.location.href);
            url.searchParams.set('paged', targetPage);
            window.location.href = url.toString();
        }
    });

    // ==========================================
    // 5. Inspection Modal: Next, Prev, Toolbar, Notes
    // ==========================================
    function updateModalToolbar(entry) {
        var isStarred = (entry.status === 'starred');
        var isUnread  = (entry.status === 'unread');

        // Star Button
        var $starBtn = $('#tsfs-modal-star-btn');
        var $starIcon = $('#tsfs-modal-star-icon');
        if (isStarred) {
            $starBtn.addClass('is-active-star');
            $starIcon.removeClass('dashicons-star-empty').addClass('dashicons-star-filled').css('color', '#f59e0b');
            $('#tsfs-modal-star-label').text('Starred');
        } else {
            $starBtn.removeClass('is-active-star');
            $starIcon.removeClass('dashicons-star-filled').addClass('dashicons-star-empty').css('color', 'inherit');
            $('#tsfs-modal-star-label').text('Star');
        }

        // Status Button
        var $statusIcon = $('#tsfs-modal-status-icon');
        if (isUnread) {
            $statusIcon.removeClass('dashicons-marker').addClass('dashicons-yes').css('color', '#10b981');
            $('#tsfs-modal-status-label').text('Mark as Read');
        } else {
            $statusIcon.removeClass('dashicons-yes').addClass('dashicons-marker').css('color', '#2563eb');
            $('#tsfs-modal-status-label').text('Mark as Unread');
        }
    }

    function loadEntryInModal(entryId, index) {
        currentEntryIndex = index;
        var total = visibleEntryIds.length;

        $('#tsfs-modal-nav-counter').text((index + 1) + ' of ' + total);
        $('#tsfs-modal-prev-btn').prop('disabled', index <= 0);
        $('#tsfs-modal-next-btn').prop('disabled', index >= total - 1);

        $('#tsfs-modal-title').text('Submission #' + entryId);
        var $body = $('#tsfs-modal-body');
        $body.html('<div class="spinner is-active" style="float:none; margin: 30px auto; display:block;"></div>');

        $.post(ajaxurl, {
            action: 'tsfs_get_entry_details',
            entry_id: entryId,
            nonce: adminNonce
        }, function(res) {
            if (res.success && res.data.entry) {
                var entry = res.data.entry;
                currentEntryData = entry;

                // Sync table row status
                updateRowStatusState(entryId, entry.status);
                updateRowStarState(entryId, entry.status === 'starred');

                // Update in-modal toolbar buttons
                updateModalToolbar(entry);

                // Build Body HTML
                var html = '<div class="tsfs-entry-details-container">';

                // Metadata Card
                html += '<div style="background:var(--tsfs-adm-slate-50); padding:14px 18px; border-radius:8px; border:1px solid var(--tsfs-adm-slate-200); font-size:13px; color:var(--tsfs-adm-slate-700); margin-bottom:16px; display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:10px;">';
                html += '<div><span style="font-size:11px; font-weight:700; color:var(--tsfs-adm-slate-400); text-transform:uppercase; display:block;">Form:</span> <strong style="color:var(--tsfs-adm-slate-900);">' + esc(entry.form_title || 'Form #' + entry.form_id) + '</strong></div>';
                html += '<div><span style="font-size:11px; font-weight:700; color:var(--tsfs-adm-slate-400); text-transform:uppercase; display:block;">Date & Time:</span> ' + esc(entry.created_at) + '</div>';
                html += '<div><span style="font-size:11px; font-weight:700; color:var(--tsfs-adm-slate-400); text-transform:uppercase; display:block;">Submitter IP:</span> <code>' + esc(entry.user_ip || 'N/A') + '</code></div>';
                html += '</div>';

                // 4. Internal Admin Notes Card
                html += '<div class="tsfs-admin-notes-box">';
                html += '<div class="tsfs-admin-notes-header">';
                html += '<div class="tsfs-admin-notes-title">';
                html += '<span class="dashicons dashicons-admin-comments"></span>';
                html += '<span>Internal Admin Notes</span>';
                html += '<span class="tsfs-admin-notes-badge">Staff Only</span>';
                html += '</div>';
                html += '<div class="tsfs-admin-notes-status" id="tsfs-admin-notes-status">';
                html += '<span class="dashicons dashicons-yes"></span> Saved';
                html += '</div>';
                html += '</div>';
                html += '<textarea id="tsfs-admin-notes-input" class="tsfs-admin-notes-textarea" rows="3" placeholder="Add private notes, customer follow-up notes, or staff remarks...">' + (entry.notes ? esc(entry.notes) : '') + '</textarea>';
                html += '<div class="tsfs-admin-notes-footer">';
                html += '<span style="font-size:11px; color:var(--tsfs-adm-slate-400);">Private notes visible only in admin dashboard.</span>';
                html += '<button type="button" class="button button-secondary" id="tsfs-admin-save-note-btn" style="font-size:12px; height:28px; line-height:26px;">';
                html += '<span class="dashicons dashicons-saved" style="font-size:13px; width:13px; height:13px; vertical-align:middle;"></span> Save Note';
                html += '</button>';
                html += '</div>';
                html += '</div>';

                // Submitted Fields Table
                html += '<div style="margin-bottom:6px;"><strong style="font-size:13.5px; color:var(--tsfs-adm-slate-800);">' + __( 'Submitted Form Fields', 'techsolution-form-studio' ) + '</strong></div>';
                html += '<table class="widefat striped" style="border:1px solid var(--tsfs-adm-slate-200); border-radius:6px; overflow:hidden;"><tbody>';

                if (Array.isArray(entry.fields_data)) {
                    entry.fields_data.forEach(function(item) {
                        var val = item.value;
                        var type = item.type || '';
                        var valFormatted = '';

                        if (typeof val === 'object' && val !== null && val.url) {
                            var safeUrl = /^https?:\/\//i.test(val.url) ? val.url : '';
                            var isImg = /\.(jpe?g|png|gif|webp)$/i.test(String(val.file || safeUrl));
                            valFormatted = '<div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">';
                            if (isImg) {
                                valFormatted += '<a href="' + esc(safeUrl) + '" target="_blank" rel="noopener noreferrer"><img src="' + esc(safeUrl) + '" style="width:52px; height:52px; object-fit:cover; border-radius:6px; border:1px solid #cbd5e1;" alt=""></a>';
                            }
                            valFormatted += '<a href="' + esc(safeUrl) + '" target="_blank" rel="noopener noreferrer" class="button button-small" style="display:inline-flex; align-items:center; gap:5px;"><span class="dashicons dashicons-download" style="font-size:14px; width:14px; height:14px; vertical-align:middle;"></span> ' + esc(val.file || __('Download File', 'techsolution-form-studio')) + '</a>';
                            valFormatted += '</div>';
                        } else if (typeof val === 'string' && /^data:image\/png;base64,[A-Za-z0-9+\/=]+$/.test(val)) {
                            valFormatted = '<img src="' + esc(val) + '" alt="' + esc(__('Signature', 'techsolution-form-studio')) + '" style="max-width:320px; max-height:120px; border:1px solid #e2e8f0; border-radius:6px; background:#fff;">';
                        } else if (typeof val === 'object' && val !== null && !Array.isArray(val)) {
                            valFormatted = '<dl style="margin:0; font-size:12px;">';
                            Object.keys(val).forEach(function(k) {
                                if (typeof val[k] === 'object' || String(val[k]).trim() === '') return;
                                valFormatted += '<dt style="font-weight:700; color:#475569; text-transform:capitalize;">' + esc(k.replace(/_/g, ' ')) + ':</dt><dd style="margin:0 0 6px 10px; color:#0f172a;">' + esc(val[k]) + '</dd>';
                            });
                            valFormatted += '</dl>';
                        } else if (Array.isArray(val)) {
                            valFormatted = val.map(function(choice) {
                                return '<span style="display:inline-block; background:#f1f5f9; color:#334155; padding:2px 8px; border-radius:12px; font-size:12px; font-weight:600; margin:2px 4px 2px 0; border:1px solid #e2e8f0;">' + esc(choice) + '</span>';
                            }).join(' ');
                        } else {
                            valFormatted = esc(val === null || val === undefined ? '' : val).replace(/\n/g, '<br>');
                        }

                        html += '<tr>';
                        html += '<td style="width: 32%; font-weight:700; color:var(--tsfs-adm-slate-800);">' + esc(item.label || item.name) + '</td>';
                        html += '<td style="color:var(--tsfs-adm-slate-700);">' + (valFormatted || '<em style="color:#94a3b8;">(Empty)</em>') + '</td>';
                        html += '</tr>';
                    });
                }
                html += '</tbody></table></div>';
                $body.html(html);
            } else {
                $body.html('<div class="notice notice-error"><p>' + esc(res.data.message || __('Failed to load details', 'techsolution-form-studio')) + '</p></div>');
            }
        });
    }

    // Open Modal on "View" button click
    $(document).on('click', '.tsfs-view-entry-btn', function(e) {
        e.preventDefault();
        refreshVisibleEntries();
        var entryId = parseInt($(this).data('id'), 10);
        var idx = visibleEntryIds.indexOf(entryId);
        if (idx === -1) idx = 0;

        $('#tsfs-entry-modal').fadeIn(150);
        loadEntryInModal(entryId, idx);
    });

    // Open an entry directly from a notification email link (…&entry_id=123).
    if (window.tsfsEntriesPage && window.tsfsEntriesPage.open_entry_id) {
        refreshVisibleEntries();
        var linkedId = parseInt(window.tsfsEntriesPage.open_entry_id, 10);
        var linkedIdx = visibleEntryIds.indexOf(linkedId);
        $('#tsfs-entry-modal').fadeIn(150);
        loadEntryInModal(linkedId, linkedIdx === -1 ? 0 : linkedIdx);
    }

    // Next / Previous Navigation buttons
    $('#tsfs-modal-prev-btn').on('click', function(e) {
        e.preventDefault();
        if (currentEntryIndex > 0) {
            var prevId = visibleEntryIds[currentEntryIndex - 1];
            loadEntryInModal(prevId, currentEntryIndex - 1);
        }
    });

    $('#tsfs-modal-next-btn').on('click', function(e) {
        e.preventDefault();
        if (currentEntryIndex < visibleEntryIds.length - 1) {
            var nextId = visibleEntryIds[currentEntryIndex + 1];
            loadEntryInModal(nextId, currentEntryIndex + 1);
        }
    });

    // In-Modal Star Toggle Button
    $('#tsfs-modal-star-btn').on('click', function(e) {
        e.preventDefault();
        if (!currentEntryData) return;

        var entryId = currentEntryData.id;
        var isStarred = (currentEntryData.status === 'starred');
        var nextStatus = isStarred ? 'read' : 'starred';

        $.post(ajaxurl, {
            action: 'tsfs_toggle_entry_status',
            entry_id: entryId,
            status: nextStatus,
            nonce: adminNonce
        }, function(res) {
            if (res.success) {
                currentEntryData.status = nextStatus;
                updateModalToolbar(currentEntryData);
                updateRowStarState(entryId, !isStarred);
                updateRowStatusState(entryId, nextStatus);

                if (window.TSFSModal && TSFSModal.toast) {
                    TSFSModal.toast(!isStarred ? 'Submission starred' : 'Submission unstarred', !isStarred ? 'success' : 'info');
                }
            }
        });
    });

    // In-Modal Status Toggle Button
    $('#tsfs-modal-status-btn').on('click', function(e) {
        e.preventDefault();
        if (!currentEntryData) return;

        var entryId = currentEntryData.id;
        var currentStatus = currentEntryData.status;
        var nextStatus = (currentStatus === 'unread') ? 'read' : 'unread';

        $.post(ajaxurl, {
            action: 'tsfs_toggle_entry_status',
            entry_id: entryId,
            status: nextStatus,
            nonce: adminNonce
        }, function(res) {
            if (res.success) {
                currentEntryData.status = nextStatus;
                updateModalToolbar(currentEntryData);
                updateRowStatusState(entryId, nextStatus);

                if (window.TSFSModal && TSFSModal.toast) {
                    TSFSModal.toast(nextStatus === 'unread' ? 'Marked as unread' : 'Marked as read', 'info');
                }
            }
        });
    });

    // In-Modal 1-Click Copy Summary
    $('#tsfs-modal-copy-btn').on('click', function(e) {
        e.preventDefault();
        if (!currentEntryData) return;

        var entry = currentEntryData;
        var summaryLines = [];
        summaryLines.push('=========================================');
        summaryLines.push('Form Studio Submission #' + entry.id);
        summaryLines.push('Form: ' + (entry.form_title || 'Form #' + entry.form_id));
        summaryLines.push('Submitted On: ' + entry.created_at);
        summaryLines.push('Submitter IP: ' + (entry.user_ip || 'N/A'));
        summaryLines.push('Status: ' + entry.status.toUpperCase());
        summaryLines.push('=========================================');
        summaryLines.push('');
        summaryLines.push('--- FIELD SUBMISSIONS ---');

        if (Array.isArray(entry.fields_data)) {
            entry.fields_data.forEach(function(item) {
                var label = item.label || item.name || 'Field';
                var val   = item.value;
                var valStr = '';

                if (typeof val === 'object' && val !== null) {
                    if (val.url) {
                        valStr = (val.file || 'File') + ': ' + val.url;
                    } else {
                        valStr = JSON.stringify(val);
                    }
                } else {
                    valStr = String(val || '');
                }

                summaryLines.push(label + ': ' + valStr);
            });
        }

        if (entry.notes) {
            summaryLines.push('');
            summaryLines.push('--- INTERNAL ADMIN NOTES ---');
            summaryLines.push(entry.notes);
        }

        var fullSummary = summaryLines.join('\n');

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(fullSummary).then(function() {
                if (window.TSFSModal && TSFSModal.toast) {
                    TSFSModal.toast('Submission summary copied to clipboard!', 'success');
                } else {
                    alert('Submission summary copied to clipboard!');
                }
            });
        } else {
            // Fallback for non-https / older browsers
            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(fullSummary).select();
            document.execCommand('copy');
            $temp.remove();
            if (window.TSFSModal && TSFSModal.toast) {
                TSFSModal.toast('Submission summary copied to clipboard!', 'success');
            } else {
                alert('Submission summary copied to clipboard!');
            }
        }
    });

    // In-Modal Save Admin Notes
    $(document).on('click', '#tsfs-admin-save-note-btn', function(e) {
        e.preventDefault();
        if (!currentEntryData) return;

        var entryId  = currentEntryData.id;
        var noteText = $('#tsfs-admin-notes-input').val();
        var $btn     = $(this);
        var $status  = $('#tsfs-admin-notes-status');

        $btn.prop('disabled', true).text('Saving...');

        $.post(ajaxurl, {
            action: 'tsfs_save_entry_note',
            entry_id: entryId,
            notes: noteText,
            nonce: adminNonce
        }, function(res) {
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved" style="font-size:13px; width:13px; height:13px; vertical-align:middle;"></span> Save Note');
            if (res.success) {
                currentEntryData.notes = noteText;
                $status.addClass('visible');
                setTimeout(function() {
                    $status.removeClass('visible');
                }, 2500);

                // Update table row note badge
                var $badgeWrapper = $('#tsfs-row-note-wrapper-' + entryId);
                if (noteText.trim()) {
                    $badgeWrapper.html('<span class="tsfs-row-note-indicator" title="' + esc(noteText) + '"><span class="dashicons dashicons-admin-comments" style="font-size:11px; width:11px; height:11px;"></span> Note</span>');
                } else {
                    $badgeWrapper.empty();
                }

                if (window.TSFSModal && TSFSModal.toast) {
                    TSFSModal.toast('Admin note saved.', 'success');
                }
            } else {
                if (window.TSFSModal && TSFSModal.alert) {
                    TSFSModal.alert({
                        title: 'Error',
                        message: res.data.message || 'Failed to save note.',
                        type: 'error'
                    });
                }
            }
        }).fail(function() {
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved" style="font-size:13px; width:13px; height:13px; vertical-align:middle;"></span> Save Note');
            if (window.TSFSModal && TSFSModal.alert) {
                TSFSModal.alert({
                    title: 'Network Error',
                    message: 'Failed to save note due to network error.',
                    type: 'error'
                });
            }
        });
    });

    // Close Modal
    function closeModal() {
        $('#tsfs-entry-modal').fadeOut(150);
        currentEntryIndex = -1;
        currentEntryData  = null;
    }

    $('#tsfs-modal-close-btn, #tsfs-modal-done-btn, .tsfs-modal-backdrop').on('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    // Keyboard Arrow Navigation (Left/Right) & Escape Key
    $(document).on('keydown', function(e) {
        if (!$('#tsfs-entry-modal').is(':visible')) {
            return;
        }

        // Do not intercept arrows if typing in an input or textarea
        var tag = (e.target.tagName || '').toLowerCase();
        if (tag === 'textarea' || tag === 'input') {
            return;
        }

        if (e.key === 'Escape') {
            if ($('#tsfs-export-modal').is(':visible')) {
                closeExportModal();
            } else {
                closeModal();
            }
        } else if (e.key === 'ArrowLeft') {
            $('#tsfs-modal-prev-btn').click();
        } else if (e.key === 'ArrowRight') {
            $('#tsfs-modal-next-btn').click();
        }
    });

    // ==========================================
    // 5. Advanced Submissions Export Modal Logic
    // ==========================================
    var cachedFormHeaders = {};

    function openExportModal(options) {
        options = options || {};
        var selectedIds = options.selectedIds || [];
        var count       = options.count || 0;

        // Reset scope banner
        if (selectedIds.length > 0) {
            $('#tsfs-export-selected-ids').val(selectedIds.join(','));
            $('#tsfs-export-scope-title').text('Exporting ' + selectedIds.length + ' Selected Submission(s)');
            $('#tsfs-export-scope-desc').text('Only the submissions chosen via table checkboxes will be exported.');
            $('#tsfs-export-scope-badge').text(selectedIds.length + ' Selected');
        } else {
            $('#tsfs-export-selected-ids').val('');
            $('#tsfs-export-scope-title').text('Exporting Filtered Submissions');
            $('#tsfs-export-scope-desc').text(wp.i18n.sprintf(__( 'All matching current form and date criteria (%d total).', 'techsolution-form-studio' ), window.tsfsEntriesPage.total_entries));
            $('#tsfs-export-scope-badge').text(wp.i18n.sprintf(__( '%d Records', 'techsolution-form-studio' ), window.tsfsEntriesPage.total_entries));
        }

        // Sync date values from active filter bar if not explicitly changed
        var activeDateFrom = $('#filter-date-from').val() || '';
        var activeDateTo   = $('#filter-date-to').val() || '';
        $('#tsfs-export-date-from').val(activeDateFrom);
        $('#tsfs-export-date-to').val(activeDateTo);

        // Sync form id
        var activeFormId = $('#filter-by-form').val() || '0';
        $('#tsfs-export-modal-form-id').val(activeFormId);

        // Load headers for the chosen form
        loadFormExportHeaders(activeFormId);

        // Open modal
        $('#tsfs-export-modal').fadeIn(150);
    }

    function closeExportModal() {
        $('#tsfs-export-modal').fadeOut(150);
    }

    // Top Header button
    $('#tsfs-open-export-modal-btn').on('click', function(e) {
        e.preventDefault();
        openExportModal();
    });

    // Close buttons
    $('#tsfs-export-modal-close-btn, #tsfs-export-modal-cancel-btn').on('click', function(e) {
        e.preventDefault();
        closeExportModal();
    });

    $('#tsfs-export-modal').on('click', function(e) {
        if (e.target === this) {
            closeExportModal();
        }
    });

    // Format card selection interaction
    $('.tsfs-format-card').on('click', function() {
        $('.tsfs-format-card').removeClass('is-selected').css({
            'border-color': '#cbd5e1',
            'background': '#ffffff'
        });
        $(this).addClass('is-selected').css({
            'border-color': '#2563eb',
            'background': '#f8fafc'
        });
        $(this).find('input[type="radio"]').prop('checked', true);

        var fmt = $(this).find('input[type="radio"]').val();
        if (fmt === 'pdf') {
            $('#tsfs-export-submit-label').text('Generate PDF Report');
        } else if (fmt === 'excel') {
            $('#tsfs-export-submit-label').text('Download Excel File');
        } else {
            $('#tsfs-export-submit-label').text('Download CSV File');
        }
    });

    // Form change in modal re-fetches headers dynamically
    $('#tsfs-export-modal-form-id').on('change', function() {
        var newFormId = $(this).val();
        loadFormExportHeaders(newFormId);
    });

    function updateHeadersCount() {
        var total   = $('.tsfs-export-header-cb').length;
        var checked = $('.tsfs-export-header-cb:checked').length;
        $('#tsfs-export-headers-count').text(checked + ' of ' + total + ' columns selected for export.');
    }

    function loadFormExportHeaders(formId) {
        var $list    = $('#tsfs-export-headers-list');
        var $loading = $('#tsfs-export-headers-loading');

        $list.empty();
        $loading.show();

        if (cachedFormHeaders[formId]) {
            renderHeaderCheckboxes(cachedFormHeaders[formId]);
            $loading.hide();
            return;
        }

        $.post(ajaxurl, {
            action: 'tsfs_get_form_headers',
            nonce: adminNonce,
            form_id: formId
        }, function(res) {
            $loading.hide();
            if (res.success && res.data) {
                cachedFormHeaders[formId] = res.data;
                renderHeaderCheckboxes(res.data);
            } else {
                $list.html('<div style="grid-column:span 2; color:#ef4444; font-size:12px;">Failed to load headers.</div>');
            }
        }).fail(function() {
            $loading.hide();
            $list.html('<div style="grid-column:span 2; color:#ef4444; font-size:12px;">Network error loading headers.</div>');
        });
    }

    function renderHeaderCheckboxes(data) {
        var $list = $('#tsfs-export-headers-list');
        $list.empty();

        var systemHeaders = data.system_headers || [];
        var fieldHeaders  = data.field_headers || [];

        // 1. System Headers Section
        if (systemHeaders.length > 0) {
            var $sysTitle = $('<div style="grid-column:span 2; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; border-bottom:1px solid #f1f5f9; padding-bottom:4px; margin-top:2px;">Standard Record Headers</div>');
            $list.append($sysTitle);

            systemHeaders.forEach(function(item) {
                var checkedAttr = item.default ? 'checked' : '';
                var html = '<label style="display:flex; align-items:center; gap:8px; font-size:12.5px; color:#334155; cursor:pointer; padding:3px 0;">' +
                    '<input type="checkbox" name="export_headers[]" value="' + esc(item.key) + '" class="tsfs-export-header-cb" ' + checkedAttr + ' style="border-radius:4px;">' +
                    '<span>' + esc(item.label) + '</span>' +
                '</label>';
                $list.append(html);
            });
        }

        // 2. Custom Form Fields Section
        if (fieldHeaders.length > 0) {
            var $fieldTitle = $('<div style="grid-column:span 2; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; border-bottom:1px solid #f1f5f9; padding-bottom:4px; margin-top:8px;">Form Submission Fields</div>');
            $list.append($fieldTitle);

            fieldHeaders.forEach(function(item) {
                var checkedAttr = item.default ? 'checked' : '';
                var html = '<label style="display:flex; align-items:center; gap:8px; font-size:12.5px; color:#1e293b; font-weight:500; cursor:pointer; padding:3px 0;">' +
                    '<input type="checkbox" name="export_headers[]" value="' + esc(item.key) + '" class="tsfs-export-header-cb" ' + checkedAttr + ' style="border-radius:4px;">' +
                    '<span>' + esc(item.label) + '</span>' +
                '</label>';
                $list.append(html);
            });
        } else {
            var $noFields = $('<div style="grid-column:span 2; font-size:12px; color:#94a3b8; font-style:italic; padding:6px 0;">No individual input fields detected for this form yet. Standard headers will be exported.</div>');
            $list.append($noFields);
        }

        updateHeadersCount();
    }

    $(document).on('change', '.tsfs-export-header-cb', function() {
        updateHeadersCount();
    });

    $('#tsfs-export-select-all-headers').on('click', function(e) {
        e.preventDefault();
        $('.tsfs-export-header-cb').prop('checked', true);
        updateHeadersCount();
    });

    $('#tsfs-export-deselect-all-headers').on('click', function(e) {
        e.preventDefault();
        $('.tsfs-export-header-cb').prop('checked', false);
        updateHeadersCount();
    });

    // Form submission validation
    $('#tsfs-export-modal-form').on('submit', function(e) {
        var checkedHeaders = $('.tsfs-export-header-cb:checked').length;
        if (checkedHeaders === 0) {
            e.preventDefault();
            if (window.TSFSModal && TSFSModal.alert) {
                TSFSModal.alert({
                    title: 'Header Selection Required',
                    message: 'Please check at least one submission header column to export.',
                    type: 'warning'
                });
            } else {
                alert('Please check at least one submission header column to export.');
            }
            return false;
        }

        // If format is PDF, open in new tab for smooth print preview
        var fmt = $('input[name="export_format"]:checked').val();
        if (fmt === 'pdf') {
            $(this).attr('target', '_blank');
        } else {
            $(this).removeAttr('target');
        }

        setTimeout(function() {
            closeExportModal();
        }, 600);
        return true;
    });
});
})();

jQuery(document).on('click', '[data-tsfs-print-entry]', function () {
    window.print();
});
