<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\Export\CsvExportService;
use App\Services\Revenue\RevenueCalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExportController extends Controller
{
    protected RevenueCalculationService $revenueService;

    public function __construct(RevenueCalculationService $revenueService)
    {
        $this->revenueService = $revenueService;
    }

    /**
     * Xuất danh sách đơn đặt xe ra file CSV UTF-8.
     * Áp dụng chống Formula Injection, phân quyền theo vai trò (kế toán/điều phối/chủ),
     * lọc chính xác theo bộ lọc màn hình và xử lý theo lô (chunking).
     */
    public function exportBookings(Request $request)
    {
        $admin = Auth::guard('admin')->user() ?? Auth::guard('sanctum')->user();

        // Kiểm tra quyền cơ bản: phải có quyền xem đơn hoặc xem báo cáo hoặc chủ hệ thống
        if (!$admin || (!$admin->isOwner() && !$admin->hasPermission('bookings.view') && !$admin->hasPermission('reports.view'))) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn không có quyền xuất dữ liệu đơn đặt xe!',
            ], 403);
        }

        // Phân quyền trường tài chính: Kế toán và Chủ hệ thống được xem số tiền; Điều phối viên thuần túy chỉ xem lịch và xe
        $isFinancial = $admin->isOwner() || $admin->hasPermission('payments.manage') || $admin->hasPermission('reports.view');

        // Khởi tạo Query theo đúng bộ lọc
        $query = Booking::query()
            ->join('cars', 'bookings.car_id', '=', 'cars.id')
            ->join('users', 'bookings.user_id', '=', 'users.id')
            ->select([
                'bookings.*',
                'cars.name as car_name',
                'users.ho_ten as user_name',
                'users.so_dien_thoai as user_phone',
                'users.email as user_email',
            ]);

        // Bộ lọc từ khóa (Mã đơn, Tên khách, SĐT, Tên xe)
        if ($request->filled('keyword')) {
            $q = trim($request->input('keyword'));
            $query->where(function ($sub) use ($q) {
                $sub->where('bookings.id', 'like', "%{$q}%")
                    ->orWhere('bookings.ma_dat_xe', 'like', "%{$q}%")
                    ->orWhere('users.ho_ten', 'like', "%{$q}%")
                    ->orWhere('users.email', 'like', "%{$q}%")
                    ->orWhere('users.so_dien_thoai', 'like', "%{$q}%")
                    ->orWhere('cars.name', 'like', "%{$q}%");
            });
        }

        // Bộ lọc trạng thái đơn hàng (tinh_trang: 0: Chờ duyệt, 1: Đã xác nhận, 2: Đang đi, 3: Hoàn thành, 4: Đã hủy)
        if ($request->has('tinh_trang') && $request->input('tinh_trang') !== '') {
            $query->where('bookings.tinh_trang', $request->input('tinh_trang'));
        }

        // Bộ lọc trạng thái thanh toán (is_thanh_toan: 0 / 1)
        if ($request->has('is_thanh_toan') && $request->input('is_thanh_toan') !== '') {
            $query->where('bookings.is_thanh_toan', $request->input('is_thanh_toan'));
        }

        // Bộ lọc xe
        if ($request->filled('car_id')) {
            $query->where('bookings.car_id', $request->input('car_id'));
        }

        // Bộ lọc khoảng thời gian thuê
        if ($request->filled('start_date')) {
            $query->where('bookings.start_date', '>=', Carbon::parse($request->input('start_date'))->startOfDay());
        }
        if ($request->filled('end_date')) {
            $query->where('bookings.end_date', '<=', Carbon::parse($request->input('end_date'))->endOfDay());
        }

        $query->orderByDesc('bookings.id');

        // Định dạng Headers
        $headers = [
            'Mã đơn',
            'Khách hàng',
            'Số điện thoại',
            'Tên xe',
            'Ngày bắt đầu',
            'Ngày kết thúc',
            'Địa điểm nhận xe',
            'Địa điểm trả xe',
            'Trạng thái thuê xe',
        ];

        if ($isFinancial) {
            $headers = array_merge($headers, [
                'Tổng tiền thuê (VND)',
                'Trạng thái thanh toán',
                'Đã thanh toán (VND)',
                'Còn phải thu (VND)',
                'Tiền cọc giữ xe (VND)',
                'Trạng thái cọc',
            ]);
        }

        $headers = array_merge($headers, [
            'Ghi chú của khách',
            'Lý do hủy đơn',
            'Ngày tạo đơn',
        ]);

        $filename = 'danh_sach_don_dat_xe_' . date('Ymd_His') . '.csv';

        return CsvExportService::streamDownload($filename, $headers, function ($stream) use ($query, $isFinancial) {
            $query->chunk(200, function ($bookings) use ($stream, $isFinancial) {
                foreach ($bookings as $b) {
                    $row = [
                        CsvExportService::sanitizeForCsv('#HD-' . $b->id),
                        CsvExportService::sanitizeForCsv($b->user_name),
                        CsvExportService::sanitizeForCsv($b->user_phone),
                        CsvExportService::sanitizeForCsv($b->car_name),
                        $b->start_date ? Carbon::parse($b->start_date)->format('d/m/Y H:i') : '',
                        $b->end_date ? Carbon::parse($b->end_date)->format('d/m/Y H:i') : '',
                        CsvExportService::sanitizeForCsv($b->dia_chi_nhan_xe ?: 'Tại showroom'),
                        CsvExportService::sanitizeForCsv($b->dia_chi_tra_xe ?: 'Tại showroom'),
                        $this->getRentalStatusText((int) $b->tinh_trang),
                    ];

                    if ($isFinancial) {
                        $financial = $b->getFinancialSummary();
                        $row[] = number_format($b->tong_tien, 0, ',', '.') . ' đ';
                        $row[] = $b->is_thanh_toan ? 'Đã thanh toán' : 'Chưa thanh toán';
                        $row[] = number_format($financial['paid_rent_amount'] ?? 0, 0, ',', '.') . ' đ';
                        $row[] = number_format($financial['remaining_rent_amount'] ?? 0, 0, ',', '.') . ' đ';
                        $row[] = number_format($financial['deposit_amount'] ?? 0, 0, ',', '.') . ' đ';
                        $row[] = $this->getDepositStatusText($financial['deposit_status'] ?? 'unpaid');
                    }

                    // Chống formula injection với các trường tự do do người dùng nhập
                    $row[] = CsvExportService::sanitizeForCsv($b->ly_do_thue);
                    $row[] = CsvExportService::sanitizeForCsv($b->cancel_reason);
                    $row[] = $b->created_at ? Carbon::parse($b->created_at)->format('d/m/Y H:i') : '';

                    fputcsv($stream, $row);
                }
            });
        });
    }

    /**
     * Xuất báo cáo tài chính & doanh thu theo kỳ.
     */
    public function exportRevenue(Request $request)
    {
        $admin = Auth::guard('admin')->user() ?? Auth::guard('sanctum')->user();

        // Chỉ kế toán và chủ hệ thống có quyền xem và xuất báo cáo tài chính
        if (!$admin || (!$admin->isOwner() && !$admin->hasPermission('reports.view'))) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn không có quyền xuất báo cáo doanh thu tài chính!',
            ], 403);
        }

        $dateBy = $request->input('date_by', RevenueCalculationService::DATE_BY_PAYMENT);
        $months = (int) $request->input('months', 6);

        $trendData = $this->revenueService->getMonthlyRevenueTrend($months, $dateBy);
        $chart = $trendData['chart'] ?? [];

        $headers = [
            'Kỳ báo cáo (Tháng)',
            'Tổng giá trị đặt đơn (VND)',
            'Doanh thu thực thu hợp lệ (VND)',
            'Tiền đơn hủy chờ hoàn / đối soát (VND)',
        ];

        $filename = 'bao_cao_doanh_thu_' . date('Ymd_His') . '.csv';

        return CsvExportService::streamDownload($filename, $headers, function ($stream) use ($chart) {
            foreach ($chart as $row) {
                fputcsv($stream, [
                    CsvExportService::sanitizeForCsv($row['month']),
                    number_format($row['total_booking_value'], 0, ',', '.') . ' đ',
                    number_format($row['valid_paid_revenue'], 0, ',', '.') . ' đ',
                    number_format($row['refund_review_value'], 0, ',', '.') . ' đ',
                ]);
            }
        });
    }

    private function getRentalStatusText(int $status): string
    {
        return match ($status) {
            0 => 'Chờ duyệt',
            1 => 'Đã xác nhận',
            2 => 'Đang thuê',
            3 => 'Hoàn thành',
            4 => 'Đã hủy',
            default => 'Không xác định',
        };
    }

    private function getDepositStatusText(string $status): string
    {
        return match ($status) {
            'paid'      => 'Đã thu cọc',
            'refunded'  => 'Đã hoàn trả',
            'partially_refunded' => 'Hoàn một phần',
            'deducted'  => 'Đã khấu trừ',
            default     => 'Chưa thu cọc',
        };
    }
}
