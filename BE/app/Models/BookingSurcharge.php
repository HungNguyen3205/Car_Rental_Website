<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingSurcharge extends Model
{
    use HasFactory;

    public const TYPE_LATE_RETURN     = 'late_return';
    public const TYPE_EXCESS_KM       = 'excess_km';
    public const TYPE_CLEANING        = 'cleaning';
    public const TYPE_FUEL_DIFFERENCE = 'fuel_difference';
    public const TYPE_DAMAGE          = 'damage';
    public const TYPE_OTHER           = 'other';

    protected $fillable = [
        'booking_id',
        'handover_record_id',
        'type',
        'amount',
        'reason',
        'evidence_images',
        'is_deducted_from_deposit',
        'transaction_id',
        'created_by',
    ];

    protected $casts = [
        'amount'                   => 'integer',
        'evidence_images'          => 'array',
        'is_deducted_from_deposit' => 'boolean',
    ];

    protected $appends = [
        'type_label',
        'type_badge_color',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function handoverRecord()
    {
        return $this->belongsTo(BookingHandoverRecord::class, 'handover_record_id');
    }

    public function transaction()
    {
        return $this->belongsTo(BookingTransaction::class, 'transaction_id');
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_LATE_RETURN     => 'Phụ phí trễ giờ',
            self::TYPE_EXCESS_KM       => 'Phụ phí vượt số km',
            self::TYPE_CLEANING        => 'Phụ phí vệ sinh xe',
            self::TYPE_FUEL_DIFFERENCE => 'Phụ phí nhiên liệu / pin',
            self::TYPE_DAMAGE          => 'Bồi thường trầy xước / hư hại',
            self::TYPE_OTHER           => 'Phụ phí khác',
            default                    => 'Phụ phí',
        };
    }

    public function getTypeBadgeColorAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_LATE_RETURN     => 'warning',
            self::TYPE_EXCESS_KM       => 'info',
            self::TYPE_CLEANING        => 'secondary',
            self::TYPE_FUEL_DIFFERENCE => 'warning',
            self::TYPE_DAMAGE          => 'danger',
            default                    => 'light',
        };
    }
}
