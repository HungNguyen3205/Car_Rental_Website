<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

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
        'payment_note'
    ];

    protected $casts = [
        'payment_confirmed_at' => 'datetime',
        'is_thanh_toan'        => 'integer',
        'tinh_trang'           => 'integer',
        'tong_tien'            => 'float',
    ];

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
}
