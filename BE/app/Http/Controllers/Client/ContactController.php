<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\CreateContactRequest;
use App\Models\Admin;
use App\Models\Contact;
use App\Models\ContactActivity;
use App\Models\User;
use App\Services\Authorization\PermissionMatrix;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class ContactController extends Controller
{
    /**
     * Tiếp nhận và lưu yêu cầu liên hệ / tư vấn từ khách hàng vào cơ sở dữ liệu.
     * Áp dụng Rate Limiting chống spam (tối đa 5 lần gửi / phút theo IP).
     * Kiểm tra honeypot chống bot tự động và chống tạo bản ghi trùng lặp khi retry.
     */
    public function store(CreateContactRequest $request): JsonResponse
    {
        $ip = $request->ip() ?: '127.0.0.1';
        $throttleKey = 'contact_submit:' . $ip;

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'status'  => 0,
                'message' => "Bạn đã gửi quá nhiều yêu cầu liên tiếp. Vui lòng chờ {$seconds} giây trước khi gửi lại!",
            ], 429);
        }

        // 1. Kiểm tra honeypot chống bot tự động
        if (!empty($request->input('website_hp'))) {
            return response()->json([
                'status'  => 0,
                'message' => 'Phát hiện hành vi gửi biểu mẫu tự động. Yêu cầu đã bị từ chối.',
            ], 422);
        }

        RateLimiter::hit($throttleKey, 60);

        try {
            $validated = $request->validated();

            // 2. Chống tạo trùng lặp (Idempotency / Retry Protection) trong vòng 30 giây
            $recentContact = Contact::where('email', strtolower(trim($validated['email'])))
                ->where('tieu_de', trim($validated['tieu_de']))
                ->where('created_at', '>=', now()->subSeconds(30))
                ->first();

            if ($recentContact) {
                return response()->json([
                    'status'  => 1,
                    'message' => 'Yêu cầu của bạn đã được ghi nhận thành công trước đó. Chuyên viên tư vấn sẽ liên hệ với bạn trong thời gian sớm nhất!',
                    'data'    => [
                        'id'         => $recentContact->id,
                        'ho_ten'     => $recentContact->ho_ten,
                        'email'      => $recentContact->email,
                        'tieu_de'    => $recentContact->tieu_de,
                        'trang_thai' => $recentContact->status_label,
                        'created_at' => $recentContact->created_at?->format('d/m/Y H:i'),
                    ],
                ], 201);
            }

            // 3. Liên kết người dùng khách hàng đăng nhập bằng server session / token, không tin cậy client
            $authUser = Auth::guard('sanctum')->user();
            $userId = ($authUser instanceof User) ? $authUser->id : null;

            // Nếu chưa đăng nhập với tư cách User khách, tự động liên kết với User trong hệ thống nếu khớp email hoặc SĐT
            if (!$userId) {
                $clientEmail = strtolower(trim($validated['email']));
                $clientPhone = !empty($validated['so_dien_thoai']) ? trim($validated['so_dien_thoai']) : null;

                $matchedUser = User::where('email', $clientEmail)
                    ->when($clientPhone, function ($q) use ($clientPhone) {
                        $q->orWhere('so_dien_thoai', $clientPhone);
                    })->first();

                if ($matchedUser) {
                    $userId = $matchedUser->id;
                }
            }

            $carId = $validated['car_id'] ?? null;

            // 4. Định dạng nội dung chi tiết: gắn kèm ngày dự kiến thuê và nguồn nếu có
            $content = trim($validated['noi_dung']);
            if (!empty($validated['rental_date'])) {
                $formattedDate = date('d/m/Y', strtotime($validated['rental_date']));
                $content .= "\n\n[Ngày dự kiến thuê: {$formattedDate}]";
            }
            $source = !empty($validated['source']) ? trim(strip_tags($validated['source'])) : 'Trang Liên hệ';
            $content .= "\n[Nguồn gửi: {$source}]";

            $contact = Contact::create([
                'user_id'       => $userId,
                'car_id'        => $carId,
                'ho_ten'        => trim($validated['ho_ten']),
                'email'         => strtolower(trim($validated['email'])),
                'so_dien_thoai' => !empty($validated['so_dien_thoai']) ? trim($validated['so_dien_thoai']) : null,
                'tieu_de'       => trim($validated['tieu_de']),
                'noi_dung'      => $content,
                'trang_thai'    => Contact::STATUS_PENDING,
                'ip_address'    => $request->ip(),
                'user_agent'    => substr((string) $request->userAgent(), 0, 500),
            ]);

            // 5. Ghi nhận nhật ký tiếp nhận ban đầu vào dòng thời gian hoạt động (ContactActivity)
            try {
                ContactActivity::create([
                    'contact_id' => $contact->id,
                    'admin_name' => 'Hệ thống',
                    'type'       => 'note',
                    'content'    => "Khách hàng gửi yêu cầu tư vấn trực tuyến từ [{$source}]." . ($carId ? " (Xe quan tâm ID: #{$carId})" : ''),
                    'metadata'   => [
                        'source'      => $source,
                        'car_id'      => $carId,
                        'rental_date' => $validated['rental_date'] ?? null,
                    ],
                ]);
            } catch (Throwable $e) {
                Log::warning('Không thể lưu ContactActivity ban đầu: ' . $e->getMessage());
            }

            // 6. Phát hành thông báo nội bộ cho nhân viên quản trị phụ trách
            try {
                $notificationService = app(NotificationService::class);
                $activeAdmins = Admin::where('tinh_trang', 1)->get();
                foreach ($activeAdmins as $admin) {
                    if ($admin->isOwner() || $admin->hasAnyPermission([
                        PermissionMatrix::PERM_BOOKINGS_VIEW,
                        PermissionMatrix::PERM_BOOKINGS_MANAGE,
                        PermissionMatrix::PERM_ADMINS_MANAGE,
                    ])) {
                        $notificationService->createAdminNotification(
                            adminId: $admin->id,
                            type: 'contact_new',
                            title: "Yêu cầu tư vấn mới #REQ-{$contact->id}",
                            content: "Khách hàng {$contact->ho_ten} vừa gửi yêu cầu: {$contact->tieu_de}",
                            actionUrl: '/admin/contacts',
                            data: ['contact_id' => $contact->id]
                        );
                    }
                }

                if ($userId) {
                    $notificationService->createInAppNotification(
                        userId: $userId,
                        type: 'contact_submitted',
                        title: "Yêu cầu tư vấn #REQ-{$contact->id} đã được gửi",
                        content: "Chúng tôi đã tiếp nhận yêu cầu '{$contact->tieu_de}'. Chuyên viên tư vấn sẽ liên hệ với bạn trong thời gian sớm nhất.",
                        actionUrl: '/profile',
                        data: ['contact_id' => $contact->id]
                    );
                }
            } catch (Throwable $e) {
                Log::warning('Không thể tạo thông báo cho yêu cầu liên hệ: ' . $e->getMessage());
            }

            return response()->json([
                'status'  => 1,
                'message' => 'Cảm ơn bạn đã liên hệ! Yêu cầu của bạn đã được tiếp nhận thành công. Chuyên viên chăm sóc khách hàng sẽ phản hồi trong thời gian sớm nhất.',
                'data'    => [
                    'id'         => $contact->id,
                    'ho_ten'     => $contact->ho_ten,
                    'email'      => $contact->email,
                    'tieu_de'    => $contact->tieu_de,
                    'trang_thai' => $contact->status_label,
                    'created_at' => $contact->created_at?->format('d/m/Y H:i'),
                ],
            ], 201);
        } catch (Throwable $e) {
            Log::error('Lỗi lưu yêu cầu liên hệ: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status'  => 0,
                'message' => 'Có lỗi xảy ra khi lưu yêu cầu liên hệ. Vui lòng thử lại sau!',
            ], 500);
        }
    }
}
