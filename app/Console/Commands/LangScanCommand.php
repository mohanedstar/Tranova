<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Services\LanguageMapper;

class LangScanCommand extends Command
{
    protected $signature = 'lang:scan
                            {--path=app : المسار المطلوب فحصه}
                            {--output=lang-report.txt : ملف التقرير}
                            {--extract : استخراج النصوص العربية فقط}
                            {--with-keys : عرض المفاتيح المقترحة}';

    protected $description = 'فحص المشروع واستخراج النصوص العربية مع عرض حالة الـ mapping';

    protected $arabicPattern = '/[\x{0600}-\x{06FF}]/u';
    protected $arabicStrings = [];
    protected $mappedStrings = [];
    protected $unmappedStrings = [];
    protected $filesScanned = 0;
    protected $totalMatches = 0;

    public function handle()
    {
        $this->info('🔍 بدء فحص المشروع...');
        $this->newLine();

        $path = base_path($this->option('path'));

        if (!File::exists($path)) {
            $this->error("❌ المسار غير موجود: {$path}");
            return 1;
        }

        $this->info("📂 المسار: {$path}");
        $this->newLine();

        // فحص الملفات
        $this->scanDirectory($path);

        // تصنيف النصوص
        $this->classifyStrings();

        // عرض التقرير
        $this->displayReport();

        // حفظ التقرير
        if ($this->option('output')) {
            $this->saveReport();
        }

        return 0;
    }

