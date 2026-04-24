<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_notes', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_number')->unique(); // DN-202601-0001
            $table->string('type')->default('customer'); // customer, technician
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('technician_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('sale_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('job_card_id')->nullable(); // set after job cards table created
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->string('recipient_name'); // who receives the goods
            $table->string('recipient_phone')->nullable();
            $table->text('delivery_address')->nullable();
            $table->string('site_location')->nullable(); // for technician deliveries
            $table->string('status')->default('pending'); // pending, dispatched, delivered, returned
            $table->text('notes')->nullable();
            $table->string('footer_text')->default('Accounts are due on demand.');
            $table->date('delivery_date')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('delivery_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_note_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('sort_order')->default(0);
            $table->text('description');
            $table->string('unit')->default('pcs');
            $table->integer('quantity');
            $table->text('notes')->nullable(); // e.g. serial number, condition
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_note_items');
        Schema::dropIfExists('delivery_notes');
    }
};