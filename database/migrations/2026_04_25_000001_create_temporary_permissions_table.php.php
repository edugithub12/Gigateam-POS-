<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temporary_permissions', function (Blueprint $table) {
            $table->id();

            // Who is being granted the permission
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');

            // The permission name (matches Spatie permissions)
            $table->string('permission');

            // Optional: scope to a specific model/record
            // e.g. restrict to a specific invoice or job card
            $table->string('scope_model')->nullable();
            $table->unsignedBigInteger('scope_id')->nullable();

            // Who granted it and why
            $table->foreignId('granted_by')
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->text('reason')->nullable();

            // Validity window
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamp('expires_at')->nullable(); // null = permanent grant

            // Status
            $table->enum('status', ['active', 'expired', 'revoked'])->default('active');
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('revocation_reason')->nullable();

            $table->timestamps();

            // Indexes for fast lookups
            $table->index(['user_id', 'status']);
            $table->index(['permission', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temporary_permissions');
    }
};