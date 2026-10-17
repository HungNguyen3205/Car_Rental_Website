<?php

namespace App\Http\Requests\Booking;

use App\Http\Requests\BaseApiRequest;
use Carbon\Carbon;

class CreateBookingRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $timezone = config('app.timezone', 'UTC');

        return [
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
                        // Cho phép sai số 5 phút do độ trễ truyền gói tin mạng
                        $threshold = Carbon::now($timezone)->subMinutes(5);
                        if ($start->lessThan($threshold)) {
                            $fail('Thời gian nhận xe không được ở trong quá khứ theo múi giờ ứng dụng!');
                        }
                    } catch (\Exception $e) {
                        $fail('Định dạng thời gian nhận xe không hợp lệ!');
                    }
                },
            ],
            'end_date' => [
                'required',
                'date',
                'after:start_date',
            ],
            'dia_chi_nhan_xe' => ['nullable', 'string', 'max:255'],
            'dia_chi_tra_xe'  => ['nullable', 'string', 'max:255'],
            'ly_do_thue'      => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'car_id.required'    => 'Vui lòng chọn xe muốn thuê!',
            'car_id.integer'     => 'Mã định danh xe không hợp lệ!',
            'car_id.exists'      => 'Xe được chọn không tồn tại trong hệ thống!',
            'start_date.required'=> 'Vui lòng chọn thời gian nhận xe!',
            'start_date.date'    => 'Thời gian nhận xe phải là định dạng ngày giờ hợp lệ!',
            'end_date.required'  => 'Vui lòng chọn thời gian trả xe!',
            'end_date.date'      => 'Thời gian trả xe phải là định dạng ngày giờ hợp lệ!',
            'end_date.after'     => 'Thời gian trả xe phải diễn ra sau thời gian nhận xe!',
            'dia_chi_nhan_xe.max'=> 'Địa chỉ nhận xe không được vượt quá 255 ký tự!',
            'dia_chi_tra_xe.max' => 'Địa chỉ trả xe không được vượt quá 255 ký tự!',
            'ly_do_thue.max'     => 'Ghi chú lý do thuê không được vượt quá 500 ký tự!',
        ];
    }
}
