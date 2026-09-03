<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'currency', 'price', 'billing_cycle',
        'storage_limit', 'memory_limit', 'cpu_limit', 'bandwidth_limit',
        'websites_limit', 'databases_limit', 'mailboxes_limit', 'domains_limit', 'email_storage',
        'node_enabled', 'laravel_enabled', 'wordpress_enabled', 'php_enabled',
        'static_enabled', 'backup_enabled', 'sftp_enabled', 'redis_enabled',
        'ssl_auto', 'websites_used', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'storage_limit' => 'integer:0',
            'memory_limit' => 'integer:0',
            'cpu_limit' => 'integer:0',
            'bandwidth_limit' => 'integer:0',
            'websites_limit' => 'integer:0',
            'databases_limit' => 'integer:0',
            'mailboxes_limit' => 'integer:0',
            'domains_limit' => 'integer:0',
            'email_storage' => 'integer:0',
            'websites_used' => 'integer:0',
            'sort_order' => 'integer:0',
            'node_enabled' => 'boolean',
            'laravel_enabled' => 'boolean',
            'wordpress_enabled' => 'boolean',
            'php_enabled' => 'boolean',
            'static_enabled' => 'boolean',
            'backup_enabled' => 'boolean',
            'sftp_enabled' => 'boolean',
            'redis_enabled' => 'boolean',
            'ssl_auto' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
