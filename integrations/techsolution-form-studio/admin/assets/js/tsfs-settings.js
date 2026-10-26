/**
 * Settings screen: tabs, CAPTCHA provider switcher, saving, and upload protection redeploy.
 *
 * @package TechSolution_Form_Studio
 */
jQuery(function ($) {
    'use strict';

    var __ = wp.i18n.__;

    // Section switching (left menu). The selected section is remembered in the URL hash.
    function showTab(tab) {
        var $tab = $('.tsfs-hub-tab[data-tab="' + tab + '"]');
        if (!$tab.length) {
            return;
        }
        $('.tsfs-hub-tab').removeClass('active').attr('aria-selected', 'false');
        $tab.addClass('active').attr('aria-selected', 'true');
        $('.tsfs-hub-panel').removeClass('active').prop('hidden', true);
        $('#tsfs-panel-' + tab).addClass('active').prop('hidden', false);
    }
    $('.tsfs-hub-tab').on('click', function () {
        var tab = $(this).data('tab');
        showTab(tab);
        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', '#' + tab);
        }
    });
    if (window.location.hash) {
        showTab(window.location.hash.slice(1));
    }

    // CAPTCHA provider: highlight the choice and show only the keys it needs.
    $('input[name="anti_spam_provider"]').on('change', function () {
        var val = $(this).val();
        $('.tsfs-provider-card').removeClass('is-selected');
        $(this).closest('.tsfs-provider-card').addClass('is-selected');
        $('#tsfs-recaptcha-keys-wrap').prop('hidden', val !== 'recaptcha_v2' && val !== 'recaptcha_v3');
        $('#tsfs-recaptcha-v3-score-wrap').prop('hidden', val !== 'recaptcha_v3');
        $('#tsfs-turnstile-keys-wrap').prop('hidden', val !== 'turnstile');
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
