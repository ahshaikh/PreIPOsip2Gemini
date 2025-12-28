<?php

namespace App\Services;

use App\Models\BulkPurchase;
use App\Models\Company;
use App\Models\CompanyShareListing;
use App\Models\Product;
use App\Models\User;
use App\Services\Accounting\AdminLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * CompanyInventoryService - Enforces Inventory Provenance
 *
 * PROTOCOL:
 * 1. NO inventory without verified company source
 * 2. NO inventory for unverified companies
 * 3. Manual inventory requires explicit admin approval with detailed reason
 * 4. All inventory changes recorded in admin ledger
 * 5. Provenance is ALWAYS traceable ("why does this inventory exist?")
 *
 * FAILURE SEMANTICS:
 * - Throws exception if company not verified
 * - Throws exception if provenance cannot be established
 * - Throws exception if manual entry lacks proper approval/documentation
 */
class CompanyInventoryService
{
    private AdminLedger $adminLedger;

    public function __construct(AdminLedger $adminLedger)
    {
        $this->adminLedger = $adminLedger;
    }

    /**
     * Create Inventory from Approved Company Share Listing
     *
     * PROTOCOL:
     * - Company MUST be verified (is_verified = true)
     * - Share listing MUST be approved
     * - Creates BulkPurchase with full provenance chain
     * - Records purchase in admin ledger as inventory expense
     *
     * @param CompanyShareListing $listing
     * @param float $actualCostPaid What admin actually paid company
     * @param array $additionalData
     * @return BulkPurchase
     * @throws ValidationException
     */
    public function createInventoryFromListing(
        CompanyShareListing $listing,
        float $actualCostPaid,
        array $additionalData = []
    ): BulkPurchase {
        // Verify company is approved
        if (!$listing->company->is_verified) {
            throw ValidationException::withMessages([
                'company' => 'Cannot create inventory from unverified company. Company must complete KYC and be approved first.',
            ]);
        }

        // Verify listing is approved
        if ($listing->status !== 'approved') {
            throw ValidationException::withMessages([
                'listing' => 'Cannot create inventory from unapproved listing. Current status: ' . $listing->status,
            ]);
        }

        // Verify listing hasn't already been converted to inventory
        if ($listing->bulk_purchase_id) {
            throw ValidationException::withMessages([
                'listing' => 'This listing has already been converted to inventory (Bulk Purchase #' . $listing->bulk_purchase_id . ')',
            ]);
        }

        return DB::transaction(function () use ($listing, $actualCostPaid, $additionalData) {
            // Create BulkPurchase with full provenance
            $bulkPurchase = BulkPurchase::create([
                'product_id' => $additionalData['product_id'],
                'company_id' => $listing->company_id, // REQUIRED - provenance
                'company_share_listing_id' => $listing->id, // REQUIRED - source document
                'source_type' => 'company_listing',
                'admin_id' => auth()->id(),

                // Financial details
                'face_value_purchased' => $listing->approved_quantity * $listing->face_value_per_share,
                'actual_cost_paid' => $actualCostPaid,
                'discount_percentage' => $this->calculateDiscount(
                    $listing->approved_quantity * $listing->face_value_per_share,
                    $actualCostPaid
                ),
                'extra_allocation_percentage' => $additionalData['extra_allocation_percentage'] ?? 0,

                // Provenance
                'seller_name' => $listing->company->company_name,
                'purchase_date' => now(),
                'notes' => $additionalData['notes'] ?? "Inventory created from approved company share listing #{$listing->id}",
                'verified_at' => now(),
            ]);

            // Link listing to bulk purchase
            $listing->update(['bulk_purchase_id' => $bulkPurchase->id]);

            // Record in admin ledger (inventory is an asset purchase)
            $this->adminLedger->recordInventoryPurchase(
                $actualCostPaid,
                $bulkPurchase->id,
                "Inventory purchased from {$listing->company->company_name} via listing #{$listing->id}"
            );

            Log::info("INVENTORY CREATED: From company listing with full provenance", [
                'bulk_purchase_id' => $bulkPurchase->id,
                'company_id' => $listing->company_id,
                'company_name' => $listing->company->company_name,
                'listing_id' => $listing->id,
                'cost_paid' => $actualCostPaid,
                'source_type' => 'company_listing',
            ]);

            return $bulkPurchase;
        });
    }

