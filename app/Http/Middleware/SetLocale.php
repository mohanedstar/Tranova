<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        // ✅ في بيئة الاختبار، استخدم APP_LOCALE فقط
        if (App::environment('testing')) {
            App::setLocale(config('app.locale', 'ar'));
            return $next($request);
        }

        $locale = $this->determineLocale($request);

        Log::info('SetLocale Middleware', [
            'locale' => $locale,
            'user_id' => $request->user()?->id,
            'preferred_language' => $request->user()?->preferred_language,
        ]);

        App::setLocale($locale);

        return $next($request);
    }

    protected function determineLocale(Request $request): string
    {
        // ✅ الأولوية 1: Query Parameter (?lang=ar)
        if ($request->has('lang')) {
            $lang = $request->input('lang');
            if ($this->isValidLocale($lang)) {
                return $lang;
            }
        }

        // ✅ الأولوية 2: Header (X-Language)
        if ($request->hasHeader('X-Language')) {
            $lang = $request->header('X-Language');
            if ($this->isValidLocale($lang)) {
                return $lang;
            }
        }

        // ✅ الأولوية 3: تفضيل المستخدم
        $user = $request->user();
        if ($user && !empty($user->preferred_language)) {
            if ($this->isValidLocale($user->preferred_language)) {
                return $user->preferred_language;
            }
        }

        // ✅ الأولوية 4: Accept-Language Header
        if ($request->hasHeader('Accept-Language')) {
            $acceptLang = $request->header('Accept-Language');
            $lang = $this->parseAcceptLanguage($acceptLang);
            if ($lang && $this->isValidLocale($lang)) {
                return $lang;
            }
        }

        // ✅ الأولوية 5: اللغة الافتراضية
        return config('app.locale', 'ar');
    }

    protected function isValidLocale(string $locale): bool
    {
        $supportedLocales = config('app.supported_locales', ['ar', 'en']);
        return in_array($locale, (array) $supportedLocales);
    }

    protected function parseAcceptLanguage(string $header): ?string
    {
        $languages = explode(',', $header);

        foreach ($languages as $lang) {
            $lang = trim(explode(';', $lang)[0]);
            $lang = substr($lang, 0, 2);

            if ($this->isValidLocale($lang)) {
                return $lang;
            }
        }

        return null;
    }
}
