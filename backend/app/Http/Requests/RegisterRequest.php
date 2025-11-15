<?php
// V-PHASE1-1730-019 (Created) | V-FINAL-1730-482 (Referral Fix)

namespace App\HttpHttp\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use App\Models\User; // <-- IMPORT
use Illuminate\Contracts\Validation\Rule; // <-- IMPORT

/**
 * Custom Rule for case-insensitive referral code check
 */
class CaseInsensitiveReferralCode implements Rule
{
    public function passes($attribute, $value)
    {
        // FSD-MKTG-010: "Referral codes must be case-insensitive"
        return User::whereRaw('BINARY referral_code = ?', [strtoupper($value)])->exists();
    }

    public function message()
    {
        return 'The selected referral code is invalid.';
    }
}


class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public route
    }

    public function rules(): array
    {
        return [
            'username' => 'required|string|alpha_dash|min:3|max:50|unique:users,username',
            'email'    => 'required|string|email|max:255|unique:users,email',
            'mobile'   => 'required|string|regex:/^[0-9]{10}$/|unique:users,mobile',
            
            'password' => [
                'required', 
                'confirmed', 
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
            ],
            
            // --- UPDATED RULE (Case-Insensitive) ---
            'referral_code' => [
                'nullable',
                'string',
                new CaseInsensitiveReferralCode() // Use custom rule
            ],
        ];
    }
}