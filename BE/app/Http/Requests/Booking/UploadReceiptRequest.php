<?php

namespace App\Http\Requests\Booking;

use App\Http\Requests\BaseApiRequest;

class UploadReceiptRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'booking_id' => [
                'required',
                'integer',
                'exists:bookings,id',
            ],
            'bill_image' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:5120', // Tối đa 5MB
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'booking_id.required' => 'Mã định danh đơn đặt xe là bắt buộc!',
            'booking_id.integer'  => 'Mã định danh đơn đặt xe không hợp lệ!',
            'booking_id.exists'   => 'Đơn đặt xe không tồn tại trong hệ thống!',
            'bill_image.required' => 'Vui lòng chọn tệp ảnh biên lai chuyển khoản!',
            'bill_image.file'     => 'Dữ liệu tải lên phải là một tệp tin hợp lệ!',
            'bill_image.image'    => 'Tệp tải lên phải là định dạng hình ảnh!',
            'bill_image.mimes'    => 'Biên lai chỉ chấp nhận các định dạng ảnh: jpeg, png, jpg, webp!',
            'bill_image.max'      => 'Dung lượng ảnh biên lai không được vượt quá 5MB!',
        ];
    }
}
