<?php

namespace App\Http\Requests\Admin;

use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;

class GetCarScheduleRequest extends FormRequest
{
    /**
     * Xác thực quyền truy cập: Chỉ dành riêng cho Admin đang kích hoạt
     */
    public function authorize(): bool
    {
        $admin = auth('admin')->user();
        if (!$admin || !($admin instanceof Admin)) {
            return false;
        }

        return (int) ($admin->tinh_trang ?? 1) !== 0;
    }

    /**
     * Validation rules cho truy vấn lịch xe
     */
    public function rules(): array
    {
        return [
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'car_id'      => 'nullable|integer|exists:cars,id',
            'category_id' => 'nullable|integer|exists:car_categories,id',
            'search'      => 'nullable|string|max:100',
            'status'      => 'nullable|string|in:all,pending,confirmed,in_progress,completed,cancelled,maintenance',
            'page'        => 'nullable|integer|min:1',
            'per_page'    => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'Ngày kết thúc phải diễn ra sau hoặc bằng ngày bắt đầu.',
            'car_id.exists'           => 'Xe yêu cầu không tồn tại trong hệ thống.',
            'category_id.exists'      => 'Danh mục xe không hợp lệ.',
            'status.in'               => 'Trạng thái lọc lịch không hợp lệ.',
        ];
    }
}
