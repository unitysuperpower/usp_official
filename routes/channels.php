<?php

use App\Models\ChatConversation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    $conversation = ChatConversation::find($conversationId);
    
    if (!$conversation) {
        return false;
    }
    
    // Allow access if user owns the conversation or is an admin
    return $user->id === $conversation->user_id || $user->is_admin;
});

Broadcast::channel('admin-notifications', function ($user) {
    // Only admins can listen to admin notifications
    return $user->is_admin;
});
