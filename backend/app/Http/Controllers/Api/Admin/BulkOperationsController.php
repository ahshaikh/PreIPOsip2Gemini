<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BulkImportJob;
use App\Models\DataExportJob;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BulkOperationsController extends Controller
{
    /**
     * Bulk update users
     * POST /api/v1/admin/bulk/users/update
     */
    public function bulkUpdateUsers(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array|max:1000',
            'user_ids.*' => 'required|exists:users,id',
            'updates' => 'required|array',
            'updates.status' => 'sometimes|in:active,suspended,blocked',
            'updates.email_verified' => 'sometimes|boolean',
            'updates.mobile_verified' => 'sometimes|boolean',
        ]);

        $count = 0;

        DB::transaction(function () use ($validated, &$count) {
            $updates = [];

            if (isset($validated['updates']['status'])) {
                $updates['status'] = $validated['updates']['status'];
            }

            if (isset($validated['updates']['email_verified'])) {
                $updates['email_verified_at'] = $validated['updates']['email_verified'] ? now() : null;
            }

            if (isset($validated['updates']['mobile_verified'])) {
                $updates['mobile_verified_at'] = $validated['updates']['mobile_verified'] ? now() : null;
            }

            $count = User::whereIn('id', $validated['user_ids'])->update($updates);
        });

        return response()->json([
            'message' => "Updated {$count} users successfully",
            'updated_count' => $count,
        ]);
    }

    /**
     * Bulk import investments from CSV
     * POST /api/v1/admin/bulk/investments/import
     */
    public function bulkImportInvestments(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240', // Max 10MB
        ]);

        $file = $request->file('file');
        $filename = 'imports/' . Str::random(20) . '.csv';
        Storage::put($filename, file_get_contents($file->getPathname()));

        // Create import job
        $job = BulkImportJob::create([
            'type' => 'investments',
            'filename' => $filename,
            'status' => 'processing',
            'created_by' => $request->user()->id,
            'started_at' => now(),
        ]);

        // Process file
        $handle = fopen($file->getPathname(), 'r');
        $header = fgetcsv($handle);

        // Expected headers: user_id, plan_id, amount, start_date
        $expectedHeaders = ['user_id', 'plan_id', 'amount', 'start_date'];
        $normalizedHeaders = array_map(fn($h) => strtolower(trim($h)), $header);

        foreach ($expectedHeaders as $required) {
            if (!in_array($required, $normalizedHeaders)) {
                fclose($handle);
                $job->update([
                    'status' => 'failed',
                    'errors' => ["Missing required column: {$required}"],
                    'completed_at' => now(),
                ]);

                return response()->json([
                    'error' => "Missing required column: {$required}",
                    'expected_headers' => $expectedHeaders,
                ], 422);
            }
        }

        $columnMap = [
            'user_id' => array_search('user_id', $normalizedHeaders),
            'plan_id' => array_search('plan_id', $normalizedHeaders),
            'amount' => array_search('amount', $normalizedHeaders),
            'start_date' => array_search('start_date', $normalizedHeaders),
        ];

        $imported = 0;
        $failed = 0;
        $errors = [];
        $totalRows = 0;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $totalRows++;

                if (empty(array_filter($row))) continue;

                $userId = trim($row[$columnMap['user_id']] ?? '');
                $planId = trim($row[$columnMap['plan_id']] ?? '');
                $amount = trim($row[$columnMap['amount']] ?? '');
                $startDate = trim($row[$columnMap['start_date']] ?? '');

                // Validate
                if (!$userId || !$planId || !$amount || !$startDate) {
                    $errors[] = "Row {$totalRows}: Missing required fields";
                    $failed++;
                    continue;
                }

                $user = User::find($userId);
                if (!$user) {
                    $errors[] = "Row {$totalRows}: User not found";
                    $failed++;
                    continue;
                }

                // Check if subscription already exists
                if ($user->subscription()->exists()) {
                    $errors[] = "Row {$totalRows}: User already has a subscription";
                    $failed++;
                    continue;
                }

                // Create subscription
                Subscription::create([
                    'user_id' => $userId,
                    'plan_id' => $planId,
                    'amount' => $amount,
                    'monthly_amount' => $amount,
                    'start_date' => $startDate,
                    'status' => 'active',
                    'payment_frequency' => 'monthly',
                    'total_months' => 12,
                ]);

                $imported++;
            }

            DB::commit();
            fclose($handle);

            $job->update([
                'status' => 'completed',
                'total_rows' => $totalRows,
                'processed_rows' => $totalRows,
                'successful_rows' => $imported,
                'failed_rows' => $failed,
                'errors' => array_slice($errors, 0, 100),
                'completed_at' => now(),
            ]);

            return response()->json([
                'message' => "Import completed. {$imported} investments created, {$failed} failed",
                'job' => $job,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);

            $job->update([
                'status' => 'failed',
                'errors' => [$e->getMessage()],
                'completed_at' => now(),
            ]);

            return response()->json([
                'error' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get import job history
     * GET /api/v1/admin/bulk/imports
     */
    public function getImportHistory(Request $request)
    {
        $query = BulkImportJob::with('creator:id,username')
            ->orderBy('created_at', 'desc');

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $imports = $query->paginate(50);

        return response()->json($imports);
    }

    /**
     * Data export wizard - get available types
     * GET /api/v1/admin/export/types
     */
    public function getExportTypes()
    {
        $types = [
            [
                'type' => 'users',
                'name' => 'Users',
                'description' => 'Export user data with profiles and KYC',
                'available_columns' => [
                    'id', 'username', 'email', 'mobile', 'status',
                    'email_verified_at', 'mobile_verified_at',
                    'first_name', 'last_name', 'city', 'state',
                    'kyc_status', 'wallet_balance', 'created_at'
                ],
                'available_formats' => ['csv', 'xlsx', 'json'],
            ],
            [
                'type' => 'payments',
                'name' => 'Payments',
                'description' => 'Export payment transactions',
                'available_columns' => [
                    'id', 'user_id', 'username', 'amount', 'status',
                    'gateway', 'method', 'paid_at', 'created_at'
                ],
                'available_formats' => ['csv', 'xlsx', 'json', 'pdf'],
            ],
            [
                'type' => 'subscriptions',
                'name' => 'Subscriptions',
                'description' => 'Export investment subscriptions',
                'available_columns' => [
                    'id', 'user_id', 'username', 'plan_name', 'amount',
                    'status', 'start_date', 'payment_frequency', 'created_at'
                ],
                'available_formats' => ['csv', 'xlsx', 'json'],
            ],
            [
                'type' => 'withdrawals',
                'name' => 'Withdrawals',
                'description' => 'Export withdrawal requests',
                'available_columns' => [
                    'id', 'user_id', 'username', 'amount', 'fee',
                    'net_amount', 'status', 'utr_number', 'created_at'
                ],
                'available_formats' => ['csv', 'xlsx', 'json'],
            ],
            [
                'type' => 'transactions',
                'name' => 'Transactions',
                'description' => 'Export wallet transactions',
                'available_columns' => [
                    'id', 'user_id', 'username', 'type', 'amount',
                    'balance_before', 'balance_after', 'description', 'created_at'
                ],
                'available_formats' => ['csv', 'xlsx', 'json'],
            ],
        ];

        return response()->json(['types' => $types]);
    }

    /**
     * Create export job
     * POST /api/v1/admin/export
     */
    public function createExport(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:users,payments,subscriptions,withdrawals,transactions',
            'format' => 'required|in:csv,xlsx,json,pdf',
            'columns' => 'nullable|array',
            'filters' => 'nullable|array',
            'filters.date_from' => 'nullable|date',
            'filters.date_to' => 'nullable|date',
            'filters.status' => 'nullable|array',
        ]);

        $job = DataExportJob::create([
            'type' => $validated['type'],
            'format' => $validated['format'],
            'columns' => $validated['columns'] ?? null,
            'filters' => $validated['filters'] ?? null,
            'status' => 'pending',
            'created_by' => $request->user()->id,
            'expires_at' => now()->addDays(7), // Auto-delete after 7 days
        ]);

        // Process export asynchronously
        $this->processExport($job);

        return response()->json([
            'message' => 'Export job created',
            'job' => $job,
        ], 201);
    }

    /**
     * Process export job
     */
    private function processExport(DataExportJob $job)
    {
        try {
            $job->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            $data = $this->fetchExportData($job);
            $filePath = $this->generateExportFile($job, $data);
            $fileSize = Storage::size($filePath);

            $job->update([
                'status' => 'completed',
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'record_count' => count($data),
                'completed_at' => now(),
            ]);
        } catch (\Exception $e) {
            $job->update([
                'status' => 'failed',
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * Fetch data for export
     */
    private function fetchExportData(DataExportJob $job)
    {
        $query = match ($job->type) {
            'users' => User::with('profile', 'kyc', 'wallet'),
            'payments' => Payment::with('user:id,username'),
            'subscriptions' => Subscription::with('user:id,username', 'plan:id,name'),
            'withdrawals' => Withdrawal::with('user:id,username'),
            'transactions' => Transaction::with('user:id,username'),
            default => throw new \Exception('Invalid export type'),
        };

        // Apply filters
        if ($job->filters) {
            if (isset($job->filters['date_from'])) {
                $query->whereDate('created_at', '>=', $job->filters['date_from']);
            }
            if (isset($job->filters['date_to'])) {
                $query->whereDate('created_at', '<=', $job->filters['date_to']);
            }
            if (isset($job->filters['status'])) {
                $query->whereIn('status', $job->filters['status']);
            }
        }

        return $query->limit(10000)->get()->toArray();
    }

    /**
     * Generate export file
     */
    private function generateExportFile(DataExportJob $job, array $data)
    {
        $filename = "exports/{$job->type}_{$job->id}.{$job->format}";

        if ($job->format === 'csv') {
            $csv = $this->arrayToCsv($data, $job->columns);
            Storage::put($filename, $csv);
        } elseif ($job->format === 'json') {
            Storage::put($filename, json_encode($data, JSON_PRETTY_PRINT));
        }
        // For xlsx and pdf, you would need additional libraries

        return $filename;
    }

    /**
     * Convert array to CSV
     */
    private function arrayToCsv(array $data, ?array $columns = null)
    {
        if (empty($data)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');

        // Write header
        $headers = $columns ?? array_keys($data[0]);
        fputcsv($output, $headers);

        // Write data
        foreach ($data as $row) {
            $filtered = [];
            foreach ($headers as $header) {
                $filtered[] = $row[$header] ?? '';
            }
            fputcsv($output, $filtered);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Get export job history
     * GET /api/v1/admin/export/history
     */
    public function getExportHistory(Request $request)
    {
        $query = DataExportJob::where('created_by', $request->user()->id)
            ->orderBy('created_at', 'desc');

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $exports = $query->paginate(50);

        return response()->json($exports);
    }

    /**
     * Download export file
     * GET /api/v1/admin/export/{job}/download
     */
    public function downloadExport(DataExportJob $job)
    {
        if ($job->status !== 'completed' || !$job->file_path) {
            return response()->json([
                'error' => 'Export file not available',
            ], 404);
        }

        if (!Storage::exists($job->file_path)) {
            return response()->json([
                'error' => 'Export file has been deleted',
            ], 404);
        }

        return Storage::download($job->file_path);
    }

    /**
     * Delete export file
     * DELETE /api/v1/admin/export/{job}
     */
    public function deleteExport(DataExportJob $job)
    {
        if ($job->file_path && Storage::exists($job->file_path)) {
            Storage::delete($job->file_path);
        }

        $job->delete();

        return response()->json([
            'message' => 'Export deleted successfully',
        ]);
    }
}
