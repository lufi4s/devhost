<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public static function createSuperAdmin(string $name, string $email, string $password): User
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            $admin = Role::where('slug', 'super_admin')->first();

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'role_id' => $admin?->id,
            ]);
        }

        $admin = Role::where('slug', 'super_admin')->first();
        if ($admin && ! $user->roles->contains($admin)) {
            $user->assignRole($admin);
        }

        return $user;
    }

    public static function createDeveloper(string $name, string $email, string $password): User
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            $developer = Role::where('slug', 'developer')->first();

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'role_id' => $developer?->id,
            ]);
        }

        $developer = Role::where('slug', 'developer')->first();
        if ($developer && ! $user->roles->contains($developer)) {
            $user->assignRole($developer);
        }

        return $user;
    }
}
