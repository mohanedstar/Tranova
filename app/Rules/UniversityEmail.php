<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniversityEmail implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // استخراج النطاق من البريد
        $domain = substr(strrchr($value, "@"), 1);

        $allowedDomains = config('universities.allowed_domains', []);

        if (!in_array(strtolower($domain), array_map('strtolower', $allowedDomains))) {
            $fail('يجب استخدام بريد إلكتروني جامعي رسمي. النطاقات المسموحة: ' .
                 implode(', ', $allowedDomains));
        }
    }
}
