<?php

use App\Models\EducationSetting;
use App\Models\User;
use App\Support\Education;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('public');
    $this->admin = User::factory()->withoutTwoFactor()->create(['is_admin' => true, 'password' => 'Qr-test-password']);
    $this->data = ['current_password' => 'Qr-test-password', 'bank_transfer' => 'Verified bank account', 'jazzcash' => 'Verified wallet account', 'easypaisa' => '', 'enabled' => 0, 'environment' => 'sandbox'];
});
test('admin can upload retain replace and remove payment QR images', function () {
    $this->actingAs($this->admin)->put('/admin/education/settings', $this->data + ['bank_transfer_qr' => UploadedFile::fake()->image('qr.png', 400, 400)])->assertSessionHasNoErrors();
    $path = EducationSetting::current()->manual_methods['bank_transfer']['qr_path'];
    Storage::disk('public')->assertExists($path);
    $this->put('/admin/education/settings', $this->data)->assertSessionHasNoErrors();
    expect(EducationSetting::current()->manual_methods['bank_transfer']['qr_path'])->toBe($path);
    $this->put('/admin/education/settings', $this->data + ['bank_transfer_qr' => UploadedFile::fake()->image('new.png', 400, 400)])->assertSessionHasNoErrors();
    $next = EducationSetting::current()->manual_methods['bank_transfer']['qr_path'];
    Storage::disk('public')->assertMissing($path);
    Storage::disk('public')->assertExists($next);
    $this->put('/admin/education/settings', $this->data + ['bank_transfer_remove_qr' => 1])->assertSessionHasNoErrors();
    expect(EducationSetting::current()->manual_methods['bank_transfer']['qr_path'])->toBeNull();
    Storage::disk('public')->assertMissing($next);
});
test('QR changes require administrator and current password', function () {
    $this->actingAs(User::factory()->withoutTwoFactor()->create())->put('/admin/education/settings', $this->data)->assertForbidden();
    $this->actingAs($this->admin)->put('/admin/education/settings', array_merge($this->data, ['current_password' => 'wrong', 'bank_transfer_qr' => UploadedFile::fake()->image('qr.png')]))->assertSessionHasErrors('current_password');
    expect(Storage::disk('public')->allFiles())->toBe([]);
});
test('invalid QR files cannot change saved payment instructions', function () {
    $this->actingAs($this->admin)->put('/admin/education/settings', $this->data)->assertSessionHasNoErrors();
    $this->put('/admin/education/settings', array_merge($this->data, ['bank_transfer' => 'Changed', 'bank_transfer_qr' => UploadedFile::fake()->create('qr.svg', 5, 'image/svg+xml')]))->assertSessionHasErrors('bank_transfer_qr');
    expect(EducationSetting::current()->manual_methods['bank_transfer']['instructions'])->toBe('Verified bank account');
    $this->put('/admin/education/settings', $this->data + ['bank_transfer_qr' => UploadedFile::fake()->image('big.png', 4100, 100)])->assertSessionHasErrors('bank_transfer_qr');
});
test('disabled methods do not become available from a QR alone', function () {
    $this->actingAs($this->admin)->put('/admin/education/settings', array_merge($this->data, ['bank_transfer' => '', 'bank_transfer_qr' => UploadedFile::fake()->image('qr.png')]))->assertSessionHasNoErrors();
    expect(array_keys(Education::methods()))->not->toContain('bank_transfer');
});
