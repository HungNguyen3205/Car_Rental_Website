<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminNotification;
use App\Models\Appointment;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Contact;
use App\Models\ContactActivity;
use App\Models\CustomerNote;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Authorization\PermissionMatrix;
use App\Services\Notification\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppointmentController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Danh sách lịch hẹn tư vấn & xem xe dạng bảng (List View) với bộ lọc, tìm kiếm và phân trang.
     */
    public function index(Request $request): JsonResponse
    {
        $admin = Auth::guard('admin')->user() ?? $request->user();
        if (!$this->canViewAppointments($admin)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn không có quyền xem danh sách lịch hẹn!',
            ], 403);
        }

        $query = Appointment::with([
            'user:id,ho_ten,email,so_dien_thoai',
            'contact:id,tieu_de,trang_thai',
            'assignee:id,ho_ten,email,role',
            'car:id,name,license_plate,image,type',
            'canceller:id,ho_ten,email',
        ])
        ->filter($request->only([
            'search',
            'status',
            'assigned_to',
            'car_id',
            'meeting_type',
            'user_id',
            'contact_id',
            'from_date',
            'to_date',
        ]))
        ->orderBy('start_time', 'desc');

        $perPage = min(max((int) $request->get('per_page', 15), 1), 100);
        $appointments = $query->paginate($perPage);

        // Bổ sung cờ cảnh báo lịch thuê xe (soft warning)
        $this->enrichAppointmentsWithRentalWarning($appointments->getCollection());

        // Thống kê KPI tổng quan lịch hẹn
        $todayStart = Carbon::today()->startOfDay();
        $todayEnd = Carbon::today()->endOfDay();

        $counts = [
            'total'       => Appointment::count(),
            'scheduled'   => Appointment::where('status', Appointment::STATUS_SCHEDULED)->count(),
            'completed'   => Appointment::where('status', Appointment::STATUS_COMPLETED)->count(),
            'cancelled'   => Appointment::where('status', Appointment::STATUS_CANCELLED)->count(),
            'no_show'     => Appointment::where('status', Appointment::STATUS_NO_SHOW)->count(),
            'today'       => Appointment::whereBetween('start_time', [$todayStart, $todayEnd])->count(),
            'my_assigned' => $admin ? Appointment::where('assigned_to', $admin->id)->where('status', Appointment::STATUS_SCHEDULED)->count() : 0,
        ];

        return response()->json([
            'status' => 1,
            'data'   => $appointments,
            'counts' => $counts,
        ]);
    }

    /**
     * Dữ liệu hiển thị Lịch trực quan theo Ngày / Tuần / Tháng (Calendar View).
     * Phân biệt rõ rệt với Lịch đội xe tại Admin/Calendar.
     */
    public function calendarView(Request $request): JsonResponse
    {
        $admin = Auth::guard('admin')->user() ?? $request->user();
        if (!$this->canViewAppointments($admin)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn không có quyền xem lịch hẹn tư vấn!',
            ], 403);
        }

        $viewType = $request->get('view_type', 'week'); // 'day', 'week', 'month'
        $dateParam = $request->get('date');

        try {
            $anchor = $dateParam ? Carbon::parse($dateParam) : Carbon::now();
        } catch (\Throwable $e) {
            $anchor = Carbon::now();
        }

        switch ($viewType) {
            case 'day':
                $start = $anchor->copy()->startOfDay();
                $end   = $anchor->copy()->endOfDay();
                break;
            case 'month':
                $start = $anchor->copy()->startOfMonth()->startOfDay();
                $end   = $anchor->copy()->endOfMonth()->endOfDay();
                break;
            case 'week':
            default:
                $start = $anchor->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
                $end   = $anchor->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
                break;
        }

        // Tối đa khoảng xem 62 ngày để đảm bảo hiệu năng
        if ($end->diffInDays($start) > 62) {
            $end = $start->copy()->addDays(62)->endOfDay();
        }

        $query = Appointment::with([
            'user:id,ho_ten,email,so_dien_thoai',
            'contact:id,tieu_de,trang_thai',
            'assignee:id,ho_ten,email,role',
            'car:id,name,license_plate,image,type',
        ])
        ->where('start_time', '<=', $end->format('Y-m-d H:i:s'))
        ->where('end_time', '>=', $start->format('Y-m-d H:i:s'))
        ->filter($request->only(['assigned_to', 'car_id', 'status', 'meeting_type']))
        ->orderBy('start_time', 'asc');

        $appointments = $query->get();

        // Đính kèm cờ xe có đang bận lịch thuê hay không để nhân viên biết chuẩn bị
        $this->enrichAppointmentsWithRentalWarning($appointments);

        return response()->json([
            'status' => 1,
            'data'   => $appointments,
            'period' => [
                'view_type' => $viewType,
                'start'     => $start->format('Y-m-d H:i:s'),
                'end'       => $end->format('Y-m-d H:i:s'),
                'anchor'    => $anchor->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Danh sách nhân viên hoạt động có quyền tham gia tư vấn / xem xe.
     */
    public function getAvailableStaff(): JsonResponse
    {
        $staff = Admin::where('tinh_trang', 1)
            ->get()
            ->filter(function (Admin $admin) {
                return $admin->isOwner() || $admin->hasAnyPermission([
                    PermissionMatrix::PERM_BOOKINGS_VIEW,
                    PermissionMatrix::PERM_BOOKINGS_MANAGE,
                    PermissionMatrix::PERM_ADMINS_MANAGE,
                ]);
            })
            ->map(function (Admin $admin) {
                return [
                    'id'         => $admin->id,
                    'ho_ten'     => $admin->ho_ten,
                    'email'      => $admin->email,
                    'role'       => $admin->admin_role,
                    'role_label' => $admin->isOwner() ? 'Chủ hệ thống' : ($admin->admin_role === 'dispatcher' ? 'Điều phối viên' : 'Nhân viên tư vấn'),
                ];
            })
            ->values();

        return response()->json([
            'status' => 1,
            'data'   => $staff,
        ]);
    }

    /**
     * Chi tiết lịch hẹn tư vấn & xem xe.
     */
    public function show($id): JsonResponse
    {
        $admin = Auth::guard('admin')->user() ?? request()->user();
        if (!$this->canViewAppointments($admin)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn không có quyền xem chi tiết lịch hẹn!',
            ], 403);
        }

        $appointment = Appointment::with([
            'user:id,ho_ten,email,so_dien_thoai,dia_chi,nhu_cau_thue,ngan_sach_du_kien',
            'contact:id,tieu_de,noi_dung,trang_thai,created_at',
            'contact.activities',
            'assignee:id,ho_ten,email,role',
            'car:id,name,license_plate,image,type,price_per_day,status',
            'canceller:id,ho_ten,email',
            'creator:id,ho_ten,email',
            'updater:id,ho_ten,email',
        ])->find($id);

        if (!$appointment) {
            return response()->json([
                'status'  => 0,
                'message' => 'Lịch hẹn không tồn tại trên hệ thống!',
            ], 404);
        }

        // Bổ sung kiểm tra lịch thuê của xe nếu có xe quan tâm
        $rentalWarning = false;
        if ($appointment->car_id) {
            $rentalWarning = Booking::where('car_id', $appointment->car_id)
                ->whereIn('tinh_trang', [Booking::STATUS_CONFIRMED, Booking::STATUS_IN_PROGRESS])
                ->where('start_date', '<', $appointment->end_time->format('Y-m-d H:i:s'))
                ->where('end_date', '>', $appointment->start_time->format('Y-m-d H:i:s'))
                ->exists();
        }
        $appointment->car_rental_conflict = $rentalWarning;

        return response()->json([
            'status' => 1,
            'data'   => $appointment,
        ]);
    }

    /**
     * Tạo mới lịch hẹn tư vấn & xem xe.
     * Cho phép tạo độc lập, tạo từ yêu cầu tư vấn (contact_id), hoặc tạo từ hồ sơ khách (user_id).
     * Tuyệt đối KHÔNG tự tạo hoặc giữ lịch thuê xe trong hệ thống booking.
     */
    public function store(Request $request): JsonResponse
    {
        $admin = Auth::guard('admin')->user() ?? $request->user();
        if (!$this->canManageAppointments($admin)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn không có quyền tạo lịch hẹn tư vấn!',
            ], 403);
        }

        $validated = $request->validate([
            'customer_name'    => 'required|string|max:190',
            'customer_phone'   => 'required|string|max:50',
            'customer_email'   => 'nullable|email|max:190',
            'assigned_to'      => 'required|integer|exists:admins,id',
            'car_id'           => 'nullable|integer|exists:cars,id',
            'user_id'          => 'nullable|integer|exists:users,id',
            'contact_id'       => 'nullable|integer|exists:contacts,id',
            'meeting_type'     => 'required|string|in:viewing,consultation,online',
            'location'         => 'required|string|max:255',
            'start_time'       => 'required|date',
            'end_time'         => 'required|date|after:start_time',
            'reminder_minutes' => 'nullable|integer|min:0|max:10080',
            'notes'            => 'nullable|string|max:1000',
        ], [
            'customer_name.required'  => 'Vui lòng cung cấp họ tên khách hàng!',
            'customer_phone.required' => 'Vui lòng cung cấp số điện thoại khách hàng!',
            'assigned_to.required'    => 'Vui lòng phân công nhân viên tư vấn!',
            'end_time.after'          => 'Thời gian kết thúc phải diễn ra sau thời gian bắt đầu!',
            'location.required'       => 'Vui lòng nhập địa điểm hoặc hình thức hẹn gặp!',
        ]);

        $startTime = Carbon::parse($validated['start_time']);
        $endTime   = Carbon::parse($validated['end_time']);

        // 1. Kiểm tra thời lượng tối thiểu 15 phút
        if (($endTime->getTimestamp() - $startTime->getTimestamp()) < 15 * 60) {
            return response()->json([
                'status'  => 0,
                'message' => 'Thời lượng cuộc hẹn phải tối thiểu 15 phút!',
            ], 422);
        }

        // 2. Kiểm tra nhân viên có đang hoạt động hay không
        $staff = Admin::find($validated['assigned_to']);
        if (!$staff || $staff->tinh_trang != 1) {
            return response()->json([
                'status'  => 0,
                'message' => 'Nhân viên được phân công hiện đang tạm khóa hoặc không hoạt động!',
            ], 422);
        }

        // 3. Quy tắc kiểm tra xung đột lịch nhân viên (Staff Conflict Rule)
        // Nhân viên không thể có 2 lịch hẹn đang hiệu lực (scheduled) giao nhau về thời gian
        $conflict = Appointment::where('assigned_to', $staff->id)
            ->where('status', Appointment::STATUS_SCHEDULED)
            ->where('start_time', '<', $endTime->format('Y-m-d H:i:s'))
            ->where('end_time', '>', $startTime->format('Y-m-d H:i:s'))
            ->first();

        if ($conflict) {
            $confStart = $conflict->start_time->format('H:i d/m/Y');
            $confEnd   = $conflict->end_time->format('H:i');
            return response()->json([
                'status'                  => 0,
                'message'                 => "Xung đột lịch: Chuyên viên {$staff->ho_ten} đã có lịch hẹn {$conflict->formatted_id} ({$confStart} - {$confEnd}) trong khung giờ này. Vui lòng chọn thời gian khác hoặc phân công chuyên viên khác!",
                'conflict_appointment_id' => $conflict->id,
            ], 422);
        }

        $appointment = DB::transaction(function () use ($validated, $admin, $staff, $startTime, $endTime) {
            $apt = Appointment::create([
                'customer_name'    => trim($validated['customer_name']),
                'customer_phone'   => trim($validated['customer_phone']),
                'customer_email'   => !empty($validated['customer_email']) ? trim($validated['customer_email']) : null,
                'assigned_to'      => $staff->id,
                'car_id'           => $validated['car_id'] ?? null,
                'user_id'          => $validated['user_id'] ?? null,
                'contact_id'       => $validated['contact_id'] ?? null,
                'meeting_type'     => $validated['meeting_type'],
                'location'         => trim($validated['location']),
                'start_time'       => $startTime,
                'end_time'         => $endTime,
                'status'           => Appointment::STATUS_SCHEDULED,
                'reminder_minutes' => (int) ($validated['reminder_minutes'] ?? 60),
                'notes'            => !empty($validated['notes']) ? trim($validated['notes']) : null,
                'created_by'       => $admin->id,
                'updated_by'       => $admin->id,
            ]);

            // Nếu tạo từ yêu cầu tư vấn (Contact), liên kết và cập nhật trạng thái Contact
            if (!empty($validated['contact_id'])) {
                $contact = Contact::find($validated['contact_id']);
                if ($contact) {
                    if ($contact->trang_thai === Contact::STATUS_PENDING) {
                        $contact->trang_thai = Contact::STATUS_IN_PROGRESS;
                        $contact->updated_by = $admin->id;
                        $contact->save();
                    }

                    ContactActivity::create([
                        'contact_id' => $contact->id,
                        'admin_id'   => $admin->id,
                        'admin_name' => $admin->ho_ten,
                        'type'       => 'appointment_scheduled',
                        'content'    => "Đã lên lịch hẹn tư vấn/xem xe {$apt->formatted_id} ({$startTime->format('H:i d/m/Y')}) tại {$apt->location}, phân công chuyên viên {$staff->ho_ten}.",
                        'metadata'   => [
                            'appointment_id' => $apt->id,
                            'meeting_type'   => $apt->meeting_type,
                            'start_time'     => $startTime->format('Y-m-d H:i:s'),
                            'assigned_to'    => $staff->id,
                        ],
                    ]);
                }
            }

            // Nếu có hồ sơ khách (User), ghi nhật ký chăm sóc khách hàng
            if (!empty($validated['user_id'])) {
                CustomerNote::create([
                    'user_id'    => $validated['user_id'],
                    'admin_id'   => $admin->id,
                    'admin_name' => $admin->ho_ten,
                    'content'    => "Đã đặt lịch hẹn tư vấn/xem xe {$apt->formatted_id} lúc {$startTime->format('H:i d/m/Y')}. Hình thức: {$apt->meeting_type_label}. Địa điểm: {$apt->location}.",
                ]);
            }

            // Ghi nhận Audit Log
            AuditService::log(
                action: 'appointment.create',
                targetType: 'appointment',
                targetId: $apt->id,
                targetLabel: "Lịch hẹn {$apt->formatted_id} - Khách {$apt->customer_name}",
                newValues: [
                    'customer_name' => $apt->customer_name,
                    'assigned_to'   => $staff->id,
                    'staff_name'    => $staff->ho_ten,
                    'start_time'    => $startTime->format('Y-m-d H:i:s'),
                    'end_time'      => $endTime->format('Y-m-d H:i:s'),
                    'meeting_type'  => $apt->meeting_type,
                    'location'      => $apt->location,
                ]
            );

            // Phát hành thông báo nội bộ (Chống gửi trùng bằng dedup_key, KHÔNG gửi SMS/email thật)
            $this->notificationService->dispatchAfterCommit(function () use ($apt, $staff, $startTime) {
                // 1. Thông báo cho nhân viên được giao phụ trách
                $this->notificationService->createAdminNotification(
                    adminId: $staff->id,
                    type: AdminNotification::TYPE_APPOINTMENT_ASSIGNED,
                    title: "Bạn có lịch hẹn tư vấn mới {$apt->formatted_id}",
                    content: "Lịch hẹn gặp khách hàng {$apt->customer_name} ({$apt->customer_phone}) lúc {$startTime->format('H:i d/m/Y')} tại {$apt->location}.",
                    actionUrl: '/admin/appointments',
                    data: ['appointment_id' => $apt->id],
                    dedupKey: "appointment_{$apt->id}_assigned_{$staff->id}"
                );

                // 2. Thông báo in-app cho khách hàng nếu khách đã có tài khoản user
                if ($apt->user_id) {
                    $this->notificationService->createInAppNotification(
                        userId: $apt->user_id,
                        type: 'appointment_scheduled',
                        title: "Lịch hẹn tư vấn & xem xe đã được xếp lịch",
                        content: "Lịch hẹn {$apt->formatted_id} của bạn vào lúc {$startTime->format('H:i d/m/Y')} tại {$apt->location} đã được tiếp nhận.",
                        actionUrl: '/profile',
                        data: ['appointment_id' => $apt->id],
                        dedupKey: "appointment_{$apt->id}_scheduled_user"
                    );
                }
            });

            return $apt;
        });

        return response()->json([
            'status'  => 1,
            'message' => "Tạo lịch hẹn {$appointment->formatted_id} thành công!",
            'data'    => $appointment->fresh(['user', 'contact', 'assignee', 'car']),
        ], 201);
    }

    /**
     * Chỉnh sửa / Đổi lịch hẹn tư vấn (Reschedule / Update).
     */
    public function update(Request $request, $id): JsonResponse
    {
        $admin = Auth::guard('admin')->user() ?? $request->user();
        if (!$this->canManageAppointments($admin)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn không có quyền cập nhật lịch hẹn!',
            ], 403);
        }

        $appointment = Appointment::find($id);
        if (!$appointment) {
            return response()->json([
                'status'  => 0,
                'message' => 'Lịch hẹn không tồn tại trên hệ thống!',
            ], 404);
        }

        if ($appointment->status === Appointment::STATUS_CANCELLED) {
            return response()->json([
                'status'  => 0,
                'message' => 'Lịch hẹn đã bị hủy, không thể chỉnh sửa! Vui lòng tạo lịch hẹn mới.',
            ], 422);
        }

        $validated = $request->validate([
            'customer_name'    => 'required|string|max:190',
            'customer_phone'   => 'required|string|max:50',
            'customer_email'   => 'nullable|email|max:190',
            'assigned_to'      => 'required|integer|exists:admins,id',
            'car_id'           => 'nullable|integer|exists:cars,id',
            'meeting_type'     => 'required|string|in:viewing,consultation,online',
            'location'         => 'required|string|max:255',
            'start_time'       => 'required|date',
            'end_time'         => 'required|date|after:start_time',
            'reminder_minutes' => 'nullable|integer|min:0|max:10080',
            'notes'            => 'nullable|string|max:1000',
        ]);

        $startTime = Carbon::parse($validated['start_time']);
        $endTime   = Carbon::parse($validated['end_time']);

        if (($endTime->getTimestamp() - $startTime->getTimestamp()) < 15 * 60) {
            return response()->json([
                'status'  => 0,
                'message' => 'Thời lượng cuộc hẹn phải tối thiểu 15 phút!',
            ], 422);
        }

        $staff = Admin::find($validated['assigned_to']);
        if (!$staff || $staff->tinh_trang != 1) {
            return response()->json([
                'status'  => 0,
                'message' => 'Nhân viên được phân công hiện đang tạm khóa hoặc không hoạt động!',
            ], 422);
        }

        // Kiểm tra xung đột loại trừ chính cuộc hẹn này
        $conflict = Appointment::where('assigned_to', $staff->id)
            ->where('status', Appointment::STATUS_SCHEDULED)
            ->where('id', '!=', $appointment->id)
            ->where('start_time', '<', $endTime->format('Y-m-d H:i:s'))
            ->where('end_time', '>', $startTime->format('Y-m-d H:i:s'))
            ->first();

        if ($conflict) {
            $confStart = $conflict->start_time->format('H:i d/m/Y');
            $confEnd   = $conflict->end_time->format('H:i');
            return response()->json([
                'status'                  => 0,
                'message'                 => "Xung đột lịch: Chuyên viên {$staff->ho_ten} đã có lịch hẹn {$conflict->formatted_id} ({$confStart} - {$confEnd}) trong khung giờ này!",
                'conflict_appointment_id' => $conflict->id,
            ], 422);
        }

        $oldValues = $appointment->only(['assigned_to', 'start_time', 'end_time', 'location', 'meeting_type']);
        $isAssigneeChanged = (int) $appointment->assigned_to !== (int) $staff->id;

        DB::transaction(function () use ($appointment, $validated, $admin, $staff, $startTime, $endTime, $oldValues, $isAssigneeChanged) {
            $appointment->update([
                'customer_name'    => trim($validated['customer_name']),
                'customer_phone'   => trim($validated['customer_phone']),
                'customer_email'   => !empty($validated['customer_email']) ? trim($validated['customer_email']) : null,
                'assigned_to'      => $staff->id,
                'car_id'           => $validated['car_id'] ?? null,
                'meeting_type'     => $validated['meeting_type'],
                'location'         => trim($validated['location']),
                'start_time'       => $startTime,
                'end_time'         => $endTime,
                'reminder_minutes' => (int) ($validated['reminder_minutes'] ?? 60),
                'notes'            => !empty($validated['notes']) ? trim($validated['notes']) : null,
                'updated_by'       => $admin->id,
            ]);

            // Ghi audit log
            AuditService::log(
                action: 'appointment.update',
                targetType: 'appointment',
                targetId: $appointment->id,
                targetLabel: "Cập nhật lịch hẹn {$appointment->formatted_id}",
                newValues: [
                    'assigned_to'  => $staff->id,
                    'start_time'   => $startTime->format('Y-m-d H:i:s'),
                    'end_time'     => $endTime->format('Y-m-d H:i:s'),
                    'location'     => $appointment->location,
                    'meeting_type' => $appointment->meeting_type,
                ],
                oldValues: $oldValues
            );

            // Nếu đổi chuyên viên phụ trách, gửi thông báo nội bộ cho người mới
            if ($isAssigneeChanged) {
                $this->notificationService->dispatchAfterCommit(function () use ($appointment, $staff, $startTime) {
                    $this->notificationService->createAdminNotification(
                        adminId: $staff->id,
                        type: AdminNotification::TYPE_APPOINTMENT_ASSIGNED,
                        title: "Bạn được điều phối lịch hẹn {$appointment->formatted_id}",
                        content: "Lịch hẹn khách {$appointment->customer_name} lúc {$startTime->format('H:i d/m/Y')} đã được chuyển giao cho bạn.",
                        actionUrl: '/admin/appointments',
                        data: ['appointment_id' => $appointment->id],
                        dedupKey: "appointment_{$appointment->id}_reassigned_{$staff->id}"
                    );
                });
            }
        });

        return response()->json([
            'status'  => 1,
            'message' => "Cập nhật lịch hẹn {$appointment->formatted_id} thành công!",
            'data'    => $appointment->fresh(['user', 'contact', 'assignee', 'car']),
        ]);
    }

    /**
     * Hủy lịch hẹn tư vấn (Cancel Appointment).
     * Giải phóng khung giờ cho chuyên viên phụ trách và lưu trữ lý do hủy minh bạch.
     */
    public function cancel(Request $request, $id): JsonResponse
    {
        $admin = Auth::guard('admin')->user() ?? $request->user();
        if (!$this->canManageAppointments($admin)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn không có quyền hủy lịch hẹn!',
            ], 403);
        }

        $appointment = Appointment::find($id);
        if (!$appointment) {
            return response()->json([
                'status'  => 0,
                'message' => 'Lịch hẹn không tồn tại trên hệ thống!',
            ], 404);
        }

        if ($appointment->status === Appointment::STATUS_CANCELLED) {
            return response()->json([
                'status'  => 0,
                'message' => 'Lịch hẹn này đã bị hủy từ trước!',
            ], 422);
        }

        if ($appointment->status === Appointment::STATUS_COMPLETED) {
            return response()->json([
                'status'  => 0,
                'message' => 'Lịch hẹn đã hoàn thành, không thể hủy!',
            ], 422);
        }

        $validated = $request->validate([
            'cancel_reason' => 'required|string|min:3|max:500',
        ], [
            'cancel_reason.required' => 'Vui lòng cung cấp lý do hủy cuộc hẹn!',
            'cancel_reason.min'      => 'Lý do hủy phải có ít nhất 3 ký tự!',
        ]);

        DB::transaction(function () use ($appointment, $validated, $admin) {
            $appointment->update([
                'status'        => Appointment::STATUS_CANCELLED,
                'cancel_reason' => trim($validated['cancel_reason']),
                'cancelled_by'  => $admin->id,
                'cancelled_at'  => Carbon::now(),
                'updated_by'    => $admin->id,
            ]);

            // Nếu có liên kết Contact, ghi lịch sử hoạt động
            if ($appointment->contact_id) {
                ContactActivity::create([
                    'contact_id' => $appointment->contact_id,
                    'admin_id'   => $admin->id,
                    'admin_name' => $admin->ho_ten,
                    'type'       => 'appointment_cancelled',
                    'content'    => "Lịch hẹn {$appointment->formatted_id} đã bị hủy bởi {$admin->ho_ten}. Lý do: {$appointment->cancel_reason}",
                    'metadata'   => ['appointment_id' => $appointment->id],
                ]);
            }

            // Nếu có liên kết User, ghi nhật ký chăm sóc
            if ($appointment->user_id) {
                CustomerNote::create([
                    'user_id'    => $appointment->user_id,
                    'admin_id'   => $admin->id,
                    'admin_name' => $admin->ho_ten,
                    'content'    => "Lịch hẹn {$appointment->formatted_id} đã bị hủy. Lý do: {$appointment->cancel_reason}",
                ]);
            }

            // Ghi Audit Log
            AuditService::log(
                action: 'appointment.cancel',
                targetType: 'appointment',
                targetId: $appointment->id,
                targetLabel: "Hủy lịch hẹn {$appointment->formatted_id}",
                newValues: [
                    'status'        => Appointment::STATUS_CANCELLED,
                    'cancel_reason' => $appointment->cancel_reason,
                    'cancelled_by'  => $admin->id,
                ]
            );

            // Thông báo nội bộ cho nhân viên phụ trách nếu người hủy không phải là người được giao
            if ($appointment->assigned_to && $appointment->assigned_to !== $admin->id) {
                $this->notificationService->dispatchAfterCommit(function () use ($appointment, $admin) {
                    $this->notificationService->createAdminNotification(
                        adminId: $appointment->assigned_to,
                        type: AdminNotification::TYPE_APPOINTMENT_CANCELLED,
                        title: "Lịch hẹn {$appointment->formatted_id} đã bị hủy",
                        content: "Lịch hẹn với khách {$appointment->customer_name} đã bị hủy bởi {$admin->ho_ten}. Lý do: {$appointment->cancel_reason}",
                        actionUrl: '/admin/appointments',
                        data: ['appointment_id' => $appointment->id],
                        dedupKey: "appointment_{$appointment->id}_cancelled"
                    );
                });
            }
        });

        return response()->json([
            'status'  => 1,
            'message' => "Hủy lịch hẹn {$appointment->formatted_id} thành công!",
            'data'    => $appointment->fresh(['user', 'contact', 'assignee', 'car', 'canceller']),
        ]);
    }

    /**
     * Ghi nhận kết quả sau buổi hẹn (Complete Appointment / Record Result).
     */
    public function complete(Request $request, $id): JsonResponse
    {
        $admin = Auth::guard('admin')->user() ?? $request->user();
        if (!$this->canManageAppointments($admin)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn không có quyền hoàn tất lịch hẹn!',
            ], 403);
        }

        $appointment = Appointment::find($id);
        if (!$appointment) {
            return response()->json([
                'status'  => 0,
                'message' => 'Lịch hẹn không tồn tại trên hệ thống!',
            ], 404);
        }

        if ($appointment->status === Appointment::STATUS_CANCELLED) {
            return response()->json([
                'status'  => 0,
                'message' => 'Lịch hẹn đã bị hủy, không thể chốt kết quả!',
            ], 422);
        }

        $validated = $request->validate([
            'result_summary' => 'required|string|min:3|max:1000',
            'status'         => 'required|string|in:completed,no_show',
        ], [
            'result_summary.required' => 'Vui lòng nhập kết quả hoặc nội dung buổi tư vấn!',
            'status.in'               => 'Trạng thái kết thúc không hợp lệ!',
        ]);

        DB::transaction(function () use ($appointment, $validated, $admin) {
            $appointment->update([
                'status'         => $validated['status'],
                'result_summary' => trim($validated['result_summary']),
                'updated_by'     => $admin->id,
            ]);

            // Cập nhật trạng thái contact nếu có
            if ($appointment->contact_id) {
                ContactActivity::create([
                    'contact_id' => $appointment->contact_id,
                    'admin_id'   => $admin->id,
                    'admin_name' => $admin->ho_ten,
                    'type'       => 'appointment_completed',
                    'content'    => "Lịch hẹn {$appointment->formatted_id} hoàn tất ({$appointment->status_label}). Kết quả: {$appointment->result_summary}",
                    'metadata'   => ['appointment_id' => $appointment->id, 'status' => $appointment->status],
                ]);
            }

            // Ghi nhật ký khách hàng nếu có
            if ($appointment->user_id) {
                CustomerNote::create([
                    'user_id'    => $appointment->user_id,
                    'admin_id'   => $admin->id,
                    'admin_name' => $admin->ho_ten,
                    'content'    => "Kết quả lịch hẹn {$appointment->formatted_id}: {$appointment->result_summary}",
                ]);
            }

            // Ghi Audit Log
            AuditService::log(
                action: 'appointment.complete',
                targetType: 'appointment',
                targetId: $appointment->id,
                targetLabel: "Hoàn tất lịch hẹn {$appointment->formatted_id}",
                newValues: [
                    'status'         => $appointment->status,
                    'result_summary' => $appointment->result_summary,
                ]
            );
        });

        return response()->json([
            'status'  => 1,
            'message' => "Ghi nhận kết quả lịch hẹn {$appointment->formatted_id} thành công!",
            'data'    => $appointment->fresh(['user', 'contact', 'assignee', 'car']),
        ]);
    }

    /**
     * Kích hoạt nhắc hẹn trong ứng dụng (In-app Reminder) với cơ chế chống gửi trùng.
     * KHÔNG tự gửi SMS/email thật.
     */
    public function triggerReminder(Request $request, $id): JsonResponse
    {
        $admin = Auth::guard('admin')->user() ?? $request->user();
        if (!$this->canManageAppointments($admin)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn không có quyền kích hoạt nhắc hẹn!',
            ], 403);
        }

        $appointment = Appointment::find($id);
        if (!$appointment || $appointment->status !== Appointment::STATUS_SCHEDULED) {
            return response()->json([
                'status'  => 0,
                'message' => 'Lịch hẹn không hợp lệ hoặc không ở trạng thái sẵn sàng nhắc hẹn!',
            ], 422);
        }

        $now = Carbon::now();
        $dateKey = $now->format('Ymd');
        $dedupKey = "appointment_{$appointment->id}_remind_{$dateKey}";

        DB::transaction(function () use ($appointment, $now, $dedupKey) {
            $appointment->update(['reminder_sent_at' => $now]);

            $this->notificationService->dispatchAfterCommit(function () use ($appointment, $dedupKey) {
                // 1. Nhắc nhân viên phụ trách
                $this->notificationService->createAdminNotification(
                    adminId: $appointment->assigned_to,
                    type: AdminNotification::TYPE_APPOINTMENT_REMINDER,
                    title: "Nhắc hẹn: Cuộc hẹn {$appointment->formatted_id} sắp diễn ra",
                    content: "Khách hàng {$appointment->customer_name} ({$appointment->customer_phone}) lúc {$appointment->start_time->format('H:i d/m/Y')} tại {$appointment->location}.",
                    actionUrl: '/admin/appointments',
                    data: ['appointment_id' => $appointment->id],
                    dedupKey: $dedupKey . "_staff"
                );

                // 2. Nhắc khách hàng (nếu có tài khoản)
                if ($appointment->user_id) {
                    $this->notificationService->createInAppNotification(
                        userId: $appointment->user_id,
                        type: 'appointment_reminder',
                        title: "Nhắc hẹn tư vấn & xem xe",
                        content: "Bạn có lịch hẹn {$appointment->formatted_id} lúc {$appointment->start_time->format('H:i d/m/Y')} tại {$appointment->location}. Hân hạnh đón tiếp bạn!",
                        actionUrl: '/profile',
                        data: ['appointment_id' => $appointment->id],
                        dedupKey: $dedupKey . "_user"
                    );
                }
            });

            AuditService::log(
                action: 'appointment.remind',
                targetType: 'appointment',
                targetId: $appointment->id,
                targetLabel: "Kích hoạt nhắc hẹn {$appointment->formatted_id}"
            );
        });

        return response()->json([
            'status'  => 1,
            'message' => "Đã gửi thông báo nhắc hẹn nội bộ thành công!",
            'data'    => $appointment->fresh(),
        ]);
    }

    /**
     * Bổ sung cảnh báo mềm nếu xe quan tâm có đơn thuê giao với lịch hẹn.
     */
    protected function enrichAppointmentsWithRentalWarning($appointments): void
    {
        $carIds = $appointments->pluck('car_id')->filter()->unique();
        if ($carIds->isEmpty()) {
            return;
        }

        $minStart = $appointments->min('start_time');
        $maxEnd   = $appointments->max('end_time');

        if (!$minStart || !$maxEnd) {
            return;
        }

        // Truy vấn các booking của những xe này trong khoảng thời gian
        $activeBookings = Booking::whereIn('car_id', $carIds)
            ->whereIn('tinh_trang', [Booking::STATUS_CONFIRMED, Booking::STATUS_IN_PROGRESS])
            ->where('start_date', '<', Carbon::parse($maxEnd)->format('Y-m-d H:i:s'))
            ->where('end_date', '>', Carbon::parse($minStart)->format('Y-m-d H:i:s'))
            ->get(['id', 'car_id', 'start_date', 'end_date', 'tinh_trang']);

        foreach ($appointments as $apt) {
            if (!$apt->car_id) {
                $apt->car_rental_conflict = false;
                continue;
            }

            $hasOverlap = $activeBookings->first(function ($b) use ($apt) {
                return $b->car_id == $apt->car_id
                    && $b->start_date < $apt->end_time
                    && $b->end_date > $apt->start_time;
            });

            $apt->car_rental_conflict = (bool) $hasOverlap;
        }
    }

    /**
     * Kiểm tra quyền xem lịch hẹn:
     * - Chủ hệ thống hoặc có quyền bookings.view / admins.manage
     */
    protected function canViewAppointments(?Admin $admin): bool
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
     * Kiểm tra quyền thao tác tạo/sửa/hủy lịch hẹn:
     * - Chủ hệ thống hoặc có quyền bookings.manage / admins.manage
     */
    protected function canManageAppointments(?Admin $admin): bool
    {
        if (!$admin) {
            return false;
        }

        return $admin->isOwner() || $admin->hasAnyPermission([
            PermissionMatrix::PERM_BOOKINGS_MANAGE,
            PermissionMatrix::PERM_ADMINS_MANAGE,
        ]);
    }
}
