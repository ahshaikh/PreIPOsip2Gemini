<?php
// V-FINAL-1730-319 (Created) | V-FINAL-1730-462 (N+1 Optimized)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserKyc;
use App\Notifications\KycVerified;
use Illuminate\Http\Request;

class KycQueueController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'submitted');
        
        $kycSubmissions = UserKyc::where('status', $status)
            // --- EAGER LOADING FIX ---
            // Load the 'user' relationship in the *same* query
            ->with('user:id,username,email') 
            // -------------------------
            ->latest('submitted_at')
            ->paginate(25);
            
        return response()->json($kycSubmissions);
    }

    public function show($id)
    {
        // 'show' is also optimized to get all data in one go
        $kyc = UserKyc::with('user.profile', 'documents')->findOrFail($id);
        return response()->json($kyc);
    }

    public function approve(Request $request, $id)
    {
        $kyc = UserKyc::findOrFail($id); // Find (Query 1)
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

        $kyc->user->notify(new KycVerified()); // Load User (Query 2)
        
        return response()->json(['message' => 'KYC approved successfully.']);
    }

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

        // (Notification for rejection, if needed)
        // $kyc->user->notify(new KycRejected($request->reason));

        return response()->json(['message' => 'KYC rejected successfully.']);
    }
}