<?php
// V-FINAL-1730-206 (Created) | V-FINAL-1730-415 (Inventory Report)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Product; // <-- IMPORT
use App\Models\BonusTransaction;
use App\Models\BulkPurchase;
use App\Services\ReportService;
use App\Services\InventoryService; // <-- IMPORT
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DynamicTableExport;
use Barryvdh\DomPDF\Facade\Pdf;


class ReportController extends Controller
{
    // ... (All other methods: getFinancialSummary, exportComplianceReport, etc.) ...
    // ... (Make sure constructor includes InventoryService) ...
    
    protected $reportService;
    protected $inventoryService;

    public function __construct(ReportService $reportService, InventoryService $inventoryService)
    {
        $this->reportService = $reportService;
        $this->inventoryService = $inventoryService;
    }

    /**
     * FSD-BULK-007: Get the Inventory Dashboard stats.
     */
    public function getInventorySummary(Request $request)
    {
        $products = Product::where('status', 'active')->get();
        
        $summary = $products->map(function ($product) {
            $stats = $this->inventoryService->getProductInventoryStats($product);
            $suggestion = $this->inventoryService->getReorderSuggestion($product);

            return [
                'id' => $product->id,
                'name' => $product->name,
                'total_inventory' => $stats->total,
                'available_inventory' => $stats->available,
                'sold_percentage' => $stats->sold_percentage,
                'is_low_stock' => $this->inventoryService->checkLowStock($product),
                'forecast' => $suggestion
            ];
        });

        return response()->json($summary);
    }
}