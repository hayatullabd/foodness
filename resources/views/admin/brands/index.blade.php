@extends('layouts.admin')

@section('title', 'Brands')
@section('heading', 'Brands')

@section('actions')
    <a href="{{ route('admin.brands.create') }}" class="btn-primary">+ Add Brand</a>
@endsection

@section('content')
    <div class="border border-ink/10 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-mist text-muted">
                    <tr>
                        <th class="px-3 py-2 font-medium">SL</th>
                        <th class="px-3 py-2 font-medium">Image</th>
                        <th class="px-3 py-2 font-medium">Name</th>
                        <th class="px-3 py-2 font-medium">Products</th>
                        <th class="px-3 py-2 font-medium">Status</th>
                        <th class="px-3 py-2 font-medium">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($brands as $brand)
                        <tr class="border-t border-ink/5">
                            <td class="px-3 py-2 text-muted">{{ $brands->firstItem() + $loop->index }}</td>
                            <td class="px-3 py-2">
                                @if ($brand->image)
                                    <img src="{{ asset('storage/'.$brand->image) }}" alt="" class="h-10 w-10 object-cover">
                                @else
                                    <div class="flex h-10 w-10 items-center justify-center bg-mist text-xs text-muted">N/A</div>
                                @endif
                            </td>
                            <td class="px-3 py-2 font-medium">{{ $brand->name }}</td>
                            <td class="px-3 py-2">{{ $brand->products_count }}</td>
                            <td class="px-3 py-2">{{ $brand->is_active ? 'Active' : 'Inactive' }}</td>
                            <td class="px-3 py-2">
                                <a href="{{ route('admin.brands.edit', $brand) }}" class="text-sage hover:underline">Edit</a>
                                <form method="POST" action="{{ route('admin.brands.destroy', $brand) }}" class="ml-3 inline" onsubmit="return confirm('Delete this brand?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-accent hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-muted">No brands yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-ink/10 px-3 py-2">{{ $brands->links() }}</div>
    </div>
@endsection
