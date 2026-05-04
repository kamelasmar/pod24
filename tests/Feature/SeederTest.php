<?php
use Spatie\Permission\Models\Role;
it('seeds Admin and Coordinator roles', function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    expect(Role::where('name', 'Admin')->exists())->toBeTrue();
    expect(Role::where('name', 'Coordinator')->exists())->toBeTrue();
});
