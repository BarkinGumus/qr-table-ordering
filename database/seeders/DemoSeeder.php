<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * One demo restaurant with one account per role, plus a platform admin.
 * Every account uses the password "password". Local and test use only.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $restaurant = Restaurant::firstOrCreate(
            ['slug' => 'demo-lokanta'],
            [
                'name' => 'Demo Lokanta',
                'default_locale' => 'tr',
                'locales' => ['tr', 'en'],
                'currency' => 'TRY',
                'timezone' => 'Europe/Istanbul',
            ],
        );

        $staff = [
            ['Ayşe Sahip', 'owner@demo.test', Role::Owner],
            ['Mehmet Müdür', 'manager@demo.test', Role::Manager],
            ['Can Garson', 'waiter@demo.test', Role::Waiter],
            ['Elif Mutfak', 'kitchen@demo.test', Role::Kitchen],
            ['Deniz Bar', 'bar@demo.test', Role::Bar],
        ];

        foreach ($staff as [$name, $email, $role]) {
            User::firstOrCreate(
                ['email' => $email],
                ['restaurant_id' => $restaurant->id, 'name' => $name, 'password' => 'password', 'role' => $role],
            );
        }

        User::firstOrCreate(
            ['email' => 'admin@platform.test'],
            ['restaurant_id' => null, 'name' => 'Platform Admin', 'password' => 'password', 'role' => Role::PlatformAdmin],
        );
    }
}
