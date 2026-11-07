<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Childcategory;
use App\Models\Subcategory;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ChildcategoryController extends Controller
{
    public function index(): View
    {
        $childcategories = Childcategory::query()
            ->with(['subcategory.category'])
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.childcategories.index', compact('childcategories'));
    }

    public function create(): View
    {
        return view('admin.childcategories.create', [
            'subcategories' => Subcategory::query()->with('category')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        Childcategory::query()->create([
            'subcategory_id' => $validated['subcategory_id'],
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['slug'] ?? null, $validated['name']),
            'image' => $this->storeImage($request),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect()
            ->route('admin.childcategories.index')
            ->with('success', 'Child category created successfully.');
    }

    public function edit(Childcategory $childcategory): View
    {
        return view('admin.childcategories.edit', [
            'childcategory' => $childcategory,
            'subcategories' => Subcategory::query()->with('category')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Childcategory $childcategory): RedirectResponse
    {
        $validated = $this->validated($request, $childcategory);

        if ($request->hasFile('image')) {
            if ($childcategory->image) {
                Storage::disk('public')->delete($childcategory->image);
            }
            $childcategory->image = ImageUploadService::upload(
                $request->file('image'),
                'childcategories',
                $validated['name']
            );
        } elseif ($request->filled('image_media_path') && ImageUploadService::exists($request->input('image_media_path'))) {
            $childcategory->image = ImageUploadService::sanitizePath($request->input('image_media_path'));
        }

        $childcategory->update([
            'subcategory_id' => $validated['subcategory_id'],
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['slug'] ?? null, $validated['name'], $childcategory->id),
            'image' => $childcategory->image,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect()
            ->route('admin.childcategories.index')
            ->with('success', 'Child category updated successfully.');
    }

    public function destroy(Childcategory $childcategory): RedirectResponse
    {
        if ($childcategory->products()->exists()) {
            return back()->with('error', 'Cannot delete a child category that has products.');
        }

        if ($childcategory->image) {
            Storage::disk('public')->delete($childcategory->image);
        }

        $childcategory->delete();

        return redirect()
            ->route('admin.childcategories.index')
            ->with('success', 'Child category deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Childcategory $childcategory = null): array
    {
        $slugRule = ['nullable', 'string', 'max:255', 'unique:childcategories,slug'];
        if ($childcategory) {
            $slugRule[3] = 'unique:childcategories,slug,'.$childcategory->id;
        }

        return $request->validate([
            'subcategory_id' => ['required', 'exists:subcategories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => $slugRule,
            'image' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function storeImage(Request $request): ?string
    {
        if ($request->hasFile('image')) {
            return ImageUploadService::upload(
                $request->file('image'),
                'childcategories',
                $request->input('name')
            );
        }

        if ($request->filled('image_media_path') && ImageUploadService::exists($request->input('image_media_path'))) {
            return ImageUploadService::sanitizePath($request->input('image_media_path'));
        }

        return null;
    }

    private function uniqueSlug(?string $slug, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $name) ?: 'childcategory';
        $candidate = $base;
        $suffix = 1;

        while (
            Childcategory::query()
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->where('slug', $candidate)
                ->exists()
        ) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
