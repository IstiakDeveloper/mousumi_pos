<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'permissions' => null, // You can add specific permissions if needed
        ]);
        $role = Role::where('slug', 'super-admin')->first();

        // Create the Super Admin user
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@mail.com',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'phone' => null,
            'address' => null,
            'status' => 1,  // Use integer 1 for active status
        ]);

        $this->call([
            UnitSeeder::class,
            CategorySeeder::class,
        ]);
    }

}
