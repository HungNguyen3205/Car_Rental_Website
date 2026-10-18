<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Contact;
use App\Models\CustomerNote;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Authorization\PermissionMatrix;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    /**
     * Danh sách hồ sơ khách hàng với tìm kiếm, lọc và phân trang phía máy chủ.
     */
    public function index(Request $request): JsonResponse
    {
        $admin = Auth::guard('admin')->user() ?? $request->user();
        if (!$this->canAccessCustomerModule($admin)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn không có quyền truy cập danh sách khách hàng!',
            ], 403);
        }

        $query = User::query()
            ->with(['car:id,name,license_plate,image,type'])
            ->withCount(['bookings', 'contacts'])
            ->filter($request->only(['search', 'status', 'nhu_cau_thue', 'car_id']))
            ->orderByDesc('id');

        $perPage = min(max((int) $request->get('per_page', 15), 1), 100);
        $customers = $query->paginate($perPage);

        // Bổ sung thông tin phân loại rõ ràng để frontend không nhầm lẫn với admin
        $customers->getCollection()->transform(function (User $user) {
            $user->entity_type = 'user';
            $user->formatted_id = '#CUS-' . $user->id;
            return $user;
        });

        // Thống kê KPI tổng quan khách hàng
        $counts = [
            'total'          => User::count(),
            'active'         => User::where('tinh_trang', 1)->count(),
            'locked'         => User::where('tinh_trang', 0)->count(),
            'with_bookings'  => User::has('bookings')->count(),
            'with_contacts'  => User::has('contacts')->count(),
        ];

        return response()->json([
            'status' => 1,
            'data'   => $customers,
            'counts' => $counts,
        ]);
    }

    /**
     * Xem hồ sơ chi tiết một khách hàng: thông tin liên hệ, nhu cầu, xe quan tâm,
     * lịch sử đơn thuê xe (lọc tài chính theo quyền), liên hệ tư vấn và nhật ký chăm sóc.
     */
    public function show($id, Request $request): JsonResponse
    {
        $admin = Auth::guard('admin')->user() ?? $request->user();
        if (!$this->canAccessCustomerModule($admin)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn không có quyền xem hồ sơ khách hàng!',
            ], 403);
        }

        /** @var User|null $user */
        $user = User::with([
            'car:id,name,license_plate,image,type,price_per_day',
            'notes' => function ($q) {
                $q->with('admin:id,ho_ten,email,role')->orderByDesc('id');
            },
        ])->find($id);

        if (!$user) {
            return response()->json([
                'status'  => 0,
                'message' => 'Không tìm thấy hồ sơ khách hàng trong hệ thống!',
            ], 404);
        }

        $canViewFinancial = $this->canViewFinancialInfo($admin);

        // 1. Lấy lịch sử các đơn đặt xe của CHÍNH khách hàng này
        $bookings = Booking::where('user_id', $user->id)
            ->with(['car:id,name,license_plate,image,type'])
            ->orderByDesc('id')
            ->get();

        $bookingsData = $bookings->map(function (Booking $b) use ($canViewFinancial) {
            return [
                'id'                  => $b->id,
                'ma_dat_xe'           => $b->ma_dat_xe,
                'car'                 => $b->car,
                'start_date'          => $b->start_date,
                'end_date'            => $b->end_date,
                'tinh_trang'          => (int) $b->tinh_trang,
                'is_thanh_toan'       => (int) $b->is_thanh_toan,
                'tong_tien'           => $canViewFinancial ? (float) $b->tong_tien : null,
                'tong_tien_formatted' => $canViewFinancial ? (number_format((float) $b->tong_tien, 0, ',', '.') . ' đ') : '[Bảo mật doanh thu]',
                'created_at'          => $b->created_at,
            ];
        });

        // 2. Lấy lịch sử liên hệ / tư vấn (cả các contact gắn user_id và các contact khách vãng lai trùng email/SĐT)
        $contacts = Contact::where(function ($q) use ($user) {
            $q->where('user_id', $user->id);
            if (!empty($user->email)) {
                $q->orWhere('email', $user->email);
            }
            if (!empty($user->so_dien_thoai)) {
                $q->orWhere('so_dien_thoai', $user->so_dien_thoai);
            }
        })
        ->with([
            'car:id,name,license_plate,image,type',
            'assignee:id,ho_ten,email,role',
        ])
        ->orderByDesc('id')
        ->get();

        $contactsData = $contacts->map(function (Contact $c) use ($user) {
            return [
                'id'            => $c->id,
                'tieu_de'       => $c->tieu_de,
                'noi_dung'      => $c->noi_dung,
                'trang_thai'    => (int) $c->trang_thai,
                'created_at'    => $c->created_at,
                'is_linked'     => $c->user_id === $user->id,
                'car'           => $c->car,
                'assignee'      => $c->assignee,
            ];
        });

        // 3. Tính toán số liệu thống kê khách hàng
        $totalBookings = $bookings->count();
        $completedBookings = $bookings->where('tinh_trang', Booking::STATUS_COMPLETED)->count();
        $activeBookings = $bookings->whereIn('tinh_trang', [
            Booking::STATUS_PENDING,
            Booking::STATUS_CONFIRMED,
            Booking::STATUS_IN_PROGRESS,
            Booking::STATUS_RETURNED,
        ])->count();

        $totalSpent = null;
        if ($canViewFinancial) {
            $totalSpent = (float) $bookings->where('tinh_trang', '!=', Booking::STATUS_CANCELLED)->sum('tong_tien');
        }

        return response()->json([
            'status' => 1,
            'data'   => [
                'user' => [
                    'id'                => $user->id,
                    'formatted_id'      => '#CUS-' . $user->id,
                    'entity_type'       => 'user',
                    'ho_ten'            => $user->ho_ten,
                    'email'             => $user->email,
                    'so_dien_thoai'     => $user->so_dien_thoai,
                    'dia_chi'           => $user->dia_chi,
                    'ngay_sinh'         => $user->ngay_sinh,
                    'gioi_tinh'         => $user->gioi_tinh,
                    'is_active'         => $user->is_active,
                    'nhu_cau_thue'      => $user->nhu_cau_thue,
                    'nhu_cau_label'     => User::DEMANDS[$user->nhu_cau_thue]['label'] ?? 'Chưa xác định',
                    'ngan_sach_du_kien' => $user->ngan_sach_du_kien ? (float) $user->ngan_sach_du_kien : null,
                    'car_id'            => $user->car_id,
                    'car'               => $user->car,
                    'ghi_chu_so_thich'  => $user->ghi_chu_so_thich,
                    'gplx_hang'         => $user->gplx_hang,
                    'created_at'        => $user->created_at,
                    'notes'             => $user->notes,
                ],
                'stats' => [
                    'total_bookings'      => $totalBookings,
                    'completed_bookings'  => $completedBookings,
                    'active_bookings'     => $activeBookings,
                    'total_contacts'      => $contacts->count(),
                    'total_spent'         => $totalSpent,
                    'can_view_financial'  => $canViewFinancial,
                ],
                'bookings' => $bookingsData,
                'contacts' => $contactsData,
                'permissions' => [
                    'can_edit_profile'   => $this->canManageCustomerProfile($admin),
                    'can_manage_status'  => $admin->isOwner() || $admin->hasPermission(PermissionMatrix::PERM_ADMINS_MANAGE),
                    'can_view_financial' => $canViewFinancial,
                ],
            ],
        ]);
    }

    /**
     * Cập nhật thông tin hồ sơ, nhu cầu thuê và ghi chú sở thích của khách hàng.
     * Sử dụng API quản trị riêng biệt, tuyệt đối không lạm dụng API profile của người đăng nhập.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $admin = Auth::guard('admin')->user() ?? $request->user();
        if (!$this->canManageCustomerProfile($admin)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn không có quyền chỉnh sửa hồ sơ khách hàng!',
            ], 403);
        }

        $request->validate([
            'ho_ten'            => ['required', 'string', 'max:150'],
            'so_dien_thoai'     => ['nullable', 'string', 'max:20'],
            'dia_chi'           => ['nullable', 'string', 'max:255'],
            'ngay_sinh'         => ['nullable', 'date'],
            'gioi_tinh'         => ['nullable', 'string', 'max:10'],
            'nhu_cau_thue'      => ['nullable', 'string', 'in:self_drive,with_driver,monthly,wedding,travel'],
            'ngan_sach_du_kien' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'car_id'            => ['nullable', 'integer', 'exists:cars,id'],
            'ghi_chu_so_thich'  => ['nullable', 'string', 'max:2000'],
            'gplx_hang'         => ['nullable', 'string', 'max:20'],
        ], [
            'ho_ten.required'       => 'Họ và tên khách hàng không được để trống!',
            'nhu_cau_thue.in'       => 'Nhu cầu thuê xe không hợp lệ!',
            'car_id.exists'         => 'Xe quan tâm được chọn không tồn tại trong hệ thống!',
            'ghi_chu_so_thich.max'  => 'Ghi chú sở thích không được vượt quá 2000 ký tự!',
        ]);

        // Kiểm tra an toàn: Không lưu giấy tờ/thẻ nhạy cảm vào trường ghi chú tự do
        if ($request->filled('ghi_chu_so_thich')) {
            $note = $request->ghi_chu_so_thich;
            // Chặn lưu chuỗi số thẻ tín dụng 16 số liên tiếp
            if (preg_match('/\b(?:\d[ -]*?){13,19}\b/', $note)) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Vì lý do bảo mật, tuyệt đối không lưu thông tin thẻ ngân hàng hoặc giấy tờ nhạy cảm vào ghi chú tự do!',
                ], 422);
            }
        }

        /** @var User|null $user */
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'status'  => 0,
                'message' => 'Không tìm thấy khách hàng cần cập nhật!',
            ], 404);
        }

        $oldValues = $user->only([
            'ho_ten', 'so_dien_thoai', 'dia_chi', 'nhu_cau_thue',
            'ngan_sach_du_kien', 'car_id', 'ghi_chu_so_thich', 'gplx_hang'
        ]);

        $user->ho_ten = trim($request->ho_ten);
        $user->so_dien_thoai = $request->so_dien_thoai ? trim($request->so_dien_thoai) : null;
        $user->dia_chi = $request->dia_chi ? trim($request->dia_chi) : null;
        $user->ngay_sinh = $request->ngay_sinh ?: null;
        $user->gioi_tinh = $request->gioi_tinh ?: null;
        $user->nhu_cau_thue = $request->nhu_cau_thue ?: null;
        $user->ngan_sach_du_kien = $request->filled('ngan_sach_du_kien') ? $request->ngan_sach_du_kien : null;
        $user->car_id = $request->car_id ?: null;
        $user->ghi_chu_so_thich = $request->ghi_chu_so_thich ? trim($request->ghi_chu_so_thich) : null;
        $user->gplx_hang = $request->gplx_hang ? trim($request->gplx_hang) : null;
        $user->save();

        AuditService::log(
            action: 'customer.profile_updated',
            targetType: 'User',
            targetId: (string) $user->id,
            targetLabel: "Cập nhật hồ sơ khách hàng #CUS-{$user->id} ({$user->ho_ten})",
            newValues: $user->only([
                'ho_ten', 'so_dien_thoai', 'dia_chi', 'nhu_cau_thue',
                'ngan_sach_du_kien', 'car_id', 'ghi_chu_so_thich', 'gplx_hang'
            ]),
            oldValues: $oldValues
        );

        return response()->json([
            'status'  => 1,
            'message' => 'Cập nhật hồ sơ khách hàng thành công!',
            'data'    => $user->fresh(['car:id,name,license_plate,image,type']),
        ]);
    }

    /**
     * Thêm một bản ghi ghi chú chăm sóc khách hàng mới vào dòng thời gian.
     */
    public function addNote(Request $request, $id): JsonResponse
    {
        $admin = Auth::guard('admin')->user() ?? $request->user();
        if (!$this->canAccessCustomerModule($admin)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn không có quyền thêm ghi chú chăm sóc khách hàng!',
            ], 403);
        }

        $request->validate([
            'content' => ['required', 'string', 'min:2', 'max:2000'],
        ], [
            'content.required' => 'Vui lòng nhập nội dung ghi chú!',
            'content.min'      => 'Ghi chú phải có ít nhất 2 ký tự!',
            'content.max'      => 'Ghi chú không được vượt quá 2000 ký tự!',
        ]);

        // Kiểm tra an toàn bảo mật giấy tờ/thẻ
        if (preg_match('/\b(?:\d[ -]*?){13,19}\b/', $request->content)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Không lưu thông tin thẻ ngân hàng hoặc số tài liệu nhạy cảm vào ghi chú!',
            ], 422);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'status'  => 0,
                'message' => 'Khách hàng không tồn tại!',
            ], 404);
        }

        $note = CustomerNote::create([
            'user_id'    => $user->id,
            'admin_id'   => $admin->id,
            'admin_name' => $admin->ho_ten ?? 'Hệ thống Quản trị',
            'content'    => trim($request->content),
        ]);

        AuditService::log(
            action: 'customer.note_added',
            targetType: 'User',
            targetId: (string) $user->id,
            targetLabel: "Thêm ghi chú chăm sóc khách hàng #CUS-{$user->id}",
            newValues: ['content' => $request->content]
        );

        return response()->json([
            'status'  => 1,
            'message' => 'Đã thêm ghi chú chăm sóc khách hàng thành công!',
            'data'    => $note->load('admin:id,ho_ten,email,role'),
        ]);
    }

    /**
     * Khóa hoặc mở khóa tài khoản khách hàng.
     * TUYỆT ĐỐI KHÔNG XÓA LỊCH SỬ ĐƠN VÀ LỊCH SỬ TƯ VẤN CỦA KHÁCH.
     */
    public function toggleStatus(Request $request, $id): JsonResponse
    {
        $admin = Auth::guard('admin')->user() ?? $request->user();
        if (!$admin || (!$admin->isOwner() && !$admin->hasPermission(PermissionMatrix::PERM_ADMINS_MANAGE))) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn không có quyền thay đổi trạng thái tài khoản khách hàng!',
            ], 403);
        }

        /** @var User|null $user */
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'status'  => 0,
                'message' => 'Không tìm thấy tài khoản khách hàng trong hệ thống!',
            ], 404);
        }

        // Đổi trạng thái: 1 -> 0 hoặc 0 -> 1
        $oldStatus = (int) $user->tinh_trang;
        $newStatus = $oldStatus === 1 ? 0 : 1;
        $user->tinh_trang = $newStatus;
        $user->save();

        $actionText = $newStatus === 1 ? 'Mở khóa' : 'Khóa';

        AuditService::log(
            action: 'customer.status_toggled',
            targetType: 'User',
            targetId: (string) $user->id,
            targetLabel: "{$actionText} tài khoản khách hàng #CUS-{$user->id} ({$user->ho_ten})",
            newValues: ['tinh_trang' => $newStatus],
            oldValues: ['tinh_trang' => $oldStatus]
        );

        return response()->json([
            'status'  => 1,
            'message' => "Đã {$actionText} tài khoản khách hàng thành công!",
            'data'    => [
                'id'        => $user->id,
                'is_active' => $user->is_active,
            ],
        ]);
    }

    /**
     * Liên kết an toàn một yêu cầu tư vấn (Lead) của khách vãng lai với tài khoản khách hàng.
     */
    public function linkContact(Request $request, $id): JsonResponse
    {
        $admin = Auth::guard('admin')->user() ?? $request->user();
        if (!$this->canManageCustomerProfile($admin)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn không có quyền liên kết dữ liệu tư vấn với tài khoản khách hàng!',
            ], 403);
        }

        $request->validate([
            'contact_id' => ['required', 'integer', 'exists:contacts,id'],
        ]);

        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'status'  => 0,
                'message' => 'Khách hàng không tồn tại!',
            ], 404);
        }

        $contact = Contact::find($request->contact_id);
        if (!$contact) {
            return response()->json([
                'status'  => 0,
                'message' => 'Yêu cầu tư vấn không tồn tại!',
            ], 404);
        }

        $contact->user_id = $user->id;
        $contact->save();

        AuditService::log(
            action: 'customer.contact_linked',
            targetType: 'User',
            targetId: (string) $user->id,
            targetLabel: "Liên kết yêu cầu tư vấn #REQ-{$contact->id} với khách hàng #CUS-{$user->id}",
            newValues: ['contact_id' => $contact->id, 'user_id' => $user->id]
        );

        return response()->json([
            'status'  => 1,
            'message' => 'Đã liên kết yêu cầu tư vấn với hồ sơ khách hàng thành công!',
            'data'    => $contact->fresh(['car', 'assignee']),
        ]);
    }

    /**
     * Quyền xem danh sách hoặc chi tiết hồ sơ khách hàng:
     * - Chủ hệ thống (Owner)
     * - Hoặc có quyền bookings.view / bookings.manage / admins.manage
     */
    protected function canAccessCustomerModule(?Admin $admin): bool
    {
        if (!$admin) {
            return false;
        }

        return $admin->isOwner() || $admin->hasAnyPermission([
            PermissionMatrix::PERM_BOOKINGS_VIEW,
            PermissionMatrix::PERM_BOOKINGS_MANAGE,
            PermissionMatrix::PERM_ADMINS_MANAGE,
        ]);
    }

    /**
     * Quyền chỉnh sửa hồ sơ khách hàng:
     * - Chủ hệ thống hoặc có quyền bookings.manage / admins.manage
     */
    protected function canManageCustomerProfile(?Admin $admin): bool
    {
        if (!$admin) {
            return false;
        }

        return $admin->isOwner() || $admin->hasAnyPermission([
            PermissionMatrix::PERM_BOOKINGS_MANAGE,
            PermissionMatrix::PERM_ADMINS_MANAGE,
        ]);
    }

    /**
     * Quyền xem thông tin tài chính (doanh thu, tổng tiền đơn):
     * - Chủ hệ thống hoặc có quyền reports.view / payments.manage
     */
    protected function canViewFinancialInfo(?Admin $admin): bool
    {
        if (!$admin) {
            return false;
        }

        return $admin->isOwner() || $admin->hasAnyPermission([
            PermissionMatrix::PERM_REPORTS_VIEW,
            PermissionMatrix::PERM_PAYMENTS_MANAGE,
        ]);
    }
}
