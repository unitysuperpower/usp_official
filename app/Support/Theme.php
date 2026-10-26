<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

// Site-wide theme from Admin → Settings → Appearance, rendered as CSS variables in every React page.
class Theme
{
    private const CACHE_KEY = 'site-theme';

    private static ?array $current = null;

    public static function current(): array
    {
        return self::$current ??= array_replace_recursive(config('theme'), Cache::rememberForever(self::CACHE_KEY, function () {
            return Schema::hasTable('site_settings') ? (SiteSetting::find('theme')?->value ?? []) : [];
        }));
    }

    public static function save(array $values): void
    {
        SiteSetting::updateOrCreate(['key' => 'theme'], ['value' => $values]);
        self::forget();
    }

    public static function saved(): array
    {
        return SiteSetting::find('theme')?->value ?? [];
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
        self::$current = null;
    }

    /** CSS custom properties for both modes. Values are validated hex colors and whitelisted fonts. */
    public static function css(): string
    {
        $theme = self::current();
        $vars = fn (array $colors) => collect($colors)
            ->filter(fn ($value) => is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value))
            ->map(fn ($value, $key) => "--{$key}:{$value}")->implode(';');
        $fonts = collect($theme['fonts'])->map(fn ($font, $role) => "--font-{$role}:".self::stack($font))->implode(';');
        $radius = max(0, min(2, (float) $theme['radius']));

        // html:root outranks the stylesheet's :root, so saved values win regardless of load order.
        return 'html:root{'.$vars($theme['colors']['light']).';'.$fonts.";--radius-scale:{$radius}}"
            .'html[data-theme="dark"]{'.$vars($theme['colors']['dark']).'}';
    }

    public static function fontsUrl(): ?string
    {
        $families = collect(self::current()['fonts'])->unique()
            ->reject(fn ($font) => isset(config('theme.system_fonts')[$font]) || ! isset(config('theme.font_choices')[$font]))
            ->map(fn ($font) => strtolower(str_replace(' ', '-', $font)).':400,500,600,700,800');

        return $families->isEmpty() ? null : 'https://fonts.bunny.net/css?family='.$families->implode('|').'&display=swap';
    }

    /** Branding and mode settings shared with the React front end. */
    public static function client(): array
    {
        $theme = self::current();
        $url = fn ($path) => $path ? self::publicPath($path) : null;

        return [
            'mode' => $theme['mode'],
            'allow_toggle' => (bool) $theme['allow_toggle'],
            'brand' => [
                'name' => $theme['brand']['name'], 'subtitle' => $theme['brand']['subtitle'], 'mark' => $theme['brand']['mark'],
                'logo' => $url($theme['brand']['logo']), 'logo_dark' => $url($theme['brand']['logo_dark']),
            ],
            'footer' => $theme['footer'],
        ];
    }

    public static function favicon(): string
    {
        $path = self::current()['brand']['favicon'];

        return $path ? self::publicPath($path) : '/favicon.png';
    }

    // Site-relative URL, so uploads work under any domain regardless of APP_URL.
    public static function publicPath(string $path): string
    {
        return parse_url(Storage::disk('public')->url($path), PHP_URL_PATH);
    }

    private static function stack(string $font): string
    {
        $system = config('theme.system_fonts');
        if (isset($system[$font])) {
            return $system[$font].', '.config('theme.font_choices')[$font];
        }

        return isset(config('theme.font_choices')[$font]) ? "\"{$font}\", ".config('theme.font_choices')[$font] : 'sans-serif';
    }
}
