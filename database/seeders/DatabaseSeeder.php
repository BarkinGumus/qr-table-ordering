<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Demo data carries a known password, so it never runs in production.
        if (app()->environment('production')) {
            $this->command?->warn('Skipping demo data in production. Use: php artisan platform:create-admin');

            return;
        }

        $this->call(DemoSeeder::class);
    }
}
