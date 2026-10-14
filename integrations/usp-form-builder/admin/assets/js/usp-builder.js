/**
 * Visual Drag-and-Drop Form Builder Engine.
 * Supports Advanced Field Types, Real-time Conditional Logic, and 1-Click Templates.
 *
 * @package USP_Form_Builder
 */

(function($) {
    'use strict';

    function contentPreview(field) {
        var escape = function(value) { return $('<div>').text(value || '').html(); };
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

    var USPBuilder = {
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
            },
            admission_pro: {
                title: 'University Admission Application',
                settings: {
                    submit_button_text: 'Submit Application & Pay Fee',
                    success_message: 'Thank you for applying! Your admission package and fee authorization have been processed successfully.',
                    send_email: true,
                    email_subject: 'New University Admission Application: {user_name} [#{entry_id}]'
                },
                fields: [
                    { id: 'tpl_a0', type: 'html', label: 'Academic Admissions', tag: 'h3', content: 'Official Student Enrollment & Degree Program Application', width: '100' },
                    { id: 'tpl_a1', type: 'admission', label: 'Applicant Student Profile', name: 'student_admission_record', required: true, width: '100' },
                    { id: 'tpl_a2', type: 'intl_tel', label: 'Emergency Contact Mobile', name: 'emergency_phone', required: true, width: '50' },
                    { id: 'tpl_a3', type: 'file', label: 'High School / College Transcripts', name: 'academic_transcripts', allowed_ext: 'pdf,doc,docx', max_size: '10', help_text: 'Attach certified PDF transcript (Max 10MB)', required: true, width: '50' },
                    { id: 'tpl_a4', type: 'payment', label: 'Admissions Processing Fee', name: 'application_fee', payment_amount: '75.00', payment_currency: 'USD', required: true, width: '100' }
                ]
            },
            checkout_payment: {
                title: 'Event Ticket & Fee Checkout',
                settings: {
                    submit_button_text: 'Complete Purchase & Register',
                    success_message: 'Thank you! Your ticket reservation and payment authorization have been confirmed.',
                    send_email: true,
                    email_subject: 'Ticket Order Confirmation: {user_name} [#{entry_id}]'
                },
                fields: [
                    { id: 'tpl_p0', type: 'html', label: 'Event Registration', tag: 'h3', content: 'Reserve your conference passes and complete checkout below.', width: '100' },
                    { id: 'tpl_p1', type: 'text', label: 'Attendee Full Name', name: 'attendee_name', placeholder: 'e.g. Alex Morgan', required: true, width: '50' },
                    { id: 'tpl_p2', type: 'email', label: 'Confirmation Email', name: 'attendee_email', placeholder: 'alex@example.com', required: true, width: '50' },
                    { id: 'tpl_p3', type: 'intl_tel', label: 'Mobile Number', name: 'attendee_phone', required: true, width: '50' },
                    { id: 'tpl_p4', type: 'select', label: 'Pass Tier Selection', name: 'pass_tier', options: ['General Admission ($49)', 'VIP Access & Networking ($99)', 'Student Pass ($25)'], required: true, width: '50' },
                    { id: 'tpl_p5', type: 'payment', label: 'Secure Checkout Gateway', name: 'event_payment', payment_amount: '49.00', payment_currency: 'USD', required: true, width: '100' }
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
            this.savedState = (window.uspBuilderData || {}).form ? this.getCurrentState() : '';
            this.updateSaveStatus();
        },

        loadInitialData: function() {
            var data = window.uspBuilderData || {};
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
            $('#usp-panel-form-settings input[id], #usp-panel-form-settings textarea[id], #usp-panel-form-settings select[id], #usp-panel-theme-style input[id], #usp-panel-theme-style textarea[id], #usp-panel-theme-style select[id]').each(function() {
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
                title: $('#usp-form-title-input').val() || '',
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
            $('#usp-canvas-submit-preview').text($('#usp-setting-btn-text').val() || 'Submit');
            [
                ['#usp-setting-send-email', '#usp-email-target-wrap'],
                ['#usp-setting-user-autoreply', '#usp-autoreply-fields-wrap'],
                ['#usp-setting-limit-submissions', '#usp-limit-entries-wrap'],
                ['#usp-setting-close-date-toggle', '#usp-close-date-wrap'],
                ['#usp-setting-require-login', '#usp-login-msg-wrap'],
                ['#usp-setting-enable-webhook', '#usp-webhook-fields-wrap']
            ].forEach(function(pair) {
                $(pair[1]).stop(true, true).toggle($(pair[0]).is(':checked'));
            });
            [
                ['.usp-theme-card', 'preset', '#usp-theme-preset-val'],
                ['.usp-radius-opt', 'radius', '#usp-theme-radius-val'],
                ['.usp-btn-align-opt', 'align', '#usp-theme-button-align-val']
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

            $('#usp-btn-undo').prop('disabled', !canUndo).attr('title', canUndo ? 'Undo (' + this.historyStack[this.historyIndex].label + ') [Ctrl+Z]' : 'Nothing to undo');
            $('#usp-btn-redo').prop('disabled', !canRedo).attr('title', canRedo ? 'Redo (' + this.historyStack[this.historyIndex + 1].label + ') [Ctrl+Y]' : 'Nothing to redo');
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

            var activeTab = $('.usp-tab-btn.active').data('tab');
            this.isHistoryAction = true;
            var controls = snapshot.controls || {};
            Object.keys(controls).forEach(function(id) {
                var $input = $(document.getElementById(id));
                if ($input.is(':checkbox')) $input.prop('checked', controls[id]);
                else $input.val(controls[id]);
            });
            this.fields = JSON.parse(JSON.stringify(snapshot.fields));
            if (snapshot.title !== undefined) {
                $('#usp-form-title-input').val(snapshot.title);
                $('#usp-canvas-title-preview').text(snapshot.title || 'Untitled Form');
            }

            this.renderCanvas();
            if (this.selectedFieldId && this.fields.some(function(f) { return f.id === USPBuilder.selectedFieldId; })) {
                this.selectField(this.selectedFieldId);
            } else if (this.fields.length > 0) {
                this.selectField(this.fields[0].id);
            } else {
                this.selectedFieldId = null;
                this.renderInspector();
            }

            this.syncRestoredSettings();
            if (activeTab) $('.usp-tab-btn').filter(function() {
                return $(this).data('tab') === activeTab;
            }).trigger('click');
            this.isHistoryAction = false;
            this.updateSaveStatus();
        },

        bindEvents: function() {
            var self = this;

            $(window).on('beforeunload.uspBuilder', function(e) {
                if (self.hasUnsavedChanges()) {
                    e.preventDefault();
                    e.originalEvent.returnValue = '';
                    return '';
                }
            });
            $('.usp-builder-wrap').on('input change', 'input, textarea, select', function() {
                setTimeout(function() { self.updateSaveStatus(); }, 0);
            });
            // Undo and Redo Button Clicks
            $('#usp-btn-undo').on('click', function() {
                self.undo();
            });
            $('#usp-btn-redo').on('click', function() {
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
            $('#usp-open-preview-btn').on('click', function() {
                self.openLivePreview();
            });
            $('#usp-live-preview-close-btn').on('click', function() {
                $('#usp-live-preview-modal').fadeOut(150);
            });
            $('#usp-live-preview-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).fadeOut(150);
                }
            });

            // Live Preview Device Switcher
            $(document).on('click', '.usp-preview-device-btn', function() {
                var device = $(this).data('device');
                $('.usp-preview-device-btn').removeClass('active').css({'background':'transparent', 'font-weight':'600', 'color':'#64748b', 'box-shadow':'none'});
                $(this).addClass('active').css({'background':'#ffffff', 'font-weight':'700', 'color':'#1e293b', 'box-shadow':'0 1px 2px rgba(0,0,0,0.05)'});

                var $viewport = $('#usp-live-preview-viewport');
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
            $('.usp-device-btn').on('click', function() {
                var device = $(this).data('device');
                $('.usp-device-btn').removeClass('active');
                $(this).addClass('active');

                var $canvas = $('#usp-canvas-container');
                $canvas.removeClass('device-desktop device-tablet device-mobile');
                $canvas.addClass('device-' + device);
            });

            // Template Modal Trigger & Dismiss
            $('#usp-open-templates-btn').on('click', function() {
                $('#usp-templates-modal').fadeIn(150);
            });

            $('#usp-templates-close-btn, #usp-templates-cancel-btn').on('click', function() {
                $('#usp-templates-modal').fadeOut(150);
            });

            $('#usp-templates-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).fadeOut(150);
                }
            });

            // Template Card Selection
            $(document).on('click', '.usp-template-card', function() {
                var tplKey = $(this).data('template');
                if (self.templates[tplKey]) {
                    if (self.fields.length > 0) {
                        USPModal.confirm({
                            title: 'Load Pre-Built Template?',
                            message: 'Loading this template will replace the current fields on your canvas with the template layout. Do you wish to proceed?',
                            type: 'warning',
                            confirmText: 'Load Template',
                            cancelText: 'Cancel'
                        }).then(function(confirmed) {
                            if (confirmed) {
                                self.loadTemplate(tplKey);
                                $('#usp-templates-modal').fadeOut(150);
                            }
                        });
                        return;
                    }
                    self.loadTemplate(tplKey);
                    $('#usp-templates-modal').fadeOut(150);
                }
            });

            // Palette item click (Add Field)
            $(document).on('click', '.usp-palette-item:not(.usp-pro-locked)', function() {
                var type = $(this).data('type');
                self.addField(type);
            });

            // Pro Locked Click Handler
            $(document).on('click', '.usp-palette-item.usp-pro-locked', function() {
                var fieldTitle = $(this).find('.title').text();
                USPModal.confirm({
                    title: 'Pro Feature: ' + fieldTitle,
                    message: '<p>The <strong>' + fieldTitle + '</strong> field is an enterprise module included in <strong>USP Form Builder Pro</strong>.</p><p style="color:#64748b; font-size:13px; margin-top:8px;">Activate your Pro license or enable Developer Mode in the <strong>Pro & Security Hub</strong> to unlock all enterprise fields immediately.</p>',
                    type: 'info',
                    confirmText: 'Go to Pro & Security Hub',
                    cancelText: 'Cancel'
                }).then(function(confirmed) {
                    if (confirmed) {
                        window.location.href = 'admin.php?page=usp-form-pro';
                    }
                });
            });

            $('.usp-palette-panel-btn').on('click', function() {
                var outline = $(this).data('panel') === 'outline';
                $('.usp-palette-panel-btn').removeClass('active').attr('aria-pressed', 'false');
                $(this).addClass('active').attr('aria-pressed', 'true');
                $('#usp-add-fields-panel').prop('hidden', outline);
                $('.usp-palette-search-wrap').prop('hidden', outline);
                $('#usp-outline-panel').prop('hidden', !outline);
                if (outline) self.renderOutline();
            });
            $('#usp-outline-search').on('input', function() { self.renderOutline(); });
            $(document).on('click', '.usp-outline-field', function() {
                var fieldId = $(this).attr('data-field-id');
                self.selectField(fieldId);
                var card = $('.usp-canvas-field-card').filter(function() {
                    return $(this).attr('data-id') === fieldId;
                })[0];
                if (card) card.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            });
            // Canvas field card click (Select Field)
            $(document).on('click', '.usp-canvas-field-card', function(e) {
                if ($(e.target).closest('.usp-card-actions').length) {
                    return;
                }
                var fieldId = $(this).data('id');
                self.selectField(fieldId);
            });

            // Duplicate Field
            $(document).on('click', '.usp-card-duplicate-btn', function(e) {
                e.stopPropagation();
                var fieldId = $(this).closest('.usp-canvas-field-card').data('id');
                self.duplicateField(fieldId);
            });

            // Delete Field
            $(document).on('click', '.usp-card-delete-btn', function(e) {
                e.stopPropagation();
                var fieldId = $(this).closest('.usp-canvas-field-card').data('id');
                self.deleteField(fieldId);
            });

            // Move Up / Move Down
            $(document).on('click', '.usp-card-move-up', function(e) {
                e.stopPropagation();
                var fieldId = $(this).closest('.usp-canvas-field-card').data('id');
                self.moveField(fieldId, -1);
            });
            $(document).on('click', '.usp-card-move-down', function(e) {
                e.stopPropagation();
                var fieldId = $(this).closest('.usp-canvas-field-card').data('id');
                self.moveField(fieldId, 1);
            });

            // Tab Navigation in Inspector
            $('.usp-tab-btn').on('click', function() {
                var tab = $(this).data('tab');
                $('.usp-tab-btn').removeClass('active');
                $(this).addClass('active');
                $('.usp-tab-panel').removeClass('active');
                $('#usp-panel-' + tab).addClass('active');
            });

            // Width Segmented Option Click
            $(document).on('click', '.usp-width-opt', function() {
                var widthVal = $(this).data('val');
                $('.usp-width-opt').removeClass('active');
                $(this).addClass('active');

                var field = self.fields.find(function(f) { return f.id === self.selectedFieldId; });
                if (field) {
                    field.width = String(widthVal);
                    var $card = $('.usp-canvas-field-card[data-id="' + field.id + '"]');
                    $card.removeClass('width-100 width-50 width-33').addClass('width-' + widthVal);
                    self.scheduleHistorySnapshot('Change field width');
                    self.updateSaveStatus();
                }
            });

            // Conditional Logic Toggle Switch
            $(document).on('change', '#insp-enable-conditions', function() {
                var isEnabled = $(this).is(':checked');
                if (isEnabled) {
                    $('#usp-conditional-rules-wrap').slideDown(150);
                    // Add default rule if none exist
                    if ($('#usp-condition-rules-list .usp-cond-rule-row').length === 0) {
                        self.addConditionRuleToInspector();
                    }
                } else {
                    $('#usp-conditional-rules-wrap').slideUp(150);
                }
                self.updateFieldFromInspector();
            });

            // Add Condition Rule Button
            $(document).on('click', '#usp-add-rule-btn', function() {
                self.addConditionRuleToInspector();
                self.updateFieldFromInspector();
            });

            // Remove Condition Rule Button
            $(document).on('click', '.usp-remove-rule-btn', function() {
                $(this).closest('.usp-cond-rule-row').remove();
                self.updateFieldFromInspector();
            });

            // Inspector Real-time Updates (Two-way binding)
            $(document).on('input change', '#usp-field-settings-content input, #usp-field-settings-content textarea, #usp-field-settings-content select', function() {
                self.updateFieldFromInspector();
            });

            // Add Option button for choice fields
            $(document).on('click', '#usp-add-option-btn', function() {
                self.addOptionToSelectedField();
            });

            // Remove Option button
            $(document).on('click', '.usp-remove-option-btn', function() {
                $(this).closest('.usp-option-row').remove();
                self.updateFieldFromInspector();
            });

            // Form Title live sync
            $('#usp-form-title-input').on('input', function() {
                $('#usp-canvas-title-preview').text($(this).val() || 'Untitled Form');
                self.scheduleHistorySnapshot('Edit form title');
            });

            // Form Submit button live sync
            $('#usp-setting-btn-text').on('input', function() {
                $('#usp-canvas-submit-preview').text($(this).val() || 'Submit');
            });

            // Save Form Action
            $('#usp-save-form-btn').on('click', function() {
                self.saveForm();
            });

            // Drag and Drop ordering on canvas
            this.setupDragAndDrop();

            // Shortcode copy pill
            $('.usp-shortcode-tag').on('click', function() {
                var shortcode = $(this).data('shortcode');
                if (shortcode) {
                    navigator.clipboard.writeText(shortcode).then(function() {
                        self.showToast('Shortcode copied to clipboard!', 'success');
                    });
                }
            });

            // Quick Title Edit Icon Click
            $('.usp-title-edit-icon').on('click', function() {
                $('#usp-form-title-input').focus().select();
            });

            // Palette Real-time Search Filter
            $('#usp-palette-search-input').on('input', function() {
                var q = $(this).val().toLowerCase().trim();
                if (!q) {
                    $('.usp-palette-item').show();
                    $('.usp-fields-group').show();
                    $('#usp-palette-no-results').prop('hidden', true);
                    return;
                }
                $('.usp-fields-group').each(function() {
                    var $group = $(this);
                    var matched = 0;
                    $group.find('.usp-palette-item').each(function() {
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
                $('#usp-palette-no-results').prop('hidden',
                    $('.usp-palette-item').filter(function() {
                        return this.style.display !== 'none';
                    }).length > 0);
            });

            // Embed Modal Trigger & Dismiss
            $('#usp-open-embed-btn').on('click', function() {
                $('#usp-embed-modal').fadeIn(150);
            });

            $('#usp-embed-close-btn, #usp-embed-done-btn').on('click', function() {
                $('#usp-embed-modal').fadeOut(150);
            });

            $('#usp-embed-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).fadeOut(150);
                }
            });

            $('#usp-embed-copy-btn').on('click', function() {
                var code = $('#usp-embed-shortcode-input').val();
                if (code) {
                    navigator.clipboard.writeText(code).then(function() {
                        self.showToast('Shortcode copied to clipboard!', 'success');
                    });
                }
            });

            // Restrictions & Scheduling toggles
            $('#usp-setting-limit-submissions').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#usp-limit-entries-wrap').slideDown(150);
                } else {
                    $('#usp-limit-entries-wrap').slideUp(150);
                }
            });

            $('#usp-setting-close-date-toggle').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#usp-close-date-wrap').slideDown(150);
                } else {
                    $('#usp-close-date-wrap').slideUp(150);
                }
            });

            $('#usp-setting-require-login').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#usp-login-msg-wrap').slideDown(150);
                } else {
                    $('#usp-login-msg-wrap').slideUp(150);
                }
            });

            // Webhook toggle
            $('#usp-setting-enable-webhook').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#usp-webhook-fields-wrap').slideDown(150);
                } else {
                    $('#usp-webhook-fields-wrap').slideUp(150);
                }
            });

            // Webhook Test Payload Button
            $('#usp-test-webhook-btn').on('click', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var url = ($('#usp-setting-webhook-url').val() || '').trim();
                var secret = ($('#usp-setting-webhook-secret').val() || '').trim();
                var $status = $('#usp-test-webhook-status');

                if (!url) {
                    USPModal.alert({
                        title: 'Webhook URL Required',
                        message: 'Please enter a valid Webhook URL before testing.',
                        type: 'warning'
                    });
                    return;
                }

                $btn.prop('disabled', true).text('Dispatching...');
                $status.html('<span style="color:#64748b;">Testing endpoint...</span>');

                $.post(window.uspBuilderData.ajax_url, {
                    action: 'usp_test_webhook',
                    nonce: window.uspBuilderData.nonce,
                    webhook_url: url,
                    webhook_secret: secret
                }, function(res) {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-networking" style="vertical-align:middle; margin-top:-2px;"></span> Send Test Payload');
                    if (res.success) {
                        $status.html('<span style="color:#15803d; font-weight:700;"><span class="dashicons dashicons-yes" style="font-size:16px; vertical-align:middle;"></span> ' + res.data.message + '</span>');
                        USPModal.toast(res.data.message, 'success');
                    } else {
                        $status.html('<span style="color:#b91c1c; font-weight:600;"><span class="dashicons dashicons-warning" style="font-size:16px; vertical-align:middle;"></span> ' + (res.data ? res.data.message : 'Failed') + '</span>');
                        USPModal.alert({
                            title: 'Webhook Test Failed',
                            message: res.data ? res.data.message : 'Connection failed.',
                            type: 'error'
                        });
                    }
                }).fail(function() {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-networking" style="vertical-align:middle; margin-top:-2px;"></span> Send Test Payload');
                    $status.html('<span style="color:#b91c1c;">Network Error</span>');
                    USPModal.alert({
                        title: 'Network Error',
                        message: 'Could not contact server to dispatch webhook test.',
                        type: 'error'
                    });
                });
            });

            // Export Form JSON button
            $('#usp-export-json-btn').on('click', function(e) {
                e.preventDefault();
                self.exportFormJson();
            });

            $('.usp-inspector-sidebar').on('input change', '#usp-panel-form-settings input, #usp-panel-form-settings textarea, #usp-panel-form-settings select, #usp-panel-theme-style input, #usp-panel-theme-style textarea, #usp-panel-theme-style select', function() {
                self.scheduleHistorySnapshot('Edit form settings or style');
            });


            // Theme Preset Selection
            $(document).on('click', '.usp-theme-card', function() {
                var $card = $(this);
                var preset = $card.data('preset');
                var color = $card.data('color');
                var bg = $card.data('bg');
                var btn = $card.data('btn');

                $('.usp-theme-card').removeClass('active');
                $card.addClass('active');
                $('#usp-theme-preset-val').val(preset);

                if (color) {
                    $('#usp-theme-primary-picker').val(color);
                    $('#usp-theme-primary-color').val(color);
                }
                if (bg) {
                    $('#usp-theme-bg-picker').val(bg);
                    $('#usp-theme-bg-color').val(bg);
                }
                if (btn) {
                    $('#usp-theme-btn-picker').val(btn);
                    $('#usp-theme-btn-color').val(btn);
                }

                self.applyThemeLive();
            });

            // Color pickers live sync
            $('#usp-theme-primary-picker').on('input', function() {
                $('#usp-theme-primary-color').val($(this).val());
                self.applyThemeLive();
            });
            $('#usp-theme-primary-color').on('input', function() {
                var val = $(this).val();
                if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
                    $('#usp-theme-primary-picker').val(val);
                }
                self.applyThemeLive();
            });

            $('#usp-theme-btn-picker').on('input', function() {
                $('#usp-theme-btn-color').val($(this).val());
                self.applyThemeLive();
            });
            $('#usp-theme-btn-color').on('input', function() {
                var val = $(this).val();
                if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
                    $('#usp-theme-btn-picker').val(val);
                }
                self.applyThemeLive();
            });

            $('#usp-theme-bg-picker').on('input', function() {
                $('#usp-theme-bg-color').val($(this).val());
                self.applyThemeLive();
            });
            $('#usp-theme-bg-color').on('input', function() {
                var val = $(this).val();
                if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
                    $('#usp-theme-bg-picker').val(val);
                }
                self.applyThemeLive();
            });

            // Corner Radius segmented options
            $('.usp-radius-opt').on('click', function() {
                $('.usp-radius-opt').removeClass('active');
                $(this).addClass('active');
                var radius = $(this).data('radius');
                $('#usp-theme-radius-val').val(radius);
                self.applyThemeLive();
            });

            // Button Alignment options
            $('.usp-btn-align-opt').on('click', function() {
                $('.usp-btn-align-opt').removeClass('active');
                $(this).addClass('active');
                var align = $(this).data('align');
                $('#usp-theme-button-align-val').val(align);
                self.applyThemeLive();
            });

            // Container Shadow select
            $('#usp-theme-shadow-val').on('change', function() {
                self.applyThemeLive();
            });

            // Custom Scoped CSS live preview
            $('#usp-theme-custom-css').on('input', function() {
                self.applyThemeLive();
            });
            $(document).on('click', '.usp-theme-card, .usp-radius-opt, .usp-btn-align-opt', function() {
                self.scheduleHistorySnapshot('Change form style');
                self.updateSaveStatus();
            });
        },

        loadTemplate: function(tplKey) {
            this.flushPendingHistory();
            var tpl = this.templates[tplKey];
            if (!tpl) return;
            var access = window.uspBuilderData || {};
            if (!access.is_pro && tpl.fields.some(function(field) {
                return (access.pro_field_types || []).indexOf(field.type) !== -1;
            })) {
                this.showToast('This template contains Pro fields. Activate your license and install Pro first.', 'error');
                return;
            }

            // Generate clean IDs for template fields
            var newFields = JSON.parse(JSON.stringify(tpl.fields));
            newFields.forEach(function(f) {
                f.id = 'field_' + Math.random().toString(36).substring(2, 9);
            });

            this.fields = newFields;
            if (tpl.title) {
                $('#usp-form-title-input').val(tpl.title);
                $('#usp-canvas-title-preview').text(tpl.title);
            }

            if (tpl.settings) {
                if (tpl.settings.submit_button_text) {
                    $('#usp-setting-btn-text').val(tpl.settings.submit_button_text);
                    $('#usp-canvas-submit-preview').text(tpl.settings.submit_button_text);
                }
                if (tpl.settings.success_message) {
                    $('#usp-setting-success-msg').val(tpl.settings.success_message);
                }
                if (tpl.settings.email_subject) {
                    $('#usp-setting-email-subject').val(tpl.settings.email_subject);
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

            $(document).on('dragstart', '.usp-canvas-field-card', function(e) {
                self.draggedIndex = $(this).index();
                e.originalEvent.dataTransfer.effectAllowed = 'move';
            });

            $(document).on('dragover', '.usp-canvas-field-card', function(e) {
                e.preventDefault();
                e.originalEvent.dataTransfer.dropEffect = 'move';
                $(this).addClass('drag-over');
            });

            $(document).on('dragleave', '.usp-canvas-field-card', function() {
                $(this).removeClass('drag-over');
            });

            $(document).on('drop', '.usp-canvas-field-card', function(e) {
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
            var builderData = window.uspBuilderData || {};
            var contentMode = {content_heading:'heading',content_paragraph:'paragraph',content_bullets:'bullets',content_numbered:'numbered'}[type];
            if (contentMode) type = 'html';
            var isNationality = type === 'nationality';
            if (isNationality) type = 'country';
            if (!builderData.is_pro && (builderData.pro_field_types || []).indexOf(type) !== -1) {
                this.showToast('This field is available in USP Form Builder Pro.', 'error');
                return;
            }
            var labels = (window.uspBuilderData || {}).i18n || {};
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
                address: 'Complete Address',
                admission: 'University Admission Application',
                intl_tel: 'International Telephone',
                payment: 'Payment Details (Non-processing)',
                page_break: 'Multi-Step Page Break',
                signature: 'Digital Signature',
                calculation: 'Calculated Field'
            };

            var newField = {
                id: 'field_' + this.generateId(),
                type: type,
                label: defaultLabels[type] || 'New Field',
                name: 'field_' + this.generateId(),
                placeholder: '',
                required: (type === 'email' || type === 'admission' || type === 'payment' || type === 'consent'),
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
                newField.help_text = 'Click a star to rate from 1 to 5';
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

            if (type === 'signature') {
                newField.canvas_height = 160;
                newField.pen_color = '#0f172a';
            }

            if (type === 'calculation') {
                newField.formula = '';
                newField.calc_prefix = '$';
                newField.calc_suffix = '';
                newField.decimals = 2;
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

            if (type === 'address') {
                newField.address_show_street = true;
                newField.address_show_postal = true;
            }

            if (type === 'admission') {
                newField.help_text = 'Applicant biodata, degree choice, and academic records.';
            }

            if (type === 'intl_tel') {
                newField.placeholder = '300 1234567';
                newField.help_text = 'Select international country code with instant formatting.';
            }

            if (type === 'payment') {
                newField.payment_amount = '50.00';
                newField.payment_currency = 'USD';
                newField.help_text = 'Secured 256-bit SSL encrypted card checkout.';
            }

            this.fields.push(newField);
            this.renderCanvas();
            this.selectField(newField.id);
            this.takeHistorySnapshot('Add ' + (defaultLabels[type] || 'Field'));
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
                USPModal.confirm({
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
            $('.usp-canvas-field-card').removeClass('active');
            $('.usp-canvas-field-card[data-id="' + fieldId + '"]').addClass('active');

            // Switch to Field Options tab
            $('.usp-tab-btn[data-tab="field-settings"]').trigger('click');
            this.renderInspector();
            $('#usp-panel-field-settings').scrollTop(0);
        },

        getOutlineItems: function(query) {
            query = String(query || '').toLowerCase().trim();
            return this.fields.map(function(field, index) {
                var typeInfo = ((window.uspBuilderData || {}).field_types || {})[field.type] || {};
                return { field: field, position: index + 1, typeLabel: typeInfo.label || field.type };
            }).filter(function(item) {
                return [item.field.label, item.field.name, item.field.type, item.typeLabel]
                    .join(' ').toLowerCase().indexOf(query) !== -1;
            });
        },

        updateOutlineSelection: function() {
            var selectedId = this.selectedFieldId;
            $('.usp-outline-field').each(function() {
                var selected = $(this).attr('data-field-id') === selectedId;
                $(this).toggleClass('active', selected).attr('aria-pressed', String(selected));
            });
        },

        renderOutline: function() {
            var $list = $('#usp-outline-list');
            if (!$list.length) return;
            var self = this;
            var labels = (window.uspBuilderData || {}).i18n || {};
            var focusedId = $(document.activeElement).closest('.usp-outline-field').attr('data-field-id');
            var items = this.getOutlineItems($('#usp-outline-search').val());
            $list.empty();
            items.forEach(function(item) {
                var field = item.field;
                var $button = $('<button>', { type: 'button', class: 'usp-outline-field' })
                    .attr('data-field-id', field.id);
                $('<span>', { class: 'usp-outline-position' }).text(item.position).appendTo($button);
                var $details = $('<span>', { class: 'usp-outline-details' }).appendTo($button);
                $('<span>', { class: 'usp-outline-label' }).text(field.label || labels.outline_untitled || 'Untitled field').appendTo($details);
                $('<span>', { class: 'usp-outline-type' }).text(item.typeLabel).appendTo($details);
                var badges = [];
                if (field.required) badges.push(labels.outline_required || 'Required');
                if (field.enable_conditions && field.conditions && field.conditions.length) {
                    badges.push(labels.outline_conditions || 'Conditional');
                }
                if (badges.length) $('<span>', { class: 'usp-outline-badges' }).text(badges.join(' · ')).appendTo($details);
                $('<li>').append($button).appendTo($list);
                if (focusedId === field.id) $button[0].focus({ preventScroll: true });
            });
            $('#usp-outline-count').text((labels.outline_count || '%shown% of %total% fields')
                .replace('%shown%', items.length).replace('%total%', self.fields.length));
            $('#usp-outline-empty').prop('hidden', items.length > 0).text(this.fields.length ?
                (labels.outline_no_matches || 'No fields match. Try a different label or field type.') :
                (labels.outline_empty || 'Your form has no fields. Choose Add fields to get started.'));
            this.updateOutlineSelection();
        },

        renderCanvas: function() {
            this.renderOutline();
            var $list = $('#usp-canvas-fields-list');
            $list.empty();

            if (this.fields.length === 0) {
                $('#usp-canvas-empty').show();
                return;
            } else {
                $('#usp-canvas-empty').hide();
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
                'address': 'dashicons-location-alt',
                'admission': 'dashicons-welcome-learn-more',
                'intl_tel': 'dashicons-translation',
                'payment': 'dashicons-cart',
                'page_break': 'dashicons-editor-break',
                'signature': 'dashicons-edit',
                'calculation': 'dashicons-calculator'
            };

            var self = this;
            this.fields.forEach(function(field, idx) {
                var isActive = (field.id === self.selectedFieldId) ? 'active' : '';
                var isFirst = (idx === 0);
                var isLast = (idx === self.fields.length - 1);
                var widthClass = 'width-' + (field.width || '100');
                var typeIcon = typeIconMap[field.type] || 'dashicons-admin-generic';

                var cardHtml = '<div class="usp-canvas-field-card ' + isActive + ' ' + widthClass + '" data-id="' + field.id + '" draggable="true">';
                
                // Top bar of card
                cardHtml += '<div class="usp-card-top-bar">';
                cardHtml += '  <div class="usp-card-handle" title="Drag to reorder">';
                cardHtml += '    <span class="dashicons dashicons-menu usp-handle-move-icon"></span>';
                cardHtml += '    <span class="usp-card-type-tag"><span class="dashicons ' + typeIcon + '"></span> ' + field.type.toUpperCase() + '</span>';
                if (field.enable_conditions && Array.isArray(field.conditions) && field.conditions.length > 0) {
                    cardHtml += '    <span class="usp-card-cond-badge" title="Has conditional logic rules"><span class="dashicons dashicons-randomize"></span> If/Then</span>';
                }
                cardHtml += '  </div>';
                cardHtml += '  <div class="usp-card-actions">';
                if (!isFirst) {
                    cardHtml += '    <button type="button" class="usp-card-action-btn usp-card-move-up" title="Move Up" aria-label="Move Up"><span class="dashicons dashicons-arrow-up-alt2"></span></button>';
                }
                if (!isLast) {
                    cardHtml += '    <button type="button" class="usp-card-action-btn usp-card-move-down" title="Move Down" aria-label="Move Down"><span class="dashicons dashicons-arrow-down-alt2"></span></button>';
                }
                cardHtml += '    <button type="button" class="usp-card-action-btn usp-card-duplicate-btn" title="Duplicate Field" aria-label="Duplicate Field"><span class="dashicons dashicons-admin-page"></span></button>';
                cardHtml += '    <button type="button" class="usp-card-action-btn btn-delete usp-card-delete-btn" title="Delete Field" aria-label="Delete Field"><span class="dashicons dashicons-trash"></span></button>';
                cardHtml += '  </div>';
                cardHtml += '</div>';

                // Field preview
                if (field.type === 'html') {
                    cardHtml += '<div class="usp-mock-section-block">' + contentPreview(field) + '</div>';
                } else if (field.type === 'hidden') {
                    cardHtml += '<div class="usp-mock-hidden-badge">';
                    cardHtml += '  <span class="dashicons dashicons-hidden"></span> ';
                    cardHtml += '  <span>Hidden Parameter: <code>' + (field.name || 'key') + '</code> = <em>"' + (field.default_val || '') + '"</em></span>';
                    cardHtml += '</div>';
                } else {
                    cardHtml += '<label class="usp-card-label">' + (field.label || 'Untitled Field') + (field.required ? '<span class="required-star">*</span>' : '') + '</label>';

                    if (field.type === 'textarea') {
                        cardHtml += '<textarea class="usp-card-mock-input" rows="' + (field.rows || 3) + '" placeholder="' + (field.placeholder || 'Enter ' + (field.label || 'message') + '...') + '" disabled></textarea>';
                    } else if (field.type === 'select' || field.type === 'country') {
                        cardHtml += '<div class="usp-mock-select-wrap">';
                        cardHtml += '  <select class="usp-card-mock-input" disabled><option>' + (field.placeholder || 'Select an option...') + '</option></select>';
                        cardHtml += '  <span class="dashicons dashicons-arrow-down-alt2 usp-mock-select-arrow"></span>';
                        cardHtml += '</div>';
                    } else if (field.type === 'radio') {
                        var opts = field.options || ['Choice 1', 'Choice 2'];
                        cardHtml += '<div class="usp-mock-choices-list">';
                        opts.forEach(function(o) {
                            cardHtml += '<label class="usp-mock-choice-item"><input type="radio" disabled> <span>' + o + '</span></label>';
                        });
                        cardHtml += '</div>';
                    } else if (field.type === 'checkbox') {
                        var opts = field.options || ['Option 1', 'Option 2'];
                        cardHtml += '<div class="usp-mock-choices-list">';
                        opts.forEach(function(o) {
                            cardHtml += '<label class="usp-mock-choice-item"><input type="checkbox" disabled> <span>' + o + '</span></label>';
                        });
                        cardHtml += '</div>';
                    } else if (field.type === 'rating') {
                        var maxStars = parseInt(field.max_stars, 10) || 5;
                        cardHtml += '<div class="usp-mock-rating-wrap" style="display:flex; align-items:center; gap:6px; padding:6px 0;">';
                        for (var starI = 1; starI <= maxStars; starI++) {
                            cardHtml += '<span class="dashicons dashicons-star-filled" style="font-size:24px; width:24px; height:24px; color:#f59e0b;"></span>';
                        }
                        cardHtml += '  <span style="font-size:12px; color:#64748b; margin-left:6px; font-weight:600;">(1 to ' + maxStars + ' stars)</span>';
                        cardHtml += '</div>';
                    } else if (field.type === 'consent') {
                        cardHtml += '<div class="usp-mock-consent-wrap" style="display:flex; align-items:flex-start; gap:10px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px; margin-top:4px;">';
                        cardHtml += '  <input type="checkbox" disabled style="margin-top:2px;">';
                        cardHtml += '  <div style="font-size:13px; color:#334155; line-height:1.45;">';
                        cardHtml += '    <span>' + (field.consent_text || 'I agree to the Terms of Service and Privacy Policy.') + '</span> ';
                        if (field.terms_url) {
                            cardHtml += '    <a href="' + field.terms_url + '" target="_blank" style="color:#4f46e5; text-decoration:underline; font-weight:600;">' + (field.terms_label || 'Terms & Conditions') + '</a>';
                        }
                        cardHtml += '  </div>';
                        cardHtml += '</div>';
                    } else if (field.type === 'range') {
                        cardHtml += '<div class="usp-mock-range-wrap">';
                        cardHtml += '  <div class="usp-mock-range-header">';
                        cardHtml += '    <span>Selected: <strong>' + (field.default_val || 5) + '</strong></span>';
                        cardHtml += '    <span class="usp-mock-range-bounds">Range: ' + (field.min || 1) + ' – ' + (field.max || 10) + '</span>';
                        cardHtml += '  </div>';
                        cardHtml += '  <input type="range" class="usp-card-mock-input usp-mock-range-input" min="' + (field.min || 1) + '" max="' + (field.max || 10) + '" value="' + (field.default_val || 5) + '" disabled />';
                        cardHtml += '</div>';
                    } else if (field.type === 'file') {
                        cardHtml += '<div class="usp-mock-file-card">';
                        cardHtml += '  <div class="usp-mock-file-icon"><span class="dashicons dashicons-upload"></span></div>';
                        cardHtml += '  <div class="usp-mock-file-text">';
                        cardHtml += '    <strong>Click or drag file to upload</strong>';
                        cardHtml += '    <small>Allowed: ' + (field.allowed_ext || 'pdf, doc, docx, jpg, png') + ' (Max: ' + (field.max_size || 5) + 'MB)</small>';
                        cardHtml += '  </div>';
                        cardHtml += '</div>';
                    } else if (field.type === 'time') {
                        cardHtml += '<input type="time" class="usp-card-mock-input" disabled />';
                    } else if (field.type === 'date') {
                        cardHtml += '<input type="text" class="usp-card-mock-input" placeholder="YYYY-MM-DD" disabled />';
                    } else if (field.type === 'address') {
                        cardHtml += '<div class="usp-mock-address-grid">';
                        var parts = (field.address_show_street !== false ? ['Street address'] : []).concat(['Country', 'Province / State', 'City']);
                        if (field.address_show_postal !== false) parts.push('Postal / ZIP code');
                        parts.forEach(function(part) {
                            cardHtml += '<div class="usp-card-mock-input">' + part + '</div>';
                        });
                        cardHtml += '</div><p class="description">Country → Province / State → City. Lists update automatically.</p>';
                    } else if (field.type === 'admission') {
                        cardHtml += '<div class="usp-mock-admission-box" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px; margin-top:6px;">';
                        cardHtml += '  <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:8px;">';
                        cardHtml += '    <input type="text" class="usp-card-mock-input" placeholder="Applicant Full Name" disabled style="margin:0;">';
                        cardHtml += '    <input type="text" class="usp-card-mock-input" placeholder="Father Name" disabled style="margin:0;">';
                        cardHtml += '  </div>';
                        cardHtml += '  <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">';
                        cardHtml += '    <input type="text" class="usp-card-mock-input" placeholder="CNIC / Form-B" disabled style="margin:0;">';
                        cardHtml += '    <select class="usp-card-mock-input" disabled style="margin:0;"><option>Select Degree Program...</option></select>';
                        cardHtml += '  </div>';
                        cardHtml += '</div>';
                    } else if (field.type === 'intl_tel') {
                        cardHtml += '<div style="display:flex; flex-direction:column; gap:6px;">';
                        cardHtml += '  <div style="display:flex; gap:8px;">';
                        cardHtml += '    <select class="usp-card-mock-input" disabled style="max-width:130px; font-weight:600;"><option>🇵🇰 +92 (PK)</option></select>';
                        cardHtml += '    <input type="tel" class="usp-card-mock-input" placeholder="' + (field.placeholder || '300 1234567') + '" disabled style="flex:1;">';
                        cardHtml += '  </div>';
                        cardHtml += '  <div style="display:flex; justify-content:space-between; font-size:11px; color:#64748b; padding:0 2px;">';
                        cardHtml += '    <span>Format: <code>XXX XXXXXXX</code></span>';
                        cardHtml += '    <span style="font-weight:600; background:#f1f5f9; padding:1px 6px; border-radius:10px; border:1px solid #e2e8f0;">10 digits max</span>';
                        cardHtml += '  </div>';
                        cardHtml += '</div>';
                    } else if (field.type === 'payment') {
                        cardHtml += '<div class="usp-mock-payment-box" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:14px; margin-top:6px;">';
                        cardHtml += '  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; padding-bottom:8px; border-bottom:1px solid #e2e8f0;">';
                        cardHtml += '    <span style="font-weight:700; color:#4f46e5; font-size:13.5px;">Fee: ' + (field.payment_currency || 'USD') + ' ' + (field.payment_amount || '50.00') + '</span>';
                        cardHtml += '    <span style="font-size:11px; color:#059669; font-weight:600;"><span class="dashicons dashicons-lock" style="font-size:13px; width:13px; height:13px;"></span> 256-bit SSL</span>';
                        cardHtml += '  </div>';
                        cardHtml += '  <div style="display:flex; gap:6px; margin-bottom:8px; align-items:center;">';
                        cardHtml += '    <span style="font-size:10.5px; font-weight:600; color:#64748b; text-transform:uppercase;">Auto-Detect:</span>';
                        cardHtml += '    <span style="font-size:10px; font-weight:700; background:#e0e7ff; color:#1e40af; padding:1px 5px; border-radius:4px;">VISA</span>';
                        cardHtml += '    <span style="font-size:10px; font-weight:700; background:#fef3c7; color:#b45309; padding:1px 5px; border-radius:4px;">MC</span>';
                        cardHtml += '    <span style="font-size:10px; font-weight:700; background:#e0f2fe; color:#0369a1; padding:1px 5px; border-radius:4px;">AMEX</span>';
                        cardHtml += '    <span style="font-size:10px; font-weight:700; background:#ffedd5; color:#c2410c; padding:1px 5px; border-radius:4px;">DISC</span>';
                        cardHtml += '  </div>';
                        cardHtml += '  <input type="text" class="usp-card-mock-input" placeholder="Cardholder Name" disabled style="margin-bottom:6px;">';
                        cardHtml += '  <input type="text" class="usp-card-mock-input" placeholder="0000  0000  0000  0000 (Card Number)" disabled style="margin-bottom:6px; font-weight:600; letter-spacing:0.5px;">';
                        cardHtml += '  <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">';
                        cardHtml += '    <input type="text" class="usp-card-mock-input" placeholder="MM / YY" disabled style="margin:0;">';
                        cardHtml += '    <input type="password" class="usp-card-mock-input" placeholder="CVC (3 digits)" disabled style="margin:0;">';
                        cardHtml += '  </div>';
                        cardHtml += '</div>';
                    } else {
                        cardHtml += '<input type="text" class="usp-card-mock-input" placeholder="' + (field.placeholder || 'Enter ' + (field.label || 'value') + '...') + '" disabled />';
                    }
                }

                if (field.help_text && field.type !== 'file') {
                    cardHtml += '<div class="usp-card-mock-help">' + field.help_text + '</div>';
                }

                cardHtml += '</div>';
                $list.append(cardHtml);
            });
        },

        renderInspector: function() {
            var $container = $('#usp-field-settings-content');
            var field = this.fields.find(function(f) { return f.id === USPBuilder.selectedFieldId; });

            if (!field) {
                $container.html('<div class="usp-empty-inspector"><span class="dashicons dashicons-arrow-left-alt"></span><p>Select a field on the canvas to configure its properties & conditional logic.</p></div>');
                return;
            }

            var currentWidth = field.width || '100';

            var inspectorLabels = (window.uspBuilderData || {}).i18n || {};
            var position = this.fields.indexOf(field) + 1;
            var positionText = (inspectorLabels.field_position || 'Field %position% of %total%')
                .replace('%position%', position).replace('%total%', this.fields.length);
            var html = '<div class="usp-field-inspector-form">';
            html += '<div class="usp-inspector-context"><span class="dashicons dashicons-admin-generic" aria-hidden="true"></span><span>' + $('<div>').text(positionText).html() + '</span></div>';
            html += '<div class="usp-settings-form-group">';
            html += '  <label for="insp-label">Field Label</label>';
            html += '  <input type="text" id="insp-label" value="' + this.escapeAttribute((field.label || '')) + '">';
            html += '</div>';

            html += '<div class="usp-settings-form-group">';
            html += '  <label for="insp-name">Field Key (Identifier)</label>';
            html += '  <input type="text" id="insp-name" value="' + this.escapeAttribute((field.name || '')) + '">';
            html += '  <p class="description">Used in database submissions and conditional rules.</p>';
            html += '</div>';

            // Width Control (Segmented)
            if (field.type !== 'html' && field.type !== 'hidden') {
                html += '<div class="usp-settings-form-group">';
                html += '  <label>Field Width in Row</label>';
                html += '  <div class="usp-segmented-control">';
                html += '    <button type="button" class="usp-width-opt ' + (currentWidth === '100' ? 'active' : '') + '" data-val="100"><span class="dashicons dashicons-editor-justify"></span> 100% Full</button>';
                html += '    <button type="button" class="usp-width-opt ' + (currentWidth === '50' ? 'active' : '') + '" data-val="50"><span class="dashicons dashicons-columns"></span> 50% Half</button>';
                html += '    <button type="button" class="usp-width-opt ' + (currentWidth === '33' ? 'active' : '') + '" data-val="33"><span class="dashicons dashicons-grid-view"></span> 33% 1/3</button>';
                html += '  </div>';
                html += '</div>';
            }

            if (field.type !== 'address' && field.type !== 'html' && field.type !== 'radio' && field.type !== 'checkbox' && field.type !== 'range' && field.type !== 'file' && field.type !== 'hidden') {
                html += '<div class="usp-settings-form-group">';
                html += '  <label for="insp-placeholder">Placeholder Text</label>';
                html += '  <input type="text" id="insp-placeholder" value="' + this.escapeAttribute((field.placeholder || '')) + '">';
                html += '</div>';
            }

            if (field.type === 'textarea') {
                html += '<div class="usp-settings-form-group">';
                html += '  <label>Rows (Height)</label>';
                html += '  <input type="number" id="insp-rows" min="2" max="15" value="' + (field.rows || 4) + '">';
                html += '</div>';
            }

            if (field.type === 'number') {
                html += '<div style="display:flex; gap:10px;">';
                html += '  <div class="usp-settings-form-group" style="flex:1;">';
                html += '    <label>Min Value</label>';
                html += '    <input type="number" id="insp-min" value="' + (field.min || '') + '">';
                html += '  </div>';
                html += '  <div class="usp-settings-form-group" style="flex:1;">';
                html += '    <label>Max Value</label>';
                html += '    <input type="number" id="insp-max" value="' + (field.max || '') + '">';
                html += '  </div>';
                html += '</div>';
            }

            if (field.type === 'range') {
                html += '<div style="display:flex; gap:10px;">';
                html += '  <div class="usp-settings-form-group" style="flex:1;">';
                html += '    <label>Min</label>';
                html += '    <input type="number" id="insp-range-min" value="' + (field.min || 1) + '">';
                html += '  </div>';
                html += '  <div class="usp-settings-form-group" style="flex:1;">';
                html += '    <label>Max</label>';
                html += '    <input type="number" id="insp-range-max" value="' + (field.max || 10) + '">';
                html += '  </div>';
                html += '  <div class="usp-settings-form-group" style="flex:1;">';
                html += '    <label>Default</label>';
                html += '    <input type="number" id="insp-range-default" value="' + (field.default_val || 5) + '">';
                html += '  </div>';
                html += '</div>';
            }

            if (field.type === 'file') {
                html += '<div class="usp-settings-form-group">';
                html += '  <label>Allowed Extensions</label>';
                html += '  <input type="text" id="insp-allowed-ext" value="' + (field.allowed_ext || 'pdf,doc,docx,jpg,jpeg,png') + '">';
                html += '  <p class="description">Comma-separated: pdf, doc, docx, jpg, png</p>';
                html += '</div>';

                html += '<div class="usp-settings-form-group">';
                html += '  <label>Max File Size (MB)</label>';
                html += '  <input type="number" id="insp-max-size" min="1" max="50" value="' + (field.max_size || 5) + '">';
                html += '</div>';
            }

            if (field.type === 'rating') {
                html += '<div class="usp-settings-form-group">';
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
                html += '<div class="usp-settings-form-group">';
                html += '  <label>Agreement Statement Text</label>';
                html += '  <textarea id="insp-consent-text" rows="3">' + (field.consent_text || 'I have read and agree to the Terms of Service and Privacy Policy.') + '</textarea>';
                html += '</div>';
                html += '<div class="usp-settings-form-group">';
                html += '  <label>Terms Page Link (URL)</label>';
                html += '  <input type="url" id="insp-terms-url" placeholder="https://example.com/terms" value="' + (field.terms_url || '') + '">';
                html += '</div>';
                html += '<div class="usp-settings-form-group">';
                html += '  <label>Terms Link Text</label>';
                html += '  <input type="text" id="insp-terms-label" placeholder="Terms & Conditions" value="' + (field.terms_label || 'Terms & Conditions') + '">';
                html += '</div>';
            }

            if (field.type === 'hidden') {
                html += '<div class="usp-settings-form-group">';
                html += '  <label>Default Value</label>';
                html += '  <input type="text" id="insp-hidden-val" value="' + (field.default_val || '') + '">';
                html += '</div>';
            }

            if (field.type === 'html') {
                html += '<div class="usp-settings-form-group"><label>Content Type</label><select id="insp-content-mode">';
                [['section','Heading + note (existing)'],['heading','Heading'],['paragraph','Paragraph'],['bullets','Bulleted list'],['numbered','Numbered list']].forEach(function(option) {
                    html += '<option value="' + option[0] + '" ' + ((field.content_mode || 'section') === option[0] ? 'selected' : '') + '>' + option[1] + '</option>';
                });
                html += '</select><p>Use the label for headings. Enter one item per line for lists, or blank lines between paragraphs.</p></div>';
                html += '<div class="usp-settings-form-group">';
                html += '  <label>Heading Tag</label>';
                html += '  <select id="insp-tag">';
                ['h2', 'h3', 'h4', 'h5', 'h6'].forEach(function(tag) {
                    var sel = (field.tag === tag) ? 'selected' : '';
                    html += '<option value="' + tag + '" ' + sel + '>' + tag.toUpperCase() + '</option>';
                });
                html += '  </select>';
                html += '</div>';

                html += '<div class="usp-settings-form-group">';
                html += '  <label>Paragraph text / list items</label>';
                html += '  <textarea id="insp-content" rows="4">' + $('<div>').text(field.content || '').html() + '</textarea>';
                html += '</div>';
            }

            if (field.type === 'payment') {
                html += '<div style="display:flex; gap:10px;">';
                html += '  <div class="usp-settings-form-group" style="flex:1;">';
                html += '    <label>Charge Amount</label>';
                html += '    <input type="text" id="insp-payment-amount" value="' + (field.payment_amount || '50.00') + '">';
                html += '  </div>';
                html += '  <div class="usp-settings-form-group" style="flex:1;">';
                html += '    <label>Currency</label>';
                html += '    <select id="insp-payment-currency">';
                ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'PKR', 'INR', 'AED', 'SAR'].forEach(function(curr) {
                    var sel = (field.payment_currency === curr) ? 'selected' : '';
                    html += '<option value="' + curr + '" ' + sel + '>' + curr + '</option>';
                });
                html += '    </select>';
                html += '  </div>';
                html += '</div>';
            }

            if (field.type === 'address') {
                html += '<div class="usp-country-inspector-note"><p>Country, province, and city lists are included. Changing a country clears its province and city. Visitors can enter places missing from the list.</p></div>';
                html += '<div class="usp-settings-form-group"><label><input type="checkbox" id="insp-address-street" ' + (field.address_show_street !== false ? 'checked' : '') + '> Include street address and apartment</label></div>';
                html += '<div class="usp-settings-form-group"><label><input type="checkbox" id="insp-address-postal" ' + (field.address_show_postal !== false ? 'checked' : '') + '> Include postal / ZIP code</label></div>';
            }

            if (field.type === 'intl_tel') {
                html += '<div style="background:var(--usp-adm-slate-50); border:1px solid var(--usp-adm-slate-200); border-radius:8px; padding:12px; margin-bottom:14px;">';
                html += '  <div style="font-weight:600; font-size:12px; color:#1e293b; margin-bottom:4px;"><span class="dashicons dashicons-translation" style="font-size:16px; vertical-align:middle; color:var(--usp-adm-primary);"></span> International Phone Format Engine</div>';
                html += '  <p style="margin:0; font-size:11.5px; color:#64748b; line-height:1.4;">Enforces exact country national digit limits, blocks typing past limit, and dynamically applies national masks for 30+ countries (Pakistan, US, UK, UAE, Saudi Arabia, India, etc.).</p>';
                html += '</div>';
            }

            if (field.type === 'country') {
                var countryInfo = ((window.uspBuilderData || {}).i18n || {}).country_info ||
                    'The country list is included automatically. Change the label to ask for residence or nationality. Answers are saved as country names.';
                html += '<div class="usp-country-inspector-note"><span class="dashicons dashicons-admin-site-alt3" aria-hidden="true"></span><p>' + $('<div>').text(countryInfo).html() + '</p></div>';
            }

            // Options for choice fields (Select, Radio, Checkbox)
            if (field.type === 'select' || field.type === 'radio' || field.type === 'checkbox') {
                html += '<div class="usp-settings-form-group">';
                html += '  <label>Choices / Options</label>';
                html += '  <div class="usp-options-list" id="insp-options-list">';
                var options = field.options || ['Option 1', 'Option 2'];
                options.forEach(function(opt) {
                    html += '<div class="usp-option-row">';
                    html += '  <input type="text" class="insp-option-val" value="' + opt + '">';
                    html += '  <button type="button" class="usp-remove-option-btn" title="Remove Option">&times;</button>';
                    html += '</div>';
                });
                html += '  </div>';
                html += '  <button type="button" class="button button-small" id="usp-add-option-btn">+ Add Option</button>';
                html += '</div>';
            }

            if (field.type !== 'html' && field.type !== 'hidden') {
                html += '<div class="usp-settings-form-group">';
                html += '  <label for="insp-help">Help / Description Text</label>';
                html += '  <input type="text" id="insp-help" value="' + this.escapeAttribute((field.help_text || '')) + '">';
                html += '</div>';

                html += '<div class="usp-toggle-row" style="margin-top:8px;">';
                html += '  <div class="usp-toggle-info">';
                html += '    <span class="usp-toggle-title">Required Field</span>';
                html += '    <span class="usp-toggle-desc">Require user to fill before submit</span>';
                html += '  </div>';
                html += '  <label class="usp-toggle-switch">';
                html += '    <input type="checkbox" id="insp-required" ' + (field.required ? 'checked' : '') + '>';
                html += '    <span class="usp-toggle-slider"></span>';
                html += '  </label>';
                html += '</div>';
            }

            // --- CONDITIONAL LOGIC SECTION ---
            if (field.type !== 'hidden') {
                var isCondEnabled = !!field.enable_conditions;
                var action = field.condition_action || 'show';
                var match = field.condition_match || 'all';

                html += '<hr style="margin:14px 0 10px 0; border:0; border-top:1px solid var(--usp-adm-slate-200);">';
                html += '<div class="usp-toggle-row">';
                html += '  <div class="usp-toggle-info">';
                html += '    <span class="usp-toggle-title"><span class="dashicons dashicons-randomize" style="font-size:15px; vertical-align:middle; color:var(--usp-adm-primary); margin-right:4px;"></span> Conditional Logic</span>';
                html += '    <span class="usp-toggle-desc">Dynamically show or hide based on rules</span>';
                html += '  </div>';
                html += '  <label class="usp-toggle-switch">';
                html += '    <input type="checkbox" id="insp-enable-conditions" ' + (isCondEnabled ? 'checked' : '') + '>';
                html += '    <span class="usp-toggle-slider"></span>';
                html += '  </label>';
                html += '</div>';

                html += '<div id="usp-conditional-rules-wrap" style="' + (isCondEnabled ? '' : 'display:none;') + ' background:var(--usp-adm-slate-50); border:1px solid var(--usp-adm-slate-200); border-radius:8px; padding:12px; margin-top:8px;">';
                html += '  <div style="display:flex; gap:6px; align-items:center; margin-bottom:12px; font-size:12px; color:var(--usp-adm-slate-700);">';
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

                html += '  <div id="usp-condition-rules-list" style="display:flex; flex-direction:column; gap:8px;">';
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

                html += '  <button type="button" class="button button-small" id="usp-add-rule-btn" style="margin-top:10px; font-weight:600;">+ Add Rule</button>';
                html += '</div>';
            }

            html += '</div>';
            $container.html(html);
        },

        buildConditionRowHtml: function(otherFields, cond) {
            cond = cond || { field: '', operator: '==', value: '' };

            var html = '<div class="usp-cond-rule-row" style="display:flex; gap:6px; align-items:center; background:#ffffff; border:1px solid #cbd5e1; border-radius:6px; padding:6px 8px;">';

            // Target Field select
            html += '<select class="insp-cond-field" style="flex:1.2; font-size:11.5px; height:28px; padding:2px 4px; border-radius:4px;">';
            html += '<option value="">— Select Field —</option>';
            otherFields.forEach(function(f) {
                var sel = (cond.field === f.name) ? 'selected' : '';
                html += '<option value="' + f.name + '" ' + sel + '>' + (f.label || f.name) + '</option>';
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
            html += '<input type="text" class="insp-cond-val" value="' + (cond.value || '') + '" placeholder="Value" style="flex:1; font-size:11.5px; height:28px; padding:2px 6px; border-radius:4px;' + (hideVal ? ' display:none;' : '') + '">';

            // Remove button
            html += '<button type="button" class="usp-remove-rule-btn" style="background:transparent; border:none; color:#ef4444; font-size:16px; cursor:pointer; line-height:1; padding:2px;" title="Remove Rule">&times;</button>';
            html += '</div>';

            return html;
        },

        addConditionRuleToInspector: function() {
            var field = this.fields.find(function(f) { return f.id === USPBuilder.selectedFieldId; });
            if (!field) return;

            var otherFields = this.fields.filter(function(f) { return f.id !== field.id && f.type !== 'html'; });
            var defaultField = (otherFields.length > 0) ? otherFields[0].name : '';
            var rowHtml = this.buildConditionRowHtml(otherFields, { field: defaultField, operator: '==', value: '' });

            $('#usp-condition-rules-list').append(rowHtml);
        },

        addOptionToSelectedField: function() {
            var $list = $('#insp-options-list');
            var nextNum = $list.find('.usp-option-row').length + 1;
            var rowHtml = '<div class="usp-option-row">' +
                '<input type="text" class="insp-option-val" value="Option ' + nextNum + '">' +
                '<button type="button" class="usp-remove-option-btn" title="Remove Option">&times;</button>' +
                '</div>';
            $list.append(rowHtml);
            this.updateFieldFromInspector();
        },

        updateFieldFromInspector: function() {
            var field = this.fields.find(function(f) { return f.id === USPBuilder.selectedFieldId; });
            if (!field) return;

            if (field.type === 'address') {
                field.address_show_street = $('#insp-address-street').is(':checked');
                field.address_show_postal = $('#insp-address-postal').is(':checked');
            }
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

            if (field.type === 'payment') {
                field.payment_amount = $('#insp-payment-amount').val() || '50.00';
                field.payment_currency = $('#insp-payment-currency').val() || 'USD';
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
                $('#usp-condition-rules-list .usp-cond-rule-row').each(function() {
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
            var $card = $('.usp-canvas-field-card[data-id="' + field.id + '"]');
            $card.find('.usp-card-label').html((field.label || 'Untitled Field') + (field.required ? '<span class="required-star">*</span>' : ''));
            $card.find('.usp-card-mock-input').attr('placeholder', field.placeholder);
            if (field.type === 'country') {
                $card.find('select option').text(field.placeholder || 'Select a country');
            }

            if (field.type === 'consent') {
                $card.find('.usp-mock-consent-wrap span').first().text(field.consent_text);
                if (field.terms_url) {
                    $card.find('.usp-mock-consent-wrap a').attr('href', field.terms_url).text(field.terms_label);
                }
            }

            // Update conditional badge on canvas card
            var $handle = $card.find('.usp-card-handle');
            $handle.find('.usp-card-cond-badge').remove();
            if (field.enable_conditions && Array.isArray(field.conditions) && field.conditions.length > 0) {
                $handle.append('<span class="usp-card-cond-badge" title="Has conditional logic rules" style="background:#eff6ff; color:#2563eb; font-size:10.5px; padding:2px 7px; border-radius:10px; font-weight:700; border:1px solid #bfdbfe; margin-left:6px; display:inline-flex; align-items:center; gap:3px;"><span class="dashicons dashicons-randomize" style="font-size:12px; width:12px; height:12px; vertical-align:middle;"></span> If/Then</span>');
            }

            this.renderOutline();

            this.scheduleHistorySnapshot('Edit ' + (field.label || 'Field'));
        },

        applyThemeLive: function() {
            var preset = $('#usp-theme-preset-val').val() || 'default';
            var primaryColor = $('#usp-theme-primary-color').val() || '#4f46e5';
            var btnColor = $('#usp-theme-btn-color').val() || '#ffffff';
            var bgColor = $('#usp-theme-bg-color').val() || '#ffffff';
            var radius = $('#usp-theme-radius-val').val() || 'rounded';
            var align = $('#usp-theme-button-align-val').val() || 'left';
            var shadow = $('#usp-theme-shadow-val').val() || 'medium';
            var customCss = $('#usp-theme-custom-css').val() || '';

            var $canvas = $('#usp-canvas-container');
            if (!$canvas.length) return;

            // 1. Remove theme preset classes and add the current one
            $canvas.removeClass('usp-theme-minimal usp-theme-dark usp-theme-emerald usp-theme-sunset usp-theme-violet');
            if (preset !== 'default') {
                $canvas.addClass('usp-theme-' + preset);
            }

            // 2. Primary / accent color & button color
            $canvas.get(0).style.setProperty('--usp-primary', primaryColor);
            $canvas.get(0).style.setProperty('--usp-btn-color', btnColor);

            var $submitBtn = $('#usp-canvas-submit-preview');
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
            $canvas.removeClass('usp-radius-sharp usp-radius-slight usp-radius-rounded usp-radius-pill');
            $canvas.addClass('usp-radius-' + radius);

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
            $('.usp-canvas-field-card').css('border-radius', radius === 'sharp' ? '0px' : (radius === 'pill' ? '12px' : (radius === 'slight' ? '4px' : '8px')));

            // 4. Button alignment
            $canvas.removeClass('usp-btn-align-left usp-btn-align-center usp-btn-align-right usp-btn-align-full');
            $canvas.addClass('usp-btn-align-' + align);

            // 5. Box shadow
            $canvas.removeClass('usp-shadow-none usp-shadow-subtle usp-shadow-medium usp-shadow-elevated usp-shadow-outline');
            $canvas.addClass('usp-shadow-' + shadow);

            // 6. Custom CSS live injection
            var $styleTag = $('#usp-live-custom-css');
            if (!$styleTag.length) {
                $styleTag = $('<style id="usp-live-custom-css"></style>').appendTo('head');
            }
            if (customCss.trim()) {
                var scopedCss = customCss
                    .replace(/\.usp-form-container/g, '#usp-canvas-container')
                    .replace(/\.usp-input/g, '#usp-canvas-container .usp-card-mock-input')
                    .replace(/\.usp-btn-submit/g, '#usp-canvas-submit-preview');
                $styleTag.text(scopedCss);
            } else {
                $styleTag.text('');
            }
        },

        collectSettings: function() {
            return {
                submit_button_text: $('#usp-setting-btn-text').val() || 'Submit',
                success_message: $('#usp-setting-success-msg').val() || 'Thank you! Your submission has been received.',
                redirect_url: $('#usp-setting-redirect-url').val() || '',
                redirect_pass_vars: $('#usp-setting-redirect-pass-vars').is(':checked'),
                send_email: $('#usp-setting-send-email').is(':checked'),
                notification_email: $('#usp-setting-admin-email').val() || '',
                email_subject: $('#usp-setting-email-subject').val() || '',
                send_user_auto_reply: $('#usp-setting-user-autoreply').is(':checked'),
                auto_reply_subject: $('#usp-setting-autoreply-subj').val() || '',
                auto_reply_message: $('#usp-setting-autoreply-msg').val() || '',

                // Theme & Styling Options
                theme_preset: $('#usp-theme-preset-val').val() || 'default',
                primary_color: $('#usp-theme-primary-color').val() || '#4f46e5',
                btn_text_color: $('#usp-theme-btn-color').val() || '#ffffff',
                container_bg: $('#usp-theme-bg-color').val() || '#ffffff',
                border_radius: $('#usp-theme-radius-val').val() || 'rounded',
                button_align: $('#usp-theme-button-align-val').val() || 'left',
                shadow_style: $('#usp-theme-shadow-val').val() || 'medium',
                custom_css: $('#usp-theme-custom-css').val() || '',

                // Scheduling & Restrictions
                limit_submissions: $('#usp-setting-limit-submissions').is(':checked'),
                max_entries: parseInt($('#usp-setting-max-entries').val(), 10) || 0,
                close_after_date: $('#usp-setting-close-date-toggle').is(':checked'),
                close_date: $('#usp-setting-close-date').val() || '',
                closed_message: $('#usp-setting-closed-msg').val() || '',
                require_login: $('#usp-setting-require-login').is(':checked'),
                login_message: $('#usp-setting-login-msg').val() || '',

                // Webhook & API Integrations
                enable_webhook: $('#usp-setting-enable-webhook').is(':checked'),
                webhook_url: ($('#usp-setting-webhook-url').val() || '').trim(),
                webhook_secret: ($('#usp-setting-webhook-secret').val() || '').trim()
            };
        },

        getCurrentState: function() {
            return JSON.stringify({
                title: $('#usp-form-title-input').val().trim() || 'Untitled Form',
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
            var labels = (window.uspBuilderData || {}).i18n || {};
            var fallback = { saving: 'Saving…', unsaved: 'Unsaved changes',
                saved: 'All changes saved', error: 'Save failed — try again' };
            $('#usp-save-status').attr('data-state', state)
                .text(labels['status_' + state] || fallback[state]);
        },

        exportFormJson: function() {
            var formTitle = $('#usp-form-title-input').val().trim() || 'Untitled Form';
            var formId = (window.uspBuilderData && window.uspBuilderData.form) ? window.uspBuilderData.form.id : 0;

            var settings = this.collectSettings();

            var exportPayload = {
                usp_version: '1.0.3',
                exported_at: new Date().toISOString(),
                generator: 'USP Form Builder Pro',
                title: formTitle,
                fields_data: this.fields,
                settings: settings
            };

            var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(exportPayload, null, 2));
            var downloadAnchor = document.createElement('a');
            downloadAnchor.setAttribute("href", dataStr);
            downloadAnchor.setAttribute("download", "usp-form-" + (formId ? formId : 'draft') + ".json");
            document.body.appendChild(downloadAnchor);
            downloadAnchor.click();
            downloadAnchor.remove();

            this.showToast('Form definition exported to JSON!', 'success');
        },

        saveForm: function() {
            if (this.isSaving) return;
            var self = this;
            var $btn = $('#usp-save-form-btn');
            var $spinner = $btn.find('.spinner');

            var formTitle = $('#usp-form-title-input').val().trim() || 'Untitled Form';
            var formId = (window.uspBuilderData && window.uspBuilderData.form) ? window.uspBuilderData.form.id : 0;

            var settings = this.collectSettings();

            var submittedState = this.getCurrentState();
            this.isSaving = true;
            this.updateSaveStatus();
            $btn.prop('disabled', true);
            $spinner.addClass('is-active');

            $.post(window.uspBuilderData.ajax_url, {
                action: 'usp_save_form',
                nonce: window.uspBuilderData.nonce,
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
                        if (!window.uspBuilderData.form) window.uspBuilderData.form = {};
                        window.uspBuilderData.form.id = res.data.form_id;
                        var newShortcode = '[usp_form id="' + res.data.form_id + '"]';
                        $('.usp-shortcode-tag').show().attr('data-shortcode', newShortcode).find('code').text(newShortcode);
                        $('#usp-embed-shortcode-input').val(newShortcode);
                    }
                    if (!formId && res.data.redirect_url) {
                        window.history.replaceState({}, '', res.data.redirect_url);
                        if (!window.uspBuilderData.form) window.uspBuilderData.form = {};
                        window.uspBuilderData.form.id = res.data.form_id;
                    }
                } else {
                    self.updateSaveStatus('error');
                    USPModal.alert({
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
                USPModal.alert({
                    title: 'Network Error',
                    message: 'Network error occurred while saving form. Please check your connection.',
                    type: 'error'
                });
            });
        },

        openLivePreview: function() {
            var self = this;
            var formTitle = $('#usp-form-title-input').val().trim() || 'Custom Form';
            var submitBtnText = $('#usp-setting-btn-text').val() || 'Submit';
            var primaryColor = $('#usp-theme-primary-color').val() || '#4f46e5';
            var btnColor = $('#usp-theme-btn-color').val() || '#ffffff';
            var radius = $('#usp-theme-radius-val').val() || 'rounded';
            var align = $('#usp-theme-button-align-val').val() || 'left';

            var radiusMap = {
                'sharp': '0px',
                'slight': '6px',
                'rounded': '10px',
                'pill': '24px'
            };
            var btnRadius = radiusMap[radius] || '8px';

            var html = '';
            var previewLabels = (window.uspBuilderData || {}).i18n || {};
            html += '<div class="usp-preview-notice"><div><span class="usp-preview-notice-title">' + $('<div>').text(previewLabels.preview_title || 'Form preview').html() + '</span>';
            html += '<p>' + $('<div>').text(previewLabels.preview_description || 'Test your form before publishing. Preview submissions are not saved.').html() + '</p></div>';
            html += '<span class="usp-preview-field-count">' + this.fields.length + ' ' + $('<div>').text(previewLabels.preview_fields || 'fields').html() + '</span></div>';

            // Form container
            html += '<div class="usp-preview-form-box" style="--usp-primary:' + primaryColor + '; font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;">';
            html += '  <h2 style="font-size:22px; font-weight:800; color:#0f172a; margin:0 0 28px 0; line-height:1.3;">' + $('<div>').text(formTitle).html() + '</h2>';

            html += '  <form id="usp-live-simulation-form" onsubmit="event.preventDefault(); USPModal.toast(\'Form simulation validated successfully!\', \'success\');">';
            html += '    <div style="display:flex; flex-wrap:wrap; gap:24px 20px;">';

            this.fields.forEach(function(f) {
                var widthPct = f.width === '50' ? 'calc(50% - 10px)' : (f.width === '33' ? 'calc(33.333% - 14px)' : '100%');

                if (f.type === 'html') {
                    html += '<div style="width:100%">' + contentPreview(f) + '</div>';
                    return;
                }

                if (f.type === 'hidden') {
                    return;
                }

                html += '<div class="usp-preview-field-col" style="width:' + widthPct + '; box-sizing:border-box; display:flex; flex-direction:column; gap:6px;">';
                html += '  <label style="font-size:13.5px; font-weight:600; color:#1e293b; display:flex; align-items:center; gap:4px;">';
                html += '    <span>' + $('<div>').text(f.label || 'Untitled').html() + '</span>';
                if (f.required) {
                    html += '    <span style="color:#ef4444; font-weight:700;">*</span>';
                }
                html += '  </label>';

                var inputStyle = 'width:100%; box-sizing:border-box; padding:9px 12px; font-size:14px; border:1.5px solid #cbd5e1; border-radius:' + btnRadius + '; background:#ffffff; color:#0f172a; outline:none; transition:border 0.15s ease;';

                if (f.type === 'textarea') {
                    html += '  <textarea rows="' + (f.rows || 4) + '" placeholder="' + $('<div>').text(f.placeholder || '').html() + '" style="' + inputStyle + '"></textarea>';
                } else if (f.type === 'address') {
                    var addressId = 'usp_preview_address_' + self.generateId();
                    var template = (window.uspBuilderData || {}).address_template || '';
                    html += template.replace(/__USP_NAME__/g, addressId).replace(/__USP_ID__/g, addressId)
                        .replace('data-required="false"', 'data-required="' + Boolean(f.required) + '"')
                        .replace('data-show-street="true"', 'data-show-street="' + (f.address_show_street !== false) + '"')
                        .replace('data-show-postal="true"', 'data-show-postal="' + (f.address_show_postal !== false) + '"');
                } else if (f.type === 'country') {
                    var countryLabels = (window.uspBuilderData || {}).i18n || {};
                    var simId = 'usp_sim_country_' + Math.random().toString(36).slice(2);
                    html += '<div class="usp-country-control">';
                    html += '<select id="' + simId + '" aria-label="' + self.escapeAttribute(f.label || 'Country') + '" class="usp-country-select" style="' + inputStyle + '"' + (f.required ? ' required' : '') + '>';
                    html += '<option value="">' + $('<div>').text(f.placeholder || 'Select a country').html() + '</option>';
                    Object.values((window.uspBuilderData || {}).countries || {}).forEach(function(country) {
                        html += '<option value="' + self.escapeAttribute(country) + '">' + $('<div>').text(country).html() + '</option>';
                    });
                    html += '</select></div>';
                } else if (f.type === 'select') {
                    html += '  <select style="' + inputStyle + '">';
                    html += '    <option value="">' + (f.placeholder ? $('<div>').text(f.placeholder).html() : 'Select an option...') + '</option>';
                    var opts = f.options || ['Option 1', 'Option 2'];
                    opts.forEach(function(o) {
                        html += '    <option value="' + $('<div>').text(o).html() + '">' + $('<div>').text(o).html() + '</option>';
                    });
                    html += '  </select>';
                } else if (f.type === 'radio') {
                    html += '  <div style="display:flex; flex-direction:column; gap:8px; padding-top:4px;">';
                    var opts = f.options || ['Option 1', 'Option 2'];
                    opts.forEach(function(o) {
                        html += '    <label style="display:flex; align-items:center; gap:8px; font-size:13.5px; color:#334155; cursor:pointer;">';
                        html += '      <input type="radio" name="sim_' + f.name + '" value="' + $('<div>').text(o).html() + '" style="accent-color:' + primaryColor + ';">';
                        html += '      <span>' + $('<div>').text(o).html() + '</span>';
                        html += '    </label>';
                    });
                    html += '  </div>';
                } else if (f.type === 'checkbox') {
                    html += '  <div style="display:flex; flex-direction:column; gap:8px; padding-top:4px;">';
                    var opts = f.options || ['Option 1', 'Option 2'];
                    opts.forEach(function(o) {
                        html += '    <label style="display:flex; align-items:center; gap:8px; font-size:13.5px; color:#334155; cursor:pointer;">';
                        html += '      <input type="checkbox" name="sim_' + f.name + '[]" value="' + $('<div>').text(o).html() + '" style="accent-color:' + primaryColor + ';">';
                        html += '      <span>' + $('<div>').text(o).html() + '</span>';
                        html += '    </label>';
                    });
                    html += '  </div>';
                } else if (f.type === 'rating') {
                    var maxS = parseInt(f.max_stars, 10) || 5;
                    html += '  <div class="usp-sim-rating" data-max="' + maxS + '" style="display:flex; align-items:center; gap:6px; cursor:pointer; padding:4px 0;">';
                    for (var s = 1; s <= maxS; s++) {
                        html += '    <span class="dashicons dashicons-star-filled usp-sim-star" data-val="' + s + '" style="font-size:26px; width:26px; height:26px; color:#d1d5db; transition:color 0.15s ease;"></span>';
                    }
                    html += '    <span class="usp-sim-rating-score" style="margin-left:8px; font-size:13px; font-weight:700; color:#475569;"></span>';
                    html += '  </div>';
                } else if (f.type === 'consent') {
                    html += '  <div style="display:flex; align-items:flex-start; gap:10px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:' + btnRadius + '; padding:12px;">';
                    html += '    <input type="checkbox" style="margin-top:3px; accent-color:' + primaryColor + ';" required>';
                    html += '    <div style="font-size:13px; color:#334155; line-height:1.45;">';
                    html += '      <span>' + $('<div>').text(f.consent_text || 'I agree to the Terms of Service and Privacy Policy.').html() + '</span> ';
                    if (f.terms_url) {
                        html += '      <a href="' + f.terms_url + '" target="_blank" style="color:' + primaryColor + '; text-decoration:underline; font-weight:600;">' + $('<div>').text(f.terms_label || 'Terms & Conditions').html() + '</a>';
                    }
                    html += '    </div>';
                    html += '  </div>';
                } else if (f.type === 'range') {
                    var rMin = f.min || 1;
                    var rMax = f.max || 10;
                    var rDef = f.default_val || 5;
                    html += '  <div style="display:flex; flex-direction:column; gap:6px;">';
                    html += '    <div style="display:flex; justify-content:space-between; font-size:12px; color:#64748b;">';
                    html += '      <span>Min: ' + rMin + '</span><span class="usp-sim-range-val" style="font-weight:700; color:' + primaryColor + ';">' + rDef + '</span><span>Max: ' + rMax + '</span>';
                    html += '    </div>';
                    html += '    <input type="range" min="' + rMin + '" max="' + rMax + '" value="' + rDef + '" style="accent-color:' + primaryColor + ';" oninput="$(this).prev().find(\'.usp-sim-range-val\').text($(this).val())">';
                    html += '  </div>';
                } else if (f.type === 'file') {
                    html += '  <div style="border:2px dashed #cbd5e1; border-radius:' + btnRadius + '; padding:20px; text-align:center; background:#f8fafc; cursor:pointer;">';
                    html += '    <span class="dashicons dashicons-upload" style="font-size:28px; width:28px; height:28px; color:#64748b; margin-bottom:6px;"></span>';
                    html += '    <div style="font-size:13.5px; font-weight:600; color:#334155;">Click or drop file to upload</div>';
                    html += '    <div style="font-size:11.5px; color:#64748b; margin-top:2px;">Allowed: ' + (f.allowed_ext || 'PDF, DOC, PNG') + ' (Max ' + (f.max_size || 5) + 'MB)</div>';
                    html += '  </div>';
                } else {
                    var fType = (f.type === 'email' || f.type === 'number' || f.type === 'tel' || f.type === 'date' || f.type === 'time' || f.type === 'url') ? f.type : 'text';
                    html += '  <input type="' + fType + '" placeholder="' + $('<div>').text(f.placeholder || '').html() + '" style="' + inputStyle + '">';
                }

                if (f.help_text) {
                    html += '  <span style="font-size:12px; color:#64748b;">' + $('<div>').text(f.help_text).html() + '</span>';
                }
                html += '</div>';
            });

            html += '    </div>';

            // Submit button row
            var alignCss = align === 'center' ? 'text-align:center;' : (align === 'right' ? 'text-align:right;' : (align === 'full' ? 'display:block;' : 'text-align:left;'));
            html += '    <div style="margin-top:28px; ' + alignCss + '">';
            html += '      <button type="submit" style="background-color:' + primaryColor + '; color:' + btnColor + '; border:none; border-radius:' + btnRadius + '; padding:11px 26px; font-size:14.5px; font-weight:700; cursor:pointer; ' + (align === 'full' ? 'width:100%;' : '') + ' box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);">';
            html += $('<div>').text(submitBtnText).html();
            html += '      </button>';
            html += '    </div>';

            html += '  </form>';
            html += '</div>';

            $('#usp-live-preview-viewport').html(html);
            if (window.USPCountry) window.USPCountry.init(document.getElementById('usp-live-preview-viewport'));
            if (window.USPAddress) window.USPAddress.init(document.getElementById('usp-live-preview-viewport'));

            // Bind interactive star rating events inside simulator
            $('#usp-live-preview-viewport').find('.usp-sim-rating').each(function() {
                var $wrap = $(this);
                $wrap.find('.usp-sim-star').on('mouseenter', function() {
                    var val = $(this).data('val');
                    $wrap.find('.usp-sim-star').each(function() {
                        $(this).css('color', $(this).data('val') <= val ? '#f59e0b' : '#d1d5db');
                    });
                });
                $wrap.on('mouseleave', function() {
                    var current = $wrap.data('selected') || 0;
                    $wrap.find('.usp-sim-star').each(function() {
                        $(this).css('color', $(this).data('val') <= current ? '#f59e0b' : '#d1d5db');
                    });
                });
                $wrap.find('.usp-sim-star').on('click', function() {
                    var val = $(this).data('val');
                    $wrap.data('selected', val);
                    $wrap.find('.usp-sim-rating-score').text(val + ' / ' + $wrap.data('max') + ' Stars');
                    $wrap.find('.usp-sim-star').each(function() {
                        $(this).css('color', $(this).data('val') <= val ? '#f59e0b' : '#d1d5db');
                    });
                });
            });

            $('#usp-live-preview-modal').fadeIn(150);
        },

        showToast: function(message, type) {
            if (window.USPModal && USPModal.toast) {
                USPModal.toast(message, type);
                return;
            }
            var $box = $('#usp-toast-box');
            $box.removeClass('success error').addClass(type || 'success').text(message).fadeIn(200);
            setTimeout(function() {
                $box.fadeOut(300);
            }, 3000);
        }
    };

    $(document).ready(function() {
        USPBuilder.init();
    });

})(jQuery);
