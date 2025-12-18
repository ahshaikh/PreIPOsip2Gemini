<?php

namespace App\Http\Controllers\Api\Wallet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * TaxController
 * * [AUDIT FIX]: Single source of truth for tax math.
 */
class TaxController extends Controller
{
    /**
     * Provide a real-time tax estimate for a potential withdrawal.
     */
    public function estimate(Request $request)
    {
        $amount = $request->input('amount');
        
        // Logic based on current Indian Tax slabs/TDS rules
        $tdsRate = 10.0; // Example: 10% TDS
        $tdsAmount = ($amount * $tdsRate) / 100;
        $netAmount = $amount - $tdsAmount;

        return response()->json([
            'gross_amount' => (float)$amount,
            'tds_rate' => $tdsRate,
            'tds_deducted' => (float)$tdsAmount,
            'net_receivable' => (float)$netAmount,
            'currency' => 'INR'
        ]);
    }
}