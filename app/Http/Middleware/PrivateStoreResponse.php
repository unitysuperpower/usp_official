<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PrivateStoreResponse
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('api/licenses/*')) { $request->headers->set('Accept', 'application/json'); }
        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        return $response;
    }
}
