<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'monthly_amount' => 'sometimes|required|numeric|min:0',
            'duration_months' => 'sometimes|required|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|required|boolean',
            'is_featured' => 'sometimes|required|boolean',
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
            'configs' => 'nullable|array',
        ];
    }
}