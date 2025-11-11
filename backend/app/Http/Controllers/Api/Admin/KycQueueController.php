// V-PHASE2-1730-054
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserKyc;
use Illuminate\Http\Request;

class KycQueueController extends Controller
{
    /**
     * Get the list of pending KYC submissions.
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'submitted');
        
        $kycSubmissions = UserKyc::where('status', $status)
            ->with('user:id,username,email')
            ->latest('submitted_at')
            ->paginate(25);
            
        return response()->json($kycSubmissions);
    }

    /**
     * Get full details for a single KYC submission.
     */
    public function show($id)
    {
        $kyc = UserKyc::with('user.profile', 'documents')->findOrFail($id);
        return response()->json($kyc);
    }

    /**
     * Approve a KYC submission.
     */
    public function approve(Request $request, $id)
    {
        $kyc = UserKyc::findOrFail($id);
        $admin = $request->user();

        if ($kyc->status !== 'submitted') {
            return response()->json(['message' => 'This submission is not pending approval.'], 400);
        }

        $kyc->update([
            'status' => 'verified',
            'verified_at' => now(),
            'verified_by' => $admin->id,
            'rejection_reason' => null,
        ]);

        // TODO: Log this admin action
        // TODO: Dispatch job to send "KYC Approved" email

        return response()->json(['message' => 'KYC approved successfully.']);
    }

    /**
     * Reject a KYC submission.
     */
    public function reject(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string|min:10|max:500']);
        
        $kyc = UserKyc::findOrFail($id);
        $admin = $request->user();

        if ($kyc->status !== 'submitted') {
            return response()->json(['message' => 'This submission is not pending approval.'], 400);
        }

        $kyc->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
            'verified_at' => null,
            'verified_by' => $admin->id,
        ]);

        // TODO: Log this admin action
        // TODO: Dispatch job to send "KYC Rejected" email with reason

        return response()->json(['message' => 'KYC rejected successfully.']);
    }
}