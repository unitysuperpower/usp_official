<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TrackPageViews
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only successful public HTML pages represent website visits.
        if ($request->isMethod('GET') && $response->isSuccessful()
            && str_starts_with($response->headers->get('Content-Type', ''), 'text/html')
            && $request->routeIs('home', 'services.*', 'blogs.*', 'contact')) {
            try {
                PageView::track($this->detectPageType($request));
            } catch (\Exception $e) {
                Log::warning('Failed to track page view: '.$e->getMessage());
            }
        }

        return $response;
    }

    private function detectPageType(Request $request): string
    {
        return match (true) {
            $request->routeIs('home') => 'home',
            $request->routeIs('services.*') => 'service',
            $request->routeIs('blogs.*') => 'blog',
            $request->routeIs('contact') => 'contact',
            default => 'other',
        };
    }
}
