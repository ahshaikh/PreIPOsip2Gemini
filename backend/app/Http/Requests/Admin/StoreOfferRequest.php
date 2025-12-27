<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Offer Store Request Validation.
 */
class StoreOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Required core fields
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'code' => 'required|string|max:50|unique:offers,code',
            'description' => 'required|string',
            'long_description' => 'nullable|string',

            // Scope & Targeting
            'scope' => 'required|in:global,products,deals,plans',
            'eligible_user_segments' => 'nullable|array',

            // Discount Configuration
            'discount_type' => 'required|in:percentage,fixed_amount',
            'discount_percent' => 'required_if:discount_type,percentage|nullable|numeric|min:0|max:100',
            'discount_amount' => 'required_if:discount_type,fixed_amount|nullable|numeric|min:0',
            'min_investment' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',

            // Usage Limits
            'usage_limit' => 'nullable|integer|min:1',
            'user_usage_limit' => 'nullable|integer|min:1',
            'auto_apply' => 'nullable|boolean',

            // Status & Expiry
            'status' => 'nullable|in:active,inactive,expired',
            'expiry' => 'nullable|date|after:now',

            // Media
            'image_url' => 'nullable|url|max:500',
            'hero_image' => 'nullable|url|max:500',
            'video_url' => 'nullable|url|max:500',

            // Additional Info
            'features' => 'nullable|array',
            'terms' => 'nullable|array',
            'is_featured' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Offer title is required.',
            'code.required' => 'Offer code is required.',
            'code.unique' => 'This offer code is already in use.',
            'description.required' => 'Description is required.',
            'scope.required' => 'Offer scope is required.',
            'discount_type.required' => 'Discount type is required.',
            'discount_percent.required_if' => 'Discount percentage is required for percentage-based offers.',
            'discount_amount.required_if' => 'Discount amount is required for fixed-amount offers.',
            'discount_percent.max' => 'Discount percentage cannot exceed 100%.',
            'expiry.after' => 'Expiry date must be in the future.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'status' => $this->status ?? 'active',
            'auto_apply' => $this->auto_apply ?? false,
            'is_featured' => $this->is_featured ?? false,
            'usage_count' => 0, // Initialize usage count
        ]);
    }
}
