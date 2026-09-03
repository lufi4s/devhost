<?php

namespace App\Services\Dns;

use App\Models\CustomerDomain;

/**
 * Default DNS provider: the platform's own managed zone.
 *
 * Records live in the `dns_records` table, which is the authoritative source
 * of truth for the application. `provisionDomain()` declares the standard
 * records a new domain needs (an apex A record pointing at the platform pool)
 * without any side effects; callers persist the declared records to the DB and
 * mirror them upstream via `syncRecord()`/`deleteRecord()`.
 *
 * A real upstream (Cloudflare) is dropped in at Increment 3 by implementing
 * `DnsProvider` against that provider's API; the controllers and callers do
 * not change.
 */
class ManagedDnsProvider implements DnsProvider
{
    public const NS_1 = 'ns1.yourplatform.com';
    public const NS_2 = 'ns2.yourplatform.com';

    public function __construct(private ?string $platformIp = null)
    {
        $this->platformIp = $platformIp ?? config('dns.platform_ip');
    }

    public function configured(): bool
    {
        return true;
    }

    public function name(): string
    {
        return 'managed';
    }

    /**
     * Declare the standard records a domain needs.
     *
     * For a nameserver-managed domain the apex A record is declared pointing at
     * the platform pool IP. No records are written here — the caller persists
     * and syncs them, so this stays a pure function of the domain state.
     *
     * @return array<int, array<string, string>>
     */
    public function provisionDomain(CustomerDomain $domain): array
    {
        if (! $domain->nameserver_managed) {
            return [];
        }

        return [
            [
                'name' => '@',
                'type' => 'A',
                'content' => $this->platformIp ?? '',
                'ttl' => 3600,
            ],
        ];
    }

    /**
     * Mirror a single record to the upstream provider. For the managed zone
     * there is nothing upstream to push: the `dns_records` table already is
     * the authoritative zone. Increment 3 adds the real push here.
     */
    public function syncRecord(\App\Models\DnsRecord $record): void
    {
        // no-op for the managed provider; the DB table is authoritative.
    }

    public function deleteRecord(\App\Models\DnsRecord $record): void
    {
        // deletion is handled against the DB table by the DNS controller.
    }
}
