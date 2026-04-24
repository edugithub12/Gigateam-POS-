<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $staff = [
            [
                'name'     => 'Admin',
                'email'    => 'admin@gigateam.co.ke',
                'phone'    => '+254 111292948',
                'password' => Hash::make('Gigateam@2024'),
                'role'     => 'admin',
            ],
            [
                'name'     => 'Accountant',
                'email'    => 'accounts@gigateam.co.ke',
                'phone'    => '',
                'password' => Hash::make('Gigateam@2024'),
                'role'     => 'accountant',
            ],
            [
                'name'     => 'Sales Person',
                'email'    => 'sales@gigateam.co.ke',
                'phone'    => '',
                'password' => Hash::make('Gigateam@2024'),
                'role'     => 'salesperson',
            ],
            [
                'name'     => 'Technician',
                'email'    => 'tech@gigateam.co.ke',
                'phone'    => '',
                'password' => Hash::make('Gigateam@2024'),
                'role'     => 'technician',
            ],
        ];

        foreach ($staff as $member) {
            $user = User::updateOrCreate(
                ['email' => $member['email']],
                [
                    'name'      => $member['name'],
                    'phone'     => $member['phone'],
                    'password'  => $member['password'],
                    'is_active' => true,
                ]
            );
            $user->syncRoles([$member['role']]);
            echo "✓ {$member['role']}: {$member['email']} / password: Gigateam@2024\n";
        }
    }
}