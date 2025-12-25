<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-SECURITY-IDOR-FIX | V-ASYNC-PIPELINE | V-FINAL-1730-629 (Manual KYC)
 * Refactored to address Module 2 Audit Gaps:
 * 1. Implements Private Storage (No public access to sensitive docs).
 * 2. Uses Temporary Signed URLs for document viewing.
 * 3. Centralizes status logic via KycStatusService.
 * 4. Manual KYC verification with address_proof, photo, and signature documents.
 */

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\KycSubmitRequest;
use App\Models\UserKyc;
use App\Models\KycDocument;
use App\Services\VerificationService;
use App\Services\FileUploadService;
use App\Services\Kyc\KycStatusService; // <-- NEW SERVICE
use App\Jobs\ProcessKycJob;
use App\Enums\KycStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class KycController extends Controller
{
    protected $fileUploader;
    protected $verificationService;
    protected $statusService; // <-- ADDED SERVICE

    /**
     * Updated Constructor to inject the KycStatusService.
     */
    public function __construct(
        FileUploadService $fileUploader, 
        VerificationService $verificationService,
        KycStatusService $statusService
    ) {
        $this->fileUploader = $fileUploader;
        $this->verificationService = $verificationService;
        $this->statusService = $statusService;
    }
    
    public function show(Request $request)
    {
        $user = $request->user();
        $kyc = $user->kyc()->with('documents')->first();

        // If KYC record doesn't exist, create a pending one
        if (!$kyc) {
            $kyc = UserKyc::create([
                'user_id' => $user->id,
                'status' => KycStatus::PENDING->value
            ]);
            // Reload with documents relationship
            $kyc = $user->kyc()->with('documents')->first();
        }

        return response()->json($kyc);
    }

    /**
     * Submit KYC documents for verification.
     * * [AUDIT FIX]: 
     * - Documents are now strictly stored on the 'private' disk.
     * - Status transitions are managed via the StatusService.
     */
    public function store(KycSubmitRequest $request)
    {
        if (!setting('kyc_enabled', true)) {
            return response()->json(['message' => 'KYC is currently disabled.'], 503);
        }

        $user = $request->user();

        // Get or create KYC record
        $kyc = $user->kyc;

        if (!$kyc) {
            // Create new KYC record if it doesn't exist
            $kyc = UserKyc::create([
                'user_id' => $user->id,
                'status' => KycStatus::PENDING->value
            ]);
        }

        if ($kyc->status === KycStatus::VERIFIED->value) {
            return response()->json(['message' => 'KYC is already verified.'], 400);
        }

        // Use the status service to handle the "Processing" transition
        $this->statusService->transitionStatus($kyc, KycStatus::PROCESSING->value);

        $kyc->update([
            'pan_number' => $request->pan_number,
            'aadhaar_number' => $request->aadhaar_number,
            'demat_account' => $request->demat_account,
            'bank_account' => $request->bank_account,
            'bank_ifsc' => $request->bank_ifsc,
            'submitted_at' => now(),
        ]);

        // Cleanup old documents before new submission
        $kyc->documents()->delete();

        $docTypes = [
            'aadhaar_front' => $request->file('aadhaar_front'),
            'aadhaar_back' => $request->file('aadhaar_back'),
            'pan' => $request->file('pan'),
            'bank_proof' => $request->file('bank_proof'),
            'demat_proof' => $request->file('demat_proof'),
            'address_proof' => $request->file('address_proof'),
            'photo' => $request->file('photo'),
            'signature' => $request->file('signature'),
        ];

        try {
            foreach ($docTypes as $type => $file) {
                if ($file) {
                    /**
                     * [AUDIT FIX]: Force storage to 'private' disk.
                     * This prevents the file from being accessible via a direct public URL.
                     */
                    $path = $this->fileUploader->upload($file, [
                        'path' => "kyc/{$user->id}",
                        'disk' => 'private', // <-- FORCE PRIVATE
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
            // Rollback status to pending on failure
            $this->statusService->transitionStatus($kyc, KycStatus::PENDING->value, "Upload failed: " . $e->getMessage());

            Log::error("KYC document upload failed", [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to upload documents.',
                'error' => $e->getMessage(), // Show actual error in response
                'debug' => config('app.debug') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 400);
        }

        // Dispatch background job for OCR/Verification
        ProcessKycJob::dispatch($kyc)->onQueue('high');

        return response()->json([
            'message' => 'KYC documents submitted successfully. Status: Processing.',
            'kyc' => $kyc->load('documents'),
        ], 201);
    }

    /**
     * NEW: Redirect user to DigiLocker (Logic unchanged but structured)
     */
    public function redirectToDigiLocker(Request $request)
    {
        $kyc = $request->user()->kyc;
        $redirectUrl = $this->verificationService->getDigiLockerRedirectUrl($kyc);
        return response()->json(['redirect_url' => $redirectUrl]);
    }

    /**
     * Handle callback from DigiLocker
     */
    public function handleDigiLockerCallback(Request $request)
    {
        $request->validate(['code' => 'required|string', 'state' => 'required|string']);
        try {
            $kyc = $this->verificationService->handleDigiLockerCallback($request->code, $request->state);
            return redirect(env('FRONTEND_URL') . '/kyc?status=digilocker_success');
        } catch (\Exception $e) {
            Log::error("DigiLocker Callback Failed: " . $e->getMessage());
            return redirect(env('FRONTEND_URL') . '/kyc?status=digilocker_failed');
        }
    }

    /**
     * Verify PAN number using third-party API or basic validation.
     */
    public function verifyPan(Request $request)
    {
        $request->validate([
            'pan_number' => 'required|string|size:10'
        ]);

        $user = $request->user();
        $panNumber = strtoupper($request->pan_number);

        try {
            $result = $this->verificationService->verifyPan(
                $panNumber,
                $user->profile->first_name . ' ' . $user->profile->last_name,
                $user->profile->dob ?? null
            );

            return response()->json([
                'success' => true,
                'verified' => $result['verified'] ?? false,
                'message' => $result['message'] ?? 'PAN verification completed',
                'details' => $result
            ]);
        } catch (\Exception $e) {
            Log::error("PAN Verification Failed", [
                'user_id' => $user->id,
                'pan' => $panNumber,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'verified' => false,
                'message' => 'PAN verification failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Verify Bank Account using Razorpay Fund Account Validation (Penny Drop).
     */
    public function verifyBank(Request $request)
    {
        $request->validate([
            'bank_account' => 'required|string',
            'bank_ifsc' => 'required|string|size:11'
        ]);

        $user = $request->user();

        try {
            $result = $this->verificationService->verifyBank(
                $request->bank_account,
                strtoupper($request->bank_ifsc),
                $user->profile->first_name . ' ' . $user->profile->last_name
            );

            return response()->json([
                'success' => true,
                'verified' => $result['verified'] ?? false,
                'message' => $result['message'] ?? 'Bank account verification completed',
                'details' => $result
            ]);
        } catch (\Exception $e) {
            Log::error("Bank Verification Failed", [
                'user_id' => $user->id,
                'account' => $request->bank_account,
                'ifsc' => $request->bank_ifsc,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'verified' => false,
                'message' => 'Bank verification failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * View a KYC document - Stream file directly
     *
     * [FIX]: Since 'private' disk uses local driver (not S3), temporaryUrl() doesn't work.
     * Instead, we stream the file directly with proper authorization checks.
     */
    public function viewDocument(Request $request, $id)
    {
        $doc = KycDocument::findOrFail($id);
        $user = $request->user();

        // Security: Ensure document belongs to user OR requester is Admin
        if ($user->id !== $doc->kyc->user_id && !$user->hasRole(['admin', 'super-admin'])) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        // Check if file exists on private disk
        if (!Storage::disk('private')->exists($doc->file_path)) {
            \Log::error("KYC file missing on private disk", ['document_id' => $id]);
            return response()->json(['message' => 'Document file not found.'], 404);
        }

        /**
         * Stream the file directly with proper headers
         * Files are encrypted during upload, must decrypt before serving
         */
        $encryptedContent = Storage::disk('private')->get($doc->file_path);

        // Decrypt the file content (files are encrypted with Laravel's encrypt())
        try {
            $file = decrypt($encryptedContent);
        } catch (\Exception $e) {
            \Log::error("Failed to decrypt KYC document", [
                'document_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => 'Failed to decrypt document.'], 500);
        }

        $mimeType = $doc->mime_type ?: 'application/octet-stream';

        return response($file, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline; filename="' . $doc->file_name . '"');
    }
}