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
        $categories = CarCategory::all();
        return response()->json([
            'status' => 1,
            'data'   => $categories
        ]);
    }

    public function getDataClient(Request $request)
    {
        $query = Car::publiclyAvailable()
            ->join('car_categories', 'cars.category_id', '=', 'car_categories.id')
            ->select('cars.*', 'car_categories.name as category_name');

        if ($request->has('city') && $request->city != '') {
            $city = strtolower($request->city);
            
            // Helper function to normalize Vietnamese accents
            $normalize = function($str) {
                $str = mb_strtolower($str, 'UTF-8');
                $str = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $str);
                $str = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $str);
                $str = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $str);
                $str = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $str);
                $str = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $str);
                $str = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $str);
                $str = preg_replace('/(đ)/', 'd', $str);
                return $str;
            };

            $cityNormalized = $normalize($city);
            
            // Comprehensive mappings for Vietnamese Regions
            $mappings = [
                'ha noi' => 'hn',
                'ho chi minh' => 'sg',
                'hcm' => 'sg',
                'da nang' => 'dn',
                'nha trang' => 'nt',
                'khanh hoa' => 'nt',
                'can tho' => 'ct',
                'hai phong' => 'hp',
                'da lat' => 'dl',
                'lam dong' => 'dl',
                'vung tau' => 'vt',
                'binh duong' => 'bd',
                'dong nai' => 'dnai'
            ];
            
            $query->where(function($q) use ($city, $cityNormalized, $mappings) {
                // 1. Search by exact keyword sequence
                $q->where('cars.city', 'like', '%' . $city . '%');
                
                // 2. Search by normalized name
                if ($city != $cityNormalized) {
                    $q->orWhere('cars.city', 'like', '%' . $cityNormalized . '%');
                }

                // 3. Search by mapped abbreviation/ID
                foreach ($mappings as $key => $val) {
                    if (str_contains($cityNormalized, $key)) {
                        $q->orWhere('cars.city', 'like', '%' . $val . '%');
                        // Also handle reverse (if DB has full name but user typed ID)
                        $q->orWhere('cars.city', 'like', '%' . $key . '%');
                    }
                }
            });
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

        $data = $query->orderByDesc('cars.id')->get();

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
