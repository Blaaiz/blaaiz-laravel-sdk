<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Blaaiz API Key
    |--------------------------------------------------------------------------
    |
    | Your Blaaiz API key. You can get this from your Blaaiz dashboard.
    | This key is used to authenticate with the Blaaiz API.
    |
    */
    'api_key' => env('BLAAIZ_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Blaaiz OAuth Credentials
    |--------------------------------------------------------------------------
    |
    | OAuth 2.0 client credentials for the Blaaiz API. When both client_id
    | and client_secret are provided, the SDK will use OAuth authentication.
    | Otherwise, it falls back to the legacy API key above.
    |
    */
    'client_id' => env('BLAAIZ_CLIENT_ID', ''),
    'client_secret' => env('BLAAIZ_CLIENT_SECRET', ''),
    'oauth_scope' => env('BLAAIZ_OAUTH_SCOPE', ''),

    /*
    |--------------------------------------------------------------------------
    | Blaaiz Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the Blaaiz API. This defaults to the development
    | environment. Change to production URL when going live.
    |
    */
    'base_url' => env('BLAAIZ_API_URL', 'https://api-dev.blaaiz.com'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout in seconds for HTTP requests to the Blaaiz API.
    | Default is 30 seconds.
    |
    */
    'timeout' => env('BLAAIZ_TIMEOUT', 30),
];