<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingHandoverRecord extends Model
{
    use HasFactory;

    public const TYPE_DELIVERY = 'delivery';
    public const TYPE_RETURN   = 'return';

    public const FUEL_ELECTRIC = 'electric';
    public const FUEL_GASOLINE = 'gasoline';
    public const FUEL_DIESEL   = 'diesel';

    public const CONFIRM_ACCOUNT = 'account_confirmed';
    public const CONFIRM_OTP     = 'otp_confirmed';
    public const CONFIRM_DIRECT  = 'direct_verified';

    protected $fillable = [
        'booking_id',
        'type',
        'handover_time',
        'staff_id',
        'current_km',
        'fuel_type',
        'fuel_or_battery_level',
        'exterior_status',
        'interior_status',
        'checklist_items',
        'notes',
        'images',
        'is_odometer_adjusted',
        'odometer_adjustment_reason',
        'customer_confirmed',
        'customer_confirmed_at',
        'customer_confirmation_method',
        'customer_notes',
    ];

    protected $casts = [
        'handover_time'         => 'datetime',
        'customer_confirmed_at' => 'datetime',
        'checklist_items'       => 'array',
        'images'                => 'array',
        'current_km'            => 'integer',
        'fuel_or_battery_level' => 'integer',
        'is_odometer_adjusted'  => 'boolean',
        'customer_confirmed'    => 'boolean',
    ];

    protected $appends = [
        'type_label',
        'type_badge_color',
        'fuel_label',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function staff()
    {
        return $this->belongsTo(Admin::class, 'staff_id');
    }

    public function surcharges()
    {
        return $this->hasMany(BookingSurcharge::class, 'handover_record_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_DELIVERY => 'Biên bản bàn giao xe',
            self::TYPE_RETURN   => 'Biên bản nhận trả xe',
            default             => 'Biên bản kiểm tra',
        };
    }

    public function getTypeBadgeColorAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_DELIVERY => 'info',
            self::TYPE_RETURN   => 'secondary',
            default             => 'light',
        };
    }

    public function getFuelLabelAttribute(): string
    {
        return match ($this->fuel_type) {
            self::FUEL_ELECTRIC => 'Dung lượng pin',
            self::FUEL_GASOLINE => 'Mức xăng',
            self::FUEL_DIESEL   => 'Mức dầu',
            default             => 'Năng lượng',
        };
    }
}
