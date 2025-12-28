<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Transaction;
use App\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * PaymentReconciliationService - External Payment Reconciliation
 *
 * [E.18]: Reconciliation for external payment failures
 *
 * PROTOCOL:
 * - Detect webhook misses (payments captured but not recorded)
 * - Handle webhook retries (idempotency via payment_gateway_id)
 * - Detect partial captures (authorized but not fully captured)
 * - Reconcile Razorpay records with database
 *
 * RECONCILIATION TYPES:
 * 1. Missing webhooks: Razorpay says "captured", we say "pending"
 * 2. Double webhooks: Same payment_gateway_id processed twice
 * 3. Partial captures: Amount mismatch between gateway and DB
 * 4. Orphaned payments: DB has payment, Razorpay doesn't
 * 5. Status mismatches: Different status in gateway vs DB
 */
class PaymentReconciliationService
{
    private PaymentGatewayInterface $gateway;

    public function __construct(PaymentGatewayInterface $gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * Reconcile payments for a specific date range
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array Reconciliation report
     */
    public function reconcilePayments(Carbon $startDate, Carbon $endDate): array
    {
        Log::info("PAYMENT RECONCILIATION STARTED", [
            'start_date' => $startDate->toDateTimeString(),
            'end_date' => $endDate->toDateTimeString(),
        ]);

        $startTime = microtime(true);

        // Get payments from database
        $dbPayments = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        // Fetch payments from gateway
        $gatewayPayments = $this->fetchGatewayPayments($startDate, $endDate);

        // Compare and identify discrepancies
        $missingWebhooks = $this->detectMissingWebhooks($dbPayments, $gatewayPayments);
        $statusMismatches = $this->detectStatusMismatches($dbPayments, $gatewayPayments);
        $amountMismatches = $this->detectAmountMismatches($dbPayments, $gatewayPayments);
        $orphanedPayments = $this->detectOrphanedPayments($dbPayments, $gatewayPayments);

        $endTime = microtime(true);

        $result = [
            'started_at' => now()->toDateTimeString(),
            'date_range' => [
                'start' => $startDate->toDateTimeString(),
                'end' => $endDate->toDateTimeString(),
            ],
            'total_db_payments' => $dbPayments->count(),
            'total_gateway_payments' => count($gatewayPayments),
            'missing_webhooks' => $missingWebhooks,
            'status_mismatches' => $statusMismatches,
            'amount_mismatches' => $amountMismatches,
            'orphaned_payments' => $orphanedPayments,
            'execution_time_seconds' => round($endTime - $startTime, 2),
            'completed_at' => now()->toDateTimeString(),
        ];

        // Overall status
        $totalIssues = count($missingWebhooks) + count($statusMismatches) +
                      count($amountMismatches) + count($orphanedPayments);

        $result['overall_status'] = $totalIssues === 0 ? 'ALL_RECONCILED' : 'DISCREPANCIES_FOUND';
        $result['total_discrepancies'] = $totalIssues;

        if ($totalIssues > 0) {
            Log::warning("PAYMENT RECONCILIATION: DISCREPANCIES FOUND", [
                'total_discrepancies' => $totalIssues,
                'missing_webhooks' => count($missingWebhooks),
                'status_mismatches' => count($statusMismatches),
                'amount_mismatches' => count($amountMismatches),
                'orphaned_payments' => count($orphanedPayments),
            ]);
        } else {
            Log::info("PAYMENT RECONCILIATION: ALL RECONCILED", $result);
        }

        return $result;
    }

    /**
     * Auto-fix missing webhooks
     *
     * Processes payments that were captured in gateway but not in our system
     *
     * @param array $missingWebhooks
     * @return array ['processed' => int, 'failed' => int, 'details' => array]
     */
    public function autoFixMissingWebhooks(array $missingWebhooks): array
    {
        $processed = 0;
        $failed = 0;
        $details = [];

        foreach ($missingWebhooks as $discrepancy) {
            try {
                DB::transaction(function () use ($discrepancy) {
                    $payment = Payment::where('payment_gateway_id', $discrepancy['payment_gateway_id'])
                        ->first();

                    if (!$payment) {
                        throw new \RuntimeException("Payment not found in database");
                    }

                    // Update payment status
                    $payment->update([
                        'status' => 'completed',
                        'payment_status' => 'captured',
                    ]);

                    // Create wallet transaction (if not already exists)
                    $existingTransaction = Transaction::where('reference_type', 'payment')
                        ->where('reference_id', $payment->id)
                        ->first();

                    if (!$existingTransaction) {
                        $wallet = $payment->user->wallet;
                        $currentBalance = $wallet->balance_paise;
                        $newBalance = $currentBalance + ($payment->amount * 100);

                        Transaction::create([
                            'wallet_id' => $wallet->id,
                            'user_id' => $payment->user_id,
                            'type' => 'deposit',
                            'status' => 'completed',
                            'amount_paise' => $payment->amount * 100,
                            'balance_before_paise' => $currentBalance,
                            'balance_after_paise' => $newBalance,
                            'description' => "Payment #{ $payment->id} (Reconciliation auto-fix)",
                            'reference_type' => 'payment',
                            'reference_id' => $payment->id,
                        ]);

                        $wallet->update(['balance_paise' => $newBalance]);
                    }
                });

                $processed++;
                $details[] = [
                    'payment_gateway_id' => $discrepancy['payment_gateway_id'],
                    'status' => 'FIXED',
                ];

                Log::info("MISSING WEBHOOK AUTO-FIXED", [
                    'payment_gateway_id' => $discrepancy['payment_gateway_id'],
                ]);

            } catch (\Throwable $e) {
                $failed++;
                $details[] = [
                    'payment_gateway_id' => $discrepancy['payment_gateway_id'],
                    'status' => 'FAILED',
                    'error' => $e->getMessage(),
                ];

                Log::error("MISSING WEBHOOK AUTO-FIX FAILED", [
                    'payment_gateway_id' => $discrepancy['payment_gateway_id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'processed' => $processed,
            'failed' => $failed,
            'details' => $details,
        ];
    }

    /**
     * Detect missing webhooks (gateway says captured, DB says pending)
     *
     * @param \Illuminate\Support\Collection $dbPayments
     * @param array $gatewayPayments
     * @return array
     */
    private function detectMissingWebhooks($dbPayments, array $gatewayPayments): array
    {
        $missing = [];

        foreach ($gatewayPayments as $gatewayPayment) {
            // Find corresponding DB payment
            $dbPayment = $dbPayments->firstWhere('payment_gateway_id', $gatewayPayment['id']);

            if (!$dbPayment) {
                // Payment exists in gateway but not in DB (very rare)
                $missing[] = [
                    'payment_gateway_id' => $gatewayPayment['id'],
                    'issue' => 'Payment exists in gateway but not in database',
                    'gateway_status' => $gatewayPayment['status'],
                    'gateway_amount' => $gatewayPayment['amount'] / 100,
                ];
                continue;
            }

            // Gateway says captured, DB says pending → webhook missed
            if ($gatewayPayment['status'] === 'captured' &&
                in_array($dbPayment->status, ['pending', 'processing'])) {
                $missing[] = [
                    'payment_id' => $dbPayment->id,
                    'payment_gateway_id' => $gatewayPayment['id'],
                    'issue' => 'Webhook missed - gateway captured, DB pending',
                    'gateway_status' => $gatewayPayment['status'],
                    'db_status' => $dbPayment->status,
                    'amount' => $gatewayPayment['amount'] / 100,
                ];
            }
        }

        return $missing;
    }

    /**
     * Detect status mismatches between gateway and DB
     *
     * @param \Illuminate\Support\Collection $dbPayments
     * @param array $gatewayPayments
     * @return array
     */
    private function detectStatusMismatches($dbPayments, array $gatewayPayments): array
    {
        $mismatches = [];

        foreach ($gatewayPayments as $gatewayPayment) {
            $dbPayment = $dbPayments->firstWhere('payment_gateway_id', $gatewayPayment['id']);

            if (!$dbPayment) {
                continue; // Already handled in missing webhooks
            }

            // Compare statuses
            $gatewayStatus = $this->normalizeGatewayStatus($gatewayPayment['status']);
            $dbStatus = $this->normalizeDbStatus($dbPayment->status);

            if ($gatewayStatus !== $dbStatus) {
                $mismatches[] = [
                    'payment_id' => $dbPayment->id,
                    'payment_gateway_id' => $gatewayPayment['id'],
                    'issue' => 'Status mismatch',
                    'gateway_status' => $gatewayPayment['status'],
                    'db_status' => $dbPayment->status,
                    'amount' => $gatewayPayment['amount'] / 100,
                ];
            }
        }

        return $mismatches;
    }

    /**
     * Detect amount mismatches (partial captures)
     *
     * @param \Illuminate\Support\Collection $dbPayments
     * @param array $gatewayPayments
     * @return array
     */
    private function detectAmountMismatches($dbPayments, array $gatewayPayments): array
    {
        $mismatches = [];

        foreach ($gatewayPayments as $gatewayPayment) {
            $dbPayment = $dbPayments->firstWhere('payment_gateway_id', $gatewayPayment['id']);

            if (!$dbPayment) {
                continue;
            }

            $gatewayAmount = $gatewayPayment['amount'] / 100; // Razorpay uses paise
            $dbAmount = $dbPayment->amount;

            if (abs($gatewayAmount - $dbAmount) > 0.01) { // Allow 1 paisa rounding error
                $mismatches[] = [
                    'payment_id' => $dbPayment->id,
                    'payment_gateway_id' => $gatewayPayment['id'],
                    'issue' => 'Amount mismatch',
                    'gateway_amount' => $gatewayAmount,
                    'db_amount' => $dbAmount,
                    'difference' => abs($gatewayAmount - $dbAmount),
                ];
            }
        }

        return $mismatches;
    }

    /**
     * Detect orphaned payments (exist in DB but not in gateway)
     *
     * @param \Illuminate\Support\Collection $dbPayments
     * @param array $gatewayPayments
     * @return array
     */
    private function detectOrphanedPayments($dbPayments, array $gatewayPayments): array
    {
        $orphaned = [];
        $gatewayPaymentIds = array_column($gatewayPayments, 'id');

        foreach ($dbPayments as $dbPayment) {
            if (!$dbPayment->payment_gateway_id) {
                continue; // Manual payments don't have gateway ID
            }

            if (!in_array($dbPayment->payment_gateway_id, $gatewayPaymentIds)) {
                $orphaned[] = [
                    'payment_id' => $dbPayment->id,
                    'payment_gateway_id' => $dbPayment->payment_gateway_id,
                    'issue' => 'Payment exists in DB but not in gateway',
                    'db_status' => $dbPayment->status,
                    'amount' => $dbPayment->amount,
                ];
            }
        }

        return $orphaned;
    }

    /**
     * Fetch payments from gateway (Razorpay) for date range
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function fetchGatewayPayments(Carbon $startDate, Carbon $endDate): array
    {
        try {
            // Use Razorpay API to fetch payments
            // This assumes the PaymentGatewayInterface has a method for this
            // If not, we'll need to add it

            // For now, return empty array (implement based on actual gateway interface)
            // In production, this would call Razorpay API:
            // $api->payment->all(['from' => $startDate->timestamp, 'to' => $endDate->timestamp])

            Log::warning("GATEWAY PAYMENT FETCH: Not implemented yet", [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            return [];

        } catch (\Throwable $e) {
            Log::error("GATEWAY PAYMENT FETCH FAILED", [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Normalize gateway status for comparison
     *
     * @param string $status
     * @return string
     */
    private function normalizeGatewayStatus(string $status): string
    {
        $statusMap = [
            'captured' => 'completed',
            'authorized' => 'processing',
            'failed' => 'failed',
            'refunded' => 'refunded',
        ];

        return $statusMap[$status] ?? $status;
    }

    /**
     * Normalize DB status for comparison
     *
     * @param string $status
     * @return string
     */
    private function normalizeDbStatus(string $status): string
    {
        return $status;
    }
}
