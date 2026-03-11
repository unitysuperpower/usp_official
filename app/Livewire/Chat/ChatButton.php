<?php

namespace App\Livewire\Chat;

use Livewire\Component;

class ChatButton extends Component
{
    public $showChat = false;

    public function toggleChat()
    {
        $this->showChat = !$this->showChat;
        $this->dispatch('chatToggled', show: $this->showChat);
    }

    public function render()
    {
        return view('livewire.chat.chat-button');
    }
}
