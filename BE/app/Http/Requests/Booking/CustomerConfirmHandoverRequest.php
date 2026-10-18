<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class CustomerConfirmHandoverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'   => 'required|string|in:delivery,return',
            'notes'  => 'nullable|string|max:1000',
            'method' => 'nullable|string|max:64',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Vui lòng xác định loại biên bản cần xác nhận (delivery hoặc return)!',
            'type.in'       => 'Loại biên bản không hợp lệ!',
        ];
    }
}
