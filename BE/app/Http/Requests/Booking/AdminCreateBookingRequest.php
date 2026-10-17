<?php

namespace App\Http\Requests\Booking;

use App\Http\Requests\BaseApiRequest;
use Carbon\Carbon;

class AdminCreateBookingRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $timezone = config('app.timezone', 'UTC');

        return [
            'customer_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'car_id' => [
                'required',
                'integer',
                'exists:cars,id',
            ],
            'start_date' => [
                'required',
                'date',
                function ($attribute, $value, $fail) use ($timezone) {
                    try {
                        $start = Carbon::parse($value, $timezone);
                        $threshold = Carbon::now($timezone)->subMinutes(5);
                        if ($start->lessThan($threshold)) {
                            $fail('Thời gian nhận xe không được ở trong quá khứ theo múi giờ ứng dụng!');
                        }
                    } catch (\Exception $e) {
                        $fail('Thời gian nhận xe không hợp lệ!');
                    }
                },
            ],
            'end_date' => [
                'required',
                'date',
                'after:start_date',
            ],
            'dia_chi_nhan_xe' => [
                'nullable',
                'string',
                'max:255',
            ],
            'dia_chi_tra_xe' => [
                'nullable',
                'string',
                'max:255',
            ],
            'ly_do_thue' => [
                'nullable',
                'string',
                'max:500',
            ],
            'is_thanh_toan' => [
                'nullable',
                'integer',
                'in:0,1',
            ],
            'tinh_trang' => [
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
            'customer_id.required' => 'Vui lòng chọn khách hàng thuê xe!',
            'customer_id.integer'  => 'Mã định danh khách hàng phải là số nguyên!',
            'customer_id.exists'   => 'Khách hàng được chọn không tồn tại trong hệ thống!',
            'car_id.required'      => 'Vui lòng chọn xe cho thuê!',
            'car_id.integer'       => 'Mã định danh xe phải là số nguyên!',
            'car_id.exists'        => 'Xe được chọn không tồn tại trong hệ thống!',
            'start_date.required'  => 'Thời gian nhận xe là bắt buộc!',
            'start_date.date'      => 'Thời gian nhận xe không đúng định dạng ngày giờ!',
            'end_date.required'    => 'Thời gian trả xe là bắt buộc!',
            'end_date.date'        => 'Thời gian trả xe không đúng định dạng ngày giờ!',
            'end_date.after'       => 'Thời gian trả xe phải sau thời gian nhận xe!',
            'dia_chi_nhan_xe.max'  => 'Địa chỉ nhận xe không được vượt quá 255 ký tự!',
            'dia_chi_tra_xe.max'   => 'Địa chỉ trả xe không được vượt quá 255 ký tự!',
            'ly_do_thue.max'       => 'Ghi chú / lý do thuê không được vượt quá 500 ký tự!',
            'is_thanh_toan.in'     => 'Trạng thái thanh toán không hợp lệ (0: Chưa thanh toán, 1: Đã thanh toán)!',
            'tinh_trang.in'        => 'Trạng thái đơn hàng không hợp lệ (0: Chờ duyệt, 1: Đã duyệt)!',
            'payment_note.max'     => 'Ghi chú thanh toán không được vượt quá 255 ký tự!',
        ];
    }
}
