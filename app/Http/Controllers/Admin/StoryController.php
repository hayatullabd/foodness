<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StoryController extends Controller
{
    public function index(): View
    {
        $stories = Story::query()
            ->orderBy('sort_order')
            ->latest('id')
            ->paginate(20);

        return view('admin.stories.index', compact('stories'));
    }

    public function create(): View
    {
        return view('admin.stories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'tag' => ['nullable', 'string', 'max:60'],
            'media_type' => ['required', 'in:image,video'],
            'media_file' => ['nullable', 'file', 'max:51200'],
            'media_url' => ['nullable', 'url', 'max:500'],
            'thumbnail_file' => ['nullable', 'image', 'max:5120'],
            'auto_thumbnail_data' => ['nullable', 'string'],
            'auto_thumbnail_url' => ['nullable', 'string', 'max:500'],
            'link_url' => ['nullable', 'string', 'max:500'],
            'link_text' => ['nullable', 'string', 'max:60'],
            'duration' => ['nullable', 'integer', 'min:1', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $mediaPath = null;
        if ($request->hasFile('media_file')) {
            $mediaPath = ImageUploadService::upload(
                $request->file('media_file'),
                'stories',
                $validated['title']
            );
        } elseif ($request->filled('media_file_media_path') && ImageUploadService::exists($request->input('media_file_media_path'))) {
            $mediaPath = ImageUploadService::sanitizePath($request->input('media_file_media_path'));
        } elseif (! empty($validated['media_url'])) {
            $mediaPath = $validated['media_url'];
        }

        if (empty($mediaPath)) {
            return back()->withInput()->withErrors(['media_file' => 'Please upload an image/video file or provide a valid media URL.']);
        }

        $thumbnailPath = null;
        if ($request->hasFile('thumbnail_file')) {
            $thumbnailPath = ImageUploadService::upload(
                $request->file('thumbnail_file'),
                'stories/thumbs',
                $validated['title'].'-thumb'
            );
        } elseif ($request->filled('thumbnail_file_media_path') && ImageUploadService::exists($request->input('thumbnail_file_media_path'))) {
            $thumbnailPath = ImageUploadService::sanitizePath($request->input('thumbnail_file_media_path'));
        } elseif ($request->filled('auto_thumbnail_data')) {
            $thumbnailPath = ImageUploadService::storeBase64(
                (string) $request->input('auto_thumbnail_data'),
                'stories/thumbs',
                $validated['title'].'-thumb'
            );
        } elseif ($request->filled('auto_thumbnail_url')) {
            $thumbnailPath = (string) $request->input('auto_thumbnail_url');
        } elseif (! empty($mediaPath)) {
            if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?|shorts)/|.*[?&]v=)|youtu\.be/)([^"&?/\s]{11})%i', $mediaPath, $match)) {
                $thumbnailPath = 'https://img.youtube.com/vi/'.$match[1].'/hqdefault.jpg';
            }
        }

        Story::query()->create([
            'title' => $validated['title'],
            'tag' => $validated['tag'] ?? null,
            'media_type' => $validated['media_type'],
            'media_path' => $mediaPath,
            'thumbnail_path' => $thumbnailPath,
            'link_url' => $validated['link_url'] ?? null,
            'link_text' => (! empty($validated['link_text'])) ? $validated['link_text'] : 'SHOP NOW',
            'duration' => (int) ((! empty($validated['duration'])) ? $validated['duration'] : ($validated['media_type'] === 'video' ? 15 : 5)),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.stories.index')
            ->with('success', 'Story created successfully.');
    }

    public function edit(Story $story): View
    {
        return view('admin.stories.edit', compact('story'));
    }

    public function update(Request $request, Story $story): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'tag' => ['nullable', 'string', 'max:60'],
            'media_type' => ['required', 'in:image,video'],
            'media_file' => ['nullable', 'file', 'max:51200'],
            'media_url' => ['nullable', 'url', 'max:500'],
            'thumbnail_file' => ['nullable', 'image', 'max:5120'],
            'auto_thumbnail_data' => ['nullable', 'string'],
            'auto_thumbnail_url' => ['nullable', 'string', 'max:500'],
            'link_url' => ['nullable', 'string', 'max:500'],
            'link_text' => ['nullable', 'string', 'max:60'],
            'duration' => ['nullable', 'integer', 'min:1', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $mediaPath = $story->media_path;
        if ($request->hasFile('media_file')) {
            if ($story->media_path && ! str_starts_with($story->media_path, 'http') && Storage::disk('public')->exists($story->media_path)) {
                Storage::disk('public')->delete($story->media_path);
            }
            $mediaPath = ImageUploadService::upload(
                $request->file('media_file'),
                'stories',
                $validated['title']
            );
        } elseif ($request->filled('media_file_media_path') && ImageUploadService::exists($request->input('media_file_media_path'))) {
            $mediaPath = ImageUploadService::sanitizePath($request->input('media_file_media_path'));
        } elseif (! empty($validated['media_url'])) {
            $mediaPath = $validated['media_url'];
        }

        $thumbnailPath = $story->thumbnail_path;
        if ($request->hasFile('thumbnail_file')) {
            if ($story->thumbnail_path && ! str_starts_with($story->thumbnail_path, 'http') && Storage::disk('public')->exists($story->thumbnail_path)) {
                Storage::disk('public')->delete($story->thumbnail_path);
            }
            $thumbnailPath = ImageUploadService::upload(
                $request->file('thumbnail_file'),
                'stories/thumbs',
                $validated['title'].'-thumb'
            );
        } elseif ($request->filled('thumbnail_file_media_path') && ImageUploadService::exists($request->input('thumbnail_file_media_path'))) {
            $thumbnailPath = ImageUploadService::sanitizePath($request->input('thumbnail_file_media_path'));
        } elseif ($request->filled('auto_thumbnail_data') && (empty($story->thumbnail_path) || $request->hasFile('media_file'))) {
            if ($story->thumbnail_path && ! str_starts_with($story->thumbnail_path, 'http') && Storage::disk('public')->exists($story->thumbnail_path)) {
                Storage::disk('public')->delete($story->thumbnail_path);
            }
            $thumbnailPath = ImageUploadService::storeBase64(
                (string) $request->input('auto_thumbnail_data'),
                'stories/thumbs',
                $validated['title'].'-thumb'
            );
        } elseif ($request->filled('auto_thumbnail_url') && empty($story->thumbnail_path)) {
            $thumbnailPath = (string) $request->input('auto_thumbnail_url');
        } elseif (! empty($mediaPath) && empty($thumbnailPath)) {
            if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?|shorts)/|.*[?&]v=)|youtu\.be/)([^"&?/\s]{11})%i', $mediaPath, $match)) {
                $thumbnailPath = 'https://img.youtube.com/vi/'.$match[1].'/hqdefault.jpg';
            }
        }

        $story->update([
            'title' => $validated['title'],
            'tag' => $validated['tag'] ?? null,
            'media_type' => $validated['media_type'],
            'media_path' => $mediaPath,
            'thumbnail_path' => $thumbnailPath,
            'link_url' => $validated['link_url'] ?? null,
            'link_text' => (! empty($validated['link_text'])) ? $validated['link_text'] : 'SHOP NOW',
            'duration' => (int) ((! empty($validated['duration'])) ? $validated['duration'] : ($validated['media_type'] === 'video' ? 15 : 5)),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.stories.index')
            ->with('success', 'Story updated successfully.');
    }

    public function destroy(Story $story): RedirectResponse
    {
        if ($story->media_path && ! str_starts_with($story->media_path, 'http') && Storage::disk('public')->exists($story->media_path)) {
            Storage::disk('public')->delete($story->media_path);
        }

        if ($story->thumbnail_path && ! str_starts_with($story->thumbnail_path, 'http') && Storage::disk('public')->exists($story->thumbnail_path)) {
            Storage::disk('public')->delete($story->thumbnail_path);
        }

        $story->delete();

        return redirect()
            ->route('admin.stories.index')
            ->with('success', 'Story deleted successfully.');
    }

    public function toggle(Story $story, Request $request): RedirectResponse|JsonResponse
    {
        $story->update(['is_active' => ! $story->is_active]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => $story->is_active,
                'message' => $story->is_active ? 'Story activated' : 'Story deactivated',
            ]);
        }

        return back()->with('success', 'Status updated successfully.');
    }
}
