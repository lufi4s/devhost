<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\ServiceProvider;

class PermissionServiceProvider extends ServiceProvider
{
    /**
     * Register permissions and roles. Safe to run on every boot because
     * permissions/roles are created idempotently.
     */
    public function boot(): void
    {
        // Only seed if the database tables already exist. This provider runs on
        // every boot (including before `migrate` has been executed), so guard the
        // seed against a missing table to avoid crashing the container on startup.
        if ($this->app->runningInConsole() && $this->hasPermissionTables()) {
            $this->seed();
        }
    }

    private function hasPermissionTables(): bool
    {
        try {
            return \Illuminate\Support\Facades\Schema::hasTable('permissions')
                && \Illuminate\Support\Facades\Schema::hasTable('roles')
                && \Illuminate\Support\Facades\Schema::hasTable('model_has_permissions')
                && \Illuminate\Support\Facades\Schema::hasTable('model_has_roles')
                && \Illuminate\Support\Facades\Schema::hasTable('role_has_permissions');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function seed(): void
    {
        $permissions = [
            'projects.create',
            'projects.edit',
            'projects.delete',
            'projects.view',
            'users.view',
            'users.manage',
            'audit.view',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['slug' => $perm], [
                'name' => $perm,
                'guard_name' => 'web',
            ]);
        }

        $superAdmin = Role::firstOrCreate(['slug' => 'super_admin'], [
            'name' => 'Super Admin',
            'description' => 'Full access to everything.',
        ]);
        $superAdmin->syncPermissions(Permission::all());

        $admin = Role::firstOrCreate(['slug' => 'admin'], [
            'name' => 'Admin',
            'description' => 'Manage projects and users, minus super-admin actions.',
        ]);
        $admin->syncPermissions(Permission::whereIn('slug', [
            'projects.create', 'projects.edit', 'projects.delete', 'projects.view',
            'users.view', 'audit.view',
        ])->get());

        $developer = Role::firstOrCreate(['slug' => 'developer'], [
            'name' => 'Developer',
            'description' => 'Create and manage own projects.',
        ]);
        $developer->syncPermissions(Permission::whereIn('slug', [
            'projects.create', 'projects.edit', 'projects.view',
        ])->get());
    }
}
