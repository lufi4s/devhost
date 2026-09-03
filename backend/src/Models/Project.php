<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'server_id',
        'name',
        'slug',
        'type',
        'status',
        'runtime',
        'runtime_version',
        'subdomain',
        'domain',
        'hostname',
        'git_repository',
        'git_branch',
        'storage_limit',
        'memory_limit',
        'cpu_limit',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'storage_limit' => 'integer',
            'cpu_limit' => 'integer',
            'status' => 'string',
        ];
    }

    public const TYPES = ['wordpress', 'laravel', 'static', 'node'];

    public const STATUSES = [
        'provisioning',
        'live',
        'stopped',
        'failed',
        'provisioning_failed',
        'deleting',
        'suspended',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(ProjectDomain::class);
    }

    public function databases(): HasMany
    {
        return $this->hasMany(ProjectDatabase::class);
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class)->orderByDesc('number');
    }

    public function latestDeployment(): HasOne
    {
        return $this->hasOne(Deployment::class)->latestOfMany();
    }

    public function environmentVariables(): HasMany
    {
        return $this->hasMany(EnvironmentVariable::class);
    }

    public function isLive(): bool
    {
        return $this->status === 'live';
    }
}
