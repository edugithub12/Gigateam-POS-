<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();

            // Role scoped to this specific shop — a user can be manager at Shop A
            // and cashier at Shop B. Spatie roles remain global; this adds shop context.
            $table->string('shop_role')->default('cashier');
            // Values: shop_manager | cashier | stock_clerk

            $table->boolean('is_primary')->default(false);
            // Which shop the user lands on by default at login

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'location_id']);

            $table->index('location_id');
            $table->index(['user_id', 'is_primary']);
        });

        // ── Migrate existing data ────────────────────────────────────────────
        // Every user who already has a location_id gets seeded into the pivot
        // as their primary shop with their current Spatie role mapped across.
        $users = DB::table('users')
            ->whereNotNull('location_id')
            ->get(['id', 'location_id']);

        foreach ($users as $user) {
            // Fetch their Spatie role name to map to a shop_role
            $roleRow = DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('model_has_roles.model_id', $user->id)
                ->where('model_has_roles.model_type', 'App\\Models\\User')
                ->first(['roles.name']);

            $shopRole = match ($roleRow?->name) {
                'super_admin', 'admin'   => 'shop_manager',
                'manager', 'shop_manager' => 'shop_manager',
                'stock_clerk'            => 'stock_clerk',
                default                  => 'cashier',
            };

            DB::table('location_user')->insertOrIgnore([
                'user_id'     => $user->id,
                'location_id' => $user->location_id,
                'shop_role'   => $shopRole,
                'is_primary'  => true,
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('location_user');
    }
};
