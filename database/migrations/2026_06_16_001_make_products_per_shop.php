<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Add shop-ownership and stock columns directly onto products ────
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('id')
                  ->constrained()->cascadeOnDelete();

            // Quantity moves from location_stocks onto the product itself,
            // since a product now belongs to exactly one shop.
            $table->integer('quantity')->default(0)->after('stock_quantity_deprecated');
            $table->integer('reorder_point')->default(5)->after('quantity');
            $table->integer('max_stock_level')->default(0)->after('reorder_point');

            // Drop the old global-unique SKU constraint — SKUs are now only
            // unique within a single shop's catalog (sku, location_id).
            $table->dropUnique(['sku']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unique(['sku', 'location_id'], 'products_sku_location_unique');
            $table->index(['location_id', 'is_active'], 'products_location_active_idx');
            $table->index(['barcode'], 'products_barcode_idx');
        });

        // ── 2. Assign the existing product to Westlands only ──────────────────
        // Per the decision: existing products stay with their original shop,
        // other shops (freddie) start with an empty catalog.
        $westlands = DB::table('locations')->where('name', 'like', 'Westlands%')->first();

        if ($westlands) {
            $products = DB::table('products')->whereNull('location_id')->get(['id']);

            foreach ($products as $product) {
                // Pull this product's quantity from location_stocks at Westlands
                $stock = DB::table('location_stocks')
                    ->where('product_id', $product->id)
                    ->where('location_id', $westlands->id)
                    ->first();

                DB::table('products')->where('id', $product->id)->update([
                    'location_id'   => $westlands->id,
                    'quantity'      => $stock->quantity ?? 0,
                    'reorder_point' => $stock->reorder_point ?? 5,
                ]);
            }
        }

        // ── 3. Make location_id NOT NULL now that all rows are assigned ───────
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_sku_location_unique');
            $table->dropIndex('products_location_active_idx');
            $table->dropIndex('products_barcode_idx');
            $table->dropForeign(['location_id']);
            $table->dropColumn(['location_id', 'quantity', 'reorder_point', 'max_stock_level']);
            $table->unique('sku');
        });
    }
};
