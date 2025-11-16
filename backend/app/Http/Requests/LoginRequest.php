<?php
// V-PHASE1-1730-020 | V-FINAL-1730-349 (Original) | V-FINAL-1730-546 (FSD-SYS-109)

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule; // <-- 1. Import the Rule class

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Guests can attempt to log in
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // 2. Check if CAPTCHA is globally enabled in the config
        // This is based on the hardcoded implementation (FSD-SYS-109 Partial)
        $isCaptchaEnabled = config('captcha.enabled', false);

        return [
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember' => 'nullable|boolean',

            // 3. Add the CAPTCHA rule
            // FSD-SYS-109: Apply CAPTCHA to Login form
            // We assume the frontend will send the field 'g_recaptcha_response'
            'g_recaptcha_response' => [
                'string',
                // This rule is conditional based on the config setting
                Rule::requiredIf($isCaptchaEnabled),
                // This 'captcha' rule relies on a Laravel package like 'anhskohbo/no-captcha'
                // which must be installed and configured.
                'captcha',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'g_recaptcha_response.required' => 'The CAPTCHA response is required. Please check the box.',
            'g_recaptcha_response.captcha' => 'Invalid CAPTCHA. Please try again.',
        ];
    }
}