<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class RecordTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'             => 'required|string|in:rental_payment,rental_refund,deposit_collection,deposit_refund,deposit_deduction',
            'amount'           => 'required|integer|min:1',
            'method'           => 'nullable|string|max:50',
            'reference_code'   => 'nullable|string|max:100',
            'transaction_time' => 'nullable|date',
            'note'             => 'nullable|string|max:1000',
            'deduction_reason' => 'nullable|required_if:type,deposit_deduction|string|max:1000',
            'idempotency_key'  => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required'                 => 'Vui lòng chọn loại giao dịch!',
            'type.in'                       => 'Loại giao dịch không hợp lệ!',
            'amount.required'               => 'Vui lòng nhập số tiền giao dịch!',
            'amount.integer'                => 'Số tiền giao dịch phải là số nguyên VND!',
            'amount.min'                    => 'Số tiền giao dịch phải lớn hơn 0!',
            'deduction_reason.required_if'  => 'Vui lòng cung cấp lý do khấu trừ tiền cọc!',
        ];
    }
}
