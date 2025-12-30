<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\LegalAgreement;
use App\Models\UserLegalAcceptance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ComplianceStatusController extends Controller
{
    /**
     * Check compliance status for the logged-in user.
     * Returns a list of documents that need acceptance.
     */
    public function status(Request $request)
    {
        $user = Auth::user();
        
        // 1. Get all ACTIVE, REQUIRED agreements
        // We only care about active ones that require a signature/tick
        $activeAgreements = LegalAgreement::active()
            ->where('require_signature', true)
            ->get();

        // 2. Get user's latest acceptances keyed by type or agreement_id
        // We look for acceptances that match the CURRENT version of the active agreements
        $userAcceptances = UserLegalAcceptance::where('user_id', $user->id)
            ->whereIn('legal_agreement_id', $activeAgreements->pluck('id'))
            ->get()
            ->keyBy('legal_agreement_id');

        $pendingAgreements = [];

        foreach ($activeAgreements as $agreement) {
            $acceptance = $userAcceptances->get($agreement->id);

            // If never accepted, OR accepted version is different from current active version
            if (!$acceptance || $acceptance->accepted_version !== $agreement->version) {
                $pendingAgreements[] = [
                    'id' => $agreement->id,
                    'type' => $agreement->type,
                    'title' => $agreement->title,
                    'version' => $agreement->version,
                    'content' => $agreement->content, // Needed for the modal
                    'reason' => !$acceptance ? 'new_agreement' : 'updated_version'
                ];
            }
        }

        return response()->json([
            'is_compliant' => empty($pendingAgreements),
            'pending_count' => count($pendingAgreements),
            'pending_documents' => $pendingAgreements
        ]);
    }

    /**
     * Batch accept documents (Used by the ComplianceGuard)
     */
    public function accept(Request $request)
    {
        $request->validate([
            'agreements' => 'required|array',
            'agreements.*.id' => 'required|exists:legal_agreements,id',
            'agreements.*.version' => 'required|string',
        ]);

        $user = Auth::user();
        $ip = $request->ip();
        $agent = $request->userAgent();

        DB::beginTransaction();
        try {
            foreach ($request->agreements as $item) {
                $agreement = LegalAgreement::findOrFail($item['id']);
                
                // Double check version match to prevent race conditions
                if ($agreement->version !== $item['version']) {
                    continue; 
                }

                UserLegalAcceptance::create([
                    'user_id' => $user->id,
                    'legal_agreement_id' => $agreement->id,
                    'document_type' => $agreement->type, // Redundant but good for history
                    'accepted_version' => $item['version'],
                    'ip_address' => $ip,
                    'user_agent' => $agent,
                ]);
            }
            DB::commit();

            return response()->json(['message' => 'Agreements accepted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to record acceptance.'], 500);
        }
    }
}