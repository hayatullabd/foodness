<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadService
{
    /**
     * Upload and store an image with an auto-generated SEO-friendly and unique timestamped filename.
     *
     * Pattern: {clean-slug}-{YmdHis}-{random6}.{extension}
     * Example: silk-saree-20261020110530-a8f3b2.webp
     *
     * @param  string|null  $prefixContext  (e.g., product name, category name, or custom title)
     * @return string Relative path where the file is stored
     */
    public static function upload(
        UploadedFile $file,
        string $directory = 'uploads',
        ?string $prefixContext = null,
        string $disk = 'public'
    ): string {
        $filename = self::generateFileName($file, $prefixContext);

        return $file->storeAs($directory, $filename, $disk);
    }

    /**
     * Store a base64 encoded image (e.g., from client-side canvas video frame capture).
     */
    public static function storeBase64(
        string $base64Data,
        string $directory = 'uploads',
        ?string $prefixContext = null,
        string $disk = 'public'
    ): ?string {
        if (! preg_match('/^data:image\/(\w+);base64,/', $base64Data, $typeMatches)) {
            return null;
        }

        $cleanData = substr($base64Data, strpos($base64Data, ',') + 1);
        $type = strtolower($typeMatches[1]);
        $extension = ($type === 'jpeg') ? 'jpg' : $type;
        $decoded = base64_decode($cleanData, true);

        if ($decoded === false) {
            return null;
        }

        $baseName = '';
        if ($prefixContext !== null && trim($prefixContext) !== '') {
            $baseName = Str::slug(trim($prefixContext));
        }
        $baseName = Str::substr($baseName, 0, 50) ?: 'auto-thumb';
        $timestamp = now()->format('YmdHis');
        $random = Str::lower(Str::random(6));
        $filename = "{$baseName}-{$timestamp}-{$random}.{$extension}";

        $path = trim($directory, '/').'/'.$filename;
        Storage::disk($disk)->put($path, $decoded);

        return $path;
    }

    /**
     * Generate an SEO-friendly and unique filename.
     */
    public static function generateFileName(UploadedFile $file, ?string $prefixContext = null): string
    {
        $baseName = '';

        if ($prefixContext !== null && trim($prefixContext) !== '') {
            $baseName = Str::slug(trim($prefixContext));
        }

        if ($baseName === '') {
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $baseName = Str::slug($originalName);
        }

        if ($baseName === '') {
            $baseName = 'image';
        }

        // Limit base name length to 50 characters for filesystem and URL safety
        $baseName = Str::substr($baseName, 0, 50);

        $timestamp = now()->format('YmdHis');
        $random = Str::lower(Str::random(6));
        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'jpg');

        return "{$baseName}-{$timestamp}-{$random}.{$extension}";
    }

    /**
     * Delete an existing file if present.
     */
    public static function delete(?string $path, string $disk = 'public'): bool
    {
        if ($path && Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }

        return false;
    }

    /**
     * Sanitize and normalize a relative public storage path.
     */
    public static function sanitizePath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $normalized = str_replace('\\', '/', trim($path));
        $normalized = ltrim($normalized, '/');

        if (str_starts_with($normalized, 'storage/')) {
            $normalized = substr($normalized, 8);
        }

        if (str_contains($normalized, '..') || str_starts_with($normalized, '.')) {
            return null;
        }

        return $normalized === '' ? null : $normalized;
    }

    /**
     * Check if a sanitized file exists in public storage.
     */
    public static function exists(?string $path, string $disk = 'public'): bool
    {
        $sanitized = self::sanitizePath($path);
        if (! $sanitized) {
            return false;
        }

        return Storage::disk($disk)->exists($sanitized);
    }
}
