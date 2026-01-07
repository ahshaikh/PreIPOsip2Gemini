<?php

namespace App\Http\Requests\Admin;

use App\Models\{Product, Company, BulkPurchase};
use Illuminate\Foundation\Http\FormRequest;

/**
 * Deal Store Request Validation.
 *
 * Centralizes validation logic for creating new deals.
 */
class StoreDealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    public function rules(): array
    {
        return [
            // Required core fields
            'company_id' => 'required|exists:companies,id',
            'product_id' => 'required|exists:products,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',

            // Pricing & Investment
            'share_price' => 'required|numeric|min:0.01',
            'min_investment' => 'nullable|numeric|min:0',
            'max_investment' => 'nullable|numeric|min:0|gte:min_investment',

            // Valuation
            'valuation' => 'nullable|numeric|min:0',
            'valuation_currency' => 'nullable|string|in:INR,USD',

            // Deal Type & Status
            'deal_type' => 'required|in:active,upcoming,closed',
            'status' => 'nullable|in:active,inactive,draft',
            'sector' => 'nullable|string|max:100',

            // Dates
            'deal_opens_at' => 'nullable|date',
            'deal_closes_at' => 'nullable|date|after:deal_opens_at',

            // Additional Info
            'highlights' => 'nullable|array',
            'documents' => 'nullable|array',
            'video_url' => 'nullable|url',
            'is_featured' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'Company is required for creating a deal.',
            'product_id.required' => 'Product is required. Create BulkPurchase inventory first.',
            'share_price.required' => 'Share price is required.',
            'share_price.min' => 'Share price must be greater than zero.',
            'max_investment.gte' => 'Maximum investment cannot be less than minimum investment.',
            'deal_closes_at.after' => 'Deal close date must be after the open date.',
        ];
    }

    /**
     * Prepare data for validation.
     */
    protected function prepareForValidation()
    {
        // Set defaults
        $this->merge([
            'status' => $this->status ?? 'active',
            'is_featured' => $this->is_featured ?? false,
            'sort_order' => $this->sort_order ?? 0,
        ]);
    }

    /**
     * FIX 7 (P1): Cross-Entity Validation
     *
     * Validates that:
     * 1. Product belongs to the specified company (via BulkPurchase provenance)
     * 2. max_investment doesn't exceed available inventory
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Only validate if both fields are present
            if (!$this->product_id || !$this->company_id) {
                return;
            }

            $product = Product::find($this->product_id);
            $company = Company::find($this->company_id);

            if (!$product || !$company) {
                return; // Will fail on exists: rule
            }

            // FIX 7a: Validate product belongs to company via BulkPurchase
            $hasInventoryFromCompany = BulkPurchase::where('product_id', $product->id)
                ->where('company_id', $company->id)
                ->exists();

            if (!$hasInventoryFromCompany) {
                $validator->errors()->add(
                    'product_id',
                    "Selected product does not have inventory from this company. " .
                    "Company '{$company->name}' has not sold shares of product '{$product->name}' to the platform."
                );
            }

            // FIX 7b: Validate max_investment doesn't exceed available inventory
            if ($this->max_investment) {
                $availableValue = BulkPurchase::where('product_id', $product->id)
                    ->where('value_remaining', '>', 0)
                    ->sum('value_remaining');

                if ($this->max_investment > $availableValue) {
                    $validator->errors()->add(
                        'max_investment',
                        "Maximum investment (₹{$this->max_investment}) exceeds available inventory (₹{$availableValue}). " .
                        "Reduce max_investment or add more inventory via BulkPurchase."
                    );
                }
            }
        });
    }
}
