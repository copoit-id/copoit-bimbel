<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DisableBrowserCache
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Disable browser caching for admin pages
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sun, 01 Jan 2014 00:00:00 GMT');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        
        return $response;
    }
}
