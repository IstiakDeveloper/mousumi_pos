<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UsernameLoginTest extends TestCase
{
    public function test_user_can_login_with_username()
    {
        $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $user = User::firstOrCreate(
            ['username' => 'testuserlogin'],
            [
                'name' => 'Test User Login',
                'email' => 'testuserlogin@example.com',
                'password' => Hash::make('secret123'),
                'role_id' => $role->id,
                'status' => 1,
            ]
        );

        $response = $this->post(route('login'), [
            'login' => 'testuserlogin',
            'password' => 'secret123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_user_can_login_with_email()
    {
        $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $user = User::firstOrCreate(
            ['username' => 'testuseremail'],
            [
                'name' => 'Test User Email',
                'email' => 'testuseremail@example.com',
                'password' => Hash::make('secret123'),
                'role_id' => $role->id,
                'status' => 1,
            ]
        );

        $response = $this->post(route('login'), [
            'login' => 'testuseremail@example.com',
            'password' => 'secret123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }
}