    protected function scanDirectory(string $path): void
    {
        $files = File::allFiles($path);

        $bar = $this->output->createProgressBar(count($files));
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');

        foreach ($files as $file) {
            $bar->setMessage(basename($file->getPathname()));

            // تجاهل ملفات معينة
            if ($this->shouldSkip($file->getPathname())) {
                $bar->advance();
                continue;
            }

            $this->scanFile($file->getPathname());
            $this->filesScanned++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    protected function shouldSkip(string $path): bool
    {
        $skipPatterns = [
            'vendor',
            'node_modules',
            'storage',
            'bootstrap/cache',
            '.git',
            'tests/Feature',
            'tests/Unit',
            'lang/',
            'Commands/',
            'Services/',
        ];

        foreach ($skipPatterns as $pattern) {
            if (Str::contains($path, $pattern)) {
                return true;
            }
        }

        // فحص الامتداد
        $allowedExtensions = ['php', 'blade.php', 'json'];
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return !in_array($extension, $allowedExtensions);
    }

    protected function scanFile(string $path): void
    {
        $content = File::get($path);

        // البحث عن النصوص العربية
        if (preg_match_all($this->arabicPattern, $content, $matches)) {
            $this->totalMatches += count($matches[0]);

            // استخراج السلاسل النصية الكاملة
            $this->extractArabicStrings($path, $content);
        }
    }

    protected function extractArabicStrings(string $path, string $content): void
    {
        $lines = explode("\n", $content);

        foreach ($lines as $lineNum => $line) {
            // تجاهل التعليقات
            $trimmedLine = trim($line);
            if (Str::startsWith($trimmedLine, '//') ||
                Str::startsWith($trimmedLine, '*') ||
                Str::startsWith($trimmedLine, '#')) {
                continue;
            }

            // البحث عن النصوص بين علامات الاقتباس
            $patterns = [
                '/\'([^\']*[\x{0600}-\x{06FF}][^\']*)\'/u',
                '/"([^"]*[\x{0600}-\x{06FF}][^"]*)"/u',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match_all($pattern, $line, $matches)) {
                    foreach ($matches[1] as $match) {
                        $text = trim($match);

                        // تجاهل النصوص القصيرة جداً
                        if (strlen($text) < 5) {
                            continue;
                        }

                        $relativePath = str_replace(base_path(), '', $path);

                        $this->arabicStrings[] = [
                            'file' => $relativePath,
                            'text' => $text,
                            'line' => $lineNum + 1,
                        ];
                    }
                }
            }
        }
    }

    protected function classifyStrings(): void
    {
        foreach ($this->arabicStrings as $string) {
            $key = LanguageMapper::getKey($string['text']);

            if ($key) {
                $string['key'] = $key;
                $this->mappedStrings[] = $string;
            } else {
                $this->unmappedStrings[] = $string;
            }
        }
    }

    protected function displayReport(): void
    {
        $this->info('📊 تقرير الفحص:');
        $this->newLine();

        $this->table(
            ['المقياس', 'القيمة'],
            [
                ['الملفات المفحوصة', $this->filesScanned],
                ['إجمالي النصوص العربية', count($this->arabicStrings)],
                ['✅ نصوص لها mapping', count($this->mappedStrings)],
                ['⚠️ نصوص بدون mapping', count($this->unmappedStrings)],
                ['إجمالي الأحرف العربية', $this->totalMatches],
            ]
        );

        // عرض النصوص التي لها mapping
        if (count($this->mappedStrings) > 0) {
            $this->newLine();
            $this->info('✅ نصوص جاهزة للاستبدال:');
            $this->newLine();

            $samples = array_slice($this->mappedStrings, 0, 10);

            foreach ($samples as $index => $string) {
                $this->line("  " . ($index + 1) . ". " . Str::limit($string['text'], 60));
                $this->line("     🔑 messages.{$string['key']}");
                $this->line("     📁 {$string['file']}:{$string['line']}");
            }

            if (count($this->mappedStrings) > 10) {
                $this->newLine();
                $this->line("  ... و " . (count($this->mappedStrings) - 10) . " نص آخر");
            }
        }

        // عرض النصوص بدون mapping
        if (count($this->unmappedStrings) > 0) {
            $this->newLine();
            $this->warn('⚠️ نصوص بدون mapping (يجب إضافتها يدوياً):');
            $this->newLine();

            $samples = array_slice($this->unmappedStrings, 0, 10);

            foreach ($samples as $index => $string) {
                $this->line("  " . ($index + 1) . ". " . Str::limit($string['text'], 70));
                $this->line("     📁 {$string['file']}:{$string['line']}");
            }

            if (count($this->unmappedStrings) > 10) {
                $this->newLine();
                $this->line("  ... و " . (count($this->unmappedStrings) - 10) . " نص آخر");
            }
        }

        // عرض خيارات الاستبدال
        $this->newLine();
        $this->info('🎯 الأوامر المتاحة:');
        $this->newLine();
        $this->line('  1️⃣ php artisan lang:preview --controller=OpportunityController.php');
        $this->line('  2️⃣ php artisan lang:replace --controller=OpportunityController.php --backup');
        $this->line('  3️⃣ php artisan lang:generate --path=app');
    }

    protected function saveReport(): void
    {
        $outputPath = base_path($this->option('output'));

        $report = "# تقرير فحص النصوص العربية\n\n";
        $report .= "## 📊 الإحصائيات\n\n";
        $report .= "- الملفات المفحوصة: {$this->filesScanned}\n";
        $report .= "- إجمالي النصوص العربية: " . count($this->arabicStrings) . "\n";
        $report .= "- ✅ نصوص لها mapping: " . count($this->mappedStrings) . "\n";
        $report .= "- ⚠️ نصوص بدون mapping: " . count($this->unmappedStrings) . "\n";
        $report .= "- إجمالي الأحرف: {$this->totalMatches}\n\n";

        if (count($this->mappedStrings) > 0) {
            $report .= "## ✅ نصوص جاهزة للاستبدال\n\n";
            $report .= "| الملف | السطر | النص | المفتاح |\n";
            $report .= "|------|-------|------|---------|\n";

            foreach ($this->mappedStrings as $string) {
                $text = str_replace('|', '\\|', $string['text']);
                $report .= "| {$string['file']} | {$string['line']} | {$text} | messages.{$string['key']} |\n";
            }

            $report .= "\n";
        }

        if (count($this->unmappedStrings) > 0) {
            $report .= "## ⚠️ نصوص بدون mapping\n\n";
            $report .= "يجب إضافة هذه النصوص إلى `app/Services/LanguageMapper.php`\n\n";
            $report .= "| الملف | السطر | النص |\n";
            $report .= "|------|-------|------|\n";

            foreach ($this->unmappedStrings as $string) {
                $text = str_replace('|', '\\|', $string['text']);
                $report .= "| {$string['file']} | {$string['line']} | {$text} |\n";
            }

            $report .= "\n";
        }

        $report .= "## 🎯 الأوامر التالية\n\n";
        $report .= "```bash\n";
        $report .= "# معاينة التغييرات\n";
        $report .= "php artisan lang:preview --controller=OpportunityController.php\n\n";
        $report .= "# تطبيق التغييرات مع نسخة احتياطية\n";
        $report .= "php artisan lang:replace --controller=OpportunityController.php --backup\n\n";
        $report .= "# توليد مفاتيح جديدة\n";
        $report .= "php artisan lang:generate --path=app\n";
        $report .= "```\n";

        File::put($outputPath, $report);

        $this->newLine();
        $this->info("✅ تم حفظ التقرير في: {$outputPath}");
    }
}
