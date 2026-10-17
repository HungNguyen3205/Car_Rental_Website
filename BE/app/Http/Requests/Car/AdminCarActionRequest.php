<?php

namespace App\Http\Requests\Car;

use App\Http\Requests\BaseApiRequest;

class AdminCarActionRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:cars,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Mã định danh xe là bắt buộc!',
            'id.integer'  => 'Mã định danh xe phải là số nguyên!',
            'id.exists'   => 'Phương tiện không tồn tại trong hệ thống!',
        ];
    }
}
