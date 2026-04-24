<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('sale_number')->unique(); // SAL-202601-0001
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->constrained()->onDelete('restrict'); // salesperson
            $table->foreignId('quotation_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0); // 16%
            $table->decimal('total', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('change_given', 12, 2)->default(0);
            $table->string('payment_status')->default('unpaid'); // unpaid, partial, paid
            $table->string('sale_type')->default('walk_in'); // walk_in, quotation_conversion, phone_order
            $table->boolean('include_vat')->default(false);
            $table->text('notes')->nullable();
            $table->string('footer_text')->default('Accounts are due on demand.');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
            $table->string('product_name'); // snapshot at time of sale
            $table->string('product_sku')->nullable();
            $table->string('unit')->default('pcs');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->integer('quantity');
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->boolean('needs_installation')->default(false);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->string('method'); // cash, mpesa, bank_transfer, card, cheque, credit
            $table->string('reference')->nullable(); // Mpesa code, cheque no, bank ref
            $table->string('status')->default('completed'); // completed, failed, reversed
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};