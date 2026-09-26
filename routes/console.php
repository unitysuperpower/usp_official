<?php

use App\Services\ChatPushService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Minishlink\WebPush\VAPID;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sitemaps are generated live on request; no cron or queue dependency.

Artisan::command('webpush:setup', function () {
    $path = config('webpush.key_file');
    if (app(ChatPushService::class)->configured()) {
        $this->info('Web Push keys are already configured; existing subscriptions remain valid.');

        return;
    }
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0700, true);
    }
    $keys = VAPID::createVapidKeys();
    file_put_contents($path, json_encode($keys, JSON_THROW_ON_ERROR), LOCK_EX);
    chmod($path, 0600);
    $this->info('Web Push keys saved securely. Start your queue worker to deliver notifications.');
})->purpose('Create private Web Push signing keys without replacing existing keys');
