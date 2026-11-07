@extends('layouts.admin')

@section('title', 'IP blocks')
@section('heading', 'IP blocks')
@section('subheading', 'Block website access from specific IP addresses')

@section('content')
    <div class="grid gap-3 lg:grid-cols-5">
        <div class="admin-card admin-card-body lg:col-span-2">
            <h2 class="mb-4 text-sm font-semibold text-ink">Block a new IP</h2>
            <form method="POST" action="{{ route('admin.ip-blocks.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="admin-label" for="ip">IP address *</label>
                    <input id="ip" type="text" name="ip" value="{{ old('ip') }}" class="field" placeholder="e.g. 192.168.0.1" required>
                    @error('ip')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="admin-label" for="reason">Reason *</label>
                    <textarea id="reason" name="reason" rows="3" class="field" placeholder="Fake order" required>{{ old('reason') }}</textarea>
                    @error('reason')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="inline-flex w-full items-center justify-center rounded-md bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">
                    Block IP
                </button>
            </form>
            <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                Blocked IPs cannot access the site. Use with care.
            </div>
        </div>

        <div class="border border-ink/10 bg-white lg:col-span-3">
            <div class="border-b border-ink/10 px-3.5 py-3">
                <h2 class="text-sm font-semibold text-ink">
                    Blocked IP list
                    <span class="ml-1 inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-red-600 px-2 text-xs text-white">{{ $ipBlocks->total() }}</span>
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-mist text-muted">
                        <tr>
                            <th class="px-3 py-2 font-medium">#</th>
                            <th class="px-3 py-2 font-medium">IP</th>
                            <th class="px-3 py-2 font-medium">Reason</th>
                            <th class="px-3 py-2 font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ipBlocks as $block)
                            <tr class="border-t border-ink/5">
                                <td class="px-3 py-2 text-muted">{{ $ipBlocks->firstItem() + $loop->index }}</td>
                                <td class="px-3 py-2 font-medium">{{ $block->ip }}</td>
                                <td class="px-3 py-2">{{ $block->reason }}</td>
                                <td class="px-3 py-2">
                                    <form method="POST" action="{{ route('admin.ip-blocks.destroy', $block) }}" onsubmit="return confirm('Unblock this IP?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-accent hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-12 text-center text-muted">
                                    No blocked IPs
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-ink/10 px-3 py-2">{{ $ipBlocks->links() }}</div>
        </div>
    </div>
@endsection