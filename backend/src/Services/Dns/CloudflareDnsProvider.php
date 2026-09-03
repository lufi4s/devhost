<?php

namespace App\Services\Dns;

use App\Models\CustomerDomain;
use App\Models\DnsRecord;
use App\Services\AuditService;
use Illuminate\Support\Facades\Http;

/**
 * Cloudflare-backed DNS provider.
 *
 * Implements `DnsProvider` against the Cloudflare API v4. Each customer domain
 * that is nameserver-managed gets its own Cloudflare zone; records are created,
 * updated and deleted through the standard zone REST endpoints.
 *
 * The provider talks to Cloudflare through Laravel's `Http` facade so the
 * outbound calls can be swapped/mocked in tests and the credentials stay out
 * of the domain logic.
 */
class CloudflareDnsProvider implements DnsProvider
{
    private const BASE = 'https://api.cloudflare.com/client/v4';

    public function __construct(
        private ?string $token = null,
        private ?string $zoneId = null,
    ) {
        $this->token = $token ?? config('dns.cloudflare.token');
        $this->zoneId = $zoneId ?? config('dns.cloudflare.zone_id');
    }

    public function configured(): bool
    {
        return ! empty($this->token);
    }

    public function name(): string
    {
        return 'cloudflare';
    }

    /**
     * Declare the standard records a domain needs.
     *
     * This is a pure declaration (no side effects). The caller persists the
     * declared records to the authoritative `dns_records` table and then calls
     * `syncRecord()` to mirror them upstream, which is where the Cloudflare
     * API calls actually happen.
     *
     * @return array<int, array<string, mixed>>
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
                'content' => config('dns.platform_ip', ''),
                'ttl' => 1,
                'proxied' => false,
            ],
        ];
    }

    public function syncRecord(DnsRecord $record): void
    {
        $domain = $record->customerDomain;
        if (! $domain) {
            return;
        }

        $zone = $this->ensureZone($domain->name);
        $payload = [
            'type' => $record->type,
            'name' => $record->name,
            'content' => $record->value,
            'ttl' => $record->ttl,
        ];
        if ($record->priority !== null) {
            $payload['priority'] = $record->priority;
        }

        if ($record->provider_ref) {
            $res = $this->recordRequest('PUT', "{$zone}/dns_records/{$record->provider_ref}", $payload);
        } else {
            $res = $this->recordRequest('POST', "{$zone}/dns_records", $payload);
            if ($res['ok'] && isset($res['result']['id'])) {
                $record->update(['provider_ref' => $res['result']['id']]);
            }
        }

        if (! $res['ok']) {
            AuditService::log('dns.provider_error', "Cloudflare failed to sync {$record->type} {$record->name}.{$domain->name}: {$res['message']}", [
                'record_id' => $record->id,
            ]);
        }
    }

    public function deleteRecord(DnsRecord $record): void
    {
        $domain = $record->customerDomain;
        if (! $domain) {
            return;
        }

        $zone = $this->ensureZone($domain->name);
        $res = $this->recordRequest('DELETE', "{$zone}/dns_records/{$record->id}", []);

        if (! $res['ok']) {
            AuditService::log('dns.provider_error', "Cloudflare failed to delete {$record->type} {$record->name}.{$domain->name}: {$res['message']}", [
                'record_id' => $record->id,
            ]);
        }
    }

    /**
     * Resolve (or create) the Cloudflare zone for a domain and return its id.
     */
    private function ensureZone(string $domain): string
    {
        if ($this->zoneId) {
            return $this->zoneId;
        }

        $res = $this->zoneRequest('GET', "/zones?name={$domain}", []);
        if ($res['ok'] && ! empty($res['result'])) {
            return $res['result'][0]['id'];
        }

        $created = $this->zoneRequest('POST', '/zones', [
            'name' => $domain,
            'type' => 'full',
        ]);

        if (! $created['ok']) {
            AuditService::log('dns.provider_error', "Cloudflare failed to create zone for {$domain}: {$created['message']}", [
                'domain' => $domain,
            ]);
        }

        return $created['result']['id'] ?? '';
    }

    /**
     * Perform a Cloudflare request and return a normalized [ok, message, data]
     * payload so callers never have to handle the raw API envelope.
     *
     * @param  array<string, mixed>  $body
     * @return array{ok: bool, message: ?string, result: mixed}
     */
    private function request(string $method, string $path, array $body): array
    {
        $url = self::BASE . $path;

        $response = Http
            ->withHeader('Authorization', 'Bearer ' . $this->token)
            ->withHeader('Content-Type', 'application/json')
            ->acceptJson()
            ->timeout(15)
            ->send($method, $url, $method === 'GET' ? ['query' => $body] : ['json' => $body]);

        if (! $response->successful()) {
            return ['ok' => false, 'message' => "HTTP {$response->status()}", 'result' => null];
        }

        $data = $response->json();

        if ($data['success'] === true) {
            return ['ok' => true, 'message' => null, 'result' => $data['result'] ?? []];
        }

        $message = data_get($data, 'errors.0.message', 'Unknown provider error');

        return ['ok' => false, 'message' => $message, 'result' => null];
    }

    private function zoneRequest(string $method, string $path, array $body): array
    {
        return $this->request($method, $path, $body);
    }

    private function recordRequest(string $method, string $path, array $body): array
    {
        return $this->request($method, $path, $body);
    }
}
