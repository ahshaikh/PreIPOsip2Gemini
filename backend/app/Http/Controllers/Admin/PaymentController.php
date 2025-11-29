<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        // ---------------------------------------------------------
        // TRAP 1: SYSTEM HEALTH CHECK
        // If this runs, your Route and Middleware are FINE.
        // ---------------------------------------------------------
        
        // Check 1: Can we load the Payment Model?
        if (!class_exists(Payment::class)) {
            dd("CRITICAL ERROR: App\Models\Payment class not found. Check filename/namespace.");
        }

        // Check 2: Can we connect to DB?
        try {
            $count = Payment::count();
        } catch (\Throwable $e) {
            dd("DB CONNECTION ERROR: " . $e->getMessage());
        }

        // Check 3: Can we fetch the first record?
        $first = Payment::first();
        if (!$first) {
            // Table is empty, return empty set safely
            return response()->json(['data' => [], 'meta' => ['total' => 0]]);
        }

        // Check 4: Check Relationships (The likely crash suspect)
        try {
            // Attempt to load relationships manually to see which one breaks
            $user = $first->user; 
            $sub = $first->subscription;
        } catch (\Throwable $e) {
            dd("RELATIONSHIP ERROR: " . $e->getMessage());
        }

        // ---------------------------------------------------------
        // END TRAP. If you see JSON below, the logic is safe.
        // ---------------------------------------------------------

        try {
            $query = Payment::with(['user', 'subscription.plan']);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('gateway_payment_id', 'like', "%{$search}%");
                    // We removed the complex orWhereHas temporarily to isolate the crash
                });
            }

            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Standard Pagination
            $payments = $query->latest()->paginate(10);

            $data = $payments->through(function ($payment) {
                // Use Raw Values first to ensure no formatting crash
                return [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'status' => $payment->status,
                    'paid_at_raw' => $payment->paid_at, // Debug raw value
                    'user_id' => $payment->user_id,     // Debug raw ID
                ];
            });

            return response()->json($data);

        } catch (\Throwable $e) {
            dd("CONTROLLER LOGIC ERROR: " . $e->getMessage(), $e->getTraceAsString());
        }
    }

    public function show($id)
    {
        return response()->json(['message' => 'Not implemented yet']);
    }
}