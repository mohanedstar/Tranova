<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOpportunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'provider';
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'requirements' => 'nullable|string',
            'required_major' => 'required|string',
            'required_skills' => 'nullable|array',
            'available_positions' => 'required|integer|min:1',
            'location' => 'required|string',
            'is_remote' => 'boolean',
            'duration_months' => 'required|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'application_deadline' => 'required|date|after:today',
            'salary' => 'nullable|numeric|min:0',
            'is_paid' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'عنوان الفرصة مطلوب',
            'description.required' => 'الوصف مطلوب',
            'required_major.required' => 'التخصص المطلوب مطلوب',
            'available_positions.required' => 'عدد الوظائف المتاحة مطلوب',
            'application_deadline.required' => 'موعد انتهاء التقديم مطلوب',
            'application_deadline.after' => 'يجب أن يكون موعد الانتهاء في المستقبل',
        ];
    }
}
