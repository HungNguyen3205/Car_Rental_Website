<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'max:255',
            ],
            'token' => [
                'required',
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
            'account_type' => [
                'nullable',
                'string',
                'in:customer,admin',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'                 => 'Địa chỉ email là bắt buộc!',
            'email.email'                    => 'Địa chỉ email không đúng định dạng!',
            'token.required'                 => 'Mã token đặt lại mật khẩu là bắt buộc!',
            'password.required'              => 'Mật khẩu mới là bắt buộc!',
            'password.min'                   => 'Mật khẩu phải có ít nhất 8 ký tự và bao gồm cả chữ và số!',
            'password.confirmed'             => 'Xác nhận mật khẩu mới không khớp!',
            'password_confirmation.required' => 'Vui lòng xác nhận mật khẩu mới!',
            'account_type.in'                => 'Loại tài khoản không hợp lệ (chỉ chấp nhận customer hoặc admin)!',
        ];
    }
}
