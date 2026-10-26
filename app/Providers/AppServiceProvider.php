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
        // SMTP settings saved in Admin → Settings → Email override the .env mail settings.
        \App\Support\SiteMail::apply();

        // Role permissions (config/permissions.php) become gates: `can:blogs.manage`, $user->can('chat.manage').
        foreach (\App\Models\Role::catalog() as $permission) {
            \Illuminate\Support\Facades\Gate::define($permission, fn (\App\Models\User $user) => $user->hasPermission($permission));
        }
        \Illuminate\Support\Facades\Gate::define('education.any', fn (\App\Models\User $user) => collect(['education.courses', 'education.teach', 'education.enrollments', 'education.payments'])->contains(fn ($p) => $user->hasPermission($p)));

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
