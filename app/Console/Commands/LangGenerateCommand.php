<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Services\LanguageMapper;

class LangGenerateCommand extends Command
{
    protected $signature = 'lang:generate
                            {--path=app/Http/Controllers : المسار المطلوب فحصه}
                            {--force : فرض التحديث بدون تأكيد}';

    protected $description = 'Generate translation keys from Arabic texts';

    protected $arabicPattern = '/[\x{0600}-\x{06FF}]/u';
    protected $newMappings = [];
    protected $skippedPatterns = [];

    public function handle()
    {
        $this->info('Starting translation key generation...');
        $this->newLine();

        $path = base_path($this->option('path'));

        if (!File::exists($path)) {
            $this->error("Path not found: {$path}");
            return 1;
        }

        $this->info("Scanning: {$path}");
        $this->newLine();

        $this->scanDirectory($path);

        if (empty($this->newMappings)) {
            $this->info('All Arabic texts already have mappings!');
            return 0;
        }

        $this->displayResults();

        if ($this->option('force') || $this->confirm('Add new keys to LanguageMapper?')) {
            $this->addToMapper();
            $this->info('Keys added successfully!');
        }

        return 0;
    }

    protected function scanDirectory(string $path): void
    {
        $files = File::allFiles($path);

        $bar = $this->output->createProgressBar(count($files));

        foreach ($files as $file) {
            if ($this->shouldSkip($file->getPathname())) {
                $bar->advance();
                continue;
            }

            $this->scanFile($file->getPathname());
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    protected function shouldSkip(string $path): bool
    {
        $skipPatterns = [
            'vendor', 'node_modules', 'storage', 'bootstrap/cache',
            '.git', 'tests/', 'lang/', 'Commands/', 'Services/',
            'Rules/', 'Notifications/', 'Requests/', 'Middleware/',
            'Models/', 'Providers/', 'Factories/', 'Seeders/',
            'Events/', 'Listeners/', 'Jobs/', 'Mail/',
        ];

        foreach ($skipPatterns as $pattern) {
            if (str_contains($path, $pattern)) {
                return true;
            }
        }

        return pathinfo($path, PATHINFO_EXTENSION) !== 'php';
    }

    protected function scanFile(string $path): void
    {
        $content = File::get($path);

        if (!preg_match_all($this->arabicPattern, $content)) {
            return;
        }

        $lines = explode("\n", $content);

        foreach ($lines as $lineNum => $line) {
            $trimmed = trim($line);

            // Skip comments
            if (Str::startsWith($trimmed, '//') ||
                Str::startsWith($trimmed, '*') ||
                Str::startsWith($trimmed, '#')) {
                continue;
            }

            // Skip lines with regex patterns
            if (Str::contains($line, ['preg_match', 'preg_replace', 'preg_split'])) {
                continue;
            }

            // Skip lines with => (array definitions)
            if (Str::contains($line, '=>')) {
                continue;
            }

            // Skip lines with backslashes (escape sequences)
            if (Str::contains($line, ['\\\\', '\\x', '\\u', '\\p'])) {
                continue;
            }

            // Extract Arabic strings
            if (preg_match_all('/[\'"]([^\'"]*[\x{0600}-\x{06FF}][^\'"]*)[\'"]/u', $line, $matches)) {
                foreach ($matches[1] as $text) {
                    $text = trim($text);

                    // Skip short texts
                    if (strlen($text) < 5) {
                        continue;
                    }

                    // Skip texts with special characters that break PHP
                    if (preg_match('/[\'"\\\\]/', $text)) {
                        $this->skippedPatterns[] = $text;
                        continue;
                    }

                    // Skip if already mapped
                    if (LanguageMapper::getKey($text)) {
                        continue;
                    }

                    // Skip duplicates
                    if (isset($this->newMappings[$text])) {
                        continue;
                    }

                    $this->newMappings[$text] = $this->generateKey($text);
                }
            }
        }
    }

    protected function generateKey(string $text): string
    {
        return 'msg_' . substr(md5($text), 0, 8);
    }

    protected function displayResults(): void
    {
        $this->info('Results:');
        $this->newLine();

        $rows = [];
        foreach ($this->newMappings as $text => $key) {
            $rows[] = [Str::limit($text, 50), $key];
        }

        $this->table(['Text', 'Key'], $rows);

        $this->newLine();
        $this->info("New keys: " . count($this->newMappings));

        if (!empty($this->skippedPatterns)) {
            $this->newLine();
            $this->warn("Skipped (contain special chars): " . count($this->skippedPatterns));
        }
    }

    protected function addToMapper(): void
    {
        $mapperPath = base_path('app/Services/LanguageMapper.php');

        if (!File::exists($mapperPath)) {
            $this->error("LanguageMapper not found: {$mapperPath}");
            return;
        }

        $content = File::get($mapperPath);

        $newEntries = '';
        foreach ($this->newMappings as $text => $key) {
            $escapedText = str_replace(["'", "\\"], ["\\'", "\\\\"], $text);
            $newEntries .= "        '{$escapedText}' => '{$key}',\n";
        }

        $pattern = '/(protected static array \$mappings = \[)(.*?)(\s*\];)/s';

        if (preg_match($pattern, $content, $matches)) {
            $replacement = $matches[1] . $matches[2] . "\n" . $newEntries . $matches[3];
            $content = preg_replace($pattern, $replacement, $content);
            File::put($mapperPath, $content);
        } else {
            $this->error('Could not find mappings array in LanguageMapper');
        }
    }
}
