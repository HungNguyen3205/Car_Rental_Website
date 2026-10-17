<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseApiRequest;

class AdminCustomerSearchRequest extends BaseApiRequest
{
    public function rules(): array
    {
        return [
            'q'        => 'nullable|string|max:100',
            'query'    => 'nullable|string|max:100',
            'page'     => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'per_page.max' => 'Số lượng kết quả mỗi trang tối đa là 50!',
        ];
    }
}
