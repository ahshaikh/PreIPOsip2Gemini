# RECONSTRUCTION PLAN: Company/Share Selection & Investment Flow
**Feature**: Post-Subscription Investment Management
**Status**: Missing
**Priority**: High
**Estimated Effort**: 3-4 days

---

## PROBLEM STATEMENT

Users can complete the entire subscription flow (signup → KYC → plan selection → payment) but there's no mechanism to:
1. Browse available investment opportunities (deals)
2. Select specific companies/shares to invest in
3. Allocate their subscription funds to deals
4. Track their investment portfolio

**Current State**: Subscription payment completes → User sees dashboard → **Dead end**

**Required State**: Subscription payment completes → User browses deals → Selects investments → Tracks portfolio

---

## ARCHITECTURE OVERVIEW

### Existing Infrastructure to Leverage

✅ **Models**: Deal, Company, InvestorInterest
✅ **Services**: SubscriptionService, PaymentWebhookService
✅ **UI Patterns**: React Query, shadcn/ui components
✅ **Auth**: User authentication, KYC verification

### New Components Required

**Backend:**
- ❌ Investment model (user investments in deals)
- ❌ User Investment API (CRUD for investments)
- ❌ User Deal API (browse available deals)
- ❌ Portfolio service (aggregate user holdings)

**Frontend:**
- ❌ User deals page (/user/deals)
- ❌ User investments page (/user/investments)
- ❌ Investment modal component
- ❌ Portfolio dashboard widget

---

## RECONSTRUCTION STEPS

### Phase 1: Backend Models & Database (Day 1)

#### Step 1.1: Create Investment Model

**File**: `/backend/app/Models/Investment.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Investment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'subscription_id',
        'deal_id',
        'company_id',
        'investment_code',
        'shares_allocated',
        'price_per_share',
        'total_amount',
        'status',
        'invested_at',
        'exited_at',
        'exit_price_per_share',
        'exit_amount',
        'profit_loss',
        'notes',
    ];

    protected $casts = [
        'price_per_share' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'exit_price_per_share' => 'decimal:2',
        'exit_amount' => 'decimal:2',
        'profit_loss' => 'decimal:2',
        'invested_at' => 'datetime',
        'exited_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Accessors
    public function getCurrentValueAttribute()
    {
        // Calculate current value based on latest share price
        return $this->shares_allocated * ($this->deal->share_price ?? $this->price_per_share);
    }

    public function getUnrealizedProfitLossAttribute()
    {
        return $this->current_value - $this->total_amount;
    }
}
```

#### Step 1.2: Create Migration

**File**: `/backend/database/migrations/2025_12_23_000001_create_investments_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->foreignId('deal_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('set null');
            $table->string('investment_code')->unique();
            $table->integer('shares_allocated');
            $table->decimal('price_per_share', 15, 2);
            $table->decimal('total_amount', 15, 2);
            $table->enum('status', ['pending', 'active', 'exited', 'cancelled'])->default('pending');
            $table->timestamp('invested_at')->nullable();
            $table->timestamp('exited_at')->nullable();
            $table->decimal('exit_price_per_share', 15, 2)->nullable();
            $table->decimal('exit_amount', 15, 2)->nullable();
            $table->decimal('profit_loss', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['subscription_id']);
            $table->index(['deal_id']);
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('investments');
    }
};
```

#### Step 1.3: Update Related Models

**Add to Subscription model** (`/backend/app/Models/Subscription.php`):

```php
public function investments(): HasMany
{
    return $this->hasMany(Investment::class);
}

public function totalInvestedAttribute(): float
{
    return $this->investments()->sum('total_amount');
}

public function availableBalanceAttribute(): float
{
    return ($this->monthly_amount * $this->plan->duration_months) - $this->total_invested;
}
```

**Add to User model** (`/backend/app/Models/User.php`):

```php
public function investments(): HasMany
{
    return $this->hasMany(Investment::class);
}

public function activeInvestments(): HasMany
{
    return $this->investments()->where('status', 'active');
}
```

