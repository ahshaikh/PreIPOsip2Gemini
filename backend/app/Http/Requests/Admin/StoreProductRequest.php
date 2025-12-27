<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Product Store Request Validation.
 */
class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Required core fields
            'name' => 'required|string|max:255',
            'company_id' => 'required|exists:companies,id',
            'sector' => 'nullable|string|max:100',
            'description' => 'nullable|array',

            // Pricing
            'face_value_per_unit' => 'required|numeric|min:0.01',
            'current_market_price' => 'nullable|numeric|min:0',
            'min_investment' => 'nullable|numeric|min:0',

            // IPO Info
            'expected_ipo_date' => 'nullable|date',
            'expected_ipo_price_range' => 'nullable|string|max:100',

            // Eligibility
            'eligibility_mode' => 'nullable|in:all_plans,specific_plans',

            // Settings
            'is_featured' => 'nullable|boolean',
            'auto_update_price' => 'nullable|boolean',

            // Compliance
            'sebi_approval_required' => 'nullable|boolean',
            'sebi_approval_date' => 'nullable|date',
            'sebi_approval_number' => 'nullable|string|max:255',
            'compliance_notes' => 'nullable|string',
            'regulatory_warnings' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required.',
            'company_id.required' => 'Company is required for creating a product.',
            'face_value_per_unit.required' => 'Face value per unit is required.',
            'face_value_per_unit.min' => 'Face value must be greater than zero.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'eligibility_mode' => $this->eligibility_mode ?? 'all_plans',
            'is_featured' => $this->is_featured ?? false,
            'auto_update_price' => $this->auto_update_price ?? false,
            'sebi_approval_required' => $this->sebi_approval_required ?? false,
        ]);
    }
}
