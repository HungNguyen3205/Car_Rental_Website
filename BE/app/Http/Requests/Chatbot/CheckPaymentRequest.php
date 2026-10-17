<?php

namespace App\Http\Requests\Chatbot;

use App\Http\Requests\BaseApiRequest;

class CheckPaymentRequest extends BaseApiRequest
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
            'ma_dat_xe.required' => 'Mã đặt xe là bắt buộc để kiểm tra thanh toán!',
            'ma_dat_xe.max'      => 'Mã đặt xe không được vượt quá 50 ký tự!',
        ];
    }
}
