<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // عام للجميع
    }

    public function rules(): array
    {
        return [
            'token' => 'required|string',
            'email' => 'required|email',
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'التوكن مطلوب',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صالح',
        ];
    }
}
