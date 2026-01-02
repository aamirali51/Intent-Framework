<?php

declare(strict_types=1);

/**
 * Auth Configuration
 * 
 * API tokens and OAuth provider settings.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | API Tokens
    |--------------------------------------------------------------------------
    |
    | Configuration for stateless API token authentication.
    |
    */
    'api_tokens' => [
        'table' => 'api_tokens',
        'expires' => 60 * 60 * 24 * 30, // 30 days in seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth Providers
    |--------------------------------------------------------------------------
    |
    | Configure OAuth 2.0 providers for social login.
    | Set credentials via environment variables for security.
    |
    | Supported: google, github, facebook
    |
    */
    'oauth' => [
        'google' => [
            'client_id' => env('GOOGLE_CLIENT_ID', ''),
            'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
            'redirect' => '/auth/google/callback',
        ],
        'github' => [
            'client_id' => env('GITHUB_CLIENT_ID', ''),
            'client_secret' => env('GITHUB_CLIENT_SECRET', ''),
            'redirect' => '/auth/github/callback',
        ],
        'facebook' => [
            'client_id' => env('FACEBOOK_CLIENT_ID', ''),
            'client_secret' => env('FACEBOOK_CLIENT_SECRET', ''),
            'redirect' => '/auth/facebook/callback',
        ],
    ],
];
