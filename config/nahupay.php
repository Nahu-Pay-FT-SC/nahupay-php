<?php

return [

    /*
    |--------------------------------------------------------------------------
    | NahuPay API Key
    |--------------------------------------------------------------------------
    |
    | Your secret API key.  Use sk_test_… for sandbox and sk_live_… for
    | production.  Never commit real keys to version control — set this in
    | your .env file instead.
    |
    */
    'api_key' => env('NAHUPAY_SECRET_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | API Base URL
    |--------------------------------------------------------------------------
    |
    | Override if you need to point to a local or staging API instance.
    | Default: https://api.nahupay.com/api/v1
    |
    */
    'base_url' => env('NAHUPAY_API_URL', 'https://api.nahupay.com/api/v1'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Per-request timeout in seconds.
    |
    */
    'timeout' => env('NAHUPAY_TIMEOUT', 30),

];
