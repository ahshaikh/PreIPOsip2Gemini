<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeadersMiddleware
{
    /**
     * [AUDIT FIX]: Inject standard security headers into all responses.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Prevents the site from being embedded in frames (Anti-Clickjacking)
        $response->headers->set('X-Frame-Options', 'DENY');
        
        // Prevents browser from "sniffing" MIME types
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        
        // Enable XSS protection in older browsers
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        
        // Strict Content Security Policy (Adjust based on your script needs)
        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';");

        return $response;
    }
}