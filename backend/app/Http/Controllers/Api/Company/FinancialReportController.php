<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyFinancialReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class FinancialReportController extends Controller
{
    /**
     * Get all financial reports for the company
     */
    public function index(Request $request)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        $query = CompanyFinancialReport::where('company_id', $company->id)
            ->with('uploadedBy:id,contact_person_name')
            ->orderBy('year', 'desc')
            ->orderBy('quarter', 'desc');

        if ($request->filled('year')) {
            $query->forYear($request->year);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $reports = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $reports->items(),
            'pagination' => [
                'total' => $reports->total(),
                'per_page' => $reports->perPage(),
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
            ],
        ], 200);
    }

    /**
     * Upload and create a new financial report
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'year' => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'quarter' => 'required|in:Q1,Q2,Q3,Q4,Annual',
            'report_type' => 'required|in:financial_statement,balance_sheet,cash_flow,income_statement,annual_report,other',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'required|file|mimes:pdf|max:10240', // 10MB max
            'status' => 'sometimes|in:draft,published,archived',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $companyUser = $request->user();
        $company = $companyUser->company;

        try {
            // Store the file
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $path = $file->store('financial-reports/' . $company->id, 'public');
            $fileSize = $file->getSize();

            // Create the report record
            $report = CompanyFinancialReport::create([
                'company_id' => $company->id,
                'uploaded_by' => $companyUser->id,
                'year' => $request->year,
                'quarter' => $request->quarter,
                'report_type' => $request->report_type,
                'title' => $request->title,
                'description' => $request->description,
                'file_path' => $path,
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'status' => $request->status ?? 'draft',
                'published_at' => $request->status === 'published' ? now() : null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Financial report uploaded successfully',
                'report' => $report,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload financial report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific financial report
     */
    public function show(Request $request, $id)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        $report = CompanyFinancialReport::where('company_id', $company->id)
            ->where('id', $id)
            ->with('uploadedBy:id,contact_person_name')
            ->first();

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Financial report not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'report' => $report,
            'download_url' => Storage::url($report->file_path),
        ], 200);
    }

    /**
     * Update financial report details
     */
    public function update(Request $request, $id)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        $report = CompanyFinancialReport::where('company_id', $company->id)
            ->where('id', $id)
            ->first();

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Financial report not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'year' => 'sometimes|integer|min:2000|max:' . (date('Y') + 1),
            'quarter' => 'sometimes|in:Q1,Q2,Q3,Q4,Annual',
            'report_type' => 'sometimes|in:financial_statement,balance_sheet,cash_flow,income_statement,annual_report,other',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:draft,published,archived',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->only(['year', 'quarter', 'report_type', 'title', 'description', 'status']);

        // If status changed to published, set published_at
        if (isset($data['status']) && $data['status'] === 'published' && $report->status !== 'published') {
            $data['published_at'] = now();
        }

        $report->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Financial report updated successfully',
            'report' => $report->fresh(),
        ], 200);
    }

    /**
     * Delete a financial report
     */
    public function destroy(Request $request, $id)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        $report = CompanyFinancialReport::where('company_id', $company->id)
            ->where('id', $id)
            ->first();

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Financial report not found',
            ], 404);
        }

        try {
            // Delete the file from storage
            if (Storage::disk('public')->exists($report->file_path)) {
                Storage::disk('public')->delete($report->file_path);
            }

            $report->delete();

            return response()->json([
                'success' => true,
                'message' => 'Financial report deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete financial report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download a financial report
     */
    public function download(Request $request, $id)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        $report = CompanyFinancialReport::where('company_id', $company->id)
            ->where('id', $id)
            ->first();

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Financial report not found',
            ], 404);
        }

        if (!Storage::disk('public')->exists($report->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
            ], 404);
        }

        return Storage::disk('public')->download($report->file_path, $report->file_name);
    }
}
