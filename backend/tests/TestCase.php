<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\Cache; // Added

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear application cache between tests
        Cache::flush();
        
        $this->faker->unique(true);

        /**
         * CRITICAL: Clear Spatie permission cache
         */
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}