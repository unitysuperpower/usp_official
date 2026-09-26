<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Support\ReactPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogCategoryController extends Controller
{
    public function index()
    {
        $categories = BlogCategory::withCount('blogs')->latest()->get();

        return ReactPage::render('admin.blog-categories.index', compact('categories'));
    }

    public function create()
    {
        return ReactPage::render('admin.blog-categories.create');
    }

    public function store(Request $request)
    {
        $request->merge(['slug' => is_string($request->input('name')) ? Str::slug($request->input('name')) : '']);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['required', 'max:255', Rule::unique('blog_categories', 'slug')],
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['color'] = $validated['color'] ?? '#6366f1';

        BlogCategory::create($validated);

        return redirect()->route('admin.blog-categories.index')
            ->with('success', 'Blog category created successfully.');
    }

    public function edit(BlogCategory $blogCategory)
    {
        return ReactPage::render('admin.blog-categories.edit', compact('blogCategory'));
    }

    public function update(Request $request, BlogCategory $blogCategory)
    {
        $request->merge(['slug' => is_string($request->input('name')) ? Str::slug($request->input('name')) : '']);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['required', 'max:255', Rule::unique('blog_categories', 'slug')->ignore($blogCategory->id)],
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $blogCategory->update($validated);

        return redirect()->route('admin.blog-categories.index')
            ->with('success', 'Blog category updated successfully.');
    }

    public function destroy(BlogCategory $blogCategory)
    {
        $blogCategory->delete();

        return redirect()->route('admin.blog-categories.index')
            ->with('success', 'Blog category deleted successfully.');
    }
}
