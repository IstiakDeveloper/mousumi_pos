<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure the 'admin' role exists
        $role = Role::firstOrCreate(['name' => 'admin']);

        // Create Super Admin User
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@example.com'], // Ensure the user is unique by email
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'password' => Hash::make('password123'), // Change this to a secure password
                'role_id' => $role->id,
                'phone' => null,
                'address' => null,
                'status' => 1,
            ]
        );

    }
}
