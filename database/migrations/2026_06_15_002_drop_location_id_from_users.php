<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop FK constraint if it exists
        $fk = DB::select("
            SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'users'
            AND CONSTRAINT_NAME = 'users_location_id_foreign'
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");
        if (!empty($fk)) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['location_id']);
            });
        }

        // Drop index if it exists
        $index = DB::select("SHOW INDEX FROM users WHERE Key_name = 'users_location_id_index'");
        if (!empty($index)) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['location_id']);
            });
        }

        // Drop column if it exists
        if (Schema::hasColumn('users', 'location_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('location_id');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('users', 'location_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('location_id')->nullable()->after('is_active')
                      ->constrained()->nullOnDelete();
            });
        }
    }
};