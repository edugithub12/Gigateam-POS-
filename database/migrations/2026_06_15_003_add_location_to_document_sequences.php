<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_sequences', function (Blueprint $table) {
            // Drop the old unique constraint on type alone — type+location will
            // now be the unique key (each shop has its own SAL, INV, QUO, etc.)
            $table->dropUnique(['type']);

            $table->foreignId('location_id')->nullable()->after('id')
                  ->constrained()->nullOnDelete();
            // null = global/shared sequence (used by super_admin only)

            $table->unique(['type', 'location_id'], 'doc_seq_type_location_unique');
            $table->index('location_id');
        });

        // ── Seed per-shop sequences from existing global ones ────────────────
        // Copy each existing global sequence row into one row per active shop,
        // starting their counters at 0 so each shop begins fresh.
        $locations = DB::table('locations')->where('is_active', true)->get();
        $globalSeqs = DB::table('document_sequences')->whereNull('location_id')->get();

        foreach ($globalSeqs as $seq) {
            foreach ($locations as $location) {
                $exists = DB::table('document_sequences')
                    ->where('type', $seq->type)
                    ->where('location_id', $location->id)
                    ->exists();

                if (! $exists) {
                    DB::table('document_sequences')->insert([
                        'location_id' => $location->id,
                        'type'        => $seq->type,
                        'prefix'      => $seq->prefix,
                        'last_number' => 0,
                        'padding'     => $seq->padding,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('document_sequences', function (Blueprint $table) {
            $table->dropUnique('doc_seq_type_location_unique');
            $table->dropForeign(['location_id']);
            $table->dropIndex(['location_id']);
            $table->dropColumn('location_id');
            $table->unique('type');
        });
    }
};
