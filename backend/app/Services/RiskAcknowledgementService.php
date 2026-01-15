<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * PHASE 5 - Issue 3: Risk Acknowledgements
 *
 * PURPOSE:
 * Enforce and log investor risk acknowledgements before investment.
 * Maintain complete audit trail with timestamps, IPs, and context.
 *
 * DEFENSIVE PRINCIPLES:
 * - Never assume investor has acknowledged
 * - Log every acknowledgement attempt (granted or denied)
 * - Track expiry if acknowledgements have time limits
 * - Block investment if ANY required acknowledgement missing
 *
 * REQUIRED ACKNOWLEDGEMENTS:
 * 1. Illiquidity - Pre-IPO shares are highly illiquid
 * 2. No Guarantee - No guarantee of returns, may lose entire investment
 * 3. Platform Non-Advisory - Platform does not provide investment advice
 * 4. Material Changes - If material changes detected (conditional)
 */
class RiskAcknowledgementService
{
    /**
     * Required acknowledgement types for all investments
     */
    protected const REQUIRED_ACKNOWLEDGEMENTS = [
        'illiquidity',
        'no_guarantee',
        'platform_non_advisory',
    ];

    /**
     * Acknowledgement validity period (in days)
     * If null, acknowledgements never expire
     */
    protected const ACKNOWLEDGEMENT_VALIDITY_DAYS = 90;

