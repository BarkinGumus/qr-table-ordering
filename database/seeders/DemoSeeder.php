<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\Station;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use App\Tenancy\Tenant;
use Illuminate\Database\Seeder;

/**
 * One demo restaurant with one account per role, twelve tables and a small
 * Turkish/English menu, plus a platform admin. Every account uses the
 * password "password". Local and test use only. Safe to run twice.
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

        app(Tenant::class)->run($restaurant, function (): void {
            $this->seedTables();
            $this->seedMenu();
        });
    }

    private function seedTables(): void
    {
        $tables = [];

        foreach (range(1, 8) as $n) {
            $tables[] = ["S{$n}", 'Salon', $n <= 2 ? 2 : 4];
        }

        foreach (range(1, 4) as $n) {
            $tables[] = ["T{$n}", 'Teras', 4];
        }

        foreach ($tables as $order => [$label, $area, $capacity]) {
            DiningTable::firstOrCreate(
                ['label' => $label],
                ['area' => $area, 'capacity' => $capacity, 'sort_order' => $order],
            );
        }
    }

    private function seedMenu(): void
    {
        // [Turkish name, English name, station, [[tr, en, tr description, en description, price], ...]]
        $menu = [
            ['Başlangıçlar', 'Starters', Station::Kitchen, [
                ['Sezar Salata', 'Caesar Salad', 'Marul, parmesan ve kruton ile', 'Romaine lettuce, parmesan and croutons', 65],
                ['Bruschetta', 'Bruschetta', 'Domates ve sarımsaklı kızarmış ekmek', 'Toasted bread with tomato and garlic', 55],
                ['Tavuk Kanat', 'Chicken Wings', 'Ranch soslu acılı tavuk kanatları', 'Spicy wings with ranch sauce', 75],
                ['Patates Kızartması', 'French Fries', 'Ketçap eşliğinde çıtır patates', 'Crispy fries with ketchup', 45],
            ]],
            ['Çorbalar', 'Soups', Station::Kitchen, [
                ['Domates Çorbası', 'Tomato Soup', 'Fesleğenli kremalı domates çorbası', 'Creamy tomato soup with basil', 50],
                ['Tavuk Çorbası', 'Chicken Soup', 'Sebzeli ev yapımı tavuk çorbası', 'Homemade chicken soup with vegetables', 60],
            ]],
            ['Ana Yemekler', 'Main Courses', Station::Kitchen, [
                ['Izgara Tavuk', 'Grilled Chicken', 'Sebzeli marine ızgara tavuk', 'Marinated grilled chicken with vegetables', 140],
                ['Dana Biftek', 'Beef Steak', 'Izgara dana biftek', 'Grilled beef steak', 280],
                ['Spaghetti Bolognese', 'Spaghetti Bolognese', 'Et soslu klasik makarna', 'Classic pasta with meat sauce', 95],
                ['Margherita Pizza', 'Margherita Pizza', 'Mozzarella, domates sosu ve fesleğen', 'Mozzarella, tomato sauce and basil', 110],
                ['Peynirli Burger', 'Cheeseburger', 'Peynirli burger ve patates kızartması', 'Cheeseburger with fries', 120],
            ]],
            ['Tatlılar', 'Desserts', Station::Kitchen, [
                ['Tiramisu', 'Tiramisu', 'Kahveli klasik İtalyan tatlısı', 'Classic Italian coffee dessert', 70],
                ['Çikolatalı Kek', 'Chocolate Cake', 'Dondurmalı yoğun çikolatalı kek', 'Rich chocolate cake with ice cream', 65],
                ['Cheesecake', 'Cheesecake', 'New York usulü cheesecake', 'New York style cheesecake', 75],
            ]],
            ['İçecekler', 'Drinks', Station::Bar, [
                ['Kola', 'Cola', 'Soğuk gazlı içecek (330 ml)', 'Cold soft drink (330 ml)', 25],
                ['Taze Sıkma Portakal Suyu', 'Fresh Orange Juice', 'Taze sıkılmış portakal suyu', 'Freshly squeezed orange juice', 40],
                ['Türk Kahvesi', 'Turkish Coffee', 'Geleneksel Türk kahvesi', 'Traditional Turkish coffee', 30],
                ['Cappuccino', 'Cappuccino', 'Süt köpüklü İtalyan kahvesi', 'Italian coffee with milk foam', 45],
            ]],
        ];

        foreach ($menu as $categoryOrder => [$tr, $en, $station, $products]) {
            $category = Category::where('name->tr', $tr)->first()
                ?? Category::create(['name' => ['tr' => $tr, 'en' => $en], 'station' => $station, 'sort_order' => $categoryOrder]);

            foreach ($products as $productOrder => [$nameTr, $nameEn, $descTr, $descEn, $price]) {
                if (Product::where('name->tr', $nameTr)->exists()) {
                    continue;
                }

                Product::create([
                    'category_id' => $category->id,
                    'name' => ['tr' => $nameTr, 'en' => $nameEn],
                    'description' => ['tr' => $descTr, 'en' => $descEn],
                    'price' => $price,
                    'sort_order' => $productOrder,
                ]);
            }
        }
    }
}
