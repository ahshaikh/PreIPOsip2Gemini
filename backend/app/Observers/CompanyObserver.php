<?php

namespace App\Observers;

use App\Models\Company;
use Illuminate\Support\Facades\Log;

/**
 * FIX 5 (P1): Company Data Immutability Observer
 *
 * Enforces immutability of company disclosure fields after freeze
 * Prevents retroactive changes to data used in investor decisions
 */
class CompanyObserver
{
    /**
     * Critical disclosure fields that become immutable after freeze
     * These are the fields investors rely on for decision-making
     */
    protected array $immutableFields = [
        // Core Identity
        'name',
        'sector',
        'founded_year',
        'headquarters',
        'ceo_name',
        'website',
        'cin',
        'pan',

        // Financial Disclosures
        'latest_valuation',
        'total_funding',
        'funding_stage',
        'last_funding_round',
        'last_funding_date',
        'last_funding_amount',
        'revenue_last_year',
        'net_profit_last_year',
        'burn_rate',

        // Regulatory
        'sebi_registered',
        'sebi_registration_number',
        'legal_structure',

        // Product/Market
        'product_description',
        'market_segment',
        'competitive_advantage',
        'key_customers',
        'key_partners',

        // Risk Disclosures
        'risk_factors',
        'pending_litigations',
        'regulatory_risks',
        'market_risks',
    ];

    /**
     * Handle the Company "updating" event
     *
     * Blocks modifications to immutable fields if company is frozen
     * Exceptions: Super-admin can make corrective edits
     */
    public function updating(Company $company): void
    {
        // Only enforce if company is frozen
        if (!$company->frozen_at) {
            return;
        }

        // Super-admin can make corrective edits (log them)
        if (auth()->check() && auth()->user()->hasRole('super-admin')) {
            $this->logAdminOverride($company);
            return;
        }

        // Check for attempts to modify immutable fields
        $dirty = $company->getDirty();
        $violations = array_intersect($this->immutableFields, array_keys($dirty));

        if (!empty($violations)) {
            Log::critical('Company immutability violation attempted', [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'frozen_at' => $company->frozen_at,
                'violations' => $violations,
                'attempted_by' => auth()->id(),
                'old_values' => array_intersect_key($company->getOriginal(), array_flip($violations)),
                'new_values' => array_intersect_key($dirty, array_flip($violations)),
            ]);

            throw new \RuntimeException(
                'Company data is frozen after inventory purchase. Cannot edit: ' .
                implode(', ', $violations) .
                '. Only additive disclosures allowed via CompanyUpdate model. ' .
                'Contact super-admin for corrective edits.'
            );
        }
    }

    /**
     * Handle the Company "deleting" event
     *
     * Prevent deletion of frozen companies (regulatory requirement)
     */
    public function deleting(Company $company): void
    {
        if ($company->frozen_at) {
            Log::critical('Attempted to delete frozen company', [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'frozen_at' => $company->frozen_at,
                'attempted_by' => auth()->id(),
            ]);

            throw new \RuntimeException(
                'Cannot delete frozen company (regulatory requirement). ' .
                'Company has investor-relied-upon data. Use soft delete instead.'
            );
        }
    }

    /**
     * Log super-admin override for audit
     */
    protected function logAdminOverride(Company $company): void
    {
        $dirty = $company->getDirty();
        $violations = array_intersect($this->immutableFields, array_keys($dirty));

        if (!empty($violations)) {
            Log::warning('Super-admin override: Editing frozen company', [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'frozen_at' => $company->frozen_at,
                'admin_id' => auth()->id(),
                'admin_name' => auth()->user()->name ?? 'Unknown',
                'modified_fields' => $violations,
                'old_values' => array_intersect_key($company->getOriginal(), array_flip($violations)),
                'new_values' => array_intersect_key($dirty, array_flip($violations)),
            ]);

            // Create audit log
            \App\Models\AuditLog::create([
                'action' => 'company.frozen_data_edited',
                'actor_id' => auth()->id(),
                'description' => 'Super-admin edited frozen company data',
                'metadata' => [
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'frozen_at' => $company->frozen_at,
                    'modified_fields' => $violations,
                    'old_values' => array_intersect_key($company->getOriginal(), array_flip($violations)),
                    'new_values' => array_intersect_key($dirty, array_flip($violations)),
                ],
            ]);
        }
    }
}
