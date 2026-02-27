<?php

namespace App\Providers;

use App\Events\DisclosureApproved;
use App\Events\ChargebackConfirmed;
use App\Listeners\CheckTierPromotionOnDisclosureApproval;
use App\Listeners\UpdateUserRiskProfile;
use App\Models\{BulkPurchase, Company, CompanyDisclosure, DisclosureVersion, Withdrawal, SupportTicket};
use App\Observers\{BulkPurchaseObserver, CompanyObserver, CompanyDisclosureObserver, DisclosureVersionObserver, WithdrawalObserver, SupportTicketObserver};
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        // STORY 3.2: Automatic tier promotion on disclosure approval
        DisclosureApproved::class => [
            CheckTierPromotionOnDisclosureApproval::class,
        ],
        // V-DISPUTE-RISK-2026-003: Risk profile update on chargeback
        ChargebackConfirmed::class => [
            UpdateUserRiskProfile::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // FIX 2 (P0): Register BulkPurchase observer for immutability enforcement
        BulkPurchase::observe(BulkPurchaseObserver::class);

        // FIX 5 (P1): Register Company observer for post-purchase data freeze
        Company::observe(CompanyObserver::class);

        // FIX 18: Register Withdrawal observer for automatic fund locking
        Withdrawal::observe(WithdrawalObserver::class);

        // PHASE 1: Register DisclosureVersion observer for immutability enforcement
        DisclosureVersion::observe(DisclosureVersionObserver::class);

        // PHASE 4: Register CompanyDisclosure observer for auto-calculation
        // Triggers platform metrics and risk flag calculation when disclosure approved
        CompanyDisclosure::observe(CompanyDisclosureObserver::class);

        // [FIX]: Support auto-assignment
        SupportTicket::observe(SupportTicketObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
