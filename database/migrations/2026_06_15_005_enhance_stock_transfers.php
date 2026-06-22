<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            // ── Intelligence fields ──────────────────────────────────────────

            // Whether this was system-generated (auto-suggested) or manually created
            $table->boolean('is_auto_suggested')->default(false)->after('notes');

            // How urgently the requesting shop needs this stock
            // critical = stock is at 0, high = below reorder_point,
            // normal = proactive top-up
            $table->enum('urgency_level', ['critical', 'high', 'normal'])
                  ->default('normal')
                  ->after('is_auto_suggested');

            // Stock level at requesting shop at time of request — lets managers
            // see the context without querying history
            $table->integer('requesting_shop_stock_at_request')->default(0)
                  ->after('urgency_level');

            // Stock level at donor shop at time of request — confirms the donor
            // had enough when the request was made
            $table->integer('donor_shop_stock_at_request')->default(0)
                  ->after('requesting_shop_stock_at_request');

            // If auto-suggested, which event triggered it
            // Values: low_stock_alert | sale_depletion | scheduled_check
            $table->string('trigger_event')->nullable()->after('donor_shop_stock_at_request');

            // ── Performance indexes ──────────────────────────────────────────
            $table->index(['status', 'urgency_level'], 'transfers_status_urgency_idx');
            $table->index(['to_location_id', 'status'],  'transfers_to_loc_status_idx');
            $table->index(['from_location_id', 'status'], 'transfers_from_loc_status_idx');
            $table->index('is_auto_suggested');
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropIndex('transfers_status_urgency_idx');
            $table->dropIndex('transfers_to_loc_status_idx');
            $table->dropIndex('transfers_from_loc_status_idx');
            $table->dropIndex(['is_auto_suggested']);

            $table->dropColumn([
                'is_auto_suggested',
                'urgency_level',
                'requesting_shop_stock_at_request',
                'donor_shop_stock_at_request',
                'trigger_event',
            ]);
        });
    }
};
