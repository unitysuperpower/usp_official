<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchRobots
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $public = $request->routeIs('home', 'services.*', 'blogs.*', 'contact');
        $discovery = $request->routeIs('seo.*');
        $filtered = $request->routeIs('services.index') && ($request->filled('search') || $request->filled('category'));
        if (! config('seo.indexable') || (! $public && ! $discovery) || $filtered || $response->getStatusCode() >= 400) {
            $response->headers->set('X-Robots-Tag', 'noindex, follow');
        }

        return $response;
    }
}
