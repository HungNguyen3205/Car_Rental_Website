<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function createBooking(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'car_id' => 'required|exists:cars,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $carId = $request->car_id;
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        // Check if overlaps (where state is not Canceled (2))
        // Tinh Trang: 0=Cho, 1=XacNhan, 2=Huy
        $overlapping = Booking::where('car_id', $carId)
            ->where('tinh_trang', '!=', 2)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                      ->orWhereBetween('end_date', [$startDate, $endDate])
                      ->orWhere(function ($q) use ($startDate, $endDate) {
                          $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                      });
            })->exists();

        if ($overlapping) {
            return response()->json([
                'status' => false,
                'message' => 'Xe đã có người thuê trong khoảng thời gian này'
            ], 400);
        }

        $car = Car::find($carId);
        $diffDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) ?: 1;
        $tongTien = $diffDays * $car->price_per_day;

        $maDatXe = 'TX' . strtoupper(substr(uniqid(), -6));

        $booking = Booking::create([
            'user_id' => $user->id,
            'car_id' => $car->id,
            'ma_dat_xe' => $maDatXe,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'ly_do_thue' => $request->ly_do_thue,
            'tong_tien' => $tongTien,
            'tinh_trang' => 0,
            'is_thanh_toan' => 0,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Đặt thuê xe thành công',
            'data' => $booking
        ]);
    }

    public function getMyBookings(Request $request)
    {
        $user = $request->user();
        $bookings = Booking::with('car')->where('user_id', $user->id)->orderBy('id', 'desc')->get();

        return response()->json([
            'status' => true,
            'message' => 'Lấy lịch sử thuê xe thành công',
            'data' => $bookings
        ]);
    }

    public function autoCheckPayment()
    {
        $payload = [
            "USERNAME"  => "THANHTRUONG2311",
            "PASSWORD"  => "TruongCuaMaiLinh2809",
            "DAY_BEGIN" => Carbon::now()->format('d/m/Y'),
            "DAY_END"   => Carbon::now()->format('d/m/Y'),
            "NUMBER_MB" => "1910061030119"
        ];
        
        $res = Http::post("https://api-mb.midstack.io.vn/api/transactions", $payload);
        $data = $res->json();
        
        if (!isset($data['data']['transactionHistoryList'])) {
            return response()->json(['status' => false, 'message' => 'Không lấy được dữ liệu ngân hàng']);
        }
        
        $list_history = $data['data']["transactionHistoryList"];
        $updatedCount = 0;

        foreach ($list_history as $value) {
            if (preg_match('/TX([a-zA-Z0-9]+)/i', $value["description"], $matches)) {
                $ma_dat_xe = 'TX' . strtoupper($matches[1]);
                
                $booking = Booking::where('ma_dat_xe', $ma_dat_xe)
                    ->where('is_thanh_toan', 0)
                    ->where('tong_tien', '=', $value["creditAmount"])
                    ->where('tinh_trang', '!=', 2)
                    ->first();
                    
                if ($booking) {
                    $booking->is_thanh_toan = 1;
                    $booking->tinh_trang = 1; // Xác nhận
                    $booking->save();
                    
                    $updatedCount++;
                }
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật thành công ' . $updatedCount . ' đơn thanh toán',
            'data' => $data
        ]);
    }
}
