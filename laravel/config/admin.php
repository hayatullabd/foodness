<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Prefix
    |--------------------------------------------------------------------------
    |
    | Obscure the administrative URL route prefix via environment variable
    | to prevent automated bot scanners from identifying standard admin panels.
    |
    */

    'prefix' => env('ADMIN_PREFIX', 'SecretAdminPanel'),

    /*
    |--------------------------------------------------------------------------
    | Allowed Admin IP Whitelist
    |--------------------------------------------------------------------------
    |
    | When configured, access to any admin route is strictly restricted to
    | IP addresses specified here. If left empty, IP whitelisting is skipped.
    |
    */

    'allowed_ips' => array_values(array_filter(array_map('trim', explode(',', env('ADMIN_ALLOWED_IPS', ''))))),

];
