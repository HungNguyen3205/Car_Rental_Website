<?php

namespace App\Http\Requests\Booking;

use App\Http\Requests\BaseApiRequest;

class LookupBookingCodeRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'ma_dat_xe' => [
                'required',
                'string',
                'max:50',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'ma_dat_xe.required' => 'Vui lòng cung cấp mã đơn đặt xe cần tra cứu!',
            'ma_dat_xe.max'      => 'Mã đơn đặt xe không được vượt quá 50 ký tự!',
        ];
    }
}
