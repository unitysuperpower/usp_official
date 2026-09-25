<?php

namespace App\Http\Controllers;

use App\Models\WebPushSubscription;
use App\Services\ChatPushService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PushSubscriptionController extends Controller
{
    public function config(ChatPushService $push)
    {
        return response()->json(['public_key' => $push->configured() ? $push->keys()['publicKey'] : null]);
    }

    public function store(Request $request, ChatPushService $push)
    {
        abort_unless($push->configured(), 503, 'Push notifications are not configured yet.');
        $data = $request->validate([
            'endpoint' => 'required|url:https|max:2048',
            'keys.p256dh' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{87}={0,2}$/'],
            'keys.auth' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{22}={0,2}$/'],
        ]);
        $parts = parse_url($data['endpoint']);
        $host = strtolower($parts['host'] ?? '');
        if (!Str::is(config('webpush.allowed_hosts'), $host) || isset($parts['user']) || isset($parts['pass']) || (isset($parts['port']) && $parts['port'] !== 443)) {
            throw ValidationException::withMessages(['endpoint' => 'This push service is not supported.']);
        }
        $hash = hash('sha256', $data['endpoint']);
        $existing = WebPushSubscription::where('endpoint_hash', $hash)->first();
        abort_if($existing && $existing->user_id !== $request->user()->id, 409, 'Disable notifications for the previous account on this browser first.');
        WebPushSubscription::updateOrCreate(['endpoint_hash' => $hash], [
            'user_id' => $request->user()->id,
            'endpoint' => $data['endpoint'],
            'public_key' => $data['keys']['p256dh'],
            'auth_token' => $data['keys']['auth'],
            'session_hash' => hash('sha256', $request->session()->getId()),
        ]);
        return response()->json(['subscribed' => true]);
    }

    public function destroy(Request $request)
    {
        $data = $request->validate(['endpoint' => 'required|string|max:2048']);
        WebPushSubscription::where('user_id', $request->user()->id)->where('endpoint_hash', hash('sha256', $data['endpoint']))->delete();
        return response()->json(['subscribed' => false]);
    }
}
