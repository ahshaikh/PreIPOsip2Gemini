<?php
// V-FINAL-1730-561 (Created) | V-FIX-MODULE-19 (Gemini) | V-AUDIT-MODULE17-CRITICAL (Split-Brain Fix)

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\LegalAgreement; // V-AUDIT-MODULE17-CRITICAL: Changed from Page to LegalAgreement
use App\Models\UserLegalAcceptance;
use Illuminate\Support\Facades\Cache; // Import Cache

class EnsureLegalAcceptance
{
    /**
     * V-AUDIT-MODULE17-CRITICAL: Fixed "Split-Brain" Data Model
     *
     * PROBLEM: Admin Compliance Dashboard manages LegalAgreement model, but this middleware
     * was checking Page model (CMS). When Admin published new version via Compliance Dashboard,
     * the middleware didn't know about it - rendering compliance enforcement USELESS.
     *
     * SOLUTION: Unified to use LegalAgreement model exclusively. Now when Admin publishes
     * a new version, the middleware immediately starts enforcing acceptance.
     *
     * Handle an incoming request.
     * FSD-LEGAL-004: Enforce acceptance.
     */
    public function handle(Request $request, Closure $next, string $documentType): Response
    {
        $user = $request->user();

        // V-AUDIT-MODULE17-HIGH: Optimized cache strategy - Load ALL required legal docs once
        // Instead of querying per-request, cache a map of all active legal agreements
        // Cache for 1 hour (3600s) - invalidate when any agreement is published/updated
        $legalVersionsMap = Cache::remember("legal_versions_map", 3600, function () {
            return LegalAgreement::active()
                ->select('id', 'type', 'title', 'version') // Only needed fields
                ->get()
                ->keyBy('type'); // Key by type for O(1) lookup
        });

        // V-AUDIT-MODULE17-CRITICAL: Look up document in unified LegalAgreement model
        // $documentType is the agreement type (e.g., 'terms-of-service', 'privacy-policy')
        $agreement = $legalVersionsMap->get($documentType);

        // 2. If document doesn't exist or is not active, let them pass
        if (!$agreement) {
            return $next($request);
        }

        // 3. Check if the user has accepted *this specific version*
        // V-AUDIT-MODULE17-CRITICAL: Query using legal_agreement_id instead of page_id
        // Note: User-specific acceptance cannot be globally cached easily, but it's an indexed lookup.
        $hasAccepted = UserLegalAcceptance::where('user_id', $user->id)
            ->where('legal_agreement_id', $agreement->id) // V-AUDIT-MODULE17: Use legal_agreement_id
            ->where('accepted_version', $agreement->version) // V-AUDIT-MODULE17: Use accepted_version
            ->exists();

        if ($hasAccepted) {
            return $next($request);
        }

        // 4. Block the request
        // V-AUDIT-MODULE17-CRITICAL: Return document_type instead of slug for frontend consistency
        return response()->json([
            'message' => 'You must accept the latest ' . $agreement->title . ' to proceed.',
            'error_code' => 'LEGAL_ACCEPTANCE_REQUIRED',
            'document_type' => $documentType, // V-AUDIT-MODULE17: Changed from document_slug
            'required_version' => $agreement->version,
        ], 403); // 403 Forbidden
    }
}