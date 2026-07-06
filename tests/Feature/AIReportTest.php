<?php

use App\Models\User;
use App\Models\Student;
use App\Models\Provider;
use App\Models\Supervisor;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // إنشاء طالب للاختبار
    $this->studentUser = User::factory()->create([
        'role' => 'student',
        'email_verified_at' => now(),
        'account_status' => 'active',
    ]);
    $this->student = Student::create([
        'user_id' => $this->studentUser->id,
        'student_id' => 'S_AI_001',
        'major' => 'IT',
        'university' => 'Test University',
        'year_of_study' => '3',
    ]);

    // إنشاء مزود للاختبار
    $this->providerUser = User::factory()->create([
        'role' => 'provider',
        'email_verified_at' => now(),
        'account_status' => 'active',
    ]);
    Provider::create([
        'user_id' => $this->providerUser->id,
        'organization_name' => 'Test Corp',
        'organization_type' => 'company',
        'address' => 'Gaza',
        'city' => 'Gaza',
    ]);

    // إنشاء مشرف للاختبار
    $this->supervisorUser = User::factory()->create([
        'role' => 'supervisor',
        'email_verified_at' => now(),
        'account_status' => 'active',
    ]);
    Supervisor::create([
        'user_id' => $this->supervisorUser->id,
        'employee_id' => 'EMP_AI_001',
        'department' => 'CS',
        'academic_title' => 'professor',
    ]);
});

afterEach(function () {
    Mockery::close();
});

/**
 * دالة مساعدة لإنشاء Mock لخدمة GeminiService
 */
function mockGeminiService(string $response): GeminiService
{
    $mock = Mockery::mock(GeminiService::class);
    $mock->shouldReceive('generateText')
        ->once()
        ->andReturn($response);

    app()->instance(GeminiService::class, $mock);

    return $mock;
}

/**
 * دالة مساعدة لإنشاء Mock يفشل
 */
function mockGeminiServiceFailure(): GeminiService
{
    $mock = Mockery::mock(GeminiService::class);
    $mock->shouldReceive('generateText')
        ->once()
        ->andThrow(new \Exception('API Error'));

    app()->instance(GeminiService::class, $mock);

    return $mock;
}

// ═══════════════════════════════════════════════════════════════
// الميزة A: تحسين التقرير (AI Report Improver)
// ═══════════════════════════════════════════════════════════════

test('الطالب يمكنه تحسين تقرير عربي', function () {
    $improvedText = 'خلال هذا الأسبوع، ركزت على تطوير مهاراتي في إطار عمل Laravel...';
    mockGeminiService($improvedText);

    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/student/ai/reports/improve', [
            'content' => 'تعلمت Laravel اليوم وعملت على database',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'تم تحسين التقرير بنجاح باستخدام الذكاء الاصطناعي.',
        ])
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'original_content',
                'improved_content',
                'detected_language',
                'original_word_count',
                'improved_word_count',
                'ai_model',
            ],
        ]);

    expect($response->json('data.detected_language'))->toBe('arabic');
    expect($response->json('data.improved_content'))->toBe($improvedText);
});

test('الطالب يمكنه تحسين تقرير إنجليزي', function () {
    $improvedText = 'During this week, I focused on developing my skills in Laravel...';
    mockGeminiService($improvedText);

    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/student/ai/reports/improve', [
            'content' => 'Today I learned Laravel and worked on database',
        ]);

    $response->assertStatus(200);
    expect($response->json('data.detected_language'))->toBe('english');
});

test('فشل تحسين التقرير عند عدم وجود محتوى', function () {
    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/student/ai/reports/improve', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['content']);
});

test('فشل تحسين التقرير عند محتوى قصير جداً', function () {
    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/student/ai/reports/improve', [
            'content' => 'قصير',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['content']);
});

test('فشل تحسين التقرير عند محتوى طويل جداً', function () {
    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/student/ai/reports/improve', [
            'content' => str_repeat('a', 2001),
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['content']);
});

test('المزود لا يمكنه تحسين تقرير', function () {
    $response = $this->actingAs($this->providerUser)
        ->postJson('/api/student/ai/reports/improve', [
            'content' => 'تعلمت Laravel اليوم وعملت على database',
        ]);

    $response->assertStatus(403);
});

