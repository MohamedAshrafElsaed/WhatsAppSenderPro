<?php

return [
    /*
    |--------------------------------------------------------------------------
    | JWT Secret Key
    |--------------------------------------------------------------------------
    |
    | This key is used to sign JWT tokens for WhatsApp API authentication.
    | IMPORTANT: Use the same secret as configured in the Go API server.
    |
    */
    'secret' => env('JWT_SECRET', env('APP_KEY')),

    /*
    |--------------------------------------------------------------------------
    | JWT Algorithm
    |--------------------------------------------------------------------------
    |
    | The algorithm used to sign the JWT tokens.
    | Must match the Go API server configuration.
    |
    */
    'algo' => 'HS256',

    /*
    |--------------------------------------------------------------------------
    | JWT Time to Live (in seconds)
    |--------------------------------------------------------------------------
    |
    | How long the JWT token is valid.
    | Default: 86400 seconds (24 hours)
    |
    */
    'ttl' => env('JWT_TTL', 86400),

    /*
    |--------------------------------------------------------------------------
    | WhatsApp API Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the WhatsApp Go API server.
    |
    */
    'whatsapp_api_url' => env('WHATSAPP_API_URL', 'http://localhost:8988'),
];
