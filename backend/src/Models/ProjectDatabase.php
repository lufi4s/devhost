<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDatabase extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'engine',
        'user',
        'password',
        'port',
    ];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
