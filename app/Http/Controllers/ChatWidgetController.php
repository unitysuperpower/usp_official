<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatWidgetController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['conversation_id' => 'nullable|integer|min:1']);
        $user = $request->user();
        $incoming = ChatMessage::where('is_admin', !$user->is_admin)
            ->when(!$user->is_admin, fn ($q) => $q->whereHas('conversation', fn ($c) => $c->where('user_id', $user->id)));
        $conversation = null;
        if (!empty($data['conversation_id'])) {
            $conversation = ChatConversation::findOrFail($data['conversation_id']);
            abort_unless($user->is_admin || $conversation->user_id === $user->id, 403);
        } elseif (!$user->is_admin) {
            $conversation = ChatConversation::forUser($user->id)->active()->latest('id')->first();
        }
        $messages = $conversation ? $conversation->messages()->with('user:id,name')->latest('id')->limit(60)->get()->reverse()->values() : collect();
        $conversations = $user->is_admin ? ChatConversation::with('user:id,name')->withCount(['messages as unread_count' => fn ($q) => $q->where('is_admin', false)->where('is_read', false)])->orderByDesc('last_message_at')->limit(30)->get(['id', 'user_id', 'subject', 'status', 'last_message_at']) : [];
        return response()->json([
            'conversation' => $conversation ? ['id' => $conversation->id, 'status' => $conversation->status, 'subject' => $conversation->subject] : null,
            'messages' => $messages,
            'conversations' => $conversations,
            'unread_count' => (clone $incoming)->where('is_read', false)->count(),
            'latest_incoming_id' => (clone $incoming)->max('id') ?? 0,
        ])->header('Cache-Control', 'no-store');
    }

    public function start(Request $request)
    {
        abort_if($request->user()->is_admin, 403);
        $conversation = DB::transaction(function () use ($request) {
            $request->user()->newQuery()->whereKey($request->user()->id)->lockForUpdate()->first();
            return ChatConversation::firstOrCreate(['user_id' => $request->user()->id, 'status' => 'active'], ['subject' => 'Support Chat']);
        });
        return response()->json(['conversation_id' => $conversation->id]);
    }

    public function read(Request $request)
    {
        $data = $request->validate(['conversation_id' => 'required|integer', 'through_id' => 'required|integer|min:1']);
        $conversation = ChatConversation::findOrFail($data['conversation_id']);
        abort_unless($request->user()->is_admin || $conversation->user_id === $request->user()->id, 403);
        $conversation->messages()->where('id', '<=', $data['through_id'])->where('is_admin', !$request->user()->is_admin)->where('is_read', false)->update(['is_read' => true, 'read_at' => now()]);
        return response()->json(['success' => true]);
    }
}
