<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory;

    // Trạng thái cuộc hẹn
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_SHOW   = 'no_show';

    // Hình thức cuộc hẹn
    public const TYPE_VIEWING      = 'viewing';      // Xem xe thực tế & Lái thử
    public const TYPE_CONSULTATION = 'consultation'; // Tư vấn tại Showroom / Văn phòng
    public const TYPE_ONLINE       = 'online';       // Tư vấn trực tuyến / Video call

    public const STATUS_METADATA = [
        self::STATUS_SCHEDULED => ['label' => 'Đã lên lịch', 'badge' => 'primary', 'color' => '#3b82f6'],
        self::STATUS_COMPLETED => ['label' => 'Đã hoàn tất', 'badge' => 'success', 'color' => '#10b981'],
        self::STATUS_CANCELLED => ['label' => 'Đã hủy', 'badge' => 'danger', 'color' => '#ef4444'],
        self::STATUS_NO_SHOW   => ['label' => 'Khách vắng mặt', 'badge' => 'warning', 'color' => '#f59e0b'],
    ];

    public const TYPE_METADATA = [
        self::TYPE_VIEWING      => ['label' => 'Xem xe & Lái thử', 'icon' => 'fa-car-side'],
        self::TYPE_CONSULTATION => ['label' => 'Tư vấn trực tiếp', 'icon' => 'fa-comments'],
        self::TYPE_ONLINE       => ['label' => 'Tư vấn từ xa / Online', 'icon' => 'fa-video'],
    ];

    protected $table = 'appointments';

    protected $fillable = [
        'user_id',
        'contact_id',
        'assigned_to',
        'car_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'meeting_type',
        'location',
        'start_time',
        'end_time',
        'status',
        'reminder_minutes',
        'reminder_sent_at',
        'cancel_reason',
        'cancelled_by',
        'cancelled_at',
        'result_summary',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_time'       => 'datetime',
        'end_time'         => 'datetime',
        'reminder_sent_at' => 'datetime',
        'cancelled_at'     => 'datetime',
        'reminder_minutes' => 'integer',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
    ];

    protected $appends = [
        'formatted_id',
        'status_label',
        'status_badge',
        'status_color',
        'meeting_type_label',
        'duration_minutes',
    ];

    public function getFormattedIdAttribute(): string
    {
        return '#APT-' . $this->id;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_METADATA[$this->status]['label'] ?? $this->status;
    }

    public function getStatusBadgeAttribute(): string
    {
        return self::STATUS_METADATA[$this->status]['badge'] ?? 'secondary';
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_METADATA[$this->status]['color'] ?? '#6b7280';
    }

    public function getMeetingTypeLabelAttribute(): string
    {
        return self::TYPE_METADATA[$this->meeting_type]['label'] ?? $this->meeting_type;
    }

    public function getDurationMinutesAttribute(): int
    {
        if ($this->start_time && $this->end_time) {
            return (int) $this->start_time->diffInMinutes($this->end_time);
        }
        return 0;
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'car_id');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'cancelled_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        // 1. Tìm kiếm từ khóa
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            // Hỗ trợ tìm kiếm theo format '#APT-123'
            if (preg_match('/^#?APT-(\d+)$/i', $search, $matches)) {
                $query->where('id', (int) $matches[1]);
            } else {
                $query->where(function ($q) use ($search) {
                    $q->where('customer_name', 'like', "%{$search}%")
                      ->orWhere('customer_phone', 'like', "%{$search}%")
                      ->orWhere('customer_email', 'like', "%{$search}%")
                      ->orWhere('location', 'like', "%{$search}%");
                });
            }
        }

        // 2. Trạng thái
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        // 3. Nhân viên phụ trách
        if (!empty($filters['assigned_to']) && $filters['assigned_to'] !== 'all') {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        // 4. Xe quan tâm
        if (!empty($filters['car_id']) && $filters['car_id'] !== 'all') {
            $query->where('car_id', $filters['car_id']);
        }

        // 5. Loại hình hẹn
        if (!empty($filters['meeting_type']) && $filters['meeting_type'] !== 'all') {
            $query->where('meeting_type', $filters['meeting_type']);
        }

        // 6. Khách hàng cụ thể
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // 7. Yêu cầu tư vấn cụ thể
        if (!empty($filters['contact_id'])) {
            $query->where('contact_id', $filters['contact_id']);
        }

        // 8. Khoảng ngày bắt đầu
        if (!empty($filters['from_date'])) {
            $query->where('start_time', '>=', Carbon::parse($filters['from_date'])->startOfDay());
        }

        if (!empty($filters['to_date'])) {
            $query->where('start_time', '<=', Carbon::parse($filters['to_date'])->endOfDay());
        }

        return $query;
    }
}
