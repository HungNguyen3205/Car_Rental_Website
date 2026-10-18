<?php

namespace App\Services\Revenue;

use App\Models\Booking;
use App\Models\BookingTransaction;
use App\Models\Car;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RevenueCalculationService
{
    /**
     * Chuẩn hóa tính toán số liệu doanh thu và giá trị đơn hàng cho toàn hệ thống.
     * 
     * Định nghĩa các chỉ số tài chính:
     * 1. total_booking_value (Tổng giá trị đặt đơn / GMV):
     *    Tổng tiền của toàn bộ đơn đặt xe được tạo, không phân biệt trạng thái duyệt hay thanh toán.
     * 2. confirmed_booking_value (Giá trị đơn đã xác nhận thuê):
     *    Tổng tiền của các đơn có tinh_trang = 1 (Đã duyệt xe sẵn sàng bàn giao).
     * 3. paid_booking_value (Tổng giá trị đơn được đánh dấu đã thanh toán):
     *    Tổng tiền của các đơn có is_thanh_toan = 1 (qua biên lai/thủ công).
     * 4. valid_paid_revenue (Doanh thu đơn hợp lệ đã thanh toán):
     *    Tổng tiền của các đơn có is_thanh_toan = 1 VÀ tinh_trang != 2 (không tính các đơn đã hủy).
     *    Đây là chỉ số doanh thu chính thức được ghi nhận.
     * 5. refund_review_value (Tiền đơn hủy chờ xử lý hoàn tiền / đối soát):
     *    Tổng tiền của các đơn có is_thanh_toan = 1 VÀ tinh_trang = 2 (Đã hủy).
     * 6. unpaid_confirmed_value (Công nợ đơn đã duyệt nhưng chưa thanh toán):
     *    Tổng tiền của các đơn có tinh_trang = 1 VÀ is_thanh_toan = 0.
     * 7. pending_receipt_value (Tiền đơn đang chờ đối soát biên lai):
     *    Tổng tiền của các đơn có is_thanh_toan = 0 VÀ có bill_image.
     */

    public const DATE_BY_PAYMENT = 'payment_date';
    public const DATE_BY_BOOKING = 'booking_date';

    /**
     * Lấy thống kê tổng quan doanh thu và chỉ số cho Admin Dashboard.
     *
     * @param array $options [
     *   'date_by'    => 'payment_date' | 'booking_date',
     *   'start_date' => 'Y-m-d' | null,
     *   'end_date'   => 'Y-m-d' | null,
     *   'months'     => int (default 6)
     * ]
     * @return array
     */
    public function getDashboardRevenueStats(array $options = []): array
    {
        $dateBy = $options['date_by'] ?? self::DATE_BY_PAYMENT;
        $months = isset($options['months']) ? (int) $options['months'] : 6;
        $startDate = !empty($options['start_date']) ? Carbon::parse($options['start_date'])->startOfDay() : null;
        $endDate = !empty($options['end_date']) ? Carbon::parse($options['end_date'])->endOfDay() : null;

        // 1. Thống kê toàn diện (All-time Summary hoặc Filtered Summary)
        $summary = $this->calculateRevenueSummary($dateBy, $startDate, $endDate);

        // 2. Thống kê xe & người dùng
        $totalCars = Car::count();
        $availableCars = Car::where('status', 'available')->count();
        $totalUsers = User::count();

        // 3. Biểu đồ doanh thu theo tháng (Monthly Trend)
        $revenueChart = $this->getMonthlyRevenueTrend($months, $dateBy);

        // 4. Phân bổ trạng thái thuê (Rental Status Distribution)
        $statusDistribution = Booking::select(
            DB::raw('count(*) as count'),
            'tinh_trang'
        )
        ->groupBy('tinh_trang')
        ->get();

        // 5. Phân bổ trạng thái thanh toán (Payment Status Distribution)
        $paymentDistribution = [
            [
                'code'  => 'valid_paid',
                'label' => 'Đã thanh toán (Hợp lệ)',
                'count' => Booking::where('is_thanh_toan', 1)->where('tinh_trang', '!=', Booking::STATUS_CANCELLED)->count(),
                'total' => $this->roundCurrency(Booking::where('is_thanh_toan', 1)->where('tinh_trang', '!=', Booking::STATUS_CANCELLED)->sum('tong_tien')),
            ],
            [
                'code'  => 'refund_review',
                'label' => 'Đã thu tiền (Cần xử lý hoàn tiền)',
                'count' => Booking::where('is_thanh_toan', 1)->where('tinh_trang', Booking::STATUS_CANCELLED)->count(),
                'total' => $this->roundCurrency(Booking::where('is_thanh_toan', 1)->where('tinh_trang', Booking::STATUS_CANCELLED)->sum('tong_tien')),
            ],
            [
                'code'  => 'pending_receipt',
                'label' => 'Chờ đối soát biên lai',
                'count' => Booking::where('is_thanh_toan', 0)->whereNotNull('bill_image')->count(),
                'total' => $this->roundCurrency(Booking::where('is_thanh_toan', 0)->whereNotNull('bill_image')->sum('tong_tien')),
            ],
            [
                'code'  => 'unpaid_approved',
                'label' => 'Đã duyệt chưa thanh toán',
                'count' => Booking::where('is_thanh_toan', 0)->where('tinh_trang', Booking::STATUS_CONFIRMED)->count(),
                'total' => $this->roundCurrency(Booking::where('is_thanh_toan', 0)->where('tinh_trang', Booking::STATUS_CONFIRMED)->sum('tong_tien')),
            ],
            [
                'code'  => 'unpaid_pending',
                'label' => 'Đơn chờ duyệt chưa thanh toán',
                'count' => Booking::where('is_thanh_toan', 0)->where('tinh_trang', Booking::STATUS_PENDING)->whereNull('bill_image')->count(),
                'total' => $this->roundCurrency(Booking::where('is_thanh_toan', 0)->where('tinh_trang', Booking::STATUS_PENDING)->whereNull('bill_image')->sum('tong_tien')),
            ]
        ];

        return [
            'summary' => array_merge($summary, [
                'total_cars'     => $totalCars,
                'available_cars' => $availableCars,
                'total_users'    => $totalUsers,
            ]),
            'revenue_chart'        => $revenueChart['chart'],
            'legacy_undated'       => $revenueChart['legacy_undated'],
            'status_distribution' => $statusDistribution,
            'payment_distribution' => $paymentDistribution,
        ];
    }

    /**
     * Tính toán bảng tóm tắt doanh thu đồng bộ cho cả Dashboard và danh sách Đơn hàng.
     *
     * @param string $dateBy
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return array
     */
    public function calculateRevenueSummary(string $dateBy = self::DATE_BY_PAYMENT, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $baseQuery = Booking::query();

        // Tổng đơn & tổng giá trị toàn hệ thống (không lọc ngày)
        $totalBookingsAllTime = Booking::count();
        $pendingBookingsCount = Booking::where('tinh_trang', Booking::STATUS_PENDING)->count();

        // Kiểm tra số lượng đơn lịch sử thiếu ngày thanh toán (payment_confirmed_at IS NULL)
        $legacyMissingDateQuery = Booking::where('is_thanh_toan', 1)->whereNull('payment_confirmed_at');
        $legacyMissingDateCount = $legacyMissingDateQuery->count();
        $legacyMissingDateValue = $this->roundCurrency($legacyMissingDateQuery->sum('tong_tien'));

        // Áp dụng bộ lọc thời gian nếu có
        $bookingDateQuery = Booking::query();
        $paymentDateQuery = Booking::query();

        if ($startDate && $endDate) {
            $bookingDateQuery->whereBetween('created_at', [$startDate, $endDate]);
            $paymentDateQuery->whereBetween('payment_confirmed_at', [$startDate, $endDate]);
        } elseif ($startDate) {
            $bookingDateQuery->where('created_at', '>=', $startDate);
            $paymentDateQuery->where('payment_confirmed_at', '>=', $startDate);
        } elseif ($endDate) {
            $bookingDateQuery->where('created_at', '<=', $endDate);
            $paymentDateQuery->where('payment_confirmed_at', '<=', $endDate);
        }

        // Tùy theo $dateBy, tính toán các chỉ số:
        // - Với chỉ số về tạo đơn: dùng $bookingDateQuery
        // - Với chỉ số về thu tiền: nếu $dateBy === 'payment_date' và có khoảng ngày thì dùng $paymentDateQuery
        $activePaymentQuery = ($dateBy === self::DATE_BY_PAYMENT && ($startDate || $endDate)) 
            ? $paymentDateQuery 
            : ($dateBy === self::DATE_BY_BOOKING && ($startDate || $endDate) ? $bookingDateQuery : Booking::query());

        // 1. Tổng giá trị đơn đặt (Total Booking Value)
        $totalBookingValue = $this->roundCurrency((clone $bookingDateQuery)->sum('tong_tien'));
        $totalBookingsFiltered = (clone $bookingDateQuery)->count();

        // 2. Giá trị đơn đã xác nhận duyệt xe (Confirmed Rental Value)
        $confirmedBookingValue = $this->roundCurrency(
            (clone $bookingDateQuery)->where('tinh_trang', Booking::STATUS_CONFIRMED)->sum('tong_tien')
        );

        // 3. Tổng giá trị đơn được đánh dấu đã thanh toán (Gross Paid Bookings)
        $paidBookingValue = $this->roundCurrency(
            (clone $activePaymentQuery)->where('is_thanh_toan', 1)->sum('tong_tien')
        );

        // 4. Doanh thu đơn hợp lệ đã thanh toán (Valid Paid Revenue - Net Valid)
        $validPaidRevenue = $this->roundCurrency(
            (clone $activePaymentQuery)->where('is_thanh_toan', 1)->where('tinh_trang', '!=', Booking::STATUS_CANCELLED)->sum('tong_tien')
        );

        // 5. Tiền đơn hủy cần xử lý hoàn tiền (Refund Review Value)
        $refundReviewValue = $this->roundCurrency(
            (clone $activePaymentQuery)->where('is_thanh_toan', 1)->where('tinh_trang', Booking::STATUS_CANCELLED)->sum('tong_tien')
        );

        // 6. Công nợ đơn đã duyệt chưa thu tiền (Unpaid Confirmed)
        $unpaidConfirmedValue = $this->roundCurrency(
            (clone $bookingDateQuery)->where('tinh_trang', Booking::STATUS_CONFIRMED)->where('is_thanh_toan', 0)->sum('tong_tien')
        );

        // 7. Chờ đối soát biên lai (Pending Receipt)
        $pendingReceiptValue = $this->roundCurrency(
            (clone $bookingDateQuery)->where('is_thanh_toan', 0)->whereNotNull('bill_image')->sum('tong_tien')
        );

        // 8. Thống kê tiền cọc bảo đảm và các đợt thanh toán từ sổ giao dịch (booking_transactions)
        $totalDepositHeld = 0;
        $totalDepositDeducted = 0;
        $totalDepositRefunded = 0;
        $totalRentalPaid = 0;
        $totalRentalRefunded = 0;

        if (Schema::hasTable('booking_transactions')) {
            $txQuery = BookingTransaction::where('status', BookingTransaction::STATUS_CONFIRMED);

            if ($startDate && $endDate) {
                $txQuery->whereBetween('transaction_time', [$startDate, $endDate]);
            } elseif ($startDate) {
                $txQuery->where('transaction_time', '>=', $startDate);
            } elseif ($endDate) {
                $txQuery->where('transaction_time', '<=', $endDate);
            }

            $depositCollected = (int) (clone $txQuery)->where('type', BookingTransaction::TYPE_DEPOSIT_COLLECTION)->sum('amount');
            $totalDepositRefunded = (int) (clone $txQuery)->where('type', BookingTransaction::TYPE_DEPOSIT_REFUND)->sum('amount');
            $totalDepositDeducted = (int) (clone $txQuery)->where('type', BookingTransaction::TYPE_DEPOSIT_DEDUCTION)->sum('amount');
            // Cọc đang giữ = Cọc đã thu - Cọc đã hoàn - Cọc đã khấu trừ
            $totalDepositHeld = max(0, $depositCollected - $totalDepositRefunded - $totalDepositDeducted);

            $totalRentalPaid = (int) (clone $txQuery)->where('type', BookingTransaction::TYPE_RENTAL_PAYMENT)->sum('amount');
            $totalRentalRefunded = (int) (clone $txQuery)->where('type', BookingTransaction::TYPE_RENTAL_REFUND)->sum('amount');
        }

        return [
            // Tổng quan số lượng
            'total_bookings'             => ($startDate || $endDate) ? $totalBookingsFiltered : $totalBookingsAllTime,
            'total_bookings_all_time'    => $totalBookingsAllTime,
            'pending_bookings'           => $pendingBookingsCount,
            
            // Các chỉ số tiền tệ chuẩn hóa (đơn vị: VNĐ nguyên bản, không float làm tròn sai)
            'total_booking_value'        => $totalBookingValue,
            'confirmed_booking_value'    => $confirmedBookingValue,
            'paid_booking_value'         => $paidBookingValue,
            'valid_paid_revenue'         => $validPaidRevenue,
            'refund_review_value'        => $refundReviewValue,
            'unpaid_confirmed_value'     => $unpaidConfirmedValue,
            'pending_receipt_value'      => $pendingReceiptValue,

            // Quản lý tiền cọc và thanh toán nhiều lần
            // LƯU Ý: Tiền cọc đang giữ là quỹ ký quỹ bảo đảm hoàn lại, TUYỆT ĐỐI KHÔNG TÍNH VÀO DOANH THU THUÊ XE.
            'total_deposit_held'         => $totalDepositHeld,
            'total_deposit_deducted'     => $totalDepositDeducted,
            'total_deposit_refunded'     => $totalDepositRefunded,
            'total_rental_paid'          => $totalRentalPaid,
            'total_rental_refunded'      => $totalRentalRefunded,

            // Tương thích ngược với field total_revenue cũ nhưng trỏ vào valid_paid_revenue chuẩn
            'total_revenue'              => $validPaidRevenue,

            // Cảnh báo dữ liệu lịch sử thiếu ngày thanh toán
            'legacy_missing_date_count'  => $legacyMissingDateCount,
            'legacy_missing_date_value'  => $legacyMissingDateValue,

            // Siêu dữ liệu mô tả chỉ số
            'date_dimension'             => $dateBy,
            'reporting_method'           => 'manual_reconciled',
            'label_note'                 => 'Số liệu doanh thu phản ánh các đơn được đánh dấu thanh toán (is_thanh_toan=1) sau khi đối soát biên lai; chưa tích hợp cổng thanh toán trực tiếp.',
        ];
    }

    /**
     * Lấy xu hướng doanh thu theo tháng (Mặc định 6 tháng gần nhất).
     * Tuyệt đối không lấy created_at làm ngày thu tiền khi date_by = payment_date.
     * Đơn lịch sử thiếu ngày thanh toán được bóc tách riêng, không bịa ngày.
     *
     * @param int $months
     * @param string $dateBy
     * @return array
     */
    public function getMonthlyRevenueTrend(int $months = 6, string $dateBy = self::DATE_BY_PAYMENT): array
    {
        $months = max(1, min($months, 24)); // Giới hạn từ 1 đến 24 tháng
        $chartData = [];

        // 1. Kiểm tra đơn lịch sử có is_thanh_toan = 1 nhưng payment_confirmed_at IS NULL
        $legacyUndatedCount = Booking::where('is_thanh_toan', 1)->whereNull('payment_confirmed_at')->count();
        $legacyUndatedTotal = $this->roundCurrency(
            Booking::where('is_thanh_toan', 1)->whereNull('payment_confirmed_at')->sum('tong_tien')
        );

        $legacyUndated = [
            'count' => $legacyUndatedCount,
            'total' => $legacyUndatedTotal,
            'label' => 'Đơn thanh toán lịch sử (thiếu mốc ngày xác nhận)',
            'note'  => 'Các đơn này được bảo toàn số tiền đã thanh toán nhưng không gắn vào biểu đồ tháng để tránh bịa đặt ngày thu tiền.'
        ];

        // 2. Tính toán từng tháng
        for ($i = $months - 1; $i >= 0; $i--) {
            $carbonMonth = Carbon::now()->subMonths($i);
            $monthStr = $carbonMonth->format('m/Y');
            $startOfMonth = $carbonMonth->copy()->startOfMonth();
            $endOfMonth = $carbonMonth->copy()->endOfMonth();

            if ($dateBy === self::DATE_BY_PAYMENT) {
                // Thu tiền tính theo payment_confirmed_at thực tế
                $validPaid = Booking::where('is_thanh_toan', 1)
                    ->where('tinh_trang', '!=', Booking::STATUS_CANCELLED)
                    ->whereBetween('payment_confirmed_at', [$startOfMonth, $endOfMonth])
                    ->sum('tong_tien');

                $refundReview = Booking::where('is_thanh_toan', 1)
                    ->where('tinh_trang', Booking::STATUS_CANCELLED)
                    ->whereBetween('payment_confirmed_at', [$startOfMonth, $endOfMonth])
                    ->sum('tong_tien');

                $bookingValue = Booking::whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->sum('tong_tien');
            } else {
                // Tính theo ngày đặt xe (created_at)
                $validPaid = Booking::where('is_thanh_toan', 1)
                    ->where('tinh_trang', '!=', Booking::STATUS_CANCELLED)
                    ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->sum('tong_tien');

                $refundReview = Booking::where('is_thanh_toan', 1)
                    ->where('tinh_trang', Booking::STATUS_CANCELLED)
                    ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->sum('tong_tien');

                $bookingValue = Booking::whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->sum('tong_tien');
            }

            $chartData[] = [
                'month'               => $monthStr,
                'total'               => $this->roundCurrency($validPaid), // Tương thích trường total cũ
                'valid_paid_revenue'  => $this->roundCurrency($validPaid),
                'refund_review_value' => $this->roundCurrency($refundReview),
                'total_booking_value' => $this->roundCurrency($bookingValue),
            ];
        }

        return [
            'chart'          => $chartData,
            'legacy_undated' => $legacyUndated,
            'date_dimension' => $dateBy,
        ];
    }

    /**
     * Làm tròn số tiền chính xác, tránh sai lệch floating point.
     * Đơn vị tiền tệ VNĐ không có số thập phân.
     *
     * @param mixed $value
     * @return int
     */
    public function roundCurrency($value): int
    {
        if (is_null($value)) {
            return 0;
        }
        return (int) round((float) $value);
    }
}
