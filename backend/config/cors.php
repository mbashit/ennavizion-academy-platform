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

    // Scoped to the actual static-site origin (FRONTEND_URL in .env) rather than '*', since
    // this API isn't meant to be callable from arbitrary third-party sites. Add the real
    // production domain here (or via FRONTEND_URL) once the site is deployed.
    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:5500'),
        'http://127.0.0.1:5500',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
