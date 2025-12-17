<?php
// [AUDIT FIX] Created missing UpdateProfileRequest validation class
// Module 2: User Management - Profile Update Validation

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for updating user profile information.
 *
 * This request class ensures that all profile updates adhere to business rules
 * and data integrity constraints. All fields are optional (using 'sometimes')
 * to allow partial updates.
 *
 * Security Note: Authorization is handled by the controller using $request->user()
 * which ensures users can only update their own profiles.
 */
class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is handled at the controller level using auth middleware
     * and $request->user() to ensure users can only access their own profile.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Personal Information
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',

            // Date of Birth - Must be at least 18 years old for investment compliance
            'dob' => 'sometimes|date|before:-18 years',

            // Gender - Standard options
            'gender' => 'sometimes|in:male,female,other,prefer_not_to_say',

            // Address Information
            'address' => 'sometimes|string|max:500',
            'city' => 'sometimes|string|max:100',
            'state' => 'sometimes|string|max:100',

            // Pincode - Indian postal code format (6 digits)
            'pincode' => 'sometimes|string|regex:/^[0-9]{6}$/',

            // Preferences - JSON object for flexible settings
            'preferences' => 'sometimes|array',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dob.before' => 'You must be at least 18 years old to use this platform.',
            'pincode.regex' => 'Pincode must be exactly 6 digits.',
            'gender.in' => 'Please select a valid gender option.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'dob' => 'date of birth',
            'first_name' => 'first name',
            'last_name' => 'last name',
        ];
    }
}
