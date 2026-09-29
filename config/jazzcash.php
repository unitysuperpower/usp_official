<?php

return [
    'enabled' => env('JAZZCASH_ENABLED', false),
    'environment' => env('JAZZCASH_ENVIRONMENT', 'sandbox'),
    'merchant_id' => env('JAZZCASH_MERCHANT_ID'),
    'password' => env('JAZZCASH_PASSWORD'),
    'integrity_salt' => env('JAZZCASH_INTEGRITY_SALT'),
    // Copy the hosted HTTP POST URL supplied for your merchant account.
    'checkout_url' => env('JAZZCASH_CHECKOUT_URL'),
    'return_url' => env('JAZZCASH_RETURN_URL'),
];
