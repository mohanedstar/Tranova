<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    // إرسال رسالة
    public function store(Request $request)
    {
        $validated = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
            'parent_id' => 'nullable|exists:messages,id',
        ]);

        $validated['sender_id'] = $request->user()->id;

        $message = Message::create($validated);

        // إرسال إشعار للمستقبل
        $receiver = User::find($request->receiver_id);
        $receiver->notify(new \App\Notifications\NewMessageReceived($message));


        return response()->json([
            'message' => __('messages.message.sent'),
            'data' => $message->load('sender', 'receiver')
        ], 201);
    }

    // عرض الرسائل المستلمة
    public function inbox(Request $request)
    {
        $messages = Message::where('receiver_id', $request->user()->id)
            ->with('sender')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json(['messages' => $messages]);
    }

    // عرض الرسائل المرسلة
    public function sent(Request $request)
    {
        $messages = Message::where('sender_id', $request->user()->id)
            ->with('receiver')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json(['messages' => $messages]);
    }

    // تعليم الرسالة كمقروءة
    public function markAsRead(Message $message, Request $request)
    {
        if ($message->receiver_id !== $request->user()->id) {
            return response()->json(['message' => __('messages.general.unauthorized')], 403);
        }

        $message->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json(['message' => __('messages.message.marked_read')]);
    }
}
