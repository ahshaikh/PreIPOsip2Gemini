<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Company Store Request Validation.
 */
class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Required core fields
            'name' => 'required|string|max:255|unique:companies,name',
            'sector' => 'nullable|string|max:100',
            'founded_year' => 'nullable|integer|min:1800|max:' . date('Y'),
            'headquarters' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',

            // Financial Info
            'latest_valuation' => 'nullable|numeric|min:0',
            'total_funding' => 'nullable|numeric|min:0',
            'key_metrics' => 'nullable|array',
            'investors' => 'nullable|array',

            // Company Details
            'description' => 'nullable|string',
            'logo' => 'nullable|string|max:500',
            'cover_image' => 'nullable|string|max:500',

            // Status & Features
            'is_featured' => 'nullable|boolean',
            'is_verified' => 'nullable|boolean',
            'profile_completed' => 'nullable|boolean',

            // Enterprise Features
            'is_enterprise' => 'nullable|boolean',
            'max_users_quota' => 'nullable|integer|min:1',
            'settings' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Company name is required.',
            'name.unique' => 'A company with this name already exists.',
            'founded_year.min' => 'Founded year must be 1800 or later.',
            'founded_year.max' => 'Founded year cannot be in the future.',
            'website.url' => 'Website must be a valid URL.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'is_featured' => $this->is_featured ?? false,
            'is_verified' => $this->is_verified ?? false,
            'profile_completed' => $this->profile_completed ?? false,
            'is_enterprise' => $this->is_enterprise ?? false,
        ]);
    }
}
