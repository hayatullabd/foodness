@extends('layouts.admin')

@section('title', 'Configure Attribute — '.$attribute->name)
@section('heading', 'Attribute: '.$attribute->name)
@section('subheading', 'Configure terms/values for '.$attribute->name.'. These values will be used to generate product variations.')

@section('actions')
    <a href="{{ route('admin.attributes.index') }}" class="admin-btn-secondary">Back to Attributes</a>
@endsection

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Attribute Settings --}}
        <div class="lg:col-span-1">
            <form method="POST" action="{{ route('admin.attributes.update', $attribute) }}" class="admin-card p-6 space-y-4">
                @csrf
                @method('PUT')

                <h2 class="text-sm font-bold uppercase tracking-wider text-muted">Attribute Details</h2>

                <div>
                    <label class="admin-label" for="name">Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $attribute->name) }}" required class="field">
                </div>

                <div>
                    <label class="admin-label" for="slug">Slug</label>
                    <input id="slug" name="slug" type="text" value="{{ old('slug', $attribute->slug) }}" class="field font-mono text-sm">
                </div>

                <div>
                    <label class="admin-label" for="type">Type</label>
                    <select id="type" name="type" required class="field">
                        <option value="button" @selected(old('type', $attribute->type) === 'button')>Button / Pill</option>
                        <option value="color" @selected(old('type', $attribute->type) === 'color')>Color Swatch</option>
                        <option value="select" @selected(old('type', $attribute->type) === 'select')>Dropdown Select</option>
                    </select>
                </div>

                <div>
                    <label class="flex items-center gap-2 text-sm font-medium text-ink cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $attribute->is_active)) class="rounded border-ink/20 text-sage focus:ring-sage">
                        <span>Active</span>
                    </label>
                </div>

                <div class="pt-2">
                    <button type="submit" class="admin-btn-primary w-full">Update Details</button>
                </div>
            </form>
        </div>

        {{-- Terms / Values Management --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Add New Value Box --}}
            <div class="admin-card p-5">
                <h2 class="text-sm font-bold text-ink mb-3">Add New Term / Value to "{{ $attribute->name }}"</h2>
                <form method="POST" action="{{ route('admin.attributes.values.store', $attribute) }}" class="grid gap-3 sm:grid-cols-4 items-end">
                    @csrf
                    <div class="sm:col-span-2">
                        <label class="admin-label">Value Name <span class="text-accent">*</span></label>
                        <input type="text" name="name" required class="field" placeholder="e.g. 128GB, Red, XL, etc.">
                    </div>

                    @if ($attribute->type === 'color')
                        <div>
                            <label class="admin-label">Color Code</label>
                            <div class="flex items-center gap-1.5">
                                <input type="color" id="picker_color" class="h-9 w-10 cursor-pointer rounded border border-ink/20 p-0.5" onchange="document.getElementById('input_color_code').value = this.value">
                                <input type="text" id="input_color_code" name="color_code" class="field text-xs font-mono" placeholder="#000000" oninput="document.getElementById('picker_color').value = this.value">
                            </div>
                        </div>
                    @else
                        <div>
                            <label class="admin-label">Sort Order</label>
                            <input type="number" name="sort_order" value="0" class="field">
                        </div>
                    @endif

                    <div>
                        <button type="submit" class="admin-btn-primary w-full">Add Value</button>
                    </div>
                </form>
            </div>

            {{-- Existing Values List --}}
            <div class="admin-card overflow-hidden">
                <div class="border-b border-ink/10 bg-mist px-4 py-3 flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-muted">Configured Values ({{ $attribute->values->count() }})</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-fog/50 text-xs font-semibold uppercase text-muted">
                            <tr>
                                <th class="px-4 py-2.5">Name</th>
                                <th class="px-4 py-2.5">Slug</th>
                                @if ($attribute->type === 'color')
                                    <th class="px-4 py-2.5">Preview</th>
                                @endif
                                <th class="px-4 py-2.5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink/5 bg-white">
                            @forelse ($attribute->values as $val)
                                <tr class="hover:bg-fog/30">
                                    <td class="px-4 py-2.5 font-medium text-ink">{{ $val->name }}</td>
                                    <td class="px-4 py-2.5 text-xs font-mono text-muted">{{ $val->slug }}</td>
                                    @if ($attribute->type === 'color')
                                        <td class="px-4 py-2.5">
                                            @if ($val->color_code)
                                                <span class="inline-flex items-center gap-1.5">
                                                    <span class="h-4 w-4 rounded-full border border-ink/20" style="background-color: {{ $val->color_code }}"></span>
                                                    <span class="text-xs font-mono text-muted">{{ $val->color_code }}</span>
                                                </span>
                                            @else
                                                <span class="text-xs text-muted">—</span>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="px-4 py-2.5 text-right">
                                        <form method="POST" action="{{ route('admin.attributes.values.destroy', [$attribute, $val]) }}" class="inline" onsubmit="return confirm('Remove value {{ $val->name }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-accent hover:underline">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-xs text-muted">
                                        No values added yet. Use the form above to add terms for this attribute.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
