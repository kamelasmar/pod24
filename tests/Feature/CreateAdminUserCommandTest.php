<?php

use App\Models\User;

it('creates a new admin user with the Admin role', function () {
    $exit = \Illuminate\Support\Facades\Artisan::call('pod24:create-admin', [
        'email' => 'newadmin@pod24.local',
        'name' => 'New Admin',
        '--password' => 'changeme123',
    ]);

    expect($exit)->toBe(0);

    $user = User::where('email', 'newadmin@pod24.local')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('Admin'))->toBeTrue();
});

it('updates an existing user when re-run', function () {
    \Illuminate\Support\Facades\Artisan::call('pod24:create-admin', [
        'email' => 'existing@pod24.local',
        'name' => 'Original Name',
        '--password' => 'original-pw',
    ]);

    $original = User::where('email', 'existing@pod24.local')->first();
    $originalHash = $original->password;

    \Illuminate\Support\Facades\Artisan::call('pod24:create-admin', [
        'email' => 'existing@pod24.local',
        'name' => 'Updated Name',
        '--password' => 'new-pw',
    ]);

    $updated = User::where('email', 'existing@pod24.local')->first();
    expect($updated->name)->toBe('Updated Name');
    expect($updated->password)->not->toBe($originalHash);
});