**Add to Deal model** (`/backend/app/Models/Deal.php`):

```php
public function investments(): HasMany
{
    return $this->hasMany(Investment::class);
}

public function remainingSharesAttribute(): int
{
    $allocated = $this->investments()->where('status', '!=', 'cancelled')->sum('shares_allocated');
    return $this->available_shares - $allocated;
}

public function isAvailableAttribute(): bool
{
    return $this->remaining_shares > 0 && $this->status === 'active';
}
```

---

### Phase 2: Backend API - User Investment Controller (Day 2)

#### Step 2.1: Create Investment Controller

**File**: `/backend/app/Http/Controllers/Api/User/InvestmentController.php`

```php
<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Investment;
use App\Models\Deal;
use App\Models\Subscription;
use App\Services\InvestmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvestmentController extends Controller
{
    protected $investmentService;

    public function __construct(InvestmentService $investmentService)
    {
        $this->investmentService = $investmentService;
    }

    /**
     * Get user's investments
     * GET /api/v1/user/investments
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $investments = Investment::where('user_id', $user->id)
            ->with(['deal', 'company', 'subscription.plan'])
            ->orderBy('invested_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'investments' => $investments->items(),
            'pagination' => [
                'total' => $investments->total(),
                'per_page' => $investments->perPage(),
                'current_page' => $investments->currentPage(),
                'last_page' => $investments->lastPage(),
            ],
        ]);
    }

    /**
     * Get user's portfolio summary
     * GET /api/v1/user/portfolio
     */
    public function portfolio(Request $request)
    {
        $user = $request->user();

        $stats = [
            'total_invested' => Investment::where('user_id', $user->id)
                ->whereIn('status', ['active', 'pending'])
                ->sum('total_amount'),
            'active_investments_count' => Investment::where('user_id', $user->id)
                ->where('status', 'active')
                ->count(),
            'total_current_value' => 0, // Calculate based on current share prices
            'unrealized_profit_loss' => 0,
            'exited_investments_count' => Investment::where('user_id', $user->id)
                ->where('status', 'exited')
                ->count(),
            'realized_profit_loss' => Investment::where('user_id', $user->id)
                ->where('status', 'exited')
                ->sum('profit_loss'),
        ];

        // Calculate current value for active investments
        $activeInvestments = Investment::where('user_id', $user->id)
            ->where('status', 'active')
            ->with('deal')
            ->get();

        foreach ($activeInvestments as $investment) {
            $stats['total_current_value'] += $investment->current_value;
        }

        $stats['unrealized_profit_loss'] = $stats['total_current_value'] - $stats['total_invested'];

        return response()->json([
            'success' => true,
            'portfolio' => $stats,
        ]);
    }

    /**
     * Create a new investment
     * POST /api/v1/user/investments
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'deal_id' => 'required|exists:deals,id',
            'subscription_id' => 'required|exists:subscriptions,id',
            'shares_allocated' => 'required|integer|min:1',
        ]);

        $user = $request->user();

        // Verify subscription ownership
        $subscription = Subscription::where('id', $validated['subscription_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        if (!in_array($subscription->status, ['active', 'paused'])) {
            return response()->json([
                'message' => 'Subscription must be active to invest.',
            ], 400);
        }

        // Verify deal availability
        $deal = Deal::findOrFail($validated['deal_id']);

        if (!$deal->is_available) {
            return response()->json([
                'message' => 'This deal is no longer available.',
            ], 400);
        }

        if ($deal->remaining_shares < $validated['shares_allocated']) {
            return response()->json([
                'message' => "Only {$deal->remaining_shares} shares available.",
            ], 400);
        }

        // Calculate investment amount
        $totalAmount = $validated['shares_allocated'] * $deal->share_price;

        // Check subscription balance
        if ($subscription->available_balance < $totalAmount) {
            return response()->json([
                'message' => 'Insufficient subscription balance. Available: ₹' . number_format($subscription->available_balance, 2),
            ], 400);
        }

        try {
            DB::beginTransaction();

            $investment = Investment::create([
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'deal_id' => $deal->id,
                'company_id' => $deal->product->company_id ?? null,
                'investment_code' => 'INV-' . strtoupper(uniqid()),
                'shares_allocated' => $validated['shares_allocated'],
                'price_per_share' => $deal->share_price,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'invested_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Investment created successfully.',
                'investment' => $investment->load(['deal', 'company']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create investment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel a pending investment
     * DELETE /api/v1/user/investments/{id}
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $investment = Investment::where('id', $id)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $investment->update([
            'status' => 'cancelled',
            'notes' => 'Cancelled by user',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Investment cancelled successfully.',
        ]);
    }
}
```

