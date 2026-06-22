<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            // The original product_id now means "the donor's product row"
            // (the one being requested FROM). Rename for clarity.
            $table->renameColumn('product_id', 'donor_product_id');

            // The receiving shop's matching product row — null until
            // they already have a matching product, or until it's
            // auto-created on receipt.
            $table->foreignId('receiver_product_id')->nullable()->after('donor_product_id')
                  ->constrained('products')->nullOnDelete();

            // Whether the receiving shop already had a matching product
            // (matched by barcode/SKU) or one was auto-created on receipt.
            $table->boolean('product_was_auto_created')->default(false)
                  ->after('receiver_product_id');
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropForeign(['receiver_product_id']);
            $table->dropColumn(['receiver_product_id', 'product_was_auto_created']);
            $table->renameColumn('donor_product_id', 'product_id');
        });
    }
};
