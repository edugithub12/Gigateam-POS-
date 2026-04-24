<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technicians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->string('phone');
            $table->string('phone_alt')->nullable();
            $table->string('email')->nullable();
            $table->string('id_number')->nullable();
            $table->string('specialization')->nullable(); // CCTV, Electric Fencing, Biometric, Alarms, General
            $table->string('status')->default('active'); // active, inactive, on_leave
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technicians');
    }
};