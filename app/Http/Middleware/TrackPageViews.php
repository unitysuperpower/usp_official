<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\PageView;
use Illuminate\Support\Facades\Log;

class TrackPageViews
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track GET requests and successful responses
        if ($request->isMethod('GET') && $response->isSuccessful() && !$request->ajax()) {
            // Skip admin, login, and API routes
            if (!$request->is('admin/*') && !$request->is('login') && !$request->is('register') && !$request->is('api/*')) {
                try {
                    $pageType = $this->detectPageType($request);
                    PageView::track($pageType);
                } catch (\Exception $e) {
                    // Silently fail - don't break the app for analytics
                    Log::warning('Failed to track page view: ' . $e->getMessage());
                }
            }
        }

        return $response;
    }

    private function detectPageType(Request $request): string
    {
        $path = $request->path();
        
        if ($path === '/' || $path === 'home') {
            return 'home';
        } elseif (str_starts_with($path, 'services')) {
            return 'service';
        } elseif (str_starts_with($path, 'blogs')) {
            return 'blog';
        } elseif (str_starts_with($path, 'contact')) {
            return 'contact';
        }
        
        return 'other';
    }
}
