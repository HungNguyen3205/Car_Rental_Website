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
                'required',
                'integer',
                'in:0,1,2,3,4,5',
            ],
            'cancel_reason' => [
                'nullable',
                'string',
                'max:1000',
                'required_if:tinh_trang,2',
            ],
            'status_note' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'is_thanh_toan' => [
                'prohibited',
            ],
            'payment_note' => [
                'prohibited',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required'              => 'Mã định danh đơn đặt xe là bắt buộc!',
            'id.exists'                => 'Đơn đặt xe không tồn tại!',
            'tinh_trang.required'      => 'Trạng thái đơn thuê xe là bắt buộc!',
            'tinh_trang.in'            => 'Trạng thái đơn thuê xe không hợp lệ (hỗ trợ: 0: Chờ duyệt, 1: Đã duyệt, 2: Đã hủy, 3: Đang thuê, 4: Đã nhận trả, 5: Hoàn tất)!',
            'cancel_reason.required_if'=> 'Vui lòng cung cấp lý do hủy đơn đặt xe!',
            'cancel_reason.max'        => 'Lý do hủy đơn không được vượt quá 1000 ký tự!',
            'status_note.max'          => 'Ghi chú nghiệp vụ không được vượt quá 1000 ký tự!',
            'is_thanh_toan.prohibited' => 'Endpoint cập nhật trạng thái đơn thuê không được phép thay đổi trạng thái tài chính! Vui lòng dùng tính năng Xác nhận thanh toán chuyên dụng.',
            'payment_note.prohibited'  => 'Endpoint cập nhật trạng thái đơn thuê không nhận thông tin thanh toán!',
        ];
    }
}