    /**
     * Record investor risk acknowledgement
     *
     * DEFENSIVE: Logs every acknowledgement with full context.
     * Creates audit trail with IP, timestamp, acknowledgement text.
     *
     * @param int $userId
     * @param int $companyId
     * @param string $acknowledgementType
     * @param array $context Additional context (investment_id, snapshot_id, etc.)
     * @return int Acknowledgement ID
     */
    public function recordAcknowledgement(
        int $userId,
        int $companyId,
        string $acknowledgementType,
        array $context = []
    ): int {
        // Validate acknowledgement type
        $validTypes = array_merge(self::REQUIRED_ACKNOWLEDGEMENTS, ['material_changes']);
        if (!in_array($acknowledgementType, $validTypes)) {
            throw new \InvalidArgumentException("Invalid acknowledgement type: {$acknowledgementType}");
        }

        // Get acknowledgement text shown to user
        $acknowledgementText = $this->getAcknowledgementText($acknowledgementType);

        // Calculate expiry date
        $expiresAt = self::ACKNOWLEDGEMENT_VALIDITY_DAYS
            ? now()->addDays(self::ACKNOWLEDGEMENT_VALIDITY_DAYS)
            : null;

        // Check if acknowledgement already exists and is valid
        $existing = $this->getExistingAcknowledgement($userId, $companyId, $acknowledgementType);
        if ($existing && !$existing->is_expired) {
            Log::info('RISK ACKNOWLEDGEMENT: Already exists and valid', [
                'user_id' => $userId,
                'company_id' => $companyId,
                'acknowledgement_type' => $acknowledgementType,
                'existing_id' => $existing->id,
            ]);

            return $existing->id;
        }

        // Create new acknowledgement record
        $acknowledgementId = DB::table('investor_risk_acknowledgements')->insertGetId([
            'user_id' => $userId,
            'company_id' => $companyId,
            'acknowledgement_type' => $acknowledgementType,
            'acknowledged_at' => now(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'session_id' => session()?->getId(),
            'investment_id' => $context['investment_id'] ?? null,
            'platform_context_snapshot_id' => $context['snapshot_id'] ?? null,
            'acknowledgement_text_shown' => $acknowledgementText,
            'expires_at' => $expiresAt,
            'is_expired' => false,
            'metadata' => json_encode([
                'acknowledged_via' => $context['source'] ?? 'investment_flow',
                'user_type' => $context['user_type'] ?? 'investor',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Log acknowledgement event
        $this->logAcknowledgementEvent(
            $userId,
            $companyId,
            'acknowledgement_granted',
            [
                'acknowledgement_id' => $acknowledgementId,
                'acknowledgement_type' => $acknowledgementType,
                'expires_at' => $expiresAt,
            ]
        );

        Log::info('RISK ACKNOWLEDGEMENT RECORDED', [
            'acknowledgement_id' => $acknowledgementId,
            'user_id' => $userId,
            'company_id' => $companyId,
            'acknowledgement_type' => $acknowledgementType,
            'ip_address' => request()?->ip(),
            'expires_at' => $expiresAt,
        ]);

        return $acknowledgementId;
    }

    /**
     * Check if user has acknowledged all required risks for company
     *
     * DEFENSIVE: Returns detailed status for each acknowledgement.
     * Checks validity and expiry.
     *
     * @param int $userId
     * @param int $companyId
     * @param bool $includeMaterialChanges Whether to require material changes acknowledgement
     * @return array Status with missing acknowledgements
     */
    public function hasAcknowledgedAllRisks(
        int $userId,
        int $companyId,
        bool $includeMaterialChanges = false
    ): array {
        $requiredAcknowledgements = self::REQUIRED_ACKNOWLEDGEMENTS;

        if ($includeMaterialChanges) {
            $requiredAcknowledgements[] = 'material_changes';
        }

        $missing = [];
        $expired = [];
        $valid = [];

        foreach ($requiredAcknowledgements as $ackType) {
            $acknowledgement = $this->getExistingAcknowledgement($userId, $companyId, $ackType);

            if (!$acknowledgement) {
                $missing[] = $ackType;
            } elseif ($acknowledgement->is_expired || ($acknowledgement->expires_at && now()->gt($acknowledgement->expires_at))) {
                // Mark as expired if needed
                if (!$acknowledgement->is_expired) {
                    $this->markAcknowledgementExpired($acknowledgement->id);
                }
                $expired[] = $ackType;
            } else {
                $valid[] = [
                    'type' => $ackType,
                    'acknowledged_at' => $acknowledgement->acknowledged_at,
                    'expires_at' => $acknowledgement->expires_at,
                ];
            }
        }

        $allAcknowledged = empty($missing) && empty($expired);

        if (!$allAcknowledged) {
            // Log blocked investment attempt
            $this->logAcknowledgementEvent(
                $userId,
                $companyId,
                'investment_blocked_missing_ack',
                [
                    'missing_acknowledgements' => $missing,
                    'expired_acknowledgements' => $expired,
                ]
            );
        }

        return [
            'all_acknowledged' => $allAcknowledged,
            'missing' => $missing,
            'expired' => $expired,
            'valid' => $valid,
            'required_acknowledgements' => $requiredAcknowledgements,
        ];
    }

    /**
     * Get all acknowledgements for user and company
     *
     * @param int $userId
     * @param int $companyId
     * @return array Acknowledgement records
     */
    public function getAllAcknowledgements(int $userId, int $companyId): array
    {
        $acknowledgements = DB::table('investor_risk_acknowledgements')
            ->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->orderBy('acknowledged_at', 'desc')
            ->get();

        return $acknowledgements->map(function ($ack) {
            return [
                'id' => $ack->id,
                'acknowledgement_type' => $ack->acknowledgement_type,
                'acknowledged_at' => $ack->acknowledged_at,
                'expires_at' => $ack->expires_at,
                'is_expired' => $ack->is_expired,
                'ip_address' => $ack->ip_address,
                'investment_id' => $ack->investment_id,
            ];
        })->toArray();
    }

    /**
     * Renew expired acknowledgement
     *
     * @param int $userId
     * @param int $companyId
     * @param string $acknowledgementType
     * @return int New acknowledgement ID
     */
    public function renewAcknowledgement(
        int $userId,
        int $companyId,
        string $acknowledgementType
    ): int {
        // Mark old acknowledgement as expired
        $existing = $this->getExistingAcknowledgement($userId, $companyId, $acknowledgementType);
        if ($existing) {
            $this->markAcknowledgementExpired($existing->id);
        }

        // Create new acknowledgement
        $newAcknowledgementId = $this->recordAcknowledgement(
            $userId,
            $companyId,
            $acknowledgementType,
            ['source' => 'renewal']
        );

        // Log renewal event
        $this->logAcknowledgementEvent(
            $userId,
            $companyId,
            'acknowledgement_renewed',
            [
                'old_acknowledgement_id' => $existing?->id,
                'new_acknowledgement_id' => $newAcknowledgementId,
                'acknowledgement_type' => $acknowledgementType,
            ]
        );

        return $newAcknowledgementId;
    }

    /**
     * Check which acknowledgements are required for investment
     *
     * Checks if material changes exist to determine if that acknowledgement required.
     *
     * @param int $companyId
     * @return array Required acknowledgement types
     */
    public function getRequiredAcknowledgements(int $companyId): array
    {
        $required = self::REQUIRED_ACKNOWLEDGEMENTS;

        // Check if material changes exist
        $snapshotService = new PlatformContextSnapshotService();
        $currentSnapshot = $snapshotService->getCurrentSnapshot($companyId);

        if ($currentSnapshot && $currentSnapshot->has_material_changes) {
            $required[] = 'material_changes';
        }

        return array_map(function ($ackType) {
            return [
                'type' => $ackType,
                'text' => $this->getAcknowledgementText($ackType),
                'required' => true,
            ];
        }, $required);
    }

    /**
     * Batch record multiple acknowledgements
     *
     * @param int $userId
     * @param int $companyId
     * @param array $acknowledgementTypes
     * @param array $context
     * @return array Acknowledgement IDs
     */
    public function recordMultipleAcknowledgements(
        int $userId,
        int $companyId,
        array $acknowledgementTypes,
        array $context = []
    ): array {
        $acknowledgementIds = [];

        foreach ($acknowledgementTypes as $ackType) {
            $acknowledgementIds[$ackType] = $this->recordAcknowledgement(
                $userId,
                $companyId,
                $ackType,
                $context
            );
        }

        return $acknowledgementIds;
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Get existing acknowledgement if exists and valid
     */
    protected function getExistingAcknowledgement(int $userId, int $companyId, string $acknowledgementType): ?object
    {
        return DB::table('investor_risk_acknowledgements')
            ->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('acknowledgement_type', $acknowledgementType)
            ->orderBy('acknowledged_at', 'desc')
            ->first();
    }

    /**
     * Mark acknowledgement as expired
     */
    protected function markAcknowledgementExpired(int $acknowledgementId): void
    {
        DB::table('investor_risk_acknowledgements')
            ->where('id', $acknowledgementId)
            ->update([
                'is_expired' => true,
                'updated_at' => now(),
            ]);

        Log::info('RISK ACKNOWLEDGEMENT EXPIRED', [
            'acknowledgement_id' => $acknowledgementId,
        ]);
    }

    /**
     * Get acknowledgement text shown to user
     */
    protected function getAcknowledgementText(string $acknowledgementType): string
    {
        return match($acknowledgementType) {
            'illiquidity' => 'I understand that Pre-IPO investments are highly illiquid and I may not be able to sell my shares for an extended period, potentially years. There is no guarantee of an exit event or IPO.',

            'no_guarantee' => 'I understand that there is no guarantee of returns on this investment. Pre-IPO companies carry significant risk, and I may lose my entire investment. Past performance is not indicative of future results.',

            'platform_non_advisory' => 'I understand that this platform facilitates investments but does not provide investment advice, recommendations, or endorsements. I am making my own independent investment decision and should consult my financial advisor if needed.',

            'material_changes' => 'I acknowledge that material changes have been detected in this company\'s platform context or disclosures. I have reviewed these changes and understand they may affect the investment profile and risk assessment.',

            default => "I acknowledge the risks associated with '{$acknowledgementType}'.",
        };
    }

    /**
     * Log acknowledgement event to audit trail
     */
    protected function logAcknowledgementEvent(
        int $userId,
        int $companyId,
        string $eventType,
        array $details = []
    ): void {
        // Get current acknowledgement status
        $acknowledgements = DB::table('investor_risk_acknowledgements')
            ->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('is_expired', false)
            ->get(['acknowledgement_type', 'acknowledged_at', 'expires_at'])
            ->keyBy('acknowledgement_type')
            ->toArray();

        DB::table('investor_acknowledgement_log')->insert([
            'user_id' => $userId,
            'company_id' => $companyId,
            'event_type' => $eventType,
            'acknowledgements_status' => json_encode($acknowledgements),
            'event_details' => json_encode($details),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Cron job: Check and expire old acknowledgements
     *
     * Run daily to mark expired acknowledgements.
     *
     * @return array Statistics
     */
    public function expireOldAcknowledgements(): array
    {
        $expiredCount = DB::table('investor_risk_acknowledgements')
            ->where('is_expired', false)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update([
                'is_expired' => true,
                'updated_at' => now(),
            ]);

        Log::info('RISK ACKNOWLEDGEMENTS EXPIRED (CRON)', [
            'expired_count' => $expiredCount,
        ]);

        return [
            'expired_count' => $expiredCount,
            'checked_at' => now(),
        ];
    }

    /**
     * Get acknowledgement statistics for admin
     *
     * @param int|null $companyId Filter by company
     * @return array Statistics
     */
    public function getAcknowledgementStatistics(?int $companyId = null): array
    {
        $query = DB::table('investor_risk_acknowledgements');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $total = $query->count();
        $expired = (clone $query)->where('is_expired', true)->count();
        $valid = $total - $expired;

        $byType = DB::table('investor_risk_acknowledgements')
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->where('is_expired', false)
            ->select('acknowledgement_type', DB::raw('COUNT(*) as count'))
            ->groupBy('acknowledgement_type')
            ->get()
            ->pluck('count', 'acknowledgement_type')
            ->toArray();

        return [
            'total_acknowledgements' => $total,
            'valid_acknowledgements' => $valid,
            'expired_acknowledgements' => $expired,
            'by_type' => $byType,
            'company_id' => $companyId,
        ];
    }
}
