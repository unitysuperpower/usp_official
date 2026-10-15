/**
 * Settings screen: tabs, CAPTCHA provider switcher, saving, and upload protection redeploy.
 *
 * @package TechSolution_Form_Studio
 */
jQuery(function ($) {
    'use strict';

    var __ = wp.i18n.__;

    // Tab Switching
    $('.tsfs-hub-tab').on('click', function () {
        var tab = $(this).data('tab');
        $('.tsfs-hub-tab').removeClass('active').css({ color: '#64748b', 'border-bottom-color': 'transparent' });
        $(this).addClass('active').css({ color: '#4f46e5', 'border-bottom-color': '#4f46e5' });
        $('.tsfs-hub-panel').hide();
        $('#tsfs-panel-' + tab).show();
    });

    // Provider Selector Change
    $('input[name="anti_spam_provider"]').on('change', function () {
        var val = $(this).val();
        $('.tsfs-provider-card').css({ 'border-color': '#cbd5e1', background: '#fff' });
        $(this).closest('.tsfs-provider-card').css({ 'border-color': '#4f46e5', background: '#eef2ff' });

        if (val === 'recaptcha_v2' || val === 'recaptcha_v3') {
            $('#tsfs-recaptcha-keys-wrap').slideDown(150);
            $('#tsfs-turnstile-keys-wrap').slideUp(150);
            $('#tsfs-recaptcha-v3-score-wrap')[val === 'recaptcha_v3' ? 'slideDown' : 'slideUp'](150);
        } else if (val === 'turnstile') {
            $('#tsfs-turnstile-keys-wrap').slideDown(150);
            $('#tsfs-recaptcha-keys-wrap').slideUp(150);
        } else {
            $('#tsfs-recaptcha-keys-wrap').slideUp(150);
            $('#tsfs-turnstile-keys-wrap').slideUp(150);
        }
    });

    function checked(id) {
        return $('#' + id).is(':checked') ? 1 : 0;
    }

    // Save settings
    $('#tsfs-save-security-settings-btn').on('click', function () {
        var $btn = $(this);
        var $spinner = $btn.find('.spinner');
        $btn.prop('disabled', true);
        $spinner.addClass('is-active');

        $.post(tsfsAdminData.ajax_url, {
            action: 'tsfs_save_security_settings',
            nonce: tsfsAdminData.nonce,
            anti_spam_provider: $('input[name="anti_spam_provider"]:checked').val() || 'none',
            recaptcha_site_key: $('#tsfs_recaptcha_site_key').val(),
            recaptcha_secret_key: $('#tsfs_recaptcha_secret_key').val(),
            recaptcha_v3_score: $('#tsfs_recaptcha_v3_score').val(),
            turnstile_site_key: $('#tsfs_turnstile_site_key').val(),
            turnstile_secret_key: $('#tsfs_turnstile_secret_key').val(),
            enable_time_gate: checked('tsfs_enable_time_gate'),
            time_gate_seconds: $('#tsfs_time_gate_seconds').val(),
            enable_rate_limiting: checked('tsfs_enable_rate_limiting'),
            rate_limit_max: $('#tsfs_rate_limit_max').val(),
            rate_limit_window: $('#tsfs_rate_limit_window').val(),
            blacklisted_ips: $('#tsfs_blacklisted_ips').val(),
            blocked_keywords: $('#tsfs_blocked_keywords').val(),
            enable_upload_hardening: 1,
            enable_mime_sniffing: checked('tsfs_enable_mime_sniffing'),
            enable_csv_injection_shield: checked('tsfs_enable_csv_injection_shield'),
            store_ip_address: checked('tsfs_store_ip_address'),
            store_user_agent: checked('tsfs_store_user_agent'),
            delete_data_on_uninstall: checked('tsfs_delete_data_on_uninstall'),
            entry_retention_days: $('#tsfs_entry_retention_days').val()
        }, function (response) {
            $btn.prop('disabled', false);
            $spinner.removeClass('is-active');
            var message = response.data && response.data.message;
            if (response.success) {
                TSFSModal.toast(message || __('Settings saved.', 'techsolution-form-studio'), 'success');
            } else {
                TSFSModal.toast(message || __('Error saving settings.', 'techsolution-form-studio'), 'error');
            }
        }).fail(function () {
            $btn.prop('disabled', false);
            $spinner.removeClass('is-active');
            TSFSModal.toast(__('Network error while saving settings.', 'techsolution-form-studio'), 'error');
        });
    });

    // Re-deploy upload protection files
    $('#tsfs-redeploy-shields-btn').on('click', function () {
        var $btn = $(this);
        $btn.prop('disabled', true);
        $.post(tsfsAdminData.ajax_url, { action: 'tsfs_redeploy_shields', nonce: tsfsAdminData.nonce }, function (response) {
            $btn.prop('disabled', false);
            if (response.success) {
                TSFSModal.toast(response.data.message, 'success');
                window.location.reload();
            } else {
                TSFSModal.toast(response.data && response.data.message ? response.data.message : __('Could not redeploy upload protection.', 'techsolution-form-studio'), 'error');
            }
        }).fail(function () {
            $btn.prop('disabled', false);
            TSFSModal.toast(__('Network error while redeploying upload protection.', 'techsolution-form-studio'), 'error');
        });
    });
});
