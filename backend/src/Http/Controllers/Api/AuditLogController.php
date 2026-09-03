<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * GET /api/audit-logs  (admin only)
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json(AuditLog::with('user:name,email')
            ->latest('created_at')
            ->paginate($request->integer('per_page', 50)));
    }
}
