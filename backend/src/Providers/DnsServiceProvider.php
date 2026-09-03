<?php

namespace App\Providers;

use App\Services\Dns\CloudflareDnsProvider;
use App\Services\Dns\DnsProvider;
use App\Services\Dns\ManagedDnsProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Resolve the active DNS provider from configuration.
 *
 * When a Cloudflare token is configured the platform provisions real upstream
 * records; otherwise the built-in DB-backed provider is used so the system
 * remains fully functional (and the managed zone stays authoritative) without
 * any third-party credentials. Callers always type-hint the `DnsProvider`
 * interface and never need to know which backend is active.
 */
class DnsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DnsProvider::class, function ($app) {
            if (config('dns.cloudflare.token')) {
                return new CloudflareDnsProvider();
            }

            return new ManagedDnsProvider();
        });
    }

    public function boot(): void
    {
        //
    }
}
