/**
 * Advanced Frontend Form Engine for USP Form Builder.
 * Features: Real-time Conditional Logic, File Uploads with FormData, accessible validation, and smooth states.
 *
 * @package USP_Form_Builder
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        /**
         * 1. Conditional Logic Engine
         */
        function evaluateConditionalLogic($form) {
            $form.find('.usp-field-wrap[data-conditions]').each(function() {
                var $fieldWrap = $(this);
                var rawConditions = $fieldWrap.attr('data-conditions');
                if (!rawConditions) return;

                var condConfig;
                try {
                    condConfig = JSON.parse(rawConditions);
                } catch(e) {
                    return;
                }

                if (!condConfig || !Array.isArray(condConfig.rules) || condConfig.rules.length === 0) {
                    return;
                }

                var matchMode = condConfig.match || 'all';
                var action = condConfig.action || 'show';
                var ruleResults = [];

                condConfig.rules.forEach(function(rule) {
                    var targetField = rule.field;
                    var operator = rule.operator || '==';
                    var expectedVal = String(rule.value || '').toLowerCase().trim();

                    // Find target input inside this specific form
                    var $targetInputs = $form.find('[name="' + targetField + '"], [name="' + targetField + '[]"]');
                    var currentVal = '';

                    if ($targetInputs.is(':checkbox') || $targetInputs.is(':radio')) {
                        var checkedVals = [];
                        $targetInputs.filter(':checked').each(function() {
                            checkedVals.push($(this).val().toLowerCase().trim());
                        });
                        currentVal = checkedVals;
                    } else {
                        currentVal = String($targetInputs.val() || '').toLowerCase().trim();
                    }

                    var isRuleMet = false;
                    if (Array.isArray(currentVal)) {
                        if (operator === '==' || operator === 'contains') {
                            isRuleMet = currentVal.indexOf(expectedVal) !== -1;
                        } else if (operator === '!=') {
                            isRuleMet = currentVal.indexOf(expectedVal) === -1;
                        }
                    } else {
                        if (operator === '==') {
                            isRuleMet = (currentVal === expectedVal);
                        } else if (operator === '!=') {
                            isRuleMet = (currentVal !== expectedVal);
                        } else if (operator === 'contains') {
                            isRuleMet = (currentVal.indexOf(expectedVal) !== -1);
                        } else if (operator === 'empty') {
                            isRuleMet = (currentVal === '');
                        } else if (operator === 'not_empty') {
                            isRuleMet = (currentVal !== '');
                        }
                    }
                    ruleResults.push(isRuleMet);
                });

                var shouldSatisfy = false;
                if (matchMode === 'any') {
                    shouldSatisfy = ruleResults.some(function(r) { return r; });
                } else {
                    shouldSatisfy = ruleResults.every(function(r) { return r; });
                }

                var shouldShow = (action === 'show') ? shouldSatisfy : !shouldSatisfy;

                if (shouldShow) {
                    if ($fieldWrap.is(':hidden')) {
                        $fieldWrap.stop(true, true).slideDown(200);
                    }
                    $fieldWrap.find('.usp-input, input, select, textarea').prop('disabled', false);
                    if (window.USPAddress) window.USPAddress.refresh($fieldWrap[0]);
                } else {
                    if ($fieldWrap.is(':visible')) {
                        $fieldWrap.stop(true, true).slideUp(200);
                    }
                    $fieldWrap.find('.usp-input, input, select, textarea').prop('disabled', true);
                    $fieldWrap.removeClass('has-error').find('.usp-field-error').hide();
                }
            });
        }

        function markFieldError($wrap, msg) {
            $wrap.addClass('has-error').find(':input').attr('aria-invalid', 'true');
            $wrap.find('.usp-field-error').text(msg).show();
        }

        // Initialize conditional logic and calculations for every form on load
        $('.usp-form').each(function() {
            evaluateConditionalLogic($(this));
            evaluateCalculationFormulas($(this));
        });

        // Re-evaluate whenever any field changes
        $(document).on('input change', '.usp-form :input', function() {
            var $form = $(this).closest('.usp-form');
            evaluateConditionalLogic($form);
            evaluateCalculationFormulas($form);
        });

        // Instant Error clearing on typing/choice
        $(document).on('input change', '.usp-form .usp-input, .usp-form input[type="radio"], .usp-form input[type="checkbox"], .usp-form input[type="file"], .usp-signature-data', function() {
            var $wrap = $(this).closest('.usp-field-wrap');
            if ($wrap.hasClass('has-error')) {
                $wrap.removeClass('has-error').find(':input').removeAttr('aria-invalid');
                $wrap.find('.usp-field-error').fadeOut(150, function() { $(this).text('').hide(); });
            }
        });

        /**
         * 2. International Phone Engine: Dynamic Masking, National Digit Limit & Real-time Validation
         */
        function applyPhoneMask(rawDigits, mask) {
            if (!rawDigits) return '';
            var formatted = '';
            var digitIndex = 0;
            for (var i = 0; i < mask.length; i++) {
                var char = mask[i];
                if (char === 'X') {
                    if (digitIndex < rawDigits.length) {
                        formatted += rawDigits[digitIndex];
                        digitIndex++;
                    } else {
                        break;
                    }
                } else {
                    if (digitIndex < rawDigits.length) {
                        formatted += char;
                    } else {
                        break;
                    }
                }
            }
            return formatted;
        }

        function handlePhoneInput($input) {
            var $wrap = $input.closest('.usp-intl-tel-control');
            var mask = $input.attr('data-mask') || 'XXXXXXXXXXXXXXX';
            var maxDigits = parseInt($input.attr('data-max-digits'), 10) || 15;
            var minDigits = parseInt($input.attr('data-min-digits'), 10) || 7;

            // Strip non-digits
            var currentVal = $input.val();
            var rawDigits = currentVal.replace(/\D/g, '');

            // Restrict past max digits strictly
            if (rawDigits.length > maxDigits) {
                rawDigits = rawDigits.substring(0, maxDigits);
            }

            var maskedVal = applyPhoneMask(rawDigits, mask);
            $input.val(maskedVal);

            // Update live digit counter
            var digitCount = rawDigits.length;
            var $counter = $wrap.find('.usp-phone-digits-counter');
            var $statusIcon = $wrap.find('.usp-tel-status-icon');

            if ($counter.length) {
                $counter.find('.usp-count-num').text(digitCount);
                $counter.find('.usp-count-max').text(maxDigits);

                if (digitCount === 0) {
                    $counter.removeClass('is-valid is-incomplete is-max');
                    $statusIcon.empty();
                } else if (digitCount >= minDigits && digitCount <= maxDigits) {
                    $counter.addClass('is-valid').removeClass('is-incomplete');
                    if (digitCount === maxDigits) {
                        $counter.addClass('is-max');
                    }
                    $statusIcon.html('<svg viewBox="0 0 20 20" style="width:16px;height:16px;fill:#10b981;"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>');
                } else {
                    $counter.addClass('is-incomplete').removeClass('is-valid is-max');
                    $statusIcon.empty();
                }
            }
        }

        // Initialize phone inputs on document load
        $('.usp-tel-masked-input').each(function() {
            handlePhoneInput($(this));
        });

        // Handle typing, pasting, and input in intl-tel fields
        $(document).on('input', '.usp-tel-masked-input', function() {
            handlePhoneInput($(this));
        });

        // Block non-numeric keystrokes and prevent typing beyond country max digits
        $(document).on('keydown', '.usp-tel-masked-input', function(e) {
            // Allow control keys: Backspace, Tab, Enter, Escape, Delete, Arrows, Home, End
            if ([8, 9, 13, 27, 46].indexOf(e.keyCode) !== -1 ||
                (e.keyCode >= 35 && e.keyCode <= 40) ||
                (e.ctrlKey || e.metaKey)) {
                return;
            }

            // Check if key is a number (0-9)
            var isDigitKey = (e.keyCode >= 48 && e.keyCode <= 57) || (e.keyCode >= 96 && e.keyCode <= 105);
            if (!isDigitKey) {
                e.preventDefault();
                return;
            }

            // If maximum digits already reached and user is not replacing text
            var $input = $(this);
            var maxDigits = parseInt($input.attr('data-max-digits'), 10) || 15;
            var rawDigits = ($input.val() || '').replace(/\D/g, '');
            var selLen = (this.selectionEnd || 0) - (this.selectionStart || 0);

            if (rawDigits.length >= maxDigits && selLen === 0) {
                e.preventDefault();
                // Visual pulse on limit
                var $counter = $input.closest('.usp-intl-tel-control').find('.usp-phone-digits-counter');
                $counter.addClass('pulse-limit');
                setTimeout(function() { $counter.removeClass('pulse-limit'); }, 300);
            }
        });

        // Handle Country Selector change
        $(document).on('change', '.usp-dial-select', function() {
            var $select = $(this);
            var $wrap = $select.closest('.usp-intl-tel-control');
            var $opt = $select.find('option:selected');
            var $input = $wrap.find('.usp-tel-masked-input');
            var $countryInput = $wrap.find('.usp-country-code-input');

            var countryCode = $opt.attr('data-country') || 'PK';
            var minDigits = parseInt($opt.attr('data-min'), 10) || 10;
            var maxDigits = parseInt($opt.attr('data-max'), 10) || 10;
            var mask = $opt.attr('data-mask') || 'XXX XXXXXXX';
            var placeholder = $opt.attr('data-placeholder') || 'Phone number';

            $countryInput.val(countryCode);
            $input.attr('data-min-digits', minDigits);
            $input.attr('data-max-digits', maxDigits);
            $input.attr('data-mask', mask);
            $input.attr('maxlength', mask.length);
            $input.attr('placeholder', placeholder);

            $wrap.find('.usp-hint-mask').text(mask);
            $wrap.find('.usp-count-max').text(maxDigits);

            // Re-apply mask to existing typed digits under new rules
            handlePhoneInput($input);
        });

        // Standard tel input (clean non-phone chars & restrict length)
        $(document).on('input', '.usp-input-tel-std', function() {
            var val = $(this).val();
            var clean = val.replace(/[^0-9+\-\s()]/g, '');
            if (clean !== val) {
                $(this).val(clean);
            }
        });

        /**
         * 3. Credit Card Engine: Real-Time Brand Auto-Detection & 4-Pair Group Formatting
         */
        var cardBrandLogos = {
            generic: '<svg viewBox="0 0 36 22" class="usp-brand-svg usp-svg-generic"><rect x="1" y="2" width="34" height="18" rx="3" fill="#f8fafc" stroke="#94a3b8" stroke-width="1.5"/><rect x="1" y="6" width="34" height="3" fill="#cbd5e1"/><circle cx="6" cy="14" r="1.5" fill="#94a3b8"/><rect x="11" y="13.5" width="8" height="1.5" rx="0.5" fill="#94a3b8"/></svg>',
            visa: '<svg viewBox="0 0 36 22" class="usp-brand-svg"><rect width="36" height="22" rx="3" fill="#1434CB"/><path d="M14.5 15.5l2.4-9h2.5l-2.4 9h-2.5zm8.9-8.8c-.5-.2-1.3-.4-2.3-.4-2.5 0-4.3 1.3-4.3 3.3 0 1.4 1.3 2.2 2.2 2.7.9.5 1.3.8 1.3 1.2 0 .6-.8.9-1.5.9-.9 0-1.5-.2-2.3-.5l-.3-.1-.3 2c.6.3 1.7.5 2.8.5 2.7 0 4.5-1.3 4.5-3.4 0-1.1-.7-2-2.3-2.7-.9-.5-1.5-.8-1.5-1.3 0-.4.5-.9 1.5-.9.8 0 1.4.2 1.9.4l.2.1.3-1.8zm6.6 5.8l1-4.8-1.5 4.8h.5zm2.3-6h-1.9c-.6 0-1.1.2-1.3.7l-3.8 8.3h2.6l.5-1.3h3.2l.3 1.3h2.3l-1.9-9zm-15.6 0l-2.4 6-.3-1.2c-.5-1.5-2-3.1-3.7-3.9l2.4 7.9h2.6l3.9-8.8h-2.5z" fill="#fff"/></svg>',
            mastercard: '<svg viewBox="0 0 36 22" class="usp-brand-svg"><rect width="36" height="22" rx="3" fill="#252525"/><circle cx="14" cy="11" r="6.5" fill="#EB001B"/><circle cx="22" cy="11" r="6.5" fill="#F79E1B"/><path d="M18 6.5a6.47 6.47 0 00-2.4 4.5 6.47 6.47 0 002.4 4.5 6.47 6.47 0 002.4-4.5A6.47 6.47 0 0018 6.5z" fill="#FF5F00"/></svg>',
            amex: '<svg viewBox="0 0 36 22" class="usp-brand-svg"><rect width="36" height="22" rx="3" fill="#016FD0"/><path d="M5 14l1.8-6h1.8l-1.8 6H5zm5.5 0l2.2-6h1.8l-2.2 6h-1.8zm8.5-6h-3.6l-1.8 6h2.2l.3-.9h2.1l.3.9h2.2l-1.7-6zm-2.4 3.2l.6-1.8.6 1.8h-1.2zm7.4-3.2h-3.6l-1.8 6h4v-1.3h-2.2v-.9h2v-1.1h-2v-.7h2.2V8z" fill="#fff"/></svg>',
            discover: '<svg viewBox="0 0 36 22" class="usp-brand-svg"><rect width="36" height="22" rx="3" fill="#fff" stroke="#e2e8f0"/><path d="M5 14l1.3-6h3c1.5 0 2.6 1 2.6 2.7s-1.1 2.7-2.6 2.7H5zm1.8-1.8h1.2c.8 0 1.2-.4 1.2-.9s-.4-.9-1.2-.9H6.8v1.8z" fill="#1e293b"/><circle cx="16.5" cy="11" r="3.5" fill="#FF6000"/><path d="M22 14l1.3-6h1.8l-1.3 6H22zm2.7 0l1.8-6h1.8l-1.8 6h-1.8z" fill="#1e293b"/></svg>'
        };

        function detectCardBrand(digits) {
            if (!digits) return 'generic';
            if (/^3[47]/.test(digits)) return 'amex';
            if (/^4/.test(digits)) return 'visa';
            if (/^(5[1-5]|2[2-7])/.test(digits)) return 'mastercard';
            if (/^(6011|622|64[4-9]|65)/.test(digits)) return 'discover';
            return 'generic';
        }

        function formatCardNumber(digits, brand) {
            if (!digits) return '';
            if (brand === 'amex') {
                var maxAmex = digits.substring(0, 15);
                var parts = [];
                if (maxAmex.length > 0) parts.push(maxAmex.substring(0, 4));
                if (maxAmex.length > 4) parts.push(maxAmex.substring(4, 10));
                if (maxAmex.length > 10) parts.push(maxAmex.substring(10, 15));
                return parts.join(' ');
            } else {
                var max16 = digits.substring(0, 16);
                var matches = max16.match(/.{1,4}/g);
                return matches ? matches.join(' ') : max16;
            }
        }

        function formatExpiryDate(digits) {
            if (!digits) return '';
            var clean = digits.substring(0, 4);
            if (clean.length === 1 && parseInt(clean, 10) > 1) {
                clean = '0' + clean;
            }
            if (clean.length >= 2) {
                var mm = parseInt(clean.substring(0, 2), 10);
                if (mm > 12) mm = 12;
                if (mm === 0) mm = 1;
                var mmStr = (mm < 10 ? '0' : '') + mm;
                var yyStr = clean.substring(2);
                return yyStr.length > 0 ? (mmStr + ' / ' + yyStr) : (mmStr + ' / ');
            }
            return clean;
        }

        function handleCardNumberInput($input) {
            var $panel = $input.closest('.usp-payment-card-panel');
            var rawVal = $input.val();
            var rawDigits = rawVal.replace(/\D/g, '');
            var brand = detectCardBrand(rawDigits);
            var maxDigits = (brand === 'amex') ? 15 : 16;

            if (rawDigits.length > maxDigits) {
                rawDigits = rawDigits.substring(0, maxDigits);
            }

            var formatted = formatCardNumber(rawDigits, brand);
            $input.val(formatted);

            // Update icon in input
            var brandSvg = cardBrandLogos[brand] || cardBrandLogos.generic;
            $panel.find('.usp-card-live-brand-icon').html(brandSvg);

            // Update badge pill
            var brandNames = {
                'visa': 'Visa',
                'mastercard': 'Mastercard',
                'amex': 'Amex',
                'discover': 'Discover',
                'generic': 'Card'
            };
            var pillText = brandNames[brand] || 'Card';
            var $pill = $panel.find('.usp-card-brand-detected-pill');
            $pill.text(pillText).removeClass('is-visa is-mastercard is-amex is-discover');
            if (brand !== 'generic') {
                $pill.addClass('is-' + brand);
            }

            // Update hidden brand input
            $panel.find('.usp-card-brand-hidden').val(brand);

            // Update Accepted Brand Badges Bar
            var $badges = $panel.find('.usp-brand-badge');
            if (brand === 'generic') {
                $badges.removeClass('is-active is-dimmed');
            } else {
                $badges.each(function() {
                    var b = $(this).attr('data-brand');
                    if (b === brand) {
                        $(this).addClass('is-active').removeClass('is-dimmed');
                    } else {
                        $(this).addClass('is-dimmed').removeClass('is-active');
                    }
                });
            }

            // Update CVC field placeholder and limit badge
            var $cvcInput = $panel.find('.usp-card-cvc-input');
            var $cvcTag = $panel.find('.usp-cvc-len-tag');
            if (brand === 'amex') {
                $cvcInput.attr('maxlength', '4').attr('placeholder', 'CID');
                $cvcTag.text('4 digits');
            } else {
                $cvcInput.attr('maxlength', '3').attr('placeholder', 'CVC');
                $cvcTag.text('3 digits');
            }
        }

        // Initialize any payment panels on page load
        $('.usp-card-number-input').each(function() {
            handleCardNumberInput($(this));
        });

        // Card Number Typing & Masking
        $(document).on('input', '.usp-card-number-input', function() {
            handleCardNumberInput($(this));
        });

        $(document).on('keydown', '.usp-card-number-input', function(e) {
            if ([8, 9, 13, 27, 46].indexOf(e.keyCode) !== -1 ||
                (e.keyCode >= 35 && e.keyCode <= 40) ||
                (e.ctrlKey || e.metaKey)) {
                return;
            }
            var isDigitKey = (e.keyCode >= 48 && e.keyCode <= 57) || (e.keyCode >= 96 && e.keyCode <= 105);
            if (!isDigitKey) {
                e.preventDefault();
                return;
            }
            var $input = $(this);
            var rawDigits = ($input.val() || '').replace(/\D/g, '');
            var brand = detectCardBrand(rawDigits);
            var maxDigits = (brand === 'amex') ? 15 : 16;
            var selLen = (this.selectionEnd || 0) - (this.selectionStart || 0);
            if (rawDigits.length >= maxDigits && selLen === 0) {
                e.preventDefault();
            }
        });

        // Expiry Date Auto-Formatting (MM / YY)
        $(document).on('input', '.usp-card-exp-input', function() {
            var raw = $(this).val().replace(/\D/g, '');
            var formatted = formatExpiryDate(raw);
            $(this).val(formatted);
        });

        $(document).on('keydown', '.usp-card-exp-input', function(e) {
            if ([8, 9, 13, 27, 46].indexOf(e.keyCode) !== -1 ||
                (e.keyCode >= 35 && e.keyCode <= 40) ||
                (e.ctrlKey || e.metaKey)) {
                return;
            }
            var isDigitKey = (e.keyCode >= 48 && e.keyCode <= 57) || (e.keyCode >= 96 && e.keyCode <= 105);
            if (!isDigitKey) {
                e.preventDefault();
                return;
            }
            var rawDigits = ($(this).val() || '').replace(/\D/g, '');
            var selLen = (this.selectionEnd || 0) - (this.selectionStart || 0);
            if (rawDigits.length >= 4 && selLen === 0) {
                e.preventDefault();
            }
        });

        // Security Code (CVC) Numeric Filtering & Max Length
        $(document).on('input', '.usp-card-cvc-input', function() {
            var raw = $(this).val().replace(/\D/g, '');
            var maxLen = parseInt($(this).attr('maxlength'), 10) || 3;
            if (raw.length > maxLen) {
                raw = raw.substring(0, maxLen);
            }
            $(this).val(raw);
        });

        $(document).on('keydown', '.usp-card-cvc-input', function(e) {
            if ([8, 9, 13, 27, 46].indexOf(e.keyCode) !== -1 ||
                (e.keyCode >= 35 && e.keyCode <= 40) ||
                (e.ctrlKey || e.metaKey)) {
                return;
            }
            var isDigitKey = (e.keyCode >= 48 && e.keyCode <= 57) || (e.keyCode >= 96 && e.keyCode <= 105);
            if (!isDigitKey) {
                e.preventDefault();
                return;
            }
            var rawDigits = ($(this).val() || '').replace(/\D/g, '');
            var maxLen = parseInt($(this).attr('maxlength'), 10) || 3;
            var selLen = (this.selectionEnd || 0) - (this.selectionStart || 0);
            if (rawDigits.length >= maxLen && selLen === 0) {
                e.preventDefault();
            }
        });

        /**
         * 4. Validation Engine (Modular for Steps & Full Form Submissions)
         */
        function validateContainerFields($container) {
            var hasErrors = false;
            var i18n = (window.uspPublicData && window.uspPublicData.i18n) ? window.uspPublicData.i18n : {};

            $container.find('.usp-field-wrap:visible').each(function() {
                var $wrap = $(this);
                var $input = $wrap.find('.usp-input, input[type="radio"], input[type="checkbox"], input[type="file"], .usp-signature-data');
                var fieldName = $wrap.data('field-name');
                var isRequired = $wrap.find('.usp-required-mark').length > 0;

                if (!fieldName || $wrap.hasClass('usp-field-html') || $wrap.hasClass('usp-field-page_break') || $input.prop('disabled')) {
                    return;
                }

                if ($wrap.hasClass('usp-field-address')) {
                    var $address = $wrap.find('.usp-address-control');
                    if ($address.attr('data-loading') === 'true') {
                        markFieldError($wrap, 'Please wait for the location list to finish loading.');
                        hasErrors = true;
                        return;
                    }
                    var incomplete = false;
                    $address.find('.usp-address-required:enabled').each(function() {
                        if (!String($(this).val() || '').trim()) incomplete = true;
                    });
                    if (incomplete) {
                        markFieldError($wrap, i18n.required_field || 'Please complete the required address fields.');
                        hasErrors = true;
                    }
                    return;
                }

                // Consent is a checkbox, not a text input. Checked consent must not
                // fall through to the generic .usp-input required check.
                if ($wrap.hasClass('usp-field-consent')) {
                    if (isRequired && !$wrap.find('input.usp-consent-checkbox').is(':checked')) {
                        markFieldError($wrap, 'Please check the consent box to continue.');
                        hasErrors = true;
                    }
                    return;
                }

                // Required check
                if (isRequired) {
                    if ($wrap.hasClass('usp-field-radio')) {
                        if ($wrap.find('input[type="radio"]:checked').length === 0) {
                            markFieldError($wrap, i18n.required_field || 'This field is required.');
                            hasErrors = true;
                            return;
                        }
                    } else if ($wrap.hasClass('usp-field-checkbox')) {
                        if ($wrap.find('input[type="checkbox"]:checked').length === 0) {
                            markFieldError($wrap, i18n.required_field || 'This field is required.');
                            hasErrors = true;
                            return;
                        }
                    } else if ($wrap.hasClass('usp-field-file')) {
                        var fileInput = $wrap.find('input[type="file"]')[0];
                        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                            markFieldError($wrap, i18n.required_field || 'Please select a file to upload.');
                            hasErrors = true;
                            return;
                        }
                    } else if ($wrap.hasClass('usp-field-signature')) {
                        var sigData = ($wrap.find('.usp-signature-data').val() || '').trim();
                        if (!sigData || sigData.indexOf('data:image/png;base64') !== 0) {
                            markFieldError($wrap, 'Please draw your digital signature before continuing.');
                            hasErrors = true;
                            return;
                        }
                    } else if ($wrap.hasClass('usp-field-admission')) {
                        var studentName = $wrap.find('input[name*="[student_name]"]').val();
                        if (!studentName || !studentName.trim()) {
                            markFieldError($wrap, 'Applicant Full Name is required.');
                            hasErrors = true;
                            return;
                        }
                    } else if ($wrap.hasClass('usp-field-payment')) {
                        var cardHolder = ($wrap.find('.usp-card-holder-input').val() || '').trim();
                        var cardNum = ($wrap.find('.usp-card-number-input').val() || '').replace(/\D/g, '');
                        var expVal = ($wrap.find('.usp-card-exp-input').val() || '').replace(/\D/g, '');
                        var cvcVal = ($wrap.find('.usp-card-cvc-input').val() || '').replace(/\D/g, '');

                        if (!cardHolder) {
                            markFieldError($wrap, 'Cardholder Name is required.');
                            hasErrors = true;
                            return;
                        }
                        if (!cardNum) {
                            markFieldError($wrap, 'Card Number is required.');
                            hasErrors = true;
                            return;
                        }
                        if (!expVal) {
                            markFieldError($wrap, 'Expiration Date is required.');
                            hasErrors = true;
                            return;
                        }
                        if (!cvcVal) {
                            markFieldError($wrap, 'Security Code (CVC) is required.');
                            hasErrors = true;
                            return;
                        }
                    } else if ($wrap.hasClass('usp-field-intl_tel')) {
                        var $telInput = $wrap.find('.usp-tel-masked-input');
                        var rawDigits = ($telInput.val() || '').replace(/\D/g, '');
                        if (!rawDigits) {
                            markFieldError($wrap, i18n.required_field || 'Phone number is required.');
                            hasErrors = true;
                            return;
                        }
                    } else {
                        var val = $wrap.find('.usp-input').val();
                        if (!val || !val.trim()) {
                            markFieldError($wrap, i18n.required_field || 'This field is required.');
                            hasErrors = true;
                            return;
                        }
                    }
                }

                // International Phone Number validation (strict digit bounds per country)
                if ($wrap.hasClass('usp-field-intl_tel')) {
                    var $telInput = $wrap.find('.usp-tel-masked-input');
                    var rawDigits = ($telInput.val() || '').replace(/\D/g, '');
                    var minDigits = parseInt($telInput.attr('data-min-digits'), 10) || 7;
                    var maxDigits = parseInt($telInput.attr('data-max-digits'), 10) || 15;
                    var $selectedOpt = $wrap.find('.usp-dial-select option:selected');
                    var countryName = $selectedOpt.attr('data-name') || 'Selected country';
                    var dialCode = $selectedOpt.val() || '';

                    if (rawDigits.length > 0) {
                        if (rawDigits.length < minDigits || rawDigits.length > maxDigits) {
                            var reqTxt = (minDigits === maxDigits) ? (minDigits + ' digits') : (minDigits + ' to ' + maxDigits + ' digits');
                            var errorMsg = 'Phone number for ' + countryName + ' (' + dialCode + ') must contain ' + reqTxt + ' (you entered ' + rawDigits.length + ').';
                            markFieldError($wrap, errorMsg);
                            hasErrors = true;
                            return;
                        }
                    }
                }

                // Standard Phone field validation
                if ($wrap.hasClass('usp-field-tel') && !$wrap.hasClass('usp-field-intl_tel')) {
                    var $telInput = $wrap.find('input[type="tel"]');
                    var rawDigits = ($telInput.val() || '').replace(/\D/g, '');
                    if (rawDigits.length > 0) {
                        if (rawDigits.length < 7 || rawDigits.length > 15) {
                            markFieldError($wrap, 'Please enter a valid phone number (between 7 and 15 digits; you entered ' + rawDigits.length + ').');
                            hasErrors = true;
                            return;
                        }
                    }
                }

                // Payment Card deep validation
                if ($wrap.hasClass('usp-field-payment')) {
                    var $cardInput = $wrap.find('.usp-card-number-input');
                    var rawCard = ($cardInput.val() || '').replace(/\D/g, '');
                    var expVal = ($wrap.find('.usp-card-exp-input').val() || '').replace(/\D/g, '');
                    var cvcVal = ($wrap.find('.usp-card-cvc-input').val() || '').replace(/\D/g, '');
                    var detectedBrand = $wrap.find('.usp-card-brand-hidden').val() || 'unknown';

                    if (rawCard.length > 0) {
                        var minCardLen = (detectedBrand === 'amex') ? 15 : 16;
                        if (rawCard.length < minCardLen) {
                            markFieldError($wrap, 'Card number must be ' + minCardLen + ' digits (you entered ' + rawCard.length + ').');
                            hasErrors = true;
                            return;
                        }
                    }

                    if (expVal.length > 0) {
                        if (expVal.length < 4) {
                            markFieldError($wrap, 'Please enter a valid expiration date (MM / YY).');
                            hasErrors = true;
                            return;
                        }
                        var expMonth = parseInt(expVal.substring(0, 2), 10);
                        var expYear = parseInt('20' + expVal.substring(2, 4), 10);
                        var now = new Date();
                        var currentYear = now.getFullYear();
                        var currentMonth = now.getMonth() + 1;

                        if (expMonth < 1 || expMonth > 12) {
                            markFieldError($wrap, 'Expiration month must be between 01 and 12.');
                            hasErrors = true;
                            return;
                        }
                        if (expYear < currentYear || (expYear === currentYear && expMonth < currentMonth)) {
                            markFieldError($wrap, 'Card has expired. Please check the expiration date.');
                            hasErrors = true;
                            return;
                        }
                    }

                    if (cvcVal.length > 0) {
                        var requiredCvcLen = (detectedBrand === 'amex') ? 4 : 3;
                        if (cvcVal.length < requiredCvcLen) {
                            markFieldError($wrap, 'Security code (CVC) must be ' + requiredCvcLen + ' digits.');
                            hasErrors = true;
                            return;
                        }
                    }
                }

                // Email validation
                if ($wrap.hasClass('usp-field-email')) {
                    var emailVal = $wrap.find('input[type="email"]').val();
                    if (emailVal && emailVal.trim()) {
                        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailRegex.test(emailVal.trim())) {
                            markFieldError($wrap, i18n.invalid_email || 'Please enter a valid email address.');
                            hasErrors = true;
                            return;
                        }
                    }
                }
            });

            return hasErrors;
        }

        /**
         * 5. Multi-Step Form Wizard Navigation Engine
         */
        function updateWizardProgress($form, stepNum, totalSteps) {
            var pct = Math.round((stepNum / totalSteps) * 100);
            $form.find('.usp-steps-progress-bar').css('width', pct + '%');

            $form.find('.usp-step-indicator-item').each(function() {
                var s = parseInt($(this).data('step'), 10);
                $(this).removeClass('is-active is-current is-completed');
                if (s < stepNum) {
                    $(this).addClass('is-completed');
                } else if (s === stepNum) {
                    $(this).addClass('is-active is-current');
                }
            });
        }

        // Stepper Navigation: Next Step Button
        $(document).on('click', '.usp-btn-step-next', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $form = $btn.closest('.usp-form');
            var $currStep = $btn.closest('.usp-form-step');
            var totalSteps = parseInt($form.find('.usp-steps-wrapper').data('total-steps'), 10) || 1;
            var currStepNum = parseInt($currStep.data('step'), 10);

            // Clear errors on current step
            $currStep.find('.usp-field-wrap').removeClass('has-error');
            $currStep.find('.usp-field-error').hide().text('');

            // Validate fields on current step
            var hasErrors = validateContainerFields($currStep);
            if (hasErrors) {
                var $firstError = $currStep.find('.usp-field-wrap.has-error').first();
                if ($firstError.length) {
                    $('html, body').animate({ scrollTop: $firstError.offset().top - 90 }, 250);
                }
                return;
            }

            var nextStepNum = currStepNum + 1;
            var $nextStep = $form.find('.usp-form-step[data-step="' + nextStepNum + '"]');

            if ($nextStep.length) {
                $currStep.removeClass('is-active').fadeOut(150, function() {
                    $nextStep.addClass('is-active').fadeIn(200);
                    updateWizardProgress($form, nextStepNum, totalSteps);
                    $('html, body').animate({ scrollTop: $form.closest('.usp-form-container').offset().top - 60 }, 250);
                    // Re-calculate or resize canvas if on next step
                    initSignaturePads($nextStep);
                    evaluateCalculationFormulas($form);
                });
            }
        });

        // Stepper Navigation: Prev Step Button
        $(document).on('click', '.usp-btn-step-prev', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $form = $btn.closest('.usp-form');
            var $currStep = $btn.closest('.usp-form-step');
            var totalSteps = parseInt($form.find('.usp-steps-wrapper').data('total-steps'), 10) || 1;
            var currStepNum = parseInt($currStep.data('step'), 10);

            var prevStepNum = currStepNum - 1;
            var $prevStep = $form.find('.usp-form-step[data-step="' + prevStepNum + '"]');

            if ($prevStep.length) {
                $currStep.removeClass('is-active').fadeOut(150, function() {
                    $prevStep.addClass('is-active').fadeIn(200);
                    updateWizardProgress($form, prevStepNum, totalSteps);
                    $('html, body').animate({ scrollTop: $form.closest('.usp-form-container').offset().top - 60 }, 250);
                });
            }
        });

        /**
         * 6. Digital Signature Pad Engine (HTML5 Touch & Stylus Canvas)
         */
        function initSignaturePads($scope) {
            $scope = $scope || $(document);

            $scope.find('.usp-signature-pad-wrap').each(function() {
                var $wrap = $(this);
                if ($wrap.data('sig-initialized')) return;
                $wrap.data('sig-initialized', true);

                var canvas = $wrap.find('.usp-signature-canvas')[0];
                if (!canvas) return;

                var $hidden = $wrap.find('.usp-signature-data');
                var $badge = $wrap.find('.usp-sig-status-badge');
                var $clearBtn = $wrap.find('.usp-sig-clear-btn');
                var penColor = $(canvas).attr('data-pen-color') || '#0f172a';
                var ctx = canvas.getContext('2d');

                function resizeCanvas() {
                    var rect = canvas.getBoundingClientRect();
                    var dpr = window.devicePixelRatio || 1;
                    if (rect.width === 0) return;

                    var oldVal = $hidden.val();
                    canvas.width = rect.width * dpr;
                    canvas.height = rect.height * dpr;
                    ctx.scale(dpr, dpr);
                    ctx.lineCap = 'round';
                    ctx.lineJoin = 'round';
                    ctx.lineWidth = 2.5;
                    ctx.strokeStyle = penColor;

                    if (oldVal && oldVal.indexOf('data:image/png;base64') === 0) {
                        var img = new Image();
                        img.onload = function() {
                            ctx.drawImage(img, 0, 0, rect.width, rect.height);
                        };
                        img.src = oldVal;
                    }
                }

                resizeCanvas();
                $(window).on('resize', function() { resizeCanvas(); });

                var isDrawing = false;
                var hasStrokes = false;

                function getPos(e) {
                    var rect = canvas.getBoundingClientRect();
                    var clientX = e.clientX;
                    var clientY = e.clientY;

                    if (e.touches && e.touches.length > 0) {
                        clientX = e.touches[0].clientX;
                        clientY = e.touches[0].clientY;
                    }

                    return {
                        x: clientX - rect.left,
                        y: clientY - rect.top
                    };
                }

                function startStroke(e) {
                    isDrawing = true;
                    var pos = getPos(e);
                    ctx.beginPath();
                    ctx.moveTo(pos.x, pos.y);
                    if (e.type === 'touchstart') {
                        e.preventDefault();
                    }
                }

                function moveStroke(e) {
                    if (!isDrawing) return;
                    var pos = getPos(e);
                    ctx.lineTo(pos.x, pos.y);
                    ctx.stroke();
                    hasStrokes = true;
                    if (e.type === 'touchmove') {
                        e.preventDefault();
                    }
                }

                function endStroke() {
                    if (!isDrawing) return;
                    isDrawing = false;
                    if (hasStrokes) {
                        var dataUrl = canvas.toDataURL('image/png');
                        $hidden.val(dataUrl).trigger('change');
                        $badge.removeClass('usp-sig-empty').addClass('usp-sig-signed').text('Signed ✓');
                        $wrap.closest('.usp-field-wrap').removeClass('has-error').find('.usp-field-error').hide();
                    }
                }

                // Mouse Events
                canvas.addEventListener('mousedown', startStroke, { passive: false });
                canvas.addEventListener('mousemove', moveStroke, { passive: false });
                window.addEventListener('mouseup', endStroke);

                // Touch Events with touch-action: none
                canvas.addEventListener('touchstart', startStroke, { passive: false });
                canvas.addEventListener('touchmove', moveStroke, { passive: false });
                window.addEventListener('touchend', endStroke);

                // Clear Button
                $clearBtn.on('click', function(e) {
                    e.preventDefault();
                    var rect = canvas.getBoundingClientRect();
                    ctx.clearRect(0, 0, rect.width, rect.height);
                    $hidden.val('').trigger('change');
                    hasStrokes = false;
                    $badge.removeClass('usp-sig-signed').addClass('usp-sig-empty').text('Awaiting signature');
                });
            });
        }

        /**
         * 7. Live Mathematical Calculation Engine
         */
        function evaluateCalculationFormulas($form) {
            $form.find('.usp-calculation-wrap').each(function() {
                var $wrap = $(this);
                var rawFormula = $wrap.attr('data-formula') || '';
                var prefix = $wrap.attr('data-prefix') || '';
                var suffix = $wrap.attr('data-suffix') || '';
                var decimals = parseInt($wrap.attr('data-decimals'), 10);
                if (isNaN(decimals)) decimals = 2;

                var $display = $wrap.find('.usp-calc-display-val');
                var $hidden = $wrap.find('.usp-calculation-hidden');

                if (!rawFormula.trim()) {
                    $display.text(prefix + (0).toFixed(decimals) + (suffix ? ' ' + suffix : ''));
                    $hidden.val((0).toFixed(decimals));
                    return;
                }

                // Replace all [field_key] with current numeric values
                var expr = rawFormula.replace(/\[([a-zA-Z0-9_]+)\]|\{([a-zA-Z0-9_]+)\}/g, function(match, key1, key2) {
                    var fieldKey = key1 || key2;
                    var $target = $form.find('[name="' + fieldKey + '"], [name="' + fieldKey + '[]"], [name^="' + fieldKey + '"]');
                    if (!$target.length) return '0';

                    var val = 0;
                    if ($target.is(':checkbox') || $target.is(':radio')) {
                        var checked = $target.filter(':checked');
                        checked.each(function() {
                            var v = parseFloat($(this).val());
                            val += isNaN(v) ? 1 : v;
                        });
                    } else if ($target.is('select')) {
                        var optVal = parseFloat($target.val());
                        val = isNaN(optVal) ? 0 : optVal;
                    } else {
                        var numVal = parseFloat(String($target.val() || '').replace(/[^0-9.-]/g, ''));
                        val = isNaN(numVal) ? 0 : numVal;
                    }
                    return String(val);
                });

                // Sanitize math string (only numbers, operators, parens, decimal point)
                expr = expr.replace(/[^0-9+\-*/().\s]/g, '');

                var computed = 0;
                try {
                    if (expr.trim()) {
                        computed = Function('"use strict"; return (' + expr + ')')();
                    }
                } catch(e) {
                    computed = 0;
                }

                if (isNaN(computed) || !isFinite(computed)) {
                    computed = 0;
                }

                var formattedNumber = Number(computed).toLocaleString('en-US', {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals
                });

                $display.text(prefix + formattedNumber + (suffix ? ' ' + suffix : ''));
                $hidden.val(Number(computed).toFixed(decimals));
            });
        }

        /**
         * 8. Dynamic URL Prefill / Query Parameter Autofill Engine
         */
        function prefillFromUrlParams() {
            if (!window.location.search) return;

            try {
                var params = new URLSearchParams(window.location.search);
                params.forEach(function(val, key) {
                    if (!val || !key) return;

                    $('.usp-form').each(function() {
                        var $form = $(this);
                        var $input = $form.find('[name="' + key + '"], [name="' + key + '[]"], [name="usp_' + key + '"], [data-field-name="' + key + '"] .usp-input');

                        if ($input.length) {
                            if ($input.is(':checkbox') || $input.is(':radio')) {
                                $input.filter('[value="' + val + '"]').prop('checked', true).trigger('change');
                            } else if ($input.is('select')) {
                                $input.val(val).trigger('change');
                            } else {
                                $input.val(val).trigger('input').trigger('change');
                            }
                        }
                    });
                });
            } catch(e) {}
        }

        /**
         * 9. Advanced AJAX Form Submission
         */
        $('.usp-form').on('submit', function(e) {
            e.preventDefault();

            var $form = $(this);
            if ($form.data('uspSubmitting')) return;
            var $submitBtn = $form.find('.usp-btn-submit');
            var $btnText = $submitBtn.find('.usp-btn-text');
            var $spinner = $submitBtn.find('.usp-spinner');
            var $messages = $form.find('.usp-form-messages');

            var i18n = (window.uspPublicData && window.uspPublicData.i18n) ? window.uspPublicData.i18n : {};
            var origBtnText = $btnText.text();

            // Clear previous alerts
            $form.find('.usp-field-wrap').removeClass('has-error');
            $form.find('.usp-field-error').hide().text('');
            $messages.hide().removeClass('usp-success usp-error').empty();

            // Validate entire form (or current step if wizard)
            var $targetContainer = $form.find('.usp-form-step.is-active').length ? $form.find('.usp-form-step.is-active') : $form;
            var hasErrors = validateContainerFields($targetContainer);

            // Pre-submission CAPTCHA check for Google reCAPTCHA v2
            if (!hasErrors && $form.find('.g-recaptcha').length > 0) {
                var recaptchaResp = (typeof grecaptcha !== 'undefined' && typeof grecaptcha.getResponse === 'function') ? grecaptcha.getResponse() : '';
                if (!recaptchaResp) {
                    var alertIcon = '<svg style="width:20px;height:20px;vertical-align:middle;margin-right:8px;fill:currentColor;" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>';
                    $messages.addClass('usp-error').html(alertIcon + ' Please check the "I am not a robot" security box.').fadeIn();
                    return;
                }
            }

            // Pre-submission CAPTCHA check for Cloudflare Turnstile
            if (!hasErrors && $form.find('.cf-turnstile').length > 0) {
                var turnstileToken = $form.find('[name="cf-turnstile-response"]').val();
                if (!turnstileToken) {
                    var alertIcon = '<svg style="width:20px;height:20px;vertical-align:middle;margin-right:8px;fill:currentColor;" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>';
                    $messages.addClass('usp-error').html(alertIcon + ' Please complete the Cloudflare security verification.').fadeIn();
                    return;
                }
            }

            if (hasErrors) {
                var errorIcon = '<svg style="width:20px;height:20px;vertical-align:middle;margin-right:8px;fill:currentColor;" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>';
                $messages.addClass('usp-error').html(errorIcon + ' Please correct the highlighted errors before submitting.').fadeIn();
                var $firstError = $form.find('.usp-field-wrap.has-error').first();
                if ($firstError.length) {
                    $('html, body').animate({ scrollTop: $firstError.offset().top - 100 }, 300);
                }
                return;
            }

            $form.data('uspSubmitting', true).attr('aria-busy', 'true');
            // Lock UI and show spinner
            $submitBtn.prop('disabled', true);
            $btnText.text(i18n.submitting || 'Submitting...');
            $spinner.show();

            var formData = new FormData($form[0]);

            $.ajax({
                url: window.uspPublicData.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                timeout: 30000,
                complete: function() { $form.data('uspSubmitting', false).removeAttr('aria-busy'); },
                success: function(response) {
                    $submitBtn.prop('disabled', false);
                    $btnText.text(origBtnText);
                    $spinner.hide();

                    if (response.success) {
                        var successIcon = '<svg style="width:22px;height:22px;vertical-align:middle;margin-right:8px;fill:currentColor;" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>';
                        var msgHtml = successIcon + ' ' + $('<div>').text(response.data.message || 'Thank you! Your submission has been received.').html();
                        $messages.addClass('usp-success').html(msgHtml).fadeIn();
                        $form[0].reset();
                        evaluateConditionalLogic($form);
                        evaluateCalculationFormulas($form);

                        // Reset signature pads
                        $form.find('.usp-signature-pad-wrap').each(function() {
                            var canvas = $(this).find('.usp-signature-canvas')[0];
                            if (canvas) {
                                var ctx = canvas.getContext('2d');
                                ctx.clearRect(0, 0, canvas.width, canvas.height);
                            }
                            $(this).find('.usp-signature-data').val('');
                            $(this).find('.usp-sig-status-badge').removeClass('usp-sig-signed').addClass('usp-sig-empty').text('Awaiting signature');
                        });

                        // Reset wizard back to step 1
                        if ($form.find('.usp-steps-wrapper').length) {
                            var totalSteps = parseInt($form.find('.usp-steps-wrapper').data('total-steps'), 10) || 1;
                            $form.find('.usp-form-step').removeClass('is-active').hide();
                            $form.find('.usp-form-step[data-step="1"]').addClass('is-active').show();
                            updateWizardProgress($form, 1, totalSteps);
                        }

                        // Optional redirect
                        if (response.data.redirect_url) {
                            setTimeout(function() {
                                try { var target = new URL(response.data.redirect_url, window.location.href); if (['http:', 'https:'].indexOf(target.protocol) !== -1) window.location.assign(target.href); } catch (error) {}
                            }, 1200);
                        }
                    } else {
                        var alertIcon = '<svg style="width:20px;height:20px;vertical-align:middle;margin-right:8px;fill:currentColor;" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>';
                        var errorMsg = (response.data && response.data.message) ? response.data.message : (i18n.generic_error || 'Something went wrong.');
                        $messages.addClass('usp-error').html(alertIcon + ' ' + $('<div>').text(errorMsg).html()).fadeIn();

                        // Field-level error highlights from server
                        if (response.data && response.data.errors) {
                            $.each(response.data.errors, function(fieldKey, fieldError) {
                                var $wrap = $form.find('.usp-field-wrap[data-field-name="' + fieldKey + '"]');
                                if ($wrap.length) {
                                    markFieldError($wrap, fieldError);
                                }
                            });
                        }
                    }
                },
                error: function() {
                    $submitBtn.prop('disabled', false);
                    $btnText.text(origBtnText);
                    $spinner.hide();
                    $messages.addClass('usp-error').text(i18n.generic_error || 'A network error occurred. Please try again.').fadeIn();
                }
            });
        });

        /**
         * 7. Interactive Star Rating Engine
         */
        function initStarRatings() {
            $(document).on('mouseenter', '.usp-star-rating-control .usp-star-btn', function() {
                var $btn = $(this);
                var $wrap = $btn.closest('.usp-star-rating-control');
                var val = parseInt($btn.data('val'), 10);
                $wrap.find('.usp-star-btn').each(function() {
                    var sVal = parseInt($(this).data('val'), 10);
                    $(this).css('color', sVal <= val ? '#f59e0b' : '#cbd5e1');
                });
            });

            $(document).on('mouseleave', '.usp-star-rating-control', function() {
                var $wrap = $(this);
                var curVal = parseInt($wrap.find('.usp-rating-hidden').val(), 10) || 0;
                $wrap.find('.usp-star-btn').each(function() {
                    var sVal = parseInt($(this).data('val'), 10);
                    $(this).css('color', sVal <= curVal ? '#f59e0b' : '#cbd5e1');
                });
            });

            $(document).on('click', '.usp-star-rating-control .usp-star-btn', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var $wrap = $btn.closest('.usp-star-rating-control');
                var val = parseInt($btn.data('val'), 10);
                $wrap.find('.usp-rating-hidden').val(val).trigger('change');
                $wrap.find('.usp-rating-num').text(val);
                $wrap.find('.usp-star-btn').each(function() {
                    var sVal = parseInt($(this).data('val'), 10);
                    $(this).toggleClass('active', sVal <= val).css('color', sVal <= val ? '#f59e0b' : '#cbd5e1');
                });
            });
        }

        /**
         * 8. Form View Impressions Tracking
         */
        function trackFormViews() {
            $('.usp-form[data-form-id]').each(function() {
                var $form = $(this);
                var formId = $form.data('form-id');
                if (formId && window.uspPublicData && window.uspPublicData.ajax_url) {
                    $.post(window.uspPublicData.ajax_url, {
                        action: 'usp_track_view',
                        form_id: formId
                    });
                }
            });
        }

        // Initialize Signature Pads, URL Prefill, Star Ratings, and Impression Tracking
        initSignaturePads();
        prefillFromUrlParams();
        initStarRatings();
        trackFormViews();
    });

})(jQuery);
