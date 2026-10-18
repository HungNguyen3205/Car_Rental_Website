<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactActivity extends Model
{
    use HasFactory;

    public const TYPE_NOTE          = 'note';
    public const TYPE_STATUS_CHANGE = 'status_change';
    public const TYPE_ASSIGNED      = 'assigned';
    public const TYPE_REASSIGNED    = 'reassigned';

    protected $table = 'contact_activities';

    protected $fillable = [
        'contact_id',
        'admin_id',
        'admin_name',
        'type',
        'content',
        'metadata',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
