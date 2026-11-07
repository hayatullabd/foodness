@extends('layouts.admin')

@section('title', 'Colors')
@section('heading', 'Colors')

@section('actions')
    <a href="{{ route('admin.colors.create') }}" class="btn-primary">+ Add Color</a>
@endsection

@section('content')
    <div class="border border-ink/10 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-mist text-muted">
                    <tr>
                        <th class="px-3 py-2 font-medium">SL</th>
                        <th class="px-3 py-2 font-medium">Color</th>
                        <th class="px-3 py-2 font-medium">Name</th>
                        <th class="px-3 py-2 font-medium">Status</th>
                        <th class="px-3 py-2 font-medium">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($colors as $color)
                        <tr class="border-t border-ink/5">
                            <td class="px-3 py-2 text-muted">{{ $colors->firstItem() + $loop->index }}</td>
                            <td class="px-3 py-2">
                                <span class="inline-block h-6 w-6 rounded border border-ink/10" style="background: {{ $color->hex ?: '#ccc' }}"></span>
                            </td>
                            <td class="px-3 py-2 font-medium">{{ $color->name }} @if($color->hex)<span class="text-xs text-muted">{{ $color->hex }}</span>@endif</td>
                            <td class="px-3 py-2">{{ $color->is_active ? 'Active' : 'Inactive' }}</td>
                            <td class="px-3 py-2">
                                <a href="{{ route('admin.colors.edit', $color) }}" class="text-sage hover:underline">Edit</a>
                                <form method="POST" action="{{ route('admin.colors.destroy', $color) }}" class="ml-3 inline" onsubmit="return confirm('Delete this color?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-accent hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-muted">No colors yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-ink/10 px-3 py-2">{{ $colors->links() }}</div>
    </div>
@endsection
