<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ReflectionClass;

class SchemaAssert extends Command
{
    /**
     * Command signature
     */
    protected $signature = 'schema:assert {--core : Validate only core / boot-critical models}
                                           {--full : Validate all active models}';

    protected $description = 'Assert database schema matches active Eloquent models (production-safe)';

    /**
     * Models that are ALWAYS skipped (hard deprecated)
     */
    protected array $hardSkippedModels = [
        \App\Models\Offer::class, // deprecated by design
    ];

    /**
     * Core models required for application boot & seeders
     */
    protected array $coreModels = [
        \App\Models\User::class,
        \App\Models\UserProfile::class,
        \App\Models\Wallet::class,
        \App\Models\Transaction::class,
        \App\Models\Company::class,
        \App\Models\CompanyUser::class,
        \App\Models\FeatureFlag::class,
        \App\Models\Setting::class,
        \App\Models\AuditLog::class,
    ];

    public function handle(): int
    {
        $this->info('🔍 Running Schema Guard...');

        $mode = $this->option('core') ? 'core' : 'full';

        if ($mode === 'core') {
            $this->line('🧠 Mode: CORE (boot-critical models only)');
            $models = $this->coreModels;
        } else {
            $this->line('🧠 Mode: FULL (all active models)');
            $models = $this->discoverAllModels();
        }

        $errors = [];

        foreach ($models as $modelClass) {
            if (!class_exists($modelClass)) {
                continue;
            }

            if (in_array($modelClass, $this->hardSkippedModels, true)) {
                $this->line("⚠️  Skipping deprecated model: {$modelClass}");
                continue;
            }

            $reflection = new ReflectionClass($modelClass);

            if (!$reflection->isSubclassOf(Model::class)) {
                continue;
            }

            // Skip models that explicitly opt out
            if ($this->isSchemaGuardIgnored($reflection)) {
                $this->line("⚠️  Skipping schema guard (opt-out): {$modelClass}");
                continue;
            }

            /** @var Model $model */
            $model = $reflection->newInstanceWithoutConstructor();

            $table = $model->getTable();

            if (!Schema::hasTable($table)) {
                $errors[] = "❌ Table missing: {$table} (from {$modelClass})";
                continue;
            }

            foreach ($model->getFillable() as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    $errors[] = "❌ Column missing: {$table}.{$column} (used in {$modelClass})";
                }
            }
        }

        if (!empty($errors)) {
            $this->error('🚨 SCHEMA ASSERTION FAILED');
            foreach ($errors as $error) {
                $this->line($error);
            }

            $this->line('');
            $this->warn('➡️ Fix schema via ADDITIVE migrations ONLY.');
            return self::FAILURE;
        }

        $this->info('✅ SCHEMA ASSERTION PASSED');
        return self::SUCCESS;
    }

    /**
     * Discover all Eloquent models in app/Models
     */
    protected function discoverAllModels(): array
    {
        $models = [];
        $modelPath = app_path('Models');

        foreach (scandir($modelPath) as $file) {
            if (!Str::endsWith($file, '.php')) {
                continue;
            }

            $class = 'App\\Models\\' . Str::replaceLast('.php', '', $file);

            if (class_exists($class)) {
                $models[] = $class;
            }
        }

        return $models;
    }

    /**
     * Check if model opts out of schema guard
     */
    protected function isSchemaGuardIgnored(ReflectionClass $reflection): bool
    {
        if ($reflection->hasProperty('schemaGuardIgnore')) {
            $prop = $reflection->getProperty('schemaGuardIgnore');
            $prop->setAccessible(true);

            return $prop->getValue(
                $reflection->newInstanceWithoutConstructor()
            ) === true;
        }

        // Also respect @deprecated tag
        $doc = $reflection->getDocComment() ?: '';
        if (Str::contains($doc, '@deprecated')) {
            return true;
        }

        return false;
    }
}
