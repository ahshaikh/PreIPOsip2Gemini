<?php
// V-PHASE1-1730-018 (Created) | V-FINAL-1730-296 | V-FINAL-1730-477 (Auto-KYC Job)

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\KycSubmitRequest;
use App\Models\UserKyc;
use App\Models\KycDocument;
use App\Services\VerificationService;
use App\Services\FileUploadService;
use App\Jobs\ProcessKycJob; // <-- IMPORT
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class KycController extends Controller
{
    protected $fileUploader;
    protected $verificationService; // <-- IMPORT
    public function __construct(FileUploadService $fileUploader, VerificationService $verificationService)
    {
        $this->fileUploader = $fileUploader;
	$this->verificationService = $verificationService; // <-- INJECT
    }
    
    public function show(Request $request)
    {
        $kyc = $request->user()->kyc()->with('documents')->first();
        return response()->json($kyc);
    }

    public function store(KycSubmitRequest $request)
    {
        // Check if KYC module is enabled
        if (!setting('kyc_enabled', true)) {
            return response()->json([
                'message' => 'KYC verification is currently disabled. Please contact support.'
            ], 503);
        }

        $user = $request->user();
        $kyc = $user->kyc;

        if ($kyc->status === 'verified') {
            return response()->json(['message' => 'KYC is already verified.'], 400);
        }

        $kyc->update([
            'pan_number' => $request->pan_number,
            'aadhaar_number' => $request->aadhaar_number,
            'demat_account' => $request->demat_account,
            'bank_account' => $request->bank_account,
            'bank_ifsc' => $request->bank_ifsc,
            'status' => 'processing', // <-- NEW: Set to processing
            'submitted_at' => now(),
        ]);

        $kyc->documents()->delete();

        $docTypes = [
            'aadhaar_front' => $request->file('aadhaar_front'),
            'aadhaar_back' => $request->file('aadhaar_back'),
            'pan' => $request->file('pan'),
            'bank_proof' => $request->file('bank_proof'),
            'demat_proof' => $request->file('demat_proof'),
        ];

        try {
            foreach ($docTypes as $type => $file) {
                if ($file) {
                    $path = $this->fileUploader->upload($file, [
                        'path' => "kyc/{$user->id}",
                        'encrypt' => true,
                        'virus_scan' => true
                    ]);
                    
                    KycDocument::create([
                        'user_kyc_id' => $kyc->id,
                        'doc_type' => $type,
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'processing_status' => 'pending',
                    ]);
                }
            }
        } catch (\Exception $e) {
            $kyc->update(['status' => 'pending']);
            return response()->json(['message' => $e->getMessage()], 400);
        }

        // --- NEW: Dispatch the Job ---
        ProcessKycJob::dispatch($kyc)->onQueue('high');

        return response()->json([
            'message' => 'KYC documents submitted. We are processing your verification.',
            'kyc' => $kyc->load('documents'),
        ], 201);
    }
/**
     * NEW: Redirect user to DigiLocker
     */
    public function redirectToDigiLocker(Request $request)
    {
        $kyc = $request->user()->kyc;
        $redirectUrl = $this->verificationService->getDigiLockerRedirectUrl($kyc);
        
        return response()->json(['redirect_url' => $redirectUrl]);
    }

    /**
     * NEW: Handle callback from DigiLocker
     */
    public function handleDigiLockerCallback(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'state' => 'required|string',
        ]);
        
        try {
            $kyc = $this->verificationService->handleDigiLockerCallback(
                $request->code,
                $request->state
            );

            // Success! Redirect user back to their KYC page
            return redirect(env('FRONTEND_URL') . '/kyc?status=digilocker_success');

        } catch (\Exception $e) {
            Log::error("DigiLocker Callback Failed: " . $e->getMessage());
            return redirect(env('FRONTEND_URL') . '/kyc?status=digilocker_failed');
        }
    }


    public function viewDocument(Request $request, $id)
    {
        $doc = KycDocument::findOrFail($id);
        $user = $request->user();
        
        if ($user->id !== $doc->kyc->user_id && !$user->hasRole(['admin', 'super-admin'])) {
            abort(403);
        }
        if (!Storage::exists($doc->file_path)) {
            abort(404);
        }

        try {
            $encryptedContent = Storage::get($doc->file_path);
            $content = Crypt::decrypt($encryptedContent);
            return response($content)
                ->header('Content-Type', $doc->mime_type)
                ->header('Content-Disposition', 'inline; filename="' . $doc->file_name . '"');
        } catch (\Exception $e) {
            abort(500, 'Could not decrypt document.');
        }
    }
    
    // The individual verifyPan/verifyBank buttons are no longer needed
    // as the ProcessKycJob handles the full flow.
}