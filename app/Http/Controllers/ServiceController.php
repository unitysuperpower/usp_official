<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\TwitterCard;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        SEOMeta::setTitle('Our Services');
        SEOMeta::setDescription('Explore our comprehensive range of professional services designed to meet your business needs.');
        SEOMeta::addKeyword(['services', 'professional services', 'business solutions']);

        $query = Service::where('is_active', true)->with('category');

        if ($request->has('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $services = $query->paginate(12)->withQueryString();
        $categories = ServiceCategory::where('is_active', true)->get();

        return \App\Support\ReactPage::render('services.index', compact('services', 'categories'));
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

        return \App\Support\ReactPage::render('services.category', compact('category', 'services', 'allCategories'));
    }

    public function show($slug)
    {
        $service = Service::where('slug', $slug)
            ->where('is_active', true)
            ->with('category')
            ->firstOrFail();

        // Increment views
        $service->incrementViews();

        SEOMeta::setTitle($service->title);
        SEOMeta::setDescription(strip_tags($service->short_description ?? $service->description));
        SEOMeta::addKeyword([$service->title, $service->category->name, 'service']);

        OpenGraph::setTitle($service->title);
        OpenGraph::setDescription(strip_tags($service->short_description ?? $service->description));
        if ($service->image) {
            OpenGraph::addImage(asset('storage/' . $service->image));
        }

        TwitterCard::setTitle($service->title);
        TwitterCard::setDescription(strip_tags($service->short_description ?? $service->description));

        $relatedServices = Service::where('category_id', $service->category_id)
            ->where('id', '!=', $service->id)
            ->where('is_active', true)
            ->take(3)
            ->get();

        return \App\Support\ReactPage::render('services.show', compact('service', 'relatedServices'));
    }
}
