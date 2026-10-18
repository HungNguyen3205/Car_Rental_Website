<?php

namespace App\Services\Authorization;

class PermissionMatrix
{
    // 1. Roles
    public const ROLE_OWNER      = 'owner';
    public const ROLE_DISPATCHER = 'dispatcher';
    public const ROLE_ACCOUNTANT = 'accountant';

    // 2. Permissions
    public const PERM_CARS_MANAGE      = 'cars.manage';
    public const PERM_BOOKINGS_VIEW    = 'bookings.view';
    public const PERM_BOOKINGS_MANAGE  = 'bookings.manage';
    public const PERM_HANDOVERS_MANAGE = 'handovers.manage';
    public const PERM_PAYMENTS_MANAGE  = 'payments.manage';
    public const PERM_REPORTS_VIEW     = 'reports.view';
    public const PERM_ADMINS_MANAGE    = 'admins.manage';
    public const PERM_SETTINGS_MANAGE  = 'settings.manage';
    public const PERM_AUDIT_LOGS_VIEW  = 'audit_logs.view';

    /**
     * Danh mục toàn bộ các vai trò trong hệ thống
     */
    public static function getAllRoles(): array
    {
        return [
            self::ROLE_OWNER => [
                'code'        => self::ROLE_OWNER,
                'name'        => 'Chủ hệ thống',
                'description' => 'Toàn quyền cấu hình, nhân sự, kiểm toán, xe và dòng tiền',
                'level'       => 100,
            ],
            self::ROLE_DISPATCHER => [
                'code'        => self::ROLE_DISPATCHER,
                'name'        => 'Điều phối viên',
                'description' => 'Quản lý đội xe, lịch trình, xử lý đơn đặt và biên bản giao nhận',
                'level'       => 50,
            ],
            self::ROLE_ACCOUNTANT => [
                'code'        => self::ROLE_ACCOUNTANT,
                'name'        => 'Kế toán',
                'description' => 'Xem đơn, xác nhận thanh toán, ghi nhận cọc/hoàn tiền và xem báo cáo',
                'level'       => 50,
            ],
        ];
    }

    /**
     * Danh mục chi tiết các quyền hạn nghiệp vụ
     */
    public static function getAllPermissions(): array
    {
        return [
            self::PERM_CARS_MANAGE => [
                'code'        => self::PERM_CARS_MANAGE,
                'name'        => 'Quản lý xe',
                'group'       => 'Đội xe & Thiết bị',
                'description' => 'Thêm mới, sửa thông số, hình ảnh, cập nhật trạng thái và bảo dưỡng xe',
            ],
            self::PERM_BOOKINGS_VIEW => [
                'code'        => self::PERM_BOOKINGS_VIEW,
                'name'        => 'Xem đơn đặt xe',
                'group'       => 'Vận hành đơn hàng',
                'description' => 'Xem danh sách đơn, chi tiết lịch hẹn, lịch xe và thông tin khách hàng',
            ],
            self::PERM_BOOKINGS_MANAGE => [
                'code'        => self::PERM_BOOKINGS_MANAGE,
                'name'        => 'Xử lý đơn đặt xe',
                'group'       => 'Vận hành đơn hàng',
                'description' => 'Tạo đơn cho khách, duyệt hoặc hủy đơn, cập nhật trạng thái đơn hàng',
            ],
            self::PERM_HANDOVERS_MANAGE => [
                'code'        => self::PERM_HANDOVERS_MANAGE,
                'name'        => 'Bàn giao & Nhận xe',
                'group'       => 'Vận hành đơn hàng',
                'description' => 'Lập biên bản giao xe, nhận trả xe, kiểm tra km, pin/xăng và ảnh hiện trạng',
            ],
            self::PERM_PAYMENTS_MANAGE => [
                'code'        => self::PERM_PAYMENTS_MANAGE,
                'name'        => 'Xác nhận thu & hoàn tiền',
                'group'       => 'Tài chính & Thu chi',
                'description' => 'Xác nhận thanh toán tiền thuê, ghi nhận cọc giữ chân, khấu trừ và hoàn trả cọc',
            ],
            self::PERM_REPORTS_VIEW => [
                'code'        => self::PERM_REPORTS_VIEW,
                'name'        => 'Báo cáo & Đối soát',
                'group'       => 'Tài chính & Thu chi',
                'description' => 'Xem thống kê doanh thu, tỷ lệ lấp đầy xe và danh sách đối soát thanh toán',
            ],
            self::PERM_ADMINS_MANAGE => [
                'code'        => self::PERM_ADMINS_MANAGE,
                'name'        => 'Quản trị nhân sự Admin',
                'group'       => 'Hệ thống & Phân quyền',
                'description' => 'Thêm mới, chỉnh sửa, khóa hoặc phân quyền cho tài khoản quản trị viên',
            ],
            self::PERM_AUDIT_LOGS_VIEW => [
                'code'        => self::PERM_AUDIT_LOGS_VIEW,
                'name'        => 'Nhật ký thao tác (Audit Log)',
                'group'       => 'Hệ thống & Phân quyền',
                'description' => 'Tra cứu lịch sử thao tác, truy vết thay đổi dữ liệu nhạy cảm trên hệ thống',
            ],
            self::PERM_SETTINGS_MANAGE => [
                'code'        => self::PERM_SETTINGS_MANAGE,
                'name'        => 'Cấu hình hệ thống',
                'group'       => 'Hệ thống & Phân quyền',
                'description' => 'Cài đặt tham số hệ thống, giá thuê mặc định và chính sách cọc',
            ],
        ];
    }

    /**
     * Ma trận quyền mặc định gắn với từng vai trò
     */
    public static function getDefaultPermissionsForRole(string $role): array
    {
        return match ($role) {
            self::ROLE_OWNER => [
                self::PERM_CARS_MANAGE,
                self::PERM_BOOKINGS_VIEW,
                self::PERM_BOOKINGS_MANAGE,
                self::PERM_HANDOVERS_MANAGE,
                self::PERM_PAYMENTS_MANAGE,
                self::PERM_REPORTS_VIEW,
                self::PERM_ADMINS_MANAGE,
                self::PERM_SETTINGS_MANAGE,
                self::PERM_AUDIT_LOGS_VIEW,
            ],
            self::ROLE_DISPATCHER => [
                self::PERM_CARS_MANAGE,
                self::PERM_BOOKINGS_VIEW,
                self::PERM_BOOKINGS_MANAGE,
                self::PERM_HANDOVERS_MANAGE,
            ],
            self::ROLE_ACCOUNTANT => [
                self::PERM_BOOKINGS_VIEW,
                self::PERM_PAYMENTS_MANAGE,
                self::PERM_REPORTS_VIEW,
            ],
            default => [],
        };
    }

    /**
     * Kiểm tra quyền cấp phát vai trò để chống leo thang đặc quyền (Privilege Escalation)
     */
    public static function canActorGrantRole(string $actorRole, string $targetRole): bool
    {
        // Chỉ chủ hệ thống (owner) mới được phép tạo, sửa hoặc cấp quyền Chủ hệ thống (owner)
        if ($targetRole === self::ROLE_OWNER) {
            return $actorRole === self::ROLE_OWNER;
        }

        // Owner có thể cấp bất kỳ role nào
        if ($actorRole === self::ROLE_OWNER) {
            return true;
        }

        // Các vai trò khác không có quyền quản lý tài khoản admin
        return false;
    }
}
