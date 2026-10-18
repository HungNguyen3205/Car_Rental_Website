<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactController extends Controller
{
    /**
     * Danh sách yêu cầu liên hệ từ khách hàng (hỗ trợ lọc theo trạng thái, từ khóa và phân trang)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Contact::with(['user:id,ho_ten,email', 'handler:id,ho_ten,email'])
            ->orderByDesc('id');

        // Lọc theo trạng thái
        if ($request->has('trang_thai') && $request->trang_thai !== '' && $request->trang_thai !== null) {
            $query->where('trang_thai', (int) $request->trang_thai);
        }

        // Tìm kiếm theo từ khóa
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('ho_ten', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('so_dien_thoai', 'like', "%{$search}%")
                  ->orWhere('tieu_de', 'like', "%{$search}%");
            });
        }

        $perPage = min(max((int) $request->get('per_page', 15), 5), 100);
        $contacts = $query->paginate($perPage);

        return response()->json([
            'status' => 1,
            'data'   => $contacts,
            'counts' => [
                'all'         => Contact::count(),
                'pending'     => Contact::where('trang_thai', Contact::STATUS_PENDING)->count(),
                'in_progress' => Contact::where('trang_thai', Contact::STATUS_IN_PROGRESS)->count(),
                'resolved'    => Contact::where('trang_thai', Contact::STATUS_RESOLVED)->count(),
            ],
        ]);
    }

    /**
     * Xem chi tiết một yêu cầu liên hệ
     */
    public function show($id): JsonResponse
    {
        $contact = Contact::with(['user', 'handler'])->find($id);

        if (!$contact) {
            return response()->json([
                'status'  => 0,
                'message' => 'Không tìm thấy yêu cầu liên hệ này!',
            ], 404);
        }

        return response()->json([
            'status' => 1,
            'data'   => $contact,
        ]);
    }

    /**
     * Cập nhật trạng thái xử lý (Đang xử lý / Đã xử lý) và lưu ghi chú nội bộ
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $request->validate([
            'trang_thai'     => ['required', 'integer', 'in:0,1,2'],
            'ghi_chu_noi_bo' => ['nullable', 'string', 'max:2000'],
        ], [
            'trang_thai.required' => 'Vui lòng chọn trạng thái xử lý!',
            'trang_thai.in'       => 'Trạng thái xử lý không hợp lệ!',
            'ghi_chu_noi_bo.max'  => 'Ghi chú nội bộ không được vượt quá 2000 ký tự!',
        ]);

        $contact = Contact::find($id);
        if (!$contact) {
            return response()->json([
                'status'  => 0,
                'message' => 'Không tìm thấy yêu cầu liên hệ để cập nhật!',
            ], 404);
        }

        $admin = Auth::guard('admin')->user() ?? $request->user();
        $oldValues = [
            'trang_thai'     => $contact->trang_thai,
            'ghi_chu_noi_bo' => $contact->ghi_chu_noi_bo,
        ];

        $newStatus = (int) $request->trang_thai;
        $contact->trang_thai = $newStatus;

        if ($request->has('ghi_chu_noi_bo')) {
            $contact->ghi_chu_noi_bo = $request->ghi_chu_noi_bo;
        }

        if ($newStatus !== Contact::STATUS_PENDING) {
            $contact->handled_by = $admin?->id;
            $contact->handled_at = now();
        } else {
            $contact->handled_by = null;
            $contact->handled_at = null;
        }

        $contact->save();

        // Ghi nhận Audit Log
        AuditService::log(
            'contact.status_updated',
            'Contact',
            $contact->id,
            "Yêu cầu liên hệ #{$contact->id} - {$contact->ho_ten}",
            [
                'old_status'     => $oldValues['trang_thai'],
                'new_status'     => $newStatus,
                'ghi_chu_noi_bo' => $contact->ghi_chu_noi_bo,
            ]
        );

        return response()->json([
            'status'  => 1,
            'message' => 'Cập nhật trạng thái yêu cầu liên hệ thành công!',
            'data'    => $contact->fresh(['user', 'handler']),
        ]);
    }
}
