<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceCategoryController extends Controller
{
    public function index()
    {
        $categories = ServiceCategory::withCount('services')->latest()->get();
        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'features.*' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        
        // Filter out empty features
        if (isset($validated['features'])) {
            $validated['features'] = array_filter($validated['features'], fn($value) => !empty($value));
        }

        ServiceCategory::create($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(ServiceCategory $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, ServiceCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'features.*' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        
        // Filter out empty features
        if (isset($validated['features'])) {
            $validated['features'] = array_filter($validated['features'], fn($value) => !empty($value));
        }

        $category->update($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(ServiceCategory $category)
    {
        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
