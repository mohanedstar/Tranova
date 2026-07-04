<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // ✅ لا تحاول إعادة التوجيه أبداً - فقط أرجع null
        // هذا سيمنع Laravel من البحث عن route('login')
        return null;
    }
}
