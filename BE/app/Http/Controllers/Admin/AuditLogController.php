<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Tra cứu danh sách nhật ký kiểm toán với bộ lọc và phân trang
     * (Chỉ đọc, nghiêm cấm sửa / xóa)
     */
    public function index(Request $request)
    {
        $query = AuditLog::query()->orderByDesc('id');

        // 1. Lọc theo hành động (action)
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // 2. Lọc theo loại đối tượng thực hiện (actor_type: admin, user, system)
        if ($request->filled('actor_type')) {
            $query->where('actor_type', $request->actor_type);
        }

        // 3. Lọc theo loại đối tượng tác động (target_type: Car, Booking, Admin, User...)
        if ($request->filled('target_type')) {
            $query->where('target_type', $request->target_type);
        }

        // 4. Lọc theo khoảng thời gian
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // 5. Tìm kiếm từ khóa tự do
        if ($request->filled('search')) {
            $keyword = trim($request->search);
            $query->where(function ($q) use ($keyword) {
                $q->where('target_label', 'like', "%{$keyword}%")
                  ->orWhere('actor_name', 'like', "%{$keyword}%")
                  ->orWhere('actor_email', 'like', "%{$keyword}%")
                  ->orWhere('action', 'like', "%{$keyword}%")
                  ->orWhere('target_id', 'like', "%{$keyword}%");
            });
        }

        $perPage = (int) $request->get('per_page', 15);
        if ($perPage <= 0 || $perPage > 100) {
            $perPage = 15;
        }

        $logs = $query->paginate($perPage);

        return response()->json([
            'status' => 1,
            'data'   => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
                'per_page'     => $logs->perPage(),
                'total'        => $logs->total(),
            ],
        ]);
    }
}
