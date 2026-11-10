<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Childcategory;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVideo;
use App\Models\Setting;
use App\Models\Story;
use App\Models\Subcategory;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'avif', 'bmp', 'ico'];

    private const VIDEO_EXTENSIONS = ['mp4', 'webm', 'mov', 'avi', 'mkv', 'ogg'];

    private const DOCUMENT_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'zip'];

    public function index(Request $request): View|JsonResponse
    {
        $allFiles = $this->collectAllFiles();

        $stats = [
            'total_files' => $allFiles->count(),
            'total_size' => $this->formatBytes($allFiles->sum('size_bytes')),
            'images_count' => $allFiles->where('is_image', true)->count(),
            'videos_count' => $allFiles->where('is_video', true)->count(),
            'documents_count' => $allFiles->where('is_document', true)->count(),
            'others_count' => $allFiles->where('is_image', false)->where('is_video', false)->where('is_document', false)->count(),
        ];

        $folders = $allFiles->pluck('folder')->unique()->sort()->values();

        // Apply filters
        $filtered = $allFiles;

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $searchLower = mb_strtolower($search);
            $filtered = $filtered->filter(function (array $file) use ($searchLower): bool {
                return str_contains(mb_strtolower($file['name']), $searchLower)
                    || str_contains(mb_strtolower($file['path']), $searchLower)
                    || str_contains(mb_strtolower($file['folder']), $searchLower);
            });
        }

        $folderFilter = trim((string) $request->input('folder', ''));
        if ($folderFilter !== '' && $folderFilter !== 'all') {
            $filtered = $filtered->where('folder', $folderFilter);
        }

        $typeFilter = trim((string) $request->input('type', ''));
        if ($typeFilter === 'image') {
            $filtered = $filtered->where('is_image', true);
        } elseif ($typeFilter === 'video') {
            $filtered = $filtered->where('is_video', true);
        } elseif ($typeFilter === 'document') {
            $filtered = $filtered->where('is_document', true);
        } elseif ($typeFilter === 'other') {
            $filtered = $filtered->where('is_image', false)->where('is_video', false)->where('is_document', false);
        }

        // Apply sorting
        $sort = $request->input('sort', 'date_desc');
        $filtered = match ($sort) {
            'date_asc' => $filtered->sortBy('last_modified'),
            'name_asc' => $filtered->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE),
            'name_desc' => $filtered->sortByDesc('name', SORT_NATURAL | SORT_FLAG_CASE),
            'size_desc' => $filtered->sortByDesc('size_bytes'),
            'size_asc' => $filtered->sortBy('size_bytes'),
            default => $filtered->sortByDesc('last_modified'),
        };

        // Paginate
        $perPage = 60;
        $page = max(1, (int) $request->input('page', 1));
        $total = $filtered->count();
        $items = $filtered->slice(($page - 1) * $perPage, $perPage)->values();

        $paginatedFiles = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => route('admin.media.index'),
                'query' => $request->query(),
            ]
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'data' => $paginatedFiles->items(),
                'current_page' => $paginatedFiles->currentPage(),
                'last_page' => $paginatedFiles->lastPage(),
                'total' => $paginatedFiles->total(),
                'per_page' => $paginatedFiles->perPage(),
                'folders' => $folders,
            ]);
        }

        $viewMode = $request->input('view', 'grid');

        return view('admin.media.index', [
            'files' => $paginatedFiles,
            'folders' => $folders,
            'stats' => $stats,
            'q' => $search,
            'currentFolder' => $folderFilter,
            'currentType' => $typeFilter,
            'currentSort' => $sort,
            'viewMode' => $viewMode,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => [
                'required',
                'file',
                'max:51200', // max 50MB per file
                'mimes:jpg,jpeg,png,webp,gif,avif,mp4,webm,mov,pdf,doc,docx,xls,xlsx,csv,txt,zip',
            ],
            'folder' => ['nullable', 'string', 'max:50'],
            'custom_folder' => ['nullable', 'string', 'max:50'],
        ]);

        $folder = trim((string) $request->input('folder', 'media'));
        $customFolder = trim((string) $request->input('custom_folder', ''));

        if ($folder === 'custom' && $customFolder !== '') {
            $folder = Str::slug($customFolder);
        }

        if (blank($folder) || $folder === 'all' || $folder === 'root') {
            $folder = 'media';
        }

        $folder = preg_replace('/[^a-zA-Z0-9_\-\/]/', '', $folder);

        $uploadedCount = 0;
        $files = $request->file('files', []);
        $uploadedItems = [];

        foreach ($files as $file) {
            if (! $file->isValid()) {
                continue;
            }

            $extension = strtolower((string) $file->guessExtension());
            if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif', 'mp4', 'webm', 'mov', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'zip'], true)) {
                continue;
            }

            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $cleanName = preg_replace('/[^\p{L}\p{N}_\-\s\.]+/u', '', $originalName);
            $cleanName = trim(preg_replace('/\s+/', '-', (string) $cleanName), '-._');
            if ($cleanName === '') {
                $cleanName = 'media-file';
            }

            $filename = "{$cleanName}.{$extension}";
            $targetDir = trim($folder, '/');

            // If file already exists, append incremental suffix: filename-1.ext, filename-2.ext
            if (Storage::disk('public')->exists($targetDir . '/' . $filename)) {
                $counter = 1;
                while (Storage::disk('public')->exists($targetDir . "/{$cleanName}-{$counter}.{$extension}")) {
                    $counter++;
                }
                $filename = "{$cleanName}-{$counter}.{$extension}";
            }

            $storedPath = $file->storeAs($folder, $filename, 'public');
            $normalizedPath = str_replace('\\', '/', $storedPath);
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            $uploadedCount++;
            $uploadedItems[] = [
                'path' => $normalizedPath,
                'name' => $filename,
                'folder' => $folder,
                'url' => asset('storage/'.$normalizedPath),
                'size_formatted' => $this->formatBytes($file->getSize() ?: 0),
                'is_image' => in_array($ext, self::IMAGE_EXTENSIONS, true),
                'is_video' => in_array($ext, self::VIDEO_EXTENSIONS, true),
                'is_document' => in_array($ext, self::DOCUMENT_EXTENSIONS, true),
            ];
        }

        if ($uploadedCount === 0) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['ok' => false, 'message' => 'No files were uploaded. Please try again.'], 422);
            }

            return back()->with('error', 'No files were uploaded. Please try again.');
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'uploaded' => $uploadedCount,
                'files' => $uploadedItems,
                'message' => "{$uploadedCount} file(s) uploaded successfully.",
            ]);
        }

        return back()->with('success', "{$uploadedCount} file(s) uploaded successfully to folder '{$folder}'.");
    }

    public function destroy(Request $request): RedirectResponse|JsonResponse
    {
        $rawPath = (string) $request->input('path');
        $path = $this->sanitizePath($rawPath);

        if (! $path || ! Storage::disk('public')->exists($path)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['ok' => false, 'message' => 'File not found.'], 404);
            }

            return back()->with('error', 'File not found on disk.');
        }

        Storage::disk('public')->delete($path);
        $this->unlinkDatabaseReferences($path);
        $this->deleteMediaMetadata($path);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'message' => 'File deleted successfully.']);
        }

        return back()->with('success', 'File deleted successfully.');
    }

    public function bulkDestroy(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'paths' => ['required', 'array', 'min:1'],
            'paths.*' => ['required', 'string'],
        ]);

        $paths = $request->input('paths', []);
        $deletedCount = 0;

        foreach ($paths as $rawPath) {
            $path = $this->sanitizePath($rawPath);
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                $this->unlinkDatabaseReferences($path);
                $this->deleteMediaMetadata($path);
                $deletedCount++;
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'deleted' => $deletedCount,
                'message' => "{$deletedCount} file(s) deleted successfully.",
            ]);
        }

        return back()->with('success', "{$deletedCount} file(s) deleted successfully.");
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $rawPath = (string) $request->input('path');
        $path = $this->sanitizePath($rawPath);

        if (! $path || ! Storage::disk('public')->exists($path)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['ok' => false, 'message' => 'File not found.'], 404);
            }

            return back()->with('error', 'File not found on disk.');
        }

        $request->validate([
            'new_name' => ['nullable', 'string', 'max:150', 'regex:/^[a-zA-Z0-9_\-\. ]+$/'],
            'title' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $currentPath = $path;
        $newName = trim((string) $request->input('new_name', ''));

        // If user wants to rename the file physically
        if ($newName !== '') {
            $currentExt = pathinfo($currentPath, PATHINFO_EXTENSION);
            $newExt = pathinfo($newName, PATHINFO_EXTENSION);

            // Keep extension if not provided
            if ($newExt === '' && $currentExt !== '') {
                $newName .= '.'.$currentExt;
            }

            $newNameSlugged = Str::slug(pathinfo($newName, PATHINFO_FILENAME));
            $finalExt = strtolower(pathinfo($newName, PATHINFO_EXTENSION) ?: $currentExt);
            $finalFileName = ($newNameSlugged !== '' ? $newNameSlugged : 'file').'.'.$finalExt;

            $folder = dirname($currentPath);
            $targetPath = ($folder === '.' || $folder === '' ? '' : $folder.'/').$finalFileName;

            if ($targetPath !== $currentPath) {
                if (Storage::disk('public')->exists($targetPath)) {
                    if ($request->wantsJson() || $request->ajax()) {
                        return response()->json(['ok' => false, 'message' => 'A file with that name already exists in this folder.'], 422);
                    }

                    return back()->with('error', 'A file with that name already exists in this folder.');
                }

                Storage::disk('public')->move($currentPath, $targetPath);

                // Update database references across tables
                $this->updateDatabaseReferences($currentPath, $targetPath);

                // Update metadata key if existed
                $this->renameMetadataKey($currentPath, $targetPath);

                $currentPath = $targetPath;
            }
        }

        // Save metadata (title, alt_text, caption, description)
        $meta = [
            'title' => trim((string) $request->input('title', '')),
            'alt_text' => trim((string) $request->input('alt_text', '')),
            'caption' => trim((string) $request->input('caption', '')),
            'description' => trim((string) $request->input('description', '')),
            'updated_at' => now()->toIso8601String(),
        ];

        $this->saveMediaMetadata($currentPath, $meta);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => 'Media details updated successfully.',
                'path' => $currentPath,
                'name' => basename($currentPath),
                'url' => asset('storage/'.$currentPath),
                'meta' => $meta,
            ]);
        }

        return back()->with('success', 'Media file details updated successfully.');
    }

    public function download(Request $request): StreamedResponse|RedirectResponse
    {
        $rawPath = (string) $request->input('path');
        $path = $this->sanitizePath($rawPath);

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return back()->with('error', 'File not found for download.');
        }

        return Storage::disk('public')->download($path, basename($path));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function collectAllFiles(): Collection
    {
        $disk = Storage::disk('public');
        $rawFiles = $disk->allFiles();
        $metadata = $this->loadMediaMetadata();

        $collection = collect();

        foreach ($rawFiles as $filePath) {
            // Skip hidden or system files
            if (str_starts_with(basename($filePath), '.')) {
                continue;
            }

            $normalizedPath = str_replace('\\', '/', $filePath);
            $ext = strtolower(pathinfo($normalizedPath, PATHINFO_EXTENSION));
            $folder = dirname($normalizedPath);
            if ($folder === '.' || $folder === '') {
                $folder = 'root';
            }

            $sizeBytes = 0;
            $lastModified = 0;
            try {
                $sizeBytes = $disk->size($normalizedPath);
                $lastModified = $disk->lastModified($normalizedPath);
            } catch (\Throwable) {
            }

            $isImage = in_array($ext, self::IMAGE_EXTENSIONS, true);
            $isVideo = in_array($ext, self::VIDEO_EXTENSIONS, true);
            $isDocument = in_array($ext, self::DOCUMENT_EXTENSIONS, true);

            $dimensions = null;
            if ($isImage) {
                $fullPath = public_path('storage/'.$normalizedPath);
                if (file_exists($fullPath) && ! in_array($ext, ['svg'], true)) {
                    $imgInfo = @getimagesize($fullPath);
                    if ($imgInfo && isset($imgInfo[0], $imgInfo[1])) {
                        $dimensions = "{$imgInfo[0]} × {$imgInfo[1]} px";
                    }
                }
            }

            $meta = $metadata[$normalizedPath] ?? [];
            $title = ! empty($meta['title']) ? $meta['title'] : pathinfo($normalizedPath, PATHINFO_FILENAME);

            $collection->push([
                'path' => $normalizedPath,
                'name' => basename($normalizedPath),
                'title' => $title,
                'alt_text' => $meta['alt_text'] ?? '',
                'caption' => $meta['caption'] ?? '',
                'description' => $meta['description'] ?? '',
                'folder' => $folder,
                'url' => asset('storage/'.$normalizedPath),
                'size_bytes' => (int) $sizeBytes,
                'size_formatted' => $this->formatBytes((int) $sizeBytes),
                'last_modified' => (int) $lastModified,
                'last_modified_formatted' => $lastModified ? date('d M Y, h:i A', $lastModified) : 'Unknown',
                'extension' => strtoupper($ext),
                'is_image' => $isImage,
                'is_video' => $isVideo,
                'is_document' => $isDocument,
                'dimensions' => $dimensions,
            ]);
        }

        return $collection;
    }

    private function sanitizePath(?string $path): ?string
    {
        if ($path === null) {
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

    private function updateDatabaseReferences(string $oldPath, string $newPath): void
    {
        try {
            ProductImage::query()->where('path', $oldPath)->update(['path' => $newPath]);
            ProductVideo::query()->where('path', $oldPath)->update(['path' => $newPath]);
            Product::query()->where('meta_image', $oldPath)->update(['meta_image' => $newPath]);
            ProductVariant::query()->where('image', $oldPath)->update(['image' => $newPath]);
            ProductVariant::query()->where('image_2', $oldPath)->update(['image_2' => $newPath]);
            ProductVariant::query()->where('image_3', $oldPath)->update(['image_3' => $newPath]);
            ProductVariant::query()->where('image_4', $oldPath)->update(['image_4' => $newPath]);
            Category::query()->where('image', $oldPath)->update(['image' => $newPath]);
            Subcategory::query()->where('image', $oldPath)->update(['image' => $newPath]);
            Childcategory::query()->where('image', $oldPath)->update(['image' => $newPath]);
            Brand::query()->where('image', $oldPath)->update(['image' => $newPath]);
            Story::query()->where('media_path', $oldPath)->update(['media_path' => $newPath]);
            Story::query()->where('thumbnail_path', $oldPath)->update(['thumbnail_path' => $newPath]);
            Setting::query()->where('value', $oldPath)->update(['value' => $newPath]);
        } catch (\Throwable) {
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function loadMediaMetadata(): array
    {
        $setting = Setting::query()->where('key', 'media_library_metadata')->first();
        if (! $setting || blank($setting->value)) {
            return [];
        }

        $decoded = json_decode($setting->value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function saveMediaMetadata(string $path, array $meta): void
    {
        $all = $this->loadMediaMetadata();
        $all[$path] = array_merge($all[$path] ?? [], $meta);

        Setting::query()->updateOrCreate(
            ['key' => 'media_library_metadata'],
            ['value' => json_encode($all, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
        );
    }

    private function renameMetadataKey(string $oldPath, string $newPath): void
    {
        $all = $this->loadMediaMetadata();
        if (isset($all[$oldPath])) {
            $all[$newPath] = $all[$oldPath];
            unset($all[$oldPath]);
            Setting::query()->updateOrCreate(
                ['key' => 'media_library_metadata'],
                ['value' => json_encode($all, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
            );
        }
    }

    private function deleteMediaMetadata(string $path): void
    {
        $all = $this->loadMediaMetadata();
        if (isset($all[$path])) {
            unset($all[$path]);
            Setting::query()->updateOrCreate(
                ['key' => 'media_library_metadata'],
                ['value' => json_encode($all, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
            );
        }
    }

    private function unlinkDatabaseReferences(string $path): void
    {
        try {
            ProductImage::query()->where('path', $path)->delete();
            ProductVideo::query()->where('path', $path)->delete();
            Product::query()->where('meta_image', $path)->update(['meta_image' => null]);
            ProductVariant::query()->where('image_path', $path)->update(['image_path' => null]);
            Category::query()->where('image', $path)->update(['image' => null]);
            Subcategory::query()->where('image', $path)->update(['image' => null]);
            Childcategory::query()->where('image', $path)->update(['image' => null]);
            Brand::query()->where('image', $path)->update(['image' => null]);
            Story::query()->where('media_path', $path)->delete();
            Story::query()->where('thumbnail_path', $path)->update(['thumbnail_path' => null]);
            Setting::query()->where('value', $path)->delete();
        } catch (\Throwable) {
            // Fail gracefully if any table doesn't have the column
        }
    }

    private function formatBytes(int $bytes, int $precision = 1): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min((int) $pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }
}
