<?php

namespace App\Http\Controllers;

use App\Services\SitemapService;
use App\Support\Seo;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SeoController extends Controller
{
    public function robots(): Response
    {
        // Allow crawlers to read noindex on login/account pages; robots is not access control.
        $rules = config('seo.indexable')
            ? "User-agent: *\nAllow: /\nDisallow: /livewire/\nDisallow: /broadcasting/\n\nSitemap: ".Seo::url('/sitemap.xml')."\n"
            : "User-agent: *\nDisallow: /\n";

        return response($rules, 200, ['Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'no-cache']);
    }

    public function index(Request $request, SitemapService $sitemap): Response
    {
        return $this->xml($request, $sitemap->index());
    }

    public function pages(Request $request, SitemapService $sitemap): Response
    {
        return $this->xml($request, $sitemap->pages());
    }

    public function section(Request $request, SitemapService $sitemap, string $section, string $page): Response
    {
        return $this->xml($request, $sitemap->section($section, (int) $page));
    }

    private function xml(Request $request, string $xml): Response
    {
        $response = response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8', 'Cache-Control' => 'no-cache', 'X-Content-Type-Options' => 'nosniff']);
        $response->setEtag(hash('sha256', $xml));
        $response->isNotModified($request);

        return $response;
    }
}
