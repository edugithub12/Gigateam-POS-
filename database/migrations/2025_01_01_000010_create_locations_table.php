<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Locations table
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');              // "Shop A", "Warehouse"
            $table->string('code')->unique();    // "SHOP_A", "WAREHOUSE"
            $table->string('type')->default('shop'); // shop, warehouse
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Add location_id to users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('location_id')
                  ->nullable()
                  ->after('is_active')
                  ->constrained('locations')
                  ->onDelete('set null');
        });

        // 3. Add location_id to sales
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('location_id')
                  ->nullable()
                  ->after('user_id')
                  ->constrained('locations')
                  ->onDelete('set null');
        });

        // 4. Add location_id to quotations
        Schema::table('quotations', function (Blueprint $table) {
            $table->foreignId('location_id')
                  ->nullable()
                  ->after('created_by')
                  ->constrained('locations')
                  ->onDelete('set null');
        });

        // 5. Add location_id to invoices
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('location_id')
                  ->nullable()
                  ->after('created_by')
                  ->constrained('locations')
                  ->onDelete('set null');
        });

        // 6. Per-location stock table
        Schema::create('location_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->foreignId('location_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->integer('quantity')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->unique(['product_id', 'location_id']); // one record per product per location
            $table->timestamps();
        });

        // 7. Stock transfer requests
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number')->unique(); // TRF-202601-0001
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->foreignId('from_location_id')->constrained('locations')->onDelete('restrict');
            $table->foreignId('to_location_id')->constrained('locations')->onDelete('restrict');
            $table->integer('quantity_requested');
            $table->integer('quantity_approved')->nullable();
            $table->string('status')->default('pending');
            // pending → approved → dispatched → received → cancelled
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('requested_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('received_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('location_stocks');
        Schema::table('invoices', fn($t) => $t->dropForeign(['location_id']));
        Schema::table('quotations', fn($t) => $t->dropForeign(['location_id']));
        Schema::table('sales', fn($t) => $t->dropForeign(['location_id']));
        Schema::table('users', fn($t) => $t->dropForeign(['location_id']));
        Schema::dropIfExists('locations');
    }
};