<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Faker\Factory as Faker;
use Carbon\Carbon;

class ComprehensiveFakerSeeder extends Seeder
{
    /**
     * Map models (FQCN) to target counts for seeding.
     * You can edit counts below for big tables to smaller numbers for local dev.
     */
    protected array $modelCounts = [
        // Core auth
        \App\Models\User::class => 200,
        \App\Models\UserProfile::class => 200,
        \App\Models\UserKyc::class => 200,

        // Payments & wallets
        \App\Models\Payment::class => 800,
        \App\Models\Wallet::class => 200,
        \App\Models\Transaction::class => 3000,
        \App\Models\Withdrawal::class => 200,

        // Products & purchases
        \App\Models\Product::class => 50,
        \App\Models\BulkPurchase::class => 40,
        \App\Models\UserInvestment::class => 1500,

        // Subscriptions & plans
        \App\Models\Plan::class => 6,
        \App\Models\Subscription::class => 400,
        \App\Models\Payment::class => 800,

        // Support & CMS
        \App\Models\SupportTicket::class => 200,
        \App\Models\SupportMessage::class => 800,
        \App\Models\Page::class => 20,
        \App\Models\KbCategory::class => 10,
        \App\Models\KbArticle::class => 80,

        // Misc (smaller)
        \App\Models\Referral::class => 300,
        \App\Models\BonusTransaction::class => 300,
        \App\Models\CannedResponse::class => 50,
        \App\Models\ActivityLog::class => 2000,
        \App\Models\WebhookLog::class => 200,
        \App\Models\Notification::class => 1000,
    ];

    protected Filesystem $fs;
    protected $faker;

    public function __construct()
    {
        $this->fs = new Filesystem();
        $this->faker = Faker::create();
    }

    public function run(): void
    {
        $this->command->getOutput()->writeln("<info>Comprehensive Faker Seeder started at " . now() . "</info>");

        // Safety: warn if not running in local / testing
        $appEnv = config('app.env');
        if (!in_array($appEnv, ['local', 'testing', 'dev'])) {
            $this->command->warn("You are running seeder in environment '{$appEnv}'. Make sure you understand the risks.");
        }

        foreach ($this->modelCounts as $modelClass => $count) {
            $short = class_basename($modelClass);
            $this->command->getOutput()->writeln("<comment>Processing: {$short} (target: {$count})</comment>");

            // If model doesn't exist: fallback to table
            if (!class_exists($modelClass)) {
                $table = $this->guessTableNameFromModel($modelClass);
                $this->command->warn("Model {$modelClass} not found. Falling back to DB insert for table: {$table}");
                $this->seedTableDirectly($table, $count);
                continue;
            }

            // If factory exists, use it
            $factoryClass = $this->guessFactoryClass($modelClass);
            if ($this->factoryExists($factoryClass)) {
                $this->command->info("Using factory: {$factoryClass}");
                try {
                    $modelClass::factory()->count($count)->create();
                } catch (\Throwable $e) {
                    $this->command->error("Factory create failed for {$modelClass}: " . $e->getMessage());
                    // try fallback: generate inserts directly
                    $table = $modelClass::getTable();
                    $this->seedTableDirectly($table, $count);
                }
                continue;
            }

            // No factory -> create a factory file automatically and then use it
            $this->command->info("Factory for {$short} not found. Generating factory file...");
            $created = $this->generateFactoryForModel($modelClass);
            if ($created) {
                // reload composer autoload in process? We cannot run composer here,
                // but Laravel will find the factory class via PSR-4 once autoloaded on next request.
                // To be safe, we'll create records directly using the factory file by requiring it.
                try {
                    // attempt to use factory via model::factory() after refreshing autoload (best-effort)
                    // Please run `composer dump-autoload` before seeding if newly generated factories are not detected.
                    $modelClass::factory()->count($count)->create();
                } catch (\Throwable $e) {
                    $this->command->warn("Could not run factory after generation (autoload may need refresh). Falling back to direct inserts. Error: " . $e->getMessage());
                    $table = $modelClass::getTable();
                    $this->seedTableDirectly($table, $count);
                }
            } else {
                $this->command->warn("Factory generation failed for {$short}. Falling back to direct inserts.");
                $table = $modelClass::getTable();
                $this->seedTableDirectly($table, $count);
            }
        }

        $this->command->getOutput()->writeln("<info>Seeder finished at " . now() . "</info>");
    }

