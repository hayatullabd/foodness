@extends('layouts.admin')

@section('title', 'Brands')
@section('heading', 'Brands')
@section('subheading', 'Product brand catalogue')

@section('actions')
    <a href="{{ route('admin.brands.create') }}" class="admin-btn-primary">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
        Add Brand
    </a>
@endsection

@section('content')
    <div class="admin-card overflow-hidden">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>SL</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Products</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($brands as $brand)
                        <tr>
                            <td class="text-muted tabular-nums">{{ $brands->firstItem() + $loop->index }}</td>
                            <td>
                                @if ($brand->image)
                                    <img src="{{ asset('storage/'.$brand->image) }}" alt="" class="h-10 w-10 rounded-md object-cover border border-ink/10">
                                @else
                                    <div class="flex h-10 w-10 items-center justify-center rounded-md bg-mist text-xs text-muted border border-ink/10">N/A</div>
                                @endif
                            </td>
                            <td class="font-medium text-ink">{{ $brand->name }}</td>
                            <td>{{ $brand->products_count }}</td>
                            <td>
                                <span @class([
                                    'admin-badge-pill',
                                    'admin-badge-success' => $brand->is_active,
                                    'admin-badge-neutral' => ! $brand->is_active,
                                ])>
                                    {{ $brand->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="admin-table-actions justify-end">
                                    <a href="{{ route('admin.brands.edit', $brand) }}" class="admin-btn-secondary">Edit</a>
                                    <form method="POST" action="{{ route('admin.brands.destroy', $brand) }}" class="inline" onsubmit="return confirm('Delete this brand?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="admin-btn-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="admin-empty-cell">
                                <div class="admin-empty">
                                    <p class="admin-empty-title">No brands yet</p>
                                    <p class="admin-empty-hint">Add your first brand to organize products.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($brands->hasPages() || $brands->total())
            <div class="orders-footer">
                <div class="text-xs text-muted">
                    @if ($brands->total())
                        Showing {{ $brands->firstItem() }}–{{ $brands->lastItem() }} of {{ $brands->total() }}
                    @endif
                </div>
                <div>{{ $brands->links() }}</div>
            </div>
        @endif
    </div>
@endsection