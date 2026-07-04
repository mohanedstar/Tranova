<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWeeklyReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'student';
    }

    public function rules(): array
    {
        return [
            'opportunity_id' => 'required|exists:internship_opportunities,id',
            'report_date' => 'required|date',
            'week_number' => 'required|integer|min:1',
            'training_hours' => 'required|numeric|min:0',
            'completed_tasks' => 'required|string',
            'challenges' => 'nullable|string',
            'achievements' => 'nullable|string',
            'next_week_plan' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,doc,docx,jpeg,jpg,png|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'opportunity_id.required' => 'الفرصة مطلوبة',
            'report_date.required' => 'تاريخ التقرير مطلوب',
            'week_number.required' => 'رقم الأسبوع مطلوب',
            'training_hours.required' => 'عدد ساعات التدريب مطلوب',
            'completed_tasks.required' => 'المهام المنجزة مطلوبة',
        ];
    }
}
