<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $provider;
    protected $apiKey;
    protected $model;

    public function __construct()
    {
        // تحديد المزود: groq أو gemini
        $this->provider = env('AI_PROVIDER', 'groq');

        if ($this->provider === 'groq') {
            $this->apiKey = env('GROQ_API_KEY');
            $this->model = env('GROQ_MODEL', 'llama-3.3-70b-versatile');
        } else {
            $this->apiKey = env('GEMINI_API_KEY');
            $this->model = env('GEMINI_MODEL', 'gemini-2.0-flash');
        }

        Log::info('AI Service Initialized', [
            'provider' => $this->provider,
            'model' => $this->model,
            'api_key_exists' => !empty($this->apiKey),
        ]);
    }

    /**
     * توليد نص باستخدام AI
     */
    public function generateText(string $prompt): ?string
    {
        if (empty($this->apiKey)) {
            Log::error('AI API Key is missing in .env file');
            throw new \Exception('مفتاح API غير موجود. تحقق من ملف .env');
        }

        try {
            if ($this->provider === 'groq') {
                return $this->callGroq($prompt);
            } else {
                return $this->callGemini($prompt);
            }
        } catch (\Exception $e) {
            Log::error('AI Service Error: ' . $e->getMessage(), [
                'provider' => $this->provider,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * استدعاء Groq API
     */
    protected function callGroq(string $prompt): ?string
    {
        Log::info('Calling Groq API', [
            'model' => $this->model,
            'prompt_length' => strlen($prompt),
        ]);

        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'أنت مساعد ذكي متخصص في تحسين التقارير الأكاديمية والتدريبية للطلاب الجامعيين. تتحدث العربية بطلاقة وتفهم السياق الأكاديمي والمهني.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 1024,
                'top_p' => 1,
            ]);

        if ($response->successful()) {
            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;

            Log::info('Groq API Success', [
                'model' => $this->model,
                'response_length' => strlen($content ?? ''),
            ]);

            return $content;
        }

        Log::error('Groq API Error', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        throw new \Exception('فشل الاتصال بخدمة Groq: ' . $response->status());
    }

    /**
     * استدعاء Gemini API (احتياطي)
     */
    protected function callGemini(string $prompt): ?string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        $response = Http::timeout(30)
            ->post($url, [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 1024,
                ]
            ]);

        if ($response->successful()) {
            return $response->json('candidates.0.content.parts.0.text');
        }

        Log::error('Gemini API Error', ['body' => $response->body()]);
        throw new \Exception('فشل الاتصال بخدمة Gemini');
    }
}