test('المشرف لا يمكنه تحسين تقرير', function () {
    $response = $this->actingAs($this->supervisorUser)
        ->postJson('/api/student/ai/reports/improve', [
            'content' => 'تعلمت Laravel اليوم وعملت على database',
        ]);

    $response->assertStatus(403);
});

test('المستخدم غير المصادق عليه لا يمكنه تحسين تقرير', function () {
    $response = $this->postJson('/api/student/ai/reports/improve', [
        'content' => 'تعلمت Laravel اليوم وعملت على database',
    ]);

    $response->assertStatus(401);
});

test('معالجة خطأ API عند تحسين التقرير', function () {
    mockGeminiServiceFailure();

    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/student/ai/reports/improve', [
            'content' => 'تعلمت Laravel اليوم وعملت على database',
        ]);

    $response->assertStatus(500)
        ->assertJson([
            'success' => false,
        ]);
});

// ═══════════════════════════════════════════════════════════════
// الميزة B: تحليل التقرير (AI Report Analyzer)
// ═══════════════════════════════════════════════════════════════

test('الطالب يمكنه تحليل تقرير', function () {
    $analysisJson = json_encode([
        'quality_score' => 85,
        'grade' => 'good',
        'strengths' => ['محتوى جيد', 'تنظيم واضح', 'مصطلحات تقنية'],
        'weaknesses' => ['يحتاج أمثلة', 'فقرات قصيرة'],
        'improvements' => ['أضف أمثلة', 'وسّع الفقرات'],
        'detailed_feedback' => 'تقرير جيد بشكل عام',
        'criteria_scores' => [
            'content_quality' => 85,
            'structure' => 80,
            'language' => 90,
            'professionalism' => 85,
        ],
    ]);

    mockGeminiService($analysisJson);

    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/student/ai/reports/analyze', [
            'content' => 'تعلمت Laravel اليوم وعملت على database',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'quality_score' => 85,
                'grade' => 'good',
            ],
        ])
        ->assertJsonStructure([
            'data' => [
                'quality_score',
                'grade',
                'strengths',
                'weaknesses',
                'improvements',
                'detailed_feedback',
                'criteria_scores',
                'statistics',
                'detected_language',
                'ai_model',
            ],
        ]);
});

test('التحليل يعيد إحصائيات النص', function () {
    $analysisJson = json_encode([
        'quality_score' => 70,
        'grade' => 'average',
        'strengths' => [],
        'weaknesses' => [],
        'improvements' => [],
        'detailed_feedback' => 'Feedback',
        'criteria_scores' => [],
    ]);

    mockGeminiService($analysisJson);

    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/student/ai/reports/analyze', [
            'content' => 'تعلمت Laravel اليوم. وعملت على database.',
        ]);

    $response->assertStatus(200);

    $statistics = $response->json('data.statistics');
    expect($statistics)->toHaveKeys([
        'word_count',
        'sentence_count',
        'paragraph_count',
        'character_count',
        'character_count_no_spaces',
        'average_sentence_length',
        'estimated_reading_time_minutes',
    ]);
});

test('التحليل يتعامل مع JSON غير صالح من AI', function () {
    mockGeminiService('This is not valid JSON');

    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/student/ai/reports/analyze', [
            'content' => 'تعلمت Laravel اليوم وعملت على database',
        ]);

    // يجب أن يعيد استجابة افتراضية بدلاً من الفشل
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'quality_score' => 70,
                'grade' => 'good',
            ],
        ]);
});

test('فشل تحليل التقرير عند عدم وجود محتوى', function () {
    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/student/ai/reports/analyze', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['content']);
});

test('المزود لا يمكنه تحليل تقرير', function () {
    $response = $this->actingAs($this->providerUser)
        ->postJson('/api/student/ai/reports/analyze', [
            'content' => 'تعلمت Laravel اليوم وعملت على database',
        ]);

    $response->assertStatus(403);
});

// ═══════════════════════════════════════════════════════════════
// الميزة C: توليد تقرير من نقاط (AI Report Generator)
// ═══════════════════════════════════════════════════════════════

