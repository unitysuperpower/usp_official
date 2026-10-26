<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ImageOptimizationService;
use App\Support\Theme;
use Illuminate\Http\Request;

// Images inserted from the rich text editor (articles, services, lessons, product pages).
class EditorUploadController extends Controller
{
    public const PERMISSIONS = ['blogs.manage', 'services.manage', 'education.courses', 'education.teach', 'store.products'];

    public function store(Request $request, ImageOptimizationService $images)
    {
        abort_unless(collect(self::PERMISSIONS)->contains(fn ($p) => $request->user()->can($p)), 403);
        $request->validate(['image' => 'required|file|image|mimes:jpg,jpeg,png,webp,gif|max:5120|dimensions:max_width=6000,max_height=6000'], [
            'image.max' => 'Images can be up to 5 MB.', 'image.mimes' => 'Use a JPG, PNG, WebP or GIF image.',
        ]);
        $path = $images->processImage($request->file('image'), 'editor/'.now()->format('Y/m'), false);
        abort_unless($path, 503, 'The image could not be stored.');

        return response()->json(['url' => Theme::publicPath($path)]);
    }
}
