<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Car;
use App\Models\Contact;
use App\Models\ContactActivity;
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

class ContactController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Danh sách yêu cầu tư vấn & liên hệ có phân quyền xem, bộ lọc nâng cao và phân trang.
     */
    public function index(Request $request): JsonResponse
    {
        $admin = Auth::guard('admin')->user() ?? $request->user();

        // 1. Áp dụng giới hạn phạm vi hiển thị theo quyền của nhân viên
        $query = Contact::visibleTo($admin)
            ->with([
                'user:id,ho_ten,email,so_dien_thoai',
                'car:id,name,license_plate,image,type',
                'assignee:id,ho_ten,email,role',
                'updater:id,ho_ten,email,role',
                'completer:id,ho_ten,email,role',
                'handler:id,ho_ten,email',
            ])
            ->filter($request->only(['trang_thai', 'assigned_to', 'from_date', 'to_date', 'search']))
            ->orderByDesc('id');

        $perPage = min(max((int) $request->get('per_page', 15), 5), 100);
        $contacts = $query->paginate($perPage);

        // 2. Thống kê số lượng trong phạm vi quyền được xem
        $baseCountQuery = Contact::visibleTo($admin);

        $counts = [
            'all'         => (clone $baseCountQuery)->count(),
            'pending'     => (clone $baseCountQuery)->where('trang_thai', Contact::STATUS_PENDING)->count(),
            'in_progress' => (clone $baseCountQuery)->where('trang_thai', Contact::STATUS_IN_PROGRESS)->count(),
            'resolved'    => (clone $baseCountQuery)->where('trang_thai', Contact::STATUS_RESOLVED)->count(),
            'unassigned'  => (clone $baseCountQuery)->whereNull('assigned_to')->count(),
            'my_assigned' => $admin ? (clone $baseCountQuery)->where('assigned_to', $admin->id)->count() : 0,
        ];

        return response()->json([
            'status' => 1,
            'data'   => $contacts,
            'counts' => $counts,
        ]);
    }

    /**
     * Lấy danh sách nhân viên đang hoạt động có quyền xử lý yêu cầu tư vấn.
     */
    public function getStaffList(): JsonResponse
    {
        $staff = Admin::where('tinh_trang', 1)
            ->get()
            ->filter(function (Admin $admin) {
                // Điều kiện: Là Owner hoặc có ít nhất 1 quyền liên quan đến đơn/hỗ trợ/quản trị
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
                    'role_label' => $admin->isOwner() ? 'Chủ hệ thống' : ($admin->admin_role === 'dispatcher' ? 'Điều phối viên' : 'Nhân viên'),
                ];
            })
            ->values();

        return response()->json([
            'status' => 1,
            'data'   => $staff,
        ]);
    }

    /**
     * Xem chi tiết một yêu cầu tư vấn kèm lịch sử hoạt động và các liên kết khách/xe.
     */
    public function show($id, Request $request): JsonResponse
    {
        $admin = Auth::guard('admin')->user() ?? $request->user();

        $contact = Contact::with([
            'user:id,ho_ten,email,so_dien_thoai',
            'car:id,name,license_plate,image,type,price_per_day',
            'assignee:id,ho_ten,email,role',
            'updater:id,ho_ten,email,role',
            'completer:id,ho_ten,email,role',
            'handler:id,ho_ten,email',
            'activities.admin:id,ho_ten,email,role',
        ])->find($id);

        if (!$contact) {
            return response()->json([
                'status'  => 0,
                'message' => 'Không tìm thấy yêu cầu tư vấn này!',
            ], 404);
        }

        // Kiểm tra quyền truy cập chi tiết: nếu nhân viên bị giới hạn và không được phân công
        if (!$this->canAdminAccessContact($admin, $contact)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn không có quyền truy cập yêu cầu tư vấn này vì chưa được phân công phụ trách!',
            ], 403);
        }

        return response()->json([
            'status' => 1,
            'data'   => $contact,
        ]);
    }

    /**
     * Phân công hoặc chuyển giao nhân viên phụ trách yêu cầu tư vấn.
     */
    public function assign(Request $request, $id): JsonResponse
    {
        $request->validate([
            'assigned_to'          => ['nullable', 'integer', 'exists:admins,id'],
            'expected_updated_at'  => ['nullable', 'string'],
            'note'                 => ['nullable', 'string', 'max:1000'],
            'claim_action'         => ['nullable', 'boolean'],
        ]);

        $admin = Auth::guard('admin')->user() ?? $request->user();

        try {
            $updatedContact = DB::transaction(function () use ($request, $id, $admin) {
                /** @var Contact|null $contact */
                $contact = Contact::where('id', $id)->lockForUpdate()->first();

                if (!$contact) {
                    abort(404, 'Không tìm thấy yêu cầu tư vấn để phân công!');
                }

                // 1. Kiểm tra tranh chấp đồng thời (Optimistic Concurrency Lock)
                if ($request->filled('expected_updated_at')) {
                    $expectedIso = Carbon::parse($request->expected_updated_at)->toISOString();
                    $currentIso = $contact->updated_at?->toISOString();

                    if ($currentIso && $expectedIso && $expectedIso !== $currentIso) {
                        return response()->json([
                            'status'          => 0,
                            'error_code'      => 'CONCURRENCY_CONFLICT',
                            'message'         => 'Yêu cầu tư vấn này vừa được một nhân viên khác cập nhật. Vui lòng tải lại dữ liệu mới nhất!',
                            'current_contact' => $contact->fresh(['assignee', 'updater']),
                        ], 409);
                    }
                }

                // 2. Chống ghi đè khi hai người cùng bấm "Nhận việc" (Claim)
                if ($request->boolean('claim_action')) {
                    if ($contact->assigned_to && $contact->assigned_to !== $admin->id) {
                        $otherStaff = Admin::find($contact->assigned_to)?->ho_ten ?? 'nhân viên khác';
                        return response()->json([
                            'status'          => 0,
                            'error_code'      => 'ALREADY_ASSIGNED',
                            'message'         => "Yêu cầu tư vấn này vừa được {$otherStaff} nhận xử lý trước!",
                            'current_contact' => $contact->fresh(['assignee', 'updater']),
                        ], 409);
                    }
                }

                $oldAssigneeId = $contact->assigned_to;
                $newAssigneeId = $request->input('assigned_to');

                // Nếu là thao tác nhận việc (claim), gán cho chính mình
                if ($request->boolean('claim_action') && !$newAssigneeId) {
                    $newAssigneeId = $admin->id;
                }

                // Kiểm tra nhân viên mới có đang hoạt động hay không
                $newAssignee = null;
                if ($newAssigneeId) {
                    $newAssignee = Admin::where('id', $newAssigneeId)->where('tinh_trang', 1)->first();
                    if (!$newAssignee) {
                        abort(422, 'Nhân viên được chọn không tồn tại hoặc tài khoản đã bị khóa.');
                    }
                }

                $oldAssigneeName = $contact->assignee?->ho_ten ?? ($oldAssigneeId ? "Nhân viên #{$oldAssigneeId}" : 'Chưa phân công');
                $newAssigneeName = $newAssignee?->ho_ten ?? 'Chưa phân công';

                // Cập nhật bản ghi Contact
                $contact->assigned_to = $newAssigneeId;
                $contact->assigned_at = $newAssigneeId ? now() : null;
                $contact->updated_by = $admin->id;
                $contact->handled_by = $newAssigneeId; // Giữ tương thích ngược với các caller cũ
                $contact->handled_at = $newAssigneeId ? now() : null;

                // Nếu đang chờ xử lý (0) và được gán nhân viên, tự động chuyển sang Đang xử lý (1)
                $statusChanged = false;
                if ($newAssigneeId && (int) $contact->trang_thai === Contact::STATUS_PENDING) {
                    $contact->trang_thai = Contact::STATUS_IN_PROGRESS;
                    $statusChanged = true;
                }

                $contact->save();

                // Tạo bản ghi lịch sử hoạt động (ContactActivity)
                $activityType = $oldAssigneeId ? ContactActivity::TYPE_REASSIGNED : ContactActivity::TYPE_ASSIGNED;
                $activityContent = $oldAssigneeId
                    ? "Chuyển giao người phụ trách từ [{$oldAssigneeName}] sang [{$newAssigneeName}]."
                    : "Phân công người phụ trách: [{$newAssigneeName}].";

                if ($request->filled('note')) {
                    $activityContent .= ' Ghi chú: ' . trim($request->note);
                }

                ContactActivity::create([
                    'contact_id' => $contact->id,
                    'admin_id'   => $admin->id,
                    'admin_name' => $admin->ho_ten ?? 'Hệ thống',
                    'type'       => $activityType,
                    'content'    => $activityContent,
                    'metadata'   => [
                        'old_assignee_id' => $oldAssigneeId,
                        'new_assignee_id' => $newAssigneeId,
                        'auto_status'     => $statusChanged ? Contact::STATUS_IN_PROGRESS : null,
                    ],
                ]);

                // Ghi nhận Audit Log
                AuditService::log(
                    action: 'contact.assigned',
                    targetType: 'Contact',
                    targetId: (string) $contact->id,
                    targetLabel: "Phân công yêu cầu tư vấn #{$contact->id} - {$contact->ho_ten}",
                    newValues: [
                        'assigned_to' => $newAssigneeId,
                        'assigned_by' => $admin->id,
                    ],
                    oldValues: [
                        'assigned_to' => $oldAssigneeId,
                    ]
                );

                // Gửi thông báo trong ứng dụng sau transaction thành công
                $this->notificationService->dispatchAfterCommit(function () use ($contact, $newAssigneeId, $admin) {
                    // Thông báo tới khách hàng nếu có tài khoản
                    if ($contact->user_id) {
                        $this->notificationService->createInAppNotification(
                            userId: $contact->user_id,
                            type: 'contact_update',
                            title: "Yêu cầu tư vấn #REQ-{$contact->id} đã được tiếp nhận",
                            content: "Chuyên viên chăm sóc khách hàng đang xử lý yêu cầu của bạn: {$contact->tieu_de}",
                            actionUrl: '/profile',
                            data: ['contact_id' => $contact->id]
                        );
                    }
                });

                return $contact->fresh([
                    'user:id,ho_ten,email,so_dien_thoai',
                    'car:id,name,license_plate,image,type',
                    'assignee:id,ho_ten,email,role',
                    'updater:id,ho_ten,email,role',
                    'completer:id,ho_ten,email,role',
                    'activities.admin:id,ho_ten,email,role',
                ]);
            });

            if ($updatedContact instanceof JsonResponse) {
                return $updatedContact;
            }

            return response()->json([
                'status'  => 1,
                'message' => 'Phân công nhân viên phụ trách thành công!',
                'data'    => $updatedContact,
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json([
                'status'  => 0,
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('Contact assign error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status'  => 0,
                'message' => 'Có lỗi xảy ra khi phân công: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cập nhật trạng thái xử lý, lưu lịch sử ghi chú nội bộ và liên kết khách/xe.
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $request->validate([
            'trang_thai'          => ['required', 'integer', 'in:0,1,2'],
            'ghi_chu_noi_bo'      => ['nullable', 'string', 'max:2000'],
            'car_id'              => ['nullable', 'integer', 'exists:cars,id'],
            'user_id'             => ['nullable', 'integer', 'exists:users,id'],
            'expected_updated_at' => ['nullable', 'string'],
        ], [
            'trang_thai.required' => 'Vui lòng chọn trạng thái xử lý!',
            'trang_thai.in'       => 'Trạng thái xử lý không hợp lệ!',
            'ghi_chu_noi_bo.max'  => 'Ghi chú nội bộ không được vượt quá 2000 ký tự!',
            'car_id.exists'       => 'Xe quan tâm được chọn không tồn tại trong hệ thống!',
            'user_id.exists'      => 'Tài khoản khách hàng được chọn không tồn tại!',
        ]);

        $admin = Auth::guard('admin')->user() ?? $request->user();

        try {
            $result = DB::transaction(function () use ($request, $id, $admin) {
                /** @var Contact|null $contact */
                $contact = Contact::where('id', $id)->lockForUpdate()->first();

                if (!$contact) {
                    abort(404, 'Không tìm thấy yêu cầu tư vấn để cập nhật!');
                }

                // 1. Kiểm tra tranh chấp sửa đồng thời (Optimistic Concurrency Control)
                if ($request->filled('expected_updated_at')) {
                    $expectedIso = Carbon::parse($request->expected_updated_at)->toISOString();
                    $currentIso = $contact->updated_at?->toISOString();

                    if ($currentIso && $expectedIso && $expectedIso !== $currentIso) {
                        return response()->json([
                            'status'          => 0,
                            'error_code'      => 'CONCURRENCY_CONFLICT',
                            'message'         => 'Yêu cầu tư vấn này vừa được một nhân viên khác cập nhật. Vui lòng tải lại dữ liệu mới nhất!',
                            'current_contact' => $contact->fresh(['assignee', 'updater']),
                        ], 409);
                    }
                }

                // 2. Kiểm tra quyền xử lý
                if (!$this->canAdminAccessContact($admin, $contact)) {
                    abort(403, 'Bạn không có quyền cập nhật yêu cầu tư vấn của nhân viên khác!');
                }

                $oldStatus = (int) $contact->trang_thai;
                $newStatus = (int) $request->trang_thai;
                $noteContent = trim((string) $request->ghi_chu_noi_bo);

                // Cập nhật trạng thái và người thực hiện
                $contact->trang_thai = $newStatus;
                $contact->updated_by = $admin->id;

                // Nếu chưa phân công mà nhân viên cập nhật -> tự động gán cho nhân viên này
                if (!$contact->assigned_to && $newStatus !== Contact::STATUS_PENDING) {
                    $contact->assigned_to = $admin->id;
                    $contact->assigned_at = now();
                }

                // Cập nhật thời điểm hoàn tất nếu chuyển sang Đã giải quyết (2)
                if ($newStatus === Contact::STATUS_RESOLVED) {
                    $contact->completed_by = $admin->id;
                    $contact->completed_at = now();
                } elseif ($newStatus === Contact::STATUS_PENDING) {
                    $contact->completed_by = null;
                    $contact->completed_at = null;
                }

                // Cập nhật liên kết xe/khách nếu có truyền
                if ($request->has('car_id')) {
                    $contact->car_id = $request->car_id;
                }
                if ($request->has('user_id')) {
                    $contact->user_id = $request->user_id;
                }

                // Giữ tương thích ngược với trường ghi_chu_noi_bo
                if (!empty($noteContent)) {
                    $contact->ghi_chu_noi_bo = $noteContent;
                }

                $contact->handled_by = $contact->assigned_to ?? $admin->id;
                $contact->handled_at = now();
                $contact->save();

                // 1. Thêm bản ghi lịch sử chuyển trạng thái nếu trạng thái thay đổi
                if ($oldStatus !== $newStatus) {
                    $oldLabel = Contact::STATUSES[$oldStatus]['label'] ?? 'Chờ xử lý';
                    $newLabel = Contact::STATUSES[$newStatus]['label'] ?? 'Chờ xử lý';

                    ContactActivity::create([
                        'contact_id' => $contact->id,
                        'admin_id'   => $admin->id,
                        'admin_name' => $admin->ho_ten ?? 'Hệ thống',
                        'type'       => ContactActivity::TYPE_STATUS_CHANGE,
                        'content'    => "Chuyển trạng thái từ [{$oldLabel}] sang [{$newLabel}].",
                        'metadata'   => [
                            'old_status' => $oldStatus,
                            'new_status' => $newStatus,
                        ],
                    ]);
                }

                // 2. Thêm bản ghi ghi chú nội bộ nếu có nội dung ghi chú mới
                if (!empty($noteContent)) {
                    ContactActivity::create([
                        'contact_id' => $contact->id,
                        'admin_id'   => $admin->id,
                        'admin_name' => $admin->ho_ten ?? 'Hệ thống',
                        'type'       => ContactActivity::TYPE_NOTE,
                        'content'    => $noteContent,
                        'metadata'   => ['status_at_note' => $newStatus],
                    ]);
                }

                // Ghi nhận Audit Log
                AuditService::log(
                    action: 'contact.status_updated',
                    targetType: 'Contact',
                    targetId: (string) $contact->id,
                    targetLabel: "Cập nhật yêu cầu tư vấn #{$contact->id} - {$contact->ho_ten}",
                    newValues: [
                        'trang_thai'     => $newStatus,
                        'ghi_chu_noi_bo' => $noteContent,
                        'car_id'         => $contact->car_id,
                        'user_id'        => $contact->user_id,
                    ],
                    oldValues: [
                        'trang_thai' => $oldStatus,
                    ]
                );

                // Gửi thông báo trong ứng dụng sau commit thành công
                $this->notificationService->dispatchAfterCommit(function () use ($contact, $newStatus) {
                    if ($contact->user_id && $newStatus === Contact::STATUS_RESOLVED) {
                        $this->notificationService->createInAppNotification(
                            userId: $contact->user_id,
                            type: 'contact_resolved',
                            title: "Yêu cầu tư vấn #REQ-{$contact->id} đã được giải quyết",
                            content: "Yêu cầu '{$contact->tieu_de}' của bạn đã được chuyên viên hoàn tất hỗ trợ. Cảm ơn bạn đã tin tưởng dịch vụ!",
                            actionUrl: '/profile',
                            data: ['contact_id' => $contact->id]
                        );
                    }
                });

                return $contact->fresh([
                    'user:id,ho_ten,email,so_dien_thoai',
                    'car:id,name,license_plate,image,type',
                    'assignee:id,ho_ten,email,role',
                    'updater:id,ho_ten,email,role',
                    'completer:id,ho_ten,email,role',
                    'activities.admin:id,ho_ten,email,role',
                ]);
            });

            if ($result instanceof JsonResponse) {
                return $result;
            }

            return response()->json([
                'status'  => 1,
                'message' => 'Cập nhật tiến độ xử lý yêu cầu tư vấn thành công!',
                'data'    => $result,
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json([
                'status'  => 0,
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('Contact updateStatus error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status'  => 0,
                'message' => 'Có lỗi xảy ra khi cập nhật trạng thái: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Thêm nhanh một bản ghi ghi chú nội bộ vào dòng thời gian lịch sử xử lý.
     */
    public function addNote(Request $request, $id): JsonResponse
    {
        $request->validate([
            'content' => ['required', 'string', 'min:2', 'max:2000'],
        ], [
            'content.required' => 'Vui lòng nhập nội dung ghi chú!',
            'content.min'      => 'Ghi chú phải có ít nhất 2 ký tự!',
            'content.max'      => 'Ghi chú không được vượt quá 2000 ký tự!',
        ]);

        $admin = Auth::guard('admin')->user() ?? $request->user();

        $contact = Contact::find($id);
        if (!$contact) {
            return response()->json([
                'status'  => 0,
                'message' => 'Không tìm thấy yêu cầu tư vấn!',
            ], 404);
        }

        if (!$this->canAdminAccessContact($admin, $contact)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn không có quyền thêm ghi chú vào yêu cầu này!',
            ], 403);
        }

        $note = ContactActivity::create([
            'contact_id' => $contact->id,
            'admin_id'   => $admin->id,
            'admin_name' => $admin->ho_ten ?? 'Hệ thống',
            'type'       => ContactActivity::TYPE_NOTE,
            'content'    => trim($request->content),
            'metadata'   => ['direct_note' => true],
        ]);

        $contact->ghi_chu_noi_bo = trim($request->content);
        $contact->updated_by = $admin->id;
        $contact->save();

        AuditService::log(
            action: 'contact.note_added',
            targetType: 'Contact',
            targetId: (string) $contact->id,
            targetLabel: "Thêm ghi chú vào yêu cầu #{$contact->id}",
            newValues: ['content' => $request->content]
        );

        return response()->json([
            'status'  => 1,
            'message' => 'Thêm ghi chú tiến độ thành công!',
            'data'    => $note->load('admin:id,ho_ten,email,role'),
        ]);
    }

    /**
     * Kiểm tra quyền truy cập của Admin đối với yêu cầu tư vấn:
     * - Owner hoặc quản lý có bookings.manage/admins.manage: toàn quyền.
     * - Nhân viên hạn chế: chỉ được thao tác trên yêu cầu được giao cho mình hoặc yêu cầu chưa được ai nhận.
     */
    protected function canAdminAccessContact(?Admin $admin, Contact $contact): bool
    {
        if (!$admin) {
            return false;
        }

        if ($admin->isOwner() || $admin->hasAnyPermission([
            PermissionMatrix::PERM_BOOKINGS_MANAGE,
            PermissionMatrix::PERM_ADMINS_MANAGE,
        ])) {
            return true;
        }

        return $contact->assigned_to === $admin->id || is_null($contact->assigned_to);
    }
}
