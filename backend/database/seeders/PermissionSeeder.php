<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Providers\PermissionServiceProvider;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        PermissionServiceProvider::seed();
    }
}
