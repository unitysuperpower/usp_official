/**
 * Frontend behaviour for TechSolution Form Studio Pro fields:
 * international phone masks, payment details, signature pad and calculations.
 * Hooks into the core form engine through window.TSFSForms and its tsfs:* events.
 *
 * @package TechSolution_Form_Studio_Pro
 */
(function ($) {
    'use strict';

    var __ = wp.i18n.__;
    var sprintf = wp.i18n.sprintf;
    var TSFSForms = window.TSFSForms = window.TSFSForms || {};
    TSFSForms.validators = TSFSForms.validators || [];

    /* ------------------------------------------------------------------ */
    /* International phone                                                 */
    /* ------------------------------------------------------------------ */

    function applyMask(digits, mask) {
        var out = '';
        var d = 0;
        for (var i = 0; i < mask.length && d < digits.length; i++) {
            out += mask[i] === 'X' ? digits[d++] : mask[i];
        }
        return out;
    }

    function phoneRule($wrap) {
        var $opt = $wrap.find('.tsfs-dial-select option:selected');
        return {
            name: $opt.attr('data-name') || '',
            dial: $opt.attr('data-dial') || '',
            min: parseInt($opt.attr('data-min'), 10) || 7,
            max: parseInt($opt.attr('data-max'), 10) || 15,
            mask: $opt.attr('data-mask') || 'XXXXXXXXXXXXXXX',
            placeholder: $opt.attr('data-placeholder') || ''
        };
    }

    function updatePhone($input) {
        var $wrap = $input.closest('.tsfs-intl-tel-control');
        var rule = phoneRule($wrap);
        var digits = ($input.val() || '').replace(/\D/g, '').substring(0, rule.max);
        $input.val(applyMask(digits, rule.mask));

        var $counter = $wrap.find('.tsfs-phone-digits-counter');
        $counter.find('.tsfs-count-num').text(digits.length);
        $counter.find('.tsfs-count-max').text(rule.max);
        $counter.toggleClass('is-valid', digits.length >= rule.min)
            .toggleClass('is-incomplete', digits.length > 0 && digits.length < rule.min)
            .toggleClass('is-max', digits.length === rule.max);
    }

    $(document).on('input', '.tsfs-tel-masked-input', function () {
        updatePhone($(this));
    });

    $(document).on('change', '.tsfs-dial-select', function () {
        var $wrap = $(this).closest('.tsfs-intl-tel-control');
        var rule = phoneRule($wrap);
        var $input = $wrap.find('.tsfs-tel-masked-input');
        $input.attr({ maxlength: rule.mask.length, placeholder: rule.placeholder, 'data-mask': rule.mask, 'data-min-digits': rule.min, 'data-max-digits': rule.max });
        $wrap.find('.tsfs-hint-mask').text(rule.mask);
        updatePhone($input);
    });

    TSFSForms.validators.push(function ($wrap, helpers) {
        if (!$wrap.hasClass('tsfs-field-intl_tel')) return undefined;
        var rule = phoneRule($wrap);
        var digits = ($wrap.find('.tsfs-tel-masked-input').val() || '').replace(/\D/g, '');
        if (!digits) return helpers.isRequired ? helpers.t('required_field', 'This field is required.') : '';
        if (digits.length < rule.min || digits.length > rule.max) {
            var expected = rule.min === rule.max ? String(rule.min) : rule.min + '–' + rule.max;
            /* translators: 1: country name, 2: dial code, 3: expected digits, 4: digits entered */
            return sprintf(__('A %1$s (%2$s) number has %3$s digits. You entered %4$d.', 'techsolution-form-studio-pro'), rule.name, rule.dial, expected, digits.length);
        }
        return '';
    });

    /* ------------------------------------------------------------------ */
    /* Payment details (card number and CVC are never submitted)           */
    /* ------------------------------------------------------------------ */

    function detectBrand(digits) {
        if (/^3[47]/.test(digits)) return 'amex';
        if (/^4/.test(digits)) return 'visa';
        if (/^(5[1-5]|2[2-7])/.test(digits)) return 'mastercard';
        if (/^(6011|622|64[4-9]|65)/.test(digits)) return 'discover';
        return '';
    }

    function luhn(digits) {
        var sum = 0;
        for (var i = 0; i < digits.length; i++) {
            var n = parseInt(digits[digits.length - 1 - i], 10);
            if (i % 2 === 1) { n *= 2; if (n > 9) n -= 9; }
            sum += n;
        }
        return digits.length > 0 && sum % 10 === 0;
    }

    $(document).on('input', '.tsfs-card-number-input', function () {
        var $panel = $(this).closest('.tsfs-payment-card-panel');
        var digits = ($(this).val() || '').replace(/\D/g, '');
        var brand = detectBrand(digits);
        var max = brand === 'amex' ? 15 : 16;
        digits = digits.substring(0, max);
        var groups = brand === 'amex' ? [4, 6, 5] : [4, 4, 4, 4];
        var parts = [];
        var pos = 0;
        groups.forEach(function (size) {
            if (pos < digits.length) parts.push(digits.substr(pos, size));
            pos += size;
        });
        $(this).val(parts.join(' '));
        $panel.find('.tsfs-card-brand-detected-pill').text(brand ? brand.toUpperCase() : __('Card', 'techsolution-form-studio-pro'))
            .removeClass('is-visa is-mastercard is-amex is-discover').addClass(brand ? 'is-' + brand : '');
        $panel.find('.tsfs-card-brand-hidden').val(brand);
        $panel.find('.tsfs-card-last4-hidden').val(digits.length >= 12 ? digits.slice(-4) : '');
        $panel.find('.tsfs-card-cvc-input').attr('maxlength', brand === 'amex' ? 4 : 3);
    });

    $(document).on('input', '.tsfs-card-exp-input', function () {
        var digits = ($(this).val() || '').replace(/\D/g, '').substring(0, 4);
        $(this).val(digits.length > 2 ? digits.substring(0, 2) + ' / ' + digits.substring(2) : digits);
    });

    $(document).on('input', '.tsfs-card-cvc-input', function () {
        $(this).val(($(this).val() || '').replace(/\D/g, ''));
    });

    TSFSForms.validators.push(function ($wrap, helpers) {
        if (!$wrap.hasClass('tsfs-field-payment')) return undefined;
        var holder = ($wrap.find('.tsfs-card-holder-input').val() || '').trim();
        var number = ($wrap.find('.tsfs-card-number-input').val() || '').replace(/\D/g, '');
        var exp = ($wrap.find('.tsfs-card-exp-input').val() || '').replace(/\D/g, '');
        var cvc = ($wrap.find('.tsfs-card-cvc-input').val() || '').replace(/\D/g, '');
        if (!holder && !number && !exp) return helpers.isRequired ? helpers.t('required_field', 'This field is required.') : '';
        if (!holder) return __('Enter the cardholder name.', 'techsolution-form-studio-pro');
        var brand = detectBrand(number);
        if (number.length < (brand === 'amex' ? 15 : 16) || !luhn(number)) return __('Enter a valid card number.', 'techsolution-form-studio-pro');
        var month = parseInt(exp.substring(0, 2), 10);
        var year = 2000 + parseInt(exp.substring(2, 4), 10);
        var now = new Date();
        if (exp.length !== 4 || month < 1 || month > 12 || year < now.getFullYear() || (year === now.getFullYear() && month < now.getMonth() + 1)) {
            return __('Enter a valid expiry date (MM / YY).', 'techsolution-form-studio-pro');
        }
        if (cvc.length !== (brand === 'amex' ? 4 : 3)) return __('Enter the card security code.', 'techsolution-form-studio-pro');
        return '';
    });

    /* ------------------------------------------------------------------ */
    /* Signature pad                                                       */
    /* ------------------------------------------------------------------ */

    function initSignatures($scope) {
        $scope.find('.tsfs-signature-pad-wrap').each(function () {
            var $wrap = $(this);
            var canvas = $wrap.find('.tsfs-signature-canvas')[0];
            if (!canvas || canvas.getBoundingClientRect().width === 0) return;
            if ($wrap.data('tsfsSig')) {
                $wrap.data('tsfsSig').resize();
                return;
            }
            var ctx = canvas.getContext('2d');
            var $hidden = $wrap.find('.tsfs-signature-data');
            var $badge = $wrap.find('.tsfs-sig-status-badge');
            var drawing = false;
            var dirty = false;

            function resize() {
                var rect = canvas.getBoundingClientRect();
                if (!rect.width) return;
                var ratio = window.devicePixelRatio || 1;
                var previous = $hidden.val();
                canvas.width = rect.width * ratio;
                canvas.height = rect.height * ratio;
                ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';
                ctx.lineWidth = 2.5;
                ctx.strokeStyle = canvas.getAttribute('data-pen-color') || '#0f172a';
                if (previous) {
                    var img = new Image();
                    img.onload = function () { ctx.drawImage(img, 0, 0, rect.width, rect.height); };
                    img.src = previous;
                }
            }
            function point(e) {
                var rect = canvas.getBoundingClientRect();
                return { x: e.clientX - rect.left, y: e.clientY - rect.top };
            }
            function clear() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                $hidden.val('').trigger('change');
                dirty = false;
                $badge.removeClass('tsfs-sig-signed').addClass('tsfs-sig-empty').text(__('Awaiting signature', 'techsolution-form-studio-pro'));
            }

            canvas.style.touchAction = 'none';
            canvas.addEventListener('pointerdown', function (e) {
                drawing = true;
                canvas.setPointerCapture(e.pointerId);
                var p = point(e);
                ctx.beginPath();
                ctx.moveTo(p.x, p.y);
            });
            canvas.addEventListener('pointermove', function (e) {
                if (!drawing) return;
                var p = point(e);
                ctx.lineTo(p.x, p.y);
                ctx.stroke();
                dirty = true;
            });
            function end() {
                if (!drawing) return;
                drawing = false;
                if (dirty) {
                    $hidden.val(canvas.toDataURL('image/png')).trigger('change');
                    $badge.removeClass('tsfs-sig-empty').addClass('tsfs-sig-signed').text(__('Signed', 'techsolution-form-studio-pro'));
                }
            }
            canvas.addEventListener('pointerup', end);
            canvas.addEventListener('pointercancel', end);
            $wrap.find('.tsfs-sig-clear-btn').on('click', function (e) {
                e.preventDefault();
                clear();
            });

            $wrap.data('tsfsSig', { resize: resize, clear: clear });
            resize();
        });
    }

    TSFSForms.validators.push(function ($wrap, helpers) {
        if (!$wrap.hasClass('tsfs-field-signature')) return undefined;
        var value = $wrap.find('.tsfs-signature-data').val() || '';
        if (!value) return helpers.isRequired ? __('Please sign before continuing.', 'techsolution-form-studio-pro') : '';
        return '';
    });

    /* ------------------------------------------------------------------ */
    /* Calculations (arithmetic parser; nothing is evaluated as code)      */
    /* ------------------------------------------------------------------ */

    function evaluate(expression) {
        var tokens = expression.match(/\d+(?:\.\d+)?(?:e[-+]?\d+)?|[-+*\/()]/gi);
        if (!tokens || tokens.join('') !== expression.replace(/\s+/g, '')) return null;
        var pos = 0;
        function factor() {
            var tok = tokens[pos];
            if (tok === undefined) return null;
            if (tok === '-' || tok === '+') { pos++; var v = factor(); return v === null ? null : (tok === '-' ? -v : v); }
            if (tok === '(') { pos++; var inner = expr(); if (tokens[pos] !== ')') return null; pos++; return inner; }
            if (/^[\d.]/.test(tok)) { pos++; return parseFloat(tok); }
            return null;
        }
        function term() {
            var v = factor();
            while (v !== null && (tokens[pos] === '*' || tokens[pos] === '/')) {
                var op = tokens[pos++];
                var r = factor();
                if (r === null) return null;
                v = op === '*' ? v * r : (r === 0 ? 0 : v / r);
            }
            return v;
        }
        function expr() {
            var v = term();
            while (v !== null && (tokens[pos] === '+' || tokens[pos] === '-')) {
                var op = tokens[pos++];
                var r = term();
                if (r === null) return null;
                v = op === '+' ? v + r : v - r;
            }
            return v;
        }
        var result = expr();
        return pos === tokens.length && isFinite(result) ? result : null;
    }

    function fieldNumber($form, key) {
        var $target = $form.find('[name="' + key + '"]:enabled, [name="' + key + '[]"]:enabled');
        if (!$target.length) return 0;
        if ($target.is(':checkbox') || $target.is(':radio')) {
            var sum = 0;
            $target.filter(':checked').each(function () {
                var n = parseFloat(String($(this).val()).replace(/[^0-9.\-]/g, ''));
                sum += isNaN(n) ? 1 : n;
            });
            return sum;
        }
        var value = parseFloat(String($target.val() || '').replace(/[^0-9.\-]/g, ''));
        return isNaN(value) ? 0 : value;
    }

    function updateCalculations($form) {
        $form.find('.tsfs-calculation-wrap').each(function () {
            var $wrap = $(this);
            var decimals = parseInt($wrap.attr('data-decimals'), 10);
            if (isNaN(decimals)) decimals = 2;
            var prefix = $wrap.attr('data-prefix') || '';
            var suffix = $wrap.attr('data-suffix') || '';
            var expression = String($wrap.attr('data-formula') || '').replace(/\[([a-z0-9_]+)\]|\{([a-z0-9_]+)\}/gi, function (m, a, b) {
                return '(' + fieldNumber($form, (a || b).toLowerCase()) + ')';
            });
            var total = expression.trim() ? evaluate(expression) : 0;
            if (total === null) total = 0;
            $wrap.find('.tsfs-calc-display-val').text(prefix + total.toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals }) + (suffix ? ' ' + suffix : ''));
            $wrap.find('.tsfs-calculation-hidden').val(total.toFixed(decimals));
        });
    }

    /* ------------------------------------------------------------------ */
    /* Address                                                             */
    /* ------------------------------------------------------------------ */

    TSFSForms.validators.push(function ($wrap, helpers) {
        if (!$wrap.hasClass('tsfs-field-address')) return undefined;
        var $address = $wrap.find('.tsfs-address-control');
        if ($address.attr('data-loading') === 'true') return __('Please wait for the location list to finish loading.', 'techsolution-form-studio-pro');
        var incomplete = false;
        $address.find('.tsfs-address-required:enabled').each(function () {
            if (!String($(this).val() || '').trim()) incomplete = true;
        });
        return incomplete ? __('Please complete the required address fields.', 'techsolution-form-studio-pro') : '';
    });

    /* ------------------------------------------------------------------ */
    /* Core form events                                                    */
    /* ------------------------------------------------------------------ */

    /* ------------------------------------------------------------------ */
    /* Rich Content SmartCodes: show the visitor's answers as they type      */
    /* ------------------------------------------------------------------ */

    function answerText($form, key) {
        var $inputs = $form.find('[name="' + key + '"], [name="' + key + '[]"]');
        if (!$inputs.length) return '';
        if ($inputs.is(':checkbox') || $inputs.is(':radio')) {
            return $inputs.filter(':checked').map(function () { return $(this).val(); }).get().join(', ');
        }
        if ($inputs.is('select')) return $inputs.find('option:selected').val() ? $inputs.find('option:selected').text() : '';
        return String($inputs.first().val() || '');
    }

    function fillSmartFields($form) {
        $form.find('.tsfs-smart-field').each(function () {
            $(this).text(answerText($form, $(this).attr('data-field')));
        });
    }

    $(document).on('tsfs:form-init', function (e, $form) {
        fillSmartFields($form);
    });
    $(document).on('tsfs:form-change tsfs:form-reset', function (e, $form) {
        fillSmartFields($form);
    });

    $(document).on('tsfs:form-init', function (e, $form) {
        $form.find('.tsfs-tel-masked-input').each(function () { updatePhone($(this)); });
        initSignatures($form);
        updateCalculations($form);
    });
    $(document).on('tsfs:form-change', function (e, $form) {
        updateCalculations($form);
    });
    $(document).on('tsfs:step-shown tsfs:field-shown', function (e, $scope) {
        initSignatures($scope);
        if (window.TSFSAddress && $scope && $scope[0]) window.TSFSAddress.refresh($scope[0]);
    });
    $(document).on('tsfs:form-reset', function (e, $form) {
        $form.find('.tsfs-signature-pad-wrap').each(function () {
            var pad = $(this).data('tsfsSig');
            if (pad) pad.clear();
        });
        $form.find('.tsfs-card-brand-hidden, .tsfs-card-last4-hidden').val('');
        $form.find('.tsfs-tel-masked-input').each(function () { updatePhone($(this)); });
        updateCalculations($form);
    });
    $(window).on('resize', function () {
        $('.tsfs-signature-pad-wrap').each(function () {
            var pad = $(this).data('tsfsSig');
            if (pad) pad.resize();
        });
    });

    // Forms already initialised before this script loaded.
    $(function () {
        $('.tsfs-form').each(function () {
            $(document).trigger('tsfs:form-init', [$(this)]);
        });
    });
})(jQuery);
