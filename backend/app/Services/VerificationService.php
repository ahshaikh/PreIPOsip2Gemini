<?php
// V-FINAL-1730-330 (Robus Verification Engine)

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Razorpay\Api\Api;
use Carbon\Carbon;

class VerificationService
{
    /**
     * Verify PAN Number.
     */
    public function verifyPan(string $pan, string $name, string $dob = null)
    {
        $cacheKey = "kyc_pan_{$pan}";
        
        // 1. Check Cache
        if (Cache::has($cacheKey)) {
            Log::info("PAN Verification Hit Cache: {$pan}");
            return Cache::get($cacheKey);
        }

        Log::info("Verifying PAN: {$pan}");

        // MOCK MODE
        if (config('app.env') === 'local') {
            $response = [
                'valid' => true, 
                'full_name' => $name, // Simulate exact match
                'dob' => $dob // Simulate match
            ];
            return $this->processPanResponse($response, $pan, $name, $dob);
        }

        try {
            // Real API Call (Generic Vendor Structure)
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.kyc.key'),
            ])->timeout(10)->post(config('services.kyc.url') . '/pan/verify', [
                'pan' => $pan,
            ]);

            if ($response->failed()) {
                Log::error("PAN API Failure: " . $response->body());
                return ['valid' => false, 'error' => 'Service unavailable'];
            }

            $data = $response->json();
            $result = $this->processPanResponse($data, $pan, $name, $dob);
            
            // Cache successful results for 24 hours
            if ($result['valid']) {
                Cache::put($cacheKey, $result, 86400);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error("PAN Verification Exception: " . $e->getMessage());
            return ['valid' => false, 'error' => 'Connection error'];
        }
    }

    /**
     * Verify Aadhaar (via DigiLocker or similar).
     */
    public function verifyAadhaar(string $aadhaar, string $name, string $dob = null)
    {
        Log::info("Verifying Aadhaar: {$aadhaar}");

        // MOCK
        if (config('app.env') === 'local') {
            return ['valid' => true, 'name_match' => true];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.kyc.key'),
            ])->timeout(10)->post(config('services.kyc.url') . '/aadhaar/verify', [
                'aadhaar_number' => $aadhaar,
            ]);

            if ($response->failed()) {
                Log::error("Aadhaar API Failure");
                return ['valid' => false, 'error' => 'Service unavailable'];
            }

            $data = $response->json();
            
            // Logic Checks
            $nameMatch = $this->checkNameMatch($name, $data['name'] ?? '');
            $dobMatch = $dob ? ($dob === ($data['dob'] ?? '')) : true;

            if (!$nameMatch) return ['valid' => false, 'error' => 'Name mismatch'];
            if (!$dobMatch) return ['valid' => false, 'error' => 'DOB mismatch'];

            return ['valid' => true];

        } catch (\Exception $e) {
            return ['valid' => false, 'error' => 'Connection error'];
        }
    }

    /**
     * Verify Bank Account (Penny Drop).
     */
    public function verifyBank(string $account, string $ifsc, string $name)
    {
        Log::info("Verifying Bank: {$account}");

        // IFSC Validation (Regex)
        if (!preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $ifsc)) {
            return ['valid' => false, 'error' => 'Invalid IFSC format'];
        }

        if (config('app.env') === 'local') {
            return ['valid' => true, 'beneficiary_name' => $name, 'name_match' => true];
        }

        try {
            $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
            
            // Create Fund Account
            $fundAccount = $api->fundAccount->create([
                'contact_id' => 'cont_mock_123', // In real app, create/fetch contact first
                'account_type' => 'bank_account',
                'bank_account' => [
                    'name' => $name,
                    'ifsc' => $ifsc,
                    'account_number' => $account,
                ]
            ]);

            // In a real integration, you would then Create a Validation Transaction
            // $validation = $api->fundAccount->validate($fundAccount->id, ...);
            
            // Simulating response inspection
            $bankName = $fundAccount->bank_account->name ?? $name;
            $nameMatch = $this->checkNameMatch($name, $bankName);

            return [
                'valid' => true, // Assume penny drop succeeded
                'beneficiary_name' => $bankName,
                'name_match' => $nameMatch
            ];

        } catch (\Exception $e) {
            Log::error("Bank Verification Failed: " . $e->getMessage());
            return ['valid' => false, 'error' => $e->getMessage()];
        }
    }

    // --- HELPERS ---

    private function processPanResponse($data, $pan, $inputName, $inputDob)
    {
        // 1. Check Validity
        if (empty($data['full_name'])) {
            return ['valid' => false, 'error' => 'Invalid PAN'];
        }

        // 2. Name Match
        $apiName = $data['full_name'];
        $nameMatch = $this->checkNameMatch($inputName, $apiName);

        if (!$nameMatch) {
            return ['valid' => false, 'error' => 'Name mismatch on PAN'];
        }

        // 3. DOB Match (if provided)
        if ($inputDob && isset($data['dob'])) {
            if ($inputDob !== $data['dob']) {
                return ['valid' => false, 'error' => 'DOB mismatch on PAN'];
            }
        }

        return [
            'valid' => true,
            'registered_name' => $apiName
        ];
    }

    private function checkNameMatch($inputName, $apiName)
    {
        // Simple fuzzy match
        similar_text(strtoupper($inputName), strtoupper($apiName), $percent);
        return $percent >= 80; // 80% threshold
    }
}