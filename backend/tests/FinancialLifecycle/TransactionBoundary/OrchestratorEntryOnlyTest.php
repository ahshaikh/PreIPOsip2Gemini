<?php

/**
 * OrchestratorEntryOnlyTest
 *
 * INVARIANT: FinancialOrchestrator is the ONLY entry point for financial mutations.
 *
 * Verifies that:
 * - Domain services cannot be invoked directly for mutations
 * - All financial operations flow through orchestrator
 * - Direct service calls are blocked or logged
 *
 * @package Tests\FinancialLifecycle\TransactionBoundary
 */

namespace Tests\FinancialLifecycle\TransactionBoundary;

use Tests\FinancialLifecycle\FinancialLifecycleTestCase;
use Tests\FinancialLifecycle\Support\StaticAnalysisHelper;
use Illuminate\Support\Facades\DB;
use App\Models\Payment;

class OrchestratorEntryOnlyTest extends FinancialLifecycleTestCase
{
    /**
     * Static analysis: Detect direct service mutation calls.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function static_analysis_detects_direct_service_calls(): void
    {
        $analyzer = new StaticAnalysisHelper(base_path());
        $violations = $analyzer->scanForDirectServiceCalls();

        // This will fail until refactor centralizes all calls through orchestrator
        $this->assertEmpty(
            $violations,
            "Direct service mutation calls detected:\n" .
            $this->formatViolations($violations) .
            "\n\nAll financial mutations should go through FinancialOrchestrator."
        );
    }

    /**
     * Test that FinancialOrchestrator exists and has processSuccessfulPayment method.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function financial_orchestrator_exists(): void
    {
        // This test will fail until orchestrator is created
        $this->assertTrue(
            class_exists(\App\Services\FinancialOrchestrator::class),
            "FinancialOrchestrator class does not exist. " .
            "Create App\\Services\\FinancialOrchestrator as single entry point for financial lifecycle."
        );
    }

    /**
     * Test that orchestrator has required methods.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function orchestrator_has_required_methods(): void
    {
        if (!class_exists(\App\Services\FinancialOrchestrator::class)) {
            $this->markTestSkipped('FinancialOrchestrator not yet implemented');
        }

        $orchestrator = app(\App\Services\FinancialOrchestrator::class);

        // Required methods for payment lifecycle
        $requiredMethods = [
            'processSuccessfulPayment',
            'processRefund',
            'processChargeback',
        ];

        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                method_exists($orchestrator, $method),
                "FinancialOrchestrator must implement {$method}()"
            );
        }
    }

    /**
     * Test that webhook service delegates to orchestrator.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function webhook_service_delegates_to_orchestrator(): void
    {
        if (!class_exists(\App\Services\FinancialOrchestrator::class)) {
            $this->markTestSkipped('FinancialOrchestrator not yet implemented');
        }

        $this->createTestUser();
        $this->createTestSubscription();
        $payment = $this->createTestPayment();

        // Mock orchestrator to verify it's called
        $orchestratorMock = $this->mock(\App\Services\FinancialOrchestrator::class);
        $orchestratorMock->shouldReceive('processSuccessfulPayment')
            ->once()
            ->with(\Mockery::on(fn($p) => $p->id === $payment->id))
            ->andReturn(true);

        $webhookService = app(\App\Services\PaymentWebhookService::class);
        $webhookService->handleSuccessfulPayment([
            'order_id' => $payment->gateway_order_id,
            'id' => 'pay_' . $payment->gateway_order_id,
        ]);
    }

    /**
     * Test that ProcessSuccessfulPaymentJob delegates to orchestrator.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function job_delegates_to_orchestrator(): void
    {
        if (!class_exists(\App\Services\FinancialOrchestrator::class)) {
            $this->markTestSkipped('FinancialOrchestrator not yet implemented');
        }

        $this->createTestUser();
        $this->createTestSubscription();
        $payment = $this->createTestPayment();
        $payment->update(['status' => Payment::STATUS_PAID]);

        // Mock orchestrator
        $orchestratorMock = $this->mock(\App\Services\FinancialOrchestrator::class);
        $orchestratorMock->shouldReceive('processSuccessfulPayment')
            ->once()
            ->andReturn(true);

        // Run job
        \App\Jobs\ProcessSuccessfulPaymentJob::dispatchSync($payment);
    }

    /**
     * Test that direct WalletService deposit calls are blocked/logged.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function direct_wallet_deposit_is_tracked(): void
    {
        $this->createTestUser();

        // After refactor, direct calls should either:
        // 1. Throw exception (enforced boundary)
        // 2. Log warning (soft enforcement)
        // 3. Check for orchestrator context

        $walletService = app(\App\Services\WalletService::class);

        // Current behavior: Direct calls work
        // Target behavior: Direct calls should be prevented or logged
        $initialBalance = $this->testWallet->balance_paise;

        try {
            $walletService->deposit(
                $this->testUser,
                100000,
                \App\Enums\TransactionType::DEPOSIT,
                'Direct deposit test'
            );

            // If we get here, direct calls still work
            // After refactor with strict enforcement, this should throw
            $this->testWallet->refresh();

            $this->markTestIncomplete(
                "Direct WalletService::deposit() still works. " .
                "After refactor, direct calls should be blocked or require orchestrator context."
            );
        } catch (\RuntimeException $e) {
            // Expected after refactor with strict enforcement
            $this->assertStringContainsString(
                'orchestrator',
                strtolower($e->getMessage()),
                "Exception should indicate orchestrator requirement"
            );
        }
    }

    /**
     * Test that orchestrator context is required for mutations.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function orchestrator_context_required_for_mutations(): void
    {
        if (!class_exists(\App\Services\FinancialOrchestrator::class)) {
            $this->markTestSkipped('FinancialOrchestrator not yet implemented');
        }

        $this->createTestUser();

        // Services should check for orchestrator context
        // Option 1: Request-level context
        // Option 2: Thread-local context
        // Option 3: Method parameter

        // This test documents expected behavior after refactor
        $this->markTestIncomplete(
            "Test for orchestrator context requirement. " .
            "After refactor, domain services should verify they're called from orchestrator."
        );
    }

    /**
     * Test that API controllers cannot directly mutate financial state.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function controllers_cannot_directly_mutate(): void
    {
        // Controllers should only trigger orchestrator methods
        // They should not directly call WalletService, AllocationService, etc.

        $controllerPath = base_path('app/Http/Controllers');
        $financialMutationPatterns = [
            'walletService->deposit',
            'walletService->withdraw',
            'allocationService->allocate',
            'bonusCalculatorService->calculateAndAward',
        ];

        $violations = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($controllerPath)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());

            foreach ($financialMutationPatterns as $pattern) {
                if (stripos($content, $pattern) !== false) {
                    $violations[] = [
                        'file' => $file->getFilename(),
                        'pattern' => $pattern,
                    ];
                }
            }
        }

        $this->assertEmpty(
            $violations,
            "Controllers directly calling financial services:\n" .
            json_encode($violations, JSON_PRETTY_PRINT) .
            "\n\nControllers should delegate to orchestrator for financial operations."
        );
    }

    /**
     * Format violations for error message.
     */
    private function formatViolations(array $violations): string
    {
        $lines = [];
        foreach ($violations as $v) {
            $lines[] = "  - {$v['file']}:{$v['line']}: {$v['description']}";
            if (isset($v['code'])) {
                $lines[] = "    Code: {$v['code']}";
            }
        }
        return implode("\n", $lines);
    }
}