#### Step 2.2: Create User Deals Controller

**File**: `/backend/app/Http/Controllers/Api/User/DealController.php`

```php
<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use Illuminate\Http\Request;

class DealController extends Controller
{
    /**
     * Get available deals for user
     * GET /api/v1/user/deals
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Filter deals based on user's subscription plan (if needed)
        $deals = Deal::live()
            ->with(['product'])
            ->orderBy('sort_order', 'asc')
            ->orderBy('deal_closes_at', 'asc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'deals' => $deals->items(),
            'pagination' => [
                'total' => $deals->total(),
                'per_page' => $deals->perPage(),
                'current_page' => $deals->currentPage(),
                'last_page' => $deals->lastPage(),
            ],
        ]);
    }

    /**
     * Get deal details
     * GET /api/v1/user/deals/{id}
     */
    public function show(Request $request, $id)
    {
        $deal = Deal::with(['product'])
            ->where('status', 'active')
            ->findOrFail($id);

        // Check if user has already invested
        $userInvestment = $deal->investments()
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['active', 'pending'])
            ->first();

        return response()->json([
            'success' => true,
            'deal' => $deal,
            'user_investment' => $userInvestment,
            'is_available' => $deal->is_available,
            'remaining_shares' => $deal->remaining_shares,
        ]);
    }
}
```

#### Step 2.3: Add API Routes

**Update**: `/backend/routes/api.php`

```php
// User Investment & Deals Routes
Route::middleware(['auth:sanctum'])->prefix('v1/user')->group(function () {
    // ... existing routes

    // Deals
    Route::prefix('deals')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\User\DealController::class, 'index']);
        Route::get('/{id}', [App\Http\Controllers\Api\User\DealController::class, 'show']);
    });

    // Investments
    Route::prefix('investments')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\User\InvestmentController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\User\InvestmentController::class, 'store']);
        Route::delete('/{id}', [App\Http\Controllers\Api\User\InvestmentController::class, 'destroy']);
    });

    // Portfolio
    Route::get('/portfolio', [App\Http\Controllers\Api\User\InvestmentController::class, 'portfolio']);
});
```

---

### Phase 3: Frontend Pages & Components (Day 3-4)

#### Step 3.1: User Deals Page

**File**: `/frontend/app/(user)/deals/page.tsx`

