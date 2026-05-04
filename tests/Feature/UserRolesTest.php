<?php
use App\Models\User;
use Spatie\Permission\Models\Role;
it('can assign a role to a user', function () {
    Role::create(['name' => 'Admin']);
    $user = User::factory()->create();
    $user->assignRole('Admin');
    expect($user->hasRole('Admin'))->toBeTrue();
});
