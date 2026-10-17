<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseApiRequest;

class UserActionIdRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Mã định danh người dùng là bắt buộc!',
            'id.integer'  => 'Mã định danh người dùng phải là số nguyên!',
            'id.exists'   => 'Người dùng không tồn tại trong hệ thống!',
        ];
    }
}
