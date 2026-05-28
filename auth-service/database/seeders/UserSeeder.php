<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
// UserSeeder.php

    public function run(): void
    {
        $usersToSeed = [
            [
                'email' => 'admin@example.com',
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'role_name' => 'IT Admin',
                'department_name' => 'IT'
            ],
            [
                'email' => 'manager@example.com',
                'first_name' => 'Department',
                'last_name' => 'Manager',
                'role_name' => 'Manager',
                'department_name' => 'Operations'
            ],
            [
                'email' => 'sales@example.com',
                'first_name' => 'Sales',
                'last_name' => 'Representative',
                'role_name' => 'Sales',
                'department_name' => 'Operations'
            ],
            [
                'email' => 'finance@example.com',
                'first_name' => 'Finance',
                'last_name' => 'Officer',
                'role_name' => 'Finance Employee',
                'department_name' => 'Finance'
            ],
            [
                'email' => 'finance-admin@example.com',
                'first_name' => 'Finance',
                'last_name' => 'Administrator',
                'role_name' => 'Admin',
                'department_name' => 'Finance'
            ],
            [
                'email' => 'finance-manager@example.com',
                'first_name' => 'Finance',
                'last_name' => 'Manager',
                'role_name' => 'Finance Manager',
                'department_name' => 'Finance'
            ],
            [
                'email' => 'employee@example.com',
                'first_name' => 'General',
                'last_name' => 'Employee',
                'role_name' => 'Employee',
                'department_name' => 'Operations'
            ],
        ];

        foreach ($usersToSeed as $userData) {
            $user = \App\Models\User::firstOrCreate(
                ['email' => $userData['email']],
                ['is_active' => true]
            );

            \App\Models\UserCredential::firstOrCreate(
                ['user_id' => $user->id],
                ['password_hash' => \Illuminate\Support\Facades\Hash::make('password', ['rounds' => 12])]
            );

            $role = \App\Models\Role::where('name', $userData['role_name'])->first();
            $department = \App\Models\Department::where('name', $userData['department_name'])->first();

            if ($role) {
                \App\Models\UserProfile::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'first_name' => $userData['first_name'],
                        'last_name' => $userData['last_name'],
                        'role_id' => $role->id,
                        'department_id' => $department ? $department->id : (\App\Models\Department::first()?->id ?? 1)
                    ]
                );
            }
        }
    }
}
