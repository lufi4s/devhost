<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platform DNS pool IP
    |--------------------------------------------------------------------------
    |
    | The public IP that apex/wildcard records resolve to for domains managed
    | by this platform. In production this is the shared Nginx/reverse-proxy
    | front. Leave empty until Increment 3 wires a real pool address.
    |
    */

    'platform_ip' => env('DNS_PLATFORM_IP', ''),

    /*
    |--------------------------------------------------------------------------
    | Provider
    |--------------------------------------------------------------------------
    |
    | "managed" is the built-in DB-backed provider (this increment). Increment
    | 3 swaps in a real upstream (e.g. cloudflare) via DNS_PROVIDER and the
    | relevant credentials; the interface keeps callers unchanged.
    |
    */

    'provider' => env('DNS_PROVIDER', 'managed'),

    'cloudflare' => [
        'token' => env('CLOUDFLARE_TOKEN'),
        'zone_id' => env('CLOUDFLARE_ZONE_ID'),
        'email' => env('CLOUDFLARE_EMAIL'),
        'api_key' => env('CLOUDFLARE_API_KEY'),
    ],

    'nameservers' => [
        env('DNS_NAMESERVER_1', 'ns1.yourplatform.com'),
        env('DNS_NAMESERVER_2', 'ns2.yourplatform.com'),
    ],

];