```tsx
'use client';

import { useQuery } from "@tanstack/react-query";
import api from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Building2, TrendingUp, Calendar, DollarSign, ArrowRight } from "lucide-react";
import Link from "next/link";
import { useState } from "react";
import { InvestmentModal } from "@/components/features/InvestmentModal";

export default function DealsPage() {
  const [selectedDeal, setSelectedDeal] = useState<any>(null);
  const [showInvestModal, setShowInvestModal] = useState(false);

  const { data: response, isLoading } = useQuery({
    queryKey: ['userDeals'],
    queryFn: async () => (await api.get('/user/deals')).data,
  });

  const deals = response?.deals || [];

  const handleInvest = (deal: any) => {
    setSelectedDeal(deal);
    setShowInvestModal(true);
  };

  if (isLoading) {
    return <div className="container py-20">Loading deals...</div>;
  }

  return (
    <div className="container py-8">
      <div className="mb-8">
        <h1 className="text-4xl font-bold mb-2">Available Deals</h1>
        <p className="text-muted-foreground">
          Browse and invest in pre-IPO companies
        </p>
      </div>

      <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        {deals.map((deal: any) => (
          <Card key={deal.id} className="hover:shadow-lg transition-shadow">
            <CardHeader>
              <div className="flex items-start justify-between mb-2">
                <div>
                  <CardTitle className="text-xl">{deal.title}</CardTitle>
                  <p className="text-sm text-muted-foreground mt-1">{deal.company_name}</p>
                </div>
                {deal.is_featured && (
                  <Badge variant="secondary" className="bg-purple-100 text-purple-700">
                    Featured
                  </Badge>
                )}
              </div>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <div className="flex justify-between text-sm">
                  <span className="text-muted-foreground">Share Price</span>
                  <span className="font-semibold">₹{deal.share_price.toLocaleString('en-IN')}</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-muted-foreground">Min Investment</span>
                  <span className="font-semibold">₹{deal.min_investment.toLocaleString('en-IN')}</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-muted-foreground">Available Shares</span>
                  <span className="font-semibold">{deal.available_shares.toLocaleString('en-IN')}</span>
                </div>
                {deal.deal_closes_at && (
                  <div className="flex justify-between text-sm">
                    <span className="text-muted-foreground">Closes At</span>
                    <span className="font-semibold">
                      {new Date(deal.deal_closes_at).toLocaleDateString('en-IN')}
                    </span>
                  </div>
                )}
              </div>

              <Button
                onClick={() => handleInvest(deal)}
                className="w-full"
              >
                Invest Now
                <ArrowRight className="ml-2 w-4 h-4" />
              </Button>

              <Link href={`/deals/${deal.slug}`}>
                <Button variant="outline" className="w-full">
                  View Details
                </Button>
              </Link>
            </CardContent>
          </Card>
        ))}
      </div>

      {showInvestModal && selectedDeal && (
        <InvestmentModal
          isOpen={showInvestModal}
          onClose={() => setShowInvestModal(false)}
          deal={selectedDeal}
        />
      )}
    </div>
  );
}
```

#### Step 3.2: Investment Modal Component

**File**: `/frontend/components/features/InvestmentModal.tsx`

