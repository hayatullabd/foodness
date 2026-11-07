<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Size;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SizeController extends Controller
{
    public function index(): View
    {
        $sizes = Size::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.sizes.index', compact('sizes'));
    }

    public function create(): View
    {
        return view('admin.sizes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        Size::query()->create([
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['slug'] ?? null, $validated['name']),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect()
            ->route('admin.sizes.index')
            ->with('success', 'Size created successfully.');
    }

    public function edit(Size $size): View
    {
        return view('admin.sizes.edit', compact('size'));
    }

    public function update(Request $request, Size $size): RedirectResponse
    {
        $validated = $this->validated($request, $size);

        $size->update([
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['slug'] ?? null, $validated['name'], $size->id),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect()
            ->route('admin.sizes.index')
            ->with('success', 'Size updated successfully.');
    }

    public function destroy(Size $size): RedirectResponse
    {
        if ($size->variants()->exists()) {
            return back()->with('error', 'Cannot delete a size used by product variants.');
        }

        $size->delete();

        return redirect()
            ->route('admin.sizes.index')
            ->with('success', 'Size deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Size $size = null): array
    {
        $slugRule = ['nullable', 'string', 'max:255', 'unique:sizes,slug'];
        if ($size) {
            $slugRule[3] = 'unique:sizes,slug,'.$size->id;
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => $slugRule,
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function uniqueSlug(?string $slug, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $name) ?: 'size';
        $candidate = $base;
        $suffix = 1;

        while (
            Size::query()
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
