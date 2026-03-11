<?php

namespace App\Http\Controllers\Admin;

use App\Events\NewChatMessage;
use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index()
    {
        $conversations = ChatConversation::with(['user', 'assignedTo', 'latestMessage'])
            ->withCount(['messages as unread_count' => function ($query) {
                $query->where('is_admin', false)->where('is_read', false);
            }])
            ->orderByDesc('last_message_at')
            ->paginate(20);

        $totalUnread = ChatMessage::fromUser()->unread()->count();
        $activeConversations = ChatConversation::active()->count();

        return view('admin.chat.index', compact('conversations', 'totalUnread', 'activeConversations'));
    }

    public function show($id)
    {
        $conversation = ChatConversation::with(['user', 'assignedTo', 'messages.user'])
            ->findOrFail($id);

        // Mark user messages as read
        ChatMessage::where('conversation_id', $id)
            ->where('is_admin', false)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return view('admin.chat.show', compact('conversation'));
    }

    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'conversation_id' => 'required|exists:chat_conversations,id',
            'message' => 'required|string|max:1000',
        ]);

        $conversation = ChatConversation::findOrFail($validated['conversation_id']);

        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'user_id' => auth()->id(),
            'message' => $validated['message'],
            'is_admin' => true,
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'assigned_to' => auth()->id(),
        ]);

        // Broadcast the new message
        broadcast(new NewChatMessage($message))->toOthers();

        return response()->json([
            'success' => true,
            'message' => $message->load('user'),
        ]);
    }

    public function getMessages($conversationId)
    {
        $messages = ChatMessage::where('conversation_id', $conversationId)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json(['messages' => $messages]);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,closed,archived',
        ]);

        $conversation = ChatConversation::findOrFail($id);
        $conversation->update($validated);

        return back()->with('success', 'Chat status updated successfully.');
    }

    public function assign(Request $request, $id)
    {
        $validated = $request->validate([
            'admin_id' => 'required|exists:users,id,is_admin,1',
        ]);

        $conversation = ChatConversation::findOrFail($id);
        $conversation->update(['assigned_to' => $validated['admin_id']]);

        return back()->with('success', 'Chat assigned successfully.');
    }
}
