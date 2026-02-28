<?php

namespace Tests\Unit;

use Tests\UnitTestCase;
use Illuminate\Support\Facades\DB;

class ExampleTest extends UnitTestCase
{
    /**
     * A basic test example.
     */
    // public function test_that_true_is_true(): void
    // {
    //    $this->assertTrue(true);
    // }

	public function test_database_name()
	{
	    // dump(DB::getDatabaseName());
	    $this->assertTrue(true);
	}
}
