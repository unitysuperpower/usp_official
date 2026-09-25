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
        SEOMeta::setDescription('USP Tech Solution brings design and technology together to build digital experiences that move your business forward.');
        SEOMeta::setKeywords(['services', 'business solutions', 'professional services', 'consultation', 'technology']);
        SEOMeta::setCanonical(url()->current());

        OpenGraph::setTitle('USP Tech Solution');
        OpenGraph::setDescription('Discover our wide range of professional services designed to help your business grow.');
        OpenGraph::setUrl(url()->current());
        OpenGraph::addProperty('type', 'website');

        TwitterCard::setTitle('USP Tech Solution');
        TwitterCard::setSite('@TechSolution');

        JsonLd::setTitle('USP Tech Solution');
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

        return \App\Support\ReactPage::render('home', compact('featuredServices', 'categories', 'latestBlogs'));
    }
}
