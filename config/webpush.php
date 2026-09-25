<?php

return [
    'public_key' => env('WEBPUSH_PUBLIC_KEY'),
    'private_key' => env('WEBPUSH_PRIVATE_KEY'),
    'subject' => env('WEBPUSH_SUBJECT', env('APP_URL', 'http://localhost')),
    'key_file' => storage_path('app/private/webpush-keys.json'),
    'allowed_hosts' => ['fcm.googleapis.com', 'updates.push.services.mozilla.com', '*.push.apple.com', '*.notify.windows.com'],
];
