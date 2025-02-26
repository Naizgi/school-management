<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;

class MessageController extends Controller
{
    public function sendMessage(Request $request)
    {
        $request->validate([
            'sender_id' => 'required|integer',
            'receiver_id' => 'required|integer',
            'content' => 'required|string',
        ]);

        Message::create($request->all());

        return response()->json(['message' => 'Message sent successfully.']);
    }

    public function viewMessages($user_id)
    {
        $messages = Message::where('receiver_id', $user_id)
            ->orWhere('sender_id', $user_id)
            ->get();
        return response()->json($messages);
    }
}
