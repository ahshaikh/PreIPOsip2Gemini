<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OfferController extends Controller
{
    /**
     * Get all active offers
     */
    public function index(): JsonResponse
    {
        $offers = Offer::active()
            ->orderBy('is_featured', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($offers);
    }

    /**
     * Get a specific offer by ID
     */
    public function show($id): JsonResponse
    {
        $offer = Offer::find($id);

        if (!$offer) {
            return response()->json([
                'message' => 'Offer not found',
            ], 404);
        }

        return response()->json([
            'data' => $offer,
        ]);
    }

    /**
     * Validate an offer code
     */
    public function validateCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'investment_amount' => 'nullable|numeric|min:0',
        ]);

        $offer = Offer::where('code', $request->code)->first();

        if (!$offer) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid offer code',
            ], 404);
        }

        if (!$offer->isValid()) {
            return response()->json([
                'valid' => false,
                'message' => 'This offer has expired or is no longer available',
            ], 400);
        }

        // Check minimum investment requirement
        if ($request->has('investment_amount') && $offer->min_investment) {
            if ($request->investment_amount < $offer->min_investment) {
                return response()->json([
                    'valid' => false,
                    'message' => "Minimum investment of ₹{$offer->min_investment} required for this offer",
                ], 400);
            }
        }

        // Calculate discount
        $discount = 0;
        if ($request->has('investment_amount')) {
            if ($offer->discount_type === 'percentage') {
                $discount = ($request->investment_amount * $offer->discount_percent) / 100;
                if ($offer->max_discount && $discount > $offer->max_discount) {
                    $discount = $offer->max_discount;
                }
            } else {
                $discount = $offer->discount_amount;
            }
        }

        return response()->json([
            'valid' => true,
            'message' => 'Offer code is valid',
            'offer' => $offer,
            'discount' => $discount,
        ]);
    }
}
