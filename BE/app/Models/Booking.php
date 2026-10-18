<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    // Trạng thái vòng đời đơn thuê xe (Rental Lifecycle Status)
    public const STATUS_PENDING     = 0; // Chờ duyệt
    public const STATUS_CONFIRMED   = 1; // Đã duyệt / Xác nhận
    public const STATUS_CANCELLED   = 2; // Đã hủy
    public const STATUS_IN_PROGRESS = 3; // Đang thuê (Đã giao xe)
    public const STATUS_RETURNED    = 4; // Đã nhận trả xe (Chờ kiểm tra & chốt)
    public const STATUS_COMPLETED   = 5; // Hoàn tất đơn

    // Trạng thái thanh toán (Payment Status)
    public const PAYMENT_UNPAID   = 0; // Chưa thanh toán
    public const PAYMENT_PAID     = 1; // Đã xác nhận thanh toán đủ
    public const PAYMENT_PARTIAL  = 2; // Đã thanh toán một phần (trả trước / đợt)

    protected $fillable = [
        'user_id',
        'car_id',
        'ma_dat_xe',
        'start_date',
        'end_date',
        'ly_do_thue',
        'tong_tien',
        'tinh_trang',
        'is_thanh_toan',
        'dia_chi_nhan_xe',
        'dia_chi_tra_xe',
        'bill_image',
        'payment_confirmed_at',
        'payment_confirmed_by',
        'payment_note',
        'created_by_admin_id',
        'delivered_at',
        'delivered_by',
        'returned_at',
        'returned_by',
        'completed_at',
        'completed_by',
        'cancelled_at',
        'cancelled_by_type',
        'cancelled_by_id',
        'cancel_reason',
        'status_note',
    ];

    protected $casts = [
        'payment_confirmed_at' => 'datetime',
        'delivered_at'         => 'datetime',
        'returned_at'          => 'datetime',
        'completed_at'         => 'datetime',
        'cancelled_at'         => 'datetime',
        'is_thanh_toan'        => 'integer',
        'tinh_trang'           => 'integer',
        'tong_tien'            => 'float',
    ];

    /**
     * Quy tắc giữ lịch: Mọi đơn chưa hủy đều giữ lịch xe
     */
    public function isHoldingSchedule(): bool
    {
        return (int) $this->tinh_trang !== self::STATUS_CANCELLED;
    }

    public function car()
    {
        return $this->belongsTo(Car::class, 'car_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function confirmedByAdmin()
    {
        return $this->belongsTo(Admin::class, 'payment_confirmed_by');
    }

    public function createdByAdmin()
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function deliveredByAdmin()
    {
        return $this->belongsTo(Admin::class, 'delivered_by');
    }

    public function returnedByAdmin()
    {
        return $this->belongsTo(Admin::class, 'returned_by');
    }

    public function completedByAdmin()
    {
        return $this->belongsTo(Admin::class, 'completed_by');
    }

    public function transactions()
    {
        return $this->hasMany(BookingTransaction::class, 'booking_id')->orderBy('id', 'asc');
    }

    public function handovers()
    {
        return $this->hasMany(BookingHandoverRecord::class, 'booking_id')->orderBy('id', 'asc');
    }

    public function deliveryRecord()
    {
        return $this->hasOne(BookingHandoverRecord::class, 'booking_id')->where('type', BookingHandoverRecord::TYPE_DELIVERY);
    }

    public function returnRecord()
    {
        return $this->hasOne(BookingHandoverRecord::class, 'booking_id')->where('type', BookingHandoverRecord::TYPE_RETURN);
    }

    public function surcharges()
    {
        return $this->hasMany(BookingSurcharge::class, 'booking_id')->orderBy('id', 'asc');
    }

    /**
     * Tính toán số dư tài chính và trạng thái tiền cọc / tiền thuê từ sổ giao dịch thực tế
     */
    public function getFinancialSummary(): array
    {
        $hasTransactions = $this->transactions()->exists();

        if (!$hasTransactions) {
            $totalRent = (int) round($this->tong_tien);
            $isPaid = (int) $this->is_thanh_toan === 1;

            return [
                'has_transactions'         => false,
                'is_legacy'                => $isPaid,
                'total_rent_fee'           => $totalRent,
                'paid_rent_amount'         => $isPaid ? $totalRent : 0,
                'remaining_rent_amount'    => $isPaid ? 0 : $totalRent,
                'deposit_collected_amount' => 0,
                'deposit_refunded_amount'  => 0,
                'deposit_deducted_amount'  => 0,
                'deposit_held_amount'      => 0,
                'payment_status_code'      => $isPaid ? 'paid_legacy' : 'unpaid',
                'transactions'             => [],
            ];
        }

        $txs = $this->transactions()->where('status', BookingTransaction::STATUS_CONFIRMED)->get();

        $rentalPaid = 0;
        $rentalRefund = 0;
        $depositCollected = 0;
        $depositRefunded = 0;
        $depositDeducted = 0;

        foreach ($txs as $tx) {
            $amount = (int) $tx->amount;
            match ($tx->type) {
                BookingTransaction::TYPE_RENTAL_PAYMENT     => $rentalPaid += $amount,
                BookingTransaction::TYPE_RENTAL_REFUND      => $rentalRefund += $amount,
                BookingTransaction::TYPE_DEPOSIT_COLLECTION => $depositCollected += $amount,
                BookingTransaction::TYPE_DEPOSIT_REFUND     => $depositRefunded += $amount,
                BookingTransaction::TYPE_DEPOSIT_DEDUCTION  => $depositDeducted += $amount,
                default                                     => null,
            };
        }

        $totalRent = (int) round($this->tong_tien);
        $netRentPaid = max(0, $rentalPaid - $rentalRefund);
        $remainingRent = max(0, $totalRent - $netRentPaid);
        $depositHeld = max(0, $depositCollected - $depositRefunded - $depositDeducted);

        $statusCode = 'unpaid';
        if ($netRentPaid >= $totalRent && $totalRent > 0) {
            $statusCode = 'paid_full';
        } elseif ($netRentPaid > 0) {
            $statusCode = 'partial';
        }

        return [
            'has_transactions'         => true,
            'is_legacy'                => false,
            'total_rent_fee'           => $totalRent,
            'paid_rent_amount'         => $netRentPaid,
            'remaining_rent_amount'    => $remainingRent,
            'deposit_collected_amount' => $depositCollected,
            'deposit_refunded_amount'  => $depositRefunded,
            'deposit_deducted_amount'  => $depositDeducted,
            'deposit_held_amount'      => $depositHeld,
            'payment_status_code'      => $statusCode,
            'transactions'             => $this->transactions()->with('createdByAdmin:id,ho_ten')->get(),
        ];
    }
}
