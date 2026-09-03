<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\AuditService;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    public function __construct(private PlanService $planService)
    {
    }

    /**
     * GET /api/plans
     * Active plans available for customers to subscribe to.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'plans' => Plan::active()->get(),
        ]);
    }

    /**
     * GET /api/plans/{plan}
     */
    public function show(Plan $plan): JsonResponse
    {
        return response()->json(['plan' => $plan]);
    }

    /**
     * POST /api/plans
     * Admin-only. Create a hosting plan.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePlan($request);

        $plan = Plan::create([
            ...$data,
            'slug' => Str::slug($data['name']) . '-' . Str::random(4),
        ]);

        AuditService::log('plan.created', "Created plan \"{$plan->name}\"", [
            'plan_id' => $plan->id,
            'slug' => $plan->slug,
        ], Auth::user());

        return response()->json(['plan' => $plan], 201);
    }

    /**
     * PATCH /api/plans/{plan}
     * Admin-only. Update a hosting plan.
     */
    public function update(Request $request, Plan $plan): JsonResponse
    {
        $data = $this->validatePlan($request, $plan->id);

        $plan->update($data);

        AuditService::log('plan.updated', "Updated plan \"{$plan->name}\"", [
            'plan_id' => $plan->id,
            'changes' => $data,
        ], Auth::user());

        return response()->json(['plan' => $plan]);
    }

    /**
     * DELETE /api/plans/{plan}
     * Admin-only. Delete a plan (only if it has no subscriptions).
     */
    public function destroy(Plan $plan): JsonResponse
    {
        if ($plan->subscriptions()->exists()) {
            return response()->json([
                'message' => 'This plan cannot be deleted because customers are subscribed to it.',
            ], 422);
        }

        $name = $plan->name;
        $plan->delete();

        AuditService::log('plan.deleted', "Deleted plan \"{$name}\"", [
            'plan_id' => $plan->id,
        ], Auth::user());

        return response()->json(['message' => 'Plan deleted.']);
    }

    /**
     * Validate and normalize a plan payload into fillable attributes.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function validatePlan(Request $request, ?int $exceptId = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:63', 'unique:plans,name,' . $exceptId],
            'billing_cycle' => ['sometimes', 'in:monthly,quarterly,annually'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'max:3'],
            'storage_limit' => ['sometimes', 'integer', 'min:0'],
            'memory_limit' => ['sometimes', 'integer', 'min:0'],
            'cpu_limit' => ['sometimes', 'integer', 'min:0'],
            'bandwidth_limit' => ['sometimes', 'integer', 'min:0'],
            'websites_limit' => ['sometimes', 'integer', 'min:0'],
            'databases_limit' => ['sometimes', 'integer', 'min:0'],
            'mailboxes_limit' => ['sometimes', 'integer', 'min:0'],
            'email_storage' => ['sometimes', 'integer', 'min:0'],
            'node_enabled' => ['sometimes', 'boolean'],
            'laravel_enabled' => ['sometimes', 'boolean'],
            'wordpress_enabled' => ['sometimes', 'boolean'],
            'php_enabled' => ['sometimes', 'boolean'],
            'static_enabled' => ['sometimes', 'boolean'],
            'backup_enabled' => ['sometimes', 'boolean'],
            'sftp_enabled' => ['sometimes', 'boolean'],
            'redis_enabled' => ['sometimes', 'boolean'],
            'ssl_auto' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];

        return validator($request->all(), $rules)->validate();
    }
}
