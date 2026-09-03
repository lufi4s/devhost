<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerDomain extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'name', 'verification_token', 'verified', 'primary',
        'nameserver_managed', 'nameserver_1', 'nameserver_2', 'ssl_status',
        'ssl_issued_at', 'ssl_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'verified' => 'boolean',
            'primary' => 'boolean',
            'nameserver_managed' => 'boolean',
            'ssl_status' => 'string',
            'ssl_issued_at' => 'datetime',
            'ssl_expires_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function dnsRecords(): HasMany
    {
        return $this->hasMany(DnsRecord::class);
    }
}
