<?php

return [
    'currency' => 'PKR',
    // An empty instruction disables that method. Enter verified receiving details before accepting payment.
    'payment_methods' => [
        'bank_transfer' => ['label' => 'Bank transfer', 'instructions' => env('EDUCATION_BANK_INSTRUCTIONS', '')],
        'jazzcash' => ['label' => 'JazzCash', 'instructions' => env('EDUCATION_JAZZCASH_INSTRUCTIONS', '')],
        'easypaisa' => ['label' => 'Easypaisa', 'instructions' => env('EDUCATION_EASYPAISA_INSTRUCTIONS', '')],
    ],
];
