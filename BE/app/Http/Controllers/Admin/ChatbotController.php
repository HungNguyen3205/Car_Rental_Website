<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Car;
use App\Models\CarCategory;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
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
     */
    public function assistantBooking(Request $request)
    {
        // Validation handled in Python or here
        $car = Car::find($request->car_id);
        if (!$car) {
            return response()->json(['status' => 0, 'message' => 'Không tìm thấy xe phù hợp!'], 404);
        }

        $start_date = Carbon::parse($request->start_date);
        $end_date = Carbon::parse($request->end_date);
        $days = $start_date->diffInDays($end_date) ?: 1;
        $total = $days * $car->price_per_day;

        $booking = Booking::create([
            'ma_dat_xe'     => 'BOT' . strtoupper(Str::random(8)),
            'user_id'       => $request->user()->id, // Automatically use logged-in user
            'car_id'        => $request->car_id,
            'start_date'    => $request->start_date,
            'end_date'      => $request->end_date,
            'tong_tien'     => $total,
            'tinh_trang'    => 0, // Chờ xác nhận
            'is_thanh_toan' => 0,
            'ly_do_thue'    => $request->notes ?? 'Đặt qua trợ lý ảo AI',
        ]);

        return response()->json([
            'status'  => 1,
            'message' => 'Đặt xe thành công qua trợ lý ảo!',
            'data'    => $booking
        ]);
    }

    /**
     * Check payment status for a booking.
     */
    public function checkPayment(Request $request)
    {
        $ma = trim($request->ma_dat_xe);
        $booking = Booking::where('ma_dat_xe', $ma)->first();

        if (!$booking) {
            return response()->json(['status' => 0, 'message' => 'Không tìm thấy mã đơn này.']);
        }

        if ($booking->is_thanh_toan == 1) {
            return response()->json(['status' => 1, 'message' => 'Đơn này đã được thanh toán trước đó.', 'data' => $booking]);
        }

        // Logic đối soát tự động (Simulated hoặc Bank API)
        // Ở đây ta giả định nếu khách báo là đã chuyển thì ta cập nhật (Hoặc bạn có thể thêm bank API vào đây)
        $booking->is_thanh_toan = 1;
        $booking->tinh_trang = 1; // Chuyển sang trạng thái đã xác nhận/đang thực hiện
        $booking->save();

        return response()->json([
            'status'  => 1,
            'message' => 'Xác nhận thanh toán thành công! Chuyến đi đã được kích hoạt.',
            'data'    => $booking
        ]);
    }
}
