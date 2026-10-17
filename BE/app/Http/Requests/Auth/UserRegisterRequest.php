<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseApiRequest;

class UserRegisterRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'ho_ten' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'string',
                'min:6',
                'max:100',
            ],
            'so_dien_thoai' => [
                'required',
                'string',
                'regex:/^(0|\+84)[0-9]{9,10}$/',
            ],
            'ngay_sinh' => [
                'nullable',
                'date',
            ],
            'gioi_tinh' => [
                'nullable',
                'integer',
                'in:0,1,2',
            ],
            'dia_chi' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'ho_ten.required'        => 'Họ và tên là bắt buộc!',
            'email.required'         => 'Email là bắt buộc!',
            'email.email'            => 'Định dạng email không hợp lệ!',
            'email.unique'           => 'Email này đã tồn tại trên hệ thống!',
            'password.required'      => 'Mật khẩu là bắt buộc!',
            'password.min'           => 'Mật khẩu phải có ít nhất 6 ký tự!',
            'so_dien_thoai.required' => 'Số điện thoại là bắt buộc!',
            'so_dien_thoai.regex'    => 'Số điện thoại phải là định dạng hợp lệ (10-11 chữ số, bắt đầu bằng 0 hoặc +84)!',
            'ngay_sinh.date'         => 'Ngày sinh không đúng định dạng ngày hợp lệ!',
            'gioi_tinh.in'           => 'Giới tính không hợp lệ!',
            'dia_chi.max'            => 'Địa chỉ không được vượt quá 255 ký tự!',
        ];
    }
}
