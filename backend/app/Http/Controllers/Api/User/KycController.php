<?php
// V-FINAL-1730-296 (Created) | V-FINAL-1730-467 (FileUploadService Refactor)

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\KycSubmitRequest;
use App\Models\UserKyc;
use App\Models\KycDocument;
use App\Services\VerificationService;
use App\Services\FileUploadService; // <-- IMPORT
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class KycController extends Controller
{
    protected $fileUploader;
    
    public function __construct(FileUploadService $fileUploader)
    {
        $this->fileUploader = $fileUploader;
    }
    
    public function show(Request $request)
    {
        // ... (same as before)
        $kyc = $request->user()->kyc()->with('documents')->first();
        return response()->json($kyc);
    }

    public function store(KycSubmitRequest $request)
    {
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
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $kyc->documents()->delete(); // Clear old docs

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
                    // --- REFACTORED: Use the Service ---
                    $path = $this->fileUploader->upload($file, [
                        'path' => "kyc/{$user->id}",
                        'encrypt' => true,
                        'virus_scan' => true
                    ]);
                    // ---------------------------------
                    
                    KycDocument::create([
                        'user_kyc_id' => $kyc->id,
                        'doc_type' => $type,
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Fails if validation, virus scan, or encryption fails
            Log::error("KYC Upload Failed for User {$user->id}: " . $e->getMessage());
            $kyc->update(['status' => 'pending']); // Reset status
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json([
            'message' => 'KYC documents submitted successfully.',
            'kyc' => $kyc->load('documents'),
        ], 201);
    }

    public function viewDocument(Request $request, $id)
    {
        // ... (same as before) ...
    }
    
    public function verifyPan(Request $request, VerificationService $service) { /* ... same as before ... */ }
    public function verifyBank(Request $request, VerificationService $service) { /* ... same as before ... */ }
}