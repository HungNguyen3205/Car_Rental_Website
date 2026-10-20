<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostImportBatch extends Model
{
    use HasFactory;

    protected $table = 'post_import_batches';

    protected $fillable = [
        'file_name',
        'total_rows',
        'valid_rows',
        'error_rows',
        'skipped_empty_rows',
        'drive_folder_id',
        'status',
        'channel',
        'default_category_id',
        'created_by_admin_id',
        'confirmed_by_admin_id',
        'confirmed_at',
        'cancelled_by_admin_id',
        'cancelled_at',
    ];

    protected $casts = [
        'total_rows' => 'integer',
        'valid_rows' => 'integer',
        'error_rows' => 'integer',
        'skipped_empty_rows' => 'integer',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(PostSchedule::class, 'batch_id');
    }

    public function defaultCategory(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'default_category_id');
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function confirmedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'confirmed_by_admin_id');
    }

    public function cancelledByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'cancelled_by_admin_id');
    }
}
