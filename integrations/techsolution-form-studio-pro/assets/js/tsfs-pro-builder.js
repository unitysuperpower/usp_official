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


    /* ------------------------------------------------------------------ */
    /* Rich Content block (WordPress editor in the field settings panel)    */
    /* ------------------------------------------------------------------ */

    var EDITOR_ID = 'tsfs-rich-editor';
    var richSyncTimer = null;

    // Keep only formatting markup in previews; the server filters saved content again with wp_kses.
    function cleanHtml(html) {
        var tpl = document.createElement('template');
        tpl.innerHTML = String(html || '');
        tpl.content.querySelectorAll('script,style,iframe,object,embed,form,input,textarea,select,button,link,meta,base,svg,math').forEach(function (n) { n.remove(); });
        tpl.content.querySelectorAll('*').forEach(function (n) {
            Array.prototype.slice.call(n.attributes).forEach(function (a) {
                var name = a.name.toLowerCase();
                if (name.indexOf('on') === 0 || ((name === 'href' || name === 'src') && /^\s*(javascript|data|vbscript):/i.test(a.value))) n.removeAttribute(a.name);
            });
        });
        return tpl.innerHTML;
    }

    function richStyle(field) {
        var parts = [
            '--tsfs-rich-font-size:' + (parseFloat(field.font_size) || 16) + 'px',
            '--tsfs-rich-line-height:' + (parseFloat(field.line_height) || 1.6),
            '--tsfs-rich-padding:' + (parseInt(field.padding, 10) || 0) + 'px',
            '--tsfs-rich-radius:' + (parseInt(field.radius, 10) || 0) + 'px',
            '--tsfs-rich-ul:' + (field.ul_style || 'disc'),
            '--tsfs-rich-ol:' + (field.ol_style || 'decimal')
        ];
        if (field.text_color) parts.push('--tsfs-rich-color:' + field.text_color);
        if (field.bg_color) parts.push('--tsfs-rich-bg:' + field.bg_color);
        if (field.border_color) parts.push('--tsfs-rich-border:' + field.border_color);
        if (field.marker_color) parts.push('--tsfs-rich-marker:' + field.marker_color);
        return esc(parts.join(';'));
    }

    function richBlock(field) {
        return '<div class="tsfs-rich-content tsfs-rich-ul-' + esc(field.ul_style || 'disc') + '" style="' + richStyle(field) + '">' + cleanHtml(field.content) + '</div>';
    }

    /* Pro editor tools: icon lists, callout boxes and tables. Each edits the editor DOM, then records an undo step. */
    var LIST_CLASSES = ['tsfs-list-check', 'tsfs-list-check-circle', 'tsfs-list-cross', 'tsfs-list-arrow', 'tsfs-list-star', 'tsfs-list-steps'];
    var CALLOUTS = [['info', 'Information'], ['success', 'Success / tip'], ['warning', 'Warning'], ['danger', 'Important'], ['note', 'Note']];

    function editorChanged(ed) {
        ed.undoManager.add();
        ed.nodeChanged();
        syncRich();
    }

    function applyListStyle(ed, cls) {
        var list = ed.dom.getParent(ed.selection.getNode(), 'ul,ol');
        if (cls) {
            var tag = cls === 'tsfs-list-steps' ? 'OL' : 'UL';
            if (!list || list.nodeName !== tag) {
                ed.execCommand(tag === 'OL' ? 'InsertOrderedList' : 'InsertUnorderedList');
                list = ed.dom.getParent(ed.selection.getNode(), 'ul,ol');
            }
        }
        if (!list) return;
        LIST_CLASSES.forEach(function (c) { ed.dom.removeClass(list, c); });
        if (cls) ed.dom.addClass(list, cls);
        if (!list.className) list.removeAttribute('class');
        editorChanged(ed);
    }

    function applyCallout(ed, type) {
        var box = ed.dom.getParent(ed.selection.getNode(), 'div.tsfs-callout');
        if (box) {
            if (type) box.className = 'tsfs-callout tsfs-callout-' + type;
            else ed.dom.remove(box, true);
        } else if (type) {
            var body = ed.getBody(), tops = [];
            ed.selection.getSelectedBlocks().forEach(function (block) {
                while (block.parentNode && block.parentNode !== body) block = block.parentNode;
                if (block.parentNode === body && tops.indexOf(block) === -1) tops.push(block);
            });
            if (!tops.length) return;
            var div = ed.dom.create('div', { 'class': 'tsfs-callout tsfs-callout-' + type });
            body.insertBefore(div, tops[0]);
            tops.forEach(function (block) { div.appendChild(block); });
        }
        editorChanged(ed);
    }

    function repeat(str, n) { return new Array(n + 1).join(str); }

    function insertTable(ed, rows, cols, header) {
        rows = Math.max(1, Math.min(30, parseInt(rows, 10) || 3));
        cols = Math.max(1, Math.min(8, parseInt(cols, 10) || 3));
        ed.insertContent('<table class="tsfs-rich-table">' +
            (header ? '<thead><tr>' + repeat('<th>Heading</th>', cols) + '</tr></thead>' : '') +
            '<tbody>' + repeat('<tr>' + repeat('<td>&nbsp;</td>', cols) + '</tr>', rows) + '</tbody></table><p>&nbsp;</p>');
        editorChanged(ed);
    }

    function editTable(ed, action) {
        // A caret at the start of a new cell can report the row as the selected node.
        var start = ed.selection.getStart();
        var cell = ed.dom.getParent(start, 'td,th') || (start && start.nodeName === 'TR' ? start.cells[0] : null);
        var table = cell && ed.dom.getParent(cell, 'table');
        if (!table) {
            ed.windowManager.alert('Place the cursor inside a table cell first.');
            return;
        }
        var row = cell.parentNode, index = cell.cellIndex, rows = Array.prototype.slice.call(table.rows);
        if (action === 'row-add') {
            var bodyRow = row.parentNode.nodeName === 'THEAD' ? (table.tBodies[0] && table.tBodies[0].rows[0]) : row;
            var newRow = ed.getDoc().createElement('tr');
            for (var i = 0; i < row.cells.length; i++) {
                var td = ed.getDoc().createElement('td');
                td.innerHTML = '&nbsp;';
                newRow.appendChild(td);
            }
            if (row.parentNode.nodeName === 'THEAD') {
                var tbody = table.tBodies[0] || table.appendChild(ed.getDoc().createElement('tbody'));
                tbody.insertBefore(newRow, bodyRow || null);
            } else {
                row.parentNode.insertBefore(newRow, row.nextSibling);
            }
        } else if (action === 'col-add') {
            rows.forEach(function (r) {
                var ref = r.cells[index];
                var c = ed.getDoc().createElement(ref && ref.nodeName === 'TH' ? 'th' : 'td');
                c.innerHTML = ref && ref.nodeName === 'TH' ? 'Heading' : '&nbsp;';
                r.insertBefore(c, ref ? ref.nextSibling : null);
            });
        } else if (action === 'row-delete') {
            if (rows.length <= 1) ed.dom.remove(table);
            else ed.dom.remove(row);
        } else if (action === 'col-delete') {
            if (row.cells.length <= 1) ed.dom.remove(table);
            else rows.forEach(function (r) { if (r.cells[index]) r.removeChild(r.cells[index]); });
        } else if (action === 'table-delete') {
            ed.dom.remove(table);
        }
        editorChanged(ed);
    }

    function addProTools(ed) {
        ed.addButton('tsfs_liststyle', {
            type: 'menubutton',
            text: 'List style',
            tooltip: 'Checklist, icon and step lists',
            menu: [
                { text: '• Standard bullets', onclick: function () { applyListStyle(ed, ''); } },
                { text: '✓ Checkmarks', onclick: function () { applyListStyle(ed, 'tsfs-list-check'); } },
                { text: '✔ Check circles', onclick: function () { applyListStyle(ed, 'tsfs-list-check-circle'); } },
                { text: '✗ Crosses', onclick: function () { applyListStyle(ed, 'tsfs-list-cross'); } },
                { text: '→ Arrows', onclick: function () { applyListStyle(ed, 'tsfs-list-arrow'); } },
                { text: '★ Stars', onclick: function () { applyListStyle(ed, 'tsfs-list-star'); } },
                { text: '① Numbered steps', onclick: function () { applyListStyle(ed, 'tsfs-list-steps'); } }
            ]
        });
        ed.addButton('tsfs_callout', {
            type: 'menubutton',
            text: 'Box',
            tooltip: 'Put the selected paragraphs in a highlighted box',
            menu: CALLOUTS.map(function (c) {
                return { text: c[1], onclick: function () { applyCallout(ed, c[0]); } };
            }).concat([{ text: '-' }, { text: 'Remove box', onclick: function () { applyCallout(ed, ''); } }])
        });
        ed.addButton('tsfs_table', {
            type: 'menubutton',
            text: 'Table',
            tooltip: 'Insert or edit a table',
            menu: [
                { text: 'Insert table…', onclick: function () {
                    ed.windowManager.open({
                        title: 'Insert table',
                        body: [
                            { type: 'textbox', name: 'rows', label: 'Rows', value: '3' },
                            { type: 'textbox', name: 'cols', label: 'Columns', value: '3' },
                            { type: 'checkbox', name: 'header', label: 'Heading row', checked: true }
                        ],
                        onsubmit: function (e) { insertTable(ed, e.data.rows, e.data.cols, e.data.header); }
                    });
                } },
                { text: '-' },
                { text: 'Add row below', onclick: function () { editTable(ed, 'row-add'); } },
                { text: 'Add column right', onclick: function () { editTable(ed, 'col-add'); } },
                { text: 'Delete row', onclick: function () { editTable(ed, 'row-delete'); } },
                { text: 'Delete column', onclick: function () { editTable(ed, 'col-delete'); } },
                { text: 'Delete table', onclick: function () { editTable(ed, 'table-delete'); } }
            ]
        });
    }

    function editorContent() {
        var ed = window.tinymce && window.tinymce.get(EDITOR_ID);
        return ed && !ed.isHidden() ? ed.getContent() : String($('#' + EDITOR_ID).val() || '');
    }

    function removeEditor() {
        if (window.wp && wp.editor && window.tinymce && window.tinymce.get(EDITOR_ID)) {
            wp.editor.remove(EDITOR_ID);
        }
    }

    function selectedRichField() {
        var field = builder.fields.find(function (f) { return f.id === builder.selectedFieldId; });
        return field && field.type === 'rich_text' ? field : null;
    }

    function syncRich() {
        clearTimeout(richSyncTimer);
        richSyncTimer = setTimeout(function () {
            var field = selectedRichField();
            if (!field) return;
            builder.updateFieldFromInspector();
            var $card = $('.tsfs-canvas-field-card[data-id="' + field.id + '"]');
            $card.find('.tsfs-rich-content').replaceWith(richBlock(field));
            $card.removeClass('width-100 width-75 width-66 width-50 width-33 width-25').addClass('width-' + (field.width || '100'));
            builder.updateSaveStatus();
        }, 250);
    }

    function colorControl(id, label, value) {
        return '<div class="tsfs-settings-form-group"><label for="' + id + '">' + esc(label) + '</label>' +
            '<input type="color" id="' + id + '" value="' + esc(value || '#000000') + '">' +
            '<label class="tsfs-rich-clear-color"><input type="checkbox" id="' + id + '-on"' + (value ? ' checked' : '') + '> Use colour</label></div>';
    }

    function selectControl(id, label, options, value) {
        return '<div class="tsfs-settings-form-group"><label for="' + id + '">' + esc(label) + '</label><select id="' + id + '">' +
            options.map(function (o) { var v = Array.isArray(o) ? o[0] : o, t = Array.isArray(o) ? o[1] : o; return '<option value="' + esc(v) + '"' + (String(value) === String(v) ? ' selected' : '') + '>' + esc(t) + '</option>'; }).join('') +
            '</select></div>';
    }

    function smartCodeOptions(current) {
        var groups = [
            ['Site', [['{site_name}', 'Site name'], ['{site_url}', 'Site address'], ['{page_title}', 'Current page title'], ['{current_date}', "Today's date"]]],
            ['Visitor', [['{user_name}', 'Logged-in user name'], ['{user_email}', 'Logged-in user email']]],
            ['Form answers (update as the visitor types)', builder.fields.filter(function (f) {
                return f.id !== current.id && f.name && ['html', 'hidden', 'page_break', 'rich_text', 'file', 'signature'].indexOf(f.type) === -1;
            }).map(function (f) { return ['{field:' + f.name + '}', f.label || f.name]; })]
        ];
        return '<option value="">Insert a SmartCode…</option>' + groups.filter(function (g) { return g[1].length; }).map(function (g) {
            return '<optgroup label="' + esc(g[0]) + '">' + g[1].map(function (o) { return '<option value="' + esc(o[0]) + '">' + esc(o[1]) + ' — ' + esc(o[0]) + '</option>'; }).join('') + '</optgroup>';
        }).join('');
    }

    function insertSmartCode(code) {
        var ed = window.tinymce && window.tinymce.get(EDITOR_ID);
        if (ed && !ed.isHidden()) {
            ed.insertContent(code);
        } else {
            var area = document.getElementById(EDITOR_ID);
            if (!area) return;
            var start = area.selectionStart || 0, end = area.selectionEnd || 0;
            area.value = area.value.slice(0, start) + code + area.value.slice(end);
            area.selectionStart = area.selectionEnd = start + code.length;
            area.focus();
        }
        syncRich();
    }

    $(document).on('change', '#insp-rich-smartcode', function () {
        if (this.value) insertSmartCode(this.value);
        this.value = '';
    });

    function initRichEditor() {
        removeEditor();
        $('#tsfs-field-settings-content .tsfs-width-opt').closest('.tsfs-settings-form-group').hide();
        $('#insp-help').closest('.tsfs-settings-form-group').hide();
        $('#insp-required').closest('.tsfs-toggle-row').hide();
        if (!$('#' + EDITOR_ID).length || !window.wp || !wp.editor || !wp.editor.initialize) return;
        wp.editor.initialize(EDITOR_ID, {
            tinymce: {
                wpautop: false,
                wordpress_adv_hidden: false,
                height: 320,
                plugins: 'charmap colorpicker fullscreen hr lists link paste tabfocus textcolor wordpress wplink wptextpattern',
                toolbar1: 'formatselect fontsizeselect bold italic underline strikethrough forecolor backcolor removeformat',
                toolbar2: 'bullist numlist tsfs_liststyle outdent indent alignleft aligncenter alignright alignjustify',
                toolbar3: 'link unlink blockquote tsfs_callout tsfs_table tsfs_button hr charmap undo redo fullscreen',
                toolbar4: '',
                // The editor shows lists, boxes and tables exactly as the live form does.
                content_css: data.rich_css || '',
                body_class: 'tsfs-rich-content',
                content_style: 'body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;font-size:15px;line-height:1.6;color:#1f2937;margin:12px}',
                block_formats: 'Paragraph=p;Heading 2=h2;Heading 3=h3;Heading 4=h4;Heading 5=h5;Heading 6=h6;Preformatted=pre',
                fontsize_formats: '12px 13px 14px 16px 18px 20px 24px 28px 32px 36px 40px 48px',
                textcolor_map: ['0f172a', 'Dark', '334155', 'Slate', '64748b', 'Grey', 'ffffff', 'White', '4f46e5', 'Indigo', '2563eb', 'Blue', '0891b2', 'Cyan', '059669', 'Green', '65a30d', 'Lime', 'ca8a04', 'Yellow', 'ea580c', 'Orange', 'dc2626', 'Red', 'db2777', 'Pink', '7c3aed', 'Violet', 'fef3c7', 'Light yellow', 'dcfce7', 'Light green', 'dbeafe', 'Light blue', 'fee2e2', 'Light red'],
                setup: function (ed) {
                    addProTools(ed);
                    ed.on('change keyup input undo redo SetContent ExecCommand', syncRich);
                    ed.on('init', function () {
                        $('#' + EDITOR_ID + '-html').text('Code');
                    });
                    // "Button" inserts a styled call-to-action link.
                    ed.addButton('tsfs_button', {
                        text: 'Button',
                        tooltip: 'Insert a button link',
                        onclick: function () {
                            ed.windowManager.open({
                                title: 'Insert button',
                                body: [
                                    { type: 'textbox', name: 'label', label: 'Button text', value: 'Learn more' },
                                    { type: 'textbox', name: 'url', label: 'Link (URL)', value: 'https://' },
                                    { type: 'checkbox', name: 'blank', label: 'Open in a new tab' }
                                ],
                                onsubmit: function (e) {
                                    var url = String(e.data.url || '').trim();
                                    if (!/^(https?:|mailto:|tel:|\/)/i.test(url)) return;
                                    ed.insertContent('<a class="tsfs-rich-button" href="' + esc(url) + '"' + (e.data.blank ? ' target="_blank" rel="noopener noreferrer"' : '') + '>' + esc(e.data.label || 'Learn more') + '</a>&nbsp;');
                                }
                            });
                        }
                    });
                }
            },
            quicktags: { buttons: 'strong,em,ul,ol,li,link,close' },
            mediaButtons: false
        });
    }

    $(document).on('input change', '#' + EDITOR_ID + ', .tsfs-rich-inspector :input', syncRich);

    builder.registerFieldType('rich_text', {
        label: 'Rich Content',
        hidePlaceholder: true,
        defaults: function (field) {
            field.required = false;
            field.help_text = '';
        },
        canvas: function (field) {
            return richBlock(field);
        },
        inspector: function (field) {
            setTimeout(initRichEditor, 0);
            var styles = data.list_styles || { ul: ['disc', 'circle', 'square', 'none'], ol: ['decimal', 'lower-alpha', 'upper-alpha', 'lower-roman', 'upper-roman', 'none'] };
            var widths = (data.rich_widths || ['100', '75', '66', '50', '33', '25']).map(function (w) { return [w, w === '66' ? '66% (two thirds)' : (w === '33' ? '33% (one third)' : w + '%')]; });
            return '<div class="tsfs-rich-inspector">' +
                '<details class="tsfs-rich-panel" open><summary>Content</summary><div class="tsfs-rich-panel-body">' +
                '<label class="tsfs-rich-label" for="' + EDITOR_ID + '">Content <span class="dashicons dashicons-info-outline" title="Use Visual to format text, or Code to edit the HTML. Scripts and styles are removed when saved."></span></label>' +
                '<textarea id="' + EDITOR_ID + '" class="wp-editor-area" rows="10">' + esc(field.content || '') + '</textarea>' +
                '<label class="tsfs-rich-label" for="insp-rich-smartcode">Dynamic SmartCodes</label>' +
                '<select id="insp-rich-smartcode">' + smartCodeOptions(field) + '</select>' +
                '<p class="description">SmartCodes are replaced on the live form. Form answers fill in while the visitor types.</p>' +
                '<p class="description">Use <strong>List style</strong> for checklists and steps, <strong>Box</strong> for highlighted notes and <strong>Table</strong> for fees or schedules. Full-screen editing is on the last button.</p>' +
                '</div></details>' +
                '<details class="tsfs-rich-panel" open><summary>Block style</summary><div class="tsfs-rich-panel-body">' +
                '<div class="tsfs-rich-grid">' +
                selectControl('insp-rich-width', 'Block width', widths, field.width || '100') +
                selectControl('insp-rich-font', 'Base text size', [12, 13, 14, 15, 16, 17, 18, 20, 22, 24].map(function (n) { return [String(n), n + 'px']; }), field.font_size || '16') +
                selectControl('insp-rich-line', 'Line spacing', [['1.2', 'Tight'], ['1.4', 'Compact'], ['1.6', 'Normal'], ['1.8', 'Relaxed'], ['2', 'Double']], field.line_height || '1.6') +
                selectControl('insp-rich-padding', 'Inner spacing', [['0', 'None'], ['8', 'Small'], ['16', 'Medium'], ['24', 'Large'], ['32', 'Extra large']], field.padding || '0') +
                selectControl('insp-rich-ul', 'Default bullet style', styles.ul.map(function (v) { return [v, { disc: '• Disc', circle: '◦ Circle', square: '▪ Square', check: '✓ Checkmarks', 'check-circle': '✔ Check circles', cross: '✗ Crosses', arrow: '→ Arrows', star: '★ Stars', none: 'No marker' }[v] || v]; }), field.ul_style || 'disc') +
                selectControl('insp-rich-ol', 'Numbering style', styles.ol.map(function (v) { return [v, { decimal: '1, 2, 3', 'decimal-leading-zero': '01, 02, 03', 'lower-alpha': 'a, b, c', 'upper-alpha': 'A, B, C', 'lower-roman': 'i, ii, iii', 'upper-roman': 'I, II, III', 'lower-greek': 'α, β, γ', none: 'No marker' }[v] || v]; }), field.ol_style || 'decimal') +
                colorControl('insp-rich-color', 'Text colour', field.text_color) +
                colorControl('insp-rich-bg', 'Background', field.bg_color) +
                colorControl('insp-rich-border', 'Border', field.border_color) +
                colorControl('insp-rich-marker', 'Bullet & icon colour', field.marker_color) +
                selectControl('insp-rich-radius', 'Corners', [['0', 'Square'], ['6', 'Slightly rounded'], ['12', 'Rounded'], ['20', 'Very rounded']], field.radius || '0') +
                '</div></div></details></div>';
        },
        readInspector: function (field) {
            if (!$('#' + EDITOR_ID).length) return;
            field.content = editorContent();
            field.width = $('#insp-rich-width').val() || '100';
            field.font_size = $('#insp-rich-font').val() || '16';
            field.line_height = $('#insp-rich-line').val() || '1.6';
            field.padding = $('#insp-rich-padding').val() || '0';
            field.radius = $('#insp-rich-radius').val() || '0';
            field.ul_style = $('#insp-rich-ul').val() || 'disc';
            field.ol_style = $('#insp-rich-ol').val() || 'decimal';
            field.text_color = $('#insp-rich-color-on').is(':checked') ? $('#insp-rich-color').val() : '';
            field.bg_color = $('#insp-rich-bg-on').is(':checked') ? $('#insp-rich-bg').val() : '';
            field.border_color = $('#insp-rich-border-on').is(':checked') ? $('#insp-rich-border').val() : '';
            field.marker_color = $('#insp-rich-marker-on').is(':checked') ? $('#insp-rich-marker').val() : '';
            field.required = false;
        },
        preview: function (field) {
            return richBlock(field);
        }
    });

    /* "Convert to Rich Content" on core Section blocks keeps the heading, text, name and conditions. */
    function sectionToRichHtml(field) {
        var tag = ['h2', 'h3', 'h4', 'h5', 'h6'].indexOf(field.tag) >= 0 ? field.tag : 'h3';
        var mode = field.content_mode || 'section';
        var content = String(field.content || '');
        var heading = field.label ? '<' + tag + '>' + esc(field.label) + '</' + tag + '>' : '';
        var lines = content.split(/\r?\n/).filter(function (l) { return l.trim(); });
        if (mode === 'heading') return heading;
        if (mode === 'bullets' || mode === 'numbered') {
            var list = mode === 'numbered' ? 'ol' : 'ul';
            return '<' + list + '>' + lines.map(function (l) { return '<li>' + esc(l.trim()) + '</li>'; }).join('') + '</' + list + '>';
        }
        var body = /<(p|ul|ol|h[2-6]|div|blockquote|br|table)\b/i.test(content) ? content : content.split(/\r?\n\s*\r?\n/).filter(function (p) { return p.trim(); }).map(function (p) {
            return '<p>' + esc(p.trim()).replace(/\r?\n/g, '<br>') + '</p>';
        }).join('');
        return (mode === 'section' ? heading : '') + body;
    }

    $(document).on('tsfs:inspector-rendered', function (e, field, $container) {
        if (!field || field.type !== 'html') return;
        $container.find('.tsfs-field-inspector-form').append(
            '<div class="tsfs-rich-convert">' +
            '<p><strong>Need checklists, highlighted boxes or tables?</strong> Convert this block to Rich Content. The heading and text are kept.</p>' +
            '<button type="button" class="button button-primary" id="tsfs-convert-to-rich"><span class="dashicons dashicons-editor-paste-text"></span> Convert to Rich Content</button>' +
            '</div>');
    });

    $(document).on('click', '#tsfs-convert-to-rich', function () {
        var field = builder.fields.find(function (f) { return f.id === builder.selectedFieldId; });
        if (!field || field.type !== 'html') return;
        builder.flushPendingHistory();
        var content = sectionToRichHtml(field);
        delete field.content_mode;
        delete field.tag;
        $.extend(field, {
            type: 'rich_text', content: content, width: '100', font_size: '16', line_height: '1.6', padding: '0', radius: '0',
            text_color: '', bg_color: '', border_color: '', marker_color: '', ul_style: 'disc', ol_style: 'decimal', required: false, help_text: ''
        });
        builder.renderCanvas();
        builder.renderInspector();
        builder.takeHistorySnapshot('Convert to Rich Content');
    });

    // Close the editor when another field is selected.
    $(document).on('click', '.tsfs-canvas-field-card, .tsfs-outline-field', function () {
        setTimeout(function () {
            if (!selectedRichField()) removeEditor();
        }, 0);
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
