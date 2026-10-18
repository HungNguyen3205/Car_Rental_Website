<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\CreateContactRequest;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class ContactController extends Controller
{
    /**
     * Tiếp nhận và lưu yêu cầu liên hệ từ khách hàng vào cơ sở dữ liệu.
     * Áp dụng Rate Limiting chống spam (tối đa 5 lần gửi / phút theo IP).
     */
    public function store(CreateContactRequest $request): JsonResponse
    {
        $ip = $request->ip() ?: '127.0.0.1';
        $throttleKey = 'contact_submit:' . $ip;

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'status'  => 0,
                'message' => "Bạn đã gửi quá nhiều yêu cầu liên hệ liên tiếp. Vui lòng chờ {$seconds} giây trước khi gửi lại!",
            ], 429);
        }

        RateLimiter::hit($throttleKey, 60);

        try {
            $validated = $request->validated();
            $user = Auth::guard('sanctum')->user() ?? Auth::guard('admin')->user();
            $userId = $user?->id;

            // Nếu chưa đăng nhập, tự động liên kết với User trong hệ thống nếu khớp email hoặc SĐT
            if (!$userId) {
                $clientEmail = strtolower(trim($validated['email']));
                $clientPhone = !empty($validated['so_dien_thoai']) ? trim($validated['so_dien_thoai']) : null;

                $matchedUser = \App\Models\User::where('email', $clientEmail)
                    ->when($clientPhone, function ($q) use ($clientPhone) {
                        $q->orWhere('so_dien_thoai', $clientPhone);
                    })->first();

                if ($matchedUser) {
                    $userId = $matchedUser->id;
                }
            }

            $carId = $validated['car_id'] ?? null;

            $contact = Contact::create([
                'user_id'       => $userId,
                'car_id'        => $carId,
                'ho_ten'        => trim($validated['ho_ten']),
                'email'         => strtolower(trim($validated['email'])),
                'so_dien_thoai' => isset($validated['so_dien_thoai']) ? trim($validated['so_dien_thoai']) : null,
                'tieu_de'       => trim($validated['tieu_de']),
                'noi_dung'      => trim($validated['noi_dung']),
                'trang_thai'    => Contact::STATUS_PENDING,
                'ip_address'    => $request->ip(),
                'user_agent'    => substr((string) $request->userAgent(), 0, 500),
            ]);

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
