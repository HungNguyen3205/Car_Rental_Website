<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseApiRequest;

class ChangePasswordRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'password' => [
                'required',
                'string',
                'min:6',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'Mật khẩu mới là bắt buộc!',
            'password.min'      => 'Mật khẩu mới phải có ít nhất 6 ký tự!',
            'password.max'      => 'Mật khẩu không được vượt quá 100 ký tự!',
        ];
    }
}
