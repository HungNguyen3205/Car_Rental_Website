<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function getDataAdmin()
    {
        $data = Booking::join('cars', 'bookings.car_id', '=', 'cars.id')
            ->join('users', 'bookings.user_id', '=', 'users.id')
            ->select('bookings.*', 'cars.name as car_name', 'users.ho_ten as user_name', 'users.email as user_email')
            ->orderByDesc('bookings.id')
            ->get();
            
        return response()->json([
            'status' => 1,
            'data'   => $data
        ]);
    }

    public function statusBooking(Request $request)
    {
        $booking = Booking::find($request->id);
        if ($booking) {
            $booking->update([
                'tinh_trang'    => $request->tinh_trang,
                'is_thanh_toan' => $request->is_thanh_toan ?? $booking->is_thanh_toan
            ]);
            return response()->json([
                'status'  => 1,
                'message' => 'Cập nhật trạng thái đơn hàng thành công!'
            ]);
        }
    }

    public function deleteBooking(Request $request)
    {
        $booking = Booking::find($request->id);
        if ($booking) {
            $booking->delete();
            return response()->json([
                'status'  => 1,
                'message' => 'Xóa đơn đặt xe thành công!'
            ]);
        }
    }
}
