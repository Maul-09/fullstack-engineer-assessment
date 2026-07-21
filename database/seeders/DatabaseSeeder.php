<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Product::query()->updateOrCreate(
            ['sku' => 'FLASH-001'],
            [
                'name' => 'Wireless Mechanical Keyboard K87',
                'regular_price' => 100000,
                'flash_price' => 10000,
                'inventory_quantity' => 10,
                'flash_starts_at' => now()->subHour(),
                'flash_ends_at' => now()->addDay(),
            ],
        );
    }
}
