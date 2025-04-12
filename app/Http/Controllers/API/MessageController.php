<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    // ✅ Apply JWT authentication middleware
  

    // ✅ SEND A MESSAGE
    public function sendMessage(Request $request)
    {
        $request->validate([
            'sender_id' => 'required|integer',
            'receiver_id' => 'required|integer',
            'content' => 'required|string',
        ]);

        try {
            // Get the authenticated user
            $user = Auth::user();

            // Check if the sender is authorized to send messages
            if ($user->id != $request->sender_id) {
                return response()->json(['error' => 'Unauthorized sender'], 403);
            }

            // Create the message
            Message::create($request->all());

            return response()->json(['message' => 'Message sent successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send message', 'message' => $e->getMessage()], 500);
        }
    }

    // ✅ VIEW MESSAGES
    public function viewMessages($user_id)
    {
        try {
            // Ensure the user is authenticated
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }
    
            // Ensure the user is viewing their own messages
            if ($user->id != $user_id) {
                return response()->json(['error' => 'Unauthorized to view these messages'], 403);
            }
    
            // Get the messages for the authenticated user and ensure created_at is not null
            $messages = Message::where('receiver_id', $user_id)
                ->orWhere('sender_id', $user_id)
                ->whereNotNull('created_at') // Ensure created_at is not null
                ->get();
    
            return response()->json($messages, 200);
        } catch (\Exception $e) {
            \Log::error('Error fetching messages: ' . $e->getMessage());  // Log the error for debugging
            return response()->json(['error' => 'Failed to fetch messages', 'message' => $e->getMessage()], 500);
        }
    }
    
}
