<?php

namespace App\Services\Store;

use App\Models\LicenseActivation;
use App\Models\ProductLicense;
use App\Models\ProductOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Licensing
{
    // Called only with the order locked, after the payment has been verified.
    public function issue(ProductOrder $order): ProductLicense
    {
        if ($order->renewal_license_id) {
            $license = ProductLicense::whereKey($order->renewal_license_id)
                ->where('user_id', $order->user_id)->where('digital_product_id', $order->digital_product_id)
                ->lockForUpdate()->firstOrFail();
            $base = $license->expires_at->isFuture() ? $license->expires_at->copy() : now();
            $license->update(['expires_at' => $base->addMonthsNoOverflow($order->term_months)]);
            return $license;
        }
        $key = 'USP-'.strtoupper(bin2hex(random_bytes(24)));
        return ProductLicense::firstOrCreate(['product_order_id' => $order->id], [
            'user_id' => $order->user_id, 'digital_product_id' => $order->digital_product_id,
            'key_hash' => hash('sha256', $key), 'key_secret' => $key, 'key_hint' => 'USP-…'.substr($key, -8),
            'site_limit' => $order->site_limit, 'expires_at' => now()->addMonthsNoOverflow($order->term_months),
        ]);
    }

    public function site(string $input): string
    {
        $parts = parse_url($input);
        if (! $parts || ! in_array(strtolower($parts['scheme'] ?? ''), ['http','https'], true)
            || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['query']) || isset($parts['fragment']) || preg_match('/[\x00-\x20\\\\]/', $input)) {
            throw ValidationException::withMessages(['url' => 'Use your WordPress home URL, without credentials, query, or fragment.']);
        }
        $host = strtolower(rtrim($parts['host'], '.'));
        $port = $parts['port'] ?? null;
        $path = rtrim($parts['path'] ?? '', '/');
        if (preg_match('#(?:^|/)(?:\.|\.\.)(?:/|$)#', rawurldecode($path))) {
            throw ValidationException::withMessages(['url' => 'Invalid WordPress home URL.']);
        }
        // HTTP→HTTPS moves retain their seat; subdomains and subdirectory installs do not.
        return 'https://'.$host.($port && ! in_array($port, [80,443], true) ? ':'.$port : '').$path;
    }

    public function request(array $data): array
    {
        $site = $this->site($data['url']);
        return DB::transaction(function () use ($data, $site) {
            $license = ProductLicense::where('key_hash', hash('sha256', $data['license']))
                ->whereHas('product', fn ($q) => $q->where('slug', $data['product']))->lockForUpdate()->first();
            $failure = fn ($error) => ['success' => false, 'license' => 'invalid', 'error' => $error];
            if (! $license) { return $failure('invalid_license'); }
            $activation = $license->activations()->where('site_hash', hash('sha256', $site))->first();
            if ($data['action'] === 'deactivate_license') {
                if ($activation && ! hash_equals($activation->token_secret, $data['token'] ?? '')) {
                    return $failure('invalid_activation');
                }
                $activation?->delete();
                return ['success' => true, 'license' => 'deactivated'];
            }
            if (! $license->usable()) { return $failure($license->status === 'revoked' ? 'revoked' : 'expired'); }
            if ($data['action'] === 'activate_license') {
                if (! $activation) {
                    if ($license->site_limit !== null && $license->activations()->count() >= $license->site_limit) {
                        return $failure('activation_limit_reached');
                    }
                    $activation = $license->activations()->create([
                        'site_url' => $site, 'site_hash' => hash('sha256', $site),
                        'token_secret' => bin2hex(random_bytes(32)), 'last_checked_at' => now(),
                    ]);
                }
            } elseif (! $activation || ! hash_equals($activation->token_secret, $data['token'] ?? '')) {
                return $failure('site_inactive');
            }
            $activation->update(['last_checked_at' => now()]);
            return [
                'success' => true, 'license' => 'valid', 'expires' => $license->expires_at->toIso8601String(),
                'site_count' => $license->activations()->count(), 'license_limit' => $license->site_limit ?? 0,
                'activation_token' => $activation->token_secret, 'activation_id' => $activation->id,
            ];
        });
    }
}
