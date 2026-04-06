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
        'type',
        'status',
        'image',
        'category_id'
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
