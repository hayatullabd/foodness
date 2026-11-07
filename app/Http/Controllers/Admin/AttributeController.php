<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AttributeController extends Controller
{
    public function index(Request $request): View
    {
        $query = Attribute::query()->withCount('values');

        if ($request->filled('q')) {
            $term = '%'.$request->string('q')->toString().'%';
            $query->where('name', 'like', $term)->orWhere('slug', 'like', $term);
        }

        $attributes = $query->orderBy('sort_order')->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.attributes.index', compact('attributes'));
    }

    public function create(): View
    {
        return view('admin.attributes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:attributes,slug'],
            'type' => ['required', 'string', 'in:button,color,select'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
            'values' => ['nullable', 'string'], // comma or newline separated initial values
            'initial_values' => ['nullable', 'string'],
        ]);

        $slug = $this->uniqueSlug($validated['slug'] ?? null, $validated['name']);

        $attribute = Attribute::query()->create([
            'name' => $validated['name'],
            'slug' => $slug,
            'type' => $validated['type'],
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        $valuesInput = $validated['values'] ?? $validated['initial_values'] ?? null;
        if (filled($valuesInput)) {
            $rawValues = preg_split('/[\r\n,]+/', (string) $valuesInput);
            $order = 0;
            foreach ($rawValues as $val) {
                $trimmed = trim($val);
                if ($trimmed !== '') {
                    AttributeValue::query()->create([
                        'attribute_id' => $attribute->id,
                        'name' => $trimmed,
                        'slug' => Str::slug($trimmed),
                        'sort_order' => $order++,
                    ]);
                }
            }
        }

        return redirect()
            ->route('admin.attributes.edit', $attribute)
            ->with('success', 'Attribute created successfully. You can now configure its terms/values.');
    }

    public function edit(Attribute $attribute): View
    {
        $attribute->load('values');

        return view('admin.attributes.edit', compact('attribute'));
    }

    public function update(Request $request, Attribute $attribute): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:attributes,slug,'.$attribute->id],
            'type' => ['required', 'string', 'in:button,color,select'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $attribute->update([
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['slug'] ?? null, $validated['name'], $attribute->id),
            'type' => $validated['type'],
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        return redirect()
            ->route('admin.attributes.index')
            ->with('success', 'Attribute updated successfully.');
    }

    public function destroy(Attribute $attribute): RedirectResponse
    {
        $attribute->delete();

        return redirect()
            ->route('admin.attributes.index')
            ->with('success', 'Attribute deleted successfully.');
    }

    public function storeValue(Request $request, Attribute $attribute): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'color_code' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $slug = filled($validated['slug'] ?? null) ? Str::slug($validated['slug']) : Str::slug($validated['name']);

        AttributeValue::query()->create([
            'attribute_id' => $attribute->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'color_code' => $validated['color_code'] ?? null,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        return redirect()->route('admin.attributes.edit', $attribute)->with('success', 'Value added to '.$attribute->name.'.');
    }

    public function destroyValue(Attribute $attribute, AttributeValue $value): RedirectResponse
    {
        abort_unless($value->attribute_id === $attribute->id, 404);

        $value->delete();

        return redirect()->route('admin.attributes.edit', $attribute)->with('success', 'Value removed.');
    }

    private function uniqueSlug(?string $slug, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $name) ?: 'attribute';
        $candidate = $base;
        $counter = 1;

        while (
            Attribute::query()
                ->where('slug', $candidate)
                ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $candidate = "{$base}-{$counter}";
            $counter++;
        }

        return $candidate;
    }
}
