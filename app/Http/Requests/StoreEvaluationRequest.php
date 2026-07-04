<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEvaluationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // السماح للمزودين والمشرفين فقط
        return in_array($this->user()->role, ['provider', 'supervisor']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_id' => 'required|exists:students,id',
            'opportunity_id' => 'required|exists:internship_opportunities,id',
            'evaluator_type' => 'required|in:provider,supervisor',
            'attendance_grade' => 'nullable|numeric|min:0|max:100',
            'commitment_grade' => 'nullable|numeric|min:0|max:100',
            'technical_skills_grade' => 'nullable|numeric|min:0|max:100',
            'teamwork_grade' => 'nullable|numeric|min:0|max:100',
            'communication_grade' => 'nullable|numeric|min:0|max:100',
            'evaluation_notes' => 'nullable|string|max:1000',
            'strengths' => 'nullable|string|max:1000',
            'areas_for_improvement' => 'nullable|string|max:1000',
            'is_final' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'student_id.required' => 'معرف الطالب مطلوب',
            'student_id.exists' => 'الطالب غير موجود',
            'opportunity_id.required' => 'معرف الفرصة مطلوب',
            'opportunity_id.exists' => 'الفرصة غير موجودة',
            'evaluator_type.required' => 'نوع المقيّم مطلوب',
            'evaluator_type.in' => 'نوع المقيّم يجب أن يكون provider أو supervisor',
            'attendance_grade.numeric' => 'درجة الحضور يجب أن تكون رقماً',
            'attendance_grade.min' => 'درجة الحضور يجب أن تكون على الأقل 0',
            'attendance_grade.max' => 'درجة الحضور يجب ألا تتجاوز 100',
            'commitment_grade.numeric' => 'درجة الالتزام يجب أن تكون رقماً',
            'technical_skills_grade.numeric' => 'درجة المهارات التقنية يجب أن تكون رقماً',
            'teamwork_grade.numeric' => 'درجة العمل الجماعي يجب أن تكون رقماً',
            'communication_grade.numeric' => 'درجة التواصل يجب أن تكون رقماً',
            'evaluation_notes.max' => 'ملاحظات التقييم يجب ألا تتجاوز 1000 حرف',
            'strengths.max' => 'نقاط القوة يجب ألا تتجاوز 1000 حرف',
            'areas_for_improvement.max' => 'مجالات التحسين يجب ألا تتجاوز 1000 حرف',
        ];
    }
}
