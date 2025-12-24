<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // [FIX] Ignore current plan ID for unique name check
            'name' => [
                'sometimes', 
                'required', 
                'string', 
                'max:255',
                Rule::unique('plans', 'name')->ignore($this->route('plan'))
            ],
            'monthly_amount' => 'sometimes|required|numeric|min:0',
            'duration_months' => 'sometimes|required|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean', // Removed 'required' to allow partial updates
            'is_featured' => 'sometimes|boolean',
            'available_from' => 'nullable|date',
            'available_until' => 'nullable|date',
            'allow_pause' => 'nullable|boolean',
            'max_pause_count' => 'nullable|integer|min:0',
            'max_pause_duration_months' => 'nullable|integer|min:1',
            'max_subscriptions_per_user' => 'nullable|integer|min:1',
            'min_investment' => 'nullable|numeric|min:0',
            'max_investment' => 'nullable|numeric|min:0',
            'display_order' => 'nullable|integer',
            'billing_cycle' => 'nullable|in:weekly,bi-weekly,monthly,quarterly,yearly',
            'trial_period_days' => 'nullable|integer|min:0',
            'metadata' => 'nullable|json',
            'features' => 'nullable|array',
            'features.*' => 'required|string',
            'configs' => 'nullable|array',
        ];
    }

    /**
     * Prepare the data for validation.
     * [FIX] Ensures string "true"/"false" are converted to booleans
     */
    protected function prepareForValidation()
    {
        $merge = [];

        if ($this->has('is_active')) {
            $merge['is_active'] = $this->boolean('is_active');
        }

        if ($this->has('is_featured')) {
            $merge['is_featured'] = $this->boolean('is_featured');
        }

        if ($this->has('allow_pause')) {
            $merge['allow_pause'] = $this->boolean('allow_pause');
        }

        if (!empty($merge)) {
            $this->merge($merge);
        }
    }
}