@extends('layouts.admin')

@section('title', 'Attributes')
@section('heading', 'Product Attributes')
@section('subheading', 'Manage attributes like Color, Size, Storage, RAM, Weight, Material for product variations.')

@section('actions')
    <a href="{{ route('admin.attributes.create') }}" class="admin-btn-primary">+ Add Attribute</a>
@endsection

@section('content')
    <div class="mb-4 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <form method="GET" action="{{ route('admin.attributes.index') }}" class="flex items-center gap-2">
            <input
                type="search"
                name="q"
                value="{{ request('q') }}"
                placeholder="Search attributes..."
                class="field sm:w-64"
            >
            <button type="submit" class="admin-btn-secondary">Search</button>
            @if (request()->filled('q'))
                <a href="{{ route('admin.attributes.index') }}" class="text-xs text-muted hover:underline">Clear</a>
            @endif
        </form>
    </div>

    <div class="admin-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-mist text-xs font-semibold uppercase tracking-wider text-muted">
                    <tr>
                        <th class="px-4 py-3">Attribute</th>
                        <th class="px-4 py-3">Slug</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Terms / Values</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink/5 bg-white">
                    @forelse ($attributes as $attribute)
                        <tr class="hover:bg-fog/40">
                            <td class="px-4 py-3 font-semibold text-ink">
                                <a href="{{ route('admin.attributes.edit', $attribute) }}" class="hover:text-accent">
                                    {{ $attribute->name }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-xs text-muted font-mono">{{ $attribute->slug }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded bg-fog px-2 py-0.5 text-xs font-medium text-ink">
                                    {{ ucfirst($attribute->type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.attributes.edit', $attribute) }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-sage hover:underline">
                                    <span class="rounded-full bg-sage/10 px-2 py-0.5 font-bold text-sage">{{ $attribute->values_count }}</span>
                                    <span>Configure Terms</span>
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $attribute->is_active ? 'bg-sage/15 text-sage' : 'bg-ink/10 text-muted' }}">
                                    {{ $attribute->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-xs">
                                <a href="{{ route('admin.attributes.edit', $attribute) }}" class="font-medium text-sage hover:underline">Configure</a>
                                <span class="mx-1 text-ink/20">|</span>
                                <form method="POST" action="{{ route('admin.attributes.destroy', $attribute) }}" class="inline" onsubmit="return confirm('Delete this attribute and all its values?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-medium text-accent hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-muted">
                                No attributes found. Click <strong>+ Add Attribute</strong> to create your first attribute.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($attributes->hasPages())
            <div class="border-t border-ink/10 px-4 py-3">{{ $attributes->links() }}</div>
        @endif
    </div>
@endsection
