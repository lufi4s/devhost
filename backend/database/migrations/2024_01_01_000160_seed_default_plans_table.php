<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $plans = [
            [
                'name' => 'Starter', 'slug' => 'starter', 'currency' => 'USD',
                'price' => 5, 'billing_cycle' => 'monthly',
                'storage_limit' => 5120, 'memory_limit' => 1024, 'cpu_limit' => 1,
                'bandwidth_limit' => 50, 'websites_limit' => 3, 'databases_limit' => 2,
                'mailboxes_limit' => 2, 'domains_limit' => 3, 'email_storage' => 512,
                'node_enabled' => true, 'laravel_enabled' => true, 'wordpress_enabled' => true,
                'php_enabled' => true, 'static_enabled' => true,
                'backup_enabled' => false, 'sftp_enabled' => false, 'redis_enabled' => false,
                'ssl_auto' => true, 'sort_order' => 1,
            ],
            [
                'name' => 'Growth', 'slug' => 'growth', 'currency' => 'USD',
                'price' => 15, 'billing_cycle' => 'monthly',
                'storage_limit' => 20480, 'memory_limit' => 4096, 'cpu_limit' => 2,
                'bandwidth_limit' => 200, 'websites_limit' => 10, 'databases_limit' => 5,
                'mailboxes_limit' => 10, 'domains_limit' => 10, 'email_storage' => 1024,
                'node_enabled' => true, 'laravel_enabled' => true, 'wordpress_enabled' => true,
                'php_enabled' => true, 'static_enabled' => true,
                'backup_enabled' => true, 'sftp_enabled' => true, 'redis_enabled' => true,
                'ssl_auto' => true, 'sort_order' => 2,
            ],
            [
                'name' => 'Pro', 'slug' => 'pro', 'currency' => 'USD',
                'price' => 40, 'billing_cycle' => 'monthly',
                'storage_limit' => 51200, 'memory_limit' => 8192, 'cpu_limit' => 4,
                'bandwidth_limit' => 500, 'websites_limit' => 50, 'databases_limit' => 20,
                'mailboxes_limit' => 50, 'domains_limit' => 50, 'email_storage' => 2048,
                'node_enabled' => true, 'laravel_enabled' => true, 'wordpress_enabled' => true,
                'php_enabled' => true, 'static_enabled' => true,
                'backup_enabled' => true, 'sftp_enabled' => true, 'redis_enabled' => true,
                'ssl_auto' => true, 'sort_order' => 3,
            ],
            [
                'name' => 'Business', 'slug' => 'business', 'currency' => 'USD',
                'price' => 100, 'billing_cycle' => 'monthly',
                'storage_limit' => 102400, 'memory_limit' => 16384, 'cpu_limit' => 8,
                'bandwidth_limit' => 1000, 'websites_limit' => 100, 'databases_limit' => 50,
                'mailboxes_limit' => 100, 'domains_limit' => 0, 'email_storage' => 4096,
                'node_enabled' => true, 'laravel_enabled' => true, 'wordpress_enabled' => true,
                'php_enabled' => true, 'static_enabled' => true,
                'backup_enabled' => true, 'sftp_enabled' => true, 'redis_enabled' => true,
                'ssl_auto' => true, 'sort_order' => 4,
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('plans')->upsert($plan, ['slug'], [
                'name', 'currency', 'price', 'billing_cycle', 'storage_limit',
                'memory_limit', 'cpu_limit', 'bandwidth_limit', 'websites_limit',
                'databases_limit', 'mailboxes_limit', 'domains_limit', 'email_storage', 'node_enabled',
                'laravel_enabled', 'wordpress_enabled', 'php_enabled', 'static_enabled',
                'backup_enabled', 'sftp_enabled', 'redis_enabled', 'ssl_auto', 'sort_order',
            ]);
        }
    }

    public function down(): void
    {
        DB::table('plans')->delete();
    }
};
