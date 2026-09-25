<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule sitemap generation daily
Schedule::command('sitemap:generate')->daily();

Artisan::command('webpush:setup', function () {
    $path = config('webpush.key_file');
    if (app(\App\Services\ChatPushService::class)->configured()) {
        $this->info('Web Push keys are already configured; existing subscriptions remain valid.');
        return;
    }
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0700, true);
    $keys = \Minishlink\WebPush\VAPID::createVapidKeys();
    file_put_contents($path, json_encode($keys, JSON_THROW_ON_ERROR), LOCK_EX);
    chmod($path, 0600);
    $this->info('Web Push keys saved securely. Start your queue worker to deliver notifications.');
})->purpose('Create private Web Push signing keys without replacing existing keys');
