<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\LocationStock;
use App\Models\Product;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        // Only seed the warehouse — shops are created via admin panel
        $warehouse = Location::firstOrCreate(
            ['code' => 'WAREHOUSE'],
            [
                'name'      => 'Main Warehouse',
                'type'      => 'warehouse',
                'is_active' => true,
            ]
        );

        // Migrate existing product stock_quantity → warehouse
        Product::where('is_service', false)->each(function (Product $product) use ($warehouse) {
            LocationStock::firstOrCreate(
                [
                    'product_id'  => $product->id,
                    'location_id' => $warehouse->id,
                ],
                [
                    'quantity'            => $product->stock_quantity,
                    'low_stock_threshold' => $product->low_stock_threshold,
                ]
            );
        });

        $this->command->info('Warehouse created and existing stock migrated.');
    }
}