<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    public function test_admin_can_access_users_index()
    {
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $admin = User::firstOrCreate(
            ['username' => 'admin_test_view'],
            ['name' => 'Admin View', 'email' => 'admin_test_view@mail.com', 'password' => Hash::make('password'), 'role_id' => $adminRole->id, 'status' => 1]
        );

        $response = $this->actingAs($admin)->get(route('admin.users.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_create_user_with_username()
    {
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $admin = User::firstOrCreate(
            ['username' => 'admin_test_creator'],
            ['name' => 'Admin Creator', 'email' => 'admin_test_creator@mail.com', 'password' => Hash::make('password'), 'role_id' => $adminRole->id, 'status' => 1]
        );

        $unique = uniqid();
        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Staff User',
            'username' => 'staff_' . $unique,
            'email' => 'staff_' . $unique . '@mail.com',
            'phone' => '01711000000',
            'role_id' => $adminRole->id,
            'password' => 'secret123',
            'status' => true,
        ]);

        $response->assertSessionHas('success');

        $created = User::where('username', 'staff_' . $unique)->first();
        $this->assertNotNull($created);
        $this->assertEquals('New Staff User', $created->name);
        $this->assertTrue(Hash::check('secret123', $created->password));
    }

    public function test_admin_can_update_user()
    {
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $admin = User::firstOrCreate(
            ['username' => 'admin_test_updater'],
            ['name' => 'Admin Updater', 'email' => 'admin_test_updater@mail.com', 'password' => Hash::make('password'), 'role_id' => $adminRole->id, 'status' => 1]
        );

        $unique = uniqid();
        $user = User::create([
            'name' => 'To Update',
            'username' => 'upd_' . $unique,
            'email' => 'upd_' . $unique . '@mail.com',
            'role_id' => $adminRole->id,
            'password' => Hash::make('password'),
            'status' => 1,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $user->id), [
            'name' => 'Updated Name',
            'username' => 'upd_' . $unique,
            'email' => 'upd_' . $unique . '@mail.com',
            'role_id' => $adminRole->id,
            'status' => false,
        ]);

        $response->assertSessionHas('success');
        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
        $this->assertFalse((bool) $user->status);
    }

    public function test_user_cannot_delete_self()
    {
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $admin = User::firstOrCreate(
            ['username' => 'admin_test_nodelete'],
            ['name' => 'Admin NoDelete', 'email' => 'admin_test_nodelete@mail.com', 'password' => Hash::make('password'), 'role_id' => $adminRole->id, 'status' => 1]
        );

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin->id));
        $response->assertSessionHas('error');
        $this->assertNotNull(User::find($admin->id));
    }
}

