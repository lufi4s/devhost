<?php

namespace App\Services\Dns;

use App\Models\CustomerDomain;

/**
 * DNS provider abstraction.
 *
 * Implementations translate our in-board DNS state (the `dns_records` table)
 * into upstream provider operations (Cloudflare, Route53, etc.). Keeping this
 * behind an interface lets Increment 3 drop in a real provider without
 * touching the domain/DNS controllers.
 */
interface DnsProvider
{
    /**
     * Whether this provider is configured and reachable.
     */
    public function configured(): bool;

    /**
     * The human-readable provider name (e.g. "cloudflare").
     */
    public function name(): string;

    /**
     * Ensure all required records exist for a domain on the upstream provider.
     * Returns the records that were created.
     *
     * @return array<int, array<string, mixed>>
     */
    public function provisionDomain(CustomerDomain $domain): array;

    /**
     * Sync a single record's content to the upstream provider.
     */
    public function syncRecord(\App\Models\DnsRecord $record): void;

    /**
     * Remove a single record from the upstream provider.
     */
    public function deleteRecord(\App\Models\DnsRecord $record): void;
}
