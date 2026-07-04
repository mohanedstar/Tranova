<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchOpportunitiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // متاح للجميع
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'major' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'is_remote' => 'nullable|in:0,1,true,false',
            'is_paid' =>  'nullable|in:0,1,true,false',
            'min_salary' => 'nullable|numeric|min:0',
            'max_salary' => 'nullable|numeric|min:0|gte:min_salary',
            'sort_by' => 'nullable|in:created_at,application_deadline,salary',
            'sort_order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'max_salary.gte' => 'الحد الأقصى للراتب يجب أن يكون أكبر من أو يساوي الحد الأدنى',
            'sort_by.in' => 'قيمة الترتيب غير صالحة',
            'sort_order.in' => 'اتجاه الترتيب غير صالح',
            'per_page.max' => 'عدد النتائج في الصفحة لا يمكن أن يتجاوز 100',
        ];
    }
}
