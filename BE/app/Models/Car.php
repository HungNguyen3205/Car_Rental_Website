<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price_per_day',
        'current_km',
        'type',
        'fuel_type',
        'city',
        'status',
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
}
