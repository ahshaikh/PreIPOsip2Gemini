<?php
// V-FINAL-1730-330 (Created) | V-FINAL-1730-478 (DigiLocker Flow)

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Razorpay\Api\Api;
use Carbon\Carbon;
use App\Models\UserKyc;
use App\Models\KycDocument;

class VerificationService
{
    protected $digilockerClientId;
    protected $digilockerClientSecret;
    protected $digilockerRedirectUri;

    public function __construct()
    {
        $this->digilockerClientId = env('DIGILOCKER_CLIENT_ID');
        $this->digilockerClientSecret = env('DIGILOCKER_CLIENT_SECRET');
        $this->digilockerRedirectUri = env('APP_URL') . '/api/v1/kyc/digilocker/callback';
    }

    /**
     * FSD-KYC-016: Get the URL to redirect the user to DigiLocker.
     */
    public function getDigiLockerRedirectUrl(UserKyc $kyc): string
    {
        $state = $kyc->id . ':' . Str::random(32); // Use KYC ID as state
        Cache::put('digilocker_state_' . $kyc->id, $state, 600); // 10 min expiry

        $params = [
            'response_type' => 'code',
            'client_id' => $this->digilockerClientId,
            'redirect_uri' => $this->digilockerRedirectUri,
            'state' => $state,
        ];

        // This is a standard DigiLocker auth endpoint
        return 'https://api.digitallocker.gov.in/v1/oauth2/authorize?' . http_build_query($params);
    }

    /**
     * FSD-KYC-016: Handle the callback from DigiLocker.
     */
    public function handleDigiLockerCallback(string $code, string $state): UserKyc
    {
        // 1. Validate State (Prevent CSRF)
        list($kycId, $nonce) = explode(':', $state);
        $storedState = Cache::pull('digilocker_state_' . $kycId);
        
        if (!$storedState || $storedState !== $state) {
            throw new \Exception("Invalid state. CSRF detected.");
        }

        $kyc = UserKyc::findOrFail($kycId);

        // 2. Exchange Code for Access Token
        $tokenResponse = Http::asForm()->post('https://api.digitallocker.gov.in/v1/oauth2/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->digilockerClientId,
            'client_secret' => $this->digilockerClientSecret,
            'redirect_uri' => $this->digilockerRedirectUri,
        ]);

        if ($tokenResponse->failed()) {
            throw new \Exception("DigiLocker token exchange failed.");
        }
        
        $accessToken = $tokenResponse->json('access_token');

        // 3. Fetch User's Aadhaar Data
        $dataResponse = Http::withToken($accessToken)
            ->get('https://api.digitallocker.gov.in/v1/xml/Aadhaar'); // Get e-Aadhaar XML
            
        if ($dataResponse->failed()) {
            throw new \Exception("Failed to fetch Aadhaar data.");
        }

        // 4. Parse XML (This is complex)
        // [AUDIT FIX] Replaced mock logic with actual XML parsing
        $xmlContent = $dataResponse->body();
        if (empty($xmlContent)) {
            throw new \Exception("Empty response from DigiLocker.");
        }

        // Use internal errors to handle XML parsing issues gracefully
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlContent);
        
        if ($xml === false) {
            Log::error("DigiLocker XML Parse Error", ['errors' => libxml_get_errors()]);
            libxml_clear_errors();
            throw new \Exception("Failed to parse Aadhaar XML response.");
        }

        // Extract Data (DigiLocker XML usually has <UidData> with <Poi> and <Poa>)
        // Adjust namespaces if required by the specific API version
        $uidData = $xml->KycRes->UidData ?? null;
        $poi = $uidData ? $uidData->Poi : null;

        if (!$poi) {
            throw new \Exception("Invalid XML structure: Identity data (Poi) missing.");
        }

        $nameOnCard = (string) $poi['name'];
        // $dobOnCard = (string) $poi['dob'];
        // $gender = (string) $poi['gender'];
        
        // --- DELETED MOCK PARSING for V1 ---
        // $nameOnCard = $kyc->user->profile->first_name . ' ' . $kyc->user->profile->last_name;
        // -----------------------------

        // 5. Run Validation
        if (!$this->checkNameMatch($kyc->user->profile->first_name, $nameOnCard)) {
            Log::warning("KYC Name Mismatch: Profile='{$kyc->user->profile->first_name}', Aadhaar='{$nameOnCard}'");
            throw new \Exception("Aadhaar name ($nameOnCard) does not match profile name.");
        }

        // 6. Update KYC
        $kyc->aadhaar_number = 'DL-VERIFIED-' . $kyc->id;
        // [AUDIT FIX] Auto-verify immediately as source is trusted
        $kyc->status = 'verified'; 
        $kyc->verified_at = now();

        $kyc->documents()->updateOrCreate(
            ['user_kyc_id' => $kyc->id, 'doc_type' => 'aadhaar_front'],
            [
                'processing_status' => 'verified', 
                'file_name' => 'DigiLocker e-Aadhaar',
                // We don't save the full XML to disk for security/privacy, just flag it
                'file_path' => 'virtual/digilocker', 
                'mime_type' => 'application/xml'
            ]
        );
        $kyc->save();

        return $kyc;
    }

    // ... (runAutomatedKyc, verifyPan, verifyBank, etc. remain the same) ...
    public function runAutomatedKyc(UserKyc $kyc) { /* ... */ }
    public function verifyPan(string $pan, string $name, string $dob = null) { /* ... */ }
    public function verifyBank(string $account, string $ifsc, string $name) { /* ... */ }
    private function processPanResponse($data, $pan, $inputName, $inputDob) { /* ... */ }
    
    private function checkNameMatch($inputName, $apiName) { 
        // Simple fuzzy match or string containment for robustness
        return Str::contains(strtolower($apiName), strtolower($inputName));
    }
    
    public function parseDocumentWithOcr(KycDocument $doc) { /* ... */ }
}