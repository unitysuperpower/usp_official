/**
 * TechSolution Form Studio - Global Admin Modal & UI Utilities
 * Provides modern custom confirmation dialogs, alert modals, and toast notifications.
 *
 * @package TSFS_Plugin
 */

(function($) {
    'use strict';

    window.TSFSModal = {
        /**
         * Ensure modal container markup exists in the DOM.
         */
        ensureDialogHtml: function() {
            var $backdrop = $('#tsfs-custom-modal-backdrop');
            if ($backdrop.length) {
                return $backdrop;
            }

            var html = '<div id="tsfs-custom-modal-backdrop" class="tsfs-dialog-backdrop" style="display:none;">' +
                '<div class="tsfs-dialog-card">' +
                '  <div class="tsfs-dialog-icon-wrap" id="tsfs-dialog-icon-box"></div>' +
                '  <h3 class="tsfs-dialog-title" id="tsfs-dialog-title"></h3>' +
                '  <div class="tsfs-dialog-msg" id="tsfs-dialog-msg"></div>' +
                '  <div class="tsfs-dialog-actions" id="tsfs-dialog-actions"></div>' +
                '</div>' +
                '</div>' +
                '<div id="tsfs-global-toast" class="tsfs-toast-box" style="display:none;"></div>';

            $('body').append(html);
            return $('#tsfs-custom-modal-backdrop');
        },

        /**
         * Show custom confirmation dialog.
         *
         * @param {Object} options Config object: { title, message, type: 'danger'|'warning'|'info'|'success', confirmText, cancelText }
         * @returns {jQuery.Promise} Resolves true if confirmed, false if cancelled.
         */
        confirm: function(options) {
            options = options || {};
            var dfd = $.Deferred();
            var $backdrop = this.ensureDialogHtml();
            var $card = $backdrop.find('.tsfs-dialog-card');
            var type = options.type || 'danger';
            var title = options.title || 'Are you sure?';
            var message = options.message || '';
            var confirmText = options.confirmText || (type === 'danger' ? 'Yes, Delete' : 'Confirm');
            var cancelText = options.cancelText || 'Cancel';

            // Select icon
            var iconHtml = '';
            var iconWrapClass = 'tsfs-dialog-icon-' + type;
            if (type === 'danger') {
                iconHtml = '<span class="dashicons dashicons-trash"></span>';
            } else if (type === 'warning') {
                iconHtml = '<span class="dashicons dashicons-warning"></span>';
            } else if (type === 'success') {
                iconHtml = '<span class="dashicons dashicons-yes-alt"></span>';
            } else {
                iconHtml = '<span class="dashicons dashicons-info"></span>';
            }

            $('#tsfs-dialog-icon-box').attr('class', 'tsfs-dialog-icon-wrap ' + iconWrapClass).html(iconHtml);
            $('#tsfs-dialog-title').text(title);
            $('#tsfs-dialog-msg').html(message);

            var confirmBtnClass = (type === 'danger') ? 'tsfs-dialog-btn-danger' : 'tsfs-dialog-btn-primary';
            var actionsHtml = '<button type="button" class="tsfs-dialog-btn-cancel" id="tsfs-dialog-cancel-btn">' + cancelText + '</button>' +
                              '<button type="button" class="' + confirmBtnClass + '" id="tsfs-dialog-confirm-btn">' + confirmText + '</button>';
            $('#tsfs-dialog-actions').html(actionsHtml);

            $backdrop.stop(true, true).fadeIn(180);
            setTimeout(function() {
                $card.addClass('tsfs-dialog-show');
            }, 10);

            function cleanup() {
                $card.removeClass('tsfs-dialog-show');
                $backdrop.stop(true, true).fadeOut(150);
                $(document).off('keydown.tsfsDialog');
            }

            $('#tsfs-dialog-confirm-btn').off('click').on('click', function(e) {
                e.preventDefault();
                cleanup();
                dfd.resolve(true);
            });

            $('#tsfs-dialog-cancel-btn').off('click').on('click', function(e) {
                e.preventDefault();
                cleanup();
                dfd.resolve(false);
            });

            $backdrop.off('click').on('click', function(e) {
                if (e.target === this) {
                    cleanup();
                    dfd.resolve(false);
                }
            });

            $(document).off('keydown.tsfsDialog').on('keydown.tsfsDialog', function(e) {
                if (e.key === 'Escape') {
                    cleanup();
                    dfd.resolve(false);
                } else if (e.key === 'Enter') {
                    cleanup();
                    dfd.resolve(true);
                }
            });

            return dfd.promise();
        },

        /**
         * Show custom alert popup.
         *
         * @param {Object|string} options Config object or message string.
         * @returns {jQuery.Promise} Resolves when dismissed.
         */
        alert: function(options) {
            if (typeof options === 'string') {
                options = { message: options };
            }
            options = options || {};
            var dfd = $.Deferred();
            var $backdrop = this.ensureDialogHtml();
            var $card = $backdrop.find('.tsfs-dialog-card');
            var type = options.type || 'info';
            var title = options.title || (type === 'warning' ? 'Attention' : (type === 'error' ? 'Notice' : 'Information'));
            var message = options.message || '';
            var buttonText = options.buttonText || 'Got it';

            var iconHtml = '';
            var iconWrapClass = 'tsfs-dialog-icon-' + (type === 'error' ? 'danger' : type);
            if (type === 'danger' || type === 'error') {
                iconHtml = '<span class="dashicons dashicons-dismiss"></span>';
            } else if (type === 'warning') {
                iconHtml = '<span class="dashicons dashicons-warning"></span>';
            } else {
                iconHtml = '<span class="dashicons dashicons-info"></span>';
            }

            $('#tsfs-dialog-icon-box').attr('class', 'tsfs-dialog-icon-wrap ' + iconWrapClass).html(iconHtml);
            $('#tsfs-dialog-title').text(title);
            $('#tsfs-dialog-msg').html(message);

            var actionsHtml = '<button type="button" class="tsfs-dialog-btn-primary" id="tsfs-dialog-alert-ok" style="width:100%;">' + buttonText + '</button>';
            $('#tsfs-dialog-actions').html(actionsHtml);

            $backdrop.stop(true, true).fadeIn(180);
            setTimeout(function() {
                $card.addClass('tsfs-dialog-show');
            }, 10);

            function cleanup() {
                $card.removeClass('tsfs-dialog-show');
                $backdrop.stop(true, true).fadeOut(150);
                $(document).off('keydown.tsfsDialog');
            }

            $('#tsfs-dialog-alert-ok').off('click').on('click', function(e) {
                e.preventDefault();
                cleanup();
                dfd.resolve(true);
            });

            $backdrop.off('click').on('click', function(e) {
                if (e.target === this) {
                    cleanup();
                    dfd.resolve(true);
                }
            });

            $(document).off('keydown.tsfsDialog').on('keydown.tsfsDialog', function(e) {
                if (e.key === 'Escape' || e.key === 'Enter') {
                    cleanup();
                    dfd.resolve(true);
                }
            });

            return dfd.promise();
        },

        /**
         * Show floating toast notification.
         */
        toast: function(message, type) {
            this.ensureDialogHtml();
            var $toast = $('#tsfs-global-toast');
            $toast.removeClass('success error warning').addClass(type || 'success').text(message).stop(true, true).fadeIn(200);
            setTimeout(function() {
                $toast.fadeOut(300);
            }, 3000);
        }
    };

})(jQuery);
