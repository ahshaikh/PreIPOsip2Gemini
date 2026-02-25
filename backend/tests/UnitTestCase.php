<?php

namespace Tests;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

abstract class UnitTestCase extends TestCase
{
    use DatabaseTransactions;
}
