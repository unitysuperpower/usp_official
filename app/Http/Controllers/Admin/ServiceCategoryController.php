<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use App\Support\ReactPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ServiceCategoryController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['search' => 'nullable|string|max:200']);
        $search = trim($filters['search'] ?? '');
        $categories = ServiceCategory::withCount('services')
            ->when($search !== '', fn ($query) => $query->where(fn ($match) => $match
                ->where('name', 'like', "%{$search}%")))
            ->latest()->orderByDesc('id')->paginate(20)->withQueryString();

        return ReactPage::render('admin.categories.index', compact('categories', 'filters'));
    }

    public function create()
    {
        return ReactPage::render('admin.categories.create');
    }

    public function store(Request $request)
    {
        $request->merge(['slug' => is_string($request->input('name')) ? Str::slug($request->input('name')) : '']);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['required', 'max:255', Rule::unique('service_categories', 'slug')],
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'features.*' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        // Filter out empty features
        if (isset($validated['features'])) {
            $validated['features'] = array_filter($validated['features'], fn ($value) => ! empty($value));
        }

        ServiceCategory::create($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(ServiceCategory $category)
    {
        return ReactPage::render('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, ServiceCategory $category)
    {
        $request->merge(['slug' => is_string($request->input('name')) ? Str::slug($request->input('name')) : '']);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['required', 'max:255', Rule::unique('service_categories', 'slug')->ignore($category->id)],
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'features.*' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        // Filter out empty features
        if (isset($validated['features'])) {
            $validated['features'] = array_filter($validated['features'], fn ($value) => ! empty($value));
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
