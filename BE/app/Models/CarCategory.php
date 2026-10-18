<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFilter($query, array $filters)
    {
        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('description', 'like', $search);
            });
        }

        if (isset($filters['status']) && $filters['status'] !== 'all' && $filters['status'] !== '') {
            if ($filters['status'] === 'active' || $filters['status'] === '1' || $filters['status'] === 1 || $filters['status'] === true) {
                $query->where('is_active', true);
            } elseif ($filters['status'] === 'inactive' || $filters['status'] === '0' || $filters['status'] === 0 || $filters['status'] === false) {
                $query->where('is_active', false);
            }
        }

        return $query;
    }

    public function cars()
    {
        return $this->hasMany(Car::class, 'category_id');
    }
}
