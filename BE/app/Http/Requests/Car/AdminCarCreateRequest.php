<?php

namespace App\Http\Requests\Car;

use App\Http\Requests\BaseApiRequest;

class AdminCarCreateRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'license_plate' => [
                'nullable',
                'string',
                'max:50',
            ],
            'price_per_day' => [
                'required',
                'numeric',
                'min:0',
            ],
            'current_km' => [
                'nullable',
                'integer',
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
            'publication_status' => [
                'nullable',
                'string',
                'in:draft,published,archived',
            ],
            'image' => [
                'nullable',
                'string',
                'max:500',
            ],
            'fuel_type' => [
                'nullable',
                'string',
                'in:electric,gasoline,diesel,hybrid',
            ],
            'seats' => [
                'nullable',
                'integer',
                'min:1',
                'max:50',
            ],
            'battery_capacity' => [
                'nullable',
                'string',
                'max:100',
            ],
            'range_per_charge' => [
                'nullable',
                'string',
                'max:100',
            ],
            'transmission' => [
                'nullable',
                'string',
                'max:50',
            ],
            'year_of_manufacture' => [
                'nullable',
                'integer',
                'min:1990',
                'max:2030',
            ],
            'gallery' => [
                'nullable',
                'array',
            ],
            'gallery.*' => [
                'nullable',
                'string',
                'max:500',
            ],
            'rental_conditions' => [
                'nullable',
                'array',
            ],
            'extra_fees_info' => [
                'nullable',
                'array',
            ],
            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'          => 'Tên xe không được để trống!',
            'name.max'               => 'Tên xe không được vượt quá 255 ký tự!',
            'price_per_day.required' => 'Giá thuê mỗi ngày là bắt buộc!',
            'price_per_day.numeric'  => 'Giá thuê mỗi ngày phải là định dạng số hợp lệ!',
            'price_per_day.min'      => 'Giá thuê mỗi ngày không được âm!',
            'category_id.exists'     => 'Danh mục xe không tồn tại!',
            'status.in'              => 'Trạng thái xe không hợp lệ (chỉ chấp nhận available, rented, maintenance)!',
        ];
    }
}
