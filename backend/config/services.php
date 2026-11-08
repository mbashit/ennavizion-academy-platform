<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'paystack' => [
        // off | test | live. Checkout only opens when the mode and the key agree (test needs
        // an sk_test_ key, live an sk_live_ key), so adding a live key never switches the
        // site to real payments on its own — PAYSTACK_MODE=live has to be set deliberately.
        'mode' => env('PAYSTACK_MODE', 'off'),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
    ],

    'frontend' => [
        'url' => env('FRONTEND_URL', 'http://localhost:5500'),
    ],

    // Inbox that gets an email for every new form submission. Leave blank to only record
    // them in the admin panel.
    'notify' => [
        'email' => env('NOTIFY_EMAIL'),
    ],

];
