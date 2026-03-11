<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\On;

class ChatBox extends Component
{
    public $message = '';
    public $messages = [];
    public $conversation;
    public $showChat = false;

    public function mount()
    {
        $user = Auth::user();
        if ($user) {
            $this->conversation = Conversation::firstOrCreate(
                ['user_id' => $user->id],
                ['is_active' => true]
            );
            $this->loadMessages();
        }
    }

    #[On('chatToggled')]
    public function handleChatToggle($show)
    {
        $this->showChat = $show;
        if ($show && $this->conversation) {
            $this->loadMessages();
            $this->markMessagesAsRead();
        }
    }

    public function loadMessages()
    {
        if ($this->conversation) {
            $this->messages = Message::where('conversation_id', $this->conversation->id)
                ->orderBy('created_at', 'asc')
                ->get();
        }
    }

    public function sendMessage()
    {
        if (empty(trim($this->message))) {
            return;
        }

        if (!$this->conversation) {
            $this->conversation = Conversation::create([
                'user_id' => Auth::id(),
                'is_active' => true,
            ]);
        }

        Message::create([
            'conversation_id' => $this->conversation->id,
            'user_id' => Auth::id(),
            'message' => $this->message,
            'is_admin' => false,
            'is_read' => false,
        ]);

        $this->conversation->update(['last_message_at' => now()]);

        $this->message = '';
        $this->loadMessages();
        $this->dispatch('messageSent');
    }

    public function markMessagesAsRead()
    {
        if ($this->conversation) {
            Message::where('conversation_id', $this->conversation->id)
                ->where('is_admin', true)
                ->where('is_read', false)
                ->update(['is_read' => true]);
        }
    }

    public function render()
    {
        return view('livewire.chat.chat-box');
    }
}
