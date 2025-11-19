<?php
// V-FINAL-1730-532 (Created)

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Redirect;
use Illuminate\Support\Facades\Cache;

class RedirectMiddleware
{
    /**
     * Handle an incoming request.
     * FSD-SEO-006: Check for redirects.
     */
    public function handle(Request $request, Closure $next): Response
    {
	    // ❗ Skip ALL API routes
    			if ($request->is('api/*')) {
        	return $next($request);
    }
        // We only care about GET requests
        if (!$request->isMethod('get')) {
            return $next($request);
        }

        $path = $request->getPathInfo();

        // 1. Get all redirects from cache
        $redirects = Cache::rememberForever('redirects.all', function () {
            return Redirect::where('is_active', true)->get()->keyBy('from_url');
        });

        // 2. Check if the requested path is in our list
        if ($redirects->has($path)) {
            $redirect = $redirects->get($path);

            // 3. Log the hit (increment)
            // We use increment which is an atomic DB operation
            Redirect::where('id', $redirect->id)->increment('hit_count');

            // 4. Perform the redirect
            return redirect($redirect->to_url, $redirect->status_code);
        }

        // 5. No redirect found, continue to the app
        return $next($request);
    }
}