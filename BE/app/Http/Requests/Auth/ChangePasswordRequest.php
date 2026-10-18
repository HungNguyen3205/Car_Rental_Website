<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'current_password' => [
                'nullable',
                'string',
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->numbers(),
            ],
            'password_confirmation' => [
                'required',
                'string',
            ],
            'revoke_other_sessions' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required'              => 'Mật khẩu mới là bắt buộc!',
            'password.min'                   => 'Mật khẩu mới phải có ít nhất 8 ký tự và bao gồm cả chữ và số!',
            'password.confirmed'             => 'Xác nhận mật khẩu mới không khớp!',
            'password_confirmation.required' => 'Vui lòng xác nhận mật khẩu mới!',
        ];
    }
}
