<?php

namespace App\Http\Requests\Contact;

use App\Http\Requests\BaseApiRequest;

class CreateContactRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'ho_ten'        => ['required', 'string', 'max:100'],
            'email'         => ['required', 'email', 'max:150'],
            'so_dien_thoai' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\s\-().]{8,20}$/'],
            'tieu_de'       => ['required', 'string', 'min:3', 'max:200'],
            'noi_dung'      => ['required', 'string', 'min:5', 'max:3000'],
        ];
    }

    public function messages(): array
    {
        return [
            'ho_ten.required'        => 'Vui lòng nhập họ và tên của bạn!',
            'ho_ten.max'             => 'Họ và tên không được vượt quá 100 ký tự!',
            'email.required'         => 'Vui lòng cung cấp địa chỉ email liên hệ!',
            'email.email'            => 'Địa chỉ email không đúng định dạng hợp lệ!',
            'email.max'              => 'Địa chỉ email không được vượt quá 150 ký tự!',
            'so_dien_thoai.max'      => 'Số điện thoại không được vượt quá 20 ký tự!',
            'so_dien_thoai.regex'    => 'Số điện thoại chứa ký tự không hợp lệ!',
            'tieu_de.required'       => 'Vui lòng nhập tiêu đề yêu cầu liên hệ!',
            'tieu_de.min'            => 'Tiêu đề phải có tối thiểu 3 ký tự!',
            'tieu_de.max'            => 'Tiêu đề không được vượt quá 200 ký tự!',
            'noi_dung.required'      => 'Vui lòng nhập nội dung cần hỗ trợ hoặc phản ánh!',
            'noi_dung.min'           => 'Nội dung phản ánh cần có ít nhất 5 ký tự!',
            'noi_dung.max'           => 'Nội dung phản ánh không được vượt quá 3000 ký tự!',
        ];
    }
}
