<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerDomain;
use App\Models\DnsRecord;
use App\Services\AuditService;
use App\Services\Dns\DnsProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Customer-scoped DNS record management.
 *
 * Records are always resolved against the owning customer's domain, so a token
 * can never touch another customer's DNS. Managed domains (those using the
 * platform nameservers) get their standard records provisioned automatically
 * via the `ManagedDnsProvider`.
 */
class DnsController extends Controller
{
    public function __construct(private DnsProvider $dns)
    {
    }

    /**
     * GET /api/domains/{domain}/dns-records
     */
    public function index(CustomerDomain $domain): JsonResponse
    {
        $this->authorizeDomain($domain);

        return response()->json([
            'records' => $domain->dnsRecords()->latest()->get(),
        ]);
    }

    /**
     * POST /api/domains/{domain}/dns-records
     * Add/replace a record.
     */
    public function store(Request $request, CustomerDomain $domain): JsonResponse
    {
        $this->authorizeDomain($domain);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:A,AAAA,CNAME,MX,TXT,CAA'],
            'value' => ['required', 'string', 'max:1024'],
            'ttl' => ['sometimes', 'integer', 'min:60', 'max:86400'],
            'priority' => ['sometimes', 'integer', 'nullable', 'min:0', 'max:65535'],
        ]);

        $record = DB::transaction(function () use ($domain, $request) {
            $r = $domain->dnsRecords()->updateOrCreate(
                ['name' => $request->input('name'), 'type' => $request->input('type')],
                ['value' => $request->input('value'), 'ttl' => $request->integer('ttl', 3600)]
            );

            if ($request->filled('priority')) {
                $r->update(['priority' => $request->integer('priority')]);
            }

            return $r;
        });

        $this->dns->syncRecord($record);

        AuditService::log('dns.record_updated', "Updated {$record->type} {$record->name}.{$domain->name}", [
            'record_id' => $record->id,
        ], Auth::user());

        return response()->json(['record' => $record], 201);
    }

    /**
     * DELETE /api/domains/{domain}/dns-records/{record}
     */
    public function destroy(CustomerDomain $domain, DnsRecord $record): JsonResponse
    {
        $this->authorizeDomain($domain);
        if ($record->customer_domain_id !== $domain->id) {
            abort(404, 'DNS record not found.');
        }

        $type = $record->type;
        $name = $record->name;
        $record->delete();

        AuditService::log('dns.record_deleted', "Deleted {$type} {$name}.{$domain->name}", [
            'record_id' => $record->id,
        ], Auth::user());

        return response()->json(['message' => 'DNS record deleted.']);
    }

    protected function authorizeDomain(CustomerDomain $domain): void
    {
        if ($domain->customer_id !== Auth::id()) {
            abort(403, 'You do not have access to this domain.');
        }
    }
}