    /**
     * Create Manual Inventory Entry (CONSTRAINED PATH)
     *
     * PROTOCOL:
     * - Requires verified company (cannot create orphaned inventory)
     * - Requires explicit admin approval
     * - Requires detailed reason (min 50 chars)
     * - Requires source documentation
     * - All manual entries flagged for audit review
     *
     * USE CASES:
     * - Emergency inventory from existing company agreement
     * - Inventory from company before share listing workflow existed
     * - Corrections/adjustments with proper documentation
     *
     * @param Company $company
     * @param Product $product
     * @param array $inventoryData
     * @param User $approvingAdmin
     * @param string $reason Min 50 characters explaining why manual entry needed
     * @param array $sourceDocumentation File paths, agreement references, etc.
     * @return BulkPurchase
     * @throws ValidationException
     */
    public function createManualInventoryEntry(
        Company $company,
        Product $product,
        array $inventoryData,
        User $approvingAdmin,
        string $reason,
        array $sourceDocumentation = []
    ): BulkPurchase {
        // CRITICAL CHECKS - Manual inventory is HIGH RISK

        // 1. Company must be verified
        if (!$company->is_verified) {
            throw ValidationException::withMessages([
                'company' => 'Cannot create inventory for unverified company. Company ID: ' . $company->id . ' must be approved first.',
            ]);
        }

        // 2. Approving admin must have permission
        if (!$approvingAdmin->hasRole(['super-admin', 'admin'])) {
            throw ValidationException::withMessages([
                'admin' => 'Only super-admins can approve manual inventory entries.',
            ]);
        }

        // 3. Reason must be detailed
        if (strlen($reason) < 50) {
            throw ValidationException::withMessages([
                'reason' => 'Manual inventory reason must be at least 50 characters. Provide detailed justification.',
            ]);
        }

        // 4. Source documentation required
        if (empty($sourceDocumentation)) {
            throw ValidationException::withMessages([
                'documentation' => 'Manual inventory requires source documentation (agreements, invoices, approvals, etc.)',
            ]);
        }

        return DB::transaction(function () use (
            $company,
            $product,
            $inventoryData,
            $approvingAdmin,
            $reason,
            $sourceDocumentation
        ) {
            // Create BulkPurchase with manual entry provenance
            $bulkPurchase = BulkPurchase::create([
                'product_id' => $product->id,
                'company_id' => $company->id, // REQUIRED - provenance
                'company_share_listing_id' => null, // Manual entries don't have listing
                'source_type' => 'manual_entry',
                'admin_id' => auth()->id(),
                'approved_by_admin_id' => $approvingAdmin->id, // REQUIRED for manual

                // Financial details
                'face_value_purchased' => $inventoryData['face_value_purchased'],
                'actual_cost_paid' => $inventoryData['actual_cost_paid'],
                'discount_percentage' => $this->calculateDiscount(
                    $inventoryData['face_value_purchased'],
                    $inventoryData['actual_cost_paid']
                ),
                'extra_allocation_percentage' => $inventoryData['extra_allocation_percentage'] ?? 0,

                // Provenance (CRITICAL for manual entries)
                'seller_name' => $company->company_name,
                'purchase_date' => $inventoryData['purchase_date'] ?? now(),
                'manual_entry_reason' => $reason, // REQUIRED
                'source_documentation' => json_encode($sourceDocumentation), // REQUIRED
                'notes' => $inventoryData['notes'] ?? '',
                'verified_at' => now(),
            ]);

            // Record in admin ledger
            $this->adminLedger->recordInventoryPurchase(
                $inventoryData['actual_cost_paid'],
                $bulkPurchase->id,
                "MANUAL INVENTORY from {$company->company_name}. Reason: {$reason}"
            );

            // Flag for audit review
            Log::warning("MANUAL INVENTORY CREATED - REQUIRES AUDIT REVIEW", [
                'bulk_purchase_id' => $bulkPurchase->id,
                'company_id' => $company->id,
                'company_name' => $company->company_name,
                'cost_paid' => $inventoryData['actual_cost_paid'],
                'approved_by' => $approvingAdmin->id,
                'reason' => $reason,
                'source_documentation' => $sourceDocumentation,
            ]);

            return $bulkPurchase;
        });
    }

