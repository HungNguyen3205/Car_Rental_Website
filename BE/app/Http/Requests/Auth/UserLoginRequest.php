<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseApiRequest;

class UserLoginRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'max:255',
            ],
            'password' => [
                'required',
                'string',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'Email đăng nhập là bắt buộc!',
            'email.email'       => 'Định dạng email không hợp lệ!',
            'password.required' => 'Mật khẩu là bắt buộc!',
        ];
    }
}
