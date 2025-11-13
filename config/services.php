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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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
    'paymob' => [
        'api_secret' => env('PAYMOB_API_SECRET'),
        'public_key' => env('PAYMOB_PUBLIC_KEY'),
        'integration_id' => [
            'card' => env('PAYMOB_INTEGRATION_ID_CARD'),
            'wallet' => env('PAYMOB_INTEGRATION_ID_WALLET'),
            'instapay' => env('PAYMOB_INTEGRATION_ID_INSTAPAY'),
        ],
        'hmac_secret' => env('PAYMOB_HMAC_SECRET'),
        'api_url' => env('PAYMOB_API_URL', 'https://accept.paymob.com'),
        'fee_percentage' => env('PAYMOB_FEE_PERCENTAGE', 2),
        'callback_url' => env('APP_URL') . '/paymob/callback',
        'success_url' => env('APP_URL') . '/paymob/success',
        'failed_url' => env('APP_URL') . '/paymob/failed',
    ],
];
