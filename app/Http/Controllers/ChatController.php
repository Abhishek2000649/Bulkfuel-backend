<?php

namespace App\Http\Controllers;

use App\Events\MessageSeen;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    public function sendMessage(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'message' => 'nullable|string',
                'file' => 'nullable|string',
                'conversation_id' => 'nullable|exists:conversations,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            // 🔹 Check empty message + file
            if (empty($request->message) && empty($request->file)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message or file is required'
                ], 422);
            }

            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized user'
                ], 401);
            }

            if ($request->conversation_id) {

                $conversation = Conversation::find($request->conversation_id);

                if (!$conversation) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Conversation not found'
                    ], 404);
                }

                if ($user->role === 'ADMIN') {
                    if (!$conversation->admin_id) {
                        $conversation->update([
                            'admin_id' => $user->id
                        ]);
                    }
                }
            } else {

                if (!in_array($user->role, ['USER', 'delivery_agent'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Only user or delivery agent can start conversation'
                    ], 403);
                }

                $conversation = Conversation::where('user_id', $user->id)
                    ->where('role', $user->role)
                    ->first();

                if (!$conversation) {
                    $conversation = Conversation::create([
                        'user_id' => $user->id,
                        'admin_id' => null,
                        'role' => $user->role
                    ]);
                }
            }

            if (
                in_array($user->role, ['USER', 'delivery_agent']) &&
                $conversation->user_id !== $user->id
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to send message in this conversation'
                ], 403);
            }
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'sender_type' => $user->role,
                'message' => $request->message,
                'file' => $request->file,
                'type' => $request->file ? 'file' : 'text',
                'is_seen' => false
            ]);
            broadcast(new MessageSent($message))->toOthers();
            $conversation->update([
                'last_message' => $request->message ?? 'File',
                'last_message_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'conversation_id' => $conversation->id,
                'message' => 'Message sent successfully',
                'data' => $message
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getMessages($conversationId = null)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized user'
                ], 401);
            }

            if ($conversationId) {
                $conversation = Conversation::find($conversationId);

                if (!$conversation) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Conversation not found'
                    ], 404);
                }

                if (
                    ($user->role === 'USER' && $conversation->user_id !== $user->id) ||
                    ($user->role === 'delivery_agent' && $conversation->user_id !== $user->id) ||
                    ($user->role === 'ADMIN' && $conversation->admin_id && $conversation->admin_id !== $user->id)
                ) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Access denied'
                    ], 403);
                }
            } else {
                $conversation = Conversation::where('user_id', $user->id)
                    ->where('role', $user->role)
                    ->first();

                if (!$conversation) {
                    return response()->json([
                        'success' => true,
                        'message' => 'No conversation found',
                        'messages' => []
                    ], 200);
                }
            }

            $messages = Message::where('conversation_id', $conversation->id)
                ->orderBy('created_at', 'asc')
                ->limit(100)->get();

            return response()->json([
                'success' => true,
                'conversation_id' => $conversation->id,
                'messages' => $messages
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ADMIN CHAT LIST
     */



    public function markSeen(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized user'
                ], 401);
            }

            $conversationId = $request->conversation_id;

            if (!$conversationId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation ID required'
                ], 422);
            }

            $conversation = Conversation::find($conversationId);

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found'
                ], 404);
            }
            if (
                in_array($user->role, ['USER', 'delivery_agent']) &&
                $conversation->user_id !== $user->id
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            Message::where('conversation_id', $conversation->id)
                ->where('sender_id', '!=', $user->id)
                ->update(['is_seen' => true]);
            broadcast(new MessageSeen($conversationId, $user->id));

            return response()->json([
                'success' => true,
                'message' => 'Messages marked as seen'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function contactusers()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized user'
                ], 401);
            }

            $conversations = Conversation::with('user')
                ->whereIn('role', ['USER', 'delivery_agent'])
                ->orderBy('last_message_at', 'desc')
                ->get();

            if ($conversations->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No conversations found',
                    'data' => []
                ], 200);
            }

            $users = $conversations->map(function ($conv) {

                if (!$conv->user) {
                    return null;
                }

                return [
                    'id' => $conv->user->id,
                    'name' => $conv->user->name,
                    'email' => $conv->user->email,
                    'role' => $conv->role,
                    'conversation_id' => $conv->id,

                    'last_message' => $conv->last_message ?? null,
                    'last_message_time' => $conv->last_message_at ?? null,
                ];
            })->filter()->values();

            return response()->json([
                'success' => true,
                'data' => $users
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
