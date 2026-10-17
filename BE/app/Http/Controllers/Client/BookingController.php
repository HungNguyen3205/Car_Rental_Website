<?php

namespace App\Http\Controllers\Client;

use App\Exceptions\CarBookingConflictException;
use App\Exceptions\CarBookingValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\CancelBookingRequest;
use App\Http\Requests\Booking\CreateBookingRequest;
use App\Http\Requests\Booking\LookupBookingCodeRequest;
use App\Http\Requests\Booking\UploadReceiptRequest;
use App\Models\Booking;
use App\Models\Car;
use App\Services\Booking\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BookingController extends Controller
{
    protected BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    public function datLichXe(CreateBookingRequest $request)
    {
        try {
            $user = Auth::guard('sanctum')->user() ?? Auth::guard('admin')->user();
            
            if (!$user) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Vui lòng đăng nhập để đặt xe!'
                ], 401);
            }

            $validated = $request->validated();

            // Xóa hoàn toàn fallback User::first() hoặc ID mặc định
            $targetUserId = $user->id;
            if (get_class($user) === 'App\Models\Admin') {
                $linkedUser = \App\Models\User::where('email', $user->email)->first();
                if (!$linkedUser) {
                    return response()->json([
                        'status'  => 0,
                        'message' => 'Tài khoản Quản trị viên chưa có hồ sơ khách hàng tương ứng. Vui lòng sử dụng chức năng Đặt xe hộ trong trang Quản trị!',
                    ], 422);
                }
                $targetUserId = $linkedUser->id;
            }

            // Gọi BookingService với pessimistic locking, quy ước [nhận, trả) và snapshot giá từ server
            $booking = $this->bookingService->createBooking(
                $targetUserId,
                (int) $validated['car_id'],
                $validated['start_date'],
                $validated['end_date'],
                [
                    'ly_do_thue'      => $validated['ly_do_thue'] ?? 'Thuê xe du lịch',
                    'dia_chi_nhan_xe' => $validated['dia_chi_nhan_xe'] ?? null,
                    'dia_chi_tra_xe'  => $validated['dia_chi_tra_xe'] ?? null,
                ]
            );

            return response()->json([
                'status'  => 1,
                'message' => 'Đặt xe thành công! Mã đơn: ' . $booking->ma_dat_xe,
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
            Log::error('Client booking error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status'  => 0,
                'message' => 'Có lỗi xảy ra trong quá trình xử lý đơn đặt xe. Vui lòng thử lại sau!',
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

        // Xóa hoàn toàn fallback User::first() hoặc ID mặc định
        $targetUserId = $user->id;
        if (get_class($user) === 'App\Models\Admin') {
            $linkedUser = \App\Models\User::where('email', $user->email)->first();
            if (!$linkedUser) {
                return response()->json([
                    'status' => 1,
                    'data'   => []
                ]);
            }
            $targetUserId = $linkedUser->id;
        }

        $data = Booking::join('cars', 'bookings.car_id', '=', 'cars.id')
            ->join('users', 'bookings.user_id', '=', 'users.id')
            ->where('bookings.user_id', $targetUserId)
            ->select('bookings.*', 'cars.name as car_name', 'cars.image as car_image', 'users.so_dien_thoai as user_phone')
            ->orderByDesc('bookings.id')
            ->get();
            
        // Gắn đường dẫn tải biên lai an toàn có kiểm soát quyền
        $data->transform(function ($item) {
            $item->receipt_url = !empty($item->bill_image) ? url('/api/bookings/' . $item->id . '/receipt') : null;
            return $item;
        });

        return response()->json([
            'status' => 1,
            'data'   => $data
        ]);
    }

    /**
     * Hủy đơn đặt xe: Chỉ cho phép chủ đơn hoặc Admin;
     * Trả về 404 nếu đơn không tồn tại hoặc không thuộc quyền để tránh lộ thông tin đơn.
     */
    public function huyLichXe(CancelBookingRequest $request)
    {
        $user = Auth::guard('sanctum')->user() ?? Auth::guard('admin')->user();
        if (!$user) {
            return response()->json(['status' => 0, 'message' => 'Bạn cần đăng nhập để hủy đơn!'], 401);
        }

        $booking = Booking::find($request->id);
        
        // Nhất quán 404: Không tiết lộ đơn của người khác có tồn tại
        if (!$booking || !$user->can('view', $booking)) {
            return response()->json(['status' => 0, 'message' => 'Không tìm thấy đơn đặt xe!'], 404);
        }

        if (!$user->can('cancel', $booking)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Chỉ có thể hủy đơn khi đang ở trạng thái Chờ xác nhận!'
            ], 400);
        }

        try {
            $this->bookingService->cancelBooking($booking);
            return response()->json(['status' => 1, 'message' => 'Hủy đơn đặt xe thành công!']);
        } catch (CarBookingValidationException $e) {
            return response()->json(['status' => 0, 'message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            Log::error('Cancel booking error: ' . $e->getMessage());
            return response()->json(['status' => 0, 'message' => 'Có lỗi xảy ra khi hủy đơn đặt xe. Vui lòng thử lại sau!'], 500);
        }
    }

    /**
     * Tra cứu mã đặt lịch an toàn: Bắt buộc xác thực người dùng và kiểm tra quyền sở hữu.
     * Không trả về bookings.* hoặc dữ liệu cá nhân của người khác.
     */
    public function kiemTraMaDatLich(LookupBookingCodeRequest $request)
    {
        $user = Auth::guard('sanctum')->user() ?? Auth::guard('admin')->user();
        if (!$user) {
            return response()->json([
                'status'  => 0,
                'message' => 'Vui lòng đăng nhập để tra cứu chi tiết đơn hàng!'
            ], 401);
        }

        $ma = trim($request->ma_dat_xe);
        $booking = Booking::with('car')->where('ma_dat_xe', $ma)->first();

        // Nhất quán 404: Không tiết lộ mã đơn của người khác có tồn tại
        if (!$booking || !$user->can('view', $booking)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Không tìm thấy thông tin đơn hàng phù hợp!'
            ], 404);
        }

        return response()->json([
            'status' => 1,
            'data'   => [
                'id'              => $booking->id,
                'ma_dat_xe'       => $booking->ma_dat_xe,
                'car_name'        => $booking->car?->name,
                'start_date'      => $booking->start_date,
                'end_date'        => $booking->end_date,
                'tong_tien'       => (float) $booking->tong_tien,
                'tinh_trang'      => (int) $booking->tinh_trang,
                'is_thanh_toan'   => (int) $booking->is_thanh_toan,
                'dia_chi_nhan_xe' => $booking->dia_chi_nhan_xe,
                'dia_chi_tra_xe'  => $booking->dia_chi_tra_xe,
                'has_receipt'     => !empty($booking->bill_image),
                'receipt_url'     => !empty($booking->bill_image) ? url('/api/bookings/' . $booking->id . '/receipt') : null,
            ]
        ]);
    }

    /**
     * Tải lên biên lai: Lưu vào private storage có kiểm tra quyền sở hữu và MIME thực tế
     */
    public function uploadBill(UploadReceiptRequest $request)
    {
        try {
            $user = Auth::guard('sanctum')->user() ?? Auth::guard('admin')->user();
            if (!$user) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Vui lòng đăng nhập để tải biên lai thanh toán!'
                ], 401);
            }

            $booking = Booking::find($request->booking_id);

            // Nhất quán 404: Không tiết lộ đơn của người khác có tồn tại
            if (!$booking || !$user->can('view', $booking)) {
                return response()->json(['status' => 0, 'message' => 'Không tìm thấy đơn hàng!'], 404);
            }

            if (!$user->can('uploadReceipt', $booking)) {
                if ($booking->is_thanh_toan === 1) {
                    return response()->json([
                        'status'  => 0,
                        'message' => 'Đơn hàng này đã được xác nhận thanh toán rồi, không cần tải lại biên lai!'
                    ], 400);
                }

                if ($booking->tinh_trang === 2) {
                    return response()->json([
                        'status'  => 0,
                        'message' => 'Đơn hàng này đã bị hủy, không thể tải biên lai!'
                    ], 400);
                }

                return response()->json([
                    'status'  => 0,
                    'message' => 'Bạn không có quyền tải biên lai cho đơn hàng này!'
                ], 403);
            }
            
            if ($request->hasFile('bill_image')) {
                $file = $request->file('bill_image');
                $ext = $file->guessExtension() ?: 'png';
                $safeCode = preg_replace('/[^a-zA-Z0-9_-]/', '', $booking->ma_dat_xe);
                $filename = 'bill_' . $safeCode . '_' . Str::random(16) . '.' . $ext;
                
                // Lưu vào private storage thay vì thư mục public công khai
                $privateDir = storage_path('app/private/bills');
                if (!File::exists($privateDir)) {
                    File::makeDirectory($privateDir, 0755, true);
                }
                $file->move($privateDir, $filename);
                
                $booking->bill_image = 'private/bills/' . $filename;
                $booking->save();

                return response()->json([
                    'status'      => 1,
                    'message'     => 'Tải biên lai thành công! Ảnh chứng từ đang chờ Kế toán/Admin đối soát sao kê.',
                    'receipt_url' => url('/api/bookings/' . $booking->id . '/receipt')
                ]);
            }

            return response()->json(['status' => 0, 'message' => 'Không tìm thấy tệp ảnh!']);
        } catch (\Exception $e) {
            Log::error('Upload bill error: ' . $e->getMessage());
            return response()->json(['status' => 0, 'message' => 'Có lỗi xảy ra khi tải lên biên lai. Vui lòng thử lại sau!'], 500);
        }
    }

    /**
     * Phục vụ biên lai chuyển khoản qua cơ chế riêng tư có kiểm soát quyền
     * Hỗ trợ tự động chuyển đổi tương thích ngược với các file legacy cũ
     */
    public function getReceipt(Request $request, $id)
    {
        $user = Auth::guard('sanctum')->user() ?? Auth::guard('admin')->user();
        if (!$user && $request->has('token')) {
            $tokenStr = $request->query('token');
            $pat = \Laravel\Sanctum\PersonalAccessToken::findToken($tokenStr);
            if ($pat) {
                $user = $pat->tokenable;
            }
        }

        if (!$user) {
            return response()->json(['status' => 0, 'message' => 'Vui lòng đăng nhập để xem biên lai!'], 401);
        }

        $booking = Booking::find($id);

        // Trả HTTP 404 nhất quán, tránh tiết lộ đơn của người khác có tồn tại
        if (!$booking || !$user->can('viewReceipt', $booking)) {
            return response()->json(['status' => 0, 'message' => 'Không tìm thấy chứng từ của đơn đặt xe!'], 404);
        }

        if (empty($booking->bill_image)) {
            return response()->json(['status' => 0, 'message' => 'Đơn đặt xe chưa có biên lai được tải lên!'], 404);
        }

        $filename = basename($booking->bill_image);

        // 1. Kiểm tra vị trí Private Storage
        $privatePath = storage_path('app/private/bills/' . $filename);
        if (File::exists($privatePath)) {
            $response = response()->file($privatePath, [
                'Content-Type' => File::mimeType($privatePath),
            ]);
            $response->headers->set('Cache-Control', 'private, no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            return $response;
        }

        // 2. Kế hoạch tương thích ngược: Tìm file tại legacy public uploads
        $legacyPath = public_path($booking->bill_image);
        if (!File::exists($legacyPath)) {
            $legacyPath = public_path('uploads/bills/' . $filename);
        }

        if (File::exists($legacyPath)) {
            // Tự động di chuyển vào private storage an toàn
            $privateDir = storage_path('app/private/bills');
            if (!File::exists($privateDir)) {
                File::makeDirectory($privateDir, 0755, true);
            }
            File::copy($legacyPath, $privatePath);
            File::delete($legacyPath);

            $booking->bill_image = 'private/bills/' . $filename;
            $booking->save();

            $response = response()->file($privatePath, [
                'Content-Type' => File::mimeType($privatePath),
            ]);
            $response->headers->set('Cache-Control', 'private, no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            return $response;
        }

        return response()->json(['status' => 0, 'message' => 'Không tìm thấy tệp biên lai vật lý trên máy chủ!'], 404);
    }
}
