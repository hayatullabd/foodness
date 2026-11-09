@php($category = $category ?? null)

<div>
    <label class="admin-label" for="name">Name</label>
    <input id="name" name="name" type="text" value="{{ old('name', $category?->name) }}" required class="field">
    @error('name') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
</div>
<div>
    <label class="admin-label" for="slug">Slug</label>
    <input id="slug" name="slug" type="text" value="{{ old('slug', $category?->slug) }}" class="field" placeholder="auto from name">
    @error('slug') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
</div>
<div>
    <label class="admin-label" for="sort_order">Sort order</label>
    <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $category?->sort_order ?? 0) }}" class="field">
</div>
<div>
    @include('admin.partials.dropzone', [
        'name' => 'image',
        'id' => 'image',
        'label' => 'Category Image',
        'current' => $category?->image ? asset('storage/'.$category->image) : null,
        'hint' => 'Recommended: 800 × 800 px (1:1) · PNG, JPG, WEBP · Max 4 MB',
        'maxHeight' => 'h-28',
    ])
    @error('image') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
</div>
<label class="flex items-center gap-2 text-sm">
    <input type="checkbox" name="status" value="1" @checked(old('status', $category?->status ?? true))>
    Active
</label>
<label class="flex items-center gap-2 text-sm">
    <input type="checkbox" name="is_front" value="1" @checked(old('is_front', $category?->is_front))>
    Show on front
</label>
