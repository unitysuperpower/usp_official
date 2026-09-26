<?php

namespace App\Http\Controllers;

use App\Events\NewChatMessage;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Support\ReactPage;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $conversation = ChatConversation::with(['messages.user', 'assignedTo'])
            ->forUser($user->id)
            ->active()
            ->first();

        if (! $conversation) {
            $conversation = ChatConversation::create([
                'user_id' => $user->id,
                'status' => 'active',
                'subject' => 'Support Chat',
            ]);
        }

        return ReactPage::render('chat.index', compact('conversation'));
    }

    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'conversation_id' => 'required|exists:chat_conversations,id',
            'message' => 'required|string|max:1000',
        ]);

        $conversation = ChatConversation::findOrFail($validated['conversation_id']);

        // Check if user owns this conversation
        if ($conversation->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'user_id' => auth()->id(),
            'message' => $validated['message'],
            'is_admin' => false,
        ]);

        $conversation->update(['last_message_at' => now()]);

        // Broadcast the new message
        broadcast(new NewChatMessage($message))->toOthers();

        return response()->json([
            'success' => true,
            'message' => $message->load('user:id,name'),
        ]);
    }

    public function getMessages(Request $request, $conversationId)
    {
        $conversation = ChatConversation::findOrFail($conversationId);

        if ($conversation->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $messages = ChatMessage::where('conversation_id', $conversationId)
            ->with('user:id,name')
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark admin messages as read
        ChatMessage::where('conversation_id', $conversationId)
            ->where('is_admin', true)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json([
            'messages' => $messages,
            'conversation' => $conversation->load('assignedTo:id,name'),
        ]);
    }
}
