<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Support\ReactPage;

class HomeController extends Controller
{
    public function index()
    {
        $featuredServices = Service::where('is_active', true)
            ->where('is_featured', true)
            ->with('category')
            ->take(6)
            ->get();

        $categories = ServiceCategory::where('is_active', true)
            ->withCount('services')
            ->get();

        $latestBlogs = Blog::published()
            ->with('category')
            ->latest('published_at')
            ->take(8)
            ->get();

        return ReactPage::render('home', compact('featuredServices', 'categories', 'latestBlogs'));
    }
}
