<?php
// V-FINAL-1730-545 (Created)

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CaptchaService
{
    /**
     * FSD-SYS-109: Verify a CAPTCHA token.
     */
    public function verify(?string $token): bool
    {
        // 1. Check if CAPTCHA is globally enabled
        if (!setting('captcha_enabled', false)) {
            return true; // Bypass if disabled
        }

        if (empty($token)) {
            return false;
        }

        $provider = setting('captcha_provider', 'recaptcha_v2');
        $secretKey = setting('captcha_secret_key');

        if (empty($secretKey)) {
            Log::error("CAPTCHA Error: Secret Key is not configured.");
            return false; // Fail safe
        }

        try {
            $url = 'https://www.google.com/recaptcha/api/siteverify';
            if ($provider === 'hcaptcha') {
                $url = 'https://hcaptcha.com/siteverify';
            }

            $response = Http::asForm()->post($url, [
                'secret' => $secretKey,
                'response' => $token,
            ]);
            
            $data = $response->json();

            if (!($data['success'] ?? false)) {
                Log::warning("CAPTCHA Verification Failed", ['errors' => $data['error-codes'] ?? 'Unknown']);
                return false;
            }
            
            // For reCAPTCHA v3, also check the score
            if ($provider === 'recaptcha_v3') {
                $threshold = (float) setting('captcha_threshold', 0.5);
                if (($data['score'] ?? 0) < $threshold) {
                    Log::warning("CAPTCHA Failed: Low score ({$data['score']})");
                    return false;
                }
            }
            
            return true;

        } catch (\Exception $e) {
            Log::error("CAPTCHA API Request Failed: " . $e->getMessage());
            return false;
        }
    }
}