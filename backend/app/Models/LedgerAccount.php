<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * DOUBLE-ENTRY LEDGER: Chart of Accounts
 * 
 * Represents an account in the chart of accounts. Each account has a type
 * that determines its normal balance and how it appears in financial statements.
 * 
 * ACCOUNT TYPES:
 * - ASSET: Debit increases, Credit decreases (Bank, Inventory)
 * - LIABILITY: Credit increases, Debit decreases (User Wallets, Bonuses)
 * - EQUITY: Credit increases, Debit decreases (Owner Capital)
 * - INCOME: Credit increases, Debit decreases (Revenue)
 * - EXPENSE: Debit increases, Credit decreases (Costs)
 * 
 * IMMUTABILITY:
 * - System accounts (is_system=true) cannot be deleted
 * - Accounts with ledger lines cannot be deleted
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $type
 * @property bool $is_system
 * @property string|null $description
 * @property string $normal_balance
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @mixin IdeHelperLedgerAccount
 */
class LedgerAccount extends Model
{
    use HasFactory;

    /**
     * Account type constants
     */
    public const TYPE_ASSET = 'ASSET';
    public const TYPE_LIABILITY = 'LIABILITY';
    public const TYPE_EQUITY = 'EQUITY';
    public const TYPE_INCOME = 'INCOME';
    public const TYPE_EXPENSE = 'EXPENSE';

    /**
     * Direction constants
     */
    public const DIRECTION_DEBIT = 'DEBIT';
    public const DIRECTION_CREDIT = 'CREDIT';

    /**
     * System account codes (non-deletable)
     */
    public const CODE_BANK = '1000';
    public const CODE_INVENTORY = 'INVENTORY';
    public const CODE_USER_WALLET_LIABILITY = '2000';
    public const CODE_BONUS_LIABILITY = 'BONUS_LIABILITY';
    public const CODE_OWNER_CAPITAL = 'OWNER_CAPITAL';
    public const CODE_SUBSCRIPTION_INCOME = 'SUBSCRIPTION_INCOME';
    public const CODE_PLATFORM_FEES = 'PLATFORM_FEES';
    public const CODE_MARKETING_EXPENSE = 'MARKETING_EXPENSE';
    public const CODE_OPERATING_EXPENSES = 'OPERATING_EXPENSES';
    public const CODE_SHARE_SALE_INCOME = 'SHARE_SALE_INCOME';
    public const CODE_COST_OF_SHARES = 'COST_OF_SHARES';
    public const CODE_TDS_PAYABLE = 'TDS_PAYABLE';
    public const CODE_REFUNDS_PAYABLE = 'REFUNDS_PAYABLE';
    public const CODE_ACCOUNTS_RECEIVABLE = 'ACCOUNTS_RECEIVABLE';

    protected $table = 'ledger_accounts';

    protected $fillable = [
        'code',
        'name',
        'type',
        'is_system',
        'description',
        'normal_balance',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot method for model event handling
     */
    protected static function booted(): void
    {
        // Prevent deletion of system accounts
        static::deleting(function (LedgerAccount $account) {
            if ($account->is_system) {
                throw new \RuntimeException(
                    "Cannot delete system account [{$account->code}]. System accounts are immutable."
                );
            }

            // Prevent deletion if account has any ledger lines
            if ($account->ledgerLines()->exists()) {
                throw new \RuntimeException(
                    "Cannot delete account [{$account->code}] with existing ledger entries."
                );
            }
        });
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get all ledger lines for this account.
     */
    public function ledgerLines(): HasMany
    {
        return $this->hasMany(LedgerLine::class, 'ledger_account_id');
    }

    // =========================================================================
    // BALANCE CALCULATIONS
    // =========================================================================

    /**
     * Calculate the current balance of this account.
     *
     * Balance is ALWAYS computed from ledger lines, never stored.
     * This ensures the ledger is the single source of truth.
     *
     * For ASSET/EXPENSE accounts: Balance = SUM(debits) - SUM(credits)
     * For LIABILITY/EQUITY/INCOME accounts: Balance = SUM(credits) - SUM(debits)
     *
     * @return float Current balance
     */
    public function getBalanceAttribute(): float
    {
        // V-AUDIT-FIX-2026: Column is amount_paise (bigint), not amount (decimal)
        $totals = $this->ledgerLines()
            ->selectRaw('direction, SUM(amount_paise) as total')
            ->groupBy('direction')
            ->pluck('total', 'direction')
            ->toArray();

        // Convert from paise to rupees for balance
        $debits = (float) (($totals[self::DIRECTION_DEBIT] ?? 0) / 100);
        $credits = (float) (($totals[self::DIRECTION_CREDIT] ?? 0) / 100);

        // Normal balance determines calculation direction
        if ($this->isDebitNormal()) {
            return $debits - $credits;
        }

        return $credits - $debits;
    }

    /**
     * Get total debits for this account.
     */
    public function getTotalDebitsAttribute(): float
    {
        return (float) $this->ledgerLines()
            ->where('direction', self::DIRECTION_DEBIT)
            ->sum('amount');
    }

    /**
     * Get total credits for this account.
     */
    public function getTotalCreditsAttribute(): float
    {
        return (float) $this->ledgerLines()
            ->where('direction', self::DIRECTION_CREDIT)
            ->sum('amount');
    }

    // =========================================================================
    // ACCOUNT TYPE HELPERS
    // =========================================================================

    /**
     * Check if this account has a normal debit balance.
     */
    public function isDebitNormal(): bool
    {
        return in_array($this->type, [self::TYPE_ASSET, self::TYPE_EXPENSE]);
    }

    /**
     * Check if this account has a normal credit balance.
     */
    public function isCreditNormal(): bool
    {
        return in_array($this->type, [self::TYPE_LIABILITY, self::TYPE_EQUITY, self::TYPE_INCOME]);
    }

    /**
     * Check if this is an asset account.
     */
    public function isAsset(): bool
    {
        return $this->type === self::TYPE_ASSET;
    }

    /**
     * Check if this is a liability account.
     */
    public function isLiability(): bool
    {
        return $this->type === self::TYPE_LIABILITY;
    }

    /**
     * Check if this is an equity account.
     */
    public function isEquity(): bool
    {
        return $this->type === self::TYPE_EQUITY;
    }

    /**
     * Check if this is an income account.
     */
    public function isIncome(): bool
    {
        return $this->type === self::TYPE_INCOME;
    }

    /**
     * Check if this is an expense account.
     */
    public function isExpense(): bool
    {
        return $this->type === self::TYPE_EXPENSE;
    }

    // =========================================================================
    // STATIC HELPERS
    // =========================================================================

    /**
     * Get account by code.
     *
     * @param string $code Account code
     * @return LedgerAccount
     * @throws \RuntimeException if account not found
     */
    public static function byCode(string $code): LedgerAccount
    {
        $account = static::where('code', $code)->first();

        if (!$account) {
            throw new \RuntimeException("Ledger account not found: {$code}");
        }

        return $account;
    }

    /**
     * Get all accounts of a specific type.
     */
    public static function ofType(string $type): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('type', $type)->get();
    }

    /**
     * Get all system accounts.
     */
    public static function systemAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_system', true)->get();
    }
}
