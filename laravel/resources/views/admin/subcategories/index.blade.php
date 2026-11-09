@extends('layouts.admin')

@section('title', 'Subcategories')
@section('heading', 'Subcategories')

@section('actions')
    <a href="{{ route('admin.subcategories.create') }}" class="admin-btn-primary">+ Add Subcategory</a>
@endsection

@section('content')
    <div class="admin-card overflow-hidden">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th class="px-3 py-2 font-medium">SL</th>
                        <th class="px-3 py-2 font-medium">Name</th>
                        <th class="px-3 py-2 font-medium">Category</th>
                        <th class="px-3 py-2 font-medium">Products</th>
                        <th class="px-3 py-2 font-medium">Status</th>
                        <th class="px-3 py-2 font-medium">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($subcategories as $subcategory)
                        <tr class="border-t border-ink/5">
                            <td class="px-3 py-2 text-muted">{{ $subcategories->firstItem() + $loop->index }}</td>
                            <td class="px-3 py-2 font-medium">{{ $subcategory->name }}</td>
                            <td class="px-3 py-2">{{ $subcategory->category?->name }}</td>
                            <td class="px-3 py-2">{{ $subcategory->products_count }}</td>
                            <td class="px-3 py-2">{{ $subcategory->is_active ? 'Active' : 'Inactive' }}</td>
                            <td class="px-3 py-2">
                                <a href="{{ route('admin.subcategories.edit', $subcategory) }}" class="admin-btn-secondary">Edit</a>
                                <form method="POST" action="{{ route('admin.subcategories.destroy', $subcategory) }}" class="ml-3 inline" onsubmit="return confirm('Delete this subcategory?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="admin-btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="admin-empty-cell"><div class="admin-empty"><p class="admin-empty-title">No subcategories yet.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-ink/10 px-3 py-2">{{ $subcategories->links() }}</div>
    </div>
@endsection
