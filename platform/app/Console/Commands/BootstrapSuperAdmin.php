<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class BootstrapSuperAdmin extends Command
{
    protected $signature = 'platform:bootstrap-super-admin {--management : Also grant the separate Management role}';

    protected $description = 'Create the first Super Admin account once, without a seeded password';

    public function handle(): int
    {
        $superAdmin = Role::where('slug', 'super-admin')->first();
        if (! $superAdmin) {
            $this->error('System roles are not seeded. Run php artisan db:seed first.');

            return self::FAILURE;
        }

        if ($superAdmin->users()->exists()) {
            $this->error('A Super Admin already exists. Bootstrap is one-time only.');

            return self::FAILURE;
        }

        $name = trim((string) $this->ask('Super Admin name'));
        $email = mb_strtolower(trim((string) $this->ask('Super Admin email')));
        if ($name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || User::where('email', $email)->exists()) {
            $this->error('Provide a valid name and a unique email address.');

            return self::FAILURE;
        }

        $password = (string) $this->secret('Password (at least 12 characters)');
        $confirmation = (string) $this->secret('Confirm password');
        if (mb_strlen($password) < 12 || ! hash_equals($password, $confirmation)) {
            $this->error('Password confirmation failed or the password is shorter than 12 characters.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($name, $email, $password, $superAdmin): void {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'status' => 'active',
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();
            $user->roles()->attach($superAdmin->id, ['assigned_by' => $user->id, 'assigned_at' => now()]);

            if ($this->option('management')) {
                $management = Role::where('slug', 'management')->firstOrFail();
                $user->roles()->attach($management->id, ['assigned_by' => $user->id, 'assigned_at' => now()]);
            }

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'system.super_admin_bootstrapped',
                'entity_type' => 'user',
                'entity_id' => (string) $user->id,
                'new_state' => ['roles' => $this->option('management') ? ['super-admin', 'management'] : ['super-admin']],
                'context' => ['source' => 'one-time CLI bootstrap'],
                'created_at' => now(),
            ]);
        });

        $this->info('Initial Super Admin created. Remove or disable CLI access according to your deployment procedure.');

        return self::SUCCESS;
    }
}
