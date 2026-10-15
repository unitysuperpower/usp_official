/**
 * Visual Drag-and-Drop Form Builder Engine.
 * Supports Advanced Field Types, Real-time Conditional Logic, and 1-Click Templates.
 *
 * @package TSFS_Plugin
 */

(function($) {
    'use strict';

    document.documentElement.classList.add('tsfs-builder-view');

    /**
     * Field types registered by add-ons. Each definition may provide:
     *   label, defaults(field), canvas(field) -> html, inspector(field, helpers) -> html,
     *   readInspector(field), preview(field, ctx) -> html, hidePlaceholder (bool)
     */
    var fieldExtensions = {};

    function layoutNumber(key, fallback, min, max) {
        var raw = $('#tsfs-layout-' + key).val();
        var value = raw === '' || raw == null ? fallback : Number(raw);
        return Number.isFinite(value) ? Math.max(min, Math.min(max, Math.round(value))) : fallback;
    }

    function safeTermsUrl(value) {
        try { var url = new URL(value, window.location.href); return ['http:', 'https:'].indexOf(url.protocol) !== -1 ? url.href : ''; }
        catch (error) { return ''; }
    }
    function escapeMarkup(value) { return String(value || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

    function contentPreview(field) {
        var escape = function(value) { return escapeMarkup(value || ''); };
        var tag = ['h2','h3','h4','h5','h6'].indexOf(field.tag) >= 0 ? field.tag : 'h3';
        var mode = field.content_mode || 'section';
        var heading = '<' + tag + '>' + escape(field.label) + '</' + tag + '>';
        if (mode === 'heading') return heading;
        if (mode === 'bullets' || mode === 'numbered') {
            var list = mode === 'numbered' ? 'ol' : 'ul';
            return '<' + list + ' style="padding-left:1.5em;list-style-type:' + (list === 'ol' ? 'decimal' : 'disc') + '">' + String(field.content || '').split(/\r?\n/).filter(function(item) { return item.trim(); }).map(function(item) { return '<li>' + escape(item.trim()) + '</li>'; }).join('') + '</' + list + '>';
        }
        return (mode === 'section' ? heading : '') + '<div style="white-space:pre-line">' + escape(field.content) + '</div>';
    }

    var TSFSBuilder = {
        fields: [],
        settings: {},
        selectedFieldId: null,
        draggedIndex: null,
        historyStack: [],
        historyIndex: -1,
        isHistoryAction: false,
        historyDebounceTimer: null,
        pendingHistoryLabel: null,
        savedState: null,
        isSaving: false,

        templates: {
            contact: {
                title: 'Contact Us',
                settings: {
                    submit_button_text: 'Send Message',
                    success_message: 'Thank you for getting in touch! We have received your message and will respond shortly.',
                    send_email: true,
                    email_subject: 'New contact message from {user_name}'
                },
                fields: [
                    { id: 'tpl_c1', type: 'text', label: 'Full Name', name: 'your_name', placeholder: 'e.g. John Doe', required: true, width: '50' },
                    { id: 'tpl_c2', type: 'email', label: 'Email Address', name: 'your_email', placeholder: 'john@example.com', required: true, width: '50' },
                    { id: 'tpl_c3', type: 'tel', label: 'Phone Number', name: 'phone_number', placeholder: '+1 (555) 000-0000', required: false, width: '50' },
                    { id: 'tpl_c4', type: 'text', label: 'Subject', name: 'subject', placeholder: 'How can we help you?', required: true, width: '50' },
                    { id: 'tpl_c5', type: 'textarea', label: 'Your Message', name: 'message', placeholder: 'Please provide full details about your inquiry...', rows: 4, required: true, width: '100' }
                ]
            },
            job_app: {
                title: 'Job Application & CV',
                settings: {
                    submit_button_text: 'Submit Application',
                    success_message: 'Thank you for applying! Our hiring team will review your application and resume shortly.',
                    send_email: true,
                    email_subject: 'New Job Application: {user_name} - [#{entry_id}]'
                },
                fields: [
                    { id: 'tpl_j0', type: 'html', label: 'Applicant Details', tag: 'h3', content: 'Please fill in your contact information and attach your latest resume.', width: '100' },
                    { id: 'tpl_j1', type: 'text', label: 'Full Name', name: 'applicant_name', placeholder: 'e.g. Jane Smith', required: true, width: '50' },
                    { id: 'tpl_j2', type: 'email', label: 'Email Address', name: 'applicant_email', placeholder: 'jane@example.com', required: true, width: '50' },
                    { id: 'tpl_j3', type: 'tel', label: 'Phone Number', name: 'applicant_phone', placeholder: '+1 (555) 234-5678', required: true, width: '50' },
                    { id: 'tpl_j4', type: 'url', label: 'LinkedIn / Portfolio URL', name: 'portfolio_url', placeholder: 'https://linkedin.com/in/username', required: false, width: '50' },
                    { id: 'tpl_j5', type: 'select', label: 'Position Applied For', name: 'position', options: ['Senior Full-Stack Developer', 'Frontend Engineer', 'UI/UX Designer', 'DevOps Specialist'], required: true, width: '100' },
                    { id: 'tpl_j6', type: 'file', label: 'Attach Resume / CV', name: 'resume_file', allowed_ext: 'pdf,doc,docx', max_size: '10', help_text: 'Supported formats: PDF, DOC, DOCX (Max 10MB)', required: true, width: '100' },
                    { id: 'tpl_j7', type: 'textarea', label: 'Cover Letter / Introduction', name: 'cover_letter', placeholder: 'Why do you think you are the right fit for this position?', rows: 4, required: false, width: '100' }
                ]
            },
            feedback: {
                title: 'Customer Feedback & Rating',
                settings: {
                    submit_button_text: 'Submit Feedback',
                    success_message: 'Thank you for your valuable feedback! We appreciate your time.',
                    send_email: true,
                    email_subject: 'New Customer Feedback Submission [#{entry_id}]'
                },
                fields: [
                    { id: 'tpl_f0', type: 'html', label: 'Share Your Experience', tag: 'h3', content: 'Your feedback helps us continuously improve our services.', width: '100' },
                    { id: 'tpl_f1', type: 'text', label: 'Your Name (Optional)', name: 'client_name', placeholder: 'Enter your name', required: false, width: '50' },
                    { id: 'tpl_f2', type: 'email', label: 'Your Email (Optional)', name: 'client_email', placeholder: 'name@example.com', required: false, width: '50' },
                    { id: 'tpl_f3', type: 'range', label: 'Overall Satisfaction (1 to 10)', name: 'satisfaction_rating', min: 1, max: 10, step: 1, default_val: 8, help_text: 'Slide to rate from 1 (unhappy) to 10 (delighted)', width: '100' },
                    { id: 'tpl_f4', type: 'radio', label: 'Would you recommend us to others?', name: 'recommend_friend', options: ['Definitely Yes', 'Probably', 'Not Sure', 'No'], required: true, width: '100' },
                    { id: 'tpl_f5', type: 'textarea', label: 'What did you like the most?', name: 'liked_most', placeholder: 'Highlight positive features...', rows: 3, required: false, width: '100' },
                    { id: 'tpl_f6', type: 'textarea', label: 'How can we improve?', name: 'improvements', placeholder: 'Suggestions for future updates...', rows: 3, required: false, width: '100' }
                ]
            },
            event_rsvp: {
                title: 'Event RSVP & Registration',
                settings: {
                    submit_button_text: 'Confirm RSVP',
                    success_message: 'Thank you! Your RSVP has been confirmed. We look forward to seeing you at the event.',
                    send_email: true,
                    email_subject: 'Event RSVP Confirmation: {user_name}'
                },
                fields: [
                    { id: 'tpl_e1', type: 'text', label: 'Full Name', name: 'attendee_name', placeholder: 'Attendee Full Name', required: true, width: '50' },
                    { id: 'tpl_e2', type: 'email', label: 'Email Address', name: 'attendee_email', placeholder: 'attendee@example.com', required: true, width: '50' },
                    { id: 'tpl_e3', type: 'radio', label: 'Will you attend the event?', name: 'will_attend', options: ['Yes, I will attend', 'No, unfortunately cannot attend'], required: true, width: '100' },
                    {
                        id: 'tpl_e4',
                        type: 'select',
                        label: 'Meal Preference',
                        name: 'meal_preference',
                        options: ['Standard Catered Meal', 'Vegetarian', 'Vegan', 'Halal', 'Special Dietary Requirements'],
                        required: false,
                        width: '100',
                        enable_conditions: true,
                        condition_action: 'show',
                        condition_match: 'all',
                        conditions: [
                            { field: 'will_attend', operator: '==', value: 'Yes, I will attend' }
                        ]
                    },
                    {
                        id: 'tpl_e5',
                        type: 'text',
                        label: 'Specify Dietary Allergies / Restrictions',
                        name: 'dietary_allergies',
                        placeholder: 'e.g. Gluten intolerance, nut allergies',
                        required: false,
                        width: '100',
                        enable_conditions: true,
                        condition_action: 'show',
                        condition_match: 'all',
                        conditions: [
                            { field: 'meal_preference', operator: '==', value: 'Special Dietary Requirements' }
                        ]
                    },
                    {
                        id: 'tpl_e6',
                        type: 'checkbox',
                        label: 'Additional Guests',
                        name: 'bringing_guests',
                        options: ['Yes, I will bring a guest (+1)'],
                        required: false,
                        width: '100',
                        enable_conditions: true,
                        condition_action: 'show',
                        condition_match: 'all',
                        conditions: [
                            { field: 'will_attend', operator: '==', value: 'Yes, I will attend' }
                        ]
                    },
                    {
                        id: 'tpl_e7',
                        type: 'text',
                        label: 'Guest Full Name',
                        name: 'guest_name',
                        placeholder: 'Enter guest name',
                        required: false,
                        width: '100',
                        enable_conditions: true,
                        condition_action: 'show',
                        condition_match: 'all',
                        conditions: [
                            { field: 'bringing_guests', operator: 'contains', value: 'Yes' }
                        ]
                    }
                ]
            }
        },

        init: function() {
            this.loadInitialData();
            this.bindEvents();
            this.renderCanvas();
            this.applyThemeLive();
            this.takeHistorySnapshot('Initial state');
            if (this.fields.length > 0) {
                this.selectField(this.fields[0].id);
            }
            this.savedState = (window.tsfsBuilderData || {}).form ? this.getCurrentState() : '';
            this.updateSaveStatus();
        },

        loadInitialData: function() {
            var data = window.tsfsBuilderData || {};
            var form = data.form;

            if (form && Array.isArray(form.fields_data)) {
                this.fields = form.fields_data;
                this.settings = form.settings || {};
            } else {
                // Default starter fields for a brand new form
                this.fields = [
                    {
                        id: 'field_' + this.generateId(),
                        type: 'text',
                        label: 'Your Name',
                        name: 'your_name',
                        placeholder: 'Enter full name',
                        required: true,
                        help_text: '',
                        width: '100'
                    },
                    {
                        id: 'field_' + this.generateId(),
                        type: 'email',
                        label: 'Email Address',
                        name: 'your_email',
                        placeholder: 'name@example.com',
                        required: true,
                        help_text: '',
                        width: '100'
                    },
                    {
                        id: 'field_' + this.generateId(),
                        type: 'textarea',
                        label: 'Message',
                        name: 'message',
                        placeholder: 'How can we help you?',
                        required: true,
                        help_text: '',
                        rows: 4,
                        width: '100'
                    }
                ];

                this.settings = {
                    submit_button_text: 'Submit Form',
                    success_message: 'Thank you! Your message has been sent successfully.',
                    redirect_url: '',
                    send_email: true,
                    notification_email: '',
                    email_subject: '',
                    send_user_auto_reply: false,
                    auto_reply_subject: '',
                    auto_reply_message: ''
                };
            }
        },

        escapeAttribute: function(value) {
            return String(value).replace(/&/g, '&amp;').replace(/"/g, '&quot;')
                .replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/'/g, '&#39;');
        },

        generateId: function() {
            return Math.random().toString(36).substring(2, 9);
        },

        collectHistoryControls: function() {
            var controls = {};
            $('#tsfs-panel-form-settings input[id], #tsfs-panel-form-settings textarea[id], #tsfs-panel-form-settings select[id], #tsfs-panel-theme-style input[id], #tsfs-panel-theme-style textarea[id], #tsfs-panel-theme-style select[id]').each(function() {
                controls[this.id] = this.type === 'checkbox' ? $(this).is(':checked') : $(this).val();
            });
            return controls;
        },

        scheduleHistorySnapshot: function(label) {
            if (this.isHistoryAction) return;
            var self = this;
            clearTimeout(this.historyDebounceTimer);
            this.pendingHistoryLabel = label || 'Edit form';
            this.historyDebounceTimer = setTimeout(function() {
                self.flushPendingHistory();
            }, 350);
        },

        flushPendingHistory: function() {
            clearTimeout(this.historyDebounceTimer);
            this.historyDebounceTimer = null;
            if (this.pendingHistoryLabel) {
                var label = this.pendingHistoryLabel;
                this.pendingHistoryLabel = null;
                this.takeHistorySnapshot(label);
            }
        },

        takeHistorySnapshot: function(label) {
            if (this.isHistoryAction) return;
            clearTimeout(this.historyDebounceTimer);
            this.historyDebounceTimer = null;
            this.pendingHistoryLabel = null;
            var snapshot = {
                fields: JSON.parse(JSON.stringify(this.fields)),
                title: $('#tsfs-form-title-input').val() || '',
                controls: this.collectHistoryControls(),
                label: label || 'Change'
            };
            var current = this.historyStack[this.historyIndex];
            if (current && JSON.stringify([current.fields, current.title, current.controls]) ===
                JSON.stringify([snapshot.fields, snapshot.title, snapshot.controls])) return;

            // Only actual changes invalidate a redo branch.
            this.historyStack = this.historyStack.slice(0, this.historyIndex + 1);
            this.historyStack.push(snapshot);
            if (this.historyStack.length > 35) this.historyStack.shift();
            this.historyIndex = this.historyStack.length - 1;
            this.updateHistoryButtons();
            this.updateSaveStatus();
        },

        syncRestoredSettings: function() {
            $('#tsfs-canvas-submit-preview').text($('#tsfs-setting-btn-text').val() || 'Submit');
            [
                ['#tsfs-setting-send-email', '#tsfs-email-target-wrap'],
                ['#tsfs-setting-user-autoreply', '#tsfs-autoreply-fields-wrap'],
                ['#tsfs-setting-limit-submissions', '#tsfs-limit-entries-wrap'],
                ['#tsfs-setting-close-date-toggle', '#tsfs-close-date-wrap'],
                ['#tsfs-setting-require-login', '#tsfs-login-msg-wrap'],
                ['#tsfs-setting-enable-webhook', '#tsfs-webhook-fields-wrap']
            ].forEach(function(pair) {
                $(pair[1]).stop(true, true).toggle($(pair[0]).is(':checked'));
            });
            [
                ['.tsfs-theme-card', 'preset', '#tsfs-theme-preset-val'],
                ['.tsfs-radius-opt', 'radius', '#tsfs-theme-radius-val'],
                ['.tsfs-btn-align-opt', 'align', '#tsfs-theme-button-align-val']
            ].forEach(function(group) {
                $(group[0]).each(function() {
                    $(this).toggleClass('active', String($(this).data(group[1])) === $(group[2]).val());
                });
            });
            this.applyThemeLive();
        },

        updateHistoryButtons: function() {
            var canUndo = this.historyIndex > 0;
            var canRedo = this.historyIndex < this.historyStack.length - 1;

            $('#tsfs-btn-undo').prop('disabled', !canUndo).attr('title', canUndo ? 'Undo (' + this.historyStack[this.historyIndex].label + ') [Ctrl+Z]' : 'Nothing to undo');
            $('#tsfs-btn-redo').prop('disabled', !canRedo).attr('title', canRedo ? 'Redo (' + this.historyStack[this.historyIndex + 1].label + ') [Ctrl+Y]' : 'Nothing to redo');
        },

        undo: function() {
            this.flushPendingHistory();
            if (this.historyIndex <= 0) return;

            var undone = this.historyStack[this.historyIndex];
            this.historyIndex--;
            var target = this.historyStack[this.historyIndex];
            this.restoreSnapshot(target);
            this.updateHistoryButtons();
            this.showToast('Undid: ' + (undone.label || 'Change'), 'info');
        },

        redo: function() {
            this.flushPendingHistory();
            if (this.historyIndex >= this.historyStack.length - 1) return;

            this.historyIndex++;
            var target = this.historyStack[this.historyIndex];
            this.restoreSnapshot(target);
            this.updateHistoryButtons();
            this.showToast('Redo applied: ' + (target.label || 'Next step'), 'info');
        },

        restoreSnapshot: function(snapshot) {
            if (!snapshot) return;

            var activeTab = $('.tsfs-tab-btn.active').data('tab');
            this.isHistoryAction = true;
            var controls = snapshot.controls || {};
            Object.keys(controls).forEach(function(id) {
                var $input = $(document.getElementById(id));
                if ($input.is(':checkbox')) $input.prop('checked', controls[id]);
                else $input.val(controls[id]);
            });
            this.fields = JSON.parse(JSON.stringify(snapshot.fields));
            if (snapshot.title !== undefined) {
                $('#tsfs-form-title-input').val(snapshot.title);
                $('#tsfs-canvas-title-preview').text(snapshot.title || 'Untitled Form');
            }

            this.renderCanvas();
            if (this.selectedFieldId && this.fields.some(function(f) { return f.id === TSFSBuilder.selectedFieldId; })) {
                this.selectField(this.selectedFieldId);
            } else if (this.fields.length > 0) {
                this.selectField(this.fields[0].id);
            } else {
                this.selectedFieldId = null;
                this.renderInspector();
            }

            this.syncRestoredSettings();
            if (activeTab) $('.tsfs-tab-btn').filter(function() {
                return $(this).data('tab') === activeTab;
            }).trigger('click');
            this.isHistoryAction = false;
            this.updateSaveStatus();
        },

        bindEvents: function() {
            var self = this;

            $(window).on('beforeunload.tsfsBuilder', function(e) {
                if (self.hasUnsavedChanges()) {
                    e.preventDefault();
                    e.originalEvent.returnValue = '';
                    return '';
                }
            });
            $('.tsfs-builder-wrap').on('input change', 'input, textarea, select', function() {
                setTimeout(function() { self.updateSaveStatus(); }, 0);
            });
            // Undo and Redo Button Clicks
            $('#tsfs-btn-undo').on('click', function() {
                self.undo();
            });
            $('#tsfs-btn-redo').on('click', function() {
                self.redo();
            });

            // Global Keyboard Shortcuts (Ctrl+Z / Ctrl+Y / Cmd+Z / Cmd+Shift+Z)
            $(document).on('keydown', function(e) {
                var isCmdOrCtrl = e.ctrlKey || e.metaKey;
                if (!isCmdOrCtrl) return;
                if (e.key.toLowerCase() === 's') {
                    e.preventDefault();
                    self.saveForm();
                    return;
                }

                if ($(e.target).is('input, textarea, [contenteditable="true"]')) return;
                if (e.key === 'z' || e.key === 'Z') {
                    if (e.shiftKey) {
                        e.preventDefault();
                        self.redo();
                    } else {
                        if (!$(e.target).is('input, textarea')) {
                            e.preventDefault();
                            self.undo();
                        }
                    }
                } else if (e.key === 'y' || e.key === 'Y') {
                    if (!$(e.target).is('input, textarea')) {
                        e.preventDefault();
                        self.redo();
                    }
                }
            });

            // In-Studio Interactive Live Preview Modal Trigger & Dismiss
            $('#tsfs-open-preview-btn').on('click', function() {
                self.openLivePreview();
            });
            $('#tsfs-live-preview-close-btn').on('click', function() {
                $('#tsfs-live-preview-modal').fadeOut(150);
            });
            $('#tsfs-live-preview-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).fadeOut(150);
                }
            });

            // Live Preview Device Switcher
            $(document).on('click', '.tsfs-preview-device-btn', function() {
                var device = $(this).data('device');
                $('.tsfs-preview-device-btn').removeClass('active').css({'background':'transparent', 'font-weight':'600', 'color':'#64748b', 'box-shadow':'none'});
                $(this).addClass('active').css({'background':'#ffffff', 'font-weight':'700', 'color':'#1e293b', 'box-shadow':'0 1px 2px rgba(0,0,0,0.05)'});

                var $viewport = $('#tsfs-live-preview-viewport');
                $viewport.removeClass('device-desktop device-tablet device-mobile');
                $viewport.addClass('device-' + device);

                if (device === 'desktop') {
                    $viewport.css('max-width', '760px');
                } else if (device === 'tablet') {
                    $viewport.css('max-width', '580px');
                } else if (device === 'mobile') {
                    $viewport.css('max-width', '380px');
                }
            });

            // Responsive Device Switcher in Canvas Header
            $('.tsfs-device-btn').on('click', function() {
                var device = $(this).data('device');
                $('.tsfs-device-btn').removeClass('active');
                $(this).addClass('active');

                var $canvas = $('#tsfs-canvas-container');
                $canvas.removeClass('device-desktop device-tablet device-mobile');
                $canvas.addClass('device-' + device);
            });

            // Template Modal Trigger & Dismiss
            $('#tsfs-open-templates-btn').on('click', function() {
                $('#tsfs-templates-modal').fadeIn(150);
            });

            $('#tsfs-templates-close-btn, #tsfs-templates-cancel-btn').on('click', function() {
                $('#tsfs-templates-modal').fadeOut(150);
            });

            $('#tsfs-templates-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).fadeOut(150);
                }
            });

            // Template Card Selection
            $(document).on('click', '.tsfs-template-card', function() {
                var tplKey = $(this).data('template');
                if (self.templates[tplKey]) {
                    if (self.fields.length > 0) {
                        TSFSModal.confirm({
                            title: 'Load Pre-Built Template?',
                            message: 'Loading this template will replace the current fields on your canvas with the template layout. Do you wish to proceed?',
                            type: 'warning',
                            confirmText: 'Load Template',
                            cancelText: 'Cancel'
                        }).then(function(confirmed) {
                            if (confirmed) {
                                self.loadTemplate(tplKey);
                                $('#tsfs-templates-modal').fadeOut(150);
                            }
                        });
                        return;
                    }
                    self.loadTemplate(tplKey);
                    $('#tsfs-templates-modal').fadeOut(150);
                }
            });

            // Palette item click (Add Field)
            $(document).on('click', '.tsfs-palette-item', function() {
                self.addField($(this).data('type'));
            });

            $('.tsfs-palette-panel-btn').on('click', function() {
                var outline = $(this).data('panel') === 'outline';
                $('.tsfs-palette-panel-btn').removeClass('active').attr('aria-pressed', 'false');
                $(this).addClass('active').attr('aria-pressed', 'true');
                $('#tsfs-add-fields-panel').prop('hidden', outline);
                $('.tsfs-palette-search-wrap').prop('hidden', outline);
                $('#tsfs-outline-panel').prop('hidden', !outline);
                if (outline) self.renderOutline();
            });
            $('#tsfs-outline-search').on('input', function() { self.renderOutline(); });
            $(document).on('click', '.tsfs-outline-field', function() {
                var fieldId = $(this).attr('data-field-id');
                self.selectField(fieldId);
                var card = $('.tsfs-canvas-field-card').filter(function() {
                    return $(this).attr('data-id') === fieldId;
                })[0];
                if (card) card.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            });
            // Canvas field card click (Select Field)
            $(document).on('click', '.tsfs-canvas-field-card', function(e) {
                if ($(e.target).closest('.tsfs-card-actions').length) {
                    return;
                }
                var fieldId = $(this).data('id');
                self.selectField(fieldId);
            });

            // Duplicate Field
            $(document).on('click', '.tsfs-card-duplicate-btn', function(e) {
                e.stopPropagation();
                var fieldId = $(this).closest('.tsfs-canvas-field-card').data('id');
                self.duplicateField(fieldId);
            });

            // Delete Field
            $(document).on('click', '.tsfs-card-delete-btn', function(e) {
                e.stopPropagation();
                var fieldId = $(this).closest('.tsfs-canvas-field-card').data('id');
                self.deleteField(fieldId);
            });

            // Move Up / Move Down
            $(document).on('click', '.tsfs-card-move-up', function(e) {
                e.stopPropagation();
                var fieldId = $(this).closest('.tsfs-canvas-field-card').data('id');
                self.moveField(fieldId, -1);
            });
            $(document).on('click', '.tsfs-card-move-down', function(e) {
                e.stopPropagation();
                var fieldId = $(this).closest('.tsfs-canvas-field-card').data('id');
                self.moveField(fieldId, 1);
            });

            // Tab Navigation in Inspector
            $('.tsfs-tab-btn').on('click', function() {
                var tab = $(this).data('tab');
                $('.tsfs-tab-btn').removeClass('active');
                $(this).addClass('active');
                $('.tsfs-tab-panel').removeClass('active');
                $('#tsfs-panel-' + tab).addClass('active');
            });

            // Width Segmented Option Click
            $(document).on('click', '.tsfs-width-opt', function() {
                var widthVal = $(this).data('val');
                $('.tsfs-width-opt').removeClass('active');
                $(this).addClass('active');

                var field = self.fields.find(function(f) { return f.id === self.selectedFieldId; });
                if (field) {
                    field.width = String(widthVal);
                    var $card = $('.tsfs-canvas-field-card[data-id="' + field.id + '"]');
                    $card.removeClass('width-100 width-50 width-33').addClass('width-' + widthVal);
                    self.scheduleHistorySnapshot('Change field width');
                    self.updateSaveStatus();
                }
            });

            // Conditional Logic Toggle Switch
            $(document).on('change', '#insp-enable-conditions', function() {
                var isEnabled = $(this).is(':checked');
                if (isEnabled) {
                    $('#tsfs-conditional-rules-wrap').slideDown(150);
                    // Add default rule if none exist
                    if ($('#tsfs-condition-rules-list .tsfs-cond-rule-row').length === 0) {
                        self.addConditionRuleToInspector();
                    }
                } else {
                    $('#tsfs-conditional-rules-wrap').slideUp(150);
                }
                self.updateFieldFromInspector();
            });

            // Add Condition Rule Button
            $(document).on('click', '#tsfs-add-rule-btn', function() {
                self.addConditionRuleToInspector();
                self.updateFieldFromInspector();
            });

            // Remove Condition Rule Button
            $(document).on('click', '.tsfs-remove-rule-btn', function() {
                $(this).closest('.tsfs-cond-rule-row').remove();
                self.updateFieldFromInspector();
            });

            // Inspector Real-time Updates (Two-way binding)
            $(document).on('input change', '#tsfs-field-settings-content input, #tsfs-field-settings-content textarea, #tsfs-field-settings-content select', function() {
                self.updateFieldFromInspector();
            });

            // Add Option button for choice fields
            $(document).on('click', '#tsfs-add-option-btn', function() {
                self.addOptionToSelectedField();
            });

            // Remove Option button
            $(document).on('click', '.tsfs-remove-option-btn', function() {
                $(this).closest('.tsfs-option-row').remove();
                self.updateFieldFromInspector();
            });

            // Form Title live sync
            $('#tsfs-form-title-input').on('input', function() {
                $('#tsfs-canvas-title-preview').text($(this).val() || 'Untitled Form');
                self.scheduleHistorySnapshot('Edit form title');
            });

            // Form Submit button live sync
            $('#tsfs-setting-btn-text').on('input', function() {
                $('#tsfs-canvas-submit-preview').text($(this).val() || 'Submit');
            });

            // Save Form Action
            $('#tsfs-save-form-btn').on('click', function() {
                self.saveForm();
            });

            // Drag and Drop ordering on canvas
            this.setupDragAndDrop();

            // Shortcode copy pill
            $('.tsfs-shortcode-tag').on('click', function() {
                var shortcode = $(this).data('shortcode');
                if (shortcode) {
                    navigator.clipboard.writeText(shortcode).then(function() {
                        self.showToast('Shortcode copied to clipboard!', 'success');
                    });
                }
            });

            // Quick Title Edit Icon Click
            $('.tsfs-title-edit-icon').on('click', function() {
                $('#tsfs-form-title-input').focus().select();
            });

            // Palette Real-time Search Filter
            $('#tsfs-palette-search-input').on('input', function() {
                var q = $(this).val().toLowerCase().trim();
                if (!q) {
                    $('.tsfs-palette-item').show();
                    $('.tsfs-fields-group').show();
                    $('#tsfs-palette-no-results').prop('hidden', true);
                    return;
                }
                $('.tsfs-fields-group').each(function() {
                    var $group = $(this);
                    var matched = 0;
                    $group.find('.tsfs-palette-item').each(function() {
                        var text = $(this).find('.title').text().toLowerCase();
                        var type = ($(this).data('type') || '') + ' ' + ($(this).attr('data-keywords') || '');
                        if (text.indexOf(q) !== -1 || type.indexOf(q) !== -1) {
                            $(this).show();
                            matched++;
                        } else {
                            $(this).hide();
                        }
                    });
                    if (matched > 0) {
                        $group.show();
                    } else {
                        $group.hide();
                    }
                });
                $('#tsfs-palette-no-results').prop('hidden',
                    $('.tsfs-palette-item').filter(function() {
                        return this.style.display !== 'none';
                    }).length > 0);
            });

            // Embed Modal Trigger & Dismiss
            $('#tsfs-open-embed-btn').on('click', function() {
                $('#tsfs-embed-modal').fadeIn(150);
            });

            $('#tsfs-embed-close-btn, #tsfs-embed-done-btn').on('click', function() {
                $('#tsfs-embed-modal').fadeOut(150);
            });

            $('#tsfs-embed-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).fadeOut(150);
                }
            });

            $('#tsfs-embed-copy-btn').on('click', function() {
                var code = $('#tsfs-embed-shortcode-input').val();
                if (code) {
                    navigator.clipboard.writeText(code).then(function() {
                        self.showToast('Shortcode copied to clipboard!', 'success');
                    });
                }
            });

            // Restrictions & Scheduling toggles
            $('#tsfs-setting-limit-submissions').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#tsfs-limit-entries-wrap').slideDown(150);
                } else {
                    $('#tsfs-limit-entries-wrap').slideUp(150);
                }
            });

            $('#tsfs-setting-close-date-toggle').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#tsfs-close-date-wrap').slideDown(150);
                } else {
                    $('#tsfs-close-date-wrap').slideUp(150);
                }
            });

            $('#tsfs-setting-require-login').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#tsfs-login-msg-wrap').slideDown(150);
                } else {
                    $('#tsfs-login-msg-wrap').slideUp(150);
                }
            });

            // Webhook toggle
            $('#tsfs-setting-enable-webhook').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#tsfs-webhook-fields-wrap').slideDown(150);
                } else {
                    $('#tsfs-webhook-fields-wrap').slideUp(150);
                }
            });

            // Webhook Test Payload Button
            $('#tsfs-test-webhook-btn').on('click', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var url = ($('#tsfs-setting-webhook-url').val() || '').trim();
                var secret = ($('#tsfs-setting-webhook-secret').val() || '').trim();
                var $status = $('#tsfs-test-webhook-status');

                if (!url) {
                    TSFSModal.alert({
                        title: 'Webhook URL Required',
                        message: 'Please enter a valid Webhook URL before testing.',
                        type: 'warning'
                    });
                    return;
                }

                $btn.prop('disabled', true).text('Dispatching...');
                $status.html('<span style="color:#64748b;">Testing endpoint...</span>');

                $.post(window.tsfsBuilderData.ajax_url, {
                    action: 'tsfs_test_webhook',
                    nonce: window.tsfsBuilderData.nonce,
                    webhook_url: url,
                    webhook_secret: secret
                }, function(res) {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-networking" style="vertical-align:middle; margin-top:-2px;"></span> Send Test Payload');
                    if (res.success) {
                        $status.html('<span style="color:#15803d; font-weight:700;"><span class="dashicons dashicons-yes" style="font-size:16px; vertical-align:middle;"></span> ' + escapeMarkup(res.data.message) + '</span>');
                        TSFSModal.toast(res.data.message, 'success');
                    } else {
                        $status.html('<span style="color:#b91c1c; font-weight:600;"><span class="dashicons dashicons-warning" style="font-size:16px; vertical-align:middle;"></span> ' + escapeMarkup(res.data ? res.data.message : 'Failed') + '</span>');
                        TSFSModal.alert({
                            title: 'Webhook Test Failed',
                            message: res.data ? res.data.message : 'Connection failed.',
                            type: 'error'
                        });
                    }
                }).fail(function() {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-networking" style="vertical-align:middle; margin-top:-2px;"></span> Send Test Payload');
                    $status.html('<span style="color:#b91c1c;">Network Error</span>');
                    TSFSModal.alert({
                        title: 'Network Error',
                        message: 'Could not contact server to dispatch webhook test.',
                        type: 'error'
                    });
                });
            });

            // Export Form JSON button
            $('#tsfs-export-json-btn').on('click', function(e) {
                e.preventDefault();
                self.exportFormJson();
            });

            $('.tsfs-inspector-sidebar').on('input change', '#tsfs-panel-form-settings input, #tsfs-panel-form-settings textarea, #tsfs-panel-form-settings select, #tsfs-panel-theme-style input, #tsfs-panel-theme-style textarea, #tsfs-panel-theme-style select', function() {
                if ($(this).is('[data-layout-setting]')) self.applyThemeLive();
                self.scheduleHistorySnapshot('Edit form settings or style');
            });


            // Theme Preset Selection
            $(document).on('click', '.tsfs-theme-card', function() {
                var $card = $(this);
                var preset = $card.data('preset');
                var color = $card.data('color');
                var bg = $card.data('bg');
                var btn = $card.data('btn');

                $('.tsfs-theme-card').removeClass('active');
                $card.addClass('active');
                $('#tsfs-theme-preset-val').val(preset);

                if (color) {
                    $('#tsfs-theme-primary-picker').val(color);
                    $('#tsfs-theme-primary-color').val(color);
                }
                if (bg) {
                    $('#tsfs-theme-bg-picker').val(bg);
                    $('#tsfs-theme-bg-color').val(bg);
                }
                if (btn) {
                    $('#tsfs-theme-btn-picker').val(btn);
                    $('#tsfs-theme-btn-color').val(btn);
                }

                self.applyThemeLive();
            });

            // Color pickers live sync
            $('#tsfs-theme-primary-picker').on('input', function() {
                $('#tsfs-theme-primary-color').val($(this).val());
                self.applyThemeLive();
            });
            $('#tsfs-theme-primary-color').on('input', function() {
                var val = $(this).val();
                if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
                    $('#tsfs-theme-primary-picker').val(val);
                }
                self.applyThemeLive();
            });

            $('#tsfs-theme-btn-picker').on('input', function() {
                $('#tsfs-theme-btn-color').val($(this).val());
                self.applyThemeLive();
            });
            $('#tsfs-theme-btn-color').on('input', function() {
                var val = $(this).val();
                if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
                    $('#tsfs-theme-btn-picker').val(val);
                }
                self.applyThemeLive();
            });

            $('#tsfs-theme-bg-picker').on('input', function() {
                $('#tsfs-theme-bg-color').val($(this).val());
                self.applyThemeLive();
            });
            $('#tsfs-theme-bg-color').on('input', function() {
                var val = $(this).val();
                if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
                    $('#tsfs-theme-bg-picker').val(val);
                }
                self.applyThemeLive();
            });

            // Corner Radius segmented options
            $('.tsfs-radius-opt').on('click', function() {
                $('.tsfs-radius-opt').removeClass('active');
                $(this).addClass('active');
                var radius = $(this).data('radius');
                $('#tsfs-theme-radius-val').val(radius);
                self.applyThemeLive();
            });

            // Button Alignment options
            $('.tsfs-btn-align-opt').on('click', function() {
                $('.tsfs-btn-align-opt').removeClass('active');
                $(this).addClass('active');
                var align = $(this).data('align');
                $('#tsfs-theme-button-align-val').val(align);
                self.applyThemeLive();
            });

            // Container Shadow select
            $('#tsfs-theme-shadow-val').on('change', function() {
                self.applyThemeLive();
            });

            $(document).on('click', '.tsfs-theme-card, .tsfs-radius-opt, .tsfs-btn-align-opt', function() {
                self.scheduleHistorySnapshot('Change form style');
                self.updateSaveStatus();
            });
        },

        loadTemplate: function(tplKey) {
            this.flushPendingHistory();
            var tpl = this.templates[tplKey];
            if (!tpl) return;
            var knownTypes = (window.tsfsBuilderData || {}).field_types || {};
            if (tpl.fields.some(function(field) { return !knownTypes[field.type]; })) {
                this.showToast('This template uses fields from an add-on that is not active.', 'error');
                return;
            }

            // Generate clean IDs for template fields
            var newFields = JSON.parse(JSON.stringify(tpl.fields));
            newFields.forEach(function(f) {
                f.id = 'field_' + Math.random().toString(36).substring(2, 9);
            });

            this.fields = newFields;
            if (tpl.title) {
                $('#tsfs-form-title-input').val(tpl.title);
                $('#tsfs-canvas-title-preview').text(tpl.title);
            }

            if (tpl.settings) {
                if (tpl.settings.submit_button_text) {
                    $('#tsfs-setting-btn-text').val(tpl.settings.submit_button_text);
                    $('#tsfs-canvas-submit-preview').text(tpl.settings.submit_button_text);
                }
                if (tpl.settings.success_message) {
                    $('#tsfs-setting-success-msg').val(tpl.settings.success_message);
                }
                if (tpl.settings.email_subject) {
                    $('#tsfs-setting-email-subject').val(tpl.settings.email_subject);
                }
            }

            this.renderCanvas();
            if (this.fields.length > 0) {
                this.selectField(this.fields[0].id);
            }
            this.takeHistorySnapshot('Load Template (' + tpl.title + ')');
            this.showToast('Template loaded: ' + tpl.title, 'success');
        },

        setupDragAndDrop: function() {
            var self = this;

            $(document).on('dragstart', '.tsfs-canvas-field-card', function(e) {
                self.draggedIndex = $(this).index();
                e.originalEvent.dataTransfer.effectAllowed = 'move';
            });

            $(document).on('dragover', '.tsfs-canvas-field-card', function(e) {
                e.preventDefault();
                e.originalEvent.dataTransfer.dropEffect = 'move';
                $(this).addClass('drag-over');
            });

            $(document).on('dragleave', '.tsfs-canvas-field-card', function() {
                $(this).removeClass('drag-over');
            });

            $(document).on('drop', '.tsfs-canvas-field-card', function(e) {
                e.preventDefault();
                $(this).removeClass('drag-over');
                var dropIndex = $(this).index();

                if (self.draggedIndex !== null && self.draggedIndex !== dropIndex) {
                    self.flushPendingHistory();
                    var movedItem = self.fields.splice(self.draggedIndex, 1)[0];
                    self.fields.splice(dropIndex, 0, movedItem);
                    self.renderCanvas();
                    self.selectField(movedItem.id);
                    self.takeHistorySnapshot('Reorder Fields');
                }
                self.draggedIndex = null;
            });
        },

        addField: function(type) {
            this.flushPendingHistory();
            var builderData = window.tsfsBuilderData || {};
            var contentMode = {content_heading:'heading',content_paragraph:'paragraph',content_bullets:'bullets',content_numbered:'numbered'}[type];
            if (contentMode) type = 'html';
            var isNationality = type === 'nationality';
            if (isNationality) type = 'country';
            var typeDefs = builderData.field_types || {};
            if (!typeDefs[type]) {
                this.showToast('This field type is not available.', 'error');
                return;
            }
            var extension = fieldExtensions[type];
            var labels = (window.tsfsBuilderData || {}).i18n || {};
            var defaultLabels = {
                text: 'Text Field',
                cnic: 'CNIC / Form-B',
                email: 'Email Address',
                textarea: 'Message / Paragraph',
                number: 'Number',
                tel: 'Phone Number',
                date: 'Select Date',
                time: 'Select Time',
                url: 'Website URL',
                file: 'Upload Document / File',
                rating: 'Star Rating Experience',
                range: 'Rating Slider',
                consent: 'Terms & Privacy Consent',
                hidden: 'Hidden Parameter',
                country: labels.country_label || 'Country',
                select: 'Dropdown Menu',
                radio: 'Radio Selection',
                checkbox: 'Checkboxes',
                html: 'Section Title',
                page_break: 'Multi-Step Page Break'
            };

            var newField = {
                id: 'field_' + this.generateId(),
                type: type,
                label: defaultLabels[type] || (extension && extension.label) || (typeDefs[type] && typeDefs[type].label) || 'New Field',
                name: 'field_' + this.generateId(),
                placeholder: '',
                required: (type === 'email' || type === 'consent'),
                help_text: '',
                width: '100'
            };

            if (type === 'select' || type === 'radio' || type === 'checkbox') {
                newField.options = ['Option 1', 'Option 2', 'Option 3'];
            }

            if (type === 'country') {
                newField.label = isNationality ? (labels.nationality_label || 'Nationality (country)') : newField.label;
                newField.placeholder = labels.country_placeholder || 'Select a country';
            }

            if (type === 'textarea') {
                newField.rows = 4;
            }

            if (type === 'file') {
                newField.allowed_ext = 'pdf,doc,docx,jpg,jpeg,png';
                newField.max_size = '5';
                newField.help_text = 'Allowed: PDF, DOC, JPG, PNG (Max 5MB)';
            }

            if (type === 'rating') {
                newField.max_stars = 5;
                newField.default_val = 5;
                newField.help_text = 'Click a star to rate';
            }

            if (type === 'consent') {
                newField.consent_text = 'I have read and agree to the Terms of Service and Privacy Policy.';
                newField.terms_url = '#';
                newField.terms_label = 'Terms & Conditions';
                newField.help_text = 'Required compliance consent checkbox.';
            }

            if (type === 'page_break') {
                newField.step_title = 'Step 2';
                newField.btn_prev_text = 'Previous';
                newField.btn_next_text = 'Next Step';
            }



            if (type === 'range') {
                newField.min = 1;
                newField.max = 10;
                newField.step = 1;
                newField.default_val = 5;
                newField.help_text = 'Slide to select a value';
            }

            if (type === 'cnic') newField.placeholder = '12345-1234567-1';

            if (type === 'url') {
                newField.placeholder = 'https://example.com';
            }

            if (type === 'hidden') {
                newField.default_val = '';
            }

            if (type === 'html') {
                newField.content_mode = contentMode || 'section';
                newField.tag = 'h3';
                newField.content = contentMode === 'bullets' || contentMode === 'numbered' ? 'First item\nSecond item' : 'Write descriptive notes or section instructions here.';
            }





            var typeDefaults = (typeDefs[type] && typeDefs[type].defaults) || {};
            if (extension) {
                Object.keys(typeDefaults).forEach(function(key) { newField[key] = typeDefaults[key]; });
                if (typeof extension.defaults === 'function') extension.defaults(newField);
            }

            this.fields.push(newField);
            this.renderCanvas();
            this.selectField(newField.id);
            this.takeHistorySnapshot('Add ' + newField.label);
            this.showToast('Field added to form', 'success');
        },

        duplicateField: function(fieldId) {
            this.flushPendingHistory();
            var index = this.fields.findIndex(function(f) { return f.id === fieldId; });
            if (index === -1) return;

            var copy = JSON.parse(JSON.stringify(this.fields[index]));
            copy.id = 'field_' + this.generateId();
            copy.name = copy.name + '_copy';
            copy.label = copy.label + ' (Copy)';

            this.fields.splice(index + 1, 0, copy);
            this.renderCanvas();
            this.selectField(copy.id);
            this.takeHistorySnapshot('Duplicate ' + copy.label);
            this.showToast('Field duplicated', 'success');
        },

        deleteField: function(fieldId) {
            this.flushPendingHistory();
            var self = this;
            function performDelete() {
                self.fields = self.fields.filter(function(f) { return f.id !== fieldId; });
                self.renderCanvas();

                if (self.selectedFieldId === fieldId) {
                    if (self.fields.length > 0) {
                        self.selectField(self.fields[0].id);
                    } else {
                        self.selectedFieldId = null;
                        self.renderInspector();
                    }
                }
                self.takeHistorySnapshot('Remove Field');
                self.showToast('Field removed', 'success');
            }

            if (this.fields.length <= 1) {
                TSFSModal.confirm({
                    title: 'Remove Last Field?',
                    message: 'This is the last field remaining on your form. Are you sure you want to delete it?',
                    type: 'warning',
                    confirmText: 'Yes, Remove Field',
                    cancelText: 'Cancel'
                }).then(function(confirmed) {
                    if (confirmed) {
                        performDelete();
                    }
                });
                return;
            }

            performDelete();
        },

        moveField: function(fieldId, direction) {
            this.flushPendingHistory();
            var index = this.fields.findIndex(function(f) { return f.id === fieldId; });
            if (index === -1) return;

            var newIndex = index + direction;
            if (newIndex < 0 || newIndex >= this.fields.length) return;

            var item = this.fields.splice(index, 1)[0];
            this.fields.splice(newIndex, 0, item);
            this.renderCanvas();
            this.selectField(item.id);
            this.takeHistorySnapshot('Reorder Field');
        },

        selectField: function(fieldId) {
            this.selectedFieldId = fieldId;
            this.updateOutlineSelection();
            $('.tsfs-canvas-field-card').removeClass('active');
            $('.tsfs-canvas-field-card[data-id="' + fieldId + '"]').addClass('active');

            // Switch to Field Options tab
            $('.tsfs-tab-btn[data-tab="field-settings"]').trigger('click');
            this.renderInspector();
            $('#tsfs-panel-field-settings').scrollTop(0);
        },

        getOutlineItems: function(query) {
            query = String(query || '').toLowerCase().trim();
            return this.fields.map(function(field, index) {
                var typeInfo = ((window.tsfsBuilderData || {}).field_types || {})[field.type] || {};
                return { field: field, position: index + 1, typeLabel: typeInfo.label || field.type };
            }).filter(function(item) {
                return [item.field.label, item.field.name, item.field.type, item.typeLabel]
                    .join(' ').toLowerCase().indexOf(query) !== -1;
            });
        },

        updateOutlineSelection: function() {
            var selectedId = this.selectedFieldId;
            $('.tsfs-outline-field').each(function() {
                var selected = $(this).attr('data-field-id') === selectedId;
                $(this).toggleClass('active', selected).attr('aria-pressed', String(selected));
            });
        },

        renderOutline: function() {
            var $list = $('#tsfs-outline-list');
            if (!$list.length) return;
            var self = this;
            var labels = (window.tsfsBuilderData || {}).i18n || {};
            var focusedId = $(document.activeElement).closest('.tsfs-outline-field').attr('data-field-id');
            var items = this.getOutlineItems($('#tsfs-outline-search').val());
            $list.empty();
            items.forEach(function(item) {
                var field = item.field;
                var $button = $('<button>', { type: 'button', class: 'tsfs-outline-field' })
                    .attr('data-field-id', field.id);
                $('<span>', { class: 'tsfs-outline-position' }).text(item.position).appendTo($button);
                var $details = $('<span>', { class: 'tsfs-outline-details' }).appendTo($button);
                $('<span>', { class: 'tsfs-outline-label' }).text(field.label || labels.outline_untitled || 'Untitled field').appendTo($details);
                $('<span>', { class: 'tsfs-outline-type' }).text(item.typeLabel).appendTo($details);
                var badges = [];
                if (field.required) badges.push(labels.outline_required || 'Required');
                if (field.enable_conditions && field.conditions && field.conditions.length) {
                    badges.push(labels.outline_conditions || 'Conditional');
                }
                if (badges.length) $('<span>', { class: 'tsfs-outline-badges' }).text(badges.join(' · ')).appendTo($details);
                $('<li>').append($button).appendTo($list);
                if (focusedId === field.id) $button[0].focus({ preventScroll: true });
            });
            $('#tsfs-outline-count').text((labels.outline_count || '%shown% of %total% fields')
                .replace('%shown%', items.length).replace('%total%', self.fields.length));
            $('#tsfs-outline-empty').prop('hidden', items.length > 0).text(this.fields.length ?
                (labels.outline_no_matches || 'No fields match. Try a different label or field type.') :
                (labels.outline_empty || 'Your form has no fields. Choose Add fields to get started.'));
            this.updateOutlineSelection();
        },

        renderCanvas: function() {
            this.renderOutline();
            var $list = $('#tsfs-canvas-fields-list');
            $list.empty();

            if (this.fields.length === 0) {
                $('#tsfs-canvas-empty').show();
                return;
            } else {
                $('#tsfs-canvas-empty').hide();
            }

            var typeIconMap = {
                'text': 'dashicons-editor-textcolor',
                'email': 'dashicons-email-alt',
                'textarea': 'dashicons-editor-paragraph',
                'number': 'dashicons-calculator',
                'tel': 'dashicons-phone',
                'date': 'dashicons-calendar-alt',
                'time': 'dashicons-clock',
                'url': 'dashicons-admin-links',
                'file': 'dashicons-upload',
                'rating': 'dashicons-star-filled',
                'range': 'dashicons-leftright',
                'hidden': 'dashicons-hidden',
                'country': 'dashicons-admin-site-alt3',
                'consent': 'dashicons-shield',
                'select': 'dashicons-arrow-down-alt2',
                'radio': 'dashicons-marker',
                'checkbox': 'dashicons-yes-alt',
                'html': 'dashicons-heading',
                'cnic': 'dashicons-id',
                'page_break': 'dashicons-editor-break'
            };
            var registeredTypes = (window.tsfsBuilderData || {}).field_types || {};

            var self = this;
            this.fields.forEach(function(field, idx) {
                var isActive = (field.id === self.selectedFieldId) ? 'active' : '';
                var isFirst = (idx === 0);
                var isLast = (idx === self.fields.length - 1);
                var widthClass = 'width-' + (field.width || '100');
                var typeIcon = typeIconMap[field.type] || (registeredTypes[field.type] && registeredTypes[field.type].icon) || 'dashicons-admin-generic';

                var cardHtml = '<div class="tsfs-canvas-field-card ' + isActive + ' ' + widthClass + '" data-id="' + field.id + '" draggable="true">';
                
                // Top bar of card
                cardHtml += '<div class="tsfs-card-top-bar">';
                cardHtml += '  <div class="tsfs-card-handle" title="Drag to reorder">';
                cardHtml += '    <span class="dashicons dashicons-menu tsfs-handle-move-icon"></span>';
                cardHtml += '    <span class="tsfs-card-type-tag"><span class="dashicons ' + escapeMarkup(typeIcon) + '"></span> ' + escapeMarkup(String(field.type).toUpperCase()) + '</span>';
                if (field.enable_conditions && Array.isArray(field.conditions) && field.conditions.length > 0) {
                    cardHtml += '    <span class="tsfs-card-cond-badge" title="Has conditional logic rules"><span class="dashicons dashicons-randomize"></span> If/Then</span>';
                }
                cardHtml += '  </div>';
                cardHtml += '  <div class="tsfs-card-actions">';
                if (!isFirst) {
                    cardHtml += '    <button type="button" class="tsfs-card-action-btn tsfs-card-move-up" title="Move Up" aria-label="Move Up"><span class="dashicons dashicons-arrow-up-alt2"></span></button>';
                }
                if (!isLast) {
                    cardHtml += '    <button type="button" class="tsfs-card-action-btn tsfs-card-move-down" title="Move Down" aria-label="Move Down"><span class="dashicons dashicons-arrow-down-alt2"></span></button>';
                }
                cardHtml += '    <button type="button" class="tsfs-card-action-btn tsfs-card-duplicate-btn" title="Duplicate Field" aria-label="Duplicate Field"><span class="dashicons dashicons-admin-page"></span></button>';
                cardHtml += '    <button type="button" class="tsfs-card-action-btn btn-delete tsfs-card-delete-btn" title="Delete Field" aria-label="Delete Field"><span class="dashicons dashicons-trash"></span></button>';
                cardHtml += '  </div>';
                cardHtml += '</div>';

                // Field preview
                if (field.type === 'page_break') {
                    cardHtml += '<div class="tsfs-mock-hidden-badge"><span class="dashicons dashicons-editor-break"></span> <span>Page break — next step: <strong>' + escapeMarkup(field.step_title || 'Next step') + '</strong></span></div>';
                } else if (field.type === 'html') {
                    cardHtml += '<div class="tsfs-mock-section-block">' + contentPreview(field) + '</div>';
                } else if (field.type === 'hidden') {
                    cardHtml += '<div class="tsfs-mock-hidden-badge">';
                    cardHtml += '  <span class="dashicons dashicons-hidden"></span> ';
                    cardHtml += '  <span>Hidden Parameter: <code>' + escapeMarkup(field.name || 'key') + '</code> = <em>"' + escapeMarkup(field.default_val || '') + '"</em></span>';
                    cardHtml += '</div>';
                } else {
                    cardHtml += '<label class="tsfs-card-label">' + escapeMarkup(field.label || 'Untitled Field') + (field.required ? '<span class="required-star">*</span>' : '') + '</label>';

                    if (fieldExtensions[field.type] && typeof fieldExtensions[field.type].canvas === 'function') {
                        cardHtml += fieldExtensions[field.type].canvas(field, { escape: escapeMarkup });
                    } else if (!registeredTypes[field.type]) {
                        var unavailable = ((window.tsfsBuilderData || {}).i18n || {}).unavailable_type || 'This field type is provided by an add-on that is not active.';
                        cardHtml += '<div class="tsfs-mock-hidden-badge"><span class="dashicons dashicons-warning"></span> <span>' + escapeMarkup(unavailable) + '</span></div>';
                    } else if (field.type === 'textarea') {
                        cardHtml += '<textarea class="tsfs-card-mock-input" rows="' + (field.rows || 3) + '" placeholder="' + escapeMarkup(field.placeholder || 'Enter ' + (field.label || 'message') + '...') + '" disabled></textarea>';
                    } else if (field.type === 'select' || field.type === 'country') {
                        cardHtml += '<div class="tsfs-mock-select-wrap">';
                        cardHtml += '  <select class="tsfs-card-mock-input" disabled><option>' + escapeMarkup(field.placeholder || 'Select an option...') + '</option></select>';
                        cardHtml += '  <span class="dashicons dashicons-arrow-down-alt2 tsfs-mock-select-arrow"></span>';
                        cardHtml += '</div>';
                    } else if (field.type === 'radio') {
                        var opts = field.options || ['Choice 1', 'Choice 2'];
                        cardHtml += '<div class="tsfs-mock-choices-list">';
                        opts.forEach(function(o) {
                            cardHtml += '<label class="tsfs-mock-choice-item"><input type="radio" disabled> <span>' + escapeMarkup(o) + '</span></label>';
                        });
                        cardHtml += '</div>';
                    } else if (field.type === 'checkbox') {
                        var opts = field.options || ['Option 1', 'Option 2'];
                        cardHtml += '<div class="tsfs-mock-choices-list">';
                        opts.forEach(function(o) {
                            cardHtml += '<label class="tsfs-mock-choice-item"><input type="checkbox" disabled> <span>' + escapeMarkup(o) + '</span></label>';
                        });
                        cardHtml += '</div>';
                    } else if (field.type === 'rating') {
                        var maxStars = parseInt(field.max_stars, 10) || 5;
                        cardHtml += '<div class="tsfs-mock-rating-wrap" style="display:flex; align-items:center; gap:6px; padding:6px 0;">';
                        for (var starI = 1; starI <= maxStars; starI++) {
                            cardHtml += '<span class="dashicons dashicons-star-filled" style="font-size:24px; width:24px; height:24px; color:#f59e0b;"></span>';
                        }
                        cardHtml += '  <span style="font-size:12px; color:#64748b; margin-left:6px; font-weight:600;">(1 to ' + maxStars + ' stars)</span>';
                        cardHtml += '</div>';
                    } else if (field.type === 'consent') {
                        cardHtml += '<div class="tsfs-mock-consent-wrap" style="display:flex; align-items:flex-start; gap:10px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px; margin-top:4px;">';
                        cardHtml += '  <input type="checkbox" disabled style="margin-top:2px;">';
                        cardHtml += '  <div style="font-size:13px; color:#334155; line-height:1.45;">';
                        cardHtml += '    <span>' + escapeMarkup(field.consent_text || 'I agree to the Terms of Service and Privacy Policy.') + '</span> ';
                        if (field.terms_url) {
                            cardHtml += '    <a href="' + escapeMarkup(safeTermsUrl(field.terms_url)) + '" target="_blank" rel="noopener noreferrer" style="color:#4f46e5; text-decoration:underline; font-weight:600;">' + escapeMarkup(field.terms_label || 'Terms & Conditions') + '</a>';
                        }
                        cardHtml += '  </div>';
                        cardHtml += '</div>';
                    } else if (field.type === 'range') {
                        cardHtml += '<div class="tsfs-mock-range-wrap">';
                        cardHtml += '  <div class="tsfs-mock-range-header">';
                        cardHtml += '    <span>Selected: <strong>' + (field.default_val || 5) + '</strong></span>';
                        cardHtml += '    <span class="tsfs-mock-range-bounds">Range: ' + (field.min || 1) + ' – ' + (field.max || 10) + '</span>';
                        cardHtml += '  </div>';
                        cardHtml += '  <input type="range" class="tsfs-card-mock-input tsfs-mock-range-input" min="' + (field.min || 1) + '" max="' + (field.max || 10) + '" value="' + (field.default_val || 5) + '" disabled />';
                        cardHtml += '</div>';
                    } else if (field.type === 'file') {
                        cardHtml += '<div class="tsfs-mock-file-card">';
                        cardHtml += '  <div class="tsfs-mock-file-icon"><span class="dashicons dashicons-upload"></span></div>';
                        cardHtml += '  <div class="tsfs-mock-file-text">';
                        cardHtml += '    <strong>Click or drag file to upload</strong>';
                        cardHtml += '    <small>Allowed: ' + (field.allowed_ext || 'pdf, doc, docx, jpg, png') + ' (Max: ' + (field.max_size || 5) + 'MB)</small>';
                        cardHtml += '  </div>';
                        cardHtml += '</div>';
                    } else if (field.type === 'time') {
                        cardHtml += '<input type="time" class="tsfs-card-mock-input" disabled />';
                    } else if (field.type === 'date') {
                        cardHtml += '<input type="text" class="tsfs-card-mock-input" placeholder="YYYY-MM-DD" disabled />';
                    } else {
                        cardHtml += '<input type="text" class="tsfs-card-mock-input" placeholder="' + escapeMarkup(field.placeholder || 'Enter ' + (field.label || 'value') + '...') + '" disabled />';
                    }
                }

                if (field.help_text && field.type !== 'file') {
                    cardHtml += '<div class="tsfs-card-mock-help">' + escapeMarkup(field.help_text) + '</div>';
                }

                cardHtml += '</div>';
                $list.append(cardHtml);
            });
        },

        renderInspector: function() {
            var $container = $('#tsfs-field-settings-content');
            var field = this.fields.find(function(f) { return f.id === TSFSBuilder.selectedFieldId; });

            if (!field) {
                $container.html('<div class="tsfs-empty-inspector"><span class="dashicons dashicons-arrow-left-alt"></span><p>Select a field on the canvas to configure its properties & conditional logic.</p></div>');
                return;
            }

            var currentWidth = field.width || '100';

            var inspectorLabels = (window.tsfsBuilderData || {}).i18n || {};
            var position = this.fields.indexOf(field) + 1;
            var positionText = (inspectorLabels.field_position || 'Field %position% of %total%')
                .replace('%position%', position).replace('%total%', this.fields.length);
            var html = '<div class="tsfs-field-inspector-form">';
            html += '<div class="tsfs-inspector-context"><span class="dashicons dashicons-admin-generic" aria-hidden="true"></span><span>' + escapeMarkup(positionText) + '</span></div>';
            html += '<div class="tsfs-settings-form-group">';
            html += '  <label for="insp-label">Field Label</label>';
            html += '  <input type="text" id="insp-label" value="' + this.escapeAttribute((field.label || '')) + '">';
            html += '</div>';

            html += '<div class="tsfs-settings-form-group">';
            html += '  <label for="insp-name">Field Key (Identifier)</label>';
            html += '  <input type="text" id="insp-name" value="' + this.escapeAttribute((field.name || '')) + '">';
            html += '  <p class="description">Used in database submissions and conditional rules.</p>';
            html += '</div>';

            // Width Control (Segmented)
            if (field.type !== 'html' && field.type !== 'hidden' && field.type !== 'page_break') {
                html += '<div class="tsfs-settings-form-group">';
                html += '  <label>Field Width in Row</label>';
                html += '  <div class="tsfs-segmented-control">';
                html += '    <button type="button" class="tsfs-width-opt ' + (currentWidth === '100' ? 'active' : '') + '" data-val="100"><span class="dashicons dashicons-editor-justify"></span> 100% Full</button>';
                html += '    <button type="button" class="tsfs-width-opt ' + (currentWidth === '50' ? 'active' : '') + '" data-val="50"><span class="dashicons dashicons-columns"></span> 50% Half</button>';
                html += '    <button type="button" class="tsfs-width-opt ' + (currentWidth === '33' ? 'active' : '') + '" data-val="33"><span class="dashicons dashicons-grid-view"></span> 33% 1/3</button>';
                html += '  </div>';
                html += '</div>';
            }

            var extension = fieldExtensions[field.type];
            if (!(extension && extension.hidePlaceholder) && field.type !== 'html' && field.type !== 'radio' && field.type !== 'checkbox' && field.type !== 'range' && field.type !== 'file' && field.type !== 'hidden' && field.type !== 'rating' && field.type !== 'consent' && field.type !== 'page_break') {
                html += '<div class="tsfs-settings-form-group">';
                html += '  <label for="insp-placeholder">Placeholder Text</label>';
                html += '  <input type="text" id="insp-placeholder" value="' + this.escapeAttribute((field.placeholder || '')) + '">';
                html += '</div>';
            }

            if (field.type === 'textarea') {
                html += '<div class="tsfs-settings-form-group">';
                html += '  <label>Rows (Height)</label>';
                html += '  <input type="number" id="insp-rows" min="2" max="15" value="' + (field.rows || 4) + '">';
                html += '</div>';
            }

            if (field.type === 'number') {
                html += '<div style="display:flex; gap:10px;">';
                html += '  <div class="tsfs-settings-form-group" style="flex:1;">';
                html += '    <label>Min Value</label>';
                html += '    <input type="number" id="insp-min" value="' + (field.min || '') + '">';
                html += '  </div>';
                html += '  <div class="tsfs-settings-form-group" style="flex:1;">';
                html += '    <label>Max Value</label>';
                html += '    <input type="number" id="insp-max" value="' + (field.max || '') + '">';
                html += '  </div>';
                html += '</div>';
            }

            if (field.type === 'range') {
                html += '<div style="display:flex; gap:10px;">';
                html += '  <div class="tsfs-settings-form-group" style="flex:1;">';
                html += '    <label>Min</label>';
                html += '    <input type="number" id="insp-range-min" value="' + (field.min || 1) + '">';
                html += '  </div>';
                html += '  <div class="tsfs-settings-form-group" style="flex:1;">';
                html += '    <label>Max</label>';
                html += '    <input type="number" id="insp-range-max" value="' + (field.max || 10) + '">';
                html += '  </div>';
                html += '  <div class="tsfs-settings-form-group" style="flex:1;">';
                html += '    <label>Default</label>';
                html += '    <input type="number" id="insp-range-default" value="' + (field.default_val || 5) + '">';
                html += '  </div>';
                html += '</div>';
            }

            if (field.type === 'file') {
                html += '<div class="tsfs-settings-form-group">';
                html += '  <label>Allowed Extensions</label>';
                html += '  <input type="text" id="insp-allowed-ext" value="' + escapeMarkup(field.allowed_ext || 'pdf,doc,docx,jpg,jpeg,png') + '">';
                html += '  <p class="description">Comma-separated: pdf, doc, docx, jpg, png</p>';
                html += '</div>';

                html += '<div class="tsfs-settings-form-group">';
                html += '  <label>Max File Size (MB)</label>';
                html += '  <input type="number" id="insp-max-size" min="1" max="' + ((window.tsfsBuilderData || {}).max_upload_mb || 50) + '" value="' + escapeMarkup(field.max_size || 5) + '">';
                html += '  <p class="description">Server upload limit: ' + ((window.tsfsBuilderData || {}).max_upload_mb || 50) + ' MB</p>';
                html += '</div>';
            }

            if (field.type === 'rating') {
                html += '<div class="tsfs-settings-form-group">';
                html += '  <label>Maximum Rating Stars</label>';
                html += '  <select id="insp-rating-stars">';
                var currentStars = parseInt(field.max_stars, 10) || 5;
                [3, 5, 7, 10].forEach(function(s) {
                    var sel = (currentStars === s) ? 'selected' : '';
                    html += '<option value="' + s + '" ' + sel + '>' + s + ' Stars</option>';
                });
                html += '  </select>';
                html += '</div>';
            }

            if (field.type === 'consent') {
                html += '<div class="tsfs-settings-form-group">';
                html += '  <label>Agreement Statement Text</label>';
                html += '  <textarea id="insp-consent-text" rows="3">' + escapeMarkup(field.consent_text || 'I have read and agree to the Terms of Service and Privacy Policy.') + '</textarea>';
                html += '</div>';
                html += '<div class="tsfs-settings-form-group">';
                html += '  <label>Terms Page Link (URL)</label>';
                html += '  <input type="url" id="insp-terms-url" placeholder="https://example.com/terms" value="' + escapeMarkup(field.terms_url || '') + '">';
                html += '</div>';
                html += '<div class="tsfs-settings-form-group">';
                html += '  <label>Terms Link Text</label>';
                html += '  <input type="text" id="insp-terms-label" placeholder="Terms & Conditions" value="' + escapeMarkup(field.terms_label || 'Terms & Conditions') + '">';
                html += '</div>';
            }

            if (field.type === 'hidden') {
                html += '<div class="tsfs-settings-form-group">';
                html += '  <label>Default Value</label>';
                html += '  <input type="text" id="insp-hidden-val" value="' + escapeMarkup(field.default_val || '') + '">';
                html += '</div>';
            }

            if (field.type === 'html') {
                html += '<div class="tsfs-settings-form-group"><label>Content Type</label><select id="insp-content-mode">';
                [['section','Heading + note (existing)'],['heading','Heading'],['paragraph','Paragraph'],['bullets','Bulleted list'],['numbered','Numbered list']].forEach(function(option) {
                    html += '<option value="' + option[0] + '" ' + ((field.content_mode || 'section') === option[0] ? 'selected' : '') + '>' + option[1] + '</option>';
                });
                html += '</select><p>Use the label for headings. Enter one item per line for lists, or blank lines between paragraphs.</p></div>';
                html += '<div class="tsfs-settings-form-group">';
                html += '  <label>Heading Tag</label>';
                html += '  <select id="insp-tag">';
                ['h2', 'h3', 'h4', 'h5', 'h6'].forEach(function(tag) {
                    var sel = (field.tag === tag) ? 'selected' : '';
                    html += '<option value="' + tag + '" ' + sel + '>' + tag.toUpperCase() + '</option>';
                });
                html += '  </select>';
                html += '</div>';

                html += '<div class="tsfs-settings-form-group">';
                html += '  <label>Paragraph text / list items</label>';
                html += '  <textarea id="insp-content" rows="4">' + escapeMarkup(field.content || '') + '</textarea>';
                html += '</div>';
            }

            if (extension && typeof extension.inspector === 'function') {
                html += extension.inspector(field, { escape: escapeMarkup });
            }

            if (field.type === 'page_break') {
                html += '<div class="tsfs-settings-form-group"><label for="insp-step-title">Next step title</label><input type="text" id="insp-step-title" value="' + escapeMarkup(field.step_title || '') + '"></div>';
                html += '<div style="display:flex; gap:10px;">';
                html += '  <div class="tsfs-settings-form-group" style="flex:1;"><label for="insp-step-prev">Previous button</label><input type="text" id="insp-step-prev" value="' + escapeMarkup(field.btn_prev_text || 'Previous') + '"></div>';
                html += '  <div class="tsfs-settings-form-group" style="flex:1;"><label for="insp-step-next">Next button</label><input type="text" id="insp-step-next" value="' + escapeMarkup(field.btn_next_text || 'Next Step') + '"></div>';
                html += '</div>';
            }

            if (field.type === 'country') {
                var countryInfo = ((window.tsfsBuilderData || {}).i18n || {}).country_info ||
                    'The country list is included automatically. Change the label to ask for residence or nationality. Answers are saved as country names.';
                html += '<div class="tsfs-country-inspector-note"><span class="dashicons dashicons-admin-site-alt3" aria-hidden="true"></span><p>' + escapeMarkup(countryInfo) + '</p></div>';
            }

            // Options for choice fields (Select, Radio, Checkbox)
            if (field.type === 'select' || field.type === 'radio' || field.type === 'checkbox') {
                html += '<div class="tsfs-settings-form-group">';
                html += '  <label>Choices / Options</label>';
                html += '  <div class="tsfs-options-list" id="insp-options-list">';
                var options = field.options || ['Option 1', 'Option 2'];
                options.forEach(function(opt) {
                    html += '<div class="tsfs-option-row">';
                    html += '  <input type="text" class="insp-option-val" value="' + escapeMarkup(opt) + '">';
                    html += '  <button type="button" class="tsfs-remove-option-btn" title="Remove Option">&times;</button>';
                    html += '</div>';
                });
                html += '  </div>';
                html += '  <button type="button" class="button button-small" id="tsfs-add-option-btn">+ Add Option</button>';
                html += '</div>';
            }

            if (field.type !== 'html' && field.type !== 'hidden' && field.type !== 'page_break') {
                html += '<div class="tsfs-settings-form-group">';
                html += '  <label for="insp-help">Help / Description Text</label>';
                html += '  <input type="text" id="insp-help" value="' + this.escapeAttribute((field.help_text || '')) + '">';
                html += '</div>';

                html += '<div class="tsfs-toggle-row" style="margin-top:8px;">';
                html += '  <div class="tsfs-toggle-info">';
                html += '    <span class="tsfs-toggle-title">Required Field</span>';
                html += '    <span class="tsfs-toggle-desc">Require user to fill before submit</span>';
                html += '  </div>';
                html += '  <label class="tsfs-toggle-switch">';
                html += '    <input type="checkbox" id="insp-required" ' + (field.required ? 'checked' : '') + '>';
                html += '    <span class="tsfs-toggle-slider"></span>';
                html += '  </label>';
                html += '</div>';
            }

            // --- CONDITIONAL LOGIC SECTION ---
            if (field.type !== 'hidden') {
                var isCondEnabled = !!field.enable_conditions;
                var action = field.condition_action || 'show';
                var match = field.condition_match || 'all';

                html += '<hr style="margin:14px 0 10px 0; border:0; border-top:1px solid var(--tsfs-adm-slate-200);">';
                html += '<div class="tsfs-toggle-row">';
                html += '  <div class="tsfs-toggle-info">';
                html += '    <span class="tsfs-toggle-title"><span class="dashicons dashicons-randomize" style="font-size:15px; vertical-align:middle; color:var(--tsfs-adm-primary); margin-right:4px;"></span> Conditional Logic</span>';
                html += '    <span class="tsfs-toggle-desc">Dynamically show or hide based on rules</span>';
                html += '  </div>';
                html += '  <label class="tsfs-toggle-switch">';
                html += '    <input type="checkbox" id="insp-enable-conditions" ' + (isCondEnabled ? 'checked' : '') + '>';
                html += '    <span class="tsfs-toggle-slider"></span>';
                html += '  </label>';
                html += '</div>';

                html += '<div id="tsfs-conditional-rules-wrap" style="' + (isCondEnabled ? '' : 'display:none;') + ' background:var(--tsfs-adm-slate-50); border:1px solid var(--tsfs-adm-slate-200); border-radius:8px; padding:12px; margin-top:8px;">';
                html += '  <div style="display:flex; gap:6px; align-items:center; margin-bottom:12px; font-size:12px; color:var(--tsfs-adm-slate-700);">';
                html += '    <span>Action:</span>';
                html += '    <select id="insp-condition-action" style="font-size:11.5px; height:28px; padding:2px 6px; border-radius:4px;">';
                html += '      <option value="show" ' + (action === 'show' ? 'selected' : '') + '>Show this field</option>';
                html += '      <option value="hide" ' + (action === 'hide' ? 'selected' : '') + '>Hide this field</option>';
                html += '    </select>';
                html += '    <span>if</span>';
                html += '    <select id="insp-condition-match" style="font-size:11.5px; height:28px; padding:2px 6px; border-radius:4px;">';
                html += '      <option value="all" ' + (match === 'all' ? 'selected' : '') + '>ALL rules match</option>';
                html += '      <option value="any" ' + (match === 'any' ? 'selected' : '') + '>ANY rule matches</option>';
                html += '    </select>';
                html += '  </div>';

                html += '  <div id="tsfs-condition-rules-list" style="display:flex; flex-direction:column; gap:8px;">';
                // Render rule rows
                var conditions = Array.isArray(field.conditions) ? field.conditions : [];
                var otherFields = this.fields.filter(function(f) { return f.id !== field.id && f.type !== 'html'; });

                if (conditions.length > 0) {
                    var self = this;
                    conditions.forEach(function(cond) {
                        html += self.buildConditionRowHtml(otherFields, cond);
                    });
                }
                html += '  </div>';

                html += '  <button type="button" class="button button-small" id="tsfs-add-rule-btn" style="margin-top:10px; font-weight:600;">+ Add Rule</button>';
                html += '</div>';
            }

            html += '</div>';
            $container.html(html);
        },

        buildConditionRowHtml: function(otherFields, cond) {
            cond = cond || { field: '', operator: '==', value: '' };

            var html = '<div class="tsfs-cond-rule-row" style="display:flex; gap:6px; align-items:center; background:#ffffff; border:1px solid #cbd5e1; border-radius:6px; padding:6px 8px;">';

            // Target Field select
            html += '<select class="insp-cond-field" style="flex:1.2; font-size:11.5px; height:28px; padding:2px 4px; border-radius:4px;">';
            html += '<option value="">— Select Field —</option>';
            otherFields.forEach(function(f) {
                var sel = (cond.field === f.name) ? 'selected' : '';
                html += '<option value="' + escapeMarkup(f.name) + '" ' + sel + '>' + escapeMarkup(f.label || f.name) + '</option>';
            });
            html += '</select>';

            // Operator select
            html += '<select class="insp-cond-op" style="flex:1; font-size:11.5px; height:28px; padding:2px 4px; border-radius:4px;">';
            html += '<option value="==" ' + (cond.operator === '==' ? 'selected' : '') + '>is equal to</option>';
            html += '<option value="!=" ' + (cond.operator === '!=' ? 'selected' : '') + '>is not equal</option>';
            html += '<option value="contains" ' + (cond.operator === 'contains' ? 'selected' : '') + '>contains</option>';
            html += '<option value="not_empty" ' + (cond.operator === 'not_empty' ? 'selected' : '') + '>is filled</option>';
            html += '<option value="empty" ' + (cond.operator === 'empty' ? 'selected' : '') + '>is empty</option>';
            html += '</select>';

            // Value input
            var hideVal = (cond.operator === 'not_empty' || cond.operator === 'empty');
            html += '<input type="text" class="insp-cond-val" value="' + escapeMarkup(cond.value || '') + '" placeholder="Value" style="flex:1; font-size:11.5px; height:28px; padding:2px 6px; border-radius:4px;' + (hideVal ? ' display:none;' : '') + '">';

            // Remove button
            html += '<button type="button" class="tsfs-remove-rule-btn" style="background:transparent; border:none; color:#ef4444; font-size:16px; cursor:pointer; line-height:1; padding:2px;" title="Remove Rule">&times;</button>';
            html += '</div>';

            return html;
        },

        addConditionRuleToInspector: function() {
            var field = this.fields.find(function(f) { return f.id === TSFSBuilder.selectedFieldId; });
            if (!field) return;

            var otherFields = this.fields.filter(function(f) { return f.id !== field.id && f.type !== 'html'; });
            var defaultField = (otherFields.length > 0) ? otherFields[0].name : '';
            var rowHtml = this.buildConditionRowHtml(otherFields, { field: defaultField, operator: '==', value: '' });

            $('#tsfs-condition-rules-list').append(rowHtml);
        },

        addOptionToSelectedField: function() {
            var $list = $('#insp-options-list');
            var nextNum = $list.find('.tsfs-option-row').length + 1;
            var rowHtml = '<div class="tsfs-option-row">' +
                '<input type="text" class="insp-option-val" value="Option ' + nextNum + '">' +
                '<button type="button" class="tsfs-remove-option-btn" title="Remove Option">&times;</button>' +
                '</div>';
            $list.append(rowHtml);
            this.updateFieldFromInspector();
        },

        updateFieldFromInspector: function() {
            var field = this.fields.find(function(f) { return f.id === TSFSBuilder.selectedFieldId; });
            if (!field) return;

            field.label = $('#insp-label').val();
            field.name = ($('#insp-name').val() || '').toLowerCase().replace(/[^a-z0-9_]/g, '_');
            field.placeholder = $('#insp-placeholder').val() || '';
            field.help_text = $('#insp-help').val() || '';
            field.required = $('#insp-required').is(':checked');

            if (field.type === 'textarea') {
                field.rows = parseInt($('#insp-rows').val(), 10) || 4;
            }

            if (field.type === 'number') {
                field.min = $('#insp-min').val();
                field.max = $('#insp-max').val();
            }

            if (field.type === 'range') {
                field.min = $('#insp-range-min').val() || 1;
                field.max = $('#insp-range-max').val() || 10;
                field.default_val = $('#insp-range-default').val() || 5;
            }

            if (field.type === 'rating') {
                field.max_stars = parseInt($('#insp-rating-stars').val(), 10) || 5;
            }

            if (field.type === 'consent') {
                field.consent_text = $('#insp-consent-text').val() || 'I have read and agree to the Terms of Service and Privacy Policy.';
                field.terms_url = $('#insp-terms-url').val() || '';
                field.terms_label = $('#insp-terms-label').val() || 'Terms & Conditions';
            }

            if (field.type === 'file') {
                field.allowed_ext = $('#insp-allowed-ext').val() || 'pdf,doc,docx,jpg,jpeg,png';
                field.max_size = $('#insp-max-size').val() || 5;
            }

            if (field.type === 'hidden') {
                field.default_val = $('#insp-hidden-val').val() || '';
            }

            if (field.type === 'html') {
                field.content_mode = $('#insp-content-mode').val();
                field.tag = $('#insp-tag').val();
                field.content = $('#insp-content').val();
            }

            if (field.type === 'page_break') {
                field.step_title = $('#insp-step-title').val() || '';
                field.btn_prev_text = $('#insp-step-prev').val() || 'Previous';
                field.btn_next_text = $('#insp-step-next').val() || 'Next Step';
            }

            if (fieldExtensions[field.type] && typeof fieldExtensions[field.type].readInspector === 'function') {
                fieldExtensions[field.type].readInspector(field);
            }

            if (field.type === 'select' || field.type === 'radio' || field.type === 'checkbox') {
                var opts = [];
                $('.insp-option-val').each(function() {
                    var val = $(this).val().trim();
                    if (val) opts.push(val);
                });
                field.options = opts;
            }

            // Conditional Logic State
            if (field.type !== 'hidden') {
                field.enable_conditions = $('#insp-enable-conditions').is(':checked');
                field.condition_action = $('#insp-condition-action').val() || 'show';
                field.condition_match = $('#insp-condition-match').val() || 'all';

                var condRules = [];
                $('#tsfs-condition-rules-list .tsfs-cond-rule-row').each(function() {
                    var fld = $(this).find('.insp-cond-field').val();
                    var op = $(this).find('.insp-cond-op').val();
                    var val = $(this).find('.insp-cond-val').val();
                    if (fld) {
                        condRules.push({ field: fld, operator: op, value: val });
                    }
                });
                field.conditions = condRules;
            }

            // Update card in canvas smoothly without re-rendering everything
            var $card = $('.tsfs-canvas-field-card[data-id="' + field.id + '"]');
            $card.find('.tsfs-card-label').html(escapeMarkup(field.label || 'Untitled Field') + (field.required ? '<span class="required-star">*</span>' : ''));
            $card.find('.tsfs-card-mock-input').attr('placeholder', field.placeholder);
            if (field.type === 'country') {
                $card.find('select option').text(field.placeholder || 'Select a country');
            }

            if (field.type === 'consent') {
                $card.find('.tsfs-mock-consent-wrap span').first().text(field.consent_text);
                if (field.terms_url) {
                    $card.find('.tsfs-mock-consent-wrap a').attr('href', safeTermsUrl(field.terms_url)).text(field.terms_label);
                }
            }

            // Update conditional badge on canvas card
            var $handle = $card.find('.tsfs-card-handle');
            $handle.find('.tsfs-card-cond-badge').remove();
            if (field.enable_conditions && Array.isArray(field.conditions) && field.conditions.length > 0) {
                $handle.append('<span class="tsfs-card-cond-badge" title="Has conditional logic rules" style="background:#eff6ff; color:#2563eb; font-size:10.5px; padding:2px 7px; border-radius:10px; font-weight:700; border:1px solid #bfdbfe; margin-left:6px; display:inline-flex; align-items:center; gap:3px;"><span class="dashicons dashicons-randomize" style="font-size:12px; width:12px; height:12px; vertical-align:middle;"></span> If/Then</span>');
            }

            this.renderOutline();

            this.scheduleHistorySnapshot('Edit ' + (field.label || 'Field'));
        },

        applyThemeLive: function() {
            var preset = $('#tsfs-theme-preset-val').val() || 'default';
            var primaryColor = $('#tsfs-theme-primary-color').val() || '#4f46e5';
            var btnColor = $('#tsfs-theme-btn-color').val() || '#ffffff';
            var bgColor = $('#tsfs-theme-bg-color').val() || '#ffffff';
            var radius = $('#tsfs-theme-radius-val').val() || 'rounded';
            var align = $('#tsfs-theme-button-align-val').val() || 'left';
            var shadow = $('#tsfs-theme-shadow-val').val() || 'medium';

            var $canvas = $('#tsfs-canvas-container');
            if (!$canvas.length) return;

            var sizes = this.collectSettings();
            $canvas.css({ 'max-width': sizes.form_max_width + 'px', width:'100%', 'min-height':sizes.form_min_height + 'px', padding:sizes.form_padding + 'px' });
            $('#tsfs-canvas-submit-preview').css({width:align === 'full' ? '100%' : (sizes.button_width ? sizes.button_width + 'px' : 'auto'), 'max-width':'100%', 'min-height':sizes.button_height + 'px', 'padding-left':sizes.button_padding + 'px', 'padding-right':sizes.button_padding + 'px'});

            // 1. Remove theme preset classes and add the current one
            $canvas.removeClass('tsfs-theme-minimal tsfs-theme-dark tsfs-theme-emerald tsfs-theme-sunset tsfs-theme-violet');
            if (preset !== 'default') {
                $canvas.addClass('tsfs-theme-' + preset);
            }

            // 2. Primary / accent color & button color
            $canvas.get(0).style.setProperty('--tsfs-primary', primaryColor);
            $canvas.get(0).style.setProperty('--tsfs-btn-color', btnColor);

            var $submitBtn = $('#tsfs-canvas-submit-preview');
            $submitBtn.css({
                'background-color': primaryColor,
                'color': btnColor
            });

            // Background
            if (preset === 'dark') {
                $canvas.css('background-color', '#0f172a');
            } else {
                $canvas.css('background-color', bgColor);
            }

            // 3. Radius
            $canvas.removeClass('tsfs-radius-sharp tsfs-radius-slight tsfs-radius-rounded tsfs-radius-pill');
            $canvas.addClass('tsfs-radius-' + radius);

            var canvasRadiusMap = {
                'sharp': '0px',
                'slight': '6px',
                'rounded': '12px',
                'pill': '24px'
            };
            var btnRadiusMap = {
                'sharp': '0px',
                'slight': '4px',
                'rounded': '8px',
                'pill': '30px'
            };
            $canvas.css('border-radius', canvasRadiusMap[radius] || '12px');
            $submitBtn.css('border-radius', btnRadiusMap[radius] || '8px');
            $('.tsfs-canvas-field-card').css('border-radius', radius === 'sharp' ? '0px' : (radius === 'pill' ? '12px' : (radius === 'slight' ? '4px' : '8px')));

            // 4. Button alignment
            $canvas.removeClass('tsfs-btn-align-left tsfs-btn-align-center tsfs-btn-align-right tsfs-btn-align-full');
            $canvas.addClass('tsfs-btn-align-' + align);

            $submitBtn.css({'background':sizes.button_bg, 'font-size':sizes.button_font_size+'px','border-radius':sizes.button_radius+'px','padding-top':sizes.button_vertical_padding+'px','padding-bottom':sizes.button_vertical_padding+'px','text-decoration':'none'});
            $canvas.css('font-size',sizes.text_size+'px');
            $canvas.find('.tsfs-card-label').css('font-size',sizes.label_size+'px');
            $canvas.find('.tsfs-mock-consent-wrap').css({background:sizes.consent_bg,border:'1px solid '+sizes.consent_border,padding:sizes.consent_padding+'px'}).find('div, span').css({color:sizes.consent_color,'font-size':sizes.consent_size+'px'});

            // 5. Box shadow
            $canvas.removeClass('tsfs-shadow-none tsfs-shadow-subtle tsfs-shadow-medium tsfs-shadow-elevated tsfs-shadow-outline');
            $canvas.addClass('tsfs-shadow-' + shadow);

        },

        collectSettings: function() {
            return {
                submit_button_text: $('#tsfs-setting-btn-text').val() || 'Submit',
                success_message: $('#tsfs-setting-success-msg').val() || 'Thank you! Your submission has been received.',
                redirect_url: $('#tsfs-setting-redirect-url').val() || '',
                redirect_pass_vars: $('#tsfs-setting-redirect-pass-vars').is(':checked'),
                send_email: $('#tsfs-setting-send-email').is(':checked'),
                notification_email: $('#tsfs-setting-admin-email').val() || '',
                email_subject: $('#tsfs-setting-email-subject').val() || '',
                send_user_auto_reply: $('#tsfs-setting-user-autoreply').is(':checked'),
                auto_reply_subject: $('#tsfs-setting-autoreply-subj').val() || '',
                auto_reply_message: $('#tsfs-setting-autoreply-msg').val() || '',

                form_max_width: layoutNumber('form_max_width',680,280,1600),
                form_min_height: layoutNumber('form_min_height',0,0,1600),
                form_padding: layoutNumber('form_padding',32,0,80),
                field_gap: layoutNumber('field_gap',18,0,64),
                input_height: layoutNumber('input_height',44,32,100),
                button_width: layoutNumber('button_width',0,0,1000),
                button_height: layoutNumber('button_height',46,32,120),
                button_padding: layoutNumber('button_padding',30,0,80),

                text_size: layoutNumber('text_size',16,12,24),
                label_size: layoutNumber('label_size',15,12,26),
                button_font_size: layoutNumber('button_font_size',16,12,30),
                button_radius: layoutNumber('button_radius',8,0,60),
                button_vertical_padding: layoutNumber('button_vertical_padding',12,4,40),
                consent_size: layoutNumber('consent_size',14,12,24),
                consent_padding: layoutNumber('consent_padding',14,0,40),
                checkbox_size: layoutNumber('checkbox_size',20,16,32),
                button_bg: $('#tsfs-color-button_bg').val() || '#4f46e5',
                button_hover: $('#tsfs-color-button_hover').val() || '#4338ca',
                consent_bg: $('#tsfs-color-consent_bg').val() || '#f8fafc',
                consent_border: $('#tsfs-color-consent_border').val() || '#e2e8f0',
                consent_color: $('#tsfs-color-consent_color').val() || '#334155',
                // Theme & Styling Options
                theme_preset: $('#tsfs-theme-preset-val').val() || 'default',
                primary_color: $('#tsfs-theme-primary-color').val() || '#4f46e5',
                btn_text_color: $('#tsfs-theme-btn-color').val() || '#ffffff',
                container_bg: $('#tsfs-theme-bg-color').val() || '#ffffff',
                border_radius: $('#tsfs-theme-radius-val').val() || 'rounded',
                button_align: $('#tsfs-theme-button-align-val').val() || 'left',
                shadow_style: $('#tsfs-theme-shadow-val').val() || 'medium',

                // Scheduling & Restrictions
                limit_submissions: $('#tsfs-setting-limit-submissions').is(':checked'),
                max_entries: parseInt($('#tsfs-setting-max-entries').val(), 10) || 0,
                close_after_date: $('#tsfs-setting-close-date-toggle').is(':checked'),
                close_date: $('#tsfs-setting-close-date').val() || '',
                closed_message: $('#tsfs-setting-closed-msg').val() || '',
                require_login: $('#tsfs-setting-require-login').is(':checked'),
                login_message: $('#tsfs-setting-login-msg').val() || '',

                // Webhook & API Integrations
                enable_webhook: $('#tsfs-setting-enable-webhook').is(':checked'),
                webhook_url: ($('#tsfs-setting-webhook-url').val() || '').trim(),
                webhook_secret: ($('#tsfs-setting-webhook-secret').val() || '').trim()
            };
        },

        getCurrentState: function() {
            return JSON.stringify({
                title: $('#tsfs-form-title-input').val().trim() || 'Untitled Form',
                fields: this.fields,
                settings: this.collectSettings()
            });
        },

        hasUnsavedChanges: function() {
            return this.savedState !== this.getCurrentState();
        },

        updateSaveStatus: function(state) {
            if (this.savedState === null) return;
            state = state || (this.isSaving ? 'saving' :
                (this.hasUnsavedChanges() ? 'unsaved' : 'saved'));
            var labels = (window.tsfsBuilderData || {}).i18n || {};
            var fallback = { saving: 'Saving…', unsaved: 'Unsaved changes',
                saved: 'All changes saved', error: 'Save failed — try again' };
            $('#tsfs-save-status').attr('data-state', state)
                .text(labels['status_' + state] || fallback[state]);
        },

        exportFormJson: function() {
            var formTitle = $('#tsfs-form-title-input').val().trim() || 'Untitled Form';
            var formId = (window.tsfsBuilderData && window.tsfsBuilderData.form) ? window.tsfsBuilderData.form.id : 0;

            var settings = this.collectSettings();

            var exportPayload = {
                tsfs_version: '2.0.0',
                exported_at: new Date().toISOString(),
                generator: 'TechSolution Form Studio',
                title: formTitle,
                fields_data: this.fields,
                settings: settings
            };

            var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(exportPayload, null, 2));
            var downloadAnchor = document.createElement('a');
            downloadAnchor.setAttribute("href", dataStr);
            downloadAnchor.setAttribute("download", "tsfs-form-" + (formId ? formId : 'draft') + ".json");
            document.body.appendChild(downloadAnchor);
            downloadAnchor.click();
            downloadAnchor.remove();

            this.showToast('Form definition exported to JSON!', 'success');
        },

        saveForm: function() {
            if (this.isSaving) return;
            var self = this;
            var $btn = $('#tsfs-save-form-btn');
            var $spinner = $btn.find('.spinner');

            var formTitle = $('#tsfs-form-title-input').val().trim() || 'Untitled Form';
            var formId = (window.tsfsBuilderData && window.tsfsBuilderData.form) ? window.tsfsBuilderData.form.id : 0;

            var settings = this.collectSettings();

            var submittedState = this.getCurrentState();
            this.isSaving = true;
            this.updateSaveStatus();
            $btn.prop('disabled', true);
            $spinner.addClass('is-active');

            $.post(window.tsfsBuilderData.ajax_url, {
                action: 'tsfs_save_form',
                nonce: window.tsfsBuilderData.nonce,
                form_id: formId,
                title: formTitle,
                fields_data: JSON.stringify(self.fields),
                settings: JSON.stringify(settings)
            }, function(res) {
                self.isSaving = false;
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');

                if (res.success) {
                    self.savedState = submittedState;
                    self.updateSaveStatus();
                    self.showToast('Form saved successfully!', 'success');
                    if (res.data.form_id) {
                        if (!window.tsfsBuilderData.form) window.tsfsBuilderData.form = {};
                        window.tsfsBuilderData.form.id = res.data.form_id;
                        var newShortcode = '[tsfs_form id="' + res.data.form_id + '"]';
                        $('.tsfs-shortcode-tag').show().attr('data-shortcode', newShortcode).find('code').text(newShortcode);
                        $('#tsfs-embed-shortcode-input').val(newShortcode);
                    }
                    if (!formId && res.data.redirect_url) {
                        window.history.replaceState({}, '', res.data.redirect_url);
                        if (!window.tsfsBuilderData.form) window.tsfsBuilderData.form = {};
                        window.tsfsBuilderData.form.id = res.data.form_id;
                    }
                } else {
                    self.updateSaveStatus('error');
                    TSFSModal.alert({
                        title: 'Save Failed',
                        message: res.data.message || 'Error saving form.',
                        type: 'error'
                    });
                }
            }).fail(function() {
                self.isSaving = false;
                self.updateSaveStatus('error');
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
                TSFSModal.alert({
                    title: 'Network Error',
                    message: 'Network error occurred while saving form. Please check your connection.',
                    type: 'error'
                });
            });
        },

        openLivePreview: function() {
            var self = this;
            var formTitle = $('#tsfs-form-title-input').val().trim() || 'Custom Form';
            var submitBtnText = $('#tsfs-setting-btn-text').val() || 'Submit';
            var primaryColor = $('#tsfs-theme-primary-color').val() || '#4f46e5';
            var btnColor = $('#tsfs-theme-btn-color').val() || '#ffffff';
            var radius = $('#tsfs-theme-radius-val').val() || 'rounded';
            var align = $('#tsfs-theme-button-align-val').val() || 'left';

            var radiusMap = {
                'sharp': '0px',
                'slight': '6px',
                'rounded': '10px',
                'pill': '24px'
            };
            var btnRadius = radiusMap[radius] || '8px';

            var html = '';
            var sizes = this.collectSettings();
            var previewLabels = (window.tsfsBuilderData || {}).i18n || {};
            html += '<div class="tsfs-preview-notice"><div><span class="tsfs-preview-notice-title">' + escapeMarkup(previewLabels.preview_title || 'Form preview') + '</span>';
            html += '<p>' + escapeMarkup(previewLabels.preview_description || 'Test your form before publishing. Preview submissions are not saved.') + '</p></div>';
            html += '<span class="tsfs-preview-field-count">' + this.fields.length + ' ' + escapeMarkup(previewLabels.preview_fields || 'fields') + '</span></div>';

            // Form container
            html += '<div class="tsfs-preview-form-box" style="--tsfs-primary:' + primaryColor + '; font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;">';
            html += '  <h2 style="font-size:22px; font-weight:800; color:#0f172a; margin:0 0 28px 0; line-height:1.3;">' + escapeMarkup(formTitle) + '</h2>';

            html += '  <form id="tsfs-live-simulation-form">';
            html += '    <div style="display:flex; flex-wrap:wrap; gap:' + sizes.field_gap + 'px;">';

            var registeredPreviewTypes = (window.tsfsBuilderData || {}).field_types || {};
            this.fields.forEach(function(f) {
                var widthPct = f.width === '50' ? 'calc(50% - ' + (sizes.field_gap / 2) + 'px)' : (f.width === '33' ? 'calc(33.333% - ' + (sizes.field_gap * 2 / 3) + 'px)' : '100%');

                if (f.type === 'html') {
                    html += '<div style="width:100%">' + contentPreview(f) + '</div>';
                    return;
                }

                if (f.type === 'hidden') {
                    return;
                }

                html += '<div class="tsfs-preview-field-col" style="width:' + widthPct + '; box-sizing:border-box; display:flex; flex-direction:column; gap:6px;">';
                html += '  <label style="font-size:13.5px; font-weight:600; color:#1e293b; display:flex; align-items:center; gap:4px;">';
                html += '    <span>' + escapeMarkup(f.label || 'Untitled') + '</span>';
                if (f.required) {
                    html += '    <span style="color:#ef4444; font-weight:700;">*</span>';
                }
                html += '  </label>';

                var inputStyle = 'width:100%; box-sizing:border-box; padding:9px 12px; font-size:14px; border:1.5px solid #cbd5e1; border-radius:' + btnRadius + '; background:#ffffff; color:#0f172a; outline:none; transition:border 0.15s ease;';

                if (fieldExtensions[f.type] && typeof fieldExtensions[f.type].preview === 'function') {
                    html += fieldExtensions[f.type].preview(f, { escape: escapeMarkup, inputStyle: inputStyle, radius: btnRadius, primaryColor: primaryColor });
                } else if (!registeredPreviewTypes[f.type]) {
                    html += '';
                } else if (f.type === 'textarea') {
                    html += '  <textarea rows="' + (f.rows || 4) + '" placeholder="' + escapeMarkup(f.placeholder || '') + '" style="' + inputStyle + '"></textarea>';
                } else if (f.type === 'country') {
                    var countryLabels = (window.tsfsBuilderData || {}).i18n || {};
                    var simId = 'tsfs_sim_country_' + Math.random().toString(36).slice(2);
                    html += '<div class="tsfs-country-control">';
                    html += '<select id="' + simId + '" aria-label="' + self.escapeAttribute(f.label || 'Country') + '" class="tsfs-country-select" style="' + inputStyle + '"' + (f.required ? ' required' : '') + '>';
                    html += '<option value="">' + escapeMarkup(f.placeholder || 'Select a country') + '</option>';
                    Object.values((window.tsfsBuilderData || {}).countries || {}).forEach(function(country) {
                        html += '<option value="' + self.escapeAttribute(country) + '">' + escapeMarkup(country) + '</option>';
                    });
                    html += '</select></div>';
                } else if (f.type === 'select') {
                    html += '  <select style="' + inputStyle + '">';
                    html += '    <option value="">' + (f.placeholder ? escapeMarkup(f.placeholder) : 'Select an option...') + '</option>';
                    var opts = f.options || ['Option 1', 'Option 2'];
                    opts.forEach(function(o) {
                        html += '    <option value="' + escapeMarkup(o) + '">' + escapeMarkup(o) + '</option>';
                    });
                    html += '  </select>';
                } else if (f.type === 'radio') {
                    html += '  <div style="display:flex; flex-direction:column; gap:8px; padding-top:4px;">';
                    var opts = f.options || ['Option 1', 'Option 2'];
                    opts.forEach(function(o) {
                        html += '    <label style="display:flex; align-items:center; gap:8px; font-size:13.5px; color:#334155; cursor:pointer;">';
                        html += '      <input type="radio" name="sim_' + f.name + '" value="' + escapeMarkup(o) + '" style="accent-color:' + primaryColor + ';">';
                        html += '      <span>' + escapeMarkup(o) + '</span>';
                        html += '    </label>';
                    });
                    html += '  </div>';
                } else if (f.type === 'checkbox') {
                    html += '  <div style="display:flex; flex-direction:column; gap:8px; padding-top:4px;">';
                    var opts = f.options || ['Option 1', 'Option 2'];
                    opts.forEach(function(o) {
                        html += '    <label style="display:flex; align-items:center; gap:8px; font-size:13.5px; color:#334155; cursor:pointer;">';
                        html += '      <input type="checkbox" name="sim_' + f.name + '[]" value="' + escapeMarkup(o) + '" style="accent-color:' + primaryColor + ';">';
                        html += '      <span>' + escapeMarkup(o) + '</span>';
                        html += '    </label>';
                    });
                    html += '  </div>';
                } else if (f.type === 'rating') {
                    var maxS = parseInt(f.max_stars, 10) || 5;
                    html += '  <div class="tsfs-sim-rating" data-max="' + maxS + '" style="display:flex; align-items:center; gap:6px; cursor:pointer; padding:4px 0;">';
                    for (var s = 1; s <= maxS; s++) {
                        html += '    <span class="dashicons dashicons-star-filled tsfs-sim-star" data-val="' + s + '" style="font-size:26px; width:26px; height:26px; color:#d1d5db; transition:color 0.15s ease;"></span>';
                    }
                    html += '    <span class="tsfs-sim-rating-score" style="margin-left:8px; font-size:13px; font-weight:700; color:#475569;"></span>';
                    html += '  </div>';
                } else if (f.type === 'consent') {
                    html += '  <div class="tsfs-preview-consent" style="display:flex; align-items:flex-start; gap:10px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:' + btnRadius + '; padding:12px;">';
                    html += '    <input type="checkbox" style="margin-top:3px; accent-color:' + primaryColor + ';" ' + (f.required ? 'required' : '') + '>';
                    html += '    <div style="font-size:13px; color:#334155; line-height:1.45;">';
                    html += '      <span>' + escapeMarkup(f.consent_text || 'I agree to the Terms of Service and Privacy Policy.') + '</span> ';
                    if (f.terms_url) {
                        html += '      <a href="' + escapeMarkup(safeTermsUrl(f.terms_url)) + '" target="_blank" rel="noopener noreferrer" style="color:' + primaryColor + '; text-decoration:underline; font-weight:600;">' + escapeMarkup(f.terms_label || 'Terms & Conditions') + '</a>';
                    }
                    html += '    </div>';
                    html += '  </div>';
                } else if (f.type === 'range') {
                    var rMin = f.min || 1;
                    var rMax = f.max || 10;
                    var rDef = f.default_val || 5;
                    html += '  <div style="display:flex; flex-direction:column; gap:6px;">';
                    html += '    <div style="display:flex; justify-content:space-between; font-size:12px; color:#64748b;">';
                    html += '      <span>Min: ' + rMin + '</span><span class="tsfs-sim-range-val" style="font-weight:700; color:' + primaryColor + ';">' + rDef + '</span><span>Max: ' + rMax + '</span>';
                    html += '    </div>';
                    html += '    <input type="range" min="' + rMin + '" max="' + rMax + '" value="' + rDef + '" style="accent-color:' + primaryColor + ';" class="tsfs-sim-range-input">';
                    html += '  </div>';
                } else if (f.type === 'file') {
                    html += '  <div style="border:2px dashed #cbd5e1; border-radius:' + btnRadius + '; padding:20px; text-align:center; background:#f8fafc; cursor:pointer;">';
                    html += '    <span class="dashicons dashicons-upload" style="font-size:28px; width:28px; height:28px; color:#64748b; margin-bottom:6px;"></span>';
                    html += '    <div style="font-size:13.5px; font-weight:600; color:#334155;">Click or drop file to upload</div>';
                    html += '    <div style="font-size:11.5px; color:#64748b; margin-top:2px;">Allowed: ' + (f.allowed_ext || 'PDF, DOC, PNG') + ' (Max ' + (f.max_size || 5) + 'MB)</div>';
                    html += '  </div>';
                } else {
                    var fType = (f.type === 'email' || f.type === 'number' || f.type === 'tel' || f.type === 'date' || f.type === 'time' || f.type === 'url') ? f.type : 'text';
                    html += '  <input type="' + fType + '" placeholder="' + escapeMarkup(f.placeholder || '') + '" style="' + inputStyle + '">';
                }

                if (f.help_text) {
                    html += '  <span style="font-size:12px; color:#64748b;">' + escapeMarkup(f.help_text) + '</span>';
                }
                html += '</div>';
            });

            html += '    </div>';

            // Submit button row
            var alignCss = align === 'center' ? 'text-align:center;' : (align === 'right' ? 'text-align:right;' : (align === 'full' ? 'display:block;' : 'text-align:left;'));
            html += '    <div style="margin-top:28px; ' + alignCss + '">';
            html += '      <button type="submit" style="background-color:' + primaryColor + '; color:' + btnColor + '; border:none; border-radius:' + btnRadius + '; padding:11px 26px; font-size:14.5px; font-weight:700; cursor:pointer; ' + (align === 'full' ? 'width:100%;' : '') + ' box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);">';
            html += escapeMarkup(submitBtnText);
            html += '      </button>';
            html += '    </div>';

            html += '  </form>';
            html += '</div>';

            $('#tsfs-live-preview-viewport').html(html);
            var $preview = $('#tsfs-live-preview-viewport');
            $preview.find('.tsfs-preview-form-box').css({width:'100%', 'max-width':sizes.form_max_width + 'px', 'min-height':sizes.form_min_height + 'px', padding:sizes.form_padding + 'px', margin:'0 auto', 'box-sizing':'border-box'});
            $preview.find('input:not([type=checkbox]):not([type=radio]):not([type=hidden]), select').css('min-height',sizes.input_height + 'px');
            $preview.find('button[type=submit]').css({width:align === 'full' ? '100%' : (sizes.button_width ? sizes.button_width + 'px' : 'auto'), 'max-width':'100%', 'min-height':sizes.button_height + 'px', 'padding-left':sizes.button_padding + 'px', 'padding-right':sizes.button_padding + 'px', 'white-space':'normal'});
            $preview.find('button[type=submit]').css({background:sizes.button_bg,'font-size':sizes.button_font_size+'px','border-radius':sizes.button_radius+'px','padding-top':sizes.button_vertical_padding+'px','padding-bottom':sizes.button_vertical_padding+'px','text-decoration':'none'});
            $preview.find('.tsfs-preview-form-box').css('font-size',sizes.text_size+'px');
            $preview.find('.tsfs-preview-consent').css({background:sizes.consent_bg,border:'1px solid '+sizes.consent_border,padding:sizes.consent_padding+'px',color:sizes.consent_color,'font-size':sizes.consent_size+'px'});
            $preview.find('.tsfs-preview-consent div, .tsfs-preview-consent span').css({'font-size':sizes.consent_size+'px',color:sizes.consent_color,'line-height':'1.6','white-space':'pre-line'});
            $preview.find('.tsfs-preview-consent input').css({width:sizes.checkbox_size+'px',height:sizes.checkbox_size+'px','flex-shrink':0});
            if (window.TSFSCountry) window.TSFSCountry.init(document.getElementById('tsfs-live-preview-viewport'));
            $(document).trigger('tsfs:preview-rendered', [document.getElementById('tsfs-live-preview-viewport')]);

            // Bind interactive star rating events inside simulator
            $('#tsfs-live-preview-viewport').find('.tsfs-sim-rating').each(function() {
                var $wrap = $(this);
                $wrap.find('.tsfs-sim-star').on('mouseenter', function() {
                    var val = $(this).data('val');
                    $wrap.find('.tsfs-sim-star').each(function() {
                        $(this).css('color', $(this).data('val') <= val ? '#f59e0b' : '#d1d5db');
                    });
                });
                $wrap.on('mouseleave', function() {
                    var current = $wrap.data('selected') || 0;
                    $wrap.find('.tsfs-sim-star').each(function() {
                        $(this).css('color', $(this).data('val') <= current ? '#f59e0b' : '#d1d5db');
                    });
                });
                $wrap.find('.tsfs-sim-star').on('click', function() {
                    var val = $(this).data('val');
                    $wrap.data('selected', val);
                    $wrap.find('.tsfs-sim-rating-score').text(val + ' / ' + $wrap.data('max') + ' Stars');
                    $wrap.find('.tsfs-sim-star').each(function() {
                        $(this).css('color', $(this).data('val') <= val ? '#f59e0b' : '#d1d5db');
                    });
                });
            });

            $('#tsfs-live-preview-modal').fadeIn(150);
        },

        showToast: function(message, type) {
            if (window.TSFSModal && TSFSModal.toast) {
                TSFSModal.toast(message, type);
                return;
            }
            var $box = $('#tsfs-toast-box');
            $box.removeClass('success error').addClass(type || 'success').text(message).fadeIn(200);
            setTimeout(function() {
                $box.fadeOut(300);
            }, 3000);
        }
    };

    /**
     * Public builder API for add-ons.
     * TSFSBuilder.registerFieldType('signature', { label, defaults, canvas, inspector, readInspector, preview, hidePlaceholder });
     * TSFSBuilder.templates.my_template = { title, settings, fields };
     */
    TSFSBuilder.registerFieldType = function(type, definition) {
        fieldExtensions[type] = definition || {};
    };
    TSFSBuilder.escapeMarkup = escapeMarkup;
    window.TSFSBuilder = TSFSBuilder;

    $(document).ready(function() {
        // Live preview helpers (no inline event handlers)
        $(document).on('input', '#tsfs-live-preview-viewport .tsfs-sim-range-input', function() {
            $(this).prev().find('.tsfs-sim-range-val').text($(this).val());
        });
        $(document).on('submit', '#tsfs-live-simulation-form', function(e) {
            e.preventDefault();
            TSFSModal.toast('Form preview check passed. Preview submissions are not saved.', 'success');
        });
        TSFSBuilder.init();
    });

})(jQuery);
