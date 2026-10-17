<?php

namespace App\Http\Requests\Car;

use App\Http\Requests\BaseApiRequest;

class AdminCarUpdateRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:cars,id',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'price_per_day' => [
                'required',
                'numeric',
                'min:0',
            ],
            'category_id' => [
                'nullable',
                'integer',
                'exists:car_categories,id',
            ],
            'type' => [
                'nullable',
                'string',
                'max:100',
            ],
            'city' => [
                'nullable',
                'string',
                'max:100',
            ],
            'status' => [
                'nullable',
                'string',
                'in:available,rented,maintenance',
            ],
            'image' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required'            => 'Mã định danh xe là bắt buộc!',
            'id.exists'              => 'Xe cần cập nhật không tồn tại!',
            'name.required'          => 'Tên xe không được để trống!',
            'price_per_day.required' => 'Giá thuê mỗi ngày là bắt buộc!',
            'price_per_day.numeric'  => 'Giá thuê mỗi ngày phải là số hợp lệ!',
            'price_per_day.min'      => 'Giá thuê mỗi ngày không được âm!',
            'category_id.exists'     => 'Danh mục xe không tồn tại!',
            'status.in'              => 'Trạng thái xe không hợp lệ!',
        ];
    }
}
