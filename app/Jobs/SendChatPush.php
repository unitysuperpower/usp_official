<?php

namespace App\Jobs;

use App\Models\ChatMessage;
use App\Services\ChatPushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendChatPush implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $messageId) {}

    public function backoff(): array { return [10, 60, 180]; }

    public function handle(ChatPushService $push): void
    {
        if ($message = ChatMessage::find($this->messageId)) {
            $push->send($message);
        }
    }
}
