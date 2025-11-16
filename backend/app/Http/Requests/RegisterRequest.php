<?php
// V-PHASE1-1730-019 (Created) | V-FINAL-1730-482 (Referral Fix) | V-FINAL-1730-546 (Captcha Added)

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use App\Models\User;
use Illuminate\Contracts\Validation\Rule;
use App\Services\CaptchaService; // <-- IMPORT

/**
 * Custom Rule for case-insensitive referral code check
 */
class CaseInsensitiveReferralCode implements Rule
{
    public function passes($attribute, $value)
    {
        return User::whereRaw('BINARY referral_code = ?', [strtoupper($value)])->exists();
    }
    public function message() { return 'The selected referral code is invalid.'; }
    public function validate($attribute, $value, $fail) {
        if (!$this->passes($attribute, $value)) $fail($this->message());
    }
}

/**
 * Custom Rule for CAPTCHA
 */
class Captcha implements Rule
{
    public function passes($attribute, $value)
    {
        // Only run validation if the setting is enabled
        if (!setting('captcha_enabled', false)) return true;
        if (!setting('captcha_show_on_registration', false)) return true;

        return app(CaptchaService::class)->verify($value);
    }
    public function message() { return 'The CAPTCHA verification failed. Please try again.'; }
    public function validate($attribute, $value, $fail) {
        if (!$this.passes($attribute, $value)) $fail($this.message());
    }
}


class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
                Password::min(8)->letters()->mixedCase()->numbers()->symbols()
            ],
            'referral_code' => [
                'nullable',
                'string',
                new CaseInsensitiveReferralCode()
            ],
            // --- NEW: CAPTCHA RULE ---
            'captcha_token' => [
                'nullable', // Nullable because it might be disabled
                'string',
                new Captcha() // Use our custom rule
            ],
        ];
    }
}