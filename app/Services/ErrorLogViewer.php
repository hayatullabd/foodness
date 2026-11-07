<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ErrorLogViewer
{
    public const DEFAULT_LINES = 500;

    public function path(): string
    {
        return storage_path('logs/laravel.log');
    }

    /**
     * @return array{
     *     path: string,
     *     exists: bool,
     *     writable: bool,
     *     size_bytes: int|null,
     *     line_count: int,
     *     content: string,
     *     channel: string,
     *     level: string,
     *     lines_limit: int
     * }
     */
    public function status(int $lines = self::DEFAULT_LINES): array
    {
        $path = $this->path();
        $exists = File::exists($path);
        $directory = dirname($path);
        $writable = $exists
            ? is_writable($path)
            : (File::isDirectory($directory) && is_writable($directory));

        $content = '';
        $lineCount = 0;
        $sizeBytes = null;

        if ($exists) {
            $sizeBytes = File::size($path);
            $content = $this->tail($path, $lines);
            $lineCount = $content === '' ? 0 : substr_count($content, "\n") + (str_ends_with($content, "\n") ? 0 : 1);
        }

        return [
            'path' => $path,
            'exists' => $exists,
            'writable' => $writable,
            'size_bytes' => $sizeBytes,
            'line_count' => $lineCount,
            'content' => $content,
            'channel' => (string) config('logging.default', 'stack'),
            'level' => (string) config('logging.channels.single.level', config('logging.channels.stack.level', 'debug')),
            'lines_limit' => $lines,
        ];
    }

    public function ensureFileExists(): bool
    {
        $path = $this->path();
        $directory = dirname($path);

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        if (! File::exists($path)) {
            File::put($path, '');
        }

        return File::exists($path);
    }

    public function writeTestEntry(): void
    {
        $this->ensureFileExists();

        Log::info('Foodness admin test log entry.', [
            'source' => 'admin.error-log',
            'at' => now()->toDateTimeString(),
        ]);
    }

    public function clear(): void
    {
        $this->ensureFileExists();
        File::put($this->path(), '');
    }

    private function tail(string $path, int $lines): string
    {
        if ($lines < 1) {
            return '';
        }

        $file = new \SplFileObject($path, 'r');
        $file->seek(PHP_INT_MAX);
        $lastLine = (int) $file->key();

        if ($lastLine < 0) {
            return '';
        }

        $start = max(0, $lastLine - $lines + 1);
        $buffer = [];

        for ($i = $start; $i <= $lastLine; $i++) {
            $file->seek($i);
            $line = $file->current();

            if ($line === false) {
                continue;
            }

            $buffer[] = rtrim((string) $line, "\r\n");
        }

        return implode("\n", $buffer);
    }
}
