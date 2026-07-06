<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Services\LanguageMapper;

class LangPreviewCommand extends Command
{
    protected $signature = 'lang:preview
                            {--controller= : Specific controller name}
                            {--output=preview.txt : Output file}';

    protected $description = 'Preview translation changes before applying';

    protected $arabicPattern = '/[\x{0600}-\x{06FF}]/u';
    protected $changes = [];
    protected $unmapped = [];

    public function handle()
    {
        $this->info('Starting preview...');
        $this->newLine();

        $path = $this->option('controller')
            ? base_path('app/Http/Controllers/' . $this->option('controller'))
            : base_path('app/Http/Controllers');

        if (!File::exists($path)) {
            $this->error("File not found: {$path}");
            return 1;
        }

        if (File::isFile($path)) {
            $this->previewFile($path);
        } else {
            $this->previewDirectory($path);
        }

        if (empty($this->changes) && empty($this->unmapped)) {
            $this->info('No changes needed!');
            return 0;
        }

        $this->displayPreview();

        if ($this->option('output')) {
            $this->savePreview();
        }

        return 0;
    }

    protected function previewDirectory(string $path): void
    {
        $files = File::allFiles($path);

        foreach ($files as $file) {
            if (pathinfo($file->getPathname(), PATHINFO_EXTENSION) === 'php') {
                $this->previewFile($file->getPathname());
            }
        }
    }

    protected function previewFile(string $path): void
    {
        $content = File::get($path);
        $lines = explode("\n", $content);

        foreach ($lines as $lineNum => $line) {
            $trimmed = trim($line);

            // Skip comments
            if (Str::startsWith($trimmed, '//') ||
                Str::startsWith($trimmed, '*') ||
                Str::startsWith($trimmed, '#')) {
                continue;
            }

            // Skip lines that are ONLY array definitions (not containing Arabic in values)
            if ($this->isOnlyArrayDefinition($line)) {
                continue;
            }

            // Skip regex pattern lines
            if ($this->isRegexPattern($line)) {
                continue;
            }

            // Skip lines with escape sequences
            if (Str::contains($line, ['\\\\', '\\x{', '\\u', '\\p{'])) {
                continue;
            }

            // Extract Arabic strings
            if (preg_match_all('/[\'"]([^\'"]*[\x{0600}-\x{06FF}][^\'"]*)[\'"]/u', $line, $matches)) {
                foreach ($matches[1] as $text) {
                    $text = trim($text);

                    if (strlen($text) < 5) {
                        continue;
                    }

                    // Skip texts with special chars that break PHP
                    if (preg_match('/[\'"\\\\]/', $text)) {
                        continue;
                    }

                    // Skip regex patterns
                    if (preg_match('/^\/.*\/[a-z]*$/', $text)) {
                        continue;
                    }

                    $key = LanguageMapper::getKey($text);

                    if ($key) {
                        $this->changes[] = [
                            'file' => str_replace(base_path(), '', $path),
                            'line' => $lineNum + 1,
                            'original' => $text,
                            'replacement' => "__('messages.{$key}')",
                            'key' => $key,
                        ];
                    } else {
                        $this->unmapped[] = [
                            'file' => str_replace(base_path(), '', $path),
                            'line' => $lineNum + 1,
                            'text' => $text,
                        ];
                    }
                }
            }
        }
    }

    /**
     * Check if line is ONLY an array definition (no Arabic text in values)
     */
    protected function isOnlyArrayDefinition(string $line): bool
    {
        // If line contains Arabic, it's NOT just an array definition
        if (preg_match($this->arabicPattern, $line)) {
            return false;
        }

        // If line contains =>, it might be an array definition
        return Str::contains($line, '=>');
    }

    /**
     * Check if line is a regex pattern
     */
    protected function isRegexPattern(string $line): bool
    {
        $regexIndicators = [
            'preg_match', 'preg_replace', 'preg_split', 'preg_match_all',
            '/^/', '/$/', '/[', '/\\', 'regex', 'pattern',
        ];

        foreach ($regexIndicators as $indicator) {
            if (Str::contains($line, $indicator)) {
                return true;
            }
        }

        return false;
    }

    protected function displayPreview(): void
    {
        if (!empty($this->changes)) {
            $this->info('Proposed changes:');
            $this->newLine();

            foreach (array_slice($this->changes, 0, 20) as $index => $change) {
                $this->line("🔹 " . ($index + 1) . ". " . basename($change['file']) . ":" . $change['line']);
                $this->line("   ❌ " . Str::limit($change['original'], 60));
                $this->line("   ✅ " . $change['replacement']);
                $this->newLine();
            }

            if (count($this->changes) > 20) {
                $this->line("... and " . (count($this->changes) - 20) . " more");
            }

            $this->newLine();
            $this->info("Ready changes: " . count($this->changes));
        }

        if (!empty($this->unmapped)) {
            $this->newLine();
            $this->warn('Texts without mapping:');
            $this->newLine();

            foreach (array_slice($this->unmapped, 0, 10) as $index => $item) {
                $this->line("  " . ($index + 1) . ". " . Str::limit($item['text'], 70));
            }

            if (count($this->unmapped) > 10) {
                $this->line("  ... and " . (count($this->unmapped) - 10) . " more");
            }

            $this->newLine();
            $this->warn("Unmapped texts: " . count($this->unmapped));
        }
    }

    protected function savePreview(): void
    {
        $outputPath = base_path($this->option('output'));

        $content = "# Preview\n\n";
        $content .= "- Ready changes: " . count($this->changes) . "\n";
        $content .= "- Unmapped: " . count($this->unmapped) . "\n\n";

        foreach ($this->changes as $change) {
            $content .= "### {$change['file']}:{$change['line']}\n";
            $content .= "- Original: `{$change['original']}`\n";
            $content .= "- Replace: `{$change['replacement']}`\n\n";
        }

        File::put($outputPath, $content);

        $this->newLine();
        $this->info("Saved to: {$outputPath}");
    }
}
