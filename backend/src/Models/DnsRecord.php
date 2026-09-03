<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DnsRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_domain_id', 'name', 'type', 'value', 'ttl', 'priority', 'provider_ref',
    ];

    protected function casts(): array
    {
        return [
            'ttl' => 'integer:0',
            'priority' => 'integer:0',
        ];
    }

    public function customerDomain(): BelongsTo
    {
        return $this->belongsTo(CustomerDomain::class, 'customer_domain_id');
    }
}
