<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Support\ReactPage;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Service::where('is_active', true)->with('category');

        if ($request->has('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        $services = $query->paginate(12)->withQueryString();
        $categories = ServiceCategory::where('is_active', true)->get();

        return ReactPage::render('services.index', compact('services', 'categories'));
    }

    public function category($slug)
    {
        $category = ServiceCategory::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $services = Service::where('category_id', $category->id)
            ->where('is_active', true)
            ->paginate(12);

        $allCategories = ServiceCategory::where('is_active', true)->get();

        return ReactPage::render('services.category', compact('category', 'services', 'allCategories'));
    }

    public function show($slug)
    {
        $service = Service::where('slug', $slug)
            ->where('is_active', true)
            ->with('category')
            ->firstOrFail();

        // Increment views
        $service->incrementViews();

        $relatedServices = Service::where('category_id', $service->category_id)
            ->where('id', '!=', $service->id)
            ->where('is_active', true)
            ->take(3)
            ->get();

        return ReactPage::render('services.show', compact('service', 'relatedServices'));
    }
}