    /**
     * Guess factory FQCN by Laravel convention
     */
    protected function guessFactoryClass(string $modelClass): string
    {
        $modelBase = class_basename($modelClass);
        return "Database\\Factories\\{$modelBase}Factory";
    }

    protected function factoryExists(string $factoryClass): bool
    {
        return class_exists($factoryClass) || $this->fs->exists(database_path('factories/' . class_basename($factoryClass) . '.php'));
    }

    /**
     * Generate a factory file for a model by inferring columns from DB schema.
     * Returns true on success.
     */
    protected function generateFactoryForModel(string $modelClass): bool
    {
        try {
            $model = new $modelClass;
            if (!method_exists($model, 'getTable')) {
                return false;
            }
            $table = $model->getTable();

            if (!Schema::hasTable($table)) {
                $this->command->warn("Table '{$table}' does not exist; cannot infer columns.");
                return false;
            }

            $columns = Schema::getColumnListing($table);
            // remove guarded/system timestamps
            $columns = array_filter($columns, function ($c) {
                return !in_array($c, ['id', 'created_at', 'updated_at', 'deleted_at']);
            });

            $definitionLines = [];
            foreach ($columns as $col) {
                $definitionLines[] = "            '{$col}' => " . $this->fakerValueForColumn($table, $col) . ",";
            }

            $modelBase = class_basename($modelClass);
            $factoryClassName = "{$modelBase}Factory";
            $factoryPath = database_path("factories/{$factoryClassName}.php");

            $factoryStub = <<<PHP
<?php

namespace Database\Factories;

use {$modelClass};
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class {$factoryClassName} extends Factory
{
    protected \$model = {$modelBase}::class;

    public function definition()
    {
        return [
{DEFINITIONS}
        ];
    }
}
PHP;

            $factoryContent = str_replace('{DEFINITIONS}', implode("\n", $definitionLines), $factoryStub);

            // write file
            $this->fs->ensureDirectoryExists(database_path('factories'));
            $this->fs->put($factoryPath, $factoryContent);

            $this->command->info("Factory created at: {$factoryPath}");
            $this->command->warn("If Seeder cannot detect the new factory, run: composer dump-autoload");

            return true;
        } catch (\Throwable $e) {
            $this->command->error("Error generating factory for {$modelClass}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Heuristic: produce a Faker expression string for a column
     * Returns a PHP snippet string (e.g. \"\$this->faker->email()\")
     */
    protected function fakerValueForColumn(string $table, string $column): string
    {
        $lc = Str::lower($column);

        // Common names heuristic
        if (Str::contains($lc, 'email')) {
            return "\$this->faker->unique()->safeEmail()";
        }
        if (Str::contains($lc, 'name') && $lc !== 'name') {
            return "\$this->faker->name()";
        }
        if (Str::endsWith($lc, '_id') || Str::endsWith($lc, 'id')) {
            return "(function () { return null; })()"; // leave null, factory relationships recommended
        }
        if (Str::contains($lc, 'mobile') || Str::contains($lc, 'phone')) {
            return "\"\" . \$this->faker->numerify('9#########')"; // 10-digit
        }
        if (Str::contains($lc, 'password') || Str::contains($lc, 'password_hash')) {
            return "bcrypt('password')";
        }
        if (Str::contains($lc, 'token') || Str::contains($lc, 'signature')) {
            return "Str::random(32)";
        }
        if (Str::contains($lc, 'slug')) {
            return "Str::slug(\$this->faker->sentence(3))";
        }
        if (Str::contains($lc, 'status')) {
            return "'pending'";
        }
        if (Str::contains($lc, 'amount') || Str::contains($lc, 'price') || Str::contains($lc, 'balance') || Str::contains($lc, 'value')) {
            return "\$this->faker->randomFloat(2, 0, 100000)";
        }
        if (Str::contains($lc, 'date') || Str::contains($lc, 'at') || Str::contains($lc, 'time')) {
            return "\$this->faker->dateTimeBetween('-2 years', 'now')";
        }
        if (Str::contains($lc, 'ip')) {
            return "\$this->faker->ipv4()";
        }
        if (Str::contains($lc, 'json') || Str::contains($lc, 'meta')) {
            return "json_encode([])";
        }
        if (Str::contains($lc, 'description') || Str::contains($lc, 'notes') || Str::contains($lc, 'message') || Str::contains($lc, 'body')) {
            return "\$this->faker->realText(200)";
        }

        // default: short string
        return "\$this->faker->word()";
    }

    /**
     * Seed a table directly via DB inserts (fallback).
     */
    protected function seedTableDirectly(string $table, int $count): void
    {
        if (!Schema::hasTable($table)) {
            $this->command->error("Table '{$table}' does not exist. Skipping.");
            return;
        }

        $columns = Schema::getColumnListing($table);
        $cols = array_filter($columns, fn($c) => !in_array($c, ['id', 'created_at', 'updated_at', 'deleted_at']));

        $batchSize = 100;
        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $row = [];
            foreach ($cols as $col) {
                $row[$col] = $this->resolveFallbackValue($table, $col, $i);
            }
            $row['created_at'] = $row['updated_at'] = Carbon::now()->toDateTimeString();
            $rows[] = $row;

            if (count($rows) >= $batchSize) {
                DB::table($table)->insert($rows);
                $rows = [];
            }
        }
        if (count($rows) > 0) {
            DB::table($table)->insert($rows);
        }
        $this->command->info("Inserted {$count} rows into {$table} (direct DB fallback).");
    }

    protected function resolveFallbackValue(string $table, string $col, int $idx)
    {
        // reuse faker heuristic
        $expr = $this->fakerValueForColumn($table, $col);
        // For direct DB inserts we need actual values, not code.
        // Map some common cases:
        if (Str::contains(Str::lower($col), 'email')) {
            return $this->faker->unique()->safeEmail();
        }
        if (Str::contains(Str::lower($col), 'name')) {
            return $this->faker->name();
        }
        if (Str::contains(Str::lower($col), 'mobile') || Str::contains(Str::lower($col), 'phone')) {
            return preg_replace('/[^0-9]/', '', $this->faker->numerify('9#########'));
        }
        if (Str::contains(Str::lower($col), 'amount') || Str::contains(Str::lower($col), 'price') || Str::contains(Str::lower($col), 'balance') || Str::contains(Str::lower($col), 'value')) {
            return $this->faker->randomFloat(2, 1, 10000);
        }
        if (Str::contains(Str::lower($col), 'date') || Str::contains(Str::lower($col), 'at') || Str::contains(Str::lower($col), 'time')) {
            return $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d H:i:s');
        }
        if (Str::contains(Str::lower($col), 'status')) {
            return 'pending';
        }
        if (Str::contains(Str::lower($col), 'slug')) {
            return Str::slug($this->faker->words(3, true));
        }
        if (Str::contains(Str::lower($col), 'json') || Str::contains(Str::lower($col), 'meta')) {
            return json_encode([]);
        }
        if (Str::contains(Str::lower($col), 'description') || Str::contains(Str::lower($col), 'notes') || Str::contains(Str::lower($col), 'message') || Str::contains(Str::lower($col), 'body')) {
            return $this->faker->realText(120);
        }

        // fallback small random string/number
        return $this->faker->word();
    }

    /**
     * Try to guess table name from a missing model FQCN
     */
    protected function guessTableNameFromModel(string $modelClass): string
    {
        $base = class_basename($modelClass);
        return Str::snake(Str::plural($base));
    }
}
