<?php

use App\Mail\NotificationMail;
use App\Models\ContactMessage;
use App\Models\DigitalProduct;
use App\Models\MailSetting;
use App\Models\ProductOrder;
use App\Models\Service;
use App\Models\User;
use App\Support\SiteMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->withoutVite();
    Mail::fake();
    SiteMail::forget();
    $this->admin = User::factory()->withoutTwoFactor()->create(['is_admin' => true, 'password' => 'Test-password-123']);
    $this->customer = User::factory()->withoutTwoFactor()->create(['email' => 'buyer@example.com']);
    $this->product = DigitalProduct::create(['slug' => 'demo-plugin', 'name' => 'Demo Plugin', 'summary' => 'A demo', 'published' => true]);
    $this->plan = $this->product->plans()->create(['name' => 'Annual', 'price_minor' => 5000, 'term_months' => 12, 'site_limit' => 1, 'published' => true]);
});

function pendingOrder($test): ProductOrder
{
    return ProductOrder::create([
        'reference' => (string) Str::uuid(), 'user_id' => $test->customer->id, 'digital_product_id' => $test->product->id,
        'product_plan_id' => $test->plan->id, 'product_name' => 'Demo Plugin', 'plan_name' => 'Annual', 'usd_minor' => 5000,
        'amount_minor' => 1400000, 'usd_pkr_rate' => 280, 'currency' => 'PKR', 'term_months' => 12, 'site_limit' => 1,
        'status' => 'pending', 'quote_expires_at' => now()->addDay(),
    ]);
}

it('emails the license when an admin approves a product payment', function () {
    MailSetting::create(['id' => 1, 'admin_address' => 'owner@example.com']);
    $order = pendingOrder($this);
    $payment = $order->payments()->create(['method' => 'bank_transfer', 'transaction_reference' => 'TX1234', 'reference' => 'MTEST1']);

    $this->actingAs($this->admin)->patch('/admin/store/payments/'.$payment->id, ['status' => 'approved', 'review_note' => 'Bank statement checked'])->assertSessionHasNoErrors();

    $license = $order->fresh()->license;
    expect($license)->not->toBeNull();
    Mail::assertSent(NotificationMail::class, fn ($mail) => $mail->hasTo('buyer@example.com')
        && str_starts_with($mail->subjectLine, 'Order confirmed: Demo Plugin')
        && $mail->details['License key'] === $license->key_secret
        && str_contains($mail->actionUrl, '/account/products/licenses/'.$license->id));
});

it('tells the customer when a payment is rejected', function () {
    $order = pendingOrder($this);
    $payment = $order->payments()->create(['method' => 'bank_transfer', 'transaction_reference' => 'TX9999', 'reference' => 'MTEST2']);

    $this->actingAs($this->admin)->patch('/admin/store/payments/'.$payment->id, ['status' => 'rejected', 'review_note' => 'No matching transfer found'])->assertSessionHasNoErrors();

    Mail::assertSent(NotificationMail::class, fn ($mail) => $mail->hasTo('buyer@example.com') && $mail->details['Reason'] === 'No matching transfer found');
    Mail::assertNotSent(NotificationMail::class, fn ($mail) => str_starts_with($mail->subjectLine, 'Order confirmed'));
});

it('confirms service requests to the customer and alerts the admin', function () {
    MailSetting::create(['id' => 1, 'admin_address' => 'owner@example.com']);
    $category = \App\Models\ServiceCategory::create(['name' => 'Development', 'slug' => 'development']);
    $service = Service::create(['category_id' => $category->id, 'title' => 'Web Development', 'slug' => 'web-development', 'short_description' => 'Sites', 'description' => 'Sites', 'price' => 500, 'is_active' => true]);

    $this->post('/contact', ['service_id' => $service->id, 'name' => 'Ali', 'email' => 'ali@example.com', 'message' => 'I need a website'])->assertSessionHasNoErrors();

    Mail::assertSent(NotificationMail::class, fn ($mail) => $mail->hasTo('ali@example.com') && str_contains($mail->subjectLine, 'Web Development'));
    Mail::assertSent(NotificationMail::class, fn ($mail) => $mail->hasTo('owner@example.com') && $mail->replyToAddress === 'ali@example.com');
});

