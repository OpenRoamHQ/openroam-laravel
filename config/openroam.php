<?php

return [
    /*
    | Your OpenRoam API key. Keep it in .env, never in code.
    */
    'api_key' => env('OPENROAM_API_KEY'),

    /*
    | 'sandbox' to test, 'production' for real orders.
    */
    'environment' => env('OPENROAM_ENVIRONMENT', 'production'),

    /*
    | Point the client at another host (rarely needed). Leave null to use the environment above.
    */
    'base_url' => env('OPENROAM_BASE_URL'),

    /*
    | Retry network errors, 429, 502/503/504 and "still running" (12002). 0 = never retry.
    | A POST is only repeated with its Idempotency-Key, so a purchase is never sent twice.
    */
    'max_retries' => (int) env('OPENROAM_MAX_RETRIES', 0),

    /*
    | The secret used to check the signature of webhooks (see the openroam.webhook middleware).
    */
    'webhook_secret' => env('OPENROAM_WEBHOOK_SECRET'),

    /*
    | How old a webhook may be, in seconds, before it is rejected.
    */
    'webhook_tolerance' => (int) env('OPENROAM_WEBHOOK_TOLERANCE', 300),
];
