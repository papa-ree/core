<?php

namespace Bale\Core\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'core:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Bale Core: Seed roles, permissions, and create root user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting Bale Core installation...');

        $this->seedRolesAndPermissions();
        $this->createRootUser();

        $this->info('Bale Core installation completed successfully!');

        return self::SUCCESS;
    }

    protected function seedRolesAndPermissions(): void
    {
        $this->info('Seeding roles and permissions...');

        // 1. Create Permissions
        $permissions = [
            'user-management.read',
            'user-management.create',
            'user-management.update',
            'user-management.delete',
            'dashboard',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 2. Create Roles
        $roleRoot = Role::firstOrCreate(['name' => 'root']);
        $roleAdmin = Role::firstOrCreate(['name' => 'admin']);
        $roleGuest = Role::firstOrCreate(['name' => 'guest']);

        // 3. Assign Permissions to Roles
        // Role root: all permissions
        $roleRoot->syncPermissions(Permission::all());

        // Role admin: dashboard
        $roleAdmin->syncPermissions(['dashboard']);

        // Role guest: no permissions (already empty by default or sync empty array)
        $roleGuest->syncPermissions([]);

        $this->info('Roles and permissions seeded.');
    }

    protected function createRootUser(): void
    {
        $this->info('Creating Root User...');

        $name = $this->ask('Full Name', 'Root User');
        $username = $this->ask('Username', 'root');
        $email = $this->ask('Email Address', 'root@example.com');
        $password = $this->secret('Password');

        if (empty($password)) {
            $this->error('Password cannot be empty!');
            return;
        }

        $user = User::updateOrCreate(
            ['username' => $username],
            [
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ]
        );

        $user->assignRole('root');

        $this->info("User {$username} created/updated and assigned 'root' role.");
    }
}
