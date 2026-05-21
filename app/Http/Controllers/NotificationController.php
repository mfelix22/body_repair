<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = UserNotification::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        // Mark all as read when viewing the full list
        UserNotification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Mark one notification as read and redirect to its URL.
     */
    public function read(UserNotification $notification)
    {
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }

        $notification->update(['read_at' => now()]);

        return redirect($notification->url ?? route('notifications.index'));
    }

    public function markAllRead()
    {
        UserNotification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Lightweight polling endpoint — returns unread count + recent items as JSON.
     * Called by the frontend every 30 seconds.
     */
    public function poll(): JsonResponse
    {
        $userId = auth()->id();

        $unreadCount = UserNotification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();

        $recent = UserNotification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get()
            ->map(fn($n) => [
                'id'        => $n->id,
                'title'     => $n->title,
                'message'   => \Illuminate\Support\Str::limit($n->message, 60),
                'url'       => route('notifications.read', $n),
                'read'      => !is_null($n->read_at),
                'icon'      => $n->iconClass(),
                'ago'       => $n->created_at->diffForHumans(),
            ]);

        return response()->json([
            'unread_count' => $unreadCount,
            'items'        => $recent,
        ]);
    }
}
