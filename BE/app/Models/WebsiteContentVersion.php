<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteContentVersion extends Model
{
    use HasFactory;

    protected $table = 'website_content_versions';

    protected $fillable = [
        'content_id',
        'section_key',
        'version',
        'content',
        'note',
        'created_by',
    ];

    protected $casts = [
        'content' => 'array',
        'version' => 'integer',
    ];

    public function websiteContent(): BelongsTo
    {
        return $this->belongsTo(WebsiteContent::class, 'content_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
