<?php

namespace App\Http\Requests\Chatbot;

use App\Http\Requests\BaseApiRequest;
use Carbon\Carbon;

class AssistantBookingRequest extends BaseApiRequest
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
            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'car_id.required'     => 'Vui lòng chọn xe muốn thuê!',
            'car_id.exists'       => 'Xe được chọn không tồn tại trong hệ thống!',
            'start_date.required' => 'Vui lòng chọn ngày nhận xe!',
            'start_date.date'     => 'Thời gian nhận xe không đúng định dạng ngày giờ!',
            'end_date.required'   => 'Vui lòng chọn ngày trả xe!',
            'end_date.date'       => 'Thời gian trả xe không đúng định dạng ngày giờ!',
            'end_date.after'      => 'Thời gian trả xe phải sau thời gian nhận xe!',
            'notes.max'           => 'Ghi chú đặt xe không được vượt quá 500 ký tự!',
        ];
    }
}
