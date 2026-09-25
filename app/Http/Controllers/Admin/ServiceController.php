<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\ImageOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::with('category')->latest()->get();
        return \App\Support\ReactPage::render('admin.services.index', compact('services'));
    }

    public function create()
    {
        $categories = ServiceCategory::where('is_active', true)->get();
        return \App\Support\ReactPage::render('admin.services.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:service_categories,id',
            'title' => 'required|string|max:255',
            'short_description' => 'nullable|string',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'price_unit' => 'required|string|max:50',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:10240',
            'features_text' => 'nullable|string',
            'delivery_days' => 'nullable|integer|min:1',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['slug'] = Str::slug($validated['title']);
        $originalSlug = $validated['slug'];
        while (\App\Models\Service::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $originalSlug . '-' . uniqid();
        }
        
        // Handle checkboxes (they won't be in request if unchecked)
        $validated['is_featured'] = $request->has('is_featured') ? 1 : 0;
        $validated['is_active'] = $request->has('is_active') ? 1 : 0;

        // Convert features text to array
        if ($request->has('features_text')) {
            $validated['features'] = array_filter(array_map('trim', explode("\n", (string) $request->features_text)));
        }
        unset($validated['features_text']);

        // Handle image upload with optimization
        if ($request->hasFile('image')) {
            $imageService = new ImageOptimizationService();
            $validated['image'] = $imageService->processImage($request->file('image'), 'services');
        }

        Service::create($validated);

        return redirect()->route('admin.services.index')
            ->with('success', 'Service created successfully.');
    }

    public function edit(Service $service)
    {
        $categories = ServiceCategory::where('is_active', true)->get();
        return \App\Support\ReactPage::render('admin.services.edit', compact('service', 'categories'));
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:service_categories,id',
            'title' => 'required|string|max:255',
            'short_description' => 'nullable|string',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'price_unit' => 'required|string|max:50',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:10240',
            'features_text' => 'nullable|string',
            'delivery_days' => 'nullable|integer|min:1',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['slug'] = Str::slug($validated['title']);
        $originalSlug = $validated['slug'];
        while (\App\Models\Service::where('slug', $validated['slug'])->where('id', '!=', $service->id)->exists()) {
            $validated['slug'] = $originalSlug . '-' . uniqid();
        }
        
        // Handle checkboxes (they won't be in request if unchecked)
        $validated['is_featured'] = $request->has('is_featured') ? 1 : 0;
        $validated['is_active'] = $request->has('is_active') ? 1 : 0;

        // Convert features text to array
        if ($request->has('features_text')) {
            $validated['features'] = array_filter(array_map('trim', explode("\n", (string) $request->features_text)));
        }
        unset($validated['features_text']);

        // Handle image upload with optimization
        if ($request->hasFile('image')) {
            $imageService = new ImageOptimizationService();
            if ($service->image) {
                $imageService->deleteImage($service->image);
            }
            $validated['image'] = $imageService->processImage($request->file('image'), 'services');
        }

        $service->update($validated);

        return redirect()->route('admin.services.index')
            ->with('success', 'Service updated successfully.');
    }

    public function destroy(Service $service)
    {
        if ($service->image) {
            $imageService = new ImageOptimizationService();
            $imageService->deleteImage($service->image);
        }

        $service->delete();

        return redirect()->route('admin.services.index')
            ->with('success', 'Service deleted successfully.');
    }
}
