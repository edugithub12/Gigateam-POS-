<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // System settings — company info, document settings
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();
        });

        // Internal notifications (e.g. quotation awaiting approval)
        Schema::create('system_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // recipient
            $table->string('type'); // quotation_pending, low_stock, job_assigned, payment_received
            $table->string('title');
            $table->text('message');
            $table->string('action_url')->nullable(); // link to the relevant record
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('related_type')->nullable(); // Quotation, JobCard, etc.
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        // Document numbering sequences
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique(); // quotation, sale, invoice, delivery_note, job_card, purchase_order
            $table->string('prefix');
            $table->integer('last_number')->default(0);
            $table->integer('padding')->default(4); // e.g. 4 = 0001
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
        Schema::dropIfExists('system_notifications');
        Schema::dropIfExists('settings');
    }
};