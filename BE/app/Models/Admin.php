<?php

namespace App\Models;

use App\Services\Authorization\PermissionMatrix;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'admins';

    protected $fillable = [
        'ho_ten',
        'email',
        'password',
        'so_dien_thoai',
        'dia_chi',
        'role',
        'permissions',
        'tinh_trang',
    ];

    protected $attributes = [
        'tinh_trang' => 1,
        'role'       => 'owner',
    ];

    protected $appends = [
        'admin_role',
        'all_permissions',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'    => 'hashed',
            'permissions' => 'array',
            'tinh_trang'  => 'integer',
        ];
    }

    /**
     * Thuộc tính bí danh cho vai trò quản trị
     */
    public function getAdminRoleAttribute(): string
    {
        return $this->attributes['role'] ?? PermissionMatrix::ROLE_OWNER;
    }

    /**
     * Lấy toàn bộ danh sách quyền thực tế của Admin
     */
    public function getAllPermissionsAttribute(): array
    {
        $role = $this->admin_role;
        if ($role === PermissionMatrix::ROLE_OWNER) {
            return array_keys(PermissionMatrix::getAllPermissions());
        }

        $defaultPerms = PermissionMatrix::getDefaultPermissionsForRole($role);
        $customPerms = $this->permissions ?? [];

        return array_values(array_unique(array_merge($defaultPerms, $customPerms)));
    }

    /**
     * Kiểm tra admin có quyền cụ thể hay không
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        return in_array($permission, $this->all_permissions, true);
    }

    /**
     * Kiểm tra admin có ít nhất một trong các quyền được chỉ định hay không
     */
    public function hasAnyPermission(array $permissions): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        foreach ($permissions as $perm) {
            if ($this->hasPermission($perm)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Kiểm tra vai trò của Admin
     */
    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;
        return in_array($this->admin_role, $roles, true);
    }

    /**
     * Kiểm tra có phải chủ hệ thống không
     */
    public function isOwner(): bool
    {
        return $this->admin_role === PermissionMatrix::ROLE_OWNER;
    }

    /**
     * Danh sách lịch hẹn tư vấn được giao phụ trách
     */
    public function assignedAppointments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Appointment::class, 'assigned_to')->orderByDesc('start_time');
    }

    /**
     * Danh sách bài viết do Admin tạo
     */
    public function posts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Post::class, 'author_id');
    }

    /**
     * Danh sách bài viết do Admin duyệt
     */
    public function reviewedPosts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Post::class, 'reviewer_id');
    }
}
