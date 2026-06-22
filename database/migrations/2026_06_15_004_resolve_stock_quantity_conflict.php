<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Upgrade location_stocks ───────────────────────────────────────
        Schema::table('location_stocks', function (Blueprint $table) {
            // Rename low_stock_threshold → reorder_point for clarity
            $table->renameColumn('low_stock_threshold', 'reorder_point');

            // Max stock capacity — used by auto-suggestion to know how much
            // a shop can actually absorb in a transfer
            $table->integer('max_stock_level')->default(0)->after('reorder_point');
            // 0 = unlimited

            // Composite index for the two columns always queried together
            $table->index(['product_id', 'location_id'], 'loc_stock_product_location_idx');
        });

        // ── 2. Seed location_stocks from products.stock_quantity ─────────────
        // If a product has global stock > 0 but no location_stock rows yet,
        // push that stock into the first active location as a starting point.
        $defaultLocation = DB::table('locations')
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');

        if ($defaultLocation) {
            $products = DB::table('products')
                ->where('stock_quantity', '>', 0)
                ->where('is_service', false)
                ->get(['id', 'stock_quantity', 'low_stock_threshold']);

            foreach ($products as $product) {
                $exists = DB::table('location_stocks')
                    ->where('product_id', $product->id)
                    ->where('location_id', $defaultLocation)
                    ->exists();

                if (! $exists) {
                    DB::table('location_stocks')->insert([
                        'product_id'    => $product->id,
                        'location_id'   => $defaultLocation,
                        'quantity'      => $product->stock_quantity,
                        'reorder_point' => $product->low_stock_threshold,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                }
            }
        }

        // ── 3. Deprecate products.stock_quantity ─────────────────────────────
        // We keep the column but mark it deprecated via a comment.
        // A future migration can drop it once all queries are migrated.
        // We do NOT drop it now — too risky without seeing your models first.
        Schema::table('products', function (Blueprint $table) {
            // Rename so it's obviously deprecated — forces compile errors in code
            // that still references it, making the migration visible immediately.
            $table->renameColumn('stock_quantity', 'stock_quantity_deprecated');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('stock_quantity_deprecated', 'stock_quantity');
        });

        Schema::table('location_stocks', function (Blueprint $table) {
            $table->dropIndex('loc_stock_product_location_idx');
            $table->dropColumn('max_stock_level');
            $table->renameColumn('reorder_point', 'low_stock_threshold');
        });
    }
};
