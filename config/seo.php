<?php

return [
    'url' => env('SEO_URL', 'https://usp.com.pk'),
    'name' => 'USP Tech Solution',
    'description' => 'USP Tech Solution brings design and technology together to build digital experiences that move your business forward.',
    // Staging/local environments should never be indexed by default.
    'indexable' => env('SEO_INDEXABLE', env('APP_ENV', 'production') === 'production'),
    'image' => env('SEO_DEFAULT_IMAGE'),
    'verification' => [
        'google-site-verification' => env('GOOGLE_SITE_VERIFICATION'),
        'msvalidate.01' => env('BING_SITE_VERIFICATION'),
        'yandex-verification' => env('YANDEX_SITE_VERIFICATION'),
        'baidu-site-verification' => env('BAIDU_SITE_VERIFICATION'),
    ],
];
