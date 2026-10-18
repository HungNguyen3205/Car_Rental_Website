<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    /**
     * Lấy các tham số cấu hình công khai phục vụ giao diện người dùng.
     * Áp dụng cơ chế Cache và lọc sạch toàn bộ secret/khóa nhạy cảm.
     */
    public function index(): JsonResponse
    {
        $settings = SystemSetting::getPublicSettings();

        return response()->json([
            'status' => 1,
            'data'   => $settings,
        ]);
    }
}
