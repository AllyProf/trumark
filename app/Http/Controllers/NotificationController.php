<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Display a listing of all notifications.
     */
    public function index()
    {
        $user     = Auth::user();
        $branchId = $this->getActiveBranchId();

        $query = $user->notifications()->latest();

        // For super_admin with an active branch filter, scope to that branch's notifications
        if ($user->role === 'super_admin' && $branchId) {
            $query->whereJsonContains('data->branch_id', $branchId);
        }

        $notifications = $query->paginate(20);
        return view('notifications.index', compact('notifications'));
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->where('id', $id)->first();
        
        if ($notification) {
            $notification->markAsRead();
            
            if (request()->ajax()) {
                return response()->json(['success' => true]);
            }

            $url = $notification->data['action_url'] ?? route('dashboard');
            
            // If it's an absolute URL, try to extract just the path to avoid APP_URL issues
            if (str_starts_with($url, 'http')) {
                $parts = parse_url($url);
                $url = $parts['path'] ?? '/';
                if (isset($parts['query'])) {
                    $url .= '?' . $parts['query'];
                }
            }

            return redirect()->to($url);
        }

        return back()->with('error', 'Notification not found.');
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Fetch unread notifications for the header.
     */
    public function fetchUnread()
    {
        $notifications = Auth::user()->unreadNotifications;
        $count = $notifications->count();
        
        return response()->json([
            'count' => $count,
            'html' => view('layouts.partials.notification_list', compact('notifications'))->render()
        ]);
    }
}
