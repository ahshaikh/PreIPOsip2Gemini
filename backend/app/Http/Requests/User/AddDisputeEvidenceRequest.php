<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

/**
 * AddDisputeEvidenceRequest - Validates evidence submission by investors
 *
 * Investors can add evidence to their disputes:
 * - File references (uploaded separately)
 * - Text descriptions
 * - External links/references
 */
class AddDisputeEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Check that the dispute belongs to the authenticated user
        $dispute = $this->route('dispute');
        return $dispute && $dispute->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'evidence' => [
                'required',
                'array',
                'min:1',
                'max:10',
            ],
            'evidence.*.type' => [
                'required',
                'string',
                'in:file,text,link,screenshot,transaction_reference',
            ],
            'evidence.*.value' => [
                'required',
                'string',
                'max:1000',
            ],
            'evidence.*.description' => [
                'nullable',
                'string',
                'max:500',
            ],
            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'evidence.required' => 'At least one piece of evidence is required.',
            'evidence.min' => 'At least one piece of evidence is required.',
            'evidence.max' => 'Maximum 10 evidence items allowed per submission.',
            'evidence.*.type.in' => 'Evidence type must be one of: file, text, link, screenshot, transaction_reference.',
            'evidence.*.value.required' => 'Each evidence item must have a value.',
            'evidence.*.value.max' => 'Evidence value cannot exceed 1000 characters.',
        ];
    }

    /**
     * Get formatted evidence array for storage.
     */
    public function getFormattedEvidence(): array
    {
        $evidence = $this->validated()['evidence'];
        $timestamp = now()->toIso8601String();

        return array_map(function ($item) use ($timestamp) {
            return [
                'type' => $item['type'],
                'value' => $item['value'],
                'description' => $item['description'] ?? null,
                'submitted_at' => $timestamp,
                'submitted_by' => $this->user()->id,
            ];
        }, $evidence);
    }
}
