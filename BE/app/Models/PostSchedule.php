<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PostSchedule extends Model
{
    use HasFactory;

    protected $table = 'post_schedules';

    protected $fillable = [
        'batch_id',
        'row_index',
        'fingerprint',
        'content',
        'title_suggested',
        'title',
        'category_id',
        'drive_image_name',
        'drive_file_id',
        'drive_file_modified_time',
        'drive_file_checksum',
        'image_snapshot_path',
        'image_mime',
        'image_size',
        'channel',
        'target_facebook_page_id',
        'scheduled_at',
        'status_website',
        'status_facebook',
        'post_id',
        'facebook_post_id',
        'facebook_post_url',
        'website_error',
        'facebook_error',
        'retry_count',
        'last_attempt_at',
        'locked_at',
        'locked_by',
        'published_at',
        'created_by_admin_id',
    ];

    protected $casts = [
        'row_index' => 'integer',
        'image_size' => 'integer',
        'retry_count' => 'integer',
        'scheduled_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'locked_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    protected $appends = [
        'snapshot_url',
    ];

    public function getSnapshotUrlAttribute(): ?string
    {
        if (empty($this->image_snapshot_path)) {
            return null;
        }

        if (str_starts_with($this->image_snapshot_path, 'http://') || str_starts_with($this->image_snapshot_path, 'https://')) {
            return $this->image_snapshot_path;
        }

        return Storage::disk('public')->url($this->image_snapshot_path);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PostImportBatch::class, 'batch_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'category_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    /**
     * Tạo chuỗi fingerprint nghiệp vụ duy nhất để chống import trùng lặp
     */
    public static function generateFingerprint(string $content, string $imageName, string $scheduledAtIso, string $channel): string
    {
        $normalizedContent = preg_replace('/\s+/', ' ', trim($content));
        $normalizedImage = mb_strtolower(trim($imageName), 'UTF-8');
        return hash('sha256', "{$normalizedContent}|{$normalizedImage}|{$scheduledAtIso}|{$channel}");
    }
}
