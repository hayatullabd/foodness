<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Subcategory;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SubcategoryController extends Controller
{
    public function index(): View
    {
        $subcategories = Subcategory::query()
            ->with('category')
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.subcategories.index', compact('subcategories'));
    }

    public function create(): View
    {
        return view('admin.subcategories.create', [
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        Subcategory::query()->create([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['slug'] ?? null, $validated['name']),
            'image' => $this->storeImage($request),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect()
            ->route('admin.subcategories.index')
            ->with('success', 'Subcategory created successfully.');
    }

    public function edit(Subcategory $subcategory): View
    {
        return view('admin.subcategories.edit', [
            'subcategory' => $subcategory,
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Subcategory $subcategory): RedirectResponse
    {
        $validated = $this->validated($request, $subcategory);

        if ($request->hasFile('image')) {
            if ($subcategory->image) {
                Storage::disk('public')->delete($subcategory->image);
            }
            $subcategory->image = ImageUploadService::upload(
                $request->file('image'),
                'subcategories',
                $validated['name']
            );
        } elseif ($request->filled('image_media_path') && ImageUploadService::exists($request->input('image_media_path'))) {
            $subcategory->image = ImageUploadService::sanitizePath($request->input('image_media_path'));
        }

        $subcategory->update([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['slug'] ?? null, $validated['name'], $subcategory->id),
            'image' => $subcategory->image,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect()
            ->route('admin.subcategories.index')
            ->with('success', 'Subcategory updated successfully.');
    }

    public function destroy(Subcategory $subcategory): RedirectResponse
    {
        if ($subcategory->products()->exists() || $subcategory->childcategories()->exists()) {
            return back()->with('error', 'Cannot delete a subcategory that has child categories or products.');
        }

        if ($subcategory->image) {
            Storage::disk('public')->delete($subcategory->image);
        }

        $subcategory->delete();

        return redirect()
            ->route('admin.subcategories.index')
            ->with('success', 'Subcategory deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Subcategory $subcategory = null): array
    {
        $slugRule = ['nullable', 'string', 'max:255', 'unique:subcategories,slug'];
        if ($subcategory) {
            $slugRule[3] = 'unique:subcategories,slug,'.$subcategory->id;
        }

        return $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
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
                'subcategories',
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
        $base = Str::slug($slug ?: $name) ?: 'subcategory';
        $candidate = $base;
        $suffix = 1;

        while (
            Subcategory::query()
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
