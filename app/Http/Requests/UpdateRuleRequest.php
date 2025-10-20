<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'based_on' => 'required|in:current_price,compare_at_price',
            'discount_value' => 'required|numeric|min:0',
            'discount_type' => 'required|in:percent,fixed',
            'applies_to' => 'required|in:products,tags,vendors,collections,whole_store',
            'applies_to_value' => 'nullable|array',
            'applies_to_value.*' => 'string|max:255',
            'status' => 'required|in:active,inactive,archived',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'add_tag' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên rule.',
            'name.string' => 'Tên rule phải là chuỗi.',
            'name.max' => 'Tên rule không được vượt quá 255 ký tự.',

            'based_on.required' => 'Bạn phải chọn loại giá áp dụng.',
            'based_on.in' => 'Giá trị của based_on không hợp lệ.',

            'discount_value.required' => 'Bạn phải nhập giá trị giảm giá.',
            'discount_value.numeric' => 'Giá trị giảm giá phải là số.',
            'discount_value.min' => 'Giá trị giảm giá phải lớn hơn hoặc bằng 0.',

            'discount_type.required' => 'Bạn phải chọn kiểu giảm giá.',
            'discount_type.in' => 'Kiểu giảm giá không hợp lệ.',

            'applies_to.required' => 'Bạn phải chọn đối tượng áp dụng.',
            'applies_to.in' => 'Đối tượng áp dụng không hợp lệ.',

            'applies_to_value.array' => 'Danh sách giá trị áp dụng phải là mảng.',
            'applies_to_value.*.string' => 'Mỗi giá trị áp dụng phải là chuỗi.',
            'applies_to_value.*.max' => 'Mỗi giá trị áp dụng không được vượt quá 255 ký tự.',

            'status.required' => 'Bạn phải chọn trạng thái.',
            'status.in' => 'Trạng thái không hợp lệ.',

            'start_at.date' => 'Ngày bắt đầu không đúng định dạng.',
            'end_at.date' => 'Ngày kết thúc không đúng định dạng.',
            'end_at.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',

            // ✅ Thông báo cho các trường thêm vào
            'add_tag.string' => 'Tag thêm phải là chuỗi.',
            'add_tag.max' => 'Tag thêm không được vượt quá 255 ký tự.',
        ];
    }
}
