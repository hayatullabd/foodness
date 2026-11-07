<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            RolePermissionSeeder::class,
            OrderStatusSeeder::class,
            EcommerceSeeder::class,
            AttributeSeeder::class,
            StorySeeder::class,
            SupplierSeeder::class,
            DemoProductSeeder::class,
        ]);

        User::factory()->create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'phone' => '01800000000',
            'address' => 'Mirpur, Dhaka',
        ]);
    }
}
