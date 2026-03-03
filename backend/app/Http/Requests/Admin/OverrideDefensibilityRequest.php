<?php

namespace App\Http\Requests\Admin;

use App\Enums\DisputeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * OverrideDefensibilityRequest - Validates defensibility override requests
 *
 * Admins can override the computed defensibility status when:
 * - Snapshot integrity shows issues but admin confirms data is correct
 * - External evidence supports override
 * - Business decision requires override with documented reason
 */
class OverrideDefensibilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Permission checked in controller
    }

    public function rules(): array
    {
        return [
            'override_type' => [
                'required',
                'string',
                Rule::in([
                    'integrity_confirmed',      // Admin confirms integrity despite system warning
                    'external_evidence',        // External evidence supports the position
                    'business_decision',        // Business decision with documented reason
                    'data_correction_pending',  // Data will be corrected, override temporary
                ]),
            ],
            'reason' => [
                'required',
                'string',
                'min:20',
                'max:2000',
            ],
            'evidence_reference' => [
                'nullable',
                'string',
                'max:500',
            ],
            'expires_at' => [
                'nullable',
                'date',
                'after:now',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'override_type.in' => 'Override type must be one of: integrity_confirmed, external_evidence, business_decision, data_correction_pending.',
            'reason.min' => 'Override reason must be at least 20 characters to ensure proper documentation.',
            'reason.required' => 'A documented reason is required for defensibility override.',
            'expires_at.after' => 'Override expiration must be in the future.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'override_type' => 'override type',
            'evidence_reference' => 'evidence reference',
            'expires_at' => 'expiration date',
        ];
    }
}
