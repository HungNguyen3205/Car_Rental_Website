<?php

namespace App\Models;

use App\Services\Authorization\PermissionMatrix;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'car_id',
        'ho_ten',
        'email',
        'so_dien_thoai',
        'tieu_de',
        'noi_dung',
        'trang_thai',
        'ghi_chu_noi_bo',
        'handled_by',
        'handled_at',
        'assigned_to',
        'assigned_at',
        'updated_by',
        'completed_by',
        'completed_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'trang_thai'   => 'integer',
        'handled_at'   => 'datetime',
        'assigned_at'  => 'datetime',
        'completed_at' => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    protected $appends = [
        'status_label',
        'status_badge',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'car_id');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'handled_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'completed_by');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ContactActivity::class, 'contact_id')->orderByDesc('created_at');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'contact_id')->orderByDesc('start_time');
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->trang_thai]['label'] ?? 'Không xác định';
    }

    public function getStatusBadgeAttribute(): string
    {
        return self::STATUSES[$this->trang_thai]['badge'] ?? 'secondary';
    }

    // Scopes
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        // 1. Trạng thái
        if (isset($filters['trang_thai']) && $filters['trang_thai'] !== '' && $filters['trang_thai'] !== 'all') {
            $query->where('trang_thai', (int) $filters['trang_thai']);
        }

        // 2. Người phụ trách (assigned_to)
        if (isset($filters['assigned_to']) && $filters['assigned_to'] !== '' && $filters['assigned_to'] !== 'all') {
            if ($filters['assigned_to'] === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', (int) $filters['assigned_to']);
            }
        }

        // 3. Khoảng ngày (from_date, to_date)
        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        // 4. Từ khóa tìm kiếm
        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('ho_ten', 'like', $search)
                  ->orWhere('email', 'like', $search)
                  ->orWhere('so_dien_thoai', 'like', $search)
                  ->orWhere('tieu_de', 'like', $search)
                  ->orWhere('noi_dung', 'like', $search);
            });
        }

        return $query;
    }

    /**
     * Giới hạn phạm vi xem dữ liệu theo phân quyền của nhân viên:
     * - Chủ hệ thống (Owner) hoặc quản lý có quyền bookings.manage / admins.manage: xem toàn bộ.
     * - Nhân viên chỉ có quyền xem thông thường: chỉ xem các yêu cầu được giao cho mình hoặc các yêu cầu chưa có người nhận.
     */
    public function scopeVisibleTo(Builder $query, ?Admin $admin): Builder
    {
        if (!$admin) {
            return $query->whereRaw('1 = 0');
        }

        if ($admin->isOwner() || $admin->hasAnyPermission([
            PermissionMatrix::PERM_BOOKINGS_MANAGE,
            PermissionMatrix::PERM_ADMINS_MANAGE,
        ])) {
            return $query; // Toàn quyền xem tất cả
        }

        // Nhân viên hạn chế: chỉ xem việc được phân công cho mình hoặc việc chưa phân công
        return $query->where(function ($q) use ($admin) {
            $q->where('assigned_to', $admin->id)
              ->orWhereNull('assigned_to');
        });
    }
}