```tsx
'use client';

import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { toast } from "sonner";
import api from "@/lib/api";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useState, useEffect } from "react";
import { TrendingUp, AlertTriangle } from "lucide-react";

interface InvestmentModalProps {
  isOpen: boolean;
  onClose: () => void;
  deal: any;
}

export function InvestmentModal({ isOpen, onClose, deal }: InvestmentModalProps) {
  const queryClient = useQueryClient();
  const [shares, setShares] = useState(1);
  const [selectedSubscription, setSelectedSubscription] = useState<string>("");

  // Fetch user's active subscriptions
  const { data: subscriptions } = useQuery({
    queryKey: ['userSubscriptions'],
    queryFn: async () => {
      const { data } = await api.get('/user/subscriptions');
      return data.filter((sub: any) => ['active', 'paused'].includes(sub.status));
    },
    enabled: isOpen,
  });

  const totalAmount = shares * deal.share_price;
  const minShares = Math.ceil(deal.min_investment / deal.share_price);

  const createInvestmentMutation = useMutation({
    mutationFn: (data: any) => api.post('/user/investments', data),
    onSuccess: (data) => {
      toast.success("Investment Created!", {
        description: `You've invested ₹${totalAmount.toLocaleString('en-IN')} in ${deal.company_name}`,
      });
      queryClient.invalidateQueries({ queryKey: ['userDeals'] });
      queryClient.invalidateQueries({ queryKey: ['userInvestments'] });
      queryClient.invalidateQueries({ queryKey: ['portfolio'] });
      onClose();
    },
    onError: (e: any) => {
      toast.error("Investment Failed", {
        description: e.response?.data?.message || "Please try again",
      });
    },
  });

  const handleConfirm = () => {
    if (!selectedSubscription) {
      toast.error("Please select a subscription");
      return;
    }

    if (shares < minShares) {
      toast.error(`Minimum ${minShares} shares required`);
      return;
    }

    createInvestmentMutation.mutate({
      deal_id: deal.id,
      subscription_id: selectedSubscription,
      shares_allocated: shares,
    });
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-lg">
        <DialogHeader>
          <DialogTitle className="text-2xl">Invest in {deal.company_name}</DialogTitle>
          <DialogDescription>{deal.title}</DialogDescription>
        </DialogHeader>

        <div className="space-y-6 py-4">
          {/* Subscription Selection */}
          <div className="space-y-2">
            <Label>Select Subscription Plan</Label>
            <Select value={selectedSubscription} onValueChange={setSelectedSubscription}>
              <SelectTrigger>
                <SelectValue placeholder="Choose a subscription" />
              </SelectTrigger>
              <SelectContent>
                {subscriptions?.map((sub: any) => (
                  <SelectItem key={sub.id} value={sub.id.toString()}>
                    {sub.plan.name} - Available: ₹{sub.available_balance.toLocaleString('en-IN')}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          {/* Shares Input */}
          <div className="space-y-2">
            <Label>Number of Shares</Label>
            <Input
              type="number"
              min={minShares}
              max={deal.available_shares}
              value={shares}
              onChange={(e) => setShares(parseInt(e.target.value) || 0)}
            />
            <p className="text-xs text-muted-foreground">
              Minimum: {minShares} shares | Available: {deal.available_shares.toLocaleString('en-IN')} shares
            </p>
          </div>

          {/* Investment Summary */}
          <div className="bg-muted p-4 rounded-lg space-y-2">
            <div className="flex justify-between">
              <span>Share Price</span>
              <span className="font-semibold">₹{deal.share_price.toLocaleString('en-IN')}</span>
            </div>
            <div className="flex justify-between">
              <span>Shares</span>
              <span className="font-semibold">{shares}</span>
            </div>
            <div className="h-px bg-border my-2" />
            <div className="flex justify-between text-lg">
              <span className="font-bold">Total Investment</span>
              <span className="font-bold text-primary">₹{totalAmount.toLocaleString('en-IN')}</span>
            </div>
          </div>

          {/* Warning */}
          {shares < minShares && (
            <div className="flex items-start gap-2 bg-destructive/10 p-3 rounded text-sm text-destructive">
              <AlertTriangle className="w-4 h-4 mt-0.5" />
              <p>Minimum investment of ₹{deal.min_investment.toLocaleString('en-IN')} required</p>
            </div>
          )}

          <Button
            onClick={handleConfirm}
            disabled={!selectedSubscription || shares < minShares || createInvestmentMutation.isPending}
            className="w-full"
            size="lg"
          >
            {createInvestmentMutation.isPending ? "Confirming..." : `Confirm Investment`}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}
```

#### Step 3.3: User Investments Page

**File**: `/frontend/app/(user)/investments/page.tsx`

```tsx
'use client';

import { useQuery } from "@tanstack/react-query";
import api from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { TrendingUp, TrendingDown, Building2, Calendar } from "lucide-react";

export default function InvestmentsPage() {
  const { data: portfolioResponse } = useQuery({
    queryKey: ['portfolio'],
    queryFn: async () => (await api.get('/user/portfolio')).data,
  });

  const { data: investmentsResponse, isLoading } = useQuery({
    queryKey: ['userInvestments'],
    queryFn: async () => (await api.get('/user/investments')).data,
  });

  const portfolio = portfolioResponse?.portfolio || {};
  const investments = investmentsResponse?.investments || [];

  if (isLoading) {
    return <div className="container py-20">Loading portfolio...</div>;
  }

  const profitLossPercentage = portfolio.total_invested > 0
    ? ((portfolio.unrealized_profit_loss / portfolio.total_invested) * 100).toFixed(2)
    : '0.00';

  return (
    <div className="container py-8">
      <h1 className="text-4xl font-bold mb-8">My Investments</h1>

      {/* Portfolio Summary */}
      <div className="grid md:grid-cols-4 gap-6 mb-8">
        <Card>
          <CardHeader className="pb-3">
            <p className="text-sm text-muted-foreground">Total Invested</p>
          </CardHeader>
          <CardContent>
            <p className="text-3xl font-bold">₹{portfolio.total_invested?.toLocaleString('en-IN') || '0'}</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-3">
            <p className="text-sm text-muted-foreground">Current Value</p>
          </CardHeader>
          <CardContent>
            <p className="text-3xl font-bold">₹{portfolio.total_current_value?.toLocaleString('en-IN') || '0'}</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-3">
            <p className="text-sm text-muted-foreground">Profit/Loss</p>
          </CardHeader>
          <CardContent>
            <p className={`text-3xl font-bold ${portfolio.unrealized_profit_loss >= 0 ? 'text-green-600' : 'text-red-600'}`}>
              ₹{portfolio.unrealized_profit_loss?.toLocaleString('en-IN') || '0'}
            </p>
            <p className="text-sm text-muted-foreground mt-1">
              {profitLossPercentage}%
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-3">
            <p className="text-sm text-muted-foreground">Active Investments</p>
          </CardHeader>
          <CardContent>
            <p className="text-3xl font-bold">{portfolio.active_investments_count || 0}</p>
          </CardContent>
        </Card>
      </div>

      {/* Investments List */}
      <div className="space-y-4">
        <h2 className="text-2xl font-bold">Your Holdings</h2>

        {investments.length === 0 ? (
          <Card>
            <CardContent className="py-16 text-center">
              <Building2 className="w-16 h-16 text-muted-foreground mx-auto mb-4" />
              <h3 className="text-2xl font-semibold mb-2">No Investments Yet</h3>
              <p className="text-muted-foreground mb-6">
                Start investing in pre-IPO companies to build your portfolio
              </p>
            </CardContent>
          </Card>
        ) : (
          investments.map((investment: any) => {
            const profitLoss = investment.current_value - investment.total_amount;
            const profitLossPercent = ((profitLoss / investment.total_amount) * 100).toFixed(2);

            return (
              <Card key={investment.id}>
                <CardContent className="py-6">
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <div className="flex items-center gap-3 mb-2">
                        <h3 className="text-xl font-bold">{investment.deal.company_name}</h3>
                        <Badge variant={investment.status === 'active' ? 'default' : 'secondary'}>
                          {investment.status}
                        </Badge>
                      </div>
                      <p className="text-sm text-muted-foreground mb-4">{investment.deal.title}</p>

                      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                          <p className="text-xs text-muted-foreground">Shares</p>
                          <p className="font-semibold">{investment.shares_allocated}</p>
                        </div>
                        <div>
                          <p className="text-xs text-muted-foreground">Invested</p>
                          <p className="font-semibold">₹{investment.total_amount.toLocaleString('en-IN')}</p>
                        </div>
                        <div>
                          <p className="text-xs text-muted-foreground">Current Value</p>
                          <p className="font-semibold">₹{investment.current_value.toLocaleString('en-IN')}</p>
                        </div>
                        <div>
                          <p className="text-xs text-muted-foreground">P/L</p>
                          <p className={`font-semibold ${profitLoss >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                            {profitLoss >= 0 ? '+' : ''}₹{profitLoss.toLocaleString('en-IN')} ({profitLossPercent}%)
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                </CardContent>
              </Card>
            );
          })
        )}
      </div>
    </div>
  );
}
```

#### Step 3.4: Update Dashboard to Show Investment Widget

**Update**: `/frontend/app/(user)/dashboard/page.tsx`

Add a quick portfolio summary widget that links to `/user/investments`:

```tsx
// Add this component to the dashboard
<Card>
  <CardHeader>
    <CardTitle>Your Portfolio</CardTitle>
  </CardHeader>
  <CardContent>
    <div className="space-y-3">
      <div className="flex justify-between">
        <span className="text-muted-foreground">Total Invested</span>
        <span className="font-bold">₹{portfolio?.total_invested?.toLocaleString('en-IN') || '0'}</span>
      </div>
      <div className="flex justify-between">
        <span className="text-muted-foreground">Active Investments</span>
        <span className="font-bold">{portfolio?.active_investments_count || 0}</span>
      </div>
      <Link href="/user/investments">
        <Button className="w-full mt-4">View Full Portfolio</Button>
      </Link>
    </div>
  </CardContent>
</Card>
```

---

### Phase 4: Post-Payment Flow Integration (Day 4)

#### Step 4.1: Update Subscribe/Payment Success Flow

**Goal**: After successful subscription payment, redirect user to deals page instead of dashboard.

**Update**: `/frontend/app/(user)/subscribe/page.tsx`

Change redirect from:
```tsx
router.push('/dashboard');
```

To:
```tsx
router.push('/deals?welcome=true');
```

**Update**: Payment verification success handler

After payment verification, show a modal asking user to browse deals:

```tsx
// In PaymentController verify method response
if (paymentVerified) {
  return {
    message: 'Payment verified successfully.',
    next_step: 'browse_deals',
    redirect_url: '/user/deals?welcome=true'
  };
}
```

#### Step 4.2: Welcome Modal on Deals Page

**Update**: `/frontend/app/(user)/deals/page.tsx`

Add a welcome modal for first-time visitors:

```tsx
const searchParams = useSearchParams();
const isWelcome = searchParams.get('welcome') === 'true';

useEffect(() => {
  if (isWelcome) {
    toast.success("Welcome to Investments!", {
      description: "Your subscription is active. Start investing in pre-IPO companies now!",
      duration: 5000,
    });
  }
}, [isWelcome]);
```

---

### Phase 5: Express Interest Integration (Optional Enhancement)

**File**: `/backend/app/Http/Controllers/Api/User/InvestorInterestController.php`

Create a new user-facing controller for expressing interest:

```php
<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\InvestorInterest;
use App\Models\Company;
use Illuminate\Http\Request;

class UserInvestorInterestController extends Controller
{
    /**
     * Express interest in a company
     * POST /api/v1/user/express-interest
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'interest_level' => 'required|in:low,medium,high',
            'investment_range_min' => 'required|numeric|min:0',
            'investment_range_max' => 'required|numeric|min:0|gte:investment_range_min',
            'message' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        // Check if already expressed interest
        $existing = InvestorInterest::where('company_id', $validated['company_id'])
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'You have already expressed interest in this company.',
            ], 400);
        }

        $interest = InvestorInterest::create([
            'company_id' => $validated['company_id'],
            'user_id' => $user->id,
            'investor_email' => $user->email,
            'investor_name' => $user->profile->first_name . ' ' . $user->profile->last_name,
            'investor_phone' => $user->mobile,
            'interest_level' => $validated['interest_level'],
            'investment_range_min' => $validated['investment_range_min'],
            'investment_range_max' => $validated['investment_range_max'],
            'message' => $validated['message'],
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Interest registered successfully. The company will contact you soon.',
            'interest' => $interest,
        ], 201);
    }
}
```

**Update "Express Interest" button** in `/frontend/app/(public)/companies/[slug]/page.tsx`:

```tsx
const [showInterestModal, setShowInterestModal] = useState(false);

// Button click handler
<Button size="lg" onClick={() => setShowInterestModal(true)}>
  Express Interest
</Button>

// Add ExpressInterestModal component
{showInterestModal && (
  <ExpressInterestModal
    isOpen={showInterestModal}
    onClose={() => setShowInterestModal(false)}
    company={company}
  />
)}
```

---

## TESTING CHECKLIST

### Backend Tests

- [ ] Investment model relationships work correctly
- [ ] Investment creation validates subscription balance
- [ ] Deal remaining shares calculation is accurate
- [ ] Portfolio calculations are correct
- [ ] Cannot invest more than subscription balance
- [ ] Cannot allocate more shares than available
- [ ] Investment cancellation works
- [ ] API authorization is enforced (users can't access others' investments)

### Frontend Tests

- [ ] Deals page loads and displays deals correctly
- [ ] Investment modal shows correct calculations
- [ ] Subscription selection shows available balance
- [ ] Share input validates min/max correctly
- [ ] Portfolio page shows accurate stats
- [ ] Investment list displays all holdings
- [ ] Profit/loss calculations are accurate
- [ ] Navigation flows from payment → deals → investments works

### Integration Tests

- [ ] Full flow: Subscribe → Pay → Browse Deals → Invest → View Portfolio
- [ ] Multiple investments from single subscription
- [ ] Multiple subscriptions with separate investments
- [ ] Deal sold out scenario (no more shares)
- [ ] Insufficient balance scenario

---

## DEPLOYMENT CHECKLIST

### Pre-Deployment

- [ ] Run migration: `php artisan migrate`
- [ ] Seed sample deals (if needed)
- [ ] Test in staging environment
- [ ] Review API permissions and authorization
- [ ] Verify all routes are registered

### Post-Deployment

- [ ] Monitor error logs for 24 hours
- [ ] Track first investments created
- [ ] Gather user feedback on flow
- [ ] Check performance of portfolio calculations
- [ ] Verify email notifications (if any)

---

## SUCCESS METRICS

After deployment, track:

1. **Adoption Rate**: % of subscribers who create at least one investment
2. **Time to First Investment**: Average time from payment to first investment
3. **Portfolio Engagement**: Visits to `/user/investments` page
4. **Deal Conversion**: % of deal views that result in investments
5. **Average Investment Size**: Average amount per investment

**Target KPIs** (30 days post-launch):
- 60%+ subscribers make at least one investment
- <24 hours average time to first investment
- 3+ average deals viewed per user
- 40%+ deal view to investment conversion

---

## FUTURE ENHANCEMENTS (Post-MVP)

1. **Secondary Market**: Allow users to sell holdings to other users
2. **Dividend Tracking**: Track and display dividend payments
3. **Exit Notifications**: Alert users when companies go IPO
4. **Watchlist**: Save deals to invest later
5. **Auto-Invest**: Automatically allocate subscription funds to preferred companies
6. **Investment Recommendations**: AI-powered deal suggestions based on user profile
7. **Fractional Shares**: Allow investment amounts smaller than 1 share

---

## ESTIMATED TIMELINE

| Phase | Task | Duration | Dependencies |
|-------|------|----------|--------------|
| 1 | Backend Models & Migrations | 4 hours | None |
| 2 | Backend API Controllers | 6 hours | Phase 1 |
| 3 | Frontend Pages (Deals, Investments) | 8 hours | Phase 2 |
| 3.5 | Investment Modal Component | 4 hours | Phase 2 |
| 4 | Post-Payment Flow Integration | 3 hours | Phase 3 |
| 5 | Express Interest Integration | 3 hours | Phase 2 |
| 6 | Testing | 4 hours | All phases |
| **Total** | **End-to-End Implementation** | **32 hours (4 days)** | |

---

## SUPPORT & MAINTENANCE

**Documentation to Create:**
1. API documentation for investment endpoints
2. User guide: "How to invest after subscription"
3. Admin guide: "Managing deals and investments"
4. Troubleshooting guide for common issues

**Ongoing Maintenance:**
- Monitor investment volume and performance
- Regular portfolio calculation accuracy checks
- Deal availability updates (sold out, new deals)
- User feedback collection and iteration

---

**Plan Created**: 2025-12-23
**Ready for Implementation**: ✅ Yes
**Blockers**: None - All prerequisites in place
