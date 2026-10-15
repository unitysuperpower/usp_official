/**
 * Registers the Pro field types and templates with the TechSolution Form Studio builder.
 *
 * @package TechSolution_Form_Studio_Pro
 */
(function ($) {
    'use strict';

    var builder = window.TSFSBuilder;
    if (!builder || typeof builder.registerFieldType !== 'function') {
        return;
    }
    var data = window.tsfsProBuilderData || {};
    var esc = builder.escapeMarkup;

    function group(label, inner) {
        return '<div class="tsfs-settings-form-group"><label>' + esc(label) + '</label>' + inner + '</div>';
    }
    function mock(placeholder) {
        return '<input type="text" class="tsfs-card-mock-input" placeholder="' + esc(placeholder) + '" disabled>';
    }
    function previewInput(placeholder, ctx) {
        return '<input type="text" placeholder="' + esc(placeholder) + '" style="' + ctx.inputStyle + '">';
    }

    builder.registerFieldType('address', {
        label: 'Complete Address',
        hidePlaceholder: true,
        canvas: function (field) {
            var parts = (field.address_show_street !== false ? ['Street address'] : []).concat(['Country', 'Province / State', 'City']);
            if (field.address_show_postal !== false) parts.push('Postal / ZIP code');
            return '<div class="tsfs-mock-address-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">' + parts.map(mock).join('') + '</div>';
        },
        inspector: function (field) {
            return '<div class="tsfs-country-inspector-note"><p>Country, province and city lists are included. Visitors can type a place that is not listed.</p></div>' +
                '<div class="tsfs-settings-form-group"><label><input type="checkbox" id="insp-address-street" ' + (field.address_show_street !== false ? 'checked' : '') + '> Include street address</label></div>' +
                '<div class="tsfs-settings-form-group"><label><input type="checkbox" id="insp-address-postal" ' + (field.address_show_postal !== false ? 'checked' : '') + '> Include postal / ZIP code</label></div>';
        },
        readInspector: function (field) {
            field.address_show_street = $('#insp-address-street').is(':checked');
            field.address_show_postal = $('#insp-address-postal').is(':checked');
        },
        preview: function (field) {
            var id = 'tsfs_preview_address_' + Math.random().toString(36).slice(2);
            return String(data.address_template || '')
                .replace(/__TSFS_NAME__/g, id).replace(/__TSFS_ID__/g, id)
                .replace('data-show-street="true"', 'data-show-street="' + (field.address_show_street !== false) + '"')
                .replace('data-show-postal="true"', 'data-show-postal="' + (field.address_show_postal !== false) + '"');
        }
    });

    builder.registerFieldType('admission', {
        label: 'Admission Application',
        hidePlaceholder: true,
        defaults: function (field) {
            field.required = true;
        },
        canvas: function () {
            return '<div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">' +
                ['Applicant full name', "Father's / guardian's name", 'CNIC / Form-B', 'Program', 'Previous education', 'Marks / percentage'].map(mock).join('') + '</div>';
        },
        inspector: function (field) {
            var programs = (field.programs || []).join('\n');
            return group('Programs (one per line)', '<textarea id="insp-adm-programs" rows="5">' + esc(programs) + '</textarea>');
        },
        readInspector: function (field) {
            field.programs = String($('#insp-adm-programs').val() || '').split(/\r?\n/).map(function (p) { return p.trim(); }).filter(Boolean);
        },
        preview: function (field, ctx) {
            return '<div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">' +
                ['Applicant full name', "Father's / guardian's name", '12345-1234567-1', 'Previous education'].map(function (p) { return previewInput(p, ctx); }).join('') + '</div>';
        }
    });

    builder.registerFieldType('intl_tel', {
        label: 'International Phone',
        hidePlaceholder: true,
        defaults: function (field) {
            field.required = true;
        },
        canvas: function () {
            return '<div style="display:flex; gap:8px;"><select class="tsfs-card-mock-input" disabled style="max-width:130px;"><option>🌐 +</option></select>' + mock('Phone number') + '</div>';
        },
        inspector: function (field) {
            var options = (data.phone_countries || []).map(function (c) {
                return '<option value="' + esc(c.code) + '"' + (c.code === (field.default_country || 'PK') ? ' selected' : '') + '>' + esc(c.label) + '</option>';
            }).join('');
            return group('Default country', '<select id="insp-tel-country">' + options + '</select>') +
                '<p class="description">Digit limits and the display format follow the visitor\'s chosen country.</p>';
        },
        readInspector: function (field) {
            field.default_country = $('#insp-tel-country').val() || 'PK';
        },
        preview: function (field, ctx) {
            return '<div style="display:flex; gap:8px;"><select style="' + ctx.inputStyle + ' max-width:120px;"><option>+</option></select>' + previewInput('Phone number', ctx) + '</div>';
        }
    });

    builder.registerFieldType('payment', {
        label: 'Payment Details',
        hidePlaceholder: true,
        defaults: function (field) {
            field.required = true;
        },
        canvas: function (field) {
            return '<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px;">' +
                '<strong>' + esc((field.payment_currency || 'USD') + ' ' + (field.payment_amount || '0.00')) + '</strong>' +
                '<p class="description" style="margin:4px 0 8px;">Collects card details for your records. No payment is processed and the full card number is never sent.</p>' +
                mock('Cardholder name') + mock('0000 0000 0000 0000') + '<div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">' + mock('MM / YY') + mock('CVC') + '</div></div>';
        },
        inspector: function (field) {
            var currencies = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'PKR', 'INR', 'AED', 'SAR'];
            return '<div style="display:flex; gap:10px;">' +
                '<div class="tsfs-settings-form-group" style="flex:1;"><label for="insp-payment-amount">Amount</label><input type="text" id="insp-payment-amount" value="' + esc(field.payment_amount || '0.00') + '"></div>' +
                '<div class="tsfs-settings-form-group" style="flex:1;"><label for="insp-payment-currency">Currency</label><select id="insp-payment-currency">' +
                currencies.map(function (c) { return '<option value="' + c + '"' + (c === field.payment_currency ? ' selected' : '') + '>' + c + '</option>'; }).join('') +
                '</select></div></div>';
        },
        readInspector: function (field) {
            field.payment_amount = $('#insp-payment-amount').val() || '0.00';
            field.payment_currency = $('#insp-payment-currency').val() || 'USD';
        },
        preview: function (field, ctx) {
            return previewInput('Cardholder name', ctx) + previewInput('0000 0000 0000 0000', ctx);
        }
    });

    builder.registerFieldType('signature', {
        label: 'Digital Signature',
        hidePlaceholder: true,
        canvas: function (field) {
            return '<div style="height:' + (parseInt(field.canvas_height, 10) || 160) + 'px; border:2px dashed #cbd5e1; border-radius:8px; display:flex; align-items:flex-end; justify-content:center; padding-bottom:16px; color:#94a3b8; font-size:12px;">Sign above the line</div>';
        },
        inspector: function (field) {
            return '<div style="display:flex; gap:10px;">' +
                '<div class="tsfs-settings-form-group" style="flex:1;"><label for="insp-sig-height">Pad height (px)</label><input type="number" min="100" max="400" id="insp-sig-height" value="' + esc(field.canvas_height || 160) + '"></div>' +
                '<div class="tsfs-settings-form-group" style="flex:1;"><label for="insp-sig-color">Pen color</label><input type="color" id="insp-sig-color" value="' + esc(field.pen_color || '#0f172a') + '"></div></div>';
        },
        readInspector: function (field) {
            field.canvas_height = $('#insp-sig-height').val() || 160;
            field.pen_color = $('#insp-sig-color').val() || '#0f172a';
        },
        preview: function (field) {
            return '<div style="height:' + (parseInt(field.canvas_height, 10) || 160) + 'px; border:2px dashed #cbd5e1; border-radius:8px;"></div>';
        }
    });

    builder.registerFieldType('calculation', {
        label: 'Calculation',
        hidePlaceholder: true,
        canvas: function (field) {
            return '<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px; font-weight:700;">∑ ' + esc((field.calc_prefix || '') + '0.00' + (field.calc_suffix ? ' ' + field.calc_suffix : '')) +
                '<div class="description" style="font-weight:400; margin-top:4px;"><code>' + esc(field.formula || 'No formula yet') + '</code></div></div>';
        },
        inspector: function (field) {
            return group('Formula', '<input type="text" id="insp-calc-formula" value="' + esc(field.formula || '') + '" placeholder="[quantity] * [price]">') +
                '<p class="description">Use field keys in square brackets with + − × ÷ and parentheses, for example <code>([adults] * 50) + ([children] * 25)</code>. Totals are recalculated on the server.</p>' +
                '<div style="display:flex; gap:10px;">' +
                '<div class="tsfs-settings-form-group" style="flex:1;"><label for="insp-calc-prefix">Prefix</label><input type="text" id="insp-calc-prefix" value="' + esc(field.calc_prefix || '') + '"></div>' +
                '<div class="tsfs-settings-form-group" style="flex:1;"><label for="insp-calc-suffix">Suffix</label><input type="text" id="insp-calc-suffix" value="' + esc(field.calc_suffix || '') + '"></div>' +
                '<div class="tsfs-settings-form-group" style="flex:1;"><label for="insp-calc-decimals">Decimals</label><input type="number" min="0" max="6" id="insp-calc-decimals" value="' + esc(field.decimals || 2) + '"></div></div>';
        },
        readInspector: function (field) {
            field.formula = $('#insp-calc-formula').val() || '';
            field.calc_prefix = $('#insp-calc-prefix').val() || '';
            field.calc_suffix = $('#insp-calc-suffix').val() || '';
            field.decimals = $('#insp-calc-decimals').val() || 2;
        },
        preview: function (field, ctx) {
            return '<div style="' + ctx.inputStyle + ' font-weight:700;">∑ ' + esc((field.calc_prefix || '') + '0.00') + '</div>';
        }
    });

    builder.templates.tsfs_pro_admission = {
        title: 'University Admission Application',
        settings: {
            submit_button_text: 'Submit Application',
            success_message: 'Thank you for applying! We have received your application.',
            send_email: true,
            email_subject: 'New admission application: {user_name} [#{entry_id}]'
        },
        fields: [
            { id: 'tpl_a0', type: 'html', label: 'Admissions', tag: 'h3', content_mode: 'section', content: 'Complete the application below.', width: '100' },
            { id: 'tpl_a1', type: 'admission', label: 'Applicant Profile', name: 'applicant', required: true, width: '100', programs: ['BS Computer Science', 'BS Software Engineering', 'BBA / Business Administration', 'BS Data Science & AI', 'MS / MPhil Program'] },
            { id: 'tpl_a2', type: 'email', label: 'Email Address', name: 'applicant_email', required: true, width: '50' },
            { id: 'tpl_a3', type: 'intl_tel', label: 'Mobile Number', name: 'applicant_phone', required: true, width: '50', default_country: 'PK' },
            { id: 'tpl_a4', type: 'address', label: 'Home Address', name: 'home_address', required: true, width: '100', address_show_street: true, address_show_postal: true },
            { id: 'tpl_a5', type: 'file', label: 'Transcripts', name: 'transcripts', allowed_ext: 'pdf,jpg,jpeg,png', max_size: '10', required: true, width: '100' },
            { id: 'tpl_a6', type: 'signature', label: 'Applicant Signature', name: 'applicant_signature', required: true, width: '100', canvas_height: 160, pen_color: '#0f172a' }
        ]
    };

    builder.templates.tsfs_pro_order = {
        title: 'Order Form with Total',
        settings: {
            submit_button_text: 'Place Order',
            success_message: 'Thank you! Your order has been received.',
            send_email: true,
            email_subject: 'New order from {user_name} [#{entry_id}]'
        },
        fields: [
            { id: 'tpl_o1', type: 'text', label: 'Full Name', name: 'your_name', required: true, width: '50' },
            { id: 'tpl_o2', type: 'email', label: 'Email Address', name: 'your_email', required: true, width: '50' },
            { id: 'tpl_o3', type: 'select', label: 'Package', name: 'package_price', options: ['25', '50', '100'], required: true, width: '50', help_text: 'Price per unit' },
            { id: 'tpl_o4', type: 'number', label: 'Quantity', name: 'quantity', min: '1', max: '100', required: true, width: '50' },
            { id: 'tpl_o5', type: 'calculation', label: 'Order Total', name: 'order_total', formula: '[package_price] * [quantity]', calc_prefix: '$', calc_suffix: '', decimals: 2, width: '100' },
            { id: 'tpl_o6', type: 'address', label: 'Delivery Address', name: 'delivery_address', required: true, width: '100', address_show_street: true, address_show_postal: true },
            { id: 'tpl_o7', type: 'signature', label: 'Signature', name: 'signature', required: false, width: '100', canvas_height: 140, pen_color: '#0f172a' }
        ]
    };

    // Initialise address previews inside the builder's live preview.
    $(document).on('tsfs:preview-rendered', function (e, root) {
        if (window.TSFSAddress && root) window.TSFSAddress.init(root);
    });
})(jQuery);
