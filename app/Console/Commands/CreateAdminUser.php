<?php
namespace App\Console\Commands;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
class CreateAdminUser extends Command {
    protected $signature = 'pod24:create-admin {email} {name} {password}';
    protected $description = 'Create an Admin user for the Pod24 admin panel';
    public function handle(): int {
        Role::firstOrCreate(['name' => 'Admin']);
        $user = User::firstOrCreate(
            ['email' => $this->argument('email')],
            ['name' => $this->argument('name'), 'password' => Hash::make($this->argument('password')), 'email_verified_at' => now()]
        );
        if (!$user->hasRole('Admin')) $user->assignRole('Admin');
        $this->info("Admin user created/updated: {$user->email}");
        return self::SUCCESS;
    }
}
