<?php
use App\Models\User;
use Spatie\Permission\Models\Role;

it('blocks non-admin users from /admin', function () {
    Role::firstOrCreate(['name' => 'Admin']);
    Role::firstOrCreate(['name' => 'Coordinator']);
    $regular = User::factory()->create();
    $this->actingAs($regular)->get('/admin')->assertForbidden();
});

it('allows Admin role users into /admin', function () {
    Role::firstOrCreate(['name' => 'Admin']);
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $this->actingAs($admin)->get('/admin')->assertOk();
});

it('allows Coordinator role users into /admin', function () {
    Role::firstOrCreate(['name' => 'Coordinator']);
    $user = User::factory()->create();
    $user->assignRole('Coordinator');
    $this->actingAs($user)->get('/admin')->assertOk();
});
