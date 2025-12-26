<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth handled by middleware/routes
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'code' => 'required|string|max:50|unique:campaigns,code|alpha_dash',
            'description' => 'required|string',
            'long_description' => 'nullable|string',
            'discount_type' => 'required|in:percentage,fixed_amount',
            'discount_percent' => 'required_if:discount_type,percentage|nullable|numeric|min:0|max:100',
            'discount_amount' => 'required_if:discount_type,fixed_amount|nullable|numeric|min:0',
            'min_investment' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'user_usage_limit' => 'nullable|integer|min:1',
            'start_at' => 'nullable|date|after_or_equal:today',
            'end_at' => 'nullable|date|after:start_at',
            'image_url' => 'nullable|url|max:500',
            'hero_image' => 'nullable|url|max:500',
            'video_url' => 'nullable|url|max:500',
            'features' => 'nullable|array',
            'features.*' => 'string',
            'terms' => 'nullable|array',
            'terms.*' => 'string',
            'is_featured' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'code.alpha_dash' => 'Campaign code must contain only letters, numbers, dashes and underscores',
            'code.unique' => 'This campaign code is already in use',
            'discount_percent.required_if' => 'Discount percentage is required when discount type is percentage',
            'discount_amount.required_if' => 'Discount amount is required when discount type is fixed amount',
            'end_at.after' => 'End date must be after start date',
        ];
    }
}
