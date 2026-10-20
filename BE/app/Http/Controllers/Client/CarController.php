<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\CarCategory;
use Illuminate\Http\Request;

class CarController extends Controller
{
    public function getCategories()
    {
        $categories = CarCategory::active()->get();
        return response()->json([
            'status' => 1,
            'data'   => $categories
        ]);
    }

    public function getDataClient(Request $request)
    {
        $query = Car::publiclyAvailable()
            ->leftJoin('car_categories', 'cars.category_id', '=', 'car_categories.id')
            ->select('cars.*', 'car_categories.name as category_name');

        // Lọc địa điểm chuẩn hóa, an toàn không mất xe
        if ($request->filled('city') && $request->city !== 'all') {
            \App\Services\Location\LocationService::applyCityFilter($query, $request->city, 'cars.city');
        }

        // Lọc theo danh mục phân khúc
        if ($request->filled('category_id') && $request->category_id !== 'all') {
            $query->where('cars.category_id', $request->category_id);
        }

        // Lọc theo loại thân xe (SUV, Sedan, Hatchback...)
        if ($request->filled('type') && $request->type !== 'all' && $request->type !== 'Tất cả') {
            $query->where('cars.type', $request->type);
        }

        // Lọc theo loại nhiên liệu (electric, gasoline, diesel, hybrid)
        if ($request->filled('fuel_type') && $request->fuel_type !== 'all') {
            $query->where('cars.fuel_type', $request->fuel_type);
        }
        
        if ($request->has('search') && $request->search != '') {
            $query->where('cars.name', 'like', '%' . $request->search . '%');
        }

        // Filter availability by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = $request->start_date;
            $endDate = $request->end_date;

            $query->where('cars.status', '!=', 'maintenance');

            $query->whereNotExists(function ($q) use ($startDate, $endDate) {
                $q->select(\DB::raw(1))
                    ->from('bookings')
                    ->whereColumn('bookings.car_id', 'cars.id')
                    ->where('bookings.tinh_trang', '!=', 2) // 2 is cancelled
                    ->where(function ($query_overlap) use ($startDate, $endDate) {
                        $query_overlap->where(function ($q1) use ($startDate, $endDate) {
                            $q1->where('bookings.start_date', '<', $endDate)
                               ->where('bookings.end_date', '>', $startDate);
                        });
                    });
            });
        }

        // Lọc theo số chỗ ngồi (seats)
        if ($request->filled('seats') && $request->seats !== 'all') {
            $seats = (int) $request->seats;
            if ($seats >= 7) {
                $query->where('cars.seats', '>=', 7);
            } elseif ($seats === 4 || $seats === 5) {
                $query->where(function ($q) {
                    $q->whereIn('cars.seats', [4, 5])
                      ->orWhereNull('cars.seats');
                });
            } else {
                $query->where('cars.seats', $seats);
            }
        }

        // Lọc theo khoảng giá
        if ($request->filled('price_min') && is_numeric($request->price_min)) {
            $query->where('cars.price_per_day', '>=', (float) $request->price_min);
        }
        if ($request->filled('price_max') && is_numeric($request->price_max)) {
            $query->where('cars.price_per_day', '<=', (float) $request->price_max);
        }

        // Sắp xếp
        $sort = $request->get('sort', 'default');
        if ($sort === 'price_asc' || $sort === 'price-asc') {
            $query->orderBy('cars.price_per_day', 'asc');
        } elseif ($sort === 'price_desc' || $sort === 'price-desc') {
            $query->orderBy('cars.price_per_day', 'desc');
        } elseif ($sort === 'name_asc') {
            $query->orderBy('cars.name', 'asc');
        } else {
            $query->orderByDesc('cars.id');
        }

        // Phân trang tùy chọn (nếu client yêu cầu page hoặc per_page)
        if ($request->filled('per_page') || $request->filled('page')) {
            $perPage = min(max((int) $request->get('per_page', 9), 1), 50);
            $paginator = $query->paginate($perPage);

            return response()->json([
                'status'     => 1,
                'data'       => $paginator->items(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                    'from'         => $paginator->firstItem(),
                    'to'           => $paginator->lastItem(),
                ]
            ]);
        }

        $data = $query->get();

        return response()->json([
            'status' => 1,
            'data'   => $data
        ]);
    }

    public function getDetailClient($id)
    {
        $car = Car::publiclyAvailable()
            ->leftJoin('car_categories', 'cars.category_id', '=', 'car_categories.id')
            ->where('cars.id', $id)
            ->select('cars.*', 'car_categories.name as category_name')
            ->first();

        if ($car) {
            return response()->json([
                'status' => 1,
                'data'   => $car
            ]);
        } else {
            return response()->json([
                'status'  => 0,
                'message' => 'Không tìm thấy xe phù hợp',
            ], 404);
        }
    }

    public function checkAvailabilityAndQuote(Request $request, \App\Services\Booking\BookingService $bookingService)
    {
        $request->validate([
            'car_id'     => 'required|integer|exists:cars,id',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
        ], [
            'car_id.required'     => 'Mã xe là bắt buộc!',
            'car_id.exists'       => 'Xe không tồn tại trong hệ thống!',
            'start_date.required' => 'Thời gian bắt đầu thuê là bắt buộc!',
            'end_date.required'   => 'Thời gian trả xe là bắt buộc!',
            'end_date.after'      => 'Thời gian trả xe phải diễn ra sau thời gian nhận xe!',
        ]);

        $car = Car::find($request->car_id);
        if (!$car || $car->publication_status !== Car::PUB_STATUS_PUBLISHED || !$car->is_active) {
            return response()->json([
                'status'       => 0,
                'is_available' => false,
                'days'         => 0,
                'price_per_day'=> 0,
                'total_price'  => 0,
                'message'      => 'Phương tiện không tồn tại hoặc chưa được công khai trên hệ thống.',
            ], 404);
        }

        if ($car->status === 'maintenance') {
            return response()->json([
                'status'       => 1,
                'is_available' => false,
                'days'         => 0,
                'price_per_day'=> (float) $car->price_per_day,
                'total_price'  => 0,
                'message'      => 'Xe hiện đang trong kế hoạch bảo trì định kỳ.',
            ]);
        }

        $start = \Carbon\Carbon::parse($request->start_date);
        $end = \Carbon\Carbon::parse($request->end_date);

        $isAvailable = $bookingService->isCarAvailable($car->id, $request->start_date, $request->end_date);
        $quote = $bookingService->calculatePrice($car, $start, $end);

        return response()->json([
            'status'         => 1,
            'is_available'   => $isAvailable,
            'days'           => $quote['days'],
            'price_per_day'  => $quote['price_per_day'],
            'total_price'    => $quote['total_price'],
            'message'        => $isAvailable ? 'Xe sẵn sàng phục vụ trong khung giờ này.' : 'Xe đã được khách hàng khác giữ chỗ trong khung giờ này.',
        ]);
    }
}
