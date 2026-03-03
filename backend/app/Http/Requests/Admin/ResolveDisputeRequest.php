<?php

namespace App\Http\Requests\Admin;

use App\Models\Dispute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolveDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Permission handled by middleware
    }

    public function rules(): array
    {
        return [
            'outcome' => [
                'required',
                'string',
                Rule::in(['approved', 'rejected']),
            ],
            'resolution' => [
                'required',
                'string',
                'max:5000',
            ],
            'settlement_action' => [
                'required_if:outcome,approved',
                'nullable',
                'string',
                Rule::in(Dispute::getSettlementActions()),
            ],
            'settlement_amount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:10000000', // Max 1 crore rupees
            ],
            'settlement_details' => [
                'nullable',
                'array',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'outcome.in' => 'Outcome must be either "approved" or "rejected".',
            'settlement_action.required_if' => 'Settlement action is required when approving a dispute.',
            'settlement_action.in' => 'Invalid settlement action. Allowed: ' . implode(', ', Dispute::getSettlementActions()),
        ];
    }

    /**
     * Get settlement amount in paise.
     */
    public function getSettlementAmountPaise(): ?int
    {
        if (!$this->settlement_amount) {
            return null;
        }

        return (int) ($this->settlement_amount * 100);
    }
}
