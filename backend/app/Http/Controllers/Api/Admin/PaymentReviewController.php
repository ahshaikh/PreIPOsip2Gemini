<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Support\Facades\Storage;

class PaymentReviewController extends Controller
{
    /**
     * [AUDIT FIX]: Generate temporary signed URLs for private manual proofs.
     */
    public function showProof(Payment $payment)
    {
        $path = $payment->manual_proof_path; // Stored in storage/app/private/payments

        if (!$path || !Storage::disk('private')->exists($path)) {
            return response()->json(['message' => 'Proof not found'], 404);
        }

        $url = Storage::disk('private')->temporaryUrl($path, now()->addMinutes(10));

        return response()->json(['url' => $url]);
    }
}