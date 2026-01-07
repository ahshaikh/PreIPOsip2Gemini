# Detailed Implementations for FIX 12-18

**Project:** PreIPOsip Platform
**Date:** 2026-01-06
**Priority:** P3 (Low Priority - Backlog)
**Scope:** Complete production-ready implementations with migrations, services, tests, and documentation

---

## TABLE OF CONTENTS

1. [FIX 12: Campaign Approval Database Constraint](#fix-12-campaign-approval-database-constraint)
2. [FIX 13: TDS Reporting Module](#fix-13-tds-reporting-module)
3. [FIX 14: User Transaction Statement Generator](#fix-14-user-transaction-statement-generator)
4. [FIX 15: Email Notification System (Queued Jobs)](#fix-15-email-notification-system-queued-jobs)
5. [FIX 16: Rate Limiting for Public Endpoints](#fix-16-rate-limiting-for-public-endpoints)
6. [FIX 17: State Machine Pattern (Spatie)](#fix-17-state-machine-pattern-spatie)
7. [FIX 18: Transaction Cryptographic Signatures](#fix-18-transaction-cryptographic-signatures)
8. [Testing Strategy](#testing-strategy)
9. [Deployment Guide](#deployment-guide)

---

# FIX 12: Campaign Approval Database Constraint

## Problem Statement

**Issue:** Campaign can have `is_active = true` without `approved_at` timestamp (approval bypass risk)
**Impact:** Unapproved campaigns could be exploited by users
**Risk Level:** Medium

## Solution Overview

Add database constraint to enforce that active campaigns must be approved.

---

## Implementation

### 1. Migration: Add Database Constraint

**File:** `/backend/database/migrations/2026_01_06_001_add_campaign_approval_constraint.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, fix any existing violations
        DB::statement("
            UPDATE campaigns
            SET is_active = false
            WHERE is_active = true
            AND approved_at IS NULL
        ");

        // Add CHECK constraint
        DB::statement("
            ALTER TABLE campaigns
            ADD CONSTRAINT check_campaign_approval
            CHECK (
                is_active = false
                OR (is_active = true AND approved_at IS NOT NULL)
            )
        ");

        // Add index for performance
        Schema::table('campaigns', function (Blueprint $table) {
            $table->index(['is_active', 'approved_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE campaigns DROP CONSTRAINT check_campaign_approval");

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'approved_at']);
        });
    }
};
```

---

### 2. Model: Add Validation

**File:** `/backend/app/Models/Campaign.php` (Update)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'subtitle', 'code', 'description',
        'discount_type', 'discount_percent', 'discount_amount',
        'min_investment', 'max_discount',
        'usage_limit', 'usage_count', 'user_usage_limit',
        'start_at', 'end_at',
        'is_featured', 'is_active', 'is_archived',
        'created_by', 'approved_by', 'approved_at',
        'features', 'terms',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'approved_at' => 'datetime',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_archived' => 'boolean',
        'features' => 'array',
        'terms' => 'array',
    ];

    /**
     * Boot method to add validation
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function (Campaign $campaign) {
            // Enforce approval requirement for active campaigns
            if ($campaign->is_active && !$campaign->approved_at) {
                throw new \InvalidArgumentException(
                    'Campaign cannot be activated without approval. Set approved_at timestamp first.'
                );
            }
        });
    }

    /**
     * Scope: Only approved campaigns
     */
    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at');
    }

    /**
     * Scope: Only unapproved campaigns
     */
    public function scopeUnapproved($query)
    {
        return $query->whereNull('approved_at');
    }

    /**
     * Approve campaign
     */
    public function approve(int $adminId): void
    {
        if ($this->approved_at) {
            throw new \RuntimeException('Campaign already approved');
        }

        $this->update([
            'approved_by' => $adminId,
            'approved_at' => now(),
        ]);

        // Log to audit
        \App\Models\AuditLog::create([
            'action' => 'campaign.approved',
            'actor_id' => $adminId,
            'description' => "Approved campaign: {$this->title}",
            'metadata' => [
                'campaign_id' => $this->id,
                'campaign_code' => $this->code,
            ],
        ]);
    }

    // ... existing code
}
```

---

### 3. Controller: Update Approval Logic

**File:** `/backend/app/Http/Controllers/Api/Admin/CampaignController.php` (Update)

```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    /**
     * Approve campaign
     */
    public function approve(Campaign $campaign)
    {
        if ($campaign->approved_at) {
            return response()->json([
                'message' => 'Campaign already approved',
            ], 422);
        }

        $campaign->approve(auth()->id());

        return response()->json([
            'message' => 'Campaign approved successfully',
            'data' => $campaign->fresh(),
        ]);
    }

    /**
     * Activate campaign (requires approval)
     */
    public function activate(Campaign $campaign)
    {
        if (!$campaign->approved_at) {
            return response()->json([
                'message' => 'Campaign must be approved before activation',
            ], 422);
        }

        $campaign->update(['is_active' => true]);

        return response()->json([
            'message' => 'Campaign activated successfully',
            'data' => $campaign,
        ]);
    }
}
```

---

### 4. Routes: Add Approval Endpoint

**File:** `/backend/routes/api.php` (Update)

```php
// Admin campaign routes
Route::middleware(['auth:sanctum', 'role:admin', 'permission:campaigns.manage'])
    ->prefix('admin/campaigns')
    ->group(function () {
        Route::post('{campaign}/approve', [CampaignController::class, 'approve']);
        Route::post('{campaign}/activate', [CampaignController::class, 'activate']);
    });
```

---

### 5. Test: Campaign Approval Constraint

**File:** `/backend/tests/Feature/CampaignApprovalTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\{Campaign, User};
use Illuminate\Foundation\Testing\RefreshDatabase;

class CampaignApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_activate_campaign_without_approval()
    {
        $campaign = Campaign::factory()->create([
            'is_active' => false,
            'approved_at' => null,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be activated without approval');

        $campaign->update(['is_active' => true]);
    }

    public function test_can_activate_approved_campaign()
    {
        $campaign = Campaign::factory()->create([
            'is_active' => false,
            'approved_at' => now(),
            'approved_by' => User::factory()->create()->id,
        ]);

        $campaign->update(['is_active' => true]);

        $this->assertTrue($campaign->is_active);
    }

    public function test_database_constraint_prevents_unapproved_active_campaign()
    {
        $this->expectException(\PDOException::class);

        // Try to insert directly (bypassing model validation)
        \DB::table('campaigns')->insert([
            'title' => 'Test Campaign',
            'code' => 'TEST123',
            'is_active' => true,
            'approved_at' => null, // Violation
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_admin_can_approve_campaign()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $campaign = Campaign::factory()->create([
            'approved_at' => null,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/campaigns/{$campaign->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.approved_at', fn($val) => !is_null($val));

        $this->assertNotNull($campaign->fresh()->approved_at);
    }
}
```

---

## Rollout Plan

1. **Pre-deployment:** Run SQL to fix existing violations
2. **Deploy migration:** Add constraint
3. **Monitor:** Check for any constraint violation errors in logs
4. **Update frontend:** Add approval button in admin panel

---

# FIX 13: TDS Reporting Module

## Problem Statement

**Issue:** TDS fields exist in Transaction, Withdrawal, BonusTransaction but no reporting/remittance tracking
**Impact:** Cannot generate TDS certificates or track government payments
**Risk Level:** High (Regulatory compliance)

## Solution Overview

Build comprehensive TDS module with quarterly reporting, Form 16A generation, and remittance tracking.

---

## Implementation

### 1. Migration: TDS Tables

**File:** `/backend/database/migrations/2026_01_06_002_create_tds_tables.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // TDS Quarters (Financial Year basis)
        Schema::create('tds_quarters', function (Blueprint $table) {
            $table->id();
            $table->string('financial_year'); // e.g., "2025-26"
            $table->integer('quarter'); // 1, 2, 3, 4
            $table->date('start_date');
            $table->date('end_date');
            $table->date('due_date'); // Filing due date
            $table->enum('status', ['open', 'calculated', 'filed', 'paid', 'closed']);
            $table->bigInteger('total_tds_collected_paise')->default(0);
            $table->bigInteger('total_tds_paid_paise')->default(0);
            $table->string('challan_number')->nullable();
            $table->date('payment_date')->nullable();
            $table->text('payment_proof_path')->nullable();
            $table->unsignedBigInteger('filed_by_admin_id')->nullable();
            $table->timestamp('filed_at')->nullable();
            $table->json('filing_metadata')->nullable();
            $table->timestamps();

            $table->unique(['financial_year', 'quarter']);
            $table->foreign('filed_by_admin_id')->references('id')->on('users');
        });

        // TDS Deductions (Individual deductions for each transaction)
        Schema::create('tds_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tds_quarter_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('deductee_pan')->nullable(); // From UserKyc
            $table->string('deductee_name');

            // Polymorphic relation to source transaction
            $table->string('source_type'); // Transaction, BonusTransaction, Withdrawal
            $table->unsignedBigInteger('source_id');

            $table->enum('income_type', [
                'interest', // Interest income
                'bonus', // Platform bonus
                'profit_share', // Profit sharing
                'other' // Other income
            ]);

            $table->bigInteger('gross_amount_paise'); // Before TDS
            $table->decimal('tds_rate', 5, 2); // e.g., 10.00 for 10%
            $table->bigInteger('tds_amount_paise'); // Deducted TDS
            $table->bigInteger('net_amount_paise'); // After TDS

            $table->date('deduction_date');
            $table->string('section')->default('194B'); // IT Act section

            $table->boolean('certificate_generated')->default(false);
            $table->string('certificate_number')->nullable()->unique();
            $table->timestamp('certificate_generated_at')->nullable();
            $table->text('certificate_path')->nullable();

            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index(['user_id', 'tds_quarter_id']);
            $table->index('deduction_date');
        });

        // TDS Certificates (Form 16A)
        Schema::create('tds_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tds_quarter_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('certificate_number')->unique(); // Auto-generated
            $table->string('financial_year');
            $table->integer('quarter');

            $table->string('deductee_pan');
            $table->string('deductee_name');
            $table->text('deductee_address')->nullable();

            $table->bigInteger('total_gross_amount_paise');
            $table->bigInteger('total_tds_amount_paise');
            $table->bigInteger('total_net_amount_paise');

            $table->json('deduction_details'); // Array of TDS deductions

            $table->string('challan_number')->nullable();
            $table->date('challan_date')->nullable();

            $table->text('certificate_pdf_path');
            $table->unsignedBigInteger('generated_by_admin_id');
            $table->timestamp('generated_at');

            $table->boolean('sent_to_user')->default(false);
            $table->timestamp('sent_at')->nullable();

            $table->timestamps();

            $table->foreign('generated_by_admin_id')->references('id')->on('users');
            $table->index(['user_id', 'financial_year', 'quarter']);
        });

        // TDS Settings (configurable rates)
        Schema::create('tds_settings', function (Blueprint $table) {
            $table->id();
            $table->string('income_type'); // bonus, profit_share, interest
            $table->decimal('rate', 5, 2); // TDS rate percentage
            $table->bigInteger('threshold_amount_paise')->default(0); // Minimum for TDS
            $table->string('section')->default('194B'); // IT Act section
            $table->boolean('is_active')->default(true);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->index(['income_type', 'is_active', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tds_certificates');
        Schema::dropIfExists('tds_deductions');
        Schema::dropIfExists('tds_quarters');
        Schema::dropIfExists('tds_settings');
    }
};
```

---

### 2. Models

#### TdsQuarter Model

**File:** `/backend/app/Models/TdsQuarter.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TdsQuarter extends Model
{
    protected $fillable = [
        'financial_year', 'quarter', 'start_date', 'end_date', 'due_date',
        'status', 'total_tds_collected_paise', 'total_tds_paid_paise',
        'challan_number', 'payment_date', 'payment_proof_path',
        'filed_by_admin_id', 'filed_at', 'filing_metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'due_date' => 'date',
        'payment_date' => 'date',
        'filed_at' => 'datetime',
        'filing_metadata' => 'array',
        'total_tds_collected_paise' => 'integer',
        'total_tds_paid_paise' => 'integer',
    ];

    // Relationships
    public function deductions()
    {
        return $this->hasMany(TdsDeduction::class);
    }

    public function certificates()
    {
        return $this->hasMany(TdsCertificate::class);
    }

    public function filedBy()
    {
        return $this->belongsTo(User::class, 'filed_by_admin_id');
    }

    // Accessors
    public function getTotalTdsCollectedAttribute(): float
    {
        return $this->total_tds_collected_paise / 100;
    }

    public function getTotalTdsPaidAttribute(): float
    {
        return $this->total_tds_paid_paise / 100;
    }

    // Scopes
    public function scopeForFinancialYear($query, string $fy)
    {
        return $query->where('financial_year', $fy);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'open');
    }

    // Helper methods
    public static function getCurrentQuarter(): ?self
    {
        $today = now();
        return self::where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->first();
    }

    public function calculateTotalTds(): void
    {
        $total = $this->deductions()
            ->sum('tds_amount_paise');

        $this->update([
            'total_tds_collected_paise' => $total,
        ]);
    }
}
```

#### TdsDeduction Model

**File:** `/backend/app/Models/TdsDeduction.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TdsDeduction extends Model
{
    protected $fillable = [
        'tds_quarter_id', 'user_id', 'deductee_pan', 'deductee_name',
        'source_type', 'source_id', 'income_type',
        'gross_amount_paise', 'tds_rate', 'tds_amount_paise', 'net_amount_paise',
        'deduction_date', 'section',
        'certificate_generated', 'certificate_number', 'certificate_generated_at', 'certificate_path',
    ];

    protected $casts = [
        'deduction_date' => 'date',
        'certificate_generated_at' => 'datetime',
        'certificate_generated' => 'boolean',
        'gross_amount_paise' => 'integer',
        'tds_amount_paise' => 'integer',
        'net_amount_paise' => 'integer',
        'tds_rate' => 'decimal:2',
    ];

    // Relationships
    public function quarter()
    {
        return $this->belongsTo(TdsQuarter::class, 'tds_quarter_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function source()
    {
        return $this->morphTo();
    }

    // Accessors
    public function getGrossAmountAttribute(): float
    {
        return $this->gross_amount_paise / 100;
    }

    public function getTdsAmountAttribute(): float
    {
        return $this->tds_amount_paise / 100;
    }

    public function getNetAmountAttribute(): float
    {
        return $this->net_amount_paise / 100;
    }
}
```

#### TdsCertificate Model

**File:** `/backend/app/Models/TdsCertificate.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TdsCertificate extends Model
{
    protected $fillable = [
        'tds_quarter_id', 'user_id', 'certificate_number',
        'financial_year', 'quarter',
        'deductee_pan', 'deductee_name', 'deductee_address',
        'total_gross_amount_paise', 'total_tds_amount_paise', 'total_net_amount_paise',
        'deduction_details', 'challan_number', 'challan_date',
        'certificate_pdf_path', 'generated_by_admin_id', 'generated_at',
        'sent_to_user', 'sent_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'sent_at' => 'datetime',
        'challan_date' => 'date',
        'deduction_details' => 'array',
        'sent_to_user' => 'boolean',
        'total_gross_amount_paise' => 'integer',
        'total_tds_amount_paise' => 'integer',
        'total_net_amount_paise' => 'integer',
    ];

    // Relationships
    public function quarter()
    {
        return $this->belongsTo(TdsQuarter::class, 'tds_quarter_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by_admin_id');
    }

    // Accessors
    public function getTotalGrossAmountAttribute(): float
    {
        return $this->total_gross_amount_paise / 100;
    }

    public function getTotalTdsAmountAttribute(): float
    {
        return $this->total_tds_amount_paise / 100;
    }

    public function getTotalNetAmountAttribute(): float
    {
        return $this->total_net_amount_paise / 100;
    }
}
```

---

### 3. Service: TDS Service

**File:** `/backend/app/Services/TdsService.php`

```php
<?php

namespace App\Services;

use App\Models\{TdsQuarter, TdsDeduction, TdsCertificate, User, UserKyc, Transaction, BonusTransaction, Withdrawal};
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class TdsService
{
    /**
     * Calculate TDS rate for given income type and amount
     */
    public function calculateTdsRate(string $incomeType, int $amountPaise): array
    {
        $setting = DB::table('tds_settings')
            ->where('income_type', $incomeType)
            ->where('is_active', true)
            ->where('effective_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', now());
            })
            ->first();

        if (!$setting) {
            return [
                'rate' => 0,
                'threshold_paise' => 0,
                'section' => '194B',
                'should_deduct' => false,
            ];
        }

        $shouldDeduct = $amountPaise >= $setting->threshold_amount_paise;

        return [
            'rate' => (float) $setting->rate,
            'threshold_paise' => (int) $setting->threshold_amount_paise,
            'section' => $setting->section,
            'should_deduct' => $shouldDeduct,
        ];
    }

    /**
     * Record TDS deduction
     */
    public function recordDeduction(
        int $userId,
        string $sourceType,
        int $sourceId,
        string $incomeType,
        int $grossAmountPaise,
        int $tdsAmountPaise
    ): TdsDeduction {
        $quarter = TdsQuarter::getCurrentQuarter();

        if (!$quarter) {
            throw new \RuntimeException('No active TDS quarter found');
        }

        $user = User::with('userKyc')->findOrFail($userId);
        $kyc = $user->userKyc;

        $tdsRate = $tdsAmountPaise > 0
            ? ($tdsAmountPaise / $grossAmountPaise) * 100
            : 0;

        $deduction = TdsDeduction::create([
            'tds_quarter_id' => $quarter->id,
            'user_id' => $userId,
            'deductee_pan' => $kyc?->pan_number,
            'deductee_name' => $user->name ?? $user->username,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'income_type' => $incomeType,
            'gross_amount_paise' => $grossAmountPaise,
            'tds_rate' => $tdsRate,
            'tds_amount_paise' => $tdsAmountPaise,
            'net_amount_paise' => $grossAmountPaise - $tdsAmountPaise,
            'deduction_date' => now(),
            'section' => $this->calculateTdsRate($incomeType, $grossAmountPaise)['section'],
        ]);

        // Update quarter total
        $quarter->increment('total_tds_collected_paise', $tdsAmountPaise);

        return $deduction;
    }

    /**
     * Calculate TDS for current quarter
     */
    public function calculateQuarterTds(TdsQuarter $quarter): void
    {
        DB::transaction(function () use ($quarter) {
            // Delete existing deductions for recalculation
            $quarter->deductions()->delete();

            // Process all transactions in quarter
            $this->processTransactions($quarter);
            $this->processBonusTransactions($quarter);
            $this->processWithdrawals($quarter);

            // Update quarter total
            $quarter->calculateTotalTds();

            $quarter->update(['status' => 'calculated']);
        });
    }

    /**
     * Process transactions for TDS
     */
    protected function processTransactions(TdsQuarter $quarter): void
    {
        $transactions = Transaction::where('type', 'credit')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$quarter->start_date, $quarter->end_date])
            ->where('tds_deducted_paise', '>', 0)
            ->get();

        foreach ($transactions as $txn) {
            $this->recordDeduction(
                $txn->user_id,
                Transaction::class,
                $txn->id,
                'interest', // or determine from reference_type
                $txn->amount_paise + $txn->tds_deducted_paise, // Gross
                $txn->tds_deducted_paise
            );
        }
    }

    /**
     * Process bonus transactions for TDS
     */
    protected function processBonusTransactions(TdsQuarter $quarter): void
    {
        $bonuses = BonusTransaction::whereBetween('created_at', [$quarter->start_date, $quarter->end_date])
            ->where('tds_deducted', '>', 0)
            ->get();

        foreach ($bonuses as $bonus) {
            $this->recordDeduction(
                $bonus->user_id,
                BonusTransaction::class,
                $bonus->id,
                'bonus',
                bcmul($bonus->amount + $bonus->tds_deducted, 100), // Convert to paise
                bcmul($bonus->tds_deducted, 100)
            );
        }
    }

    /**
     * Process withdrawals for TDS
     */
    protected function processWithdrawals(TdsQuarter $quarter): void
    {
        $withdrawals = Withdrawal::where('status', 'processed')
            ->whereBetween('processed_at', [$quarter->start_date, $quarter->end_date])
            ->where('tds_deducted', '>', 0)
            ->get();

        foreach ($withdrawals as $withdrawal) {
            $this->recordDeduction(
                $withdrawal->user_id,
                Withdrawal::class,
                $withdrawal->id,
                'other',
                bcmul($withdrawal->amount, 100),
                bcmul($withdrawal->tds_deducted, 100)
            );
        }
    }

    /**
     * Generate Form 16A certificate for user
     */
    public function generateCertificate(int $userId, int $quarterId): TdsCertificate
    {
        $quarter = TdsQuarter::findOrFail($quarterId);
        $user = User::with('userKyc', 'userProfile')->findOrFail($userId);

        // Get all deductions for this user in this quarter
        $deductions = TdsDeduction::where('tds_quarter_id', $quarterId)
            ->where('user_id', $userId)
            ->get();

        if ($deductions->isEmpty()) {
            throw new \RuntimeException('No TDS deductions found for this user in this quarter');
        }

        $totalGross = $deductions->sum('gross_amount_paise');
        $totalTds = $deductions->sum('tds_amount_paise');
        $totalNet = $deductions->sum('net_amount_paise');

        $certificateNumber = $this->generateCertificateNumber($quarter, $user);

        $certificate = TdsCertificate::create([
            'tds_quarter_id' => $quarterId,
            'user_id' => $userId,
            'certificate_number' => $certificateNumber,
            'financial_year' => $quarter->financial_year,
            'quarter' => $quarter->quarter,
            'deductee_pan' => $user->userKyc?->pan_number,
            'deductee_name' => $user->name ?? $user->username,
            'deductee_address' => $user->userProfile?->address,
            'total_gross_amount_paise' => $totalGross,
            'total_tds_amount_paise' => $totalTds,
            'total_net_amount_paise' => $totalNet,
            'deduction_details' => $deductions->toArray(),
            'challan_number' => $quarter->challan_number,
            'challan_date' => $quarter->payment_date,
            'certificate_pdf_path' => '', // Will be set after PDF generation
            'generated_by_admin_id' => auth()->id(),
            'generated_at' => now(),
        ]);

        // Generate PDF
        $pdfPath = $this->generatePdf($certificate);
        $certificate->update(['certificate_pdf_path' => $pdfPath]);

        // Mark deductions as certificate generated
        $deductions->each(function ($deduction) use ($certificateNumber) {
            $deduction->update([
                'certificate_generated' => true,
                'certificate_number' => $certificateNumber,
                'certificate_generated_at' => now(),
            ]);
        });

        return $certificate;
    }

    /**
     * Generate unique certificate number
     */
    protected function generateCertificateNumber(TdsQuarter $quarter, User $user): string
    {
        return sprintf(
            'TDS/%s/Q%d/%06d',
            $quarter->financial_year,
            $quarter->quarter,
            $user->id
        );
    }

    /**
     * Generate PDF for certificate
     */
    protected function generatePdf(TdsCertificate $certificate): string
    {
        $pdf = Pdf::loadView('pdfs.tds-certificate', [
            'certificate' => $certificate,
        ]);

        $filename = "tds_certificate_{$certificate->certificate_number}.pdf";
        $path = "tds/certificates/{$certificate->financial_year}/Q{$certificate->quarter}/{$filename}";

        \Storage::disk('private')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Generate bulk certificates for all users in quarter
     */
    public function generateBulkCertificates(int $quarterId): array
    {
        $quarter = TdsQuarter::findOrFail($quarterId);

        $userIds = TdsDeduction::where('tds_quarter_id', $quarterId)
            ->distinct()
            ->pluck('user_id');

        $generated = [];
        $errors = [];

        foreach ($userIds as $userId) {
            try {
                $certificate = $this->generateCertificate($userId, $quarterId);
                $generated[] = $certificate->id;
            } catch (\Exception $e) {
                $errors[$userId] = $e->getMessage();
            }
        }

        return [
            'generated' => count($generated),
            'failed' => count($errors),
            'certificates' => $generated,
            'errors' => $errors,
        ];
    }
}
```

---

### 4. Controller: TDS Controller

**File:** `/backend/app/Http/Controllers/Api/Admin/TdsController.php`

```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\{TdsQuarter, TdsDeduction, TdsCertificate};
use App\Services\TdsService;
use Illuminate\Http\Request;

class TdsController extends Controller
{
    protected $tdsService;

    public function __construct(TdsService $tdsService)
    {
        $this->tdsService = $tdsService;
    }

    /**
     * List all TDS quarters
     */
    public function quarters()
    {
        $quarters = TdsQuarter::with('filedBy')
            ->orderBy('financial_year', 'desc')
            ->orderBy('quarter', 'desc')
            ->paginate(20);

        return response()->json(['data' => $quarters]);
    }

    /**
     * Get quarter details
     */
    public function showQuarter(TdsQuarter $quarter)
    {
        $quarter->load(['deductions.user', 'certificates']);

        $stats = [
            'total_deductions' => $quarter->deductions()->count(),
            'total_users' => $quarter->deductions()->distinct('user_id')->count(),
            'certificates_generated' => $quarter->certificates()->count(),
            'pending_certificates' => $quarter->deductions()
                ->where('certificate_generated', false)
                ->distinct('user_id')
                ->count(),
        ];

        return response()->json([
            'data' => $quarter,
            'stats' => $stats,
        ]);
    }

    /**
     * Calculate TDS for quarter
     */
    public function calculateQuarter(TdsQuarter $quarter)
    {
        try {
            $this->tdsService->calculateQuarterTds($quarter);

            return response()->json([
                'message' => 'TDS calculated successfully',
                'data' => $quarter->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'TDS calculation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate certificate for user
     */
    public function generateCertificate(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'quarter_id' => 'required|exists:tds_quarters,id',
        ]);

        try {
            $certificate = $this->tdsService->generateCertificate(
                $request->user_id,
                $request->quarter_id
            );

            return response()->json([
                'message' => 'Certificate generated successfully',
                'data' => $certificate,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Certificate generation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate certificates for all users in quarter
     */
    public function generateBulkCertificates(TdsQuarter $quarter)
    {
        $result = $this->tdsService->generateBulkCertificates($quarter->id);

        return response()->json([
            'message' => "Generated {$result['generated']} certificates",
            'data' => $result,
        ]);
    }

    /**
     * Download certificate
     */
    public function downloadCertificate(TdsCertificate $certificate)
    {
        if (!file_exists(storage_path("app/private/{$certificate->certificate_pdf_path}"))) {
            abort(404, 'Certificate file not found');
        }

        return response()->download(
            storage_path("app/private/{$certificate->certificate_pdf_path}"),
            "TDS_Certificate_{$certificate->certificate_number}.pdf"
        );
    }

    /**
     * Get user's TDS summary
     */
    public function userSummary(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'financial_year' => 'nullable|string',
        ]);

        $query = TdsDeduction::where('user_id', $request->user_id);

        if ($request->financial_year) {
            $query->whereHas('quarter', function ($q) use ($request) {
                $q->where('financial_year', $request->financial_year);
            });
        }

        $deductions = $query->with('quarter')->get();

        $summary = [
            'total_gross_paise' => $deductions->sum('gross_amount_paise'),
            'total_tds_paise' => $deductions->sum('tds_amount_paise'),
            'total_net_paise' => $deductions->sum('net_amount_paise'),
            'deduction_count' => $deductions->count(),
            'by_income_type' => $deductions->groupBy('income_type')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_tds_paise' => $group->sum('tds_amount_paise'),
                ];
            }),
        ];

        return response()->json([
            'data' => [
                'summary' => $summary,
                'deductions' => $deductions,
            ],
        ]);
    }
}
```

---

### 5. PDF View: Form 16A Template

**File:** `/backend/resources/views/pdfs/tds-certificate.blade.php`

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>TDS Certificate - Form 16A</title>
    <style>
        @page { margin: 2cm; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.5;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
        }
        .section {
            margin-bottom: 15px;
        }
        .section-title {
            background: #f0f0f0;
            padding: 5px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        table, th, td {
            border: 1px solid #333;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background: #e0e0e0;
            font-weight: bold;
        }
        .row {
            display: flex;
            margin-bottom: 5px;
        }
        .col {
            flex: 1;
        }
        .label {
            font-weight: bold;
            display: inline-block;
            width: 200px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .amount {
            text-align: right;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>CERTIFICATE UNDER SECTION 203 OF THE INCOME TAX ACT, 1961</h1>
        <h2 style="margin: 5px 0;">FOR TAX DEDUCTED AT SOURCE ON INCOME OTHER THAN SALARY</h2>
        <p style="margin: 5px 0;">[See rule 31(1)(a)]</p>
    </div>

    <div class="section">
        <div class="section-title">1. CERTIFICATE DETAILS</div>
        <div class="row">
            <div class="col">
                <span class="label">Certificate No:</span> {{ $certificate->certificate_number }}
            </div>
            <div class="col">
                <span class="label">Financial Year:</span> {{ $certificate->financial_year }}
            </div>
        </div>
        <div class="row">
            <div class="col">
                <span class="label">Quarter:</span> Q{{ $certificate->quarter }}
            </div>
            <div class="col">
                <span class="label">Generated On:</span> {{ $certificate->generated_at->format('d/m/Y') }}
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">2. DEDUCTOR DETAILS</div>
        <div class="row">
            <span class="label">Name:</span> PreIPOsip Platform Pvt Ltd
        </div>
        <div class="row">
            <span class="label">TAN:</span> {{ config('tds.deductor_tan', 'XXXX00000X') }}
        </div>
        <div class="row">
            <span class="label">PAN:</span> {{ config('tds.deductor_pan', 'XXXXX0000X') }}
        </div>
        <div class="row">
            <span class="label">Address:</span> {{ config('tds.deductor_address') }}
        </div>
    </div>

    <div class="section">
        <div class="section-title">3. DEDUCTEE DETAILS</div>
        <div class="row">
            <span class="label">Name:</span> {{ $certificate->deductee_name }}
        </div>
        <div class="row">
            <span class="label">PAN:</span> {{ $certificate->deductee_pan ?? 'NOT PROVIDED' }}
        </div>
        <div class="row">
            <span class="label">Address:</span> {{ $certificate->deductee_address ?? 'As per records' }}
        </div>
    </div>

    <div class="section">
        <div class="section-title">4. DETAILS OF TAX DEDUCTED</div>
        <table>
            <thead>
                <tr>
                    <th>Sr No</th>
                    <th>Date of Deduction</th>
                    <th>Income Type</th>
                    <th>Section</th>
                    <th>Gross Amount (₹)</th>
                    <th>TDS Rate (%)</th>
                    <th>TDS Amount (₹)</th>
                    <th>Net Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($certificate->deduction_details as $index => $deduction)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($deduction['deduction_date'])->format('d/m/Y') }}</td>
                    <td>{{ ucfirst($deduction['income_type']) }}</td>
                    <td>{{ $deduction['section'] }}</td>
                    <td class="amount">{{ number_format($deduction['gross_amount_paise'] / 100, 2) }}</td>
                    <td class="amount">{{ number_format($deduction['tds_rate'], 2) }}</td>
                    <td class="amount">{{ number_format($deduction['tds_amount_paise'] / 100, 2) }}</td>
                    <td class="amount">{{ number_format($deduction['net_amount_paise'] / 100, 2) }}</td>
                </tr>
                @endforeach
                <tr style="font-weight: bold; background: #f0f0f0;">
                    <td colspan="4">TOTAL</td>
                    <td class="amount">{{ number_format($certificate->total_gross_amount / 100, 2) }}</td>
                    <td></td>
                    <td class="amount">{{ number_format($certificate->total_tds_amount / 100, 2) }}</td>
                    <td class="amount">{{ number_format($certificate->total_net_amount / 100, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">5. TAX PAYMENT DETAILS</div>
        <div class="row">
            <span class="label">Challan No:</span> {{ $certificate->challan_number ?? 'Pending' }}
        </div>
        <div class="row">
            <span class="label">Challan Date:</span> {{ $certificate->challan_date ? \Carbon\Carbon::parse($certificate->challan_date)->format('d/m/Y') : 'Pending' }}
        </div>
    </div>

    <div class="section" style="margin-top: 40px;">
        <p style="text-align: right;">
            <strong>For PreIPOsip Platform Pvt Ltd</strong><br>
            <br><br>
            _________________________<br>
            Authorized Signatory<br>
            Name: {{ $certificate->generatedBy->name }}<br>
            Date: {{ $certificate->generated_at->format('d/m/Y') }}
        </p>
    </div>

    <div class="footer">
        <p>This is a computer-generated certificate and does not require a signature.</p>
        <p>Certificate generated on {{ $certificate->generated_at->format('d/m/Y H:i:s') }}</p>
        <p>PreIPOsip Platform | {{ config('app.url') }}</p>
    </div>
</body>
</html>
```

---

### 6. Command: Initialize TDS Quarters

**File:** `/backend/app/Console/Commands/InitializeTdsQuarters.php`

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TdsQuarter;
use Carbon\Carbon;

class InitializeTdsQuarters extends Command
{
    protected $signature = 'tds:init-quarters {financial_year}';
    protected $description = 'Initialize TDS quarters for a financial year';

    public function handle()
    {
        $fy = $this->argument('financial_year');

        // Validate format (e.g., "2025-26")
        if (!preg_match('/^\d{4}-\d{2}$/', $fy)) {
            $this->error('Invalid format. Use: YYYY-YY (e.g., 2025-26)');
            return 1;
        }

        list($startYear, $endYearShort) = explode('-', $fy);
        $endYear = '20' . $endYearShort;

        $quarters = [
            [
                'quarter' => 1,
                'start_date' => Carbon::create($startYear, 4, 1),
                'end_date' => Carbon::create($startYear, 6, 30),
                'due_date' => Carbon::create($startYear, 7, 31),
            ],
            [
                'quarter' => 2,
                'start_date' => Carbon::create($startYear, 7, 1),
                'end_date' => Carbon::create($startYear, 9, 30),
                'due_date' => Carbon::create($startYear, 10, 31),
            ],
            [
                'quarter' => 3,
                'start_date' => Carbon::create($startYear, 10, 1),
                'end_date' => Carbon::create($startYear, 12, 31),
                'due_date' => Carbon::create($endYear, 1, 31),
            ],
            [
                'quarter' => 4,
                'start_date' => Carbon::create($endYear, 1, 1),
                'end_date' => Carbon::create($endYear, 3, 31),
                'due_date' => Carbon::create($endYear, 5, 15),
            ],
        ];

        foreach ($quarters as $quarter) {
            TdsQuarter::updateOrCreate(
                [
                    'financial_year' => $fy,
                    'quarter' => $quarter['quarter'],
                ],
                [
                    'start_date' => $quarter['start_date'],
                    'end_date' => $quarter['end_date'],
                    'due_date' => $quarter['due_date'],
                    'status' => 'open',
                ]
            );

            $this->info("Created Q{$quarter['quarter']} ({$quarter['start_date']->format('Y-m-d')} to {$quarter['end_date']->format('Y-m-d')})");
        }

        $this->info("TDS quarters initialized for FY {$fy}");
        return 0;
    }
}
```

---

### 7. Seeder: TDS Settings

**File:** `/backend/database/seeders/TdsSettingsSeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TdsSettingsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tds_settings')->insert([
            [
                'income_type' => 'bonus',
                'rate' => 10.00,
                'threshold_amount_paise' => 250000, // ₹2,500 threshold
                'section' => '194B',
                'is_active' => true,
                'effective_from' => '2024-04-01',
                'effective_to' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'income_type' => 'profit_share',
                'rate' => 10.00,
                'threshold_amount_paise' => 250000,
                'section' => '194B',
                'is_active' => true,
                'effective_from' => '2024-04-01',
                'effective_to' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'income_type' => 'interest',
                'rate' => 10.00,
                'threshold_amount_paise' => 1000000, // ₹10,000 threshold
                'section' => '194A',
                'is_active' => true,
                'effective_from' => '2024-04-01',
                'effective_to' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'income_type' => 'other',
                'rate' => 10.00,
                'threshold_amount_paise' => 250000,
                'section' => '194J',
                'is_active' => true,
                'effective_from' => '2024-04-01',
                'effective_to' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
```

---

### 8. Routes: TDS Endpoints

**File:** `/backend/routes/api.php` (Update)

```php
// Admin TDS routes
Route::middleware(['auth:sanctum', 'role:admin', 'permission:reports.view_financial'])
    ->prefix('admin/tds')
    ->group(function () {
        Route::get('quarters', [TdsController::class, 'quarters']);
        Route::get('quarters/{quarter}', [TdsController::class, 'showQuarter']);
        Route::post('quarters/{quarter}/calculate', [TdsController::class, 'calculateQuarter']);
        Route::post('certificates/generate', [TdsController::class, 'generateCertificate']);
        Route::post('quarters/{quarter}/bulk-certificates', [TdsController::class, 'generateBulkCertificates']);
        Route::get('certificates/{certificate}/download', [TdsController::class, 'downloadCertificate']);
        Route::get('user-summary', [TdsController::class, 'userSummary']);
    });

// User TDS routes
Route::middleware(['auth:sanctum'])
    ->prefix('user/tds')
    ->group(function () {
        Route::get('my-certificates', [UserTdsController::class, 'myCertificates']);
        Route::get('certificates/{certificate}/download', [UserTdsController::class, 'downloadMyCertificate']);
        Route::get('summary', [UserTdsController::class, 'mySummary']);
    });
```

---

### 9. Config: TDS Configuration

**File:** `/backend/config/tds.php`

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Deductor Details
    |--------------------------------------------------------------------------
    */
    'deductor_name' => env('TDS_DEDUCTOR_NAME', 'PreIPOsip Platform Pvt Ltd'),
    'deductor_pan' => env('TDS_DEDUCTOR_PAN'),
    'deductor_tan' => env('TDS_DEDUCTOR_TAN'),
    'deductor_address' => env('TDS_DEDUCTOR_ADDRESS'),

    /*
    |--------------------------------------------------------------------------
    | Default TDS Rates
    |--------------------------------------------------------------------------
    */
    'default_rates' => [
        'bonus' => 10.00,
        'profit_share' => 10.00,
        'interest' => 10.00,
        'other' => 10.00,
    ],

    /*
    |--------------------------------------------------------------------------
    | TDS Thresholds (in paise)
    |--------------------------------------------------------------------------
    */
    'thresholds' => [
        'bonus' => 250000, // ₹2,500
        'profit_share' => 250000,
        'interest' => 1000000, // ₹10,000
        'other' => 250000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Certificate Settings
    |--------------------------------------------------------------------------
    */
    'certificate_prefix' => 'TDS',
    'auto_generate_certificates' => env('TDS_AUTO_GENERATE_CERTIFICATES', false),
];
```

---

### 10. Test: TDS Service Test

**File:** `/backend/tests/Feature/TdsServiceTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\{User, TdsQuarter, Transaction, Wallet};
use App\Services\TdsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TdsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $tdsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tdsService = app(TdsService::class);

        // Create TDS quarter
        TdsQuarter::create([
            'financial_year' => '2025-26',
            'quarter' => 1,
            'start_date' => '2025-04-01',
            'end_date' => '2025-06-30',
            'due_date' => '2025-07-31',
            'status' => 'open',
        ]);
    }

    public function test_calculates_tds_rate_correctly()
    {
        $this->seed(\Database\Seeders\TdsSettingsSeeder::class);

        $result = $this->tdsService->calculateTdsRate('bonus', 500000); // ₹5,000

        $this->assertEquals(10.00, $result['rate']);
        $this->assertTrue($result['should_deduct']);
        $this->assertEquals('194B', $result['section']);
    }

    public function test_does_not_deduct_below_threshold()
    {
        $this->seed(\Database\Seeders\TdsSettingsSeeder::class);

        $result = $this->tdsService->calculateTdsRate('bonus', 100000); // ₹1,000

        $this->assertFalse($result['should_deduct']);
    }

    public function test_records_tds_deduction()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);

        $transaction = Transaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'type' => 'credit',
            'status' => 'completed',
            'reference_type' => 'BonusTransaction',
            'reference_id' => 1,
            'amount_paise' => 500000,
            'balance_before_paise' => 0,
            'balance_after_paise' => 500000,
            'tds_deducted_paise' => 50000,
            'description' => 'Test bonus',
        ]);

        $deduction = $this->tdsService->recordDeduction(
            $user->id,
            Transaction::class,
            $transaction->id,
            'bonus',
            550000, // Gross
            50000 // TDS
        );

        $this->assertDatabaseHas('tds_deductions', [
            'user_id' => $user->id,
            'gross_amount_paise' => 550000,
            'tds_amount_paise' => 50000,
        ]);
    }

    public function test_generates_certificate()
    {
        $user = User::factory()->create();
        $quarter = TdsQuarter::first();

        // Create deductions
        $this->tdsService->recordDeduction(
            $user->id,
            Transaction::class,
            1,
            'bonus',
            500000,
            50000
        );

        $certificate = $this->tdsService->generateCertificate($user->id, $quarter->id);

        $this->assertNotNull($certificate->certificate_number);
        $this->assertEquals(500000, $certificate->total_gross_amount_paise);
        $this->assertEquals(50000, $certificate->total_tds_amount_paise);
    }
}
```

---

## Rollout Plan for FIX 13

1. **Week 1:** Deploy migrations, seed TDS settings, initialize quarters
2. **Week 2:** Backfill historical TDS deductions for current FY
3. **Week 3:** Test certificate generation in staging
4. **Week 4:** Train admin team, deploy to production
5. **Ongoing:** Generate quarterly certificates, file returns

---

# FIX 14: User Transaction Statement Generator

## Problem Statement

**Issue:** No downloadable transaction statements for users
**Impact:** Tax compliance gap, poor user experience
**Risk Level:** Medium

## Solution Overview

Generate PDF/Excel statements with all transactions, investments, bonuses, and withdrawals.

---

## Implementation

### 1. Service: Statement Generator Service

**File:** `/backend/app/Services/StatementGeneratorService.php`

```php
<?php

namespace App\Services;

use App\Models\{User, Transaction, UserInvestment, BonusTransaction, Withdrawal, Payment};
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Carbon\Carbon;

class StatementGeneratorService
{
    /**
     * Generate transaction statement for user
     */
    public function generateStatement(
        int $userId,
        string $startDate,
        string $endDate,
        string $format = 'pdf'
    ): string {
        $user = User::with([
            'wallet',
            'userProfile',
            'transactions' => fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]),
            'bonusTransactions' => fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]),
            'withdrawals' => fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]),
            'payments' => fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]),
            'userInvestments' => fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]),
        ])->findOrFail($userId);

        $data = $this->prepareStatementData($user, $startDate, $endDate);

        if ($format === 'pdf') {
            return $this->generatePdf($data);
        } elseif ($format === 'excel') {
            return $this->generateExcel($data);
        }

        throw new \InvalidArgumentException("Unsupported format: {$format}");
    }

    /**
     * Prepare statement data
     */
    protected function prepareStatementData(User $user, string $startDate, string $endDate): array
    {
        // Get opening balance (balance at start of period)
        $openingTransaction = Transaction::where('user_id', $user->id)
            ->where('created_at', '<', $startDate)
            ->orderBy('created_at', 'desc')
            ->first();

        $openingBalance = $openingTransaction
            ? $openingTransaction->balance_after_paise
            : 0;

        // Get closing balance (current balance)
        $closingBalance = $user->wallet->balance_paise ?? 0;

        // Combine all transactions chronologically
        $allTransactions = $this->combineTransactions($user, $startDate, $endDate);

        // Calculate summaries
        $summary = $this->calculateSummary($user, $startDate, $endDate);

        return [
            'user' => $user,
            'period' => [
                'start' => Carbon::parse($startDate),
                'end' => Carbon::parse($endDate),
            ],
            'opening_balance_paise' => $openingBalance,
            'closing_balance_paise' => $closingBalance,
            'transactions' => $allTransactions,
            'summary' => $summary,
            'generated_at' => now(),
        ];
    }

    /**
     * Combine all transaction types chronologically
     */
    protected function combineTransactions(User $user, string $startDate, string $endDate): array
    {
        $transactions = [];

        // Wallet transactions
        foreach ($user->transactions as $txn) {
            $transactions[] = [
                'date' => $txn->created_at,
                'type' => 'Wallet ' . ucfirst($txn->type),
                'description' => $txn->description,
                'reference' => $txn->transaction_id,
                'debit' => $txn->type === 'debit' ? $txn->amount_paise : null,
                'credit' => $txn->type === 'credit' ? $txn->amount_paise : null,
                'balance' => $txn->balance_after_paise,
            ];
        }

        // Investments
        foreach ($user->userInvestments as $inv) {
            $transactions[] = [
                'date' => $inv->created_at,
                'type' => 'Investment',
                'description' => "Allocated {$inv->units_allocated} units of {$inv->product->name}",
                'reference' => "INV-{$inv->id}",
                'debit' => $inv->value_allocated * 100, // Convert to paise
                'credit' => null,
                'balance' => null,
            ];
        }

        // Bonuses
        foreach ($user->bonusTransactions as $bonus) {
            $transactions[] = [
                'date' => $bonus->created_at,
                'type' => 'Bonus - ' . ucfirst($bonus->type),
                'description' => $bonus->description ?? ucfirst($bonus->type) . ' bonus',
                'reference' => "BONUS-{$bonus->id}",
                'debit' => null,
                'credit' => $bonus->amount * 100, // Convert to paise
                'balance' => null,
            ];
        }

        // Withdrawals
        foreach ($user->withdrawals as $withdrawal) {
            $transactions[] = [
                'date' => $withdrawal->created_at,
                'type' => 'Withdrawal',
                'description' => "Withdrawal to bank (UTR: {$withdrawal->utr_number})",
                'reference' => "WD-{$withdrawal->id}",
                'debit' => $withdrawal->amount * 100,
                'credit' => null,
                'balance' => null,
            ];
        }

        // Sort by date
        usort($transactions, fn($a, $b) => $a['date'] <=> $b['date']);

        return $transactions;
    }

    /**
     * Calculate summary statistics
     */
    protected function calculateSummary(User $user, string $startDate, string $endDate): array
    {
        return [
            'total_credits_paise' => $user->transactions()
                ->where('type', 'credit')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount_paise'),

            'total_debits_paise' => $user->transactions()
                ->where('type', 'debit')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount_paise'),

            'total_investments_paise' => $user->userInvestments()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum(\DB::raw('value_allocated * 100')),

            'total_bonuses_paise' => $user->bonusTransactions()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum(\DB::raw('amount * 100')),

            'total_withdrawals_paise' => $user->withdrawals()
                ->where('status', 'processed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum(\DB::raw('amount * 100')),

            'total_tds_paise' => $user->transactions()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('tds_deducted_paise'),
        ];
    }

    /**
     * Generate PDF statement
     */
    protected function generatePdf(array $data): string
    {
        $pdf = Pdf::loadView('pdfs.transaction-statement', $data);

        $filename = sprintf(
            'statement_%d_%s_to_%s.pdf',
            $data['user']->id,
            $data['period']['start']->format('Ymd'),
            $data['period']['end']->format('Ymd')
        );

        $path = "statements/{$data['user']->id}/{$filename}";

        \Storage::disk('private')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Generate Excel statement
     */
    protected function generateExcel(array $data): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'PreIPOsip Platform - Transaction Statement');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // User details
        $row = 3;
        $sheet->setCellValue("A{$row}", 'User:');
        $sheet->setCellValue("B{$row}", $data['user']->username);
        $row++;
        $sheet->setCellValue("A{$row}", 'Email:');
        $sheet->setCellValue("B{$row}", $data['user']->email);
        $row++;
        $sheet->setCellValue("A{$row}", 'Period:');
        $sheet->setCellValue("B{$row}",
            $data['period']['start']->format('d/m/Y') . ' to ' . $data['period']['end']->format('d/m/Y')
        );
        $row += 2;

        // Summary
        $sheet->setCellValue("A{$row}", 'SUMMARY');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue("A{$row}", 'Opening Balance:');
        $sheet->setCellValue("B{$row}", number_format($data['opening_balance_paise'] / 100, 2));
        $row++;

        $sheet->setCellValue("A{$row}", 'Closing Balance:');
        $sheet->setCellValue("B{$row}", number_format($data['closing_balance_paise'] / 100, 2));
        $row++;

        $sheet->setCellValue("A{$row}", 'Total Credits:');
        $sheet->setCellValue("B{$row}", number_format($data['summary']['total_credits_paise'] / 100, 2));
        $row++;

        $sheet->setCellValue("A{$row}", 'Total Debits:');
        $sheet->setCellValue("B{$row}", number_format($data['summary']['total_debits_paise'] / 100, 2));
        $row += 2;

        // Transaction table header
        $sheet->setCellValue("A{$row}", 'Date');
        $sheet->setCellValue("B{$row}", 'Type');
        $sheet->setCellValue("C{$row}", 'Description');
        $sheet->setCellValue("D{$row}", 'Reference');
        $sheet->setCellValue("E{$row}", 'Debit (₹)');
        $sheet->setCellValue("F{$row}", 'Credit (₹)');
        $sheet->setCellValue("G{$row}", 'Balance (₹)');
        $sheet->getStyle("A{$row}:G{$row}")->getFont()->setBold(true);
        $row++;

        // Transaction rows
        foreach ($data['transactions'] as $txn) {
            $sheet->setCellValue("A{$row}", $txn['date']->format('d/m/Y H:i'));
            $sheet->setCellValue("B{$row}", $txn['type']);
            $sheet->setCellValue("C{$row}", $txn['description']);
            $sheet->setCellValue("D{$row}", $txn['reference']);
            $sheet->setCellValue("E{$row}", $txn['debit'] ? number_format($txn['debit'] / 100, 2) : '');
            $sheet->setCellValue("F{$row}", $txn['credit'] ? number_format($txn['credit'] / 100, 2) : '');
            $sheet->setCellValue("G{$row}", $txn['balance'] ? number_format($txn['balance'] / 100, 2) : '');
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Save
        $writer = new Xlsx($spreadsheet);
        $filename = sprintf(
            'statement_%d_%s_to_%s.xlsx',
            $data['user']->id,
            $data['period']['start']->format('Ymd'),
            $data['period']['end']->format('Ymd')
        );

        $path = "statements/{$data['user']->id}/{$filename}";
        $fullPath = storage_path("app/private/{$path}");

        // Ensure directory exists
        @mkdir(dirname($fullPath), 0755, true);

        $writer->save($fullPath);

        return $path;
    }

    /**
     * Generate annual tax statement
     */
    public function generateAnnualTaxStatement(int $userId, string $financialYear): string
    {
        list($startYear, $endYearShort) = explode('-', $financialYear);
        $endYear = '20' . $endYearShort;

        $startDate = "{$startYear}-04-01";
        $endDate = "{$endYear}-03-31";

        $user = User::with([
            'tdsDeductions' => fn($q) => $q->whereHas('quarter', fn($q2) =>
                $q2->where('financial_year', $financialYear)
            ),
            'bonusTransactions' => fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]),
            'userInvestments' => fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]),
        ])->findOrFail($userId);

        $data = [
            'user' => $user,
            'financial_year' => $financialYear,
            'total_income_paise' => $user->bonusTransactions->sum('amount') * 100,
            'total_tds_paise' => $user->tdsDeductions->sum('tds_amount_paise'),
            'total_investments_paise' => $user->userInvestments->sum('value_allocated') * 100,
            'deductions' => $user->tdsDeductions,
            'generated_at' => now(),
        ];

        $pdf = Pdf::loadView('pdfs.annual-tax-statement', $data);

        $filename = "annual_tax_statement_{$userId}_{$financialYear}.pdf";
        $path = "statements/{$userId}/tax/{$filename}";

        \Storage::disk('private')->put($path, $pdf->output());

        return $path;
    }
}
```

---

### 2. PDF View: Transaction Statement Template

**File:** `/backend/resources/views/pdfs/transaction-statement.blade.php`

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Transaction Statement</title>
    <style>
        @page { margin: 1.5cm; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }
        .user-details {
            background: #f5f5f5;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .user-details table {
            width: 100%;
            border: none;
        }
        .user-details td {
            padding: 3px;
            border: none;
        }
        .summary {
            background: #e8f4f8;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .summary table {
            width: 100%;
            border: none;
        }
        .summary td {
            padding: 5px;
            border: none;
        }
        .summary .label {
            font-weight: bold;
            width: 200px;
        }
        table.transactions {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 10px;
        }
        table.transactions th {
            background: #333;
            color: white;
            padding: 8px 5px;
            text-align: left;
            font-weight: bold;
        }
        table.transactions td {
            padding: 6px 5px;
            border-bottom: 1px solid #ddd;
        }
        table.transactions tr:nth-child(even) {
            background: #f9f9f9;
        }
        .amount {
            text-align: right;
            font-family: monospace;
        }
        .debit {
            color: #d32f2f;
        }
        .credit {
            color: #388e3c;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>TRANSACTION STATEMENT</h1>
        <p style="margin: 5px 0;">PreIPOsip Platform</p>
    </div>

    <div class="user-details">
        <table>
            <tr>
                <td><strong>User ID:</strong></td>
                <td>{{ $user->id }}</td>
                <td><strong>Username:</strong></td>
                <td>{{ $user->username }}</td>
            </tr>
            <tr>
                <td><strong>Email:</strong></td>
                <td>{{ $user->email }}</td>
                <td><strong>Mobile:</strong></td>
                <td>{{ $user->mobile ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Statement Period:</strong></td>
                <td colspan="3">{{ $period['start']->format('d/m/Y') }} to {{ $period['end']->format('d/m/Y') }}</td>
            </tr>
        </table>
    </div>

    <div class="summary">
        <h3 style="margin-top: 0;">Account Summary</h3>
        <table>
            <tr>
                <td class="label">Opening Balance:</td>
                <td class="amount">₹ {{ number_format($opening_balance_paise / 100, 2) }}</td>
                <td class="label">Closing Balance:</td>
                <td class="amount">₹ {{ number_format($closing_balance_paise / 100, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Total Credits:</td>
                <td class="amount credit">₹ {{ number_format($summary['total_credits_paise'] / 100, 2) }}</td>
                <td class="label">Total Debits:</td>
                <td class="amount debit">₹ {{ number_format($summary['total_debits_paise'] / 100, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Total Investments:</td>
                <td class="amount">₹ {{ number_format($summary['total_investments_paise'] / 100, 2) }}</td>
                <td class="label">Total Bonuses:</td>
                <td class="amount credit">₹ {{ number_format($summary['total_bonuses_paise'] / 100, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Total Withdrawals:</td>
                <td class="amount debit">₹ {{ number_format($summary['total_withdrawals_paise'] / 100, 2) }}</td>
                <td class="label">Total TDS Deducted:</td>
                <td class="amount">₹ {{ number_format($summary['total_tds_paise'] / 100, 2) }}</td>
            </tr>
        </table>
    </div>

    <h3>Transaction Details</h3>
    <table class="transactions">
        <thead>
            <tr>
                <th style="width: 12%;">Date</th>
                <th style="width: 15%;">Type</th>
                <th style="width: 30%;">Description</th>
                <th style="width: 13%;">Reference</th>
                <th style="width: 10%;">Debit (₹)</th>
                <th style="width: 10%;">Credit (₹)</th>
                <th style="width: 10%;">Balance (₹)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $txn)
            <tr>
                <td>{{ $txn['date']->format('d/m/Y H:i') }}</td>
                <td>{{ $txn['type'] }}</td>
                <td>{{ Str::limit($txn['description'], 50) }}</td>
                <td style="font-size: 9px;">{{ $txn['reference'] }}</td>
                <td class="amount debit">
                    @if($txn['debit'])
                        {{ number_format($txn['debit'] / 100, 2) }}
                    @endif
                </td>
                <td class="amount credit">
                    @if($txn['credit'])
                        {{ number_format($txn['credit'] / 100, 2) }}
                    @endif
                </td>
                <td class="amount">
                    @if($txn['balance'])
                        {{ number_format($txn['balance'] / 100, 2) }}
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 20px; color: #999;">
                    No transactions found for this period
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p><strong>This is a computer-generated statement and does not require a signature.</strong></p>
        <p>Generated on {{ $generated_at->format('d/m/Y H:i:s') }} | PreIPOsip Platform | {{ config('app.url') }}</p>
        <p style="font-size: 8px; margin-top: 10px;">
            For any discrepancies or queries, please contact support@preiposip.com within 7 days of statement generation.
        </p>
    </div>
</body>
</html>
```

---

### 3. Controller: Statement Controller

**File:** `/backend/app/Http/Controllers/Api/User/StatementController.php`

```php
<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\StatementGeneratorService;
use Illuminate\Http\Request;

class StatementController extends Controller
{
    protected $statementService;

    public function __construct(StatementGeneratorService $statementService)
    {
        $this->statementService = $statementService;
    }

    /**
     * Generate and download statement
     */
    public function generate(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'nullable|in:pdf,excel',
        ]);

        $format = $request->format ?? 'pdf';

        try {
            $path = $this->statementService->generateStatement(
                auth()->id(),
                $request->start_date,
                $request->end_date,
                $format
            );

            return response()->download(
                storage_path("app/private/{$path}"),
                basename($path)
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Statement generation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate monthly statement
     */
    public function monthly(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2050',
            'format' => 'nullable|in:pdf,excel',
        ]);

        $startDate = "{$request->year}-{$request->month}-01";
        $endDate = \Carbon\Carbon::parse($startDate)->endOfMonth()->format('Y-m-d');

        $path = $this->statementService->generateStatement(
            auth()->id(),
            $startDate,
            $endDate,
            $request->format ?? 'pdf'
        );

        return response()->download(
            storage_path("app/private/{$path}"),
            basename($path)
        );
    }

    /**
     * Generate annual tax statement
     */
    public function annualTax(Request $request)
    {
        $request->validate([
            'financial_year' => 'required|regex:/^\d{4}-\d{2}$/',
        ]);

        $path = $this->statementService->generateAnnualTaxStatement(
            auth()->id(),
            $request->financial_year
        );

        return response()->download(
            storage_path("app/private/{$path}"),
            basename($path)
        );
    }

    /**
     * List previously generated statements
     */
    public function list(Request $request)
    {
        $userId = auth()->id();
        $statementDir = storage_path("app/private/statements/{$userId}");

        if (!is_dir($statementDir)) {
            return response()->json(['data' => []]);
        }

        $files = \File::allFiles($statementDir);
        $statements = [];

        foreach ($files as $file) {
            $statements[] = [
                'filename' => $file->getFilename(),
                'size' => $file->getSize(),
                'created_at' => \Carbon\Carbon::createFromTimestamp($file->getCTime()),
                'download_url' => route('statements.download', ['filename' => $file->getFilename()]),
            ];
        }

        return response()->json(['data' => $statements]);
    }
}
```

---

### 4. Routes: Statement Endpoints

**File:** `/backend/routes/api.php` (Update)

```php
// User statement routes
Route::middleware(['auth:sanctum'])
    ->prefix('user/statements')
    ->group(function () {
        Route::post('generate', [StatementController::class, 'generate']);
        Route::post('monthly', [StatementController::class, 'monthly']);
        Route::post('annual-tax', [StatementController::class, 'annualTax']);
        Route::get('list', [StatementController::class, 'list']);
    });
```

---

### 5. Test: Statement Generator Test

**File:** `/backend/tests/Feature/StatementGeneratorTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\{User, Wallet, Transaction};
use App\Services\StatementGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StatementGeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(StatementGeneratorService::class);
    }

    public function test_generates_pdf_statement()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);

        // Create sample transactions
        Transaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'type' => 'credit',
            'status' => 'completed',
            'reference_type' => 'Payment',
            'reference_id' => 1,
            'amount_paise' => 100000,
            'balance_before_paise' => 0,
            'balance_after_paise' => 100000,
            'description' => 'Test deposit',
        ]);

        $path = $this->service->generateStatement(
            $user->id,
            now()->startOfMonth()->format('Y-m-d'),
            now()->endOfMonth()->format('Y-m-d'),
            'pdf'
        );

        $this->assertFileExists(storage_path("app/private/{$path}"));
        $this->assertStringEndsWith('.pdf', $path);
    }

    public function test_generates_excel_statement()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);

        $path = $this->service->generateStatement(
            $user->id,
            now()->startOfMonth()->format('Y-m-d'),
            now()->endOfMonth()->format('Y-m-d'),
            'excel'
        );

        $this->assertFileExists(storage_path("app/private/{$path}"));
        $this->assertStringEndsWith('.xlsx', $path);
    }

    public function test_user_can_download_statement()
    {
        $user = User::factory()->create();
        Wallet::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/statements/generate', [
                'start_date' => now()->startOfMonth()->format('Y-m-d'),
                'end_date' => now()->endOfMonth()->format('Y-m-d'),
                'format' => 'pdf',
            ]);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
```

---

## Rollout Plan for FIX 14

1. **Week 1:** Deploy service and controller
2. **Week 2:** Add frontend UI for statement generation
3. **Week 3:** Test with sample users
4. **Week 4:** Launch feature with email notifications

---

# (CONTINUED IN NEXT MESSAGE DUE TO LENGTH)

I'll continue with FIX 15-18 in the next message.
