/**
 * Frontend form engine for TechSolution Form Studio.
 * Conditional logic, validation, multi-step navigation, ratings and AJAX submission.
 *
 * Add-ons extend it through window.TSFSForms:
 *   TSFSForms.validators.push(function ($wrap, helpers) { return undefined | '' | 'error message'; });
 * and these jQuery events on document:
 *   tsfs:form-init [$form], tsfs:form-change [$form], tsfs:field-shown [$wrap],
 *   tsfs:step-shown [$step, $form], tsfs:form-reset [$form]
 *
 * @package TechSolution_Form_Studio
 */

(function($) {
    'use strict';

    var TSFSForms = window.TSFSForms = window.TSFSForms || {};
    TSFSForms.validators = TSFSForms.validators || [];

    function data() {
        return window.tsfsPublicData || {};
    }
    function t(key, fallback) {
        var i18n = data().i18n || {};
        return i18n[key] || fallback;
    }
    TSFSForms.t = t;

    $(document).ready(function() {

        /**
         * 1. Conditional Logic Engine
         */
        function evaluateConditionalLogic($form) {
            $form.find('.tsfs-field-wrap[data-conditions]').each(function() {
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
                        } else if (operator === 'empty') {
                            isRuleMet = currentVal.length === 0;
                        } else if (operator === 'not_empty') {
                            isRuleMet = currentVal.length > 0;
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
                    $fieldWrap.find('.tsfs-input, input, select, textarea').prop('disabled', false);
                    $(document).trigger('tsfs:field-shown', [$fieldWrap]);
                } else {
                    if ($fieldWrap.is(':visible')) {
                        $fieldWrap.stop(true, true).slideUp(200);
                    }
                    $fieldWrap.find('.tsfs-input, input, select, textarea').prop('disabled', true);
                    $fieldWrap.removeClass('has-error').find('.tsfs-field-error').hide();
                }
            });
        }

        function markFieldError($wrap, msg) {
            $wrap.addClass('has-error').find(':input').attr('aria-invalid', 'true');
            $wrap.find('.tsfs-field-error').text(msg).show();
        }

        TSFSForms.markFieldError = markFieldError;

        // Initialize conditional logic for every form on load
        $('.tsfs-form').each(function() {
            evaluateConditionalLogic($(this));
            $(document).trigger('tsfs:form-init', [$(this)]);
        });

        // Re-evaluate whenever any field changes
        $(document).on('input change', '.tsfs-form :input', function() {
            var $form = $(this).closest('.tsfs-form');
            evaluateConditionalLogic($form);
            $(document).trigger('tsfs:form-change', [$form]);
        });

        // Range slider value badge
        $(document).on('input change', '.tsfs-input-range[data-value-target]', function() {
            $('#' + $(this).attr('data-value-target')).text($(this).val());
        });

        // Instant Error clearing on typing/choice
        $(document).on('input change', '.tsfs-form :input', function() {
            var $wrap = $(this).closest('.tsfs-field-wrap');
            if ($wrap.hasClass('has-error')) {
                $wrap.removeClass('has-error').find(':input').removeAttr('aria-invalid');
                $wrap.find('.tsfs-field-error').fadeOut(150, function() { $(this).text('').hide(); });
            }
        });

        // Standard tel input (clean non-phone chars & restrict length)
        $(document).on('input', '.tsfs-input-tel-std', function() {
            var val = $(this).val();
            var clean = val.replace(/[^0-9+\-\s()]/g, '');
            if (clean !== val) {
                $(this).val(clean);
            }
        });

        /**
         * Validation Engine (current step or whole form)
         */
        function validateContainerFields($container) {
            var hasErrors = false;

            $container.find('.tsfs-field-wrap:visible').each(function() {
                var $wrap = $(this);
                var fieldName = $wrap.data('field-name');
                var isRequired = $wrap.find('.tsfs-required-mark').length > 0;
                var $input = $wrap.find(':input').not('button');

                if (!fieldName || $wrap.hasClass('tsfs-field-html') || $wrap.hasClass('tsfs-field-page_break') || ($input.length && $input.first().prop('disabled'))) {
                    return;
                }

                // Add-on validators: return undefined to skip, '' when valid, or an error message.
                for (var v = 0; v < TSFSForms.validators.length; v++) {
                    var result = TSFSForms.validators[v]($wrap, { isRequired: isRequired, t: t });
                    if (typeof result === 'string') {
                        if (result) {
                            markFieldError($wrap, result);
                            hasErrors = true;
                        }
                        return;
                    }
                }

                // Consent is a checkbox, not a text input.
                if ($wrap.hasClass('tsfs-field-consent')) {
                    if (isRequired && !$wrap.find('input.tsfs-consent-checkbox').is(':checked')) {
                        markFieldError($wrap, t('consent_required', 'Please check the consent box to continue.'));
                        hasErrors = true;
                    }
                    return;
                }

                if (isRequired) {
                    var missing = false;
                    if ($wrap.hasClass('tsfs-field-radio')) {
                        missing = $wrap.find('input[type="radio"]:checked').length === 0;
                    } else if ($wrap.hasClass('tsfs-field-checkbox')) {
                        missing = $wrap.find('input[type="checkbox"]:checked').length === 0;
                    } else if ($wrap.hasClass('tsfs-field-file')) {
                        var fileInput = $wrap.find('input[type="file"]')[0];
                        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                            markFieldError($wrap, t('required_file', 'Please select a file to upload.'));
                            hasErrors = true;
                            return;
                        }
                    } else if ($wrap.hasClass('tsfs-field-rating')) {
                        missing = !(parseInt($wrap.find('.tsfs-rating-hidden').val(), 10) > 0);
                    } else {
                        var val = $wrap.find('.tsfs-input').val();
                        missing = !val || !String(val).trim();
                    }
                    if (missing) {
                        markFieldError($wrap, t('required_field', 'This field is required.'));
                        hasErrors = true;
                        return;
                    }
                }

                // Standard phone field
                if ($wrap.hasClass('tsfs-field-tel')) {
                    var digits = ($wrap.find('input[type="tel"]').val() || '').replace(/\D/g, '');
                    if (digits.length > 0 && (digits.length < 7 || digits.length > 15)) {
                        markFieldError($wrap, t('invalid_phone', 'Please enter a valid phone number (between 7 and 15 digits; you entered %d).').replace('%d', digits.length));
                        hasErrors = true;
                        return;
                    }
                }

                // Email
                if ($wrap.hasClass('tsfs-field-email')) {
                    var emailVal = $wrap.find('input[type="email"]').val();
                    if (emailVal && emailVal.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal.trim())) {
                        markFieldError($wrap, t('invalid_email', 'Please enter a valid email address.'));
                        hasErrors = true;
                        return;
                    }
                }

                // Native constraints (number min/max, URL, CNIC pattern)
                var el = $wrap.find('.tsfs-input')[0];
                if (el && el.validity && !el.validity.valid && !el.validity.valueMissing) {
                    markFieldError($wrap, el.validationMessage || t('required_field', 'This field is required.'));
                    hasErrors = true;
                }
            });

            return hasErrors;
        }
        TSFSForms.validateContainerFields = validateContainerFields;

        /**
         * 5. Multi-Step Form Wizard Navigation Engine
         */
        function updateWizardProgress($form, stepNum, totalSteps) {
            var pct = Math.round((stepNum / totalSteps) * 100);
            $form.find('.tsfs-steps-progress-bar').css('width', pct + '%');

            $form.find('.tsfs-step-indicator-item').each(function() {
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
        $(document).on('click', '.tsfs-btn-step-next', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $form = $btn.closest('.tsfs-form');
            var $currStep = $btn.closest('.tsfs-form-step');
            var totalSteps = parseInt($form.find('.tsfs-steps-wrapper').data('total-steps'), 10) || 1;
            var currStepNum = parseInt($currStep.data('step'), 10);

            // Clear errors on current step
            $currStep.find('.tsfs-field-wrap').removeClass('has-error');
            $currStep.find('.tsfs-field-error').hide().text('');

            // Validate fields on current step
            var hasErrors = validateContainerFields($currStep);
            if (hasErrors) {
                var $firstError = $currStep.find('.tsfs-field-wrap.has-error').first();
                if ($firstError.length) {
                    $('html, body').animate({ scrollTop: $firstError.offset().top - 90 }, 250);
                }
                return;
            }

            var nextStepNum = currStepNum + 1;
            var $nextStep = $form.find('.tsfs-form-step[data-step="' + nextStepNum + '"]');

            if ($nextStep.length) {
                $currStep.removeClass('is-active').fadeOut(150, function() {
                    $nextStep.addClass('is-active').fadeIn(200);
                    updateWizardProgress($form, nextStepNum, totalSteps);
                    $('html, body').animate({ scrollTop: $form.closest('.tsfs-form-container').offset().top - 60 }, 250);
                    $(document).trigger('tsfs:step-shown', [$nextStep, $form]);
                });
            }
        });

        // Stepper Navigation: Prev Step Button
        $(document).on('click', '.tsfs-btn-step-prev', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $form = $btn.closest('.tsfs-form');
            var $currStep = $btn.closest('.tsfs-form-step');
            var totalSteps = parseInt($form.find('.tsfs-steps-wrapper').data('total-steps'), 10) || 1;
            var currStepNum = parseInt($currStep.data('step'), 10);

            var prevStepNum = currStepNum - 1;
            var $prevStep = $form.find('.tsfs-form-step[data-step="' + prevStepNum + '"]');

            if ($prevStep.length) {
                $currStep.removeClass('is-active').fadeOut(150, function() {
                    $prevStep.addClass('is-active').fadeIn(200);
                    updateWizardProgress($form, prevStepNum, totalSteps);
                    $(document).trigger('tsfs:step-shown', [$prevStep, $form]);
                    $('html, body').animate({ scrollTop: $form.closest('.tsfs-form-container').offset().top - 60 }, 250);
                });
            }
        });

        /**
         * 8. Dynamic URL Prefill / Query Parameter Autofill Engine
         */
        function prefillFromUrlParams() {
            if (!window.location.search) return;

            try {
                var params = new URLSearchParams(window.location.search);
                params.forEach(function(val, key) {
                    if (!val || !key) return;

                    $('.tsfs-form').each(function() {
                        var $form = $(this);
                        var $input = $form.find('[name="' + key + '"], [name="' + key + '[]"], [name="tsfs_' + key + '"], [data-field-name="' + key + '"] .tsfs-input');

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
         * AJAX Form Submission
         */
        var alertIcon = '<svg aria-hidden="true" style="width:20px;height:20px;vertical-align:middle;margin-right:8px;fill:currentColor;" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>';
        var successIcon = '<svg aria-hidden="true" style="width:22px;height:22px;vertical-align:middle;margin-right:8px;fill:currentColor;" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>';

        function showMessage($messages, type, text) {
            $messages.removeClass('tsfs-success tsfs-error').addClass(type === 'success' ? 'tsfs-success' : 'tsfs-error')
                .attr('role', type === 'success' ? 'status' : 'alert')
                .html((type === 'success' ? successIcon : alertIcon) + ' ' + $('<div>').text(text).html()).fadeIn();
        }

        // Fetch a fresh reCAPTCHA v3 token right before sending (tokens expire after two minutes).
        // Cached pages can carry an expired security token, so fetch a fresh one just before
        // submitting. If that request fails, submit with the token already in the form.
        function withFreshNonce($form, callback) {
            var formId = $form.find('[name="form_id"]').val();
            if (!formId || !data().ajax_url) {
                callback();
                return;
            }
            $.ajax({
                url: data().ajax_url,
                type: 'POST',
                data: { action: 'tsfs_refresh_nonce', form_id: formId },
                timeout: 15000,
                cache: false
            }).done(function(response) {
                if (response && response.success && response.data && response.data.nonce) {
                    $form.find('[name="tsfs_nonce"]').val(response.data.nonce);
                }
            }).always(callback);
        }

        function withRecaptchaV3($form, callback) {
            var siteKey = data().recaptcha_v3_key;
            var $token = $form.find('.tsfs-recaptcha-v3-token');
            if (!siteKey || !$token.length || typeof window.grecaptcha === 'undefined') {
                callback();
                return;
            }
            window.grecaptcha.ready(function() {
                window.grecaptcha.execute(siteKey, { action: 'submit' }).then(function(token) {
                    $token.val(token);
                    callback();
                }, callback);
            });
        }

        $(document).on('submit', '.tsfs-form', function(e) {
            e.preventDefault();

            var $form = $(this);
            if ($form.data('tsfsSubmitting')) return;
            var $submitBtn = $form.find('.tsfs-btn-submit');
            var $btnText = $submitBtn.find('.tsfs-btn-text');
            var $spinner = $submitBtn.find('.tsfs-spinner');
            var $messages = $form.find('.tsfs-form-messages');
            var origBtnText = $btnText.text();

            // Clear previous alerts
            $form.find('.tsfs-field-wrap').removeClass('has-error');
            $form.find('.tsfs-field-error').hide().text('');
            $messages.hide().removeClass('tsfs-success tsfs-error').empty();

            // Validate entire form (or current step if wizard)
            var $targetContainer = $form.find('.tsfs-form-step.is-active').length ? $form.find('.tsfs-form-step.is-active') : $form;
            if (validateContainerFields($targetContainer)) {
                showMessage($messages, 'error', t('fix_errors', 'Please correct the highlighted errors before submitting.'));
                var $firstError = $form.find('.tsfs-field-wrap.has-error').first();
                if ($firstError.length) {
                    $('html, body').animate({ scrollTop: $firstError.offset().top - 100 }, 300);
                    $firstError.find(':input:visible').first().trigger('focus');
                }
                return;
            }

            if ($form.find('.g-recaptcha').length && typeof window.grecaptcha !== 'undefined' && typeof window.grecaptcha.getResponse === 'function' && !window.grecaptcha.getResponse()) {
                showMessage($messages, 'error', t('captcha_v2', 'Please check the "I am not a robot" box.'));
                return;
            }
            if ($form.find('.cf-turnstile').length && !$form.find('[name="cf-turnstile-response"]').val()) {
                showMessage($messages, 'error', t('captcha_turnstile', 'Please complete the security verification.'));
                return;
            }

            $form.data('tsfsSubmitting', true).attr('aria-busy', 'true');
            $submitBtn.prop('disabled', true);
            $btnText.text(t('submitting', 'Submitting...'));
            $spinner.show();

            function finish() {
                $form.data('tsfsSubmitting', false).removeAttr('aria-busy');
                $submitBtn.prop('disabled', false);
                $btnText.text(origBtnText);
                $spinner.hide();
            }

            withRecaptchaV3($form, function() {
              withFreshNonce($form, function() {
                $.ajax({
                    url: data().ajax_url,
                    type: 'POST',
                    data: new FormData($form[0]),
                    processData: false,
                    contentType: false,
                    timeout: 60000
                }).done(function(response) {
                    finish();
                    if (response && response.success) {
                        showMessage($messages, 'success', response.data.message || t('success', 'Thank you! Your submission has been received.'));
                        $form[0].reset();
                        $form.find('.tsfs-input-range[data-value-target]').trigger('input');
                        evaluateConditionalLogic($form);

                        // Reset wizard back to step 1
                        if ($form.find('.tsfs-steps-wrapper').length) {
                            var totalSteps = parseInt($form.find('.tsfs-steps-wrapper').data('total-steps'), 10) || 1;
                            $form.find('.tsfs-form-step').removeClass('is-active').hide();
                            $form.find('.tsfs-form-step[data-step="1"]').addClass('is-active').show();
                            updateWizardProgress($form, 1, totalSteps);
                        }
                        if (window.grecaptcha && $form.find('.g-recaptcha').length && typeof window.grecaptcha.reset === 'function') {
                            window.grecaptcha.reset();
                        }
                        if (window.turnstile && $form.find('.cf-turnstile').length && typeof window.turnstile.reset === 'function') {
                            window.turnstile.reset();
                        }
                        $(document).trigger('tsfs:form-reset', [$form]);

                        if (response.data.redirect_url) {
                            setTimeout(function() {
                                try {
                                    var target = new URL(response.data.redirect_url, window.location.href);
                                    if (['http:', 'https:'].indexOf(target.protocol) !== -1) window.location.assign(target.href);
                                } catch (error) {}
                            }, 1200);
                        }
                    } else {
                        var errorMsg = (response && response.data && response.data.message) ? response.data.message : t('generic_error', 'Something went wrong. Please try again.');
                        showMessage($messages, 'error', errorMsg);

                        // Field-level error highlights from server; open the step that contains the first one.
                        if (response && response.data && response.data.errors) {
                            $.each(response.data.errors, function(fieldKey, fieldError) {
                                var $wrap = $form.find('.tsfs-field-wrap[data-field-name="' + fieldKey + '"]');
                                if ($wrap.length) markFieldError($wrap, fieldError);
                            });
                            var $errStep = $form.find('.tsfs-field-wrap.has-error').first().closest('.tsfs-form-step');
                            if ($errStep.length && !$errStep.hasClass('is-active')) {
                                $form.find('.tsfs-form-step').removeClass('is-active').hide();
                                $errStep.addClass('is-active').show();
                                updateWizardProgress($form, parseInt($errStep.data('step'), 10), parseInt($form.find('.tsfs-steps-wrapper').data('total-steps'), 10) || 1);
                            }
                        }
                    }
                }).fail(function() {
                    finish();
                    showMessage($messages, 'error', t('network_error', 'A network error occurred. Please try again.'));
                });
              });
            });
        });

        /**
         * 7. Interactive Star Rating Engine
         */
        function initStarRatings() {
            $(document).on('mouseenter', '.tsfs-star-rating-control .tsfs-star-btn', function() {
                var $btn = $(this);
                var $wrap = $btn.closest('.tsfs-star-rating-control');
                var val = parseInt($btn.data('val'), 10);
                $wrap.find('.tsfs-star-btn').each(function() {
                    var sVal = parseInt($(this).data('val'), 10);
                    $(this).css('color', sVal <= val ? '#f59e0b' : '#cbd5e1');
                });
            });

            $(document).on('mouseleave', '.tsfs-star-rating-control', function() {
                var $wrap = $(this);
                var curVal = parseInt($wrap.find('.tsfs-rating-hidden').val(), 10) || 0;
                $wrap.find('.tsfs-star-btn').each(function() {
                    var sVal = parseInt($(this).data('val'), 10);
                    $(this).css('color', sVal <= curVal ? '#f59e0b' : '#cbd5e1');
                });
            });

            $(document).on('click', '.tsfs-star-rating-control .tsfs-star-btn', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var $wrap = $btn.closest('.tsfs-star-rating-control');
                var val = parseInt($btn.data('val'), 10);
                $wrap.find('.tsfs-rating-hidden').val(val).trigger('change');
                $wrap.find('.tsfs-rating-num').text(val);
                $wrap.find('.tsfs-star-btn').attr('aria-pressed', 'false').filter(function() { return parseInt($(this).data('val'), 10) === val; }).attr('aria-pressed', 'true');
                $wrap.find('.tsfs-star-btn').each(function() {
                    var sVal = parseInt($(this).data('val'), 10);
                    $(this).toggleClass('active', sVal <= val).css('color', sVal <= val ? '#f59e0b' : '#cbd5e1');
                });
            });
        }

        /**
         * 8. Form View Impressions Tracking
         */
        function trackFormViews() {
            $('.tsfs-form[data-form-id]').each(function() {
                var $form = $(this);
                var formId = $form.data('form-id');
                if (formId && window.tsfsPublicData && window.tsfsPublicData.ajax_url) {
                    $.post(window.tsfsPublicData.ajax_url, {
                        action: 'tsfs_track_view',
                        form_id: formId
                    });
                }
            });
        }

        // Initialize URL Prefill, Star Ratings, and Impression Tracking
        prefillFromUrlParams();
        initStarRatings();
        trackFormViews();
    });

})(jQuery);
