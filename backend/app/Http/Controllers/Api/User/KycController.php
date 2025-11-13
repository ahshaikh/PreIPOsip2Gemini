<?php
// V-PHASE1-1730-018

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\KycSubmitRequest;
use App\Models\UserKyc;
use App\Models\KycDocument;
use App\Services\VerificationService; // Added this
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KycController extends Controller
{
    /**
     * Get the authenticated user's KYC status and documents.
     */
    public function show(Request $request)
    {
        $kyc = $request->user()->kyc()->with('documents')->first();
        return response()->json($kyc);
    }

public function verifyPan(Request $request, VerificationService $service)
    {
        $request->validate([
            'pan_number' => 'required|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
            'full_name' => 'required|string',
        ]);

        $result = $service->verifyPan($request->pan_number, $request->full_name);

        if ($result['valid']) {
            // Auto-save if valid
            $request->user()->kyc()->update([
                'pan_number' => $request->pan_number,
                // We could add a 'pan_verified' flag column here in a future migration
            ]);
            return response()->json(['message' => 'PAN Verified', 'data' => $result]);
        }

        return response()->json(['message' => 'PAN Verification Failed', 'error' => $result['error'] ?? 'Unknown error'], 400);
    }

    public function verifyBank(Request $request, VerificationService $service)
    {
        $request->validate([
            'account_number' => 'required|string',
            'ifsc' => 'required|string',
            'full_name' => 'required|string',
        ]);

        $result = $service->verifyBank($request->account_number, $request->ifsc, $request->full_name);

        if ($result['valid']) {
            $request->user()->kyc()->update([
                'bank_account' => $request->account_number,
                'bank_ifsc' => $request->ifsc,
            ]);
            return response()->json(['message' => 'Bank Account Verified', 'data' => $result]);
        }

        return response()->json(['message' => 'Bank Verification Failed', 'error' => $result['error']], 400);
    }


    /**
     * Submit KYC documents.
     */
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

        // Clear old documents if resubmitting
        $kyc->documents()->delete();

        $docTypes = [
            'aadhaar_front' => $request->file('aadhaar_front'),
            'aadhaar_back' => $request->file('aadhaar_back'),
            'pan' => $request->file('pan'),
            'bank_proof' => $request->file('bank_proof'),
            'demat_proof' => $request->file('demat_proof'),
        ];

        foreach ($docTypes as $type => $file) {
            if ($file) {
                // Store file securely
                $path = $file->store("kyc/{$user->id}", 'local'); // Use 's3' in production
                
                KycDocument::create([
                    'user_kyc_id' => $kyc->id,
                    'doc_type' => $type,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                ]);
            }
        }

        // TODO: Dispatch a job to notify admins
        // dispatch(new NotifyAdminKycSubmittedJob($kyc));

        return response()->json([
            'message' => 'KYC documents submitted successfully for verification.',
            'kyc' => $kyc->load('documents'),
        ], 201);
    }
}