test('الطالب يمكنه توليد تقرير من نقاط عربية', function () {
    $generatedReport = 'خلال هذا الأسبوع، ركزت على تطوير مهاراتي في Laravel...';
    mockGeminiService($generatedReport);

    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/student/ai/reports/generate', [
            'points' => [
                'تعلمت Laravel',
                'عملت على database',
                'طورت API',
            ],
            'context' => 'الأسبوع الأول من التدريب',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'generated_report' => $generatedReport,
                'detected_language' => 'arabic',
                'points_count' => 3,
            ],
        ])
        ->assertJsonStructure([
            'data' => [
                'input_points',
                'context',
                'generated_report',
                'detected_language',
                'points_count',
                'report_statistics',
                'ai_model',
            ],
        ]);
});

test('الطالب يمكنه توليد تقرير من نقاط إنجليزية', function () {
    $generatedReport = 'During this week, I focused on Laravel development...';
    mockGeminiService($generatedReport);

    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/student/ai/reports/generate', [
            'points' => [
                'Learned Laravel',
                'Worked on database',
                'Developed API',
            ],
        ]);

    $response->assertStatus(200);
    expect($response->json('data.detected_language'))->toBe('english');
});

test('فشل التوليد عند وجود أقل من نقطتين', function () {
    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/student/ai/reports/generate', [
            'points' => ['نقطة واحدة فقط'],
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['points']);
});

test('فشل التوليد عند وجود أكثر من 20 نقطة', function () {
    $points = array_fill(0, 21, 'نقطة اختبار');

    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/student/ai/reports/generate', [
            'points' => $points,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['points']);
});

test('فشل التوليد عند وجود نقطة قصيرة جداً', function () {
    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/student/ai/reports/generate', [
            'points' => ['نقطة جيدة', 'قص'],
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['points.1']);
});

test('فشل التوليد عند عدم وجود نقاط', function () {
    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/student/ai/reports/generate', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['points']);
});

test('التوليد يعمل بدون context اختياري', function () {
    $generatedReport = 'Report without context...';
    mockGeminiService($generatedReport);

    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/student/ai/reports/generate', [
            'points' => ['نقطة أولى', 'نقطة ثانية'],
        ]);

    $response->assertStatus(200);
    expect($response->json('data.context'))->toBe('');
});

test('المزود لا يمكنه توليد تقرير', function () {
    $response = $this->actingAs($this->providerUser)
        ->postJson('/api/student/ai/reports/generate', [
            'points' => ['نقطة أولى', 'نقطة ثانية'],
        ]);

    $response->assertStatus(403);
});

// ═══════════════════════════════════════════════════════════════
// الميزة D: اقتراحات ذكية (AI Suggestions)
// ═══════════════════════════════════════════════════════════════

test('الطالب يمكنه الحصول على اقتراحات بالعربية', function () {
    $suggestionsJson = json_encode([
        'suggested_topics' => ['تعلم Laravel', 'قواعد البيانات', 'APIs'],
        'suggested_tasks' => ['قراءة الوثائق', 'بناء نموذج'],
        'suggested_challenges' => ['فهم العلاقات'],
        'suggested_skills_learned' => ['التواصل المهني'],
        'writing_tips' => ['استخدم مصطلحات تقنية'],
        'example_bullet_points' => ['تعلمت أساسيات Laravel'],
    ]);

    mockGeminiService($suggestionsJson);

    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/student/ai/reports/suggest', [
            'major' => 'تقنية المعلومات',
            'week_number' => 3,
            'current_tasks' => 'تعلمت Laravel',
            'language' => 'arabic',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'major' => 'تقنية المعلومات',
                'week_number' => 3,
                'detected_language' => 'arabic',
            ],
        ])
        ->assertJsonStructure([
            'data' => [
                'suggested_topics',
                'suggested_tasks',
                'suggested_challenges',
                'suggested_skills_learned',
                'writing_tips',
                'example_bullet_points',
                'major',
                'week_number',
                'detected_language',
                'ai_model',
            ],
        ]);
});

