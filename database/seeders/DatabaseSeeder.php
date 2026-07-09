<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OrganizationSeeder::class,
            EventCatalogSeeder::class,
            DemoDataSeeder::class, // self-guards against app()->environment('production')
        ]);
    }
}