    /**
     * Verify Inventory Provenance
     *
     * Answers: "Why does this inventory exist? Who created it? What company supplied it?"
     *
     * @param BulkPurchase $bulkPurchase
     * @return array Full provenance chain
     */
    public function verifyProvenance(BulkPurchase $bulkPurchase): array
    {
        $provenance = [
            'bulk_purchase_id' => $bulkPurchase->id,
            'has_provenance' => false,
            'company_verified' => false,
            'source_verified' => false,
            'issues' => [],
        ];

        // Check company provenance
        if (!$bulkPurchase->company_id) {
            $provenance['issues'][] = 'CRITICAL: No company_id (orphaned inventory)';
            return $provenance;
        }

        $company = $bulkPurchase->company;
        if (!$company) {
            $provenance['issues'][] = 'CRITICAL: Company not found (broken FK)';
            return $provenance;
        }

        if (!$company->is_verified) {
            $provenance['issues'][] = 'WARNING: Company not verified';
        } else {
            $provenance['company_verified'] = true;
        }

        $provenance['company'] = [
            'id' => $company->id,
            'name' => $company->company_name,
            'is_verified' => $company->is_verified,
        ];

        // Check source provenance
        if ($bulkPurchase->source_type === 'company_listing') {
            if (!$bulkPurchase->company_share_listing_id) {
                $provenance['issues'][] = 'CRITICAL: source_type is company_listing but no listing_id';
                return $provenance;
            }

            $listing = $bulkPurchase->companyShareListing;
            if (!$listing) {
                $provenance['issues'][] = 'CRITICAL: Company share listing not found';
                return $provenance;
            }

            $provenance['source'] = [
                'type' => 'company_listing',
                'listing_id' => $listing->id,
                'listing_status' => $listing->status,
                'approved_at' => $listing->reviewed_at,
                'approved_by' => $listing->reviewedBy?->name,
            ];

            $provenance['source_verified'] = ($listing->status === 'approved');

        } elseif ($bulkPurchase->source_type === 'manual_entry') {
            if (!$bulkPurchase->approved_by_admin_id || !$bulkPurchase->manual_entry_reason) {
                $provenance['issues'][] = 'CRITICAL: manual_entry missing approval or reason';
                return $provenance;
            }

            $provenance['source'] = [
                'type' => 'manual_entry',
                'approved_by' => $bulkPurchase->approvedByAdmin?->name,
                'approved_at' => $bulkPurchase->verified_at,
                'reason' => $bulkPurchase->manual_entry_reason,
                'documentation' => json_decode($bulkPurchase->source_documentation, true),
            ];

            $provenance['source_verified'] = true; // Manual entries are pre-approved
        }

        // Check admin ledger entry
        $ledgerEntries = $this->adminLedger->getEntries(
            account: 'inventory',
            referenceType: 'bulk_purchase',
            referenceId: $bulkPurchase->id
        );

        $provenance['admin_ledger_tracked'] = $ledgerEntries->isNotEmpty();
        if ($ledgerEntries->isEmpty()) {
            $provenance['issues'][] = 'WARNING: Not tracked in admin ledger';
        }

        // Final verdict
        $provenance['has_provenance'] = empty($provenance['issues']) ||
            collect($provenance['issues'])->filter(fn($i) => str_starts_with($i, 'CRITICAL'))->isEmpty();

        return $provenance;
    }

    /**
     * Get All Inventory Without Proper Provenance (for audit)
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUnprovenInventory()
    {
        return BulkPurchase::query()
            ->whereNull('company_id')
            ->orWhereNull('verified_at')
            ->orWhere(function ($q) {
                $q->where('source_type', 'manual_entry')
                    ->whereNull('manual_entry_reason');
            })
            ->with('company', 'companyShareListing')
            ->get();
    }

    /**
     * Calculate discount percentage
     */
    private function calculateDiscount(float $faceValue, float $actualCost): float
    {
        if ($faceValue <= 0) {
            return 0;
        }

        return (($faceValue - $actualCost) / $faceValue) * 100;
    }
}
