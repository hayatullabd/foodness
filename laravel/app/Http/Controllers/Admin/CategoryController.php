<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $categories = Category::query()
            ->withCount('products')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where('name', 'like', $term);
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:categories,slug'],
            'image' => ['nullable', 'image', 'max:2048'],
            'status' => ['nullable', 'boolean'],
            'is_front' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['name']);
        if (Category::query()->where('slug', $slug)->exists()) {
            $slug .= '-'.Str::lower(Str::random(4));
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = ImageUploadService::upload(
                $request->file('image'),
                'categories',
                $validated['name']
            );
        } elseif ($request->filled('image_media_path') && ImageUploadService::exists($request->input('image_media_path'))) {
            $imagePath = ImageUploadService::sanitizePath($request->input('image_media_path'));
        }

        Category::query()->create([
            'name' => $validated['name'],
            'slug' => $slug,
            'image' => $imagePath,
            'status' => $request->boolean('status', true),
            'is_front' => $request->boolean('is_front'),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:categories,slug,'.$category->id],
            'image' => ['nullable', 'image', 'max:2048'],
            'status' => ['nullable', 'boolean'],
            'is_front' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['name']);

        if ($request->hasFile('image')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $category->image = ImageUploadService::upload(
                $request->file('image'),
                'categories',
                $validated['name']
            );
        } elseif ($request->filled('image_media_path') && ImageUploadService::exists($request->input('image_media_path'))) {
            $category->image = ImageUploadService::sanitizePath($request->input('image_media_path'));
        }

        $category->update([
            'name' => $validated['name'],
            'slug' => $slug,
            'image' => $category->image,
            'status' => $request->boolean('status'),
            'is_front' => $request->boolean('is_front'),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'Cannot delete a category that has products.');
        }

        if ($category->subcategories()->exists()) {
            return back()->with('error', 'Cannot delete a category that has subcategories.');
        }

        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    public function toggle(Request $request, Category $category): RedirectResponse|JsonResponse
    {
        $category->update(['status' => ! $category->status]);

        $message = $category->status ? 'Category activated successfully.' : 'Category deactivated successfully.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'status' => (bool) $category->status,
                'status_label' => $category->status ? 'Active' : 'Inactive',
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }
}
