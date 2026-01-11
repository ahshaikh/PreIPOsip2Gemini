<?php

namespace App\Traits;

use App\Models\Company;
use App\Models\CompanyDisclosure;
use App\Models\DisclosureVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 1 STABILIZATION - Issue 3: Circular FK Risk
 *
 * PROBLEM:
 * companies → company_disclosures → disclosure_versions circular dependencies
 * can cause deadlocks during concurrent updates.
 *
 * SURGICAL FIX:
 * Enforce lock acquisition in consistent order:
 * 1. Company (parent)
 * 2. CompanyDisclosure (child)
 * 3. DisclosureVersion (grandchild)
 *
 * USAGE:
 * use EnforcesLockOrder;
 * $this->withLockOrder(function() use ($company, $disclosure) {
 *     // Multi-table updates here
 * });
 */
trait EnforcesLockOrder
{
    /**
     * Lock hierarchy order constant
     * Lower number = acquire lock first
     */
    protected const LOCK_ORDER = [
        Company::class => 1,
        CompanyDisclosure::class => 2,
        DisclosureVersion::class => 3,
    ];

    /**
     * Execute callback with proper lock ordering
     *
     * Automatically acquires locks in correct order based on models used.
     *
     * @param callable $callback
     * @param array $models Array of model instances to lock
     * @return mixed
     */
    public function withLockOrder(callable $callback, array $models = [])
    {
        // Sort models by lock order
        usort($models, function ($a, $b) {
            $orderA = self::LOCK_ORDER[get_class($a)] ?? 999;
            $orderB = self::LOCK_ORDER[get_class($b)] ?? 999;
            return $orderA <=> $orderB;
        });

        // Acquire locks in order
        $lockedModels = [];

        DB::beginTransaction();

        try {
            foreach ($models as $model) {
                // Use SELECT ... FOR UPDATE to acquire row lock
                $locked = DB::table($model->getTable())
                    ->where($model->getKeyName(), $model->getKey())
                    ->lockForUpdate()
                    ->first();

                if (!$locked) {
                    throw new \RuntimeException(
                        "Failed to acquire lock on " . get_class($model) . " #{$model->getKey()}"
                    );
                }

                $lockedModels[] = $model;
            }

            // Execute callback with all locks acquired
            $result = $callback();

            DB::commit();

            Log::info('Lock order enforced successfully', [
                'locked_models' => array_map(fn($m) => get_class($m) . ' #' . $m->getKey(), $lockedModels),
            ]);

            return $result;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Lock order enforcement failed', [
                'error' => $e->getMessage(),
                'models' => array_map(fn($m) => get_class($m) . ' #' . $m->getKey(), $models),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Shorthand for locking company and its disclosures
     *
     * @param Company $company
     * @param CompanyDisclosure|null $disclosure
     * @param callable $callback
     * @return mixed
     */
    public function withCompanyLock(Company $company, ?CompanyDisclosure $disclosure, callable $callback)
    {
        $models = [$company];
        if ($disclosure) {
            $models[] = $disclosure;
        }

        return $this->withLockOrder($callback, $models);
    }

    /**
     * Shorthand for locking disclosure and creating version
     *
     * @param CompanyDisclosure $disclosure
     * @param callable $callback Returns DisclosureVersion
     * @return mixed
     */
    public function withDisclosureVersionLock(CompanyDisclosure $disclosure, callable $callback)
    {
        return $this->withLockOrder(function () use ($disclosure, $callback) {
            // Lock company first
            $company = Company::lockForUpdate()->find($disclosure->company_id);

            // Lock disclosure
            $disclosure = CompanyDisclosure::lockForUpdate()->find($disclosure->id);

            // Execute callback (which creates version)
            return $callback($company, $disclosure);

        }, [$disclosure->company, $disclosure]);
    }

    /**
     * Detect potential deadlock risk
     *
     * Analyzes query log for lock acquisition patterns that might cause deadlock.
     * For monitoring/debugging purposes.
     *
     * @return array
     */
    public function detectDeadlockRisk(): array
    {
        // Get recent queries from query log
        $queries = DB::getQueryLog();

        $lockPatterns = [];

        foreach ($queries as $query) {
            if (str_contains($query['query'], 'FOR UPDATE')) {
                // Extract table name
                preg_match('/from\s+`?(\w+)`?/i', $query['query'], $matches);
                $table = $matches[1] ?? 'unknown';

                $lockPatterns[] = [
                    'table' => $table,
                    'timestamp' => microtime(true),
                    'query' => $query['query'],
                ];
            }
        }

        // Check for out-of-order locking
        $violations = [];
        for ($i = 1; $i < count($lockPatterns); $i++) {
            $prev = $lockPatterns[$i - 1]['table'];
            $curr = $lockPatterns[$i]['table'];

            $prevOrder = $this->getTableLockOrder($prev);
            $currOrder = $this->getTableLockOrder($curr);

            if ($prevOrder > $currOrder) {
                $violations[] = [
                    'from_table' => $prev,
                    'to_table' => $curr,
                    'risk' => 'Locks acquired out of order - potential deadlock',
                ];
            }
        }

        return $violations;
    }

    /**
     * Get lock order for a table name
     *
     * @param string $tableName
     * @return int
     */
    protected function getTableLockOrder(string $tableName): int
    {
        $modelMap = [
            'companies' => Company::class,
            'company_disclosures' => CompanyDisclosure::class,
            'disclosure_versions' => DisclosureVersion::class,
        ];

        $modelClass = $modelMap[$tableName] ?? null;

        return self::LOCK_ORDER[$modelClass] ?? 999;
    }
}
