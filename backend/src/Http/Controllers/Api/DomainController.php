<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerDomain;
use App\Services\AuditService;
use App\Services\DomainService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Customer-scoped domain management.
 *
 * Every lookup is scoped to the authenticated customer (`customer_id`), so a
 * token for one customer can never read, update, or delete another customer's
 * domain. This is the core multi-tenant isolation guarantee (no IDOR).
 */
class DomainController extends Controller
{
    public function __construct(private DomainService $domainService)
    {
    }

    /**
     * GET /api/domains
     * The customer's domains with their DNS records.
     */
    public function index(): JsonResponse
    {
        $domains = Auth::user()->domains()->with('dnsRecords')->latest()->get();

        return response()->json(['domains' => $domains]);
    }

    /**
     * POST /api/domains
     * Add a domain to the customer's account.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:253'],
            'primary' => ['sometimes', 'boolean'],
        ]);

        $result = $this->domainService->addDomain(
            Auth::user(),
            $request->input('name'),
            $request->boolean('primary')
        );

        if (! $result['ok']) {
            return response()->json(['message' => $result['message']], 422);
        }

        AuditService::log('domain.added', "Added domain \"{$result['domain']->name}\"", [
            'domain_id' => $result['domain']->id,
        ], Auth::user());

        return response()->json([
            'domain' => $result['domain'],
            'records' => $result['records'],
        ], 201);
    }

    /**
     * DELETE /api/domains/{domain}
     * Remove a domain. The {domain} param is matched to the customer.
     */
    public function destroy(CustomerDomain $domain): JsonResponse
    {
        $this->authorizeDomain($domain);

        $name = $domain->name;
        $domain->delete();

        AuditService::log('domain.removed', "Removed domain \"{$name}\"", [
            'domain_id' => $domain->id,
        ], Auth::user());

        return response()->json(['message' => 'Domain removed.']);
    }

    /**
     * POST /api/domains/{domain}/set-primary
     * Mark a domain as the customer's primary domain.
     */
    public function setPrimary(Request $request, CustomerDomain $domain): JsonResponse
    {
        $this->authorizeDomain($domain);

        $result = $this->domainService->setPrimary(Auth::user(), $domain->name);
        if (! $result['ok']) {
            return response()->json(['message' => $result['message']], 422);
        }

        return response()->json(['domain' => $result['domain']]);
    }

    /**
     * POST /api/domains/{domain}/configure-managed
     * Switch a domain to the platform's nameservers and provision its records.
     */
    public function configureManaged(CustomerDomain $domain): JsonResponse
    {
        $this->authorizeDomain($domain);

        $result = $this->domainService->configureManaged(Auth::user(), $domain->name);
        if (! $result['ok']) {
            return response()->json(['message' => $result['message']], 422);
        }

        AuditService::log('domain.managed', "Configured managed nameservers for \"{$domain->name}\"", [
            'domain_id' => $domain->id,
        ], Auth::user());

        return response()->json([
            'domain' => $result['domain'],
            'records' => $result['records'],
        ]);
    }

    /**
     * Load a domain scoped to the authenticated customer only.
     */
    protected function authorizeDomain(CustomerDomain $domain): void
    {
        if ($domain->customer_id !== Auth::id()) {
            abort(403, 'You do not have access to this domain.');
        }
    }
}
