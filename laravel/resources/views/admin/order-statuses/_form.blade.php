<div>
    <label class="admin-label" for="section">Section *</label>
    <select id="section" name="section" class="field" required>
        <option value="orders" @selected(old('section', $orderStatus->section ?? ($defaultSection ?? 'orders')) === 'orders')>All Orders Section (Internal Lifecycle)</option>
        <option value="courier" @selected(old('section', $orderStatus->section ?? ($defaultSection ?? 'orders')) === 'courier')>Courier Section (Courier Delivery Lifecycle)</option>
    </select>
    <p class="mt-1 text-xs text-muted">All Order and Courier Order statuses are isolated into separate workflows.</p>
    @error('section')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>
<div>
    <label class="admin-label" for="name">Status name *</label>
    <input id="name" type="text" name="name" value="{{ old('name', $orderStatus->name ?? '') }}" class="field" required>
    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>
<div>
    <label class="admin-label" for="slug">Slug</label>
    <input id="slug" type="text" name="slug" value="{{ old('slug', $orderStatus->slug ?? '') }}" class="field" placeholder="processing">
    @error('slug')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>
<div>
    <label class="admin-label" for="sort_order">Sort order</label>
    <input id="sort_order" type="number" name="sort_order" value="{{ old('sort_order', $orderStatus->sort_order ?? 0) }}" class="field" min="0">
</div>
<label class="flex items-center gap-2 text-sm">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $orderStatus->is_active ?? true))>
    Active
</label>
