<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class FullSetupSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            PermissionSeeder::class,
            ExhaustivePreIPOSeeder::class,
            CompanyUserSeeder::class,
            OffersSeeder::class,
            PromotionalMaterialsSeeder::class,
        ]);
    }
}
