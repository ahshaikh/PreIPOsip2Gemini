<?php

namespace App\Providers;

use App\Models\{Deal, BulkPurchase, CompanyShareListing};
use App\Policies\{DealPolicy, BulkPurchasePolicy, ShareListingPolicy};
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

/**
 * FIX 9 (P2): Authorization Service Provider
 *
 * Registers Laravel Policies for resource-level authorization
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Deal::class => DealPolicy::class,
        BulkPurchase::class => BulkPurchasePolicy::class,
        CompanyShareListing::class => ShareListingPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
