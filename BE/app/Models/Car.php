<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Car extends Model
{
    use HasFactory, SoftDeletes;

    // Trạng thái vận hành
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_MAINTENANCE = 'maintenance';

    // Trạng thái nội dung / công khai
    public const PUB_STATUS_DRAFT = 'draft';
    public const PUB_STATUS_PUBLISHED = 'published';
    public const PUB_STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'name',
        'license_plate',
        'price_per_day',
        'current_km',
        'type',
        'fuel_type',
        'city',
        'status',
        'publication_status',
        'image',
        'category_id',
        'seats',
        'battery_capacity',
        'range_per_charge',
        'transmission',
        'year_of_manufacture',
        'gallery',
        'rental_conditions',
        'extra_fees_info',
        'description',
        'is_active',
    ];

    protected $casts = [
        'current_km'          => 'integer',
        'price_per_day'       => 'float',
        'seats'               => 'integer',
        'year_of_manufacture' => 'integer',
        'gallery'             => 'array',
        'rental_conditions'   => 'array',
        'extra_fees_info'     => 'array',
        'is_active'           => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(CarCategory::class, 'category_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'car_id');
    }

    /**
     * Kiểm tra xem xe có đơn đặt xe đang hoạt động (chờ duyệt hoặc đã duyệt chưa kết thúc) hay không.
     */
    public function hasActiveBookings(): bool
    {
        return $this->bookings()
            ->whereIn('tinh_trang', [0, 1]) // 0: chờ duyệt, 1: đã xác nhận / đang thực hiện
            ->where('end_date', '>=', now())
            ->exists();
    }

    /**
     * Lấy danh sách các đơn đặt xe đang hoạt động của xe.
     */
    public function activeBookings()
    {
        return $this->bookings()
            ->whereIn('tinh_trang', [0, 1])
            ->where('end_date', '>=', now());
    }

    public function scopePubliclyAvailable($query)
    {
        return $query->where('cars.publication_status', self::PUB_STATUS_PUBLISHED);
    }
}
