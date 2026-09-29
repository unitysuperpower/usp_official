<?php

namespace App\Http\Controllers\Education;

use App\Http\Controllers\Controller;
use App\Models\CoursePayment;
use App\Models\EducationSetting;
use App\Services\JazzCashService;
use App\Support\Education;
use App\Support\ReactPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PaymentSettingsController extends Controller
{
    public function edit(JazzCashService $gateway)
    {
        $settings = EducationSetting::current();
        $config = EducationSetting::gatewayConfig();

        return ReactPage::render('admin.education.settings', [
            'manualMethods' => $settings?->manual_methods ?? config('education.payment_methods'),
            'gateway' => collect($config)->except(['password', 'integrity_salt'])->all(),
            'hasPassword' => filled($config['password']), 'hasSalt' => filled($config['integrity_salt']),
            'ready' => $gateway->configured(),
            'callbackPath' => '/education/jazzcash/return',
        ])->withHeaders(['Cache-Control' => 'private, no-store']);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|current_password',
            'bank_transfer' => 'nullable|string|max:5000', 'jazzcash' => 'nullable|string|max:5000', 'easypaisa' => 'nullable|string|max:5000',
            'enabled' => 'required|boolean', 'environment' => ['required', Rule::in(['sandbox', 'live'])],
            'merchant_id' => 'nullable|string|max:100', 'jazzcash_password' => 'nullable|string|max:255', 'jazzcash_integrity_salt' => 'nullable|string|max:255',
            'checkout_url' => 'nullable|url:https|max:2000', 'return_url' => 'nullable|url:https|max:2000',
        ]);
        EducationSetting::firstOrCreate(['id' => 1]);
        DB::transaction(function () use ($data, $request) {
            $settings = EducationSetting::whereKey(1)->lockForUpdate()->firstOrFail();
            $previous = array_replace(config('jazzcash'), $settings->gateway ?? []);
            $gateway = [
                'enabled' => $request->boolean('enabled'), 'environment' => $data['environment'],
                'merchant_id' => $data['merchant_id'] ?? null, 'checkout_url' => $data['checkout_url'] ?? null, 'return_url' => $data['return_url'] ?? null,
                'password' => filled($data['jazzcash_password'] ?? null) ? $data['jazzcash_password'] : $previous['password'],
                'integrity_salt' => filled($data['jazzcash_integrity_salt'] ?? null) ? $data['jazzcash_integrity_salt'] : $previous['integrity_salt'],
            ];
            $host = $gateway['environment'] === 'live' ? 'payments.jazzcash.com.pk' : 'sandbox.jazzcash.com.pk';
            Education::ensure(! $gateway['checkout_url'] || parse_url($gateway['checkout_url'], PHP_URL_HOST) === $host, 'Use the official JazzCash checkout host matching this environment.', 'checkout_url');
            Education::ensure(! $gateway['return_url'] || parse_url($gateway['return_url'], PHP_URL_PATH) === '/education/jazzcash/return', 'The callback URL must end with /education/jazzcash/return.', 'return_url');
            if ($gateway['enabled']) {
                Education::ensure(collect($gateway)->except('enabled')->every(fn ($value) => filled($value)), 'Enter all merchant credentials and HTTPS URLs before enabling checkout.', 'enabled');
                Education::ensure(! app()->isProduction() || $gateway['environment'] === 'live', 'Production requires live merchant credentials.', 'environment');
            }
            $changed = collect($gateway)->except('enabled')->all() !== collect($previous)->except('enabled')->all();
            Education::ensure(! $changed || ! CoursePayment::where('method', 'jazzcash_online')->whereIn('status', ['pending', 'review_required'])->exists(), 'Reconcile pending JazzCash transactions before changing merchant details or environment.', 'merchant_id');
            $manual = config('education.payment_methods');
            foreach ($manual as $key => &$method) $method['instructions'] = $data[$key] ?? '';
            $settings->update(['gateway' => $gateway, 'manual_methods' => $manual]);
        });

        return back()->with('success', 'Payment settings saved. Secret fields remain hidden; blank secret fields preserve saved values.');
    }
}
