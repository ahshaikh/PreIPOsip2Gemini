<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Company Share Listing Store Request Validation.
 *
 * Used when companies submit share offerings for admin review.
 */
class StoreShareListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    public function rules(): array
    {
        return [
            // Listing Info
            'listing_title' => 'required|string|max:255',
            'description' => 'nullable|string',

            // Share Details
            'total_shares_offered' => 'required|numeric|min:1',
            'asking_price_per_share' => 'required|numeric|min:0.01',
            'face_value_per_share' => 'required|numeric|min:0.01',
            'share_class' => 'nullable|in:equity,preference,other',
            'share_certificate_numbers' => 'nullable|string',

            // Valuation Context
            'current_company_valuation' => 'nullable|numeric|min:0',
            'valuation_currency' => 'nullable|in:INR,USD',
            'percentage_of_company' => 'nullable|numeric|min:0|max:100',

            // Terms & Conditions
            'lock_in_period' => 'nullable|string|max:100',
            'rights_attached' => 'nullable|string',
            'minimum_purchase_quantity' => 'nullable|integer|min:1',
            'minimum_purchase_value' => 'nullable|numeric|min:0',

            // Financial Context
            'reason_for_selling' => 'nullable|string',
            'use_of_proceeds' => 'nullable|string',
            'revenue_last_fy' => 'nullable|numeric',
            'profit_loss_last_fy' => 'nullable|numeric',

            // Documents
            'documents' => 'nullable|array',
            'financial_documents' => 'nullable|array',

            // Validity
            'offer_valid_until' => 'nullable|date|after:now',
        ];
    }

    public function messages(): array
    {
        return [
            'listing_title.required' => 'Listing title is required.',
            'total_shares_offered.required' => 'Total shares offered is required.',
            'total_shares_offered.min' => 'Must offer at least 1 share.',
            'asking_price_per_share.required' => 'Asking price per share is required.',
            'asking_price_per_share.min' => 'Asking price must be greater than zero.',
            'face_value_per_share.required' => 'Face value per share is required.',
            'percentage_of_company.max' => 'Cannot offer more than 100% of company.',
            'offer_valid_until.after' => 'Offer validity must be in the future.',
        ];
    }

    /**
     * Additional validation after standard rules.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate asking price >= face value
            if ($this->asking_price_per_share && $this->face_value_per_share) {
                if ($this->asking_price_per_share < $this->face_value_per_share) {
                    $validator->errors()->add(
                        'asking_price_per_share',
                        'Asking price cannot be less than face value.'
                    );
                }
            }
        });
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'valuation_currency' => $this->valuation_currency ?? 'INR',
            'share_class' => $this->share_class ?? 'equity',
        ]);
    }
}
