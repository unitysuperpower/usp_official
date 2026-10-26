<?php

use App\Models\Role;
use App\Models\User;
use App\Support\Theme;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    Theme::forget();
    $this->admin = User::factory()->withoutTwoFactor()->create(['is_admin' => true]);
});

function themePayload(array $overrides = []): array
{
    $defaults = config('theme');

    return array_replace_recursive([
        'colors' => $defaults['colors'], 'fonts' => $defaults['fonts'], 'radius' => 1, 'mode' => 'light', 'allow_toggle' => 1,
        'brand' => ['name' => 'USP', 'subtitle' => 'TECH SOLUTION', 'mark' => 'u'], 'footer' => $defaults['footer'],
    ], $overrides);
}

it('renders the default theme variables on every page', function () {
    $this->get('/')->assertOk()
        ->assertSee('html:root{--bg:#fafbf8', false)
        ->assertSee('fonts.bunny.net/css?family=dm-sans', false)
        ->assertSee('\u0022brand\u0022:{\u0022name\u0022:\u0022USP\u0022', false);
});

it('saves colors, fonts, branding and footer text site-wide', function () {
    $this->actingAs($this->admin)->put('/admin/settings/theme', themePayload([
        'colors' => ['light' => ['accent' => '#1D5FA8']], 'fonts' => ['heading' => 'Poppins'], 'radius' => 0.5, 'mode' => 'system',
        'brand' => ['name' => 'Acme'], 'footer' => ['motto' => 'Build better.'],
    ]))->assertSessionHasNoErrors();

    Theme::forget();
    $this->get('/')->assertOk()
        ->assertSee('--accent:#1d5fa8', false)
        ->assertSee('--font-heading:"Poppins", sans-serif', false)
        ->assertSee('--radius-scale:0.5', false)
        ->assertSee('poppins:400', false)
        ->assertSee('\u0022name\u0022:\u0022Acme\u0022', false)
        ->assertSee('Build better.', false);
});

it('rejects values that could break out of the stylesheet', function () {
    $this->actingAs($this->admin)->put('/admin/settings/theme', themePayload([
        'colors' => ['light' => ['accent' => 'red;}body{display:none']], 'fonts' => ['body' => 'Comic"}</style>'],
    ]))->assertSessionHasErrors(['colors.light.accent', 'fonts.body']);
    $this->actingAs($this->admin)->put('/admin/settings/theme', themePayload(['logo' => UploadedFile::fake()->create('logo.svg', 5, 'image/svg+xml')]))
        ->assertSessionHasErrors('logo');
});

it('stores and removes logo uploads on the public disk', function () {
    Storage::fake('public');
    $this->actingAs($this->admin)->put('/admin/settings/theme', themePayload(['logo' => UploadedFile::fake()->image('logo.png', 300, 80)]))->assertSessionHasNoErrors();
    $path = Theme::saved()['brand']['logo'];
    Storage::disk('public')->assertExists($path);

    $this->actingAs($this->admin)->put('/admin/settings/theme', themePayload(['remove_logo' => 1]))->assertSessionHasNoErrors();
    Storage::disk('public')->assertMissing($path);
    expect(Theme::saved()['brand']['logo'])->toBeNull();
});

it('resets to defaults and requires the appearance permission', function () {
    $this->actingAs($this->admin)->put('/admin/settings/theme', themePayload(['colors' => ['light' => ['accent' => '#000000']]]));
    $this->actingAs($this->admin)->post('/admin/settings/theme/reset')->assertRedirect();
    Theme::forget();
    expect(Theme::current()['colors']['light']['accent'])->toBe('#226c57');

    $editor = User::factory()->withoutTwoFactor()->create();
    $editor->syncRoles([Role::where('slug', 'editor')->value('id')]);
    $this->actingAs($editor)->get('/admin/settings/theme')->assertForbidden();
});
