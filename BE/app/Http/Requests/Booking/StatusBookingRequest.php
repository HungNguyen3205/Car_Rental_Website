<?php

namespace App\Http\Requests\Booking;

use App\Http\Requests\BaseApiRequest;

class StatusBookingRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:bookings,id',
            ],
            'tinh_trang' => [
                'nullable',
                'integer',
                'in:0,1,2',
            ],
            'is_thanh_toan' => [
                'nullable',
                'integer',
                'in:0,1',
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
            'id.required'            => 'Mã định danh đơn đặt xe là bắt buộc!',
            'id.exists'              => 'Đơn đặt xe không tồn tại!',
            'tinh_trang.in'          => 'Trạng thái đơn hàng không hợp lệ (chỉ chấp nhận 0: Chờ duyệt, 1: Đã duyệt, 2: Đã hủy)!',
            'is_thanh_toan.in'       => 'Trạng thái thanh toán không hợp lệ (chỉ chấp nhận 0: Chưa thanh toán, 1: Đã thanh toán)!',
            'payment_note.max'       => 'Ghi chú thanh toán không được vượt quá 255 ký tự!',
        ];
    }
}
