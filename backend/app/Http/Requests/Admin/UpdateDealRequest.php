<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Deal Update Request Validation.
 */
class UpdateDealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Core fields (optional for updates)
            'company_id' => 'sometimes|exists:companies,id',
            'product_id' => 'sometimes|exists:products,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',

            // Pricing & Investment
            'share_price' => 'sometimes|numeric|min:0.01',
            'min_investment' => 'nullable|numeric|min:0',
            'max_investment' => 'nullable|numeric|min:0|gte:min_investment',

            // Valuation
            'valuation' => 'nullable|numeric|min:0',
            'valuation_currency' => 'nullable|string|in:INR,USD',

            // Deal Type & Status
            'deal_type' => 'sometimes|in:active,upcoming,closed',
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
            'share_price.min' => 'Share price must be greater than zero.',
            'max_investment.gte' => 'Maximum investment cannot be less than minimum investment.',
            'deal_closes_at.after' => 'Deal close date must be after the open date.',
        ];
    }
}
