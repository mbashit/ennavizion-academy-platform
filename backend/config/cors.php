<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // Scoped to the actual static-site origin(s) rather than '*', since this API isn't meant
    // to be callable from arbitrary third-party sites: FRONTEND_URL plus any comma-separated
    // CORS_EXTRA_ORIGINS (in production: the www. variant of the domain).
    'allowed_origins' => array_values(array_filter([
        env('FRONTEND_URL', 'http://localhost:5500'),
        ...explode(',', env('CORS_EXTRA_ORIGINS', 'http://127.0.0.1:5500')),
    ])),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
