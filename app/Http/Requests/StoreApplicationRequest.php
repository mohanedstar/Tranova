<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'student';
    }

    public function rules(): array
    {
        return [
            'cover_letter' => 'nullable|string',
            'cv' => 'required|file|mimes:pdf,doc,docx,jpeg,jpg,png|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'cv.required' => 'يجب رفع السيرة الذاتية',
            'cv.file' => 'يجب أن يكون الملف ملفاً',
            'cv.mimes' => 'يجب أن يكون الملف PDF أو Word أو صورة',
            'cv.max' => 'يجب ألا يتجاوز حجم الملف 5 ميجابايت',
        ];
    }
}
