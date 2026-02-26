<?php
/**
 * Special base class for "unit" tests that require full DB refresh.
 *
 * Use only when DatabaseTransactions is insufficient
 * (e.g., factory state isolation, aggregate calculations, cross-test leakage).
 */

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class UnitRefreshTestCase extends TestCase
{
    use RefreshDatabase;
}