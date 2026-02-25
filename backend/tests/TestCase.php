<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker; // 1. Add this import
// use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, WithFaker;
//     use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
	$this->faker->unique(true); // clears the unique pool

        /**
         * CRITICAL: Clear Spatie permission cache
         *
         * Without this, permission IDs can become stale between tests
         * (especially after migrate:fresh or during parallel execution),
         * causing FK violations in role_has_permissions.
         *
         * This does NOT affect production behavior.
         * It only ensures test isolation correctness.
         */
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Do NOT seed globally.
        // Seed explicitly inside tests when required.
    }
}
