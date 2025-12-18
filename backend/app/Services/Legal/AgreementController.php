<?php

namespace App\Http\Controllers\Api\Legal;

use App\Http\Controllers\Controller;
use App\Models\LegalAgreement;
use App\Models\UserAgreementSignature;
use Illuminate\Http\Request;

class AgreementController extends Controller
{
    /**
     * Store a user's acceptance of a legal version.
     * [AUDIT FIX]: Captures forensic metadata for legal enforceability.
     */
    public function accept(Request $request, LegalAgreement $agreement)
    {
        $user = auth()->user();

        UserAgreementSignature::updateOrCreate(
            [
                'user_id' => $user->id,
                'legal_agreement_id' => $agreement->id
            ],
            [
                'version_signed' => $agreement->version,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'signed_at' => now(),
            ]
        );

        return response()->json(['message' => 'Agreement signed successfully.']);
    }
}