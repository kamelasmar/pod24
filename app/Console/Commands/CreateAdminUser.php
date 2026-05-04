<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateAdminUser extends Command
{
    protected $signature = 'pod24:create-admin {email} {name} {--password=}';

    protected $description = 'Create or update an Admin user. Prompts for password securely if --password is omitted.';

    public function handle(): int
    {
        Role::firstOrCreate(['name' => 'Admin']);

        $password = $this->option('password') ?: $this->secret('Password');

        $user = User::updateOrCreate(
            ['email' => $this->argument('email')],
            [
                'name' => $this->argument('name'),
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        if (! $user->hasRole('Admin')) {
            $user->assignRole('Admin');
        }

        $this->info("Admin user ensured: {$user->email}");

        return self::SUCCESS;
    }
}