test('الطالب يمكنه الحصول على اقتراحات بالإنجليزية', function () {
    $suggestionsJson = json_encode([
        'suggested_topics' => ['Learn Laravel', 'Database design'],
        'suggested_tasks' => ['Read docs', 'Build model'],
        'suggested_challenges' => ['Understanding relations'],
        'suggested_skills_learned' => ['Professional communication'],
        'writing_tips' => ['Use technical terms'],
        'example_bullet_points' => ['Learned Laravel basics'],
    ]);

    mockGeminiService($suggestionsJson);

    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/student/ai/reports/suggest', [
            'major' => 'Computer Science',
            'week_number' => 3,
            'language' => 'english',
        ]);

    $response->assertStatus(200);
    expect($response->json('data.detected_language'))->toBe('english');
});

test('اكتشاف اللغة تلقائياً عند عدم تحديدها', function () {
    $suggestionsJson = json_encode([
        'suggested_topics' => ['تعلم Laravel'],
        'suggested_tasks' => ['قراءة الوثائق'],
        'suggested_challenges' => ['فهم العلاقات'],
        'suggested_skills_learned' => ['التواصل'],
        'writing_tips' => ['استخدم مصطلحات'],
        'example_bullet_points' => ['تعلمت Laravel'],
    ]);

    mockGeminiService($suggestionsJson);

    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/student/ai/reports/suggest', [
            'major' => 'تقنية المعلومات',
        ]);

    $response->assertStatus(200);
    expect($response->json('data.detected_language'))->toBe('arabic');
});

test('فشل الاقتراحات عند عدم وجود التخصص', function () {
    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/student/ai/reports/suggest', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['major']);
});

test('فشل الاقتراحات عند رقم أسبوع غير صالح', function () {
    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/student/ai/reports/suggest', [
            'major' => 'IT',
            'week_number' => 100,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['week_number']);
});

test('فشل الاقتراحات عند لغة غير صالحة', function () {
    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/student/ai/reports/suggest', [
            'major' => 'IT',
            'language' => 'french',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['language']);
});

test('الاقتراحات تتعامل مع JSON غير صالح', function () {
    mockGeminiService('Invalid JSON response');

    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/student/ai/reports/suggest', [
            'major' => 'تقنية المعلومات',
        ]);

    // يجب أن يعيد استجابة افتراضية
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);
});

test('المزود لا يمكنه الحصول على اقتراحات', function () {
    $response = $this->actingAs($this->providerUser)
        ->postJson('/api/student/ai/reports/suggest', [
            'major' => 'IT',
        ]);

    $response->assertStatus(403);
});

test('المشرف لا يمكنه الحصول على اقتراحات', function () {
    $response = $this->actingAs($this->supervisorUser)
        ->postJson('/api/student/ai/reports/suggest', [
            'major' => 'IT',
        ]);

    $response->assertStatus(403);
});

// ═══════════════════════════════════════════════════════════════
// اختبارات كشف اللغة (detectLanguage)
// ═══════════════════════════════════════════════════════════════

test('كشف النص العربي بشكل صحيح', function () {
    // ✅ إعداد الـ Mock أولاً
    mockGeminiService('نص محسّن');

    // ✅ ثم إرسال الطلب
    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/student/ai/reports/improve', [
            'content' => 'هذا نص عربي بالكامل تعلمت فيه Laravel',
        ]);

    $response->assertStatus(200);
    expect($response->json('data.detected_language'))->toBe('arabic');
});

test('كشف النص الإنجليزي بشكل صحيح', function () {
    mockGeminiService('Improved text');

    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/student/ai/reports/improve', [
            'content' => 'This is a fully English text about Laravel development',
        ]);

    $response->assertStatus(200);
    expect($response->json('data.detected_language'))->toBe('english');
});

test('كشف النص المختلط (أغلبه عربي) كعربي', function () {
    mockGeminiService('نص محسّن');

    $response = $this->actingAs($this->studentUser)
        ->postJson('/api/student/ai/reports/improve', [
            'content' => 'اليوم تعلمت Laravel وعملت على database و API',
        ]);

    $response->assertStatus(200);
    expect($response->json('data.detected_language'))->toBe('arabic');
});
