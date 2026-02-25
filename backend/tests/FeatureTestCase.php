<?php

namespace Tests;

use Tests\TestCase;
// use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
abstract class FeatureTestCase extends TestCase
{
    // use RefreshDatabase;
	use DatabaseTransactions;
}
