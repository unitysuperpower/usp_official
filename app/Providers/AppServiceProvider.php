<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \App\Models\ChatMessage::created(function ($message) {
            if (app(\App\Services\ChatPushService::class)->configured()) {
                \App\Jobs\SendChatPush::dispatch($message->id)->afterCommit();
            }
        });

        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Logout::class, function ($event) {
            if ($event->user && request()->hasSession() && \Illuminate\Support\Facades\Schema::hasTable('web_push_subscriptions')) {
                \App\Models\WebPushSubscription::where('user_id', $event->user->id)
                    ->where('session_hash', hash('sha256', request()->session()->getId()))->delete();
            }
        });
    }
}
