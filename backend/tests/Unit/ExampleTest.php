<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class ExampleTest extends TestCase
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
	    dump(DB::getDatabaseName());
	    $this->assertTrue(true);
	}
}
