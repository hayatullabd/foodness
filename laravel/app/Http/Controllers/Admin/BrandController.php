<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(): View
    {
        $brands = Brand::query()
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.brands.index', compact('brands'));
    }

    public function create(): View
    {
        return view('admin.brands.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        Brand::query()->create([
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['slug'] ?? null, $validated['name']),
            'image' => $this->storeImage($request),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Brand created successfully.');
    }

    public function edit(Brand $brand): View
    {
        return view('admin.brands.edit', compact('brand'));
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $validated = $this->validated($request, $brand);

        if ($request->hasFile('image')) {
            if ($brand->image) {
                Storage::disk('public')->delete($brand->image);
            }
            $brand->image = ImageUploadService::upload(
                $request->file('image'),
                'brands',
                $validated['name']
            );
        } elseif ($request->filled('image_media_path') && ImageUploadService::exists($request->input('image_media_path'))) {
            $brand->image = ImageUploadService::sanitizePath($request->input('image_media_path'));
        }

        $brand->update([
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['slug'] ?? null, $validated['name'], $brand->id),
            'image' => $brand->image,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Brand updated successfully.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        if ($brand->products()->exists()) {
            return back()->with('error', 'Cannot delete a brand that has products.');
        }

        if ($brand->image) {
            Storage::disk('public')->delete($brand->image);
        }

        $brand->delete();

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Brand deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Brand $brand = null): array
    {
        $slugRule = ['nullable', 'string', 'max:255', 'unique:brands,slug'];
        if ($brand) {
            $slugRule[3] = 'unique:brands,slug,'.$brand->id;
        }

        return $request->validate([
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
                'brands',
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
        $base = Str::slug($slug ?: $name) ?: 'brand';
        $candidate = $base;
        $suffix = 1;

        while (
            Brand::query()
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
