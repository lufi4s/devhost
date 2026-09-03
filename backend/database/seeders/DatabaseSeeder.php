<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
        ]);

        // Default super admin. Password is dev-only; change in production.
        $admin = User::firstOrWhere('email', 'admin@devhost.local');

        if (! $admin) {
            $admin = User::create([
                'name' => 'Admin',
                'email' => 'admin@devhost.local',
                'password' => Hash::make('password'),
            ]);
            $admin->assignRole('super_admin');

            AuditService::log('user.created', 'Created default super admin', [
                'user_id' => $admin->id,
            ], $admin);
        }
    }
}
