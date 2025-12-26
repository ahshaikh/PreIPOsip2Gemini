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
            'name' => 'required|string|max:255',
            'monthly_amount' => 'required|numeric|min:0',
            'duration_months' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
            'is_featured' => 'required|boolean',
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
            // Features array - accepts both string[] and object[] formats
            'features' => 'nullable|array',
            'configs' => 'nullable|array',
        ];
    }
}