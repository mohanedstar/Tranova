<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AIReportController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * كشف لغة النص (عربي أو إنجليزي)
     */
    protected function detectLanguage(string $text): string
    {
        $arabicChars = preg_match_all('/[\p{Arabic}]/u', $text);
        $totalChars = mb_strlen($text);

        if ($totalChars > 0 && ($arabicChars / $totalChars) > 0.3) {
            return 'arabic';
        }

        return 'english';
    }

    /**
     * حساب إحصائيات النص
     */
    protected function calculateTextStats(string $text, string $language): array
    {
        $words = str_word_count($text);
        $sentences = preg_split('/[.!?؟。]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $paragraphs = preg_split('/\n\s*\n/', $text, -1, PREG_SPLIT_NO_EMPTY);

        $charCount = mb_strlen($text);
        $charCountNoSpaces = mb_strlen(str_replace(' ', '', $text));

        $avgSentenceLength = count($sentences) > 0 ? round($words / count($sentences), 1) : 0;

        return [
            'word_count' => $words,
            'sentence_count' => count($sentences),
            'paragraph_count' => max(count($paragraphs), 1),
            'character_count' => $charCount,
            'character_count_no_spaces' => $charCountNoSpaces,
            'average_sentence_length' => $avgSentenceLength,
            'estimated_reading_time_minutes' => max(1, round($words / 200)),
        ];
    }

    /**
     * ========================================
     * الميزة A: تحسين التقرير (AI Report Improver)
     * ========================================
     * ✅ يعمل للطالب والمشرف
     */
    public function improveReport(Request $request)
    {
        $request->validate([
            'content' => 'required|string|min:10|max:2000',
        ], [
            'content.required' => __('messages.validation.content_required'),
            'content.min' => __('messages.validation.content_min'),
        ]);

        $originalContent = $request->input('content');

        $detectedLanguage = $this->detectLanguage($originalContent);
        $languageName = $detectedLanguage === 'arabic' ? 'Arabic' : 'English';

        Log::info('Detected language for AI improvement', [
            'language' => $detectedLanguage,
            'content_preview' => substr($originalContent, 0, 50),
            'user_role' => $request->user()->role, // ✅ Log للدور
        ]);

        $prompt = "You are a smart and professional assistant specialized in improving weekly internship training reports for university students.

Your task is to rewrite the following report to make it more professional, academic, and detailed.

CRITICAL RULES:
1. You MUST respond in the SAME LANGUAGE as the original text ({$languageName}).
2. If the original text is in English, your entire response MUST be in English.
3. If the original text is in Arabic, your entire response MUST be in Arabic.
4. Do NOT mix languages.
5. Do NOT add any introductory phrases like 'Here is the improved version' or 'إليك النسخة المحسنة'.
6. Return ONLY the improved text, nothing else.

IMPROVEMENT GUIDELINES:
- Correct any grammatical or spelling errors.
- Add appropriate technical and professional terminology based on the context.
- Make the text coherent and organized in clear paragraphs.
- Do NOT invent tasks that the student did not mention.
- Maintain the original meaning and facts.
- Expand the content naturally with relevant professional details.

Original student text:
\"{$originalContent}\"

Improved text (in {$languageName}):";

        try {
            $improvedContent = $this->geminiService->generateText($prompt);

            if (!$improvedContent) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.ai.failed_improve')
                ], 500);
            }

            $improvedContent = trim(str_replace(['```markdown', '```', '```text'], '', $improvedContent));

            return response()->json([
                'success' => true,
                'message' => __('messages.ai.improve_success'),
                'data' => [
                    'original_content' => $originalContent,
                    'improved_content' => $improvedContent,
                    'detected_language' => $detectedLanguage,
                    'original_word_count' => str_word_count($originalContent),
                    'improved_word_count' => str_word_count($improvedContent),
                    'ai_model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('AI Report Improvement Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('messages.ai.connection_error_prefix') . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ========================================
     * الميزة B: تحليل التقرير (AI Report Analyzer)
     * ========================================
     * ✅ يعمل للطالب والمشرف
     */
    public function analyzeReport(Request $request)
    {
        $request->validate([
            'content' => 'required|string|min:10|max:5000',
        ], [
            'content.required' => __('messages.validation.content_required'),
            'content.min' => __('messages.validation.content_min'),
        ]);

        $content = $request->input('content');
        $detectedLanguage = $this->detectLanguage($content);
        $languageName = $detectedLanguage === 'arabic' ? 'Arabic' : 'English';

        Log::info('Analyzing report with AI', [
            'language' => $detectedLanguage,
            'content_length' => strlen($content),
            'user_role' => $request->user()->role, // ✅ Log للدور
        ]);

        $prompt = "You are an expert academic evaluator specialized in assessing internship training reports for university students.

Your task is to analyze the following student report and provide a comprehensive evaluation.

CRITICAL RULES:
1. You MUST respond in the SAME LANGUAGE as the original text ({$languageName}).
2. If the original text is in English, your entire response MUST be in English.
3. If the original text is in Arabic, your entire response MUST be in Arabic.
4. Do NOT mix languages.

EVALUATION CRITERIA:
1. Content Quality (40%): Relevance, depth, technical accuracy
2. Structure & Organization (20%): Logical flow, paragraph structure
3. Language & Grammar (20%): Grammar, spelling, vocabulary
4. Professionalism (20%): Technical terminology, formal tone

RESPONSE FORMAT (JSON ONLY - no other text):
{
    \"quality_score\": <number between 0-100>,
    \"grade\": \"<excellent|good|average|poor>\",
    \"strengths\": [\"<strength 1>\", \"<strength 2>\", \"<strength 3>\"],
    \"weaknesses\": [\"<weakness 1>\", \"<weakness 2>\", \"<weakness 3>\"],
    \"improvements\": [\"<suggestion 1>\", \"<suggestion 2>\", \"<suggestion 3>\"],
    \"detailed_feedback\": \"<2-3 sentences overall feedback>\",
    \"criteria_scores\": {
        \"content_quality\": <0-100>,
        \"structure\": <0-100>,
        \"language\": <0-100>,
        \"professionalism\": <0-100>
    }
}

Student report to analyze:
\"{$content}\"

Respond with ONLY the JSON object, no other text.";

        try {
            $aiResponse = $this->geminiService->generateText($prompt);

            if (!$aiResponse) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.ai.failed_analyze')
                ], 500);
            }

            $cleanResponse = trim(str_replace(['```json', '```', '```text'], '', $aiResponse));
            $analysis = json_decode($cleanResponse, true);

            if (!$analysis) {
                Log::error('Failed to parse AI analysis response', [
                    'response' => $cleanResponse,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => __('messages.ai.analyze_default'),
                    'data' => [
                        'quality_score' => 70,
                        'grade' => 'good',
                        'strengths' => $detectedLanguage === 'arabic'
                            ? [__('messages.ai.default_strength_content'), __('messages.ai.default_strength_structure')]
                            : ['Good content', 'Clear organization'],
                        'weaknesses' => $detectedLanguage === 'arabic'
                            ? [__('messages.ai.default_weakness_details')]
                            : ['Needs more details'],
                        'improvements' => $detectedLanguage === 'arabic'
                            ? [__('messages.ai.default_improvement_examples'), __('messages.ai.default_improvement_terms')]
                            : ['Add practical examples', 'Use technical terms'],
                        'detailed_feedback' => $detectedLanguage === 'arabic'
                            ? __('messages.ai.default_feedback')
                            : 'The report is generally good but can be improved by adding more technical details and practical examples.',
                        'criteria_scores' => [
                            'content_quality' => 70,
                            'structure' => 70,
                            'language' => 70,
                            'professionalism' => 70,
                        ],
                        'statistics' => $this->calculateTextStats($content, $detectedLanguage),
                        'detected_language' => $detectedLanguage,
                        'ai_model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
                    ]
                ]);
            }

            $stats = $this->calculateTextStats($content, $detectedLanguage);

            return response()->json([
                'success' => true,
                'message' => __('messages.ai.analyze_success'),
                'data' => [
                    'quality_score' => $analysis['quality_score'] ?? 70,
                    'grade' => $analysis['grade'] ?? 'good',
                    'strengths' => $analysis['strengths'] ?? [],
                    'weaknesses' => $analysis['weaknesses'] ?? [],
                    'improvements' => $analysis['improvements'] ?? [],
                    'detailed_feedback' => $analysis['detailed_feedback'] ?? '',
                    'criteria_scores' => $analysis['criteria_scores'] ?? [
                        'content_quality' => 70,
                        'structure' => 70,
                        'language' => 70,
                        'professionalism' => 70,
                    ],
                    'statistics' => $stats,
                    'detected_language' => $detectedLanguage,
                    'ai_model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('AI Report Analysis Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('messages.ai.connection_error_prefix') . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ========================================
     * الميزة C: توليد تقرير كامل (AI Report Generator)
     * ========================================
     * ✅ يعمل للطالب والمشرف
     */
    public function generateReport(Request $request)
    {
        $request->validate([
            'points' => 'required|array|min:2|max:20',
            'points.*' => 'required|string|min:3|max:200',
            'context' => 'nullable|string|max:500',
        ], [
            'points.required' => __('messages.validation.points_required'),
            'points.min' => __('messages.validation.points_min'),
            'points.max' => __('messages.validation.points_max'),
            'points.*.required' => __('messages.validation.point_required'),
            'points.*.min' => __('messages.validation.point_min'),
        ]);

        $points = $request->input('points');
        $context = $request->input('context', '');

        $combinedText = implode(' ', $points);
        $detectedLanguage = $this->detectLanguage($combinedText);
        $languageName = $detectedLanguage === 'arabic' ? 'Arabic' : 'English';

        Log::info('Generating report from points with AI', [
            'language' => $detectedLanguage,
            'points_count' => count($points),
            'has_context' => !empty($context),
            'user_role' => $request->user()->role, // ✅ Log للدور
        ]);

        $formattedPoints = '';
        foreach ($points as $index => $point) {
            $formattedPoints .= ($index + 1) . '. ' . $point . "\n";
        }

        $contextSection = '';
        if (!empty($context)) {
            $contextSection = "ADDITIONAL CONTEXT:\n{$context}\n\n";
        }

        $prompt = "You are an expert academic writer specialized in creating professional internship training reports for university students.

Your task is to generate a complete, professional weekly internship report based on the bullet points provided by the student.

CRITICAL RULES:
1. You MUST write the entire report in {$languageName}.
2. If the points are in English, the entire report MUST be in English.
3. If the points are in Arabic, the entire report MUST be in Arabic.
4. Do NOT mix languages.
5. Do NOT add any introductory phrases like 'Here is the report' or 'إليك التقرير'.
6. Return ONLY the report content, nothing else.

REPORT STRUCTURE:
1. Introduction paragraph (2-3 sentences about the week's focus)
2. Main body with detailed description of tasks (organized in paragraphs)
3. Challenges faced and how they were overcome (if applicable)
4. Skills developed and learning outcomes
5. Brief conclusion about the week's progress

WRITING GUIDELINES:
- Use professional, academic language
- Expand each point with relevant technical details
- Add logical connections between tasks
- Use appropriate technical terminology
- Make the report coherent and well-organized
- Length: 200-400 words
- Do NOT invent tasks not mentioned in the points
- Do NOT add personal opinions or evaluations

{$contextSection}STUDENT'S BULLET POINTS:
{$formattedPoints}

Generate the complete professional report (in {$languageName}):";

        try {
            $generatedReport = $this->geminiService->generateText($prompt);

            if (!$generatedReport) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.ai.failed_generate')
                ], 500);
            }

            $generatedReport = trim(str_replace(['```markdown', '```', '```text'], '', $generatedReport));
            $stats = $this->calculateTextStats($generatedReport, $detectedLanguage);

            return response()->json([
                'success' => true,
                'message' => __('messages.ai.generate_success'),
                'data' => [
                    'input_points' => $points,
                    'context' => $context,
                    'generated_report' => $generatedReport,
                    'detected_language' => $detectedLanguage,
                    'points_count' => count($points),
                    'report_statistics' => $stats,
                    'ai_model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('AI Report Generation Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('messages.ai.connection_error_prefix') . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ========================================
     * الميزة D: اقتراحات ذكية (AI Suggestions)
     * ========================================
     * ✅ يعمل للطالب والمشرف
     */
    public function suggestContent(Request $request)
    {
        $request->validate([
            'major' => 'required|string|max:100',
            'current_tasks' => 'nullable|string|max:1000',
            'week_number' => 'nullable|integer|min:1|max:52',
            'language' => 'nullable|in:arabic,english',
        ], [
            'major.required' => __('messages.validation.major_required'),
        ]);

        $major = $request->input('major');
        $currentTasks = $request->input('current_tasks', '');
        $weekNumber = $request->input('week_number', 1);
        $language = $request->input('language');

        if (empty($language)) {
            $language = $this->detectLanguage($major . ' ' . $currentTasks);
        }
        $languageName = $language === 'arabic' ? 'Arabic' : 'English';

        Log::info('Generating AI suggestions', [
            'language' => $language,
            'major' => $major,
            'week_number' => $weekNumber,
            'user_role' => $request->user()->role, // ✅ Log للدور
        ]);

        $tasksSection = '';
        if (!empty($currentTasks)) {
            $tasksSection = "- Current Tasks Mentioned: {$currentTasks}\n";
        } else {
            $tasksSection = "- Current Tasks Mentioned: Not specified\n";
        }

        $prompt = "You are an expert academic advisor specialized in internship training programs for university students.

Your task is to provide intelligent suggestions to help a student write a comprehensive weekly internship report.

CRITICAL RULES:
1. You MUST respond in {$languageName}.
2. If the input is in English, your entire response MUST be in English.
3. If the input is in Arabic, your entire response MUST be in Arabic.
4. Do NOT mix languages.
5. Return ONLY a JSON object, no other text.

STUDENT INFORMATION:
- Major/Specialization: {$major}
- Week Number: {$weekNumber}
{$tasksSection}

RESPONSE FORMAT (JSON ONLY):
{
    \"suggested_topics\": [
        \"<topic 1 relevant to the major and week>\",
        \"<topic 2>\",
        \"<topic 3>\",
        \"<topic 4>\",
        \"<topic 5>\"
    ],
    \"suggested_tasks\": [
        \"<specific task 1 the student might have done>\",
        \"<specific task 2>\",
        \"<specific task 3>\",
        \"<specific task 4>\",
        \"<specific task 5>\"
    ],
    \"suggested_challenges\": [
        \"<common challenge 1 in this field>\",
        \"<common challenge 2>\",
        \"<common challenge 3>\"
    ],
    \"suggested_skills_learned\": [
        \"<skill 1>\",
        \"<skill 2>\",
        \"<skill 3>\",
        \"<skill 4>\"
    ],
    \"writing_tips\": [
        \"<tip 1 for writing a better report>\",
        \"<tip 2>\",
        \"<tip 3>\"
    ],
    \"example_bullet_points\": [
        \"<example bullet point 1>\",
        \"<example bullet point 2>\",
        \"<example bullet point 3>\"
    ]
}

GUIDELINES:
- Make suggestions specific to the student's major
- Consider the week number (early weeks = learning, later weeks = advanced tasks)
- Include technical terms relevant to the field
- Make suggestions realistic and achievable
- Provide actionable and practical suggestions

Generate the suggestions (in {$languageName}):";

        try {
            $aiResponse = $this->geminiService->generateText($prompt);

            if (!$aiResponse) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.ai.failed_suggest')
                ], 500);
            }

            $cleanResponse = trim(str_replace(['```json', '```', '```text'], '', $aiResponse));
            $suggestions = json_decode($cleanResponse, true);

            if (!$suggestions) {
                Log::error('Failed to parse AI suggestions response', [
                    'response' => $cleanResponse,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => __('messages.ai.suggest_default'),
                    'data' => [
                        'suggested_topics' => $language === 'arabic'
                            ? [__('messages.ai.default_topic_1'), __('messages.ai.default_topic_2'), __('messages.ai.default_topic_3')]
                            : ['Learn field fundamentals', 'Review technical documentation', 'Participate in team meetings'],
                        'suggested_tasks' => $language === 'arabic'
                            ? [__('messages.ai.default_task_1'), __('messages.ai.default_task_2'), __('messages.ai.default_task_3')]
                            : ['Read documentation', 'Attend meeting', 'Complete simple task'],
                        'suggested_challenges' => $language === 'arabic'
                            ? [__('messages.ai.default_challenge_1'), __('messages.ai.default_challenge_2')]
                            : ['Understanding work environment', 'Working with new tools'],
                        'suggested_skills_learned' => $language === 'arabic'
                            ? [__('messages.ai.default_skill_1'), __('messages.ai.default_skill_2')]
                            : ['Professional communication', 'Time management'],
                        'writing_tips' => $language === 'arabic'
                            ? [__('messages.ai.default_improvement_terms'), __('messages.ai.default_tip_2')]
                            : ['Use technical terms', 'Mention challenges and solutions'],
                        'example_bullet_points' => $language === 'arabic'
                            ? [__('messages.ai.default_example_1'), __('messages.ai.default_example_2')]
                            : ['Learned field fundamentals', 'Participated in team meeting'],
                        'major' => $major,
                        'week_number' => $weekNumber,
                        'detected_language' => $language,
                        'ai_model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => __('messages.ai.suggest_success'),
                'data' => [
                    'suggested_topics' => $suggestions['suggested_topics'] ?? [],
                    'suggested_tasks' => $suggestions['suggested_tasks'] ?? [],
                    'suggested_challenges' => $suggestions['suggested_challenges'] ?? [],
                    'suggested_skills_learned' => $suggestions['suggested_skills_learned'] ?? [],
                    'writing_tips' => $suggestions['writing_tips'] ?? [],
                    'example_bullet_points' => $suggestions['example_bullet_points'] ?? [],
                    'major' => $major,
                    'week_number' => $weekNumber,
                    'detected_language' => $language,
                    'ai_model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('AI Suggestions Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('messages.ai.connection_error_prefix') . $e->getMessage()
            ], 500);
        }
    }
}
