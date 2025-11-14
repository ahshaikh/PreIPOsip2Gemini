<?php
// V-PHASE1-1730-019 (Created) | V-FINAL-1730-423 (Security Upgraded)

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password; // <-- IMPORT

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
            
            // --- UPGRADED RULE (FSD-SYS-106) ---
            'password' => [
                'required', 
                'confirmed', 
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
            ],
            // ---------------------------------
            
            'referral_code' => 'nullable|string|exists:users,referral_code',
        ];
    }
}