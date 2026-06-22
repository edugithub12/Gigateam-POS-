<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('document_sequences')->insertOrIgnore([
            'type'        => 'transfer',
            'last_number' => 0,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('document_sequences')->where('type', 'transfer')->delete();
    }
};