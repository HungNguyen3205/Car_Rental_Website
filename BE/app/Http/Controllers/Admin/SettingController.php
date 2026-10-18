<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    /**
     * Lấy toàn bộ cấu hình hệ thống phục vụ trang quản trị.
     */
    public function index(): JsonResponse
    {
        $allSettings = SystemSetting::all();
        $formatted = [];

        foreach ($allSettings as $item) {
            $formatted[$item->key] = [
                'key'         => $item->key,
                'value'       => $item->getParsedValue(),
                'type'        => $item->type,
                'group'       => $item->group,
                'is_public'   => $item->is_public,
                'description' => $item->description,
                'updated_at'  => $item->updated_at,
            ];
        }

        return response()->json([
            'status' => 1,
            'data'   => $formatted,
        ]);
    }

    /**
     * Cập nhật các thông số cấu hình hệ thống.
     * Tự động phát hiện thay đổi thông tin ngân hàng và ghi vết kiểm toán (Audit Log).
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'site_name'                  => 'sometimes|string|max:100',
            'site_logo'                  => 'nullable|string|max:500',
            'hotline'                    => 'sometimes|string|max:30',
            'support_email'              => 'sometimes|email|max:100',
            'company_address'            => 'sometimes|string|max:255',
            'service_locations'          => 'sometimes|array',
            'service_locations.*'        => 'string|max:100',
            'bank_name'                  => 'sometimes|string|max:100',
            'bank_account_number'        => 'sometimes|string|max:50',
            'bank_account_holder'        => 'sometimes|string|max:100',
            'bank_bin'                   => 'sometimes|string|max:20',
            'bank_transfer_instruction'  => 'nullable|string|max:500',
            'rental_policy'              => 'sometimes|array',
        ]);

        $admin = Auth::guard('admin')->user() ?? Auth::guard('sanctum')->user();
        $adminId = $admin?->id;

        // 1. Kiểm tra phát hiện thay đổi thông tin ngân hàng
        $bankKeys = ['bank_name', 'bank_account_number', 'bank_account_holder', 'bank_bin', 'bank_transfer_instruction'];
        $bankChanged = false;
        $oldBankValues = [];
        $newBankValues = [];

        foreach ($bankKeys as $bKey) {
            if ($request->has($bKey)) {
                $currentVal = SystemSetting::get($bKey);
                $newVal = $request->input($bKey);
                if ($currentVal !== $newVal) {
                    $bankChanged = true;
                }
                $oldBankValues[$bKey] = $currentVal;
                $newBankValues[$bKey] = $newVal;
            }
        }

        // 2. Lưu từng cấu hình tương ứng
        $groups = [
            'site_name'                 => 'general',
            'site_logo'                 => 'general',
            'service_locations'         => 'general',
            'hotline'                   => 'contact',
            'support_email'             => 'contact',
            'company_address'           => 'contact',
            'bank_name'                 => 'bank',
            'bank_account_number'       => 'bank',
            'bank_account_holder'       => 'bank',
            'bank_bin'                  => 'bank',
            'bank_transfer_instruction' => 'bank',
            'rental_policy'             => 'policy',
        ];

        foreach ($validated as $key => $val) {
            $group = $groups[$key] ?? 'general';
            SystemSetting::set($key, $val, $group, true, $adminId);
        }

        // 3. Ghi audit log nếu thông tin ngân hàng thay đổi
        // Đảm bảo không sửa ngầm snapshot của đơn/giao dịch cũ
        if ($bankChanged) {
            AuditService::log(
                action: 'settings.bank_updated',
                targetType: 'SystemSetting',
                targetId: null,
                targetLabel: 'Tài khoản ngân hàng VietQR',
                newValues: $newBankValues,
                oldValues: $oldBankValues
            );
        }

        // 4. Xóa cache công khai để Frontend phản hồi ngay
        SystemSetting::clearPublicCache();

        return response()->json([
            'status'  => 1,
            'message' => 'Cập nhật cấu hình hệ thống thành công!',
            'data'    => SystemSetting::getPublicSettings(),
        ]);
    }
}
