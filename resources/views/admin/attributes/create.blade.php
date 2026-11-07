@extends('layouts.admin')

@section('title', 'Add Attribute')
@section('heading', 'Add Product Attribute')
@section('subheading', 'Create a new variation attribute like Storage, Color, RAM, Flavor, or Size.')

@section('actions')
    <a href="{{ route('admin.attributes.index') }}" class="admin-btn-secondary">Cancel</a>
@endsection

@section('content')
    <div class="mx-auto max-w-2xl">
        <form method="POST" action="{{ route('admin.attributes.store') }}" class="admin-card p-6 space-y-5">
            @csrf

            <div>
                <label class="admin-label" for="name">Attribute Name <span class="text-accent">*</span></label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name') }}"
                    required
                    class="field"
                    placeholder="e.g. Storage, RAM, Color, Flavor, Material"
                >
                <p class="mt-1 text-[11px] text-muted">The name shown on the storefront and product variation manager.</p>
                @error('name') <p class="mt-1 text-xs text-accent">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="admin-label" for="type">Display Type <span class="text-accent">*</span></label>
                    <select id="type" name="type" required class="field">
                        <option value="button" @selected(old('type') === 'button')>Button / Pill (e.g. Size, Storage)</option>
                        <option value="color" @selected(old('type') === 'color')>Color Swatch (with color preview)</option>
                        <option value="select" @selected(old('type') === 'select')>Dropdown Select Menu</option>
                    </select>
                </div>

                <div>
                    <label class="admin-label" for="slug">Slug <span class="font-normal text-muted">(optional)</span></label>
                    <input
                        id="slug"
                        name="slug"
                        type="text"
                        value="{{ old('slug') }}"
                        class="field font-mono text-sm"
                        placeholder="e.g. storage"
                    >
                </div>
            </div>

            <div>
                <label class="admin-label" for="values">Initial Values / Terms <span class="font-normal text-muted">(comma or line separated)</span></label>
                <textarea
                    id="values"
                    name="values"
                    rows="3"
                    class="field"
                    placeholder="e.g. 128GB, 256GB, 512GB (or Black, White, Silver)"
                >{{ old('values') }}</textarea>
                <p class="mt-1 text-[11px] text-muted">You can also add, edit, or set color codes for individual values on the next page.</p>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <label class="flex items-center gap-2 text-sm font-medium text-ink cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-ink/20 text-sage focus:ring-sage">
                    <span>Enable this attribute</span>
                </label>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-ink/10 pt-4">
                <a href="{{ route('admin.attributes.index') }}" class="admin-btn-secondary">Cancel</a>
                <button type="submit" class="admin-btn-primary">Save Attribute</button>
            </div>
        </form>
    </div>
@endsection
