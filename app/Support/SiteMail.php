<?php

namespace App\Support;

use App\Mail\NotificationMail;
use App\Models\MailSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Applies the SMTP settings saved in Admin → Settings → Email and sends site emails.
 * Emails are sent after the surrounding database transaction commits, and a delivery
 * failure is logged without interrupting the order, enrollment or request.
 */
class SiteMail
{
    private static ?MailSetting $settings = null;

    public static function settings(): MailSetting
    {
        if (self::$settings === null) {
            try {
                self::$settings = MailSetting::current(); // a missing table (before migrating) falls back below
            } catch (\Throwable) {
                self::$settings = new MailSetting;
            }
        }

        return self::$settings;
    }

    public static function forget(): void
    {
        self::$settings = null;
    }

    /** Override the mail configuration with the saved SMTP settings when they are enabled. */
    public static function apply(?MailSetting $settings = null): void
    {
        $settings ??= self::settings();
        if (! $settings->enabled || ! $settings->host) {
            return;
        }
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.scheme' => $settings->encryption === 'ssl' ? 'smtps' : 'smtp',
            'mail.mailers.smtp.host' => $settings->host,
            'mail.mailers.smtp.port' => $settings->port,
            'mail.mailers.smtp.username' => $settings->username,
            'mail.mailers.smtp.password' => $settings->password,
            'mail.mailers.smtp.require_tls' => $settings->encryption === 'tls',
            'mail.mailers.smtp.auto_tls' => $settings->encryption !== 'none',
            'mail.mailers.smtp.timeout' => 15,
            'mail.mailers.smtp.url' => null,
        ]);
        if ($settings->from_address) {
            config(['mail.from.address' => $settings->from_address, 'mail.from.name' => $settings->from_name ?: config('app.name')]);
        }
        app('mail.manager')->forgetMailers();
    }

    public static function adminAddress(): ?string
    {
        return self::settings()->admin_address ?: self::settings()->from_address;
    }

    /** Queue an email of the given type for delivery after the current transaction commits. */
    public static function send(?string $to, string $type, NotificationMail $mail): void
    {
        if (! $to || ! filter_var($to, FILTER_VALIDATE_EMAIL) || ! self::settings()->wants($type)) {
            return;
        }
        DB::afterCommit(function () use ($to, $type, $mail) {
            try {
                Mail::to($to)->send($mail);
            } catch (\Throwable $e) {
                Log::warning('Site email could not be sent', ['type' => $type, 'to' => $to, 'error' => $e->getMessage()]);
            }
        });
    }
}
