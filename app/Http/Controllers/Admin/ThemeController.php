<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ReactPage;
use App\Support\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ThemeController extends Controller
{
    private const IMAGES = ['logo', 'logo_dark', 'favicon'];

    public function show()
    {
        return ReactPage::render('admin.settings.theme', [
            'theme' => Theme::current(),
            'defaults' => config('theme'),
            'images' => collect(self::IMAGES)->mapWithKeys(fn ($key) => [$key => ($path = Theme::current()['brand'][$key]) ? Storage::disk('public')->url($path) : null]),
            'fonts' => array_keys(config('theme.font_choices')),
            'presets' => config('theme.presets'),
        ]);
    }

    public function update(Request $request)
    {
        $colorKeys = array_keys(config('theme.colors.light'));
        $hex = ['required', 'regex:/^#[0-9a-fA-F]{6}$/'];
        $rules = [
            'fonts.body' => ['required', Rule::in(array_keys(config('theme.font_choices')))],
            'fonts.heading' => ['required', Rule::in(array_keys(config('theme.font_choices')))],
            'fonts.accent' => ['required', Rule::in(array_keys(config('theme.font_choices')))],
            'radius' => 'required|numeric|min:0|max:2',
            'mode' => 'required|in:light,dark,system',
            'allow_toggle' => 'nullable|boolean',
            'brand.name' => 'required|string|max:40',
            'brand.subtitle' => 'nullable|string|max:40',
            'brand.mark' => 'nullable|string|max:3',
            'footer.tagline' => 'nullable|string|max:200',
            'footer.statement' => 'nullable|string|max:120',
            'footer.statement_emphasis' => 'nullable|string|max:120',
            'footer.copyright' => 'nullable|string|max:120',
            'footer.motto' => 'nullable|string|max:120',
            'logo' => 'nullable|file|max:2048|mimes:png,jpg,jpeg,webp',
            'logo_dark' => 'nullable|file|max:2048|mimes:png,jpg,jpeg,webp',
            'favicon' => 'nullable|file|max:512|mimes:png,ico,webp',
        ];
        foreach (['light', 'dark'] as $mode) {
            foreach ($colorKeys as $key) {
                $rules["colors.{$mode}.{$key}"] = $hex;
            }
        }
        $data = $request->validate($rules, ['regex' => 'Use a 6-digit hex color such as #226c57.']);

        $saved = Theme::saved();
        $brand = ['name' => $data['brand']['name'], 'subtitle' => $data['brand']['subtitle'] ?? '', 'mark' => $data['brand']['mark'] ?? ''];
        foreach (self::IMAGES as $key) {
            $current = $saved['brand'][$key] ?? null;
            if ($request->hasFile($key) || $request->boolean("remove_{$key}")) {
                if ($current) {
                    Storage::disk('public')->delete($current);
                }
                $current = $request->hasFile($key) ? $request->file($key)->store('theme', 'public') : null;
            }
            $brand[$key] = $current;
        }

        Theme::save([
            'colors' => ['light' => array_map('strtolower', $data['colors']['light']), 'dark' => array_map('strtolower', $data['colors']['dark'])],
            'fonts' => $data['fonts'],
            'radius' => round((float) $data['radius'], 2),
            'mode' => $data['mode'],
            'allow_toggle' => $request->boolean('allow_toggle'),
            'brand' => $brand,
            'footer' => array_map(fn ($value) => (string) $value, $data['footer'] ?? []),
        ]);

        return back()->with('success', 'Appearance saved. Every page now uses the new theme.');
    }

    // Back to the built-in look; uploaded images are kept unless removed explicitly.
    public function reset()
    {
        $brand = Theme::saved()['brand'] ?? [];
        Theme::save(['brand' => array_intersect_key($brand, array_flip(self::IMAGES))]);

        return back()->with('success', 'Colors, fonts and text restored to the defaults. Uploaded logos were kept.');
    }
}
