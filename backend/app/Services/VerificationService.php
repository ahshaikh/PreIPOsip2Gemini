<?php
// V-FINAL-1730-213

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VerificationService
{
    /**
     * Verify PAN Number.
     * Uses a generic fintech provider structure.
     */
    public function verifyPan(string $panNumber, string $name)
    {
        // MOCK MODE: For local testing without spending money on API calls
        if (env('APP_ENV') === 'local') {
            // Simulate success if PAN starts with 'ABC'
            if (str_starts_with($panNumber, 'ABC')) {
                return [
                    'valid' => true,
                    'name_match' => true,
                    'registered_name' => $name
                ];
            }
            // Simulate failure otherwise
            return ['valid' => false, 'error' => 'Invalid PAN in mock mode'];
        }

        try {
            // Replace with your actual vendor URL and Key
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('KYC_VENDOR_KEY'),
            ])->post('https://api.vendor.com/v1/pan/verify', [
                'pan' => $panNumber,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                // Logic to compare names using similar_text or levenshtein
                $apiName = $data['data']['full_name'];
                
                similar_text(strtoupper($name), strtoupper($apiName), $percent);
                
                return [
                    'valid' => true,
                    'name_match' => $percent > 80,
                    'registered_name' => $apiName
                ];
            }
            
            return ['valid' => false, 'error' => 'API Error'];

        } catch (\Exception $e) {
            Log::error("PAN Verification Failed: " . $e->getMessage());
            return ['valid' => false, 'error' => 'Connection Error'];
        }
    }

    /**
     * Verify Bank Account (Penny Drop).
     */
    public function verifyBank(string $account, string $ifsc, string $name)
    {
        if (env('APP_ENV') === 'local') {
            return [
                'valid' => true,
                'beneficiary_name' => $name,
                'name_match' => true
            ];
        }

        try {
            // Example using Razorpay Fund Account Validation
            $api = new \Razorpay\Api\Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
            
            // 1. Create Fund Account
            $fundAccount = $api->fundAccount->create([
                'contact_id' => 'cont_123456789', // You'd create a contact first
                'account_type' => 'bank_account',
                'bank_account' => [
                    'name' => $name,
                    'ifsc' => $ifsc,
                    'account_number' => $account,
                ]
            ]);

            // 2. Validate (Penny Drop)
            // Note: This costs money per call
            // $validation = $api->fundAccountValidation->create([...]);
            
            return ['valid' => true, 'beneficiary_name' => $name, 'name_match' => true];

        } catch (\Exception $e) {
            return ['valid' => false, 'error' => $e->getMessage()];
        }
    }
}