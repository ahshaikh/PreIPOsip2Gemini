<?php

namespace App\Models\Traits;

/**
 * FIX 10 (P2): Has Monetary Fields Trait
 *
 * Provides backward-compatible accessors for monetary fields stored as integer paise
 * Allows gradual migration from decimal to integer storage
 *
 * Usage in Model:
 * use HasMonetaryFields;
 *
 * protected $monetaryFields = ['amount', 'fee', 'total_value'];
 */
trait HasMonetaryFields
{
    /**
     * Boot the trait
     */
    public static function bootHasMonetaryFields()
    {
        // Automatically convert decimal to paise on create/update
        static::saving(function ($model) {
            if (isset($model->monetaryFields)) {
                foreach ($model->monetaryFields as $field) {
                    $paiseField = $field . '_paise';

                    // If decimal field is set but paise field is not, convert
                    if (isset($model->attributes[$field]) && !isset($model->attributes[$paiseField])) {
                        $model->attributes[$paiseField] = bcmul($model->attributes[$field], 100);
                    }

                    // If paise field is set, sync decimal field for backward compatibility
                    if (isset($model->attributes[$paiseField])) {
                        $model->attributes[$field] = bcdiv($model->attributes[$paiseField], 100, 2);
                    }
                }
            }
        });
    }

    /**
     * Get amount in rupees from paise
     *
     * @param string $field Field name without _paise suffix
     * @return float
     */
    public function getAmountInRupees(string $field): float
    {
        $paiseField = $field . '_paise';

        // Prefer paise field if available
        if (isset($this->attributes[$paiseField])) {
            return (float) bcdiv($this->attributes[$paiseField], 100, 2);
        }

        // Fallback to decimal field
        if (isset($this->attributes[$field])) {
            return (float) $this->attributes[$field];
        }

        return 0.0;
    }

    /**
     * Get amount in paise from rupees
     *
     * @param string $field Field name without _paise suffix
     * @return int
     */
    public function getAmountInPaise(string $field): int
    {
        $paiseField = $field . '_paise';

        // Prefer paise field if available
        if (isset($this->attributes[$paiseField])) {
            return (int) $this->attributes[$paiseField];
        }

        // Convert from decimal field
        if (isset($this->attributes[$field])) {
            return (int) bcmul($this->attributes[$field], 100);
        }

        return 0;
    }

    /**
     * Set amount from rupees, stores as paise
     *
     * @param string $field Field name without _paise suffix
     * @param float|int|string $amount Amount in rupees
     * @return void
     */
    public function setAmountFromRupees(string $field, $amount): void
    {
        $paiseField = $field . '_paise';
        $amountPaise = bcmul($amount, 100);

        $this->attributes[$paiseField] = $amountPaise;
        $this->attributes[$field] = $amount; // Keep both for backward compatibility
    }

    /**
     * Set amount from paise
     *
     * @param string $field Field name without _paise suffix
     * @param int $amountPaise Amount in paise
     * @return void
     */
    public function setAmountFromPaise(string $field, int $amountPaise): void
    {
        $paiseField = $field . '_paise';

        $this->attributes[$paiseField] = $amountPaise;
        $this->attributes[$field] = bcdiv($amountPaise, 100, 2); // Keep both for backward compatibility
    }

    /**
     * Format amount for display
     *
     * @param string $field Field name without _paise suffix
     * @param string $currency Currency symbol
     * @return string
     */
    public function formatAmount(string $field, string $currency = '₹'): string
    {
        $amount = $this->getAmountInRupees($field);
        return $currency . number_format($amount, 2);
    }
}
