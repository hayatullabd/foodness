<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Color;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ColorController extends Controller
{
    public function index(): View
    {
        $colors = Color::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.colors.index', compact('colors'));
    }

    public function create(): View
    {
        return view('admin.colors.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        Color::query()->create([
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['slug'] ?? null, $validated['name']),
            'hex' => $validated['hex'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect()
            ->route('admin.colors.index')
            ->with('success', 'Color created successfully.');
    }

    public function edit(Color $color): View
    {
        return view('admin.colors.edit', compact('color'));
    }

    public function update(Request $request, Color $color): RedirectResponse
    {
        $validated = $this->validated($request, $color);

        $color->update([
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['slug'] ?? null, $validated['name'], $color->id),
            'hex' => $validated['hex'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect()
            ->route('admin.colors.index')
            ->with('success', 'Color updated successfully.');
    }

    public function destroy(Color $color): RedirectResponse
    {
        if ($color->variants()->exists()) {
            return back()->with('error', 'Cannot delete a color used by product variants.');
        }

        $color->delete();

        return redirect()
            ->route('admin.colors.index')
            ->with('success', 'Color deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Color $color = null): array
    {
        $slugRule = ['nullable', 'string', 'max:255', 'unique:colors,slug'];
        if ($color) {
            $slugRule[3] = 'unique:colors,slug,'.$color->id;
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => $slugRule,
            'hex' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function uniqueSlug(?string $slug, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $name) ?: 'color';
        $candidate = $base;
        $suffix = 1;

        while (
            Color::query()
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
