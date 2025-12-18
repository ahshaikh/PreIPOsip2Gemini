<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-COMPLIANCE-ORCHESTRATOR
 * * ARCHITECTURAL FIX: 
 * Manages the transition between agreement versions.
 * Identifies users requiring re-acceptance via cryptographic hash comparison.
 */

namespace App\Services\Compliance;

use App\Models\User;
use App\Models\LegalAgreement;
use App\Models\LegalAgreementVersion;

class AgreementService
{
    /**
     * Check if a user needs to sign the latest version.
     * [AUDIT REQUIREMENT]: Version Transition Logic
     */
    public function needsReacceptance(User $user, string $slug): bool
    {
        $latest = LegalAgreementVersion::whereHas('legalAgreement', fn($q) => $q->where('slug', $slug))
            ->where('status', 'active')
            ->first();

        if (!$latest) return false;

        $lastSignature = $user->signatures()
            ->where('legal_agreement_id', $latest->legal_agreement_id)
            ->latest('signed_at')
            ->first();

        // [SECURITY FIX]: Verify signature against the latest content hash
        return !$lastSignature || ($lastSignature->content_hash_at_signing !== $latest->content_hash);
    }
}