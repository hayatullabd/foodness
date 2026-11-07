<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        Supplier::query()->updateOrCreate(
            ['phone' => '01711112222'],
            [
                'name' => 'Dhaka Textile Supply',
                'email' => 'dhaka-textile@example.com',
                'address' => 'Mirpur-10, Dhaka',
            ],
        );

        Supplier::query()->updateOrCreate(
            ['phone' => '01822223333'],
            [
                'name' => 'Chittagong Garments Hub',
                'email' => 'ctg-garments@example.com',
                'address' => 'Agrabad, Chattogram',
            ],
        );
    }
}
