<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Software Version
    |--------------------------------------------------------------------------
    |
    | Tracks the current core software version, build number, and update channel.
    |
    */
    'version' => '1.0.0',
    'build' => '2026101901',
    'release_date' => '2026-10-19',
    'min_php_version' => '8.5.12',
    'name' => env('APP_NAME', 'Foodness eCommerce'),

    /*
    |--------------------------------------------------------------------------
    | Central Update Server / GitHub Repository
    |--------------------------------------------------------------------------
    |
    | When GITHUB_REPO is specified (e.g. 'owner/repo'), updates are fetched
    | directly from GitHub Releases. Otherwise, update_server_url is queried.
    |
    */
    'update_server_url' => env('UPDATE_SERVER_URL', ''),
    'github_repo' => env('GITHUB_REPO', ''),
    'github_token' => env('GITHUB_TOKEN', ''),
    'auto_update_enabled' => (bool) env('AUTO_UPDATE_ENABLED', false),
];
