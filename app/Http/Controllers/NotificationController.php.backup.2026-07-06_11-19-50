<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // جلب جميع الإشعارات
  public function index(Request $request)
{
    $notifications = $request->user()->notifications()->paginate(20);

    return response()->json([
        'notifications' => $notifications->map(function($notification) {
            return [
                'id' => $notification->id, // هذا هو UUID
                'type' => class_basename($notification->type),
                'data' => $notification->data,
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at,
            ];
        }),
        'unread_count' => $request->user()->unreadNotifications->count()
    ]);
}
    // جلب الإشعارات غير المقروءة فقط
    public function unread(Request $request)
    {
        $notifications = $request->user()->unreadNotifications()->paginate(20);

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $notifications->total()
        ]);
    }

    // تعليم إشعار كمقروء
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->find($id);

        if (!$notification) {
            return response()->json(['message' => 'الإشعار غير موجود'], 404);
        }

        $notification->markAsRead();

        return response()->json(['message' => 'تم تعليم الإشعار كمقروء']);
    }

    // تعليم جميع الإشعارات كمقروءة
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'تم تعليم جميع الإشعارات كمقروءة']);
    }

    // حذف إشعار
    public function destroy(Request $request, $id)
    {
        $notification = $request->user()->notifications()->find($id);

        if (!$notification) {
            return response()->json(['message' => 'الإشعار غير موجود'], 404);
        }

        $notification->delete();

        return response()->json(['message' => 'تم حذف الإشعار']);
    }

    // حذف جميع الإشعارات
    public function clearAll(Request $request)
    {
        $request->user()->notifications()->delete();

        return response()->json(['message' => 'تم حذف جميع الإشعارات']);
    }
}
