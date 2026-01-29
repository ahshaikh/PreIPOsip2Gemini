<?php

namespace App\Providers;

use App\Models\{Deal, BulkPurchase, CompanyShareListing, Company, CompanyDisclosure, DisclosureClarification, Product};
use App\Policies\{DealPolicy, BulkPurchasePolicy, ShareListingPolicy, CompanyDisclosurePolicy, InvestmentPolicy, ProductPolicy};
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

/**
 * FIX 9 (P2): Authorization Service Provider
 *
 * Registers Laravel Policies for resource-level authorization
 *
 * PHASE 2 & 3: Governance Protocol Policies
 * - InvestmentPolicy: Guards investment operations against company lifecycle states
 * - CompanyDisclosurePolicy: Role-based access control for disclosure management
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // Existing policies
        Deal::class => DealPolicy::class,
        BulkPurchase::class => BulkPurchasePolicy::class,
        CompanyShareListing::class => ShareListingPolicy::class,
        Product::class => ProductPolicy::class,

        // Phase 2 & 3: Governance Protocol Policies
        Company::class => InvestmentPolicy::class, // Governs invest() action
        CompanyDisclosure::class => CompanyDisclosurePolicy::class,
        DisclosureClarification::class => CompanyDisclosurePolicy::class, // Uses answerClarification() method
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
