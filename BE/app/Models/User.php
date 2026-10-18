<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    // Các phân loại nhu cầu thuê xe phổ biến (Rental Demand Types)
    public const DEMAND_SELF_DRIVE   = 'self_drive';   // Thuê xe tự lái
    public const DEMAND_WITH_DRIVER  = 'with_driver';  // Thuê xe có tài xế
    public const DEMAND_MONTHLY      = 'monthly';      // Thuê dài hạn / theo tháng
    public const DEMAND_WEDDING      = 'wedding';      // Xe hoa / sự kiện
    public const DEMAND_TRAVEL       = 'travel';       // Du lịch / gia đình

    public const DEMANDS = [
        self::DEMAND_SELF_DRIVE  => ['code' => self::DEMAND_SELF_DRIVE,  'label' => 'Tự lái'],
        self::DEMAND_WITH_DRIVER => ['code' => self::DEMAND_WITH_DRIVER, 'label' => 'Có tài xế'],
        self::DEMAND_MONTHLY     => ['code' => self::DEMAND_MONTHLY,     'label' => 'Thuê dài hạn'],
        self::DEMAND_WEDDING     => ['code' => self::DEMAND_WEDDING,     'label' => 'Xe cưới / Sự kiện'],
        self::DEMAND_TRAVEL      => ['code' => self::DEMAND_TRAVEL,      'label' => 'Du lịch / Gia đình'],
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'ho_ten',
        'email',
        'password',
        'so_dien_thoai',
        'ngay_sinh',
        'gioi_tinh',
        'dia_chi',
        'role',
        'tinh_trang',
        'hash_active',
        'hash_reset',
        'nhu_cau_thue',
        'ngan_sach_du_kien',
        'car_id',
        'ghi_chu_so_thich',
        'gplx_hang',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = ['role', 'is_active'];

    public function getRoleAttribute(): string
    {
        return 'customer';
    }

    public function getIsActiveAttribute(): int
    {
        return (int) ($this->tinh_trang ?? 1);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'ngan_sach_du_kien' => 'decimal:2',
            'tinh_trang'        => 'integer',
        ];
    }

    /**
     * Lịch sử các đơn đặt xe của khách hàng này
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'user_id')->orderByDesc('id');
    }

    /**
     * Các yêu cầu tư vấn / liên hệ của khách hàng này
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'user_id')->orderByDesc('id');
    }

    /**
     * Nhật ký chăm sóc / ghi chú nội bộ theo thời gian
     */
    public function notes(): HasMany
    {
        return $this->hasMany(CustomerNote::class, 'user_id')->orderByDesc('id');
    }

    /**
     * Xe quan tâm yêu thích hàng đầu
     */
    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'car_id');
    }

    /**
     * Lịch hẹn tư vấn & xem xe của khách hàng này
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'user_id')->orderByDesc('start_time');
    }

    /**
     * Scope tìm kiếm và lọc danh sách khách hàng
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        if (!empty($filters['search'])) {
            $s = trim($filters['search']);
            $query->where(function (Builder $q) use ($s) {
                $q->where('ho_ten', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('so_dien_thoai', 'like', "%{$s}%")
                    ->orWhere('dia_chi', 'like', "%{$s}%");
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('tinh_trang', (int) $filters['status']);
        }

        if (!empty($filters['nhu_cau_thue'])) {
            $query->where('nhu_cau_thue', $filters['nhu_cau_thue']);
        }

        if (!empty($filters['car_id'])) {
            $query->where('car_id', $filters['car_id']);
        }

        return $query;
    }
}
