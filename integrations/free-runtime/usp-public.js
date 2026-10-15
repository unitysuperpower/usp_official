/* Free form submission runtime. Pro input engines are not distributed here. */
(function ($) {
    'use strict';
    $(function () {
        function conditions($form) {
            $form.find('[data-conditions]').each(function () {
                const $wrap = $(this), rule = $wrap.data('conditions');
                if (!rule || !Array.isArray(rule.rules) || !rule.rules.length) return;
                const answers = rule.rules.map(function (r) {
                    const $inputs = $form.find(':input').filter(function () { return this.name === r.field || this.name === r.field + '[]'; });
                    const checked = $inputs.is(':checkbox,:radio');
                    const value = checked ? $inputs.filter(':checked').map(function () { return this.value.toLowerCase().trim(); }).get() : String($inputs.val() || '').toLowerCase().trim();
                    const target = String(r.value || '').toLowerCase().trim();
                    switch (r.operator || '==') {
                        case '!=': case 'not_equals': return checked ? !value.includes(target) : value !== target;
                        case 'contains': return value.includes(target);
                        case 'not_contains': return !value.includes(target);
                        case 'greater_than': return Number(value) > Number(target);
                        case 'less_than': return Number(value) < Number(target);
                        case 'empty': case 'is_empty': return !value.length;
                        case 'not_empty': case 'is_not_empty': return !!value.length;
                        default: return checked ? value.includes(target) : value === target;
                    }
                });
                const matched = rule.match === 'any' ? answers.some(Boolean) : answers.every(Boolean);
                const show = rule.action === 'hide' ? !matched : matched;
                $wrap.toggle(show).find(':input').prop('disabled', !show);
            });
        }
        $('.usp-form').each(function () { conditions($(this)); });
        $(document).on('input change', '.usp-form :input', function () { conditions($(this).closest('form')); });
        $('.usp-form').on('submit', function (event) {
            event.preventDefault();
            const form = this, $form = $(form), $button = $form.find('[type=submit]'), $message = $form.find('.usp-form-messages');
            if ($form.data('submitting')) return;
            conditions($form);
            if (!form.reportValidity()) return;
            $form.data('submitting', true); $button.prop('disabled', true);
            $message.removeClass('usp-error usp-success').text('Submitting…').show().attr('role','status');
            $form.find('.usp-field-wrap').removeClass('has-error').find('.usp-field-error').hide();
            $.ajax({url: window.uspPublicData.ajax_url, type:'POST', data:new FormData(form), processData:false, contentType:false, timeout:30000})
                .done(function (response) {
                    const data = response.data || {};
                    $message.addClass(response.success ? 'usp-success' : 'usp-error').text(data.message || 'Please try again.');
                    if (response.success) {
                        form.reset(); conditions($form);
                        if (data.redirect_url) {
                            const destination = new URL(data.redirect_url, location.href);
                            if (['http:', 'https:'].includes(destination.protocol)) location.assign(destination.href);
                        }
                    } else if (data.errors) {
                        Object.entries(data.errors).forEach(function ([name, message]) {
                            $form.find('[data-field-name]').filter(function () { return $(this).attr('data-field-name') === name; })
                                .addClass('has-error').find('.usp-field-error').text(message).show();
                        });
                    }
                    if (window.grecaptcha && $form.find('.g-recaptcha').length) window.grecaptcha.reset();
                    if (window.turnstile && $form.find('.cf-turnstile').length) window.turnstile.reset();
                })
                .fail(function () { $message.addClass('usp-error').text('Could not submit. Please check your connection and try again.'); })
                .always(function () { $form.data('submitting', false); $button.prop('disabled', false); });
        });
    });
})(jQuery);
