@extends('layouts.admin')

@section('title', 'Order statuses')
@section('heading', 'Order statuses')
@section('subheading', 'Manage isolated statuses for All Orders and Courier order lifecycles')

@section('actions')
    <a href="{{ route('admin.order-statuses.create', array_filter(['section' => $currentSection])) }}" class="admin-btn-primary">+ New status</a>
@endsection

@section('content')
    {{-- Section Isolation Filter Tabs --}}
    <div class="mb-4 flex flex-wrap items-center gap-2 border-b border-ink/10 pb-2">
        <a
            href="{{ route('admin.order-statuses.index', array_filter(['q' => request('q')])) }}"
            @class([
                'shrink-0 rounded-md px-3 py-1.5 text-xs font-semibold transition',
                'bg-ink text-white' => empty($currentSection),
                'border border-ink/10 bg-white text-muted hover:text-ink' => ! empty($currentSection),
            ])
        >
            All Sections
            <span class="ml-1 rounded-full bg-mist px-1.5 py-0.5 text-[10px] tabular-nums text-ink">{{ $allCount }}</span>
        </a>
        <a
            href="{{ route('admin.order-statuses.index', array_filter(['section' => 'orders', 'q' => request('q')])) }}"
            @class([
                'shrink-0 rounded-md px-3 py-1.5 text-xs font-semibold transition',
                'bg-sage text-white' => $currentSection === 'orders',
                'border border-ink/10 bg-white text-muted hover:text-ink' => $currentSection !== 'orders',
            ])
        >
            All Orders Section
            <span class="ml-1 rounded-full bg-mist px-1.5 py-0.5 text-[10px] tabular-nums text-ink">{{ $ordersCount }}</span>
        </a>
        <a
            href="{{ route('admin.order-statuses.index', array_filter(['section' => 'courier', 'q' => request('q')])) }}"
            @class([
                'shrink-0 rounded-md px-3 py-1.5 text-xs font-semibold transition',
                'bg-indigo-600 text-white' => $currentSection === 'courier',
                'border border-ink/10 bg-white text-muted hover:text-ink' => $currentSection !== 'courier',
            ])
        >
            Courier Section
            <span class="ml-1 rounded-full bg-mist px-1.5 py-0.5 text-[10px] tabular-nums text-ink">{{ $courierCount }}</span>
        </a>
    </div>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-sm font-semibold text-ink">
            Status list
            <span class="ml-1 inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-sage px-2 text-xs text-white">{{ $statuses->total() }}</span>
        </h2>
        <form method="GET" class="flex gap-2">
            @if (! empty($currentSection))
                <input type="hidden" name="section" value="{{ $currentSection }}">
            @endif
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search status or slug..." class="field max-w-xs">
            <button type="submit" class="btn-secondary !py-2.5">Search</button>
        </form>
    </div>

    <div class="border border-ink/10 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-mist text-muted">
                    <tr>
                        <th class="px-3 py-2 font-medium">#</th>
                        <th class="px-3 py-2 font-medium">Section</th>
                        <th class="px-3 py-2 font-medium">Status Name</th>
                        <th class="px-3 py-2 font-medium">Slug</th>
                        <th class="px-3 py-2 font-medium">Sort</th>
                        <th class="px-3 py-2 font-medium">State</th>
                        <th class="px-3 py-2 font-medium">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($statuses as $status)
                        <tr class="border-t border-ink/5 hover:bg-mist/30">
                            <td class="px-3 py-2 text-muted">{{ $statuses->firstItem() + $loop->index }}</td>
                            <td class="px-3 py-2">
                                @if ($status->section === 'courier')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-purple-50 px-2.5 py-0.5 text-[11px] font-semibold text-purple-700 border border-purple-200">
                                        Courier
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-0.5 text-[11px] font-semibold text-blue-700 border border-blue-200">
                                        All Orders
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-2 font-medium text-ink">
                                {{ $status->name }}
                            </td>
                            <td class="px-3 py-2 font-mono text-xs text-muted">
                                {{ $status->slug }}
                            </td>
                            <td class="px-3 py-2 text-xs tabular-nums text-muted">
                                {{ $status->sort_order }}
                            </td>
                            <td class="px-3 py-2">
                                @if ($status->is_active)
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">Active</span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">Inactive</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('admin.order-statuses.toggle', $status) }}">
                                        @csrf
                                        <button type="submit" class="rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-800 hover:bg-amber-200" title="Toggle">
                                            {{ $status->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                    <a href="{{ route('admin.order-statuses.edit', $status) }}" class="rounded-full bg-sky-100 px-3 py-1 text-xs font-medium text-sky-800 hover:bg-sky-200">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-muted">No statuses found for this filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-ink/10 px-3 py-2">{{ $statuses->links() }}</div>
    </div>
@endsection
