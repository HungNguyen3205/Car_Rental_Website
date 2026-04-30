<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function datLichXe(Request $request)
    {
        try {
            // Check all possible guards
            $user = Auth::guard('sanctum')->user() ?? Auth::guard('admin')->user();
            
            if (!$user) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Vui lòng đăng nhập để đặt xe!'
                ], 401);
            }

            // Important: If an Admin is booking, we must link it to a valid record in the 'users' table
            // because the bookings table has a foreign key to 'users'.
            $targetUserId = $user->id;
            if (get_class($user) === 'App\Models\Admin') {
                // Try to find a user with the same email, or fallback to the first user
                $linkedUser = \App\Models\User::where('email', $user->email)->first();
                $targetUserId = $linkedUser ? $linkedUser->id : 2; // Fallback to ID 2 (Demo User)
            }

            $carId     = $request->car_id;
            $startDate = $request->start_date;
            $endDate   = $request->end_date;

            $isBooked = Booking::where('car_id', $carId)
                ->where('tinh_trang', '!=', 2)
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                          ->orWhereBetween('end_date', [$startDate, $endDate])
                          ->orWhere(function ($q) use ($startDate, $endDate) {
                              $q->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                          });
                })
                ->exists();

            if ($isBooked) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Xe đã có người đặt trong khoảng thời gian này!'
                ], 400);
            }

            $car = Car::find($carId);
            if (!$car || $car->status == 'maintenance') {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Xe hiện không sẵn sàng phục vụ!'
                ], 400);
            }

            $diffInDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate));
            if ($diffInDays < 1) $diffInDays = 1;
            $tongTien   = $diffInDays * $car->price_per_day;

            $booking = Booking::create([
                'user_id'       => $targetUserId,
                'car_id'        => $carId,
                'ma_dat_xe'     => '',
                'start_date'    => $startDate,
                'end_date'      => $endDate,
                'ly_do_thue'    => $request->ly_do_thue ?? 'Thuê xe du lịch',
                'tong_tien'     => $tongTien,
                'tinh_trang'    => 0,
                'is_thanh_toan' => 0,
                'dia_chi_nhan_xe' => $request->dia_chi_nhan_xe,
                'dia_chi_tra_xe'  => $request->dia_chi_tra_xe,
            ]);

            $booking->ma_dat_xe = 'HDTX' . str_pad($booking->id, 6, '0', STR_PAD_LEFT);
            $booking->save();

            return response()->json([
                'status'  => 1,
                'message' => 'Đặt xe thành công! Mã đơn: ' . $booking->ma_dat_xe,
                'data'    => $booking
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 0,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ], 500);
        }
    }

    public function getDataLichHenClient()
    {
        $user = Auth::guard('sanctum')->user() ?? Auth::guard('admin')->user();
        
        if (!$user) {
            return response()->json([
                'status' => 0,
                'message' => 'Bạn cần đăng nhập để xem hành trình!'
            ], 401);
        }

        $targetUserId = $user->id;
        if (get_class($user) === 'App\Models\Admin') {
            $linkedUser = \App\Models\User::where('email', $user->email)->first();
            $targetUserId = $linkedUser ? $linkedUser->id : 2;
        }

        $data = Booking::join('cars', 'bookings.car_id', '=', 'cars.id')
            ->join('users', 'bookings.user_id', '=', 'users.id')
            ->where('bookings.user_id', $targetUserId)
            ->select('bookings.*', 'cars.name as car_name', 'cars.image as car_image', 'users.so_dien_thoai as user_phone')
            ->orderByDesc('bookings.id')
            ->get();
            
        return response()->json([
            'status' => 1,
            'data'   => $data
        ]);
    }

    public function huyLichXe(Request $request)
    {
        $user = Auth::guard('sanctum-user')->user();
        if ($user) {
            $booking = Booking::where('user_id', $user->id)->where('id', $request->id)->first();
            if ($booking && $booking->tinh_trang == 0) {
                $booking->update(['tinh_trang' => 2]);
                return response()->json(['status' => 1, 'message' => 'Hủy đơn thành công!']);
            }
        }
        return response()->json(['status' => 0, 'message' => 'Không thể hủy đơn này!']);
    }

    public function kiemTraMaDatLich(Request $request)
    {
        $ma = trim($request->ma_dat_xe);
        $data = Booking::join('cars', 'bookings.car_id', '=', 'cars.id')
            ->join('users', 'bookings.user_id', '=', 'users.id')
            ->where('bookings.ma_dat_xe', $ma)
            ->select('bookings.*', 'cars.name as car_name', 'users.ho_ten as user_name')
            ->first();

        if ($data) {
            return response()->json(['status' => 1, 'data' => $data]);
        }
        return response()->json(['status' => 0, 'message' => 'Không tìm thấy đơn hàng!']);
    }

    public function uploadBill(Request $request)
    {
        try {
            $request->validate([
                'booking_id' => 'required|exists:bookings,id',
                'bill_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            ]);

            $booking = Booking::find($request->booking_id);
            
            if ($request->hasFile('bill_image')) {
                $file = $request->file('bill_image');
                $filename = 'bill_' . $booking->ma_dat_xe . '_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/bills'), $filename);
                
                $booking->bill_image = 'uploads/bills/' . $filename;
                $booking->save();

                return response()->json([
                    'status' => 1,
                    'message' => 'Tải hóa đơn thành công! Admin sẽ kiểm tra ngay.',
                    'bill_url' => asset($booking->bill_image)
                ]);
            }

            return response()->json(['status' => 0, 'message' => 'Không tìm thấy tệp ảnh!']);
        } catch (\Exception $e) {
            return response()->json(['status' => 0, 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }
}
