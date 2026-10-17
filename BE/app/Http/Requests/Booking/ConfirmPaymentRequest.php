<?php

namespace App\Http\Requests\Booking;

use App\Http\Requests\BaseApiRequest;

class ConfirmPaymentRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:bookings,id',
            ],
            'payment_note' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required'      => 'Mã định danh đơn đặt xe là bắt buộc!',
            'id.exists'        => 'Đơn đặt xe không tồn tại!',
            'payment_note.max' => 'Ghi chú thanh toán không được vượt quá 255 ký tự!',
        ];
    }
}
