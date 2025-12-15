<?php
// V-FINAL-1730-561 (Created) | V-FIX-MODULE-19 (Gemini)

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Page;
use App\Models\UserLegalAcceptance;
use Illuminate\Support\Facades\Cache; // Import Cache

class EnsureLegalAcceptance
{
    /**
     * Handle an incoming request.
     * FSD-LEGAL-004: Enforce acceptance.
     */
    public function handle(Request $request, Closure $next, string $slug): Response
    {
        $user = $request->user();
        
        // FIX: Module 19 - Cache Legal Middleware (High)
        // Wrapped DB lookup in Cache::remember for 1 hour (3600s) to prevent DB hit on every request.
        $page = Cache::remember("legal_doc_{$slug}", 3600, function () use ($slug) {
            return Page::where('slug', $slug)
                ->where('status', 'published')
                ->where('require_user_acceptance', true)
                ->select('id', 'title', 'current_version') // Select only needed fields
                ->first();
        });
        
        // 2. If doc doesn't exist or doesn't require acceptance, let them pass
        if (!$page) {
            return $next($request);
        }

        // 3. Check if the user has accepted *this specific version*
        // Note: User-specific acceptance cannot be globally cached easily, but it's an indexed lookup.
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