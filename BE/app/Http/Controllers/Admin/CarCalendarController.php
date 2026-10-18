<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GetCarScheduleRequest;
use App\Models\Booking;
use App\Models\Car;
use App\Models\CarCategory;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class CarCalendarController extends Controller
{
    /**
     * Lấy lịch hoạt động của đội xe theo khoảng thời gian trực quan (Tuần / Tháng).
     * 
     * Nguyên tắc xử lý:
     * 1. Chỉ truy vấn các booking giao với khoảng đang xem: [start_date, end_date)
     *    Điều kiện nửa mở: booking.start_date < viewEnd AND booking.end_date > viewStart
     * 2. Phân biệt rõ các trạng thái: Chờ duyệt, Đã duyệt (giữ lịch), Đang thuê (hành trình), Hoàn tất, Đã hủy.
     * 3. Xe đang bảo trì (car.status = 'maintenance') được hiển thị đúng giới hạn hiện tại, không bịa mốc ngày.
     * 4. Phân trang xe và bộ lọc kết hợp để đảm bảo hiệu năng khi đội xe mở rộng.
     */
    public function getSchedule(GetCarScheduleRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // 1. Xác định khoảng thời gian hiển thị (View Window)
        if (!empty($validated['start_date']) && !empty($validated['end_date'])) {
            $viewStart = Carbon::parse($validated['start_date'])->startOfDay();
            $viewEnd = Carbon::parse($validated['end_date'])->endOfDay();
        } else {
            // Mặc định tuần hiện tại (từ Thứ Hai đến Chủ Nhật)
            $viewStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->startOfDay();
            $viewEnd = Carbon::now()->endOfWeek(Carbon::SUNDAY)->endOfDay();
        }

        // Giới hạn khoảng xem tối đa 62 ngày để bảo đảm hiệu năng
        if ($viewEnd->diffInDays($viewStart) > 62) {
            $viewEnd = $viewStart->copy()->addDays(62)->endOfDay();
        }

        $viewStartStr = $viewStart->format('Y-m-d H:i:s');
        $viewEndStr = $viewEnd->format('Y-m-d H:i:s');

        // 2. Truy vấn danh sách xe (kèm phân trang)
        $carQuery = Car::with('category:id,name');

        if (!empty($validated['car_id'])) {
            $carQuery->where('id', $validated['car_id']);
        }

        if (!empty($validated['category_id'])) {
            $carQuery->where('category_id', $validated['category_id']);
        }

        if (!empty($validated['search'])) {
            $search = '%' . trim($validated['search']) . '%';
            $carQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('city', 'like', $search)
                  ->orWhere('type', 'like', $search);
            });
        }

        // Lọc xe bảo trì nếu admin yêu cầu
        if (!empty($validated['status']) && $validated['status'] === 'maintenance') {
            $carQuery->where('status', 'maintenance');
        }

        $perPage = (int) ($validated['per_page'] ?? 15);
        $cars = $carQuery->orderBy('id', 'asc')->paginate($perPage);

        $carIds = $cars->pluck('id');

        // 3. Truy vấn các đơn đặt xe giao với khoảng thời gian của các xe đang xem
        $bookingQuery = Booking::whereIn('car_id', $carIds)
            ->where('start_date', '<', $viewEndStr)
            ->where('end_date', '>', $viewStartStr)
            ->join('users', 'bookings.user_id', '=', 'users.id')
            ->select(
                'bookings.*',
                'users.ho_ten as user_name',
                'users.email as user_email',
                'users.so_dien_thoai as user_phone'
            )
            ->orderBy('bookings.start_date', 'asc');

        // Lọc theo trạng thái lịch
        $statusFilter = $validated['status'] ?? 'all';
        $now = Carbon::now();

        if ($statusFilter === 'pending') {
            $bookingQuery->where('tinh_trang', Booking::STATUS_PENDING);
        } elseif ($statusFilter === 'confirmed') {
            $bookingQuery->where('tinh_trang', Booking::STATUS_CONFIRMED)
                ->where('start_date', '>', $now->format('Y-m-d H:i:s'));
        } elseif ($statusFilter === 'in_progress') {
            $bookingQuery->where(function ($q) use ($now) {
                $q->whereIn('tinh_trang', [Booking::STATUS_IN_PROGRESS, Booking::STATUS_RETURNED])
                  ->orWhere(function ($sub) use ($now) {
                      $sub->where('tinh_trang', Booking::STATUS_CONFIRMED)
                          ->where('start_date', '<=', $now->format('Y-m-d H:i:s'))
                          ->where('end_date', '>', $now->format('Y-m-d H:i:s'));
                  });
            });
        } elseif ($statusFilter === 'completed') {
            $bookingQuery->where(function ($q) use ($now) {
                $q->where('tinh_trang', Booking::STATUS_COMPLETED)
                  ->orWhere(function ($sub) use ($now) {
                      $sub->where('tinh_trang', Booking::STATUS_CONFIRMED)
                          ->where('end_date', '<=', $now->format('Y-m-d H:i:s'));
                  });
            });
        } elseif ($statusFilter === 'cancelled') {
            $bookingQuery->where('tinh_trang', Booking::STATUS_CANCELLED);
        }

        $bookings = $bookingQuery->get();

        // 4. Biến đổi dữ liệu booking và tính toán trạng thái ngữ cảnh
        $transformedBookings = $bookings->map(function ($b) use ($now) {
            $startDate = Carbon::parse($b->start_date);
            $endDate = Carbon::parse($b->end_date);
            $tinhTrang = (int) $b->tinh_trang;

            // Phân loại trạng thái lịch rõ ràng (có nhãn và icon ngữ nghĩa, không chỉ dùng màu)
            if ($tinhTrang === Booking::STATUS_PENDING) {
                $statusKey   = 'pending';
                $statusLabel = 'Chờ duyệt';
                $statusIcon  = 'fa-solid fa-hourglass-half';
                $holdsSchedule = true;
            } elseif ($tinhTrang === Booking::STATUS_CANCELLED) {
                $statusKey   = 'cancelled';
                $statusLabel = 'Đã hủy';
                $statusIcon  = 'fa-solid fa-ban';
                $holdsSchedule = false;
            } elseif ($tinhTrang === Booking::STATUS_IN_PROGRESS) {
                $statusKey   = 'in_progress';
                $statusLabel = 'Đang thuê (Đã giao xe)';
                $statusIcon  = 'fa-solid fa-car-side';
                $holdsSchedule = true;
            } elseif ($tinhTrang === Booking::STATUS_RETURNED) {
                $statusKey   = 'returned';
                $statusLabel = 'Đã nhận trả xe';
                $statusIcon  = 'fa-solid fa-arrow-right-arrow-left';
                $holdsSchedule = true;
            } elseif ($tinhTrang === Booking::STATUS_COMPLETED) {
                $statusKey   = 'completed';
                $statusLabel = 'Đã hoàn tất';
                $statusIcon  = 'fa-solid fa-circle-check';
                $holdsSchedule = true;
            } elseif ($startDate->isFuture()) {
                $statusKey   = 'confirmed';
                $statusLabel = 'Đã duyệt (Sắp tới)';
                $statusIcon  = 'fa-solid fa-calendar-check';
                $holdsSchedule = true;
            } elseif ($endDate->isPast()) {
                $statusKey   = 'completed';
                $statusLabel = 'Đã hoàn tất';
                $statusIcon  = 'fa-solid fa-circle-check';
                $holdsSchedule = true;
            } else {
                $statusKey   = 'in_progress';
                $statusLabel = 'Đang thuê (Hành trình)';
                $statusIcon  = 'fa-solid fa-car-side';
                $holdsSchedule = true;
            }

            return [
                'id'              => $b->id,
                'ma_dat_xe'       => $b->ma_dat_xe,
                'car_id'          => $b->car_id,
                'user_id'         => $b->user_id,
                'user_name'       => $b->user_name,
                'user_email'      => $b->user_email,
                'user_phone'      => $b->user_phone,
                'start_date'      => $b->start_date,
                'end_date'        => $b->end_date,
                'tong_tien'       => (int) round((float) $b->tong_tien),
                'tinh_trang'      => $tinhTrang,
                'is_thanh_toan'   => (int) $b->is_thanh_toan,
                'dia_chi_nhan_xe' => $b->dia_chi_nhan_xe,
                'dia_chi_tra_xe'  => $b->dia_chi_tra_xe,
                'ly_do_thue'      => $b->ly_do_thue,
                'receipt_url'     => $b->bill_image ? url('/api/bookings/' . $b->id . '/receipt') : null,
                'status_key'      => $statusKey,
                'status_label'    => $statusLabel,
                'status_icon'     => $statusIcon,
                'holds_schedule'  => $holdsSchedule,
            ];
        });

        // Nhóm booking theo car_id
        $bookingsByCar = $transformedBookings->groupBy('car_id');

        // Gắn danh sách booking vào từng xe
        $cars->getCollection()->transform(function ($car) use ($bookingsByCar) {
            $carBookings = $bookingsByCar->get($car->id, collect())->values();

            return [
                'id'                 => $car->id,
                'name'               => $car->name,
                'price_per_day'      => (int) round((float) $car->price_per_day),
                'city'               => $car->city,
                'status'             => $car->status,
                'is_maintenance'     => $car->status === 'maintenance',
                'maintenance_notice' => $car->status === 'maintenance'
                    ? 'Xe đang ở trạng thái bảo dưỡng tổng quát; hệ thống tạm khóa nhận lịch.'
                    : null,
                'image'              => $car->image,
                'category_id'        => $car->category_id,
                'category_name'      => $car->category?->name,
                'bookings'           => $carBookings,
            ];
        });

        // 5. Thống kê nhanh và danh mục cho bộ lọc
        $categories = CarCategory::select('id', 'name')->orderBy('name')->get();

        $windowAllBookings = Booking::where('start_date', '<', $viewEndStr)
            ->where('end_date', '>', $viewStartStr)
            ->where('tinh_trang', '!=', Booking::STATUS_CANCELLED)
            ->get();

        $activeBookingCount = $windowAllBookings->count();
        $inProgressCount = $windowAllBookings->filter(function ($b) use ($now) {
            return Carbon::parse($b->start_date)->lte($now) && Carbon::parse($b->end_date)->gt($now);
        })->count();
        $pendingCount = Booking::where('start_date', '<', $viewEndStr)
            ->where('end_date', '>', $viewStartStr)
            ->where('tinh_trang', Booking::STATUS_PENDING)
            ->count();
        $maintenanceCarsCount = Car::where('status', 'maintenance')->count();

        return response()->json([
            'status' => 1,
            'data'   => [
                'cars'            => $cars->items(),
                'pagination'      => [
                    'current_page' => $cars->currentPage(),
                    'last_page'    => $cars->lastPage(),
                    'per_page'     => $cars->perPage(),
                    'total_cars'   => $cars->total(),
                ],
                'window'          => [
                    'start_date' => $viewStart->format('Y-m-d H:i:s'),
                    'end_date'   => $viewEnd->format('Y-m-d H:i:s'),
                    'start_day'  => $viewStart->format('Y-m-d'),
                    'end_day'    => $viewEnd->format('Y-m-d'),
                    'days_count' => (int) $viewEnd->diffInDays($viewStart) + 1,
                ],
                'stats'           => [
                    'active_bookings_count' => $activeBookingCount,
                    'in_progress_count'     => $inProgressCount,
                    'pending_count'         => $pendingCount,
                    'maintenance_cars'      => $maintenanceCarsCount,
                ],
                'categories'      => $categories,
            ]
        ]);
    }
}
