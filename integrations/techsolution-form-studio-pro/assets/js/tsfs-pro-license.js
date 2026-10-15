/**
 * Pro License screen: activate, check and deactivate.
 *
 * @package TechSolution_Form_Studio_Pro
 */
jQuery(function ($) {
    'use strict';

    var __ = wp.i18n.__;
    var $message = $('.tsfs-pro-license-message');

    $('[data-license-action]').on('click', function () {
        var action = $(this).data('license-action');
        if (action === 'deactivate' && !window.confirm(__('Remove the license from this site? Pro fields keep working, but updates stop.', 'techsolution-form-studio-pro'))) {
            return;
        }
        var $buttons = $('[data-license-action]').prop('disabled', true);
        $message.text(__('Contacting usp.com.pk…', 'techsolution-form-studio-pro'));

        $.post(tsfsProLicense.ajax_url, {
            action: 'tsfs_pro_license',
            nonce: tsfsProLicense.nonce,
            license_action: action,
            license_key: $('#tsfs-pro-license-key').val()
        }).done(function (response) {
            $message.text(response.data && response.data.message ? response.data.message : '');
            if (response.success) {
                setTimeout(function () { window.location.reload(); }, 900);
            } else {
                $buttons.prop('disabled', false);
            }
        }).fail(function () {
            $message.text(__('Network error. Please try again.', 'techsolution-form-studio-pro'));
            $buttons.prop('disabled', false);
        });
    });
});
