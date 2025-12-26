<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth handled by middleware/routes
    }

    public function rules(): array
    {
        $campaignId = $this->route('campaign')->id;

        return [
            'title' => 'sometimes|required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('campaigns', 'code')->ignore($campaignId),
            ],
            'description' => 'sometimes|required|string',
            'long_description' => 'nullable|string',
            'discount_type' => 'sometimes|required|in:percentage,fixed_amount',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'min_investment' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'user_usage_limit' => 'nullable|integer|min:1',
            'start_at' => 'nullable|date',
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
            'end_at.after' => 'End date must be after start date',
        ];
    }
}
