<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    // ✅ SEND A MESSAGE
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|integer|exists:users,id',
            'content' => 'required|string|max:1000',
            'message_type' => 'sometimes|string|in:text,image,file', // Added message type
        ]);

        try {
            // Get the authenticated user
            $user = Auth::user();

            // Create the message
            $message = Message::create([
                'sender_id' => $user->id,
                'receiver_id' => $request->receiver_id,
                'content' => $request->content,
                'message_type' => $request->message_type ?? 'text',
                'is_read' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully.',
                'data' => $message
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to send message', 
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ GET CHAT HISTORY WITH LAST MESSAGE (Like WhatsApp)
    public function getChatHistory(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Get all unique users that the current user has conversed with
            $chatPartners = Message::select('sender_id', 'receiver_id')
                ->where(function($query) use ($user) {
                    $query->where('sender_id', $user->id)
                          ->orWhere('receiver_id', $user->id);
                })
                ->get()
                ->flatMap(function ($message) use ($user) {
                    // Return the other user's ID in each conversation
                    return [
                        $message->sender_id == $user->id ? $message->receiver_id : $message->sender_id
                    ];
                })
                ->unique()
                ->values();

            // Get user details and last message for each chat partner
            $chatHistory = [];
            foreach ($chatPartners as $partnerId) {
                $partner = User::find($partnerId);
                
                if (!$partner) continue;

                // Get last message in this conversation
                $lastMessage = Message::where(function($query) use ($user, $partnerId) {
                    $query->where('sender_id', $user->id)
                          ->where('receiver_id', $partnerId);
                })->orWhere(function($query) use ($user, $partnerId) {
                    $query->where('sender_id', $partnerId)
                          ->where('receiver_id', $user->id);
                })
                ->latest()
                ->first();

                // Count unread messages from this partner
                $unreadCount = Message::where('sender_id', $partnerId)
                    ->where('receiver_id', $user->id)
                    ->where('is_read', false)
                    ->count();

                $chatHistory[] = [
                    'user_id' => $partner->id,
                    'user_name' => $partner->user_name,
                    'full_name' => $partner->full_name,
                    'profile_picture' => $partner->profile_picture_url,
                    'role' => $partner->role,
                    'last_message' => $lastMessage ? [
                        'content' => $lastMessage->content,
                        'message_type' => $lastMessage->message_type,
                        'is_read' => $lastMessage->is_read,
                        'is_sent_by_me' => $lastMessage->sender_id == $user->id,
                        'created_at' => $lastMessage->created_at->format('Y-m-d H:i:s'),
                        'time_ago' => $lastMessage->created_at->diffForHumans(),
                    ] : null,
                    'unread_count' => $unreadCount,
                    'last_activity' => $lastMessage ? $lastMessage->created_at : $partner->created_at,
                ];
            }

            // Sort by last activity (most recent first)
            usort($chatHistory, function($a, $b) {
                return strtotime($b['last_activity']) - strtotime($a['last_activity']);
            });

            return response()->json([
                'success' => true,
                'data' => $chatHistory,
                'total_chats' => count($chatHistory)
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error fetching chat history: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch chat history',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ GET MESSAGES WITH SPECIFIC USER
    public function getMessagesWithUser($user_id)
    {
        try {
            $currentUser = Auth::user();
            
            // Validate the target user exists
            $targetUser = User::find($user_id);
            if (!$targetUser) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not found'
                ], 404);
            }

            // Get messages between current user and target user
            $messages = Message::where(function($query) use ($currentUser, $user_id) {
                $query->where('sender_id', $currentUser->id)
                      ->where('receiver_id', $user_id);
            })->orWhere(function($query) use ($currentUser, $user_id) {
                $query->where('sender_id', $user_id)
                      ->where('receiver_id', $currentUser->id);
            })
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) use ($currentUser) {
                return [
                    'id' => $message->id,
                    'content' => $message->content,
                    'message_type' => $message->message_type,
                    'is_read' => $message->is_read,
                    'is_sent_by_me' => $message->sender_id == $currentUser->id,
                    'sender' => [
                        'id' => $message->sender->id,
                        'name' => $message->sender->full_name,
                        'profile_picture' => $message->sender->profile_picture_url,
                    ],
                    'receiver' => [
                        'id' => $message->receiver->id,
                        'name' => $message->receiver->full_name,
                        'profile_picture' => $message->receiver->profile_picture_url,
                    ],
                    'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                    'time_ago' => $message->created_at->diffForHumans(),
                ];
            });

            // Mark messages as read
            Message::where('sender_id', $user_id)
                ->where('receiver_id', $currentUser->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            return response()->json([
                'success' => true,
                'data' => [
                    'target_user' => [
                        'id' => $targetUser->id,
                        'user_name' => $targetUser->user_name,
                        'full_name' => $targetUser->full_name,
                        'profile_picture' => $targetUser->profile_picture_url,
                        'role' => $targetUser->role,
                    ],
                    'messages' => $messages,
                    'total_messages' => $messages->count()
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error fetching messages with user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch messages',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ MARK MESSAGES AS READ
    public function markAsRead(Request $request)
    {
        try {
            $user = Auth::user();
            
            $request->validate([
                'message_ids' => 'required|array',
                'message_ids.*' => 'integer|exists:messages,id'
            ]);

            Message::whereIn('id', $request->message_ids)
                ->where('receiver_id', $user->id)
                ->update(['is_read' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Messages marked as read'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error marking messages as read: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to mark messages as read',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ GET UNREAD MESSAGE COUNT
    public function getUnreadCount()
    {
        try {
            $user = Auth::user();

            $unreadCount = Message::where('receiver_id', $user->id)
                ->where('is_read', false)
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'unread_count' => $unreadCount
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error fetching unread count: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch unread count',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ DELETE MESSAGE
    public function deleteMessage($message_id)
    {
        try {
            $user = Auth::user();

            $message = Message::where('id', $message_id)
                ->where(function($query) use ($user) {
                    $query->where('sender_id', $user->id)
                          ->orWhere('receiver_id', $user->id);
                })
                ->first();

            if (!$message) {
                return response()->json([
                    'success' => false,
                    'error' => 'Message not found or unauthorized'
                ], 404);
            }

            $message->delete();

            return response()->json([
                'success' => true,
                'message' => 'Message deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error deleting message: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete message',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}