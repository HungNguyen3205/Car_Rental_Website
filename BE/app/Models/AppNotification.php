<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppNotification extends Model
{
    use HasFactory;

    public const TYPE_BOOKING_CREATED   = 'booking_created';
    public const TYPE_BOOKING_CONFIRMED = 'booking_confirmed';
    public const TYPE_BOOKING_CANCELLED = 'booking_cancelled';
    public const TYPE_PAYMENT_CONFIRMED = 'payment_confirmed';
    public const TYPE_UPCOMING_PICKUP   = 'upcoming_pickup';
    public const TYPE_UPCOMING_RETURN   = 'upcoming_return';

    protected $table = 'app_notifications';

    protected $fillable = [
        'user_id',
        'booking_id',
        'type',
        'title',
        'content',
        'action_url',
        'data',
        'is_read',
        'read_at',
        'dedup_key',
    ];

    protected $casts = [
        'data'       => 'array',
        'is_read'    => 'boolean',
        'read_at'    => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function markAsRead(): bool
    {
        if ($this->is_read) {
            return true;
        }

        $this->is_read = true;
        $this->read_at = now();
        return $this->save();
    }
}
