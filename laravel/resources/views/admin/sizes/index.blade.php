@extends('layouts.admin')

@section('title', 'Sizes')
@section('heading', 'Sizes')

@section('actions')
    <a href="{{ route('admin.sizes.create') }}" class="admin-btn-primary">+ Add Size</a>
@endsection

@section('content')
    <div class="admin-card overflow-hidden">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th class="px-3 py-2 font-medium">SL</th>
                        <th class="px-3 py-2 font-medium">Name</th>
                        <th class="px-3 py-2 font-medium">Status</th>
                        <th class="px-3 py-2 font-medium">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sizes as $size)
                        <tr class="border-t border-ink/5">
                            <td class="px-3 py-2 text-muted">{{ $sizes->firstItem() + $loop->index }}</td>
                            <td class="px-3 py-2 font-medium">{{ $size->name }}</td>
                            <td class="px-3 py-2">{{ $size->is_active ? 'Active' : 'Inactive' }}</td>
                            <td class="px-3 py-2">
                                <a href="{{ route('admin.sizes.edit', $size) }}" class="admin-btn-secondary">Edit</a>
                                <form method="POST" action="{{ route('admin.sizes.destroy', $size) }}" class="ml-3 inline" onsubmit="return confirm('Delete this size?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="admin-btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="admin-empty-cell"><div class="admin-empty"><p class="admin-empty-title">No sizes yet.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-ink/10 px-3 py-2">{{ $sizes->links() }}</div>
    </div>
@endsection
