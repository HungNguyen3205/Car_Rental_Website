<?php

namespace App\Services\Booking;

use App\Exceptions\CarBookingConflictException;
use App\Exceptions\CarBookingValidationException;
use App\Models\Booking;
use App\Models\Car;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BookingService
{
    /**
     * Kiểm tra tính khả dụng của xe trong khoảng thời gian [start_date, end_date)
     * Quy ước nửa mở: Đơn kết thúc đúng lúc đơn tiếp theo bắt đầu thì KHÔNG trùng.
     *
     * @param int $carId
     * @param string|Carbon $startDate
     * @param string|Carbon $endDate
     * @param int|null $excludeBookingId
     * @return bool
     */
    public function isCarAvailable(int $carId, $startDate, $endDate, ?int $excludeBookingId = null): bool
    {
        $car = Car::find($carId);
        if (!$car || $car->status === 'maintenance' || $car->publication_status !== Car::PUB_STATUS_PUBLISHED || !$car->is_active) {
            return false;
        }

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($end->lessThanOrEqualTo($start)) {
            return false;
        }

        $startStr = $start->format('Y-m-d H:i:s');
        $endStr = $end->format('Y-m-d H:i:s');

        $query = Booking::where('car_id', $carId)
            ->where('tinh_trang', '!=', 2) // 0: Chờ duyệt, 1: Đã duyệt đều giữ lịch; 2: Đã hủy (không giữ lịch)
            ->where('start_date', '<', $endStr)
            ->where('end_date', '>', $startStr);

        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        return !$query->exists();
    }

    /**
     * Tính toán số ngày và tổng tiền thuê xe dựa trên giá niêm yết server (Snapshot).
     *
     * @param Car $car
     * @param Carbon $start
     * @param Carbon $end
     * @return array
     */
    public function calculatePrice(Car $car, Carbon $start, Carbon $end): array
    {
        $diffInHours = $start->diffInHours($end);
        $diffInDays = (int) max(1, ceil($diffInHours / 24));
        $totalPrice = $diffInDays * (float) $car->price_per_day;

        return [
            'days'          => $diffInDays,
            'price_per_day' => (float) $car->price_per_day,
            'total_price'   => $totalPrice,
        ];
    }

    /**
     * Đặt xe an toàn tuyệt đối với Transaction & Khóa bi quan trên dòng xe (Pessimistic Row Lock).
     * Mọi luồng (Client Web, Chatbot AI, Admin đặt hộ) bắt buộc phải đi qua method này.
     *
     * @param int $userId
     * @param int $carId
     * @param string $startDate
     * @param string $endDate
     * @param array $payload
     * @return Booking
     * @throws CarBookingValidationException
     * @throws CarBookingConflictException
     */
    public function createBooking(int $userId, int $carId, string $startDate, string $endDate, array $payload = []): Booking
    {
        return DB::transaction(function () use ($userId, $carId, $startDate, $endDate, $payload) {
            // BƯỚC 1: Khóa bi quan dòng xe trước khi đọc và kiểm tra (FOR UPDATE)
            // Khóa này giữ chân các request đồng thời khác cho tới khi transaction này kết thúc.
            $car = Car::where('id', $carId)->lockForUpdate()->first();
            if (!$car) {
                throw new CarBookingValidationException('Xe không tồn tại trong hệ thống!', 404);
            }

            // BƯỚC 1.1: Kiểm tra trạng thái xuất bản và hoạt động
            if ($car->publication_status !== Car::PUB_STATUS_PUBLISHED || !$car->is_active) {
                throw new CarBookingValidationException('Xe hiện không ở trạng thái mở cho thuê công khai!', 400);
            }

            // BƯỚC 2: Kiểm tra trạng thái xe bảo trì
            if ($car->status === 'maintenance') {
                throw new CarBookingValidationException('Xe hiện đang trong quá trình bảo dưỡng, vui lòng chọn xe khác!', 400);
            }

            // BƯỚC 3: Kiểm tra tính hợp lệ của mốc thời gian
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);

            if ($end->lessThanOrEqualTo($start)) {
                throw new CarBookingValidationException('Thời gian trả xe phải diễn ra sau thời gian nhận xe!', 422);
            }

            $startStr = $start->format('Y-m-d H:i:s');
            $endStr = $end->format('Y-m-d H:i:s');

            // BƯỚC 4: Kiểm tra xung đột lịch theo quy ước nửa mở [nhận, trả)
            // Hai khoảng [start1, end1) và [start2, end2) giao nhau khi:
            // start_date < endStr AND end_date > startStr
            // Đơn có tinh_trang = 0 (chờ duyệt) và 1 (đã duyệt) đều giữ lịch.
            $conflict = Booking::where('car_id', $carId)
                ->where('tinh_trang', '!=', 2) // Bỏ qua đơn đã hủy
                ->where('start_date', '<', $endStr)
                ->where('end_date', '>', $startStr)
                ->exists();

            if ($conflict) {
                throw new CarBookingConflictException(
                    'Xe vừa được khách hàng khác giữ chỗ trong khung giờ này. Vui lòng chọn khung giờ khác hoặc đổi xe phù hợp!',
                    409
                );
            }

            // BƯỚC 5: Tính giá thuê từ giá niêm yết server (Snapshot)
            $priceData = $this->calculatePrice($car, $start, $end);

            // BƯỚC 6: Tạo bản ghi đặt xe mới
            $booking = Booking::create([
                'user_id'             => $userId,
                'car_id'              => $car->id,
                'ma_dat_xe'           => '',
                'start_date'          => $startStr,
                'end_date'            => $endStr,
                'ly_do_thue'          => $payload['ly_do_thue'] ?? 'Thuê xe du lịch',
                'tong_tien'           => $priceData['total_price'],
                'tinh_trang'          => isset($payload['tinh_trang']) ? (int) $payload['tinh_trang'] : 0, // Mặc định: Chờ xác nhận (giữ lịch)
                'is_thanh_toan'       => isset($payload['is_thanh_toan']) ? (int) $payload['is_thanh_toan'] : 0,
                'dia_chi_nhan_xe'     => $payload['dia_chi_nhan_xe'] ?? null,
                'dia_chi_tra_xe'      => $payload['dia_chi_tra_xe'] ?? null,
                'created_by_admin_id' => $payload['created_by_admin_id'] ?? null,
                'payment_confirmed_at'=> $payload['payment_confirmed_at'] ?? null,
                'payment_confirmed_by'=> $payload['payment_confirmed_by'] ?? null,
                'payment_note'        => $payload['payment_note'] ?? null,
            ]);

            // BƯỚC 7: Cập nhật mã đơn
            if (!empty($payload['ma_dat_xe'])) {
                $booking->ma_dat_xe = $payload['ma_dat_xe'];
            } else {
                $booking->ma_dat_xe = 'HDTX' . str_pad($booking->id, 6, '0', STR_PAD_LEFT);
            }
            $booking->save();

            return $booking;
        });
    }

    /**
     * Hủy đơn đặt xe an toàn và nhả lịch tức thì.
     *
     * @param Booking $booking
     * @return Booking
     * @throws CarBookingValidationException
     */
    public function cancelBooking(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            $lockedBooking = Booking::where('id', $booking->id)->lockForUpdate()->first();
            if (!$lockedBooking) {
                throw new CarBookingValidationException('Không tìm thấy đơn đặt xe!', 404);
            }

            if ((int) $lockedBooking->tinh_trang === 2) {
                throw new CarBookingValidationException('Đơn hàng này đã được hủy trước đó!', 400);
            }

            if ((int) $lockedBooking->tinh_trang !== 0) {
                throw new CarBookingValidationException('Chỉ có thể hủy đơn khi đang ở trạng thái chờ duyệt!', 400);
            }

            $lockedBooking->tinh_trang = 2; // Đã hủy
            $lockedBooking->cancelled_at = Carbon::now();
            $lockedBooking->cancelled_by_type = 'user';
            $lockedBooking->cancelled_by_id = $lockedBooking->user_id;
            $lockedBooking->cancel_reason = 'Khách hàng hủy đơn';
            $lockedBooking->save();

            return $lockedBooking;
        });
    }

    /**
     * Đổi lịch thuê xe (giữ tính nhất quán với cơ chế giữ lịch và chống race condition).
     *
     * @param Booking $booking
     * @param string $newStartDate
     * @param string $newEndDate
     * @return Booking
     * @throws CarBookingValidationException
     * @throws CarBookingConflictException
     */
    public function updateBookingDates(Booking $booking, string $newStartDate, string $newEndDate): Booking
    {
        return DB::transaction(function () use ($booking, $newStartDate, $newEndDate) {
            $car = Car::where('id', $booking->car_id)->lockForUpdate()->first();
            if (!$car) {
                throw new CarBookingValidationException('Xe không tồn tại trong hệ thống!', 404);
            }

            if ($car->status === 'maintenance') {
                throw new CarBookingValidationException('Xe hiện đang trong quá trình bảo dưỡng, không thể dời lịch sang thời gian này!', 400);
            }

            $start = Carbon::parse($newStartDate);
            $end = Carbon::parse($newEndDate);

            if ($end->lessThanOrEqualTo($start)) {
                throw new CarBookingValidationException('Thời gian trả xe phải diễn ra sau thời gian nhận xe!', 422);
            }

            $startStr = $start->format('Y-m-d H:i:s');
            $endStr = $end->format('Y-m-d H:i:s');

            $conflict = Booking::where('car_id', $booking->car_id)
                ->where('id', '!=', $booking->id)
                ->where('tinh_trang', '!=', 2)
                ->where('start_date', '<', $endStr)
                ->where('end_date', '>', $startStr)
                ->exists();

            if ($conflict) {
                throw new CarBookingConflictException(
                    'Xe vừa được khách hàng khác giữ chỗ trong khung giờ này. Vui lòng chọn khung giờ khác hoặc đổi xe phù hợp!',
                    409
                );
            }

            $priceData = $this->calculatePrice($car, $start, $end);

            $booking->start_date = $startStr;
            $booking->end_date = $endStr;
            $booking->tong_tien = $priceData['total_price'];
            $booking->save();

            return $booking;
        });
    }
}
