<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 🔔 NotificationController - Центр уведомлений
 */
class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * 📋 Список уведомлений
     */
    public function index(Request $request)
    {
        $notifications = DB::table('admin_notifications')
            ->where('user_id', auth()->id())
            ->orWhereNull('user_id') // Глобальные уведомления
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $unreadCount = DB::table('admin_notifications')
            ->where(function($query) {
                $query->where('user_id', auth()->id())
                      ->orWhereNull('user_id');
            })
            ->where('read', false)
            ->count();

        if ($request->wantsJson()) {
            return response()->json([
                'notifications' => $notifications,
                'unread_count' => $unreadCount
            ]);
        }

        return view('admin.notifications.index', compact('notifications', 'unreadCount'));
    }

    /**
     * ✅ Отметить как прочитанное
     */
    public function markAsRead(Request $request, $id)
    {
        DB::table('admin_notifications')
            ->where('id', $id)
            ->where(function($query) {
                $query->where('user_id', auth()->id())
                      ->orWhereNull('user_id');
            })
            ->update(['read' => true, 'read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * ✅ Отметить все как прочитанные
     */
    public function markAllAsRead(Request $request)
    {
        DB::table('admin_notifications')
            ->where(function($query) {
                $query->where('user_id', auth()->id())
                      ->orWhereNull('user_id');
            })
            ->update(['read' => true, 'read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * 🗑️ Удалить уведомление
     */
    public function destroy($id)
    {
        DB::table('admin_notifications')
            ->where('id', $id)
            ->where(function($query) {
                $query->where('user_id', auth()->id())
                      ->orWhereNull('user_id');
            })
            ->delete();

        return response()->json(['success' => true]);
    }
}

