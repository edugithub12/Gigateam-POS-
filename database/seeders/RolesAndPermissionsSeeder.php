<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Products
            'view products', 'create products', 'edit products', 'delete products',
            'view categories', 'create categories', 'edit categories', 'delete categories',

            // Customers
            'view customers', 'create customers', 'edit customers', 'delete customers',

            // Sales
            'view sales', 'create sales', 'edit sales', 'delete sales',
            'access pos',

            // Quotations
            'view quotations', 'create quotations', 'edit quotations', 'delete quotations',
            'approve quotations',

            // Invoices
            'view invoices', 'create invoices', 'edit invoices', 'delete invoices',

            // Delivery Notes
            'view delivery notes', 'create delivery notes', 'edit delivery notes', 'delete delivery notes',

            // Job Cards
            'view job cards', 'create job cards', 'edit job cards', 'delete job cards',
            'update job status',

            // Suppliers
            'view suppliers', 'create suppliers', 'edit suppliers', 'delete suppliers',

            // Reports
            'view reports', 'export reports',

            // Staff
            'view staff', 'create staff', 'edit staff', 'delete staff',

            // Technicians
            'view technicians', 'create technicians', 'edit technicians', 'delete technicians',

            // Settings
            'view settings', 'edit settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ── Admin — everything ─────────────────────────────────────────────────
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());

        // ── Accountant — finance & reporting ──────────────────────────────────
        $accountant = Role::firstOrCreate(['name' => 'accountant']);
        $accountant->syncPermissions([
            'view products',
            'view categories',
            'view customers', 'create customers', 'edit customers',
            'view sales',
            'view quotations',
            'view invoices', 'create invoices', 'edit invoices',
            'view delivery notes',
            'view suppliers',
            'view reports', 'export reports',
        ]);

        // ── Salesperson / Cashier — sales & POS ───────────────────────────────
        $salesperson = Role::firstOrCreate(['name' => 'salesperson']);
        $salesperson->syncPermissions([
            'view products',
            'view customers', 'create customers', 'edit customers',
            'view sales', 'create sales',
            'access pos',
            'view quotations', 'create quotations', 'edit quotations',
            'view invoices', 'create invoices',
            'view delivery notes', 'create delivery notes', 'edit delivery notes',
        ]);

        // ── Technician — field work ────────────────────────────────────────────
        $technician = Role::firstOrCreate(['name' => 'technician']);
        $technician->syncPermissions([
            'view job cards', 'edit job cards', 'update job status',
            'view delivery notes',
        ]);

        echo "✓ Roles and permissions updated\n";
        echo "  Admin: all permissions\n";
        echo "  Accountant: finance & reporting\n";
        echo "  Salesperson: POS + sales + quotations\n";
        echo "  Technician: job cards + delivery notes\n";
    }
}