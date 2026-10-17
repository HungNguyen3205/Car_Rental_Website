<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\CarBookingConflictException;
use App\Exceptions\CarBookingValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chatbot\AssistantBookingRequest;
use App\Http\Requests\Chatbot\CheckPaymentRequest;
use App\Models\Booking;
use App\Models\Car;
use App\Models\CarCategory;
use App\Services\Booking\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    protected BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * Get cars with optional filters for the chatbot.
     */
    public function getCars(Request $request)
    {
        $query = Car::join('car_categories', 'cars.category_id', '=', 'car_categories.id')
            ->select('cars.*', 'car_categories.name as category_name');

        if ($request->has('search') && $request->search != '') {
            $query->where('cars.name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('city') && $request->city != '') {
            $query->where('cars.city', 'like', '%' . $request->city . '%');
        }

        if ($request->has('type') && $request->type != '') {
            $query->where('car_categories.name', 'like', '%' . $request->type . '%');
        }

        $data = $query->orderByDesc('cars.id')->limit(10)->get();

        return response()->json([
            'status' => 1,
            'data'   => $data
        ]);
    }

    /**
     * Get all car categories.
     */
    public function getCategories()
    {
        $data = CarCategory::all();
        return response()->json([
            'status' => 1,
            'data'   => $data
        ]);
    }

    /**
     * Place a booking from the chatbot assistant.
     * Sử dụng chung BookingService và cơ chế chống race condition như Website.
     */
    public function assistantBooking(AssistantBookingRequest $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 0, 'message' => 'Vui lòng đăng nhập để đặt xe!'], 401);
        }

        $validated = $request->validated();

        try {
            $booking = $this->bookingService->createBooking(
                $user->id,
                (int) $validated['car_id'],
                $validated['start_date'],
                $validated['end_date'],
                [
                    'ma_dat_xe'  => 'BOT' . strtoupper(Str::random(8)),
                    'ly_do_thue' => $validated['notes'] ?? 'Đặt qua trợ lý ảo AI',
                ]
            );

            return response()->json([
                'status'  => 1,
                'message' => 'Đặt xe thành công qua trợ lý ảo! Mã đơn: ' . $booking->ma_dat_xe,
                'data'    => $booking
            ]);
        } catch (CarBookingConflictException $e) {
            return response()->json([
                'status'     => 0,
                'message'    => $e->getMessage(),
                'error_type' => 'SCHEDULE_CONFLICT'
            ], 409);
        } catch (CarBookingValidationException $e) {
            return response()->json([
                'status'  => 0,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 400);
        } catch (\Exception $e) {
            Log::error('Chatbot assistantBooking error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status'  => 0,
                'message' => 'Có lỗi xảy ra trong quá trình đặt xe qua trợ lý. Vui lòng thử lại sau!',
            ], 500);
        }
    }

    /**
     * Check payment status for a booking.
     * TUYỆT ĐỐI CHỈ ĐỌC: Không cập nhật trạng thái đã thanh toán từ lời xác nhận của khách.
     * Kiểm tra quyền sở hữu đơn đặt xe hoặc quyền Admin trước khi trả về dữ liệu.
     */
    public function checkPayment(CheckPaymentRequest $request)
    {
        $validated = $request->validated();
        $ma = trim($validated['ma_dat_xe']);
        $booking = Booking::where('ma_dat_xe', $ma)->first();
        $user = $request->user();

        // Trả HTTP 404 nhất quán, tránh tiết lộ đơn của người khác có tồn tại
        if (!$booking || !$user || !$user->can('view', $booking)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Không tìm thấy thông tin đơn đặt xe với mã: ' . $ma
            ], 404);
        }

        // Chỉ đọc trạng thái thông qua Service, không thay đổi bất kỳ bản ghi nào trong database
        $paymentService = new \App\Services\Payment\BookingPaymentService();
        $statusInfo = $paymentService->getPaymentStatusInfo($booking);

        return response()->json([
            'status'         => 1,
            'payment_status' => $statusInfo['code'],
            'is_thanh_toan'  => (int) $booking->is_thanh_toan,
            'tinh_trang'     => (int) $booking->tinh_trang,
            'message'        => $statusInfo['message'],
            'info'           => $statusInfo,
            'data'           => [
                'id'                   => $booking->id,
                'ma_dat_xe'            => $booking->ma_dat_xe,
                'is_thanh_toan'        => (int) $booking->is_thanh_toan,
                'tinh_trang'           => (int) $booking->tinh_trang,
                'tong_tien'            => (float) $booking->tong_tien,
                'has_bill'             => !empty($booking->bill_image),
                'receipt_url'          => !empty($booking->bill_image) ? url('/api/bookings/' . $booking->id . '/receipt') : null,
                'payment_confirmed_at' => $booking->payment_confirmed_at,
                'payment_note'         => $booking->payment_note,
            ]
        ]);
    }
}
