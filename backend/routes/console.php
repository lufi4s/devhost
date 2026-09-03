<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspiring', function () {
    $this->comment(Inspiring::inspiring());
})->purpose('Display an inspiring quote');

Artisan::command('devhost:seed-permissions', function () {
    $this->components->info('Seeding permissions, roles, and default users...');
    (new \Database\Seeders\PermissionSeeder())->run();
})->purpose('Seed permissions, roles, and the default super admin');

Artisan::command('devhost:create-user', function () {
    $name = $this->ask('Name');
    $email = $this->ask('Email');
    $password = $this->secret('Password');

    \App\Services\UserService::createSuperAdmin($name, $email, $password);

    $this->components->info("User {$email} created as super_admin.");
})->purpose('Create the first super admin user');
