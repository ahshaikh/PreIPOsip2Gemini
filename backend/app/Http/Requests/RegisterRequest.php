// V-PHASE1-1730-019
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => 'required|string|max:50|unique:users|alpha_dash',
            'email' => 'required|string|email|max:255|unique:users',
            'mobile' => 'required|string|size:10|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'referral_code' => 'nullable|string|exists:users,referral_code',
        ];
    }
}