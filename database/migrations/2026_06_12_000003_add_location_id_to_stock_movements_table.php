<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('location_id')
                  ->nullable()
                  ->after('product_id')
                  ->constrained('locations')
                  ->onDelete('set null');

            $table->index(['product_id', 'location_id']);
            $table->index(['source', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'location_id']);
            $table->dropIndex(['source', 'source_id']);
            $table->dropConstrainedForeignId('location_id');
        });
    }
};