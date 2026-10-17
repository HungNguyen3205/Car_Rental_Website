<?php

namespace App\Http\Requests\Booking;

use App\Http\Requests\BaseApiRequest;

class UpdateBookingDatesRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'start_date' => [
                'required',
                'date',
            ],
            'end_date' => [
                'required',
                'date',
                'after:start_date',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.required' => 'Vui lòng chọn thời gian nhận xe!',
            'start_date.date'     => 'Thời gian nhận xe không hợp lệ!',
            'end_date.required'   => 'Vui lòng chọn thời gian trả xe!',
            'end_date.date'       => 'Thời gian trả xe không hợp lệ!',
            'end_date.after'      => 'Thời gian trả xe phải diễn ra sau thời gian nhận xe!',
        ];
    }
}
