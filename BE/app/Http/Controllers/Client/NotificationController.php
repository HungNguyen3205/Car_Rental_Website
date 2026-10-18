<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Danh sách thông báo của người dùng hiện tại (kèm số lượng chưa đọc)
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::guard('sanctum')->user() ?? Auth::guard('admin')->user();
        if (!$user) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn cần đăng nhập để xem thông báo!',
            ], 401);
        }

        $perPage = min(max((int) $request->get('per_page', 15), 5), 50);

        $notifications = AppNotification::with(['booking:id,ma_dat_xe,car_id,tinh_trang', 'booking.car:id,name,images'])
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->paginate($perPage);

        $unreadCount = AppNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'status'       => 1,
            'data'         => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Đánh dấu một thông báo là đã đọc (bảo vệ quyền riêng tư người dùng)
     */
    public function markAsRead(Request $request, $id): JsonResponse
    {
        $user = Auth::guard('sanctum')->user() ?? Auth::guard('admin')->user();
        if (!$user) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn cần đăng nhập để thao tác!',
            ], 401);
        }

        $notification = AppNotification::find($id);

        if (!$notification) {
            return response()->json([
                'status'  => 0,
                'message' => 'Không tìm thấy thông báo!',
            ], 404);
        }

        // Kiểm tra quyền sở hữu thông báo
        if ((int) $notification->user_id !== (int) $user->id) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn không có quyền thao tác trên thông báo của người khác!',
            ], 403);
        }

        $notification->markAsRead();

        $unreadCount = AppNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'status'       => 1,
            'message'      => 'Đã đánh dấu thông báo là đã đọc.',
            'data'         => $notification,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Đánh dấu toàn bộ thông báo của người dùng hiện tại là đã đọc
     */
    public function markAllAsRead(): JsonResponse
    {
        $user = Auth::guard('sanctum')->user() ?? Auth::guard('admin')->user();
        if (!$user) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn cần đăng nhập để thao tác!',
            ], 401);
        }

        AppNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'status'       => 1,
            'message'      => 'Đã đánh dấu tất cả thông báo là đã đọc.',
            'unread_count' => 0,
        ]);
    }
}
