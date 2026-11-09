@php($childcategory = $childcategory ?? null)

<div>
    <label class="admin-label" for="subcategory_id">Subcategory</label>
    <select id="subcategory_id" name="subcategory_id" required class="field">
        <option value="">Select subcategory</option>
        @foreach ($subcategories as $subcategory)
            <option value="{{ $subcategory->id }}" @selected(old('subcategory_id', $childcategory?->subcategory_id) == $subcategory->id)>
                {{ $subcategory->name }} ({{ $subcategory->category?->name }})
            </option>
        @endforeach
    </select>
    @error('subcategory_id') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
</div>
<div>
    <label class="admin-label" for="name">Name</label>
    <input id="name" name="name" type="text" value="{{ old('name', $childcategory?->name) }}" required class="field">
    @error('name') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
</div>
<div>
    <label class="admin-label" for="slug">Slug</label>
    <input id="slug" name="slug" type="text" value="{{ old('slug', $childcategory?->slug) }}" class="field" placeholder="auto from name">
</div>
<div>
    <label class="admin-label" for="sort_order">Sort order</label>
    <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $childcategory?->sort_order ?? 0) }}" class="field">
</div>
<div>
    @include('admin.partials.dropzone', [
        'name' => 'image',
        'id' => 'image',
        'label' => 'Childcategory Image',
        'current' => $childcategory?->image ? asset('storage/'.$childcategory->image) : null,
        'hint' => 'Recommended: 800 × 800 px (1:1) · PNG, JPG, WEBP · Max 4 MB',
        'maxHeight' => 'h-28',
    ])
    @error('image') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
</div>
<label class="flex items-center gap-2 text-sm">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $childcategory?->is_active ?? true))>
    Active
</label>
