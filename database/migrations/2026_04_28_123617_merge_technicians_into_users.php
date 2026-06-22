<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1 — Add technician fields to users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('specialization')->nullable()->after('phone');
            $table->string('technician_status')->nullable()->after('specialization'); // active, inactive, on_leave
            $table->string('id_number')->nullable()->after('technician_status');
            $table->text('technician_notes')->nullable()->after('id_number');
        });

        // Step 2 — Copy technician data into users where user_id is linked
        $technicians = DB::table('technicians')->whereNotNull('user_id')->get();
        foreach ($technicians as $tech) {
            DB::table('users')->where('id', $tech->user_id)->update([
                'specialization'    => $tech->specialization,
                'technician_status' => $tech->status,
                'id_number'         => $tech->id_number,
                'technician_notes'  => $tech->notes,
                'phone'             => DB::table('users')->where('id', $tech->user_id)->value('phone') ?? $tech->phone,
            ]);
        }

        // Step 3 — Update job_cards.technician_id to point to users
        // For each job card, find the technician's user_id and update
        $jobCards = DB::table('job_cards')->whereNotNull('technician_id')->get();
        foreach ($jobCards as $job) {
            $tech = DB::table('technicians')->find($job->technician_id);
            if ($tech && $tech->user_id) {
                DB::table('job_cards')->where('id', $job->id)
                    ->update(['technician_id' => $tech->user_id]);
            } else {
                // Technician has no linked user — set null
                DB::table('job_cards')->where('id', $job->id)
                    ->update(['technician_id' => null]);
            }
        }

        // Step 4 — Drop old foreign key and add new one pointing to users
        Schema::table('job_cards', function (Blueprint $table) {
            $table->dropForeign(['technician_id']);
            $table->foreign('technician_id')->references('id')->on('users')->onDelete('set null');
        });

        // Step 5 — Do the same for delivery_notes if it has technician_id
        if (Schema::hasColumn('delivery_notes', 'technician_id')) {
            $deliveryNotes = DB::table('delivery_notes')->whereNotNull('technician_id')->get();
            foreach ($deliveryNotes as $dn) {
                $tech = DB::table('technicians')->find($dn->technician_id);
                if ($tech && $tech->user_id) {
                    DB::table('delivery_notes')->where('id', $dn->id)
                        ->update(['technician_id' => $tech->user_id]);
                } else {
                    DB::table('delivery_notes')->where('id', $dn->id)
                        ->update(['technician_id' => null]);
                }
            }

            Schema::table('delivery_notes', function (Blueprint $table) {
                $table->dropForeign(['technician_id']);
                $table->foreign('technician_id')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['specialization', 'technician_status', 'id_number', 'technician_notes']);
        });
    }
};