@php($color = $color ?? null)

<div>
    <label class="admin-label" for="name">Name</label>
    <input id="name" name="name" type="text" value="{{ old('name', $color?->name) }}" required class="field">
    @error('name') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
</div>
<div>
    <label class="admin-label" for="slug">Slug</label>
    <input id="slug" name="slug" type="text" value="{{ old('slug', $color?->slug) }}" class="field" placeholder="auto from name">
</div>
<div>
    <label class="admin-label" for="hex">Hex</label>
    <input id="hex" name="hex" type="text" value="{{ old('hex', $color?->hex) }}" class="field" placeholder="#3d5a4c">
</div>
<div>
    <label class="admin-label" for="sort_order">Sort order</label>
    <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $color?->sort_order ?? 0) }}" class="field">
</div>
<label class="flex items-center gap-2 text-sm">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $color?->is_active ?? true))>
    Active
</label>
