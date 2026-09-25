<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\User;
use App\Models\WebPushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class ChatPushService
{
    public function keys(): array
    {
        $keys = [];
        $path = config('webpush.key_file');
        if (is_file($path)) {
            $keys = json_decode(file_get_contents($path), true) ?: [];
        }
        return [
            'publicKey' => config('webpush.public_key') ?: ($keys['publicKey'] ?? null),
            'privateKey' => config('webpush.private_key') ?: ($keys['privateKey'] ?? null),
        ];
    }

    public function configured(): bool
    {
        $keys = $this->keys();
        return !empty($keys['publicKey']) && !empty($keys['privateKey']);
    }

    public function send(ChatMessage $message): void
    {
        if (!$this->configured()) return;
        $message->loadMissing('conversation');
        if (!$message->conversation) return;
        $recipients = $message->is_admin
            ? [$message->conversation->user_id]
            : User::where('is_admin', true)->pluck('id')->all();
        $subscriptions = WebPushSubscription::whereIn('user_id', $recipients)
            ->where('user_id', '!=', $message->user_id)->get();
        if ($subscriptions->isEmpty()) return;
        $webPush = new WebPush(['VAPID' => array_merge($this->keys(), ['subject' => config('webpush.subject')])], ['TTL' => 3600], 10, ['allow_redirects' => false]);
        foreach ($subscriptions as $subscription) {
            $webPush->queueNotification(Subscription::create([
                'endpoint' => $subscription->endpoint,
                'publicKey' => $subscription->public_key,
                'authToken' => $subscription->auth_token,
                'contentEncoding' => 'aes128gcm',
            ]), json_encode([
                'title' => 'USP Tech Solution',
                'body' => $message->is_admin ? 'Your support team sent you a message.' : 'A customer sent a new chat message.',
                'tag' => 'usp-chat-'.$message->conversation_id,
                'url' => $message->is_admin ? '/chat' : '/admin/chat/'.$message->conversation_id,
            ], JSON_THROW_ON_ERROR));
        }
        foreach ($webPush->flush() as $report) {
            if ($report->isSubscriptionExpired()) {
                WebPushSubscription::where('endpoint_hash', hash('sha256', $report->getEndpoint()))->delete();
            } elseif (!$report->isSuccess()) {
                // Never log subscription endpoints or private notification data.
                logger()->warning('A chat push notification could not be delivered.', ['status' => $report->getResponse()?->getStatusCode()]);
            }
        }
    }
}
