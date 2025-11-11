// V-PHASE2-1730-057
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BulkPurchase;
use Illuminate\Http\Request;

class BulkPurchaseController extends Controller
{
    public function index(Request $request)
    {
        return BulkPurchase::with('product:id,name')->latest()->paginate(25);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'face_value_purchased' => 'required|numeric|min:0',
            'actual_cost_paid' => 'required|numeric|min:0',
            'extra_allocation_percentage' => 'required|numeric|min:0',
            'seller_name' => 'nullable|string|max:255',
            'purchase_date' => 'required|date',
        ]);

        $discount = ($validated['face_value_purchased'] - $validated['actual_cost_paid']) / $validated['face_value_purchased'];
        $totalValue = $validated['face_value_purchased'] * (1 + ($validated['extra_allocation_percentage'] / 100));

        $purchase = BulkPurchase::create($validated + [
            'admin_id' => $request->user()->id,
            'discount_percentage' => $discount * 100,
            'total_value_received' => $totalValue,
            'value_remaining' => $totalValue,
        ]);

        return response()->json($purchase, 201);
    }

    public function show(BulkPurchase $bulkPurchase)
    {
        return $bulkPurchase->load('product');
    }
}