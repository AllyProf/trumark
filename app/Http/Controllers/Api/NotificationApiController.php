<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationApiController extends Controller
{
    /**
     * Get unread notifications.
     */
    public function index()
    {
        $notifications = Auth::user()->unreadNotifications;
        
        return response()->json([
            'success' => true,
            'count' => $notifications->count(),
            'data' => $notifications->map(function($n) {
                return [
                    'id' => $n->id,
                    'message' => $n->data['message'] ?? 'New Notification',
                    'type' => $n->data['type'] ?? 'general',
                    'action_url' => $n->data['action_url'] ?? null,
                    'time_ago' => $n->created_at->diffForHumans(),
                    'created_at' => $n->created_at,
                ];
            })
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->where('id', $id)->first();
        
        if ($notification) {
            $notification->markAsRead();
            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read.'
            ]);
        }

        return response()->json(['message' => 'Notification not found.'], 404);
    }

    /**
     * Mark all as read.
     */
    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.'
        ]);
    }
}
