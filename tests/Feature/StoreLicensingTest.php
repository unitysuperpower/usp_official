<?php

use App\Models\DigitalProduct;
use App\Models\ProductLicense;
use App\Models\ProductOrder;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->withoutTwoFactor()->create();
    $this->product = DigitalProduct::create([
        'slug' => 'usp-form-builder',
        'name' => 'USP Form Builder',
        'summary' => 'Forms for WordPress',
        'published' => true,
    ]);
    $plan = $this->product->plans()->create([
        'name' => 'Pro',
        'price_minor' => 12000,
        'term_months' => 12,
        'site_limit' => 1,
        'published' => true,
    ]);
    $order = ProductOrder::create([
        'reference' => (string) Illuminate\Support\Str::uuid(),
        'user_id' => $this->user->id,
        'digital_product_id' => $this->product->id,
        'product_plan_id' => $plan->id,
        'product_name' => $this->product->name,
        'plan_name' => $plan->name,
        'usd_minor' => $plan->price_minor,
        'amount_minor' => 12000,
        'usd_pkr_rate' => 1,
        'currency' => 'PKR',
        'term_months' => $plan->term_months,
        'site_limit' => $plan->site_limit,
        'status' => 'paid',
        'quote_expires_at' => now()->addDay(),
        'paid_at' => now(),
    ]);
    $this->key = 'USP-'.strtoupper(bin2hex(random_bytes(24)));
    $this->license = ProductLicense::create([
        'product_order_id' => $order->id,
        'user_id' => $this->user->id,
        'digital_product_id' => $this->product->id,
        'key_hash' => hash('sha256', $this->key),
        'key_secret' => $this->key,
        'key_hint' => 'USP-…'.substr($this->key, -8),
        'site_limit' => 1,
        'status' => 'active',
        'expires_at' => now()->addYear(),
    ]);
});

test('license activations are product-scoped, token-bound, and limited by site count', function () {
    $site = 'https://customer.example.test';
    $payload = [
        'action' => 'activate_license',
        'product' => 'usp-form-builder',
        'license' => $this->key,
        'url' => $site,
    ];

    $activation = $this->postJson('/api/licenses/v1', $payload);
    $activation
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('license', 'valid')
        ->assertJsonStructure(['activation_token']);
    $token = $activation->json('activation_token');
    expect($token)->toBeString()->not->toBeEmpty();

    $this->postJson('/api/licenses/v1', array_merge($payload, ['token' => 'forged-token']))
        ->assertOk()
        ->assertJsonPath('success', false)
        ->assertJsonPath('error', 'invalid_activation');

    $this->postJson('/api/licenses/v1', array_merge($payload, ['token' => $token, 'action' => 'check_license']))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('license', 'valid');

    $this->postJson('/api/licenses/v1', array_merge($payload, ['product' => 'another-plugin']))
        ->assertOk()
        ->assertJsonPath('success', false)
        ->assertJsonPath('error', 'invalid_license');

    $this->postJson('/api/licenses/v1', array_merge($payload, ['url' => 'https://sub.customer.example.test']))
        ->assertOk()
        ->assertJsonPath('success', false)
        ->assertJsonPath('error', 'activation_limit_reached');
});
