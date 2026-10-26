<?php

use App\Models\DigitalProduct;
use App\Models\ProductLicense;
use App\Models\ProductOrder;
use App\Models\ProductRelease;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('public');
    $this->admin = User::factory()->withoutTwoFactor()->create(['is_admin' => true]);
    $this->product = DigitalProduct::create(['slug' => 'demo-plugin', 'name' => 'Demo Plugin', 'summary' => 'A demo', 'published' => true]);
});

function productPayload(array $overrides = []): array
{
    return array_replace_recursive(['name' => 'Demo Plugin', 'slug' => 'demo-plugin', 'summary' => 'A demo', 'published' => 1], $overrides);
}

it('saves the product details, icon, screenshots and FAQ', function () {
    $this->actingAs($this->admin)->put("/admin/store/products/{$this->product->id}", productPayload([
        'tagline' => 'Forms, faster.', 'category' => 'WordPress plugin',
        'requirements' => ['wordpress' => '6.0', 'php' => '8.1', 'tested' => ''],
        'faq' => [['q' => 'Is it fast?', 'a' => 'Very.'], ['q' => '', 'a' => 'dropped']],
        'activation_steps' => "Install\n\nActivate",
        'icon' => UploadedFile::fake()->image('icon.png', 256, 256),
        'screenshots' => [UploadedFile::fake()->image('a.png', 1280, 800), UploadedFile::fake()->image('b.png', 1280, 800)],
    ]))->assertSessionHasNoErrors();

    $product = $this->product->fresh();
    expect($product->tagline)->toBe('Forms, faster.')
        ->and($product->requirements)->toBe(['wordpress' => '6.0', 'php' => '8.1'])
        ->and($product->faq)->toBe([['q' => 'Is it fast?', 'a' => 'Very.']])
        ->and($product->activation_steps)->toBe(['Install', 'Activate'])
        ->and($product->screenshots)->toHaveCount(2)
        ->and($product->icon_url)->toStartWith('/storage/');
    Storage::disk('public')->assertExists($product->image_path);

    // Remove one screenshot and caption the other.
    [$first, $second] = $product->screenshots;
    $this->put("/admin/store/products/{$product->id}", productPayload(['remove_screenshots' => [$first['path']], 'captions' => [$second['path'] => 'Builder']]))->assertSessionHasNoErrors();
    expect($product->fresh()->screenshots)->toBe([['path' => $second['path'], 'caption' => 'Builder']]);
    Storage::disk('public')->assertMissing($first['path']);
});

it('keeps one most-popular plan per billing term', function () {
    $base = "/admin/store/products/{$this->product->id}/plans";
    $plan = fn ($name, $featured) => ['name' => $name, 'price' => 99, 'term_months' => 12, 'site_limit' => 1, 'published' => 1, 'is_featured' => $featured, 'features' => "One\nTwo"];
    $this->actingAs($this->admin)->post($base, $plan('A', 1))->assertSessionHasNoErrors();
    $this->post($base, $plan('B', 1))->assertSessionHasNoErrors();
    $this->post($base, ['term_months' => 1] + $plan('C', 1))->assertSessionHasNoErrors();
    expect($this->product->plans()->where('is_featured', true)->pluck('name')->sort()->values()->all())->toBe(['B', 'C'])
        ->and($this->product->plans()->firstWhere('name', 'A')->features)->toBe(['One', 'Two']);
    $this->post($base, ['term_months' => 7] + $plan('D', 0))->assertSessionHasErrors('term_months');
});

it('shows a public changelog without private package details', function () {
    ProductRelease::create(['digital_product_id' => $this->product->id, 'version' => '1.0.0', 'edition' => 'pro', 'package_path' => 'products/packages/secret.zip', 'sha256' => str_repeat('a', 64), 'published' => true, 'changelog' => 'First']);
    ProductRelease::create(['digital_product_id' => $this->product->id, 'version' => '0.9.0', 'edition' => 'free', 'package_path' => 'products/packages/draft.zip', 'sha256' => str_repeat('b', 64), 'published' => false]);
    $response = $this->get('/products/demo-plugin')->assertOk();
    $releases = $response->viewData('props')['releases'];
    expect($releases)->toHaveCount(1)->and($releases[0])->not->toHaveKeys(['package_path', 'sha256']);
    $response->assertDontSee('secret.zip');
});

it('filters admin orders and licenses with counts', function () {
    $customer = User::factory()->withoutTwoFactor()->create(['email' => 'buyer@example.com']);
    $plan = $this->product->plans()->create(['name' => 'Annual', 'price_minor' => 5000, 'term_months' => 12, 'site_limit' => 1, 'published' => true]);
    $order = fn ($status) => ProductOrder::create(['reference' => (string) Str::uuid(), 'user_id' => $customer->id, 'digital_product_id' => $this->product->id, 'product_plan_id' => $plan->id, 'product_name' => 'Demo', 'plan_name' => 'Annual', 'usd_minor' => 5000, 'amount_minor' => 1400000, 'usd_pkr_rate' => 280, 'currency' => 'PKR', 'term_months' => 12, 'site_limit' => 1, 'status' => $status, 'quote_expires_at' => now()->addDay()]);
    $paid = $order('paid');
    $review = $order('pending');
    $review->payments()->create(['method' => 'bank_transfer', 'reference' => 'MREF1', 'transaction_reference' => 'TX1', 'proof_path' => 'p.png', 'status' => 'pending']);
    $order('pending');

    $props = $this->actingAs($this->admin)->get('/admin/store/orders?status=review')->assertOk()->viewData('props');
    expect($props['counts'])->toMatchArray(['all' => 3, 'pending' => 2, 'paid' => 1, 'review' => 1])
        ->and(collect($props['orders']['data'])->pluck('id')->all())->toBe([$review->id]);

    $license = fn ($days, $status = 'active') => ProductLicense::create(['product_order_id' => $paid->id, 'user_id' => $customer->id, 'digital_product_id' => $this->product->id, 'key_hash' => Str::random(40), 'key_secret' => 'k', 'key_hint' => 'USP-…'.Str::random(8), 'site_limit' => 1, 'expires_at' => now()->addDays($days), 'status' => $status]);
    $license(5);
    $props = $this->get('/admin/store/licenses?status=expiring')->assertOk()->viewData('props');
    expect($props['counts']['expiring'])->toBe(1)->and($props['licenses']['data'])->toHaveCount(1);
    $this->get('/admin/store/licenses?search=buyer@example')->assertOk();
});
