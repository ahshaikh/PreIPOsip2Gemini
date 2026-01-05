<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Seeder\SeederRuntimeAutoCompleter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider for Seeder Runtime Auto-Completer
 *
 * Registers global hooks to intercept database writes during seeding
 * and automatically complete missing required fields.
 *
 * Only active when explicitly enabled via SeederRuntimeAutoCompleter::enable()
 */
class SeederAutoCompleterServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        $this->app->singleton(SeederRuntimeAutoCompleter::class, function ($app) {
            return new SeederRuntimeAutoCompleter();
        });
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Hook into Eloquent's creating event
        Model::creating(function (Model $model) {
            /** @var SeederRuntimeAutoCompleter $autoCompleter */
            $autoCompleter = app(SeederRuntimeAutoCompleter::class);

            if (!$autoCompleter->isEnabled()) {
                return;
            }

            // Get table name
            $table = $model->getTable();

            // Get current attributes
            $attributes = $model->getAttributes();

            // Auto-complete missing fields
            $completedAttributes = $autoCompleter->complete($table, $attributes);

            // Merge completed attributes back into model
            foreach ($completedAttributes as $key => $value) {
                if (!isset($attributes[$key])) {
                    $model->setAttribute($key, $value);
                }
            }
        });

        // Hook into DB Query Builder inserts
        DB::listen(function ($query) {
            // This would require more complex implementation
            // For now, focusing on Eloquent models which cover most seeder cases
        });
    }
}
