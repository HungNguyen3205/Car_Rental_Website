<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseApiRequest;

class ForgotPasswordRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'max:255',
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
            'email.required' => 'Vui lòng cung cấp địa chỉ email!',
            'email.email'    => 'Địa chỉ email không đúng định dạng!',
            'email.max'      => 'Địa chỉ email không được vượt quá 255 ký tự!',
            'account_type.in'=> 'Loại tài khoản không hợp lệ (chỉ chấp nhận customer hoặc admin)!',
        ];
    }
}
