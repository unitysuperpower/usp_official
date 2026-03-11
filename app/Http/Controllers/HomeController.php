<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\TwitterCard;
use Artesaos\SEOTools\Facades\JsonLd;

class HomeController extends Controller
{
    public function index()
    {
        SEOMeta::setTitle('Home');
        SEOMeta::setDescription('Professional service provider delivering innovative solutions for your business needs. Quality services with expert consultation and support.');
        SEOMeta::setKeywords(['services', 'business solutions', 'professional services', 'consultation', 'technology']);
        SEOMeta::setCanonical(url()->current());

        OpenGraph::setTitle('Professional Services & Solutions');
        OpenGraph::setDescription('Discover our wide range of professional services designed to help your business grow.');
        OpenGraph::setUrl(url()->current());
        OpenGraph::addProperty('type', 'website');

        TwitterCard::setTitle('Professional Services & Solutions');
        TwitterCard::setSite('@TechSolution');

        JsonLd::setTitle('Professional Services & Solutions');
        JsonLd::setDescription('Professional service provider delivering innovative solutions.');
        JsonLd::setType('WebSite');

        $featuredServices = Service::where('is_active', true)
            ->where('is_featured', true)
            ->with('category')
            ->take(6)
            ->get();

        $categories = ServiceCategory::where('is_active', true)
            ->withCount('services')
            ->get();

        $latestBlogs = Blog::where('is_published', true)
            ->with('category')
            ->latest('published_at')
            ->take(8)
            ->get();

        return view('home', compact('featuredServices', 'categories', 'latestBlogs'));
    }
}
