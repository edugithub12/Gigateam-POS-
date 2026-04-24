<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('individual'); // individual, business, government, school, estate
            $table->string('company_name')->nullable();
            $table->string('contact_person')->nullable(); // for businesses
            $table->string('phone')->nullable();
            $table->string('phone_alt')->nullable();
            $table->string('email')->nullable();
            $table->string('id_number')->nullable(); // National ID or KRA PIN
            $table->text('address')->nullable();
            $table->string('area')->nullable(); // e.g. Westlands, Karen, CBD
            $table->string('city')->default('Nairobi');
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->decimal('outstanding_balance', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};