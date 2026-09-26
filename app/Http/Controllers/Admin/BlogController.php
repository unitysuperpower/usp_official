<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Services\ImageOptimizationService;
use App\Support\ReactPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index()
    {
        $blogs = Blog::with(['category', 'user'])
            ->withCount(['likes', 'comments'])
            ->latest()
            ->get();

        return ReactPage::render('admin.blogs.index', compact('blogs'));
    }

    public function create()
    {
        $categories = BlogCategory::where('is_active', true)->get();

        return ReactPage::render('admin.blogs.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:blog_categories,id',
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'featured_image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:10240',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'tags' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'is_published' => 'nullable|boolean',
        ]);

        $validated['slug'] = Str::slug($validated['title']);
        $originalSlug = $validated['slug'];
        while (Blog::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $originalSlug.'-'.uniqid();
        }
        $validated['user_id'] = Auth::id();
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_published'] = $request->boolean('is_published');

        if ($validated['is_published']) {
            $validated['published_at'] = now();
        }

        // Convert tags to array
        if ($request->has('tags')) {
            $validated['tags'] = array_filter(array_map('trim', explode(',', (string) $request->tags)));
        }

        // Handle image upload with optimization
        if ($request->hasFile('featured_image')) {
            $imageService = new ImageOptimizationService;
            $validated['featured_image'] = $imageService->processImage($request->file('featured_image'), 'blogs');
        }

        Blog::create($validated);

        return redirect()->route('admin.blogs.index')
            ->with('success', 'Blog created successfully.');
    }

    public function edit(Blog $blog)
    {
        $categories = BlogCategory::where('is_active', true)->get();

        return ReactPage::render('admin.blogs.edit', compact('blog', 'categories'));
    }

    public function update(Request $request, Blog $blog)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:blog_categories,id',
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'featured_image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:10240',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'tags' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'is_published' => 'nullable|boolean',
        ]);

        $validated['slug'] = Str::slug($validated['title']);
        $originalSlug = $validated['slug'];
        while (Blog::where('slug', $validated['slug'])->where('id', '!=', $blog->id)->exists()) {
            $validated['slug'] = $originalSlug.'-'.uniqid();
        }
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_published'] = $request->boolean('is_published');

        if ($validated['is_published'] && ! $blog->published_at) {
            $validated['published_at'] = now();
        }

        // Convert tags to array
        if ($request->has('tags')) {
            $validated['tags'] = array_filter(array_map('trim', explode(',', (string) $request->tags)));
        }

        // Handle image upload with optimization
        if ($request->hasFile('featured_image')) {
            $imageService = new ImageOptimizationService;
            if ($blog->featured_image) {
                $imageService->deleteImage($blog->featured_image);
            }
            $validated['featured_image'] = $imageService->processImage($request->file('featured_image'), 'blogs');
        }

        $blog->update($validated);

        return redirect()->route('admin.blogs.index')
            ->with('success', 'Blog updated successfully.');
    }

    public function destroy(Blog $blog)
    {
        if ($blog->featured_image) {
            $imageService = new ImageOptimizationService;
            $imageService->deleteImage($blog->featured_image);
        }

        $blog->delete();

        return redirect()->route('admin.blogs.index')
            ->with('success', 'Blog deleted successfully.');
    }
}
