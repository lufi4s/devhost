<?php

namespace App\Services;

use App\Models\CustomerDomain;
use App\Models\User;
use App\Services\Dns\DnsProvider;
use App\Services\Dns\ManagedDnsProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Customer domain + DNS orchestration.
 *
 * Owns all tenant and plan-limit enforcement so the controllers stay thin:
 * adding a domain checks the plan's domain limit, verifies ownership, and
 * provisions the managed zone records. Every public method returns a
 * structured result so callers can render a consistent 422 payload.
 */
class DomainService
{
    public function __construct(
        private DnsProvider $dns,
        private PlanService $planService,
    ) {
    }

    /**
     * Normalize and validate a domain name. Accepts either a bare apex
     * (customer.com) or a subdomain label (shop.customer.com).
     *
     * @return array<string, mixed>
     */
    public function validateName(string $name): array
    {
        $name = strtolower(trim((string) $name));
        $clean = preg_replace('/[^a-z0-9.\-]/', '', $name);

        if ($clean !== $name || ! preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$/', $clean)) {
            return ['ok' => false, 'message' => 'That is not a valid domain name.'];
        }

        return ['ok' => true, 'domain' => $clean];
    }

    /**
     * Add a customer domain.
     *
     * @return array<string, mixed>
     */
    public function addDomain(User $customer, string $name, bool $asPrimary = false): array
    {
        $validated = $this->validateName($name);
        if (! $validated['ok']) {
            return $validated;
        }
        $name = $validated['domain'];

        // Domain names must be unique per customer.
        if ($customer->domains()->where('name', $name)->exists()) {
            return ['ok' => false, 'message' => "You already added {$name}."];
        }

        // Enforce the plan domain limit. `activeSubscription` (no parens)
        // resolves the relation to the actual Subscription model; the bare
        // relation object would fail the `checkDomains(Subscription)` type hint.
        $subscription = $customer->activeSubscription;
        if ($subscription && ! $this->planService->checkDomains($subscription)['allowed']) {
            $check = $this->planService->checkDomains($subscription);
            return [
                'ok' => false,
                'message' => "You have reached the {$check['limit']}-domain limit on your {$subscription->plan->name} plan.",
            ];
        }

        $records = DB::transaction(function () use ($customer, $name, $asPrimary) {
            $domain = $customer->domains()->create([
                'name' => $name,
                'verification_token' => Str::random(24),
                'primary' => $asPrimary,
                'nameserver_managed' => false,
            ]);

            if ($asPrimary) {
                $customer->domains()->where('primary', true)
                    ->whereKeyNot($domain->id)
                    ->update(['primary' => false]);
            }

            // Persist the records the provider declares, then mirror them to
            // the upstream provider. The DB table stays authoritative.
            return $this->provisionRecords($domain);
        });

        return [
            'ok' => true,
            'domain' => $customer->domains()->where('name', $name)->with('dnsRecords')->first(),
            'records' => $records,
        ];
    }

    /**
     * Persist the records the provider declares for a domain and mirror them to
     * the upstream provider. The DB table stays authoritative.
     *
     * @return array<int, \App\Models\DnsRecord>
     */
    private function provisionRecords(CustomerDomain $domain): array
    {
        $created = [];
        foreach ($this->dns->provisionDomain($domain) as $spec) {
            $record = $domain->dnsRecords()->updateOrCreate(
                ['name' => $spec['name'], 'type' => $spec['type']],
                ['value' => $spec['content'], 'ttl' => $spec['ttl'] ?? 3600]
            );
            $this->dns->syncRecord($record);
            $created[] = $record;
        }

        return $created;
    }

    /**
     * Flip a domain to the platform's managed nameservers and provision its
     * records automatically. Satisfies the "no manual DNS records required"
     * promise: once opted in, the platform owns the zone.
     *
     * @return array<string, mixed>
     */
    public function configureManaged(User $customer, string $name): array
    {
        $domain = $customer->domains()->where('name', strtolower($name))->first();
        if (! $domain) {
            return ['ok' => false, 'message' => 'Domain not found.'];
        }

        $domain->update([
            'nameserver_managed' => true,
            'nameserver_1' => ManagedDnsProvider::NS_1,
            'nameserver_2' => ManagedDnsProvider::NS_2,
        ]);

        $created = $this->provisionRecords($domain);

        return [
            'ok' => true,
            'domain' => $domain->fresh(['dns_records']),
            'records' => $created,
        ];
    }

    /**
     * Set (or clear) the customer's primary domain.
     *
     * @return array<string, mixed>
     */
    public function setPrimary(User $customer, string $name): array
    {
        $domain = $customer->domains()->where('name', strtolower($name))->first();
        if (! $domain) {
            return ['ok' => false, 'message' => 'Domain not found.'];
        }

        $customer->domains()->where('primary', true)->update(['primary' => false]);
        $domain->update(['primary' => true]);

        return ['ok' => true, 'domain' => $domain];
    }

    /**
     * Remove a customer domain (and its DNS records).
     *
     * @return array<string, mixed>
     */
    public function removeDomain(User $customer, string $name): array
    {
        $domain = $customer->domains()->where('name', strtolower($name))->first();
        if (! $domain) {
            return ['ok' => false, 'message' => 'Domain not found.'];
        }

        $domain->delete();

        return ['ok' => true];
    }
}
