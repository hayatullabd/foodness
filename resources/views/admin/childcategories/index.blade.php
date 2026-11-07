@extends('layouts.admin')

@section('title', 'Childcategories')
@section('heading', 'Childcategories')

@section('actions')
    <a href="{{ route('admin.childcategories.create') }}" class="btn-primary">+ Add Childcategory</a>
@endsection

@section('content')
    <div class="border border-ink/10 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-mist text-muted">
                    <tr>
                        <th class="px-3 py-2 font-medium">SL</th>
                        <th class="px-3 py-2 font-medium">Name</th>
                        <th class="px-3 py-2 font-medium">Subcategory</th>
                        <th class="px-3 py-2 font-medium">Products</th>
                        <th class="px-3 py-2 font-medium">Status</th>
                        <th class="px-3 py-2 font-medium">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($childcategories as $childcategory)
                        <tr class="border-t border-ink/5">
                            <td class="px-3 py-2 text-muted">{{ $childcategories->firstItem() + $loop->index }}</td>
                            <td class="px-3 py-2 font-medium">{{ $childcategory->name }}</td>
                            <td class="px-3 py-2">
                                {{ $childcategory->subcategory?->name }}
                                <div class="text-xs text-muted">{{ $childcategory->subcategory?->category?->name }}</div>
                            </td>
                            <td class="px-3 py-2">{{ $childcategory->products_count }}</td>
                            <td class="px-3 py-2">{{ $childcategory->is_active ? 'Active' : 'Inactive' }}</td>
                            <td class="px-3 py-2">
                                <a href="{{ route('admin.childcategories.edit', $childcategory) }}" class="text-sage hover:underline">Edit</a>
                                <form method="POST" action="{{ route('admin.childcategories.destroy', $childcategory) }}" class="ml-3 inline" onsubmit="return confirm('Delete this child category?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-accent hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-muted">No child categories yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-ink/10 px-3 py-2">{{ $childcategories->links() }}</div>
    </div>
@endsection
