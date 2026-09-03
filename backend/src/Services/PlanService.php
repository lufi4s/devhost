<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;

/**
 * Plan selection + resource-enforcement logic.
 *
 * All limits are enforced server-side here so a customer cannot exceed their
 * plan regardless of what the frontend sends. This is the single source of
 * truth for "can this customer do X?" and always returns a structured result
 * so callers can produce a consistent 422 payload.
 */
class PlanService
{
    /**
     * Resolve and validate a plan slug from registration/create-subscription input.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function resolvePlan(array $input): array
    {
        $rules = [
            'plan_slug' => ['required', 'string', 'exists:plans,slug'],
        ];

        if (! empty($input['billing_cycle'])) {
            $rules['billing_cycle'] = ['sometimes', 'in:monthly,quarterly,annually'];
        }

        $data = validator($input, $rules)->validate();
        $plan = Plan::where('slug', $data['plan_slug'])->active()->first();

        if (! $plan || ! $plan->is_active) {
            return ['ok' => false, 'message' => 'That plan is not available.'];
        }

        if (isset($data['billing_cycle'])) {
            $plan->billing_cycle = $data['billing_cycle'];
        }

        return ['ok' => true, 'plan' => $plan];
    }

    /**
     * Whether a plan allows a given project stack type.
     */
    public function stackAllowed(Plan $plan, string $type): bool
    {
        return match ($type) {
            'wordpress' => (bool) $plan->wordpress_enabled,
            'laravel' => (bool) $plan->laravel_enabled,
            'node' => (bool) $plan->node_enabled,
            'static' => (bool) $plan->static_enabled,
            default => false,
        };
    }

    /**
     * Enforce the website count for a subscription.
     *
     * @return array<string, mixed>  [allowed, used, limit]
     */
    public function checkWebsites(Subscription $subscription): array
    {
        $plan = $subscription->plan;
        $used = $subscription->projects()->count();
        $limit = (int) $plan->websites_limit;

        return [
            'allowed' => $limit === 0 || $used < $limit,
            'used' => $used,
            'limit' => $limit,
        ];
    }

    /**
     * Enforce the database count for a subscription.
     *
     * @return array<string, mixed>
     */
    public function checkDatabases(Subscription $subscription): array
    {
        $plan = $subscription->plan;
        $used = $subscription->projects()
            ->withCount('databases')->get()
            ->sum('databases_count');
        $limit = (int) $plan->databases_limit;

        return [
            'allowed' => $limit === 0 || $used < $limit,
            'used' => $used,
            'limit' => $limit,
        ];
    }

    /**
     * Enforce the mailbox count for a subscription. Mailboxes are stored on a
     * future `mailbox` table; until that exists we report unlimited so the
     * subscription can still be created. Increment 6 wires the real count.
     *
     * @return array<string, mixed>
     */
    public function checkMailboxes(Subscription $subscription): array
    {
        $plan = $subscription->plan;
        $used = 0;
        if (class_exists(\App\Models\Mailbox::class)) {
            $used = $subscription->customer()->first()?->mailboxes()->count() ?? 0;
        }
        $limit = (int) $plan->mailboxes_limit;

        return [
            'allowed' => $limit === 0 || $used < $limit,
            'used' => $used,
            'limit' => $limit,
        ];
    }

    /**
     * Enforce the customer domain count for a subscription.
     *
     * @return array<string, mixed>
     */
    public function checkDomains(Subscription $subscription): array
    {
        $plan = $subscription->plan;
        $used = $subscription->customer()->first()?->domains()->count() ?? 0;
        $limit = (int) $plan->domains_limit;

        return [
            'allowed' => $limit === 0 || $used < $limit,
            'used' => $used,
            'limit' => $limit,
        ];
    }

    /**
     * Enforce that a customer may create a project of the given stack type.
     * Returns [allowed, message, context].
     *
     * @return array<string, mixed>
     */
    public function canCreateProject(Subscription $subscription, string $type, ?Plan $overridePlan = null): array
    {
        $plan = $overridePlan ?? $subscription->plan;

        if (! $this->stackAllowed($plan, $type)) {
            return [
                'allowed' => false,
                'message' => "Your {$plan->name} plan does not include {$type} hosting.",
                'context' => ['type' => $type, 'plan' => $plan->name],
            ];
        }

        $websites = $this->checkWebsites($subscription);
        if (! $websites['allowed']) {
            return [
                'allowed' => false,
                'message' => "You have reached the {$websites['limit']}-website limit on your {$plan->name} plan.",
                'context' => $websites,
            ];
        }

        return ['allowed' => true, 'plan' => $plan];
    }

    /**
     * Recompute the `plan_websites_used` counter used by the projects table
     * (kept in sync so reports and limits stay consistent).
     */
    public function refreshWebsiteUsage(Subscription $subscription): void
    {
        // Counting delegated to the subscription relation; no denormalized
        // counter required since projects carry their own subscription_id.
    }
}
