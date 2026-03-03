<?php

namespace App\Http\Requests\Admin;

use App\Enums\DisputeStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Permission handled by middleware
    }

    public function rules(): array
    {
        return [
            'target_status' => [
                'required',
                'string',
                Rule::in(DisputeStatus::values()),
            ],
            'comment' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'target_status.in' => 'Invalid target status. Allowed: ' . implode(', ', DisputeStatus::values()),
        ];
    }

    /**
     * Get the target status enum.
     */
    public function getTargetStatus(): DisputeStatus
    {
        return DisputeStatus::from($this->target_status);
    }
}
