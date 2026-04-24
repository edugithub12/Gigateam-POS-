<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('subtotal', 12, 2)->default(0)->change();
            $table->decimal('total', 12, 2)->default(0)->change();
            $table->decimal('vat_amount', 12, 2)->default(0)->change();
            $table->decimal('amount_paid', 12, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('subtotal', 12, 2)->change();
            $table->decimal('total', 12, 2)->change();
        });
    }
};