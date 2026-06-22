<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── purchase_orders: add shop context ────────────────────────────────
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('created_by')
                  ->constrained()->nullOnDelete();

            $table->index(['location_id', 'status'], 'po_location_status_idx');
        });

        // ── Performance indexes on high-traffic tables ───────────────────────

        // sales: the POS queries this constantly
        Schema::table('sales', function (Blueprint $table) {
            $table->index(['location_id', 'created_at'],      'sales_location_date_idx');
            $table->index(['location_id', 'payment_status'],  'sales_location_payment_idx');
            $table->index(['user_id', 'created_at'],          'sales_user_date_idx');
        });

        // sale_items: queried on every sale load and every stock deduction
        Schema::table('sale_items', function (Blueprint $table) {
            $table->index(['sale_id', 'product_id'], 'sale_items_sale_product_idx');
        });

        // location_stocks: queried on every POS product search
        Schema::table('location_stocks', function (Blueprint $table) {
            // Partial-style: low stock alerts only scan rows below reorder_point
            // MySQL doesn't support partial indexes but this composite covers it
            $table->index(['location_id', 'quantity'], 'loc_stock_location_qty_idx');
        });

        // stock_movements: heavy audit table — index by date for reports
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index(['location_id', 'created_at'],  'movements_location_date_idx');
            $table->index(['product_id', 'created_at'],   'movements_product_date_idx');
        });

        // invoices: filtered by shop + status in billing views
        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['location_id', 'status'],      'invoices_location_status_idx');
            $table->index(['location_id', 'created_at'],  'invoices_location_date_idx');
        });

        // quotations: same pattern
        Schema::table('quotations', function (Blueprint $table) {
            $table->index(['location_id', 'status'],      'quotations_location_status_idx');
        });

        // activity_log: Spatie's log is queried by causer — add date for reporting
        Schema::table('activity_log', function (Blueprint $table) {
            $table->index(['causer_id', 'created_at'], 'activity_causer_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex('activity_causer_date_idx');
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropIndex('quotations_location_status_idx');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_location_date_idx');
            $table->dropIndex('invoices_location_status_idx');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('movements_product_date_idx');
            $table->dropIndex('movements_location_date_idx');
        });

        Schema::table('location_stocks', function (Blueprint $table) {
            $table->dropIndex('loc_stock_location_qty_idx');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex('sale_items_sale_product_idx');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_user_date_idx');
            $table->dropIndex('sales_location_payment_idx');
            $table->dropIndex('sales_location_date_idx');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex('po_location_status_idx');
            $table->dropForeign(['location_id']);
            $table->dropColumn('location_id');
        });
    }
};
