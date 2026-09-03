<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Server extends Model
{
    protected $fillable = [
        'name',
        'ip',
        'agent_url',
        'agent_token',
        'status',
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
