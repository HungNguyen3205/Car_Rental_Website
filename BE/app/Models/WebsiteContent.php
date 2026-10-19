<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebsiteContent extends Model
{
    use HasFactory;

    protected $table = 'website_contents';

    protected $fillable = [
        'section_key',
        'title',
        'draft_content',
        'published_content',
        'status',
        'has_unpublished_changes',
        'version',
        'published_at',
        'publisher_id',
        'updated_by',
    ];

    protected $casts = [
        'draft_content'           => 'array',
        'published_content'       => 'array',
        'has_unpublished_changes' => 'boolean',
        'version'                 => 'integer',
        'published_at'            => 'datetime',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(WebsiteContentVersion::class, 'content_id')->orderBy('version', 'desc');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'publisher_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    /**
     * Tạo một bản ghi snapshot phiên bản mới
     */
    public function recordSnapshot(?int $adminId = null, ?string $note = null): WebsiteContentVersion
    {
        return $this->versions()->create([
            'section_key' => $this->section_key,
            'version'     => $this->version,
            'content'     => $this->published_content,
            'note'        => $note ?? ("Xuất bản phiên bản v" . $this->version),
            'created_by'  => $adminId,
        ]);
    }
}
