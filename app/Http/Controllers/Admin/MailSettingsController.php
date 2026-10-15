<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\NotificationMail;
use App\Models\MailSetting;
use App\Support\ReactPage;
use App\Support\SiteMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MailSettingsController extends Controller
{
    public function show()
    {
        $settings = MailSetting::current();

        return ReactPage::render('admin.settings.email', [
            'settings' => $settings->only(['enabled', 'host', 'port', 'encryption', 'username', 'from_address', 'from_name', 'admin_address', 'last_tested_at']) + [
                'has_password' => filled($settings->getRawOriginal('password')),
                'notifications' => collect(MailSetting::TYPES)->mapWithKeys(fn ($label, $key) => [$key => $settings->wants($key)]),
            ],
            'types' => MailSetting::TYPES,
            'mailer' => config('mail.default'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'enabled' => 'nullable|boolean',
            'host' => ['nullable', 'required_if:enabled,1', 'string', 'max:255', 'regex:/^[A-Za-z0-9.-]+$/'],
            'port' => 'required|integer|min:1|max:65535',
            'encryption' => ['required', Rule::in(['tls', 'ssl', 'none'])],
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:500',
            'from_address' => 'nullable|required_if:enabled,1|email|max:255',
            'from_name' => 'nullable|string|max:120',
            'admin_address' => 'nullable|email|max:255',
            'notifications' => 'nullable|array',
            'current_password' => 'required|current_password',
        ], ['host.required_if' => 'Enter the SMTP server to enable sending.', 'from_address.required_if' => 'Enter the address emails are sent from.']);

        $settings = MailSetting::current();
        $settings->fill([
            'enabled' => $request->boolean('enabled'),
            'host' => $data['host'] ?? null,
            'port' => $data['port'],
            'encryption' => $data['encryption'],
            'username' => $data['username'] ?? null,
            'from_address' => $data['from_address'] ?? null,
            'from_name' => $data['from_name'] ?? null,
            'admin_address' => $data['admin_address'] ?? null,
            'notifications' => collect(MailSetting::TYPES)->mapWithKeys(fn ($label, $key) => [$key => ! empty($data['notifications'][$key])])->all(),
        ]);
        // A blank password keeps the saved one; tick "Remove password" to clear it.
        if ($request->boolean('clear_password')) {
            $settings->password = null;
        } elseif (filled($data['password'] ?? null)) {
            $settings->password = $data['password'];
        }
        $settings->id = 1;
        $settings->save();
        SiteMail::forget();

        return back()->with('success', $settings->enabled ? 'Email settings saved. Send a test email to confirm delivery.' : 'Email settings saved. Sending is turned off, so emails are written to the log only.');
    }

    public function test(Request $request)
    {
        $data = $request->validate(['test_to' => 'required|email|max:255']);
        $settings = MailSetting::current();
        if (! $settings->enabled || ! $settings->host) {
            throw ValidationException::withMessages(['test_to' => 'Turn on sending and save your SMTP settings first.']);
        }
        SiteMail::apply($settings);
        try {
            Mail::to($data['test_to'])->send(new NotificationMail(
                subjectLine: 'Test email from '.($settings->from_name ?: config('app.name')),
                heading: 'Your email settings work',
                lines: ['This test was sent from Admin → Settings → Email. Order, enrollment and request emails will be delivered the same way.'],
                details: ['SMTP server' => $settings->host.':'.$settings->port, 'Sent at' => now()->format('j F Y, g:i a')],
            ));
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['test_to' => 'The test email could not be sent: '.mb_substr($e->getMessage(), 0, 300)]);
        }
        $settings->forceFill(['last_tested_at' => now()])->save();

        return back()->with('success', 'Test email sent to '.$data['test_to'].'. Check the inbox (and spam folder).');
    }
}
