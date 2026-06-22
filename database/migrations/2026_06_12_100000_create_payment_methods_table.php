<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');              // "Cash", "M-Pesa", "Bank Transfer"
            $table->string('code')->unique();    // cash, mpesa, bank_transfer
            $table->string('icon')->nullable();  // emoji or short label
            $table->boolean('requires_reference')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed with Cash only — others added via admin panel
        DB::table('payment_methods')->insert([
            'name'                => 'Cash',
            'code'                => 'cash',
            'icon'                => '💵',
            'requires_reference'  => false,
            'is_active'           => true,
            'sort_order'          => 0,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};