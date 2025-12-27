<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth handled by middleware/routes
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:plans,name',
            'monthly_amount' => 'required|numeric|min:0',
            'duration_months' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'available_from' => 'nullable|date',
            'available_until' => 'nullable|date|after:available_from',
            'allow_pause' => 'nullable|boolean',
            'max_pause_count' => 'nullable|integer|min:0',
            'max_pause_duration_months' => 'nullable|integer|min:1',
            'max_subscriptions_per_user' => 'nullable|integer|min:1',
            'min_investment' => 'nullable|numeric|min:0',
            'max_investment' => 'nullable|numeric|min:0|gte:min_investment',
            'display_order' => 'nullable|integer',
            'billing_cycle' => 'nullable|in:weekly,bi-weekly,monthly,quarterly,yearly',
            'trial_period_days' => 'nullable|integer|min:0',
            'metadata' => 'nullable|array',
            'features' => 'nullable|array',
            'configs' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Plan name is required.',
            'name.unique' => 'A plan with this name already exists.',
            'monthly_amount.required' => 'Monthly amount is required.',
            'monthly_amount.min' => 'Monthly amount cannot be negative.',
            'duration_months.required' => 'Duration is required.',
            'duration_months.min' => 'Duration must be at least 1 month.',
            'max_investment.gte' => 'Maximum investment cannot be less than minimum investment.',
            'available_until.after' => 'Available until date must be after available from date.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'is_active' => $this->is_active ?? true,
            'is_featured' => $this->is_featured ?? false,
            'display_order' => $this->display_order ?? 0,
            'allow_pause' => $this->allow_pause ?? false,
            'billing_cycle' => $this->billing_cycle ?? 'monthly',
            'trial_period_days' => $this->trial_period_days ?? 0,
        ]);
    }
}