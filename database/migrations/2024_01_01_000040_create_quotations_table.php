<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number')->unique(); // QT-202601-0001
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('client_name'); // snapshot — in case no customer record
            $table->string('client_phone')->nullable();
            $table->string('client_email')->nullable();
            $table->text('client_address')->nullable();
            $table->string('site_location')->nullable(); // where installation will happen
            $table->string('status')->default('draft');
            // draft → pending_approval → approved → sent → accepted → rejected → converted
            $table->text('notes')->nullable();
            $table->text('terms')->nullable(); // editable per quotation
            $table->string('footer_text')->default('Accounts are due on demand.');
            $table->boolean('include_vat')->default(false);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->date('valid_until')->nullable();
            $table->timestamp('submitted_at')->nullable(); // when sent for approval
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('sent_at')->nullable(); // when sent to customer
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('sort_order')->default(0);
            $table->text('description'); // free text — can differ from product name
            $table->string('unit')->default('pcs');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('cost_price', 12, 2)->default(0); // for margin tracking
            $table->integer('quantity');
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
    }
};