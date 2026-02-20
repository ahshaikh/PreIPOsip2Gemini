<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;


abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use DatabaseMigrations;

    protected function setUp(): void
    {
         parent::setUp();
	
        // Seed the database for every test
        // $this->seed();
    }
    protected function refreshTestDatabase()
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        parent::refreshTestDatabase();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}