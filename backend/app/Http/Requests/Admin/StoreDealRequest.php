<?php

namespace App\Http\Requests\Admin;

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
}
