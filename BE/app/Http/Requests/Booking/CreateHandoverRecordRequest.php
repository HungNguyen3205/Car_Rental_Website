<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class CreateHandoverRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'                         => 'required|string|in:delivery,return',
            'current_km'                   => 'required|integer|min:0',
            'fuel_or_battery_level'        => 'required|integer|min:0|max:100',
            'fuel_type'                    => 'nullable|string|in:electric,gasoline,diesel',
            'handover_time'                => 'nullable|date',
            'exterior_status'              => 'nullable|string|max:2000',
            'interior_status'              => 'nullable|string|max:2000',
            'checklist_items'              => 'nullable|array',
            'notes'                        => 'nullable|string|max:2000',
            'is_odometer_adjusted'         => 'nullable|boolean',
            'odometer_adjustment_reason'   => 'nullable|required_if:is_odometer_adjusted,true|string|max:1000',
            'customer_confirmed'           => 'nullable|boolean',
            'customer_confirmation_method' => 'nullable|string|max:64',
            'customer_notes'               => 'nullable|string|max:1000',
            'images'                       => 'nullable|array',
            'images.*'                     => 'file|image|mimes:jpeg,png,jpg,webp|max:5120',
            'surcharges'                   => 'nullable|array',
            'surcharges.*.type'            => 'nullable|string|in:late_return,excess_km,cleaning,fuel_difference,damage,other',
            'surcharges.*.amount'          => 'nullable|integer|min:1',
            'surcharges.*.reason'          => 'nullable|string|max:1000',
            'surcharges.*.is_deducted_from_deposit' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required'                          => 'Vui lòng chọn loại biên bản (bàn giao hoặc nhận trả)!',
            'type.in'                                => 'Loại biên bản không hợp lệ!',
            'current_km.required'                    => 'Vui lòng nhập số km hiện tại trên đồng hồ xe!',
            'current_km.integer'                     => 'Số km phải là số nguyên!',
            'current_km.min'                         => 'Số km không được nhỏ hơn 0!',
            'fuel_or_battery_level.required'         => 'Vui lòng nhập mức pin hoặc nhiên liệu!',
            'fuel_or_battery_level.min'              => 'Mức pin/nhiên liệu tối thiểu là 0%!',
            'fuel_or_battery_level.max'              => 'Mức pin/nhiên liệu tối đa là 100%!',
            'odometer_adjustment_reason.required_if' => 'Vui lòng nêu rõ lý do điều chỉnh số km đồng hồ!',
            'images.*.max'                           => 'Mỗi ảnh tải lên không được vượt quá 5MB!',
            'images.*.mimes'                         => 'Chỉ chấp nhận các định dạng ảnh: JPEG, PNG, JPG, WEBP!',
        ];
    }
}
