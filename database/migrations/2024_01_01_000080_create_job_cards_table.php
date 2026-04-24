<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_cards', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->unique(); // JOB-202601-0001
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('technician_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('sale_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('quotation_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->string('client_name');
            $table->string('client_phone')->nullable();
            $table->text('site_address');
            $table->string('site_area')->nullable(); // e.g. Westlands, Karen
            $table->string('job_type'); // installation, maintenance, repair, survey, upgrade
            $table->string('category'); // CCTV, Electric Fencing, Biometric, Alarm, Access Control, Networking
            $table->text('work_description'); // what needs to be done
            $table->text('work_done')->nullable(); // what was actually done — filled by technician
            $table->string('status')->default('pending');
            // pending → scheduled → in_progress → completed → invoiced → cancelled
            $table->date('scheduled_date')->nullable();
            $table->time('scheduled_time')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('labour_cost', 12, 2)->default(0);
            $table->decimal('transport_cost', 12, 2)->default(0);
            $table->text('technician_notes')->nullable(); // private notes from technician
            $table->text('client_signature')->nullable(); // base64 or path to signature
            $table->boolean('client_satisfied')->nullable(); // filled on completion
            $table->text('follow_up_notes')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_card_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
            $table->string('description');
            $table->string('unit')->default('pcs');
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('source')->default('shop'); // shop, site (materials already on site)
            $table->timestamps();
        });

        // Now add the foreign key to delivery_notes
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->foreign('job_card_id')->references('id')->on('job_cards')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->dropForeign(['job_card_id']);
        });
        Schema::dropIfExists('job_card_items');
        Schema::dropIfExists('job_cards');
    }
};