it('respects the per-email switches', function () {
    MailSetting::create(['id' => 1, 'admin_address' => 'owner@example.com', 'notifications' => ['admin_contact' => false]]);

    $this->post('/contact', ['name' => 'Sara', 'email' => 'sara@example.com', 'subject' => 'Hello', 'message' => 'Just saying hello there'])->assertSessionHasNoErrors();

    expect(ContactMessage::count())->toBe(1);
    Mail::assertNothingSent();
});

it('lets an admin save SMTP settings with an encrypted password', function () {
    $this->actingAs($this->admin)->get('/admin/settings/email')->assertOk();

    $this->actingAs($this->admin)->put('/admin/settings/email', [
        'enabled' => '1', 'host' => 'smtp.example.com', 'port' => 465, 'encryption' => 'ssl', 'username' => 'info@example.com',
        'password' => 'secret-pass', 'from_address' => 'info@example.com', 'from_name' => 'Example', 'admin_address' => 'owner@example.com',
        'notifications' => ['order_confirmed' => '1', 'payment_received' => '0'], 'current_password' => 'Test-password-123',
    ])->assertSessionHasNoErrors();

    $saved = MailSetting::current();
    expect($saved->enabled)->toBeTrue()
        ->and($saved->password)->toBe('secret-pass')
        ->and($saved->getRawOriginal('password'))->not->toContain('secret-pass')
        ->and($saved->wants('order_confirmed'))->toBeTrue()
        ->and($saved->wants('payment_received'))->toBeFalse();

    // A blank password keeps the saved one.
    $this->actingAs($this->admin)->put('/admin/settings/email', [
        'enabled' => '1', 'host' => 'smtp.example.com', 'port' => 465, 'encryption' => 'ssl', 'from_address' => 'info@example.com', 'current_password' => 'Test-password-123',
    ])->assertSessionHasNoErrors();
    expect(MailSetting::current()->password)->toBe('secret-pass');

    SiteMail::forget();
    SiteMail::apply();
    expect(config('mail.default'))->toBe('smtp')->and(config('mail.mailers.smtp.host'))->toBe('smtp.example.com')->and(config('mail.from.address'))->toBe('info@example.com');
});

it('requires the admin password to change email settings and blocks non-admins', function () {
    $this->actingAs($this->admin)->put('/admin/settings/email', ['port' => 587, 'encryption' => 'tls', 'current_password' => 'wrong'])->assertSessionHasErrors('current_password');
    $this->actingAs($this->customer)->get('/admin/settings/email')->assertForbidden();
});

it('shows a product and deletes products without orders, including release files', function () {
    Storage::fake('local');
    Storage::disk('local')->put('products/packages/demo.zip', 'zip');
    $this->product->releases()->create(['version' => '1.0.0', 'edition' => 'pro', 'package_path' => 'products/packages/demo.zip', 'sha256' => str_repeat('a', 64)]);

    $this->actingAs($this->admin)->get('/admin/store/products/'.$this->product->id)->assertOk()->assertSee('Demo Plugin');
    $this->actingAs($this->admin)->delete('/admin/store/products/'.$this->product->id)->assertRedirect('/admin/store/products');

    expect(DigitalProduct::find($this->product->id))->toBeNull();
    Storage::disk('local')->assertMissing('products/packages/demo.zip');
});

it('keeps products and plans that have orders', function () {
    pendingOrder($this);

    $this->actingAs($this->admin)->delete('/admin/store/products/'.$this->product->id)->assertSessionHasErrors('product');
    $this->actingAs($this->admin)->delete('/admin/store/products/'.$this->product->id.'/plans/'.$this->plan->id)->assertSessionHasErrors('plan');

    expect(DigitalProduct::find($this->product->id))->not->toBeNull()->and($this->plan->fresh())->not->toBeNull();
});
