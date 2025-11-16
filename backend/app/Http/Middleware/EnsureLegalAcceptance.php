<?php
// V-FINAL-1730-561 (Created)

namespace App\Http{Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Page;
use App\Models\UserLegalAcceptance;

class EnsureLegalAcceptance
{
    /**
     * Handle an incoming request.
     * FSD-LEGAL-004: Enforce acceptance.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $slug  (e.g., 'terms-of-service', 'risk-disclosure')
     */
    public function handle(Request $request, Closure $next, string $slug): Response
    {
        $user = $request->user();
        
        // 1. Find the live version of the document
        $page = Page::where('slug', $slug)
                    ->where('status', 'published')
                    ->where('require_user_acceptance', true)
                    ->first();
        
        // 2. If doc doesn't exist or doesn't require acceptance, let them pass
        if (!$page) {
            return $next($request);
        }

        // 3. Check if the user has accepted *this specific version*
        $hasAccepted = UserLegalAcceptance::where('user_id', $user->id)
            ->where('page_id', $page->id)
            ->where('page_version', $page->current_version)
            ->exists();

        if ($hasAccepted) {
            return $next($request);
        }

        // 4. Block the request
        return response()->json([
            'message' => 'You must accept the latest ' . $page->title . ' to proceed.',
            'error_code' => 'LEGAL_ACCEPTANCE_REQUIRED',
            'document_slug' => $slug,
            'required_version' => $page->current_version,
        ], 403); // 403 Forbidden
    }
}