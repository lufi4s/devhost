<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Subscription lifecycle: subscribe, upgrade/downgrade, suspend, renew.
 */
class SubscriptionService
{
    public function subscribe(User $customer, Plan $plan, string $billingCycle = 'monthly'): Subscription
    {
        return DB::transaction(function () use ($customer, $plan, $billingCycle) {
            $subscription = Subscription::create([
                'customer_id' => $customer->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'billing_cycle' => $billingCycle,
                'amount' => $plan->price,
                'current_period_start' => now(),
                'current_period_end' => now()->addMonths($this->cycleMonths($billingCycle)),
            ]);

            $customer->update([
                'status' => 'active',
                'plan_id' => $plan->id,
            ]);

            return $subscription;
        });
    }

    /**
     * Change the plan on an existing subscription (upgrade or downgrade).
     */
    public function changePlan(Subscription $subscription, Plan $newPlan): void
    {
        DB::transaction(function () use ($subscription, $newPlan) {
            $subscription->update([
                'plan_id' => $newPlan->id,
                'amount' => $newPlan->price,
            ]);

            if ($subscription->customer) {
                $subscription->customer->update(['plan_id' => $newPlan->id]);
            }
        });
    }

    public function suspend(Subscription $subscription): void
    {
        $subscription->update(['status' => 'suspended']);
        $subscription->customer->update(['status' => 'suspended']);
    }

    public function unsuspend(Subscription $subscription): void
    {
        $subscription->update(['status' => 'active']);
        $subscription->customer->update(['status' => 'active']);
    }

    public function expire(Subscription $subscription): void
    {
        $subscription->update([
            'status' => 'expired',
            'cancelled_at' => now(),
        ]);
        $subscription->customer->update(['status' => 'suspended']);
    }

    public function renew(Subscription $subscription, ?string $billingCycle = null): void
    {
        DB::transaction(function () use ($subscription, $billingCycle) {
            $cycle = $billingCycle ?? $subscription->billing_cycle;
            $subscription->update([
                'status' => 'active',
                'billing_cycle' => $cycle,
                'renewed_at' => now(),
                'current_period_start' => now(),
                'current_period_end' => now()->addMonths($this->cycleMonths($cycle)),
            ]);

            $subscription->customer->update(['status' => 'active']);
        });
    }

    /**
     * @return array<int, mixed>  a fresh invoice payload for a renewal cycle
     */
    public function buildInvoice(Subscription $subscription): array
    {
        return [
            'invoice_number' => $this->nextInvoiceNumber($subscription),
            'amount' => $subscription->amount,
            'currency' => $subscription->plan->currency,
            'due_date' => now()->addDays(14),
        ];
    }

    private function cycleMonths(string $cycle): int
    {
        return match ($cycle) {
            'quarterly' => 3,
            'annually' => 12,
            default => 1,
        };
    }

    private function nextInvoiceNumber(Subscription $subscription): string
    {
        $last = $subscription->invoices()->latest('id')->first();
        $n = $last ? (int) Str::afterLast($last->invoice_number, '-') : 0;

        return 'INV-' . now()->format('ym') . '-' . str_pad($n + 1, 4, '0', STR_PAD_LEFT);
    }
}
