<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mpesa_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('merchant_request_id')->nullable();
            $table->string('checkout_request_id')->unique();
            $table->string('phone');
            $table->decimal('amount', 12, 2);
            $table->string('reference');          // sale number or invoice number
            $table->string('description')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->string('mpesa_receipt')->nullable();  // M-Pesa transaction code e.g. QKA1BXXXXX
            $table->string('result_code')->nullable();
            $table->string('result_desc')->nullable();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpesa_transactions');
    }
};