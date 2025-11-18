<?php
// V-PHASE1-1730-020 (Created) | V-FINAL-1730-349 (Original) | V-FINAL-1730-546 (FSD-SYS-109) | V-FINAL-1730-642 

namespace App\Http\Requests;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Services\CaptchaService;
use App\Models\User;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            // FSD-SYS-109: Captcha
            'captcha_token' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if (setting('captcha_enabled', false) && setting('captcha_show_on_login', false)) {
                        if (!app(CaptchaService::class)->verify($value)) {
                            $fail('CAPTCHA verification failed.');
                        }
                    }
                },
            ],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): User
    {
        $this->ensureIsNotRateLimited();

        $login = $this->input('login');
        $password = $this->input('password');

        // Determine login type
        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        
        // Check if it's a mobile number (10 digits)
        if (preg_match('/^[0-9]{10}$/', $login)) {
            $fieldType = 'mobile';
        }

        if (! Auth::attempt([$fieldType => $login, 'password' => $password], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        
        return Auth::user();
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('login')).'|'.$this->ip());
    }
}