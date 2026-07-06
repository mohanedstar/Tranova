<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Services\LanguageMapper;

class LangReplaceCommand extends Command
{
    protected $signature = 'lang:replace
                            {--controller= : Specific controller name}
                            {--backup : Create backup}
                            {--dry-run : Preview only}
                            {--force : Apply without confirmation}';

    protected $description = 'Replace Arabic texts with translation keys';

    protected $arabicPattern = '/[\x{0600}-\x{06FF}]/u';
    protected $replacements = 0;
    protected $filesModified = 0;
    protected $skipped = [];

    public function handle()
    {
        $this->info('Starting replacement...');
        $this->newLine();

        $path = $this->option('controller')
            ? base_path('app/Http/Controllers/' . $this->option('controller'))
            : base_path('app/Http/Controllers');

        if (!File::exists($path)) {
            $this->error("File not found: {$path}");
            return 1;
        }

        if (!$this->option('force') && !$this->option('dry-run')) {
            if (!$this->confirm('This will modify files. Continue?')) {
                $this->info('Cancelled');
                return 0;
            }
        }

        if (File::isFile($path)) {
            $this->processFile($path);
        } else {
            $this->processDirectory($path);
        }

        $this->newLine();
        $this->info("Done!");
        $this->info("Files modified: {$this->filesModified}");
        $this->info("Replacements: {$this->replacements}");

        if (!empty($this->skipped)) {
            $this->newLine();
            $this->warn("Skipped (no mapping): " . count($this->skipped));
        }

        return 0;
    }

    protected function processDirectory(string $path): void
    {
        $files = File::allFiles($path);

        $bar = $this->output->createProgressBar(count($files));

        foreach ($files as $file) {
            $bar->setMessage(basename($file->getPathname()));

            if (pathinfo($file->getPathname(), PATHINFO_EXTENSION) === 'php') {
                $this->processFile($file->getPathname());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    protected function processFile(string $path): void
    {
        $content = File::get($path);
        $originalContent = $content;
        $fileReplacements = 0;
        $lines = explode("\n", $content);
        $newLines = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Skip comments
            if (Str::startsWith($trimmed, '//') ||
                Str::startsWith($trimmed, '*') ||
                Str::startsWith($trimmed, '#')) {
                $newLines[] = $line;
                continue;
            }

            // Skip regex pattern lines
            if ($this->isRegexPattern($line)) {
                $newLines[] = $line;
                continue;
            }

            // Skip lines with escape sequences
            if (Str::contains($line, ['\\\\', '\\x{', '\\u', '\\p{'])) {
                $newLines[] = $line;
                continue;
            }

            // Replace Arabic texts
            $newLine = preg_replace_callback('/[\'"]([^\'"]*[\x{0600}-\x{06FF}][^\'"]*)[\'"]/u', function($matches) use (&$fileReplacements) {
                $text = trim($matches[1]);

                if (strlen($text) < 5) {
                    return $matches[0];
                }

                // Skip texts with special chars
                if (preg_match('/[\'"\\\\]/', $text)) {
                    return $matches[0];
                }

                // Skip regex patterns
                if (preg_match('/^\/.*\/[a-z]*$/', $text)) {
                    return $matches[0];
                }

                $key = LanguageMapper::getKey($text);

                if (!$key) {
                    $this->skipped[] = $text;
                    return $matches[0];
                }

                $fileReplacements++;
                $this->replacements++;

                return "__('messages.{$key}')";
            }, $line);

            $newLines[] = $newLine;
        }

        $content = implode("\n", $newLines);

        if ($content !== $originalContent && $fileReplacements > 0) {
            if ($this->option('backup') && !$this->option('dry-run')) {
                $backupPath = $path . '.backup.' . date('Y-m-d_H-i-s');
                File::copy($path, $backupPath);
            }

            if (!$this->option('dry-run')) {
                File::put($path, $content);
            }

            $this->filesModified++;
            $this->line("✏️ Modified: " . basename($path) . " ({$fileReplacements} replacements)");
        }
    }

    /**
     * Check if line is a regex pattern
     */
    protected function isRegexPattern(string $line): bool
    {
        $regexIndicators = [
            'preg_match', 'preg_replace', 'preg_split', 'preg_match_all',
        ];

        foreach ($regexIndicators as $indicator) {
            if (Str::contains($line, $indicator)) {
                return true;
            }
        }

        return false;
    }
}
