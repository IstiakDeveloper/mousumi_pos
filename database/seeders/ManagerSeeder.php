<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ManagerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create([
            'name' => 'Manager',
            'slug' => 'manager',
            'permissions' => null, // You can add specific permissions if needed
        ]);

        $role = Role::where('slug', 'manager')->first();

        // Create the Manager user
        User::create([
            'name' => 'Zahid Hasan',
            'email' => 'zahidhasan1la@gmail.com',
            'password' => Hash::make('Zahid1la$'),
            'role_id' => $role->id,
            'phone' => null,
            'address' => null,
            'status' => 1,  // Use integer 1 for active status
        ]);


        Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'permissions' => null, // You can add specific permissions if needed
        ]);

        $role = Role::where('slug', 'super-admin')->first();

        // Create the Manager user
        User::create([
            'name' => 'Arun Kumar',
            'email' => 'arun@mail.com',
            'password' => Hash::make('Arun001$'),
            'role_id' => $role->id,
            'phone' => null,
            'address' => null,
            'status' => 1,  // Use integer 1 for active status
        ]);
    }
}
