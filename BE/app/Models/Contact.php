<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    use HasFactory;

    public const STATUS_PENDING     = 0;
    public const STATUS_IN_PROGRESS = 1;
    public const STATUS_RESOLVED    = 2;

    public const STATUSES = [
        self::STATUS_PENDING     => ['label' => 'Chờ xử lý', 'badge' => 'warning'],
        self::STATUS_IN_PROGRESS => ['label' => 'Đang xử lý', 'badge' => 'info'],
        self::STATUS_RESOLVED    => ['label' => 'Đã xử lý', 'badge' => 'success'],
    ];

    protected $table = 'contacts';

    protected $fillable = [
        'user_id',
        'ho_ten',
        'email',
        'so_dien_thoai',
        'tieu_de',
        'noi_dung',
        'trang_thai',
        'ghi_chu_noi_bo',
        'handled_by',
        'handled_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'trang_thai'  => 'integer',
        'handled_at'  => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'handled_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->trang_thai]['label'] ?? 'Không xác định';
    }

    public function getStatusBadgeAttribute(): string
    {
        return self::STATUSES[$this->trang_thai]['badge'] ?? 'secondary';
    }
}
