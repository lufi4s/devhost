<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AuditService;
use App\Services\PlanService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BillingController extends Controller
{
    public function __construct(
        private PlanService $planService,
        private SubscriptionService $subscriptionService,
    ) {
    }

    /**
     * GET /api/billing/plans
     * Active plans a customer can subscribe to.
     */
    public function plans(): JsonResponse
    {
        return response()->json(['plans' => Plan::active()->get()]);
    }

    /**
     * POST /api/billing/subscribe
     * Subscribe the customer to a plan (or upgrade the existing one).
     */
    public function subscribe(Request $request): JsonResponse
    {
        $data = validator($request->all(), [
            'plan_slug' => ['required', 'string', 'exists:plans,slug'],
            'billing_cycle' => ['sometimes', 'in:monthly,quarterly,annually'],
        ])->validate();

        $resolved = $this->planService->resolvePlan($data);
        if (! $resolved['ok']) {
            return response()->json(['message' => $resolved['message']], 422);
        }

        $customer = Auth::user();
        $existing = $customer->activeSubscription();

        DB::transaction(function () use ($resolved, $data, $existing, $customer) {
            if ($existing) {
                $this->subscriptionService->changePlan($existing, $resolved['plan']);
            } else {
                $this->subscriptionService->subscribe(
                    $customer,
                    $resolved['plan'],
                    $data['billing_cycle'] ?? $resolved['plan']->billing_cycle
                );
            }
        });

        AuditService::log('billing.subscribed', "Subscribed to plan \"{$resolved['plan']->name}\"", [
            'plan_slug' => $resolved['plan']->slug,
            'subscription_id' => $existing?->id,
        ], Auth::user());

        return response()->json([
            'subscription' => $customer->activeSubscription()->load('plan'),
        ], 201);
    }

    /**
     * GET /api/billing/subscription
     * The customer's current subscription with plan limits + usage.
     */
    public function show(): JsonResponse
    {
        $customer = Auth::user();
        $subscription = $customer->activeSubscription();

        if (! $subscription) {
            return response()->json(['message' => 'No active subscription.']);
        }

        return response()->json([
            'subscription' => $subscription->load('plan'),
            'usage' => [
                'websites' => $this->planService->checkWebsites($subscription),
                'databases' => $this->planService->checkDatabases($subscription),
                'mailboxes' => $this->planService->checkMailboxes($subscription),
                'domains' => $this->planService->checkDomains($subscription),
            ],
        ]);
    }

    /**
     * PATCH /api/billing/subscription/plan
     * Change the plan on the active subscription (admin or customer).
     */
    public function changePlan(Request $request): JsonResponse
    {
        $request->validate(['plan_slug' => ['required', 'string', 'exists:plans,slug']]);

        $resolved = $this->planService->resolvePlan($request->all());
        if (! $resolved['ok']) {
            return response()->json(['message' => $resolved['message']], 422);
        }

        $subscription = Auth::user()->activeSubscription();
        if (! $subscription) {
            return response()->json(['message' => 'No active subscription to change.'], 422);
        }

        $this->subscriptionService->changePlan($subscription, $resolved['plan']);

        AuditService::log('billing.plan_changed', "Changed plan to \"{$resolved['plan']->name}\"", [
            'plan_slug' => $resolved['plan']->slug,
            'subscription_id' => $subscription->id,
        ], Auth::user());

        return response()->json([
            'subscription' => $subscription->fresh()->load('plan'),
        ]);
    }

    /**
     * POST /api/billing/subscription/renew
     */
    public function renew(Request $request): JsonResponse
    {
        $subscription = Auth::user()->activeSubscription();
        if (! $subscription) {
            return response()->json(['message' => 'No active subscription.'], 422);
        }

        $this->subscriptionService->renew(
            $subscription,
            $request->input('billing_cycle')
        );

        return response()->json(['message' => 'Subscription renewed.']);
    }

    /**
     * GET /api/billing/invoices
     * Paginated invoice history for the customer's active subscription.
     */
    public function invoices(Request $request): JsonResponse
    {
        $subscription = Auth::user()->activeSubscription();

        if (! $subscription) {
            return response()->json(['invoices' => []]);
        }

        return response()->json([
            'invoices' => $subscription->invoices()
                ->latest()
                ->paginate($request->integer('per_page', 15)),
        ]);
    }
}
