<?php

namespace App\Http\Requests\Booking;

use App\Http\Requests\BaseApiRequest;

class CancelBookingRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:bookings,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Mã định danh đơn đặt xe là bắt buộc!',
            'id.integer'  => 'Mã định danh đơn đặt xe phải là số nguyên!',
            'id.exists'   => 'Đơn đặt xe không tồn tại trong hệ thống!',
        ];
    }
}
