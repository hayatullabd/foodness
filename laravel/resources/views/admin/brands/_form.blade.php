@php($brand = $brand ?? null)

<div>
    <label class="admin-label" for="name">Name</label>
    <input id="name" name="name" type="text" value="{{ old('name', $brand?->name) }}" required class="field">
    @error('name') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
</div>
<div>
    <label class="admin-label" for="slug">Slug</label>
    <input id="slug" name="slug" type="text" value="{{ old('slug', $brand?->slug) }}" class="field" placeholder="auto from name">
</div>
<div>
    <label class="admin-label" for="sort_order">Sort order</label>
    <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $brand?->sort_order ?? 0) }}" class="field">
</div>
<div>
    @include('admin.partials.dropzone', [
        'name' => 'image',
        'id' => 'image',
        'label' => 'Brand Logo / Image',
        'current' => $brand?->image ? asset('storage/'.$brand->image) : null,
        'hint' => 'Recommended: 400 × 400 px · PNG, JPG, WEBP · Max 4 MB',
        'maxHeight' => 'h-28',
    ])
    @error('image') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
</div>
<label class="flex items-center gap-2 text-sm">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $brand?->is_active ?? true))>
    Active
</label>
