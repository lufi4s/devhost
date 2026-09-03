<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * Record an audit log entry.
     */
    public static function log(
        string $action,
        ?string $description = null,
        ?array $context = null,
        ?User $user = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $user?->id ?? Auth::id(),
            'action' => $action,
            'description' => $description,
            'context' => $context,
        ]);
    }
}
