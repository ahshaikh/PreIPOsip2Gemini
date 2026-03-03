<?php

namespace App\Http\Requests\User;

use App\Enums\DisputeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FileDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth handled by middleware
    }

    public function rules(): array
    {
        return [
            'type' => [
                'required',
                'string',
                Rule::in(DisputeType::values()),
            ],
            'disputable_type' => [
                'nullable',
                'string',
                Rule::in([
                    'Payment',
                    'Investment',
                    'Withdrawal',
                    'BonusTransaction',
                    'Allocation',
                ]),
            ],
            'disputable_id' => [
                'nullable',
                'integer',
                'required_with:disputable_type',
            ],
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'required',
                'string',
                'max:5000',
            ],
            'evidence' => [
                'nullable',
                'array',
            ],
            'evidence.*' => [
                'array',
            ],
            'evidence.*.type' => [
                'required_with:evidence.*',
                'string',
                'in:text,screenshot,document,link',
            ],
            'evidence.*.value' => [
                'required_with:evidence.*',
                'string',
                'max:500',
            ],
            'evidence.*.description' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'Invalid dispute type. Allowed: ' . implode(', ', DisputeType::values()),
            'disputable_id.required_with' => 'You must specify the ID of the entity being disputed.',
        ];
    }

    /**
     * Get the disputable model instance if specified.
     */
    public function getDisputable(): ?\Illuminate\Database\Eloquent\Model
    {
        if (!$this->disputable_type || !$this->disputable_id) {
            return null;
        }

        $modelClass = $this->getDisputableModelClass();

        if (!$modelClass || !class_exists($modelClass)) {
            return null;
        }

        // Ensure the disputable belongs to the authenticated user
        $model = $modelClass::where('id', $this->disputable_id)
            ->where('user_id', $this->user()->id)
            ->first();

        return $model;
    }

    /**
     * Map short name to full model class.
     */
    private function getDisputableModelClass(): ?string
    {
        return match ($this->disputable_type) {
            'Payment' => \App\Models\Payment::class,
            'Investment' => \App\Models\Investment::class,
            'Withdrawal' => \App\Models\Withdrawal::class,
            'BonusTransaction' => \App\Models\BonusTransaction::class,
            'Allocation' => \App\Models\Allocation::class,
            default => null,
        };
    }
}
