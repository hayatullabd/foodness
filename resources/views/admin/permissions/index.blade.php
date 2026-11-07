@extends('layouts.admin')

@section('title', 'Permissions')
@section('heading', 'Permissions')
@section('subheading', 'Admin capability keys and access controls used by roles')

@section('actions')
    <a href="{{ route('admin.permissions.create') }}" class="admin-btn-primary">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Add Permission
    </a>
@endsection

@section('content')
    @php
        $total = $permissions->total();
        $isFiltered = request()->filled('q') || request()->filled('group');
    @endphp

    {{-- Compact KPI / Summary Strip --}}
    <div class="mb-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
        <div class="rounded-lg border border-ink/10 bg-white px-3 py-2 shadow-2xs">
            <span class="text-[11px] font-medium text-muted">Total Capabilities</span>
            <div class="mt-0.5 flex items-baseline gap-1.5">
                <span class="font-display text-lg font-bold text-ink">{{ number_format($totalPermissions) }}</span>
                <span class="text-[10px] text-muted">keys</span>
            </div>
        </div>
        <div class="rounded-lg border border-ink/10 bg-white px-3 py-2 shadow-2xs">
            <span class="text-[11px] font-medium text-muted">Module Groups</span>
            <div class="mt-0.5 flex items-baseline gap-1.5">
                <span class="font-display text-lg font-bold text-ink">{{ count($groupCounts) }}</span>
                <span class="text-[10px] text-muted">modules</span>
            </div>
        </div>
        <div class="rounded-lg border border-ink/10 bg-white px-3 py-2 shadow-2xs">
            <span class="text-[11px] font-medium text-muted">Active Roles</span>
            <div class="mt-0.5 flex items-baseline gap-1.5">
                <span class="font-display text-lg font-bold text-ink">{{ $totalRoles }}</span>
                <span class="text-[10px] text-muted">roles</span>
            </div>
        </div>
        <div class="rounded-lg border border-ink/10 bg-white px-3 py-2 shadow-2xs">
            <span class="text-[11px] font-medium text-muted">System Guard</span>
            <div class="mt-0.5 flex items-center gap-1.5">
                <span class="font-mono text-xs font-semibold text-sage">admin</span>
                <span class="inline-flex items-center rounded-full bg-emerald-50 px-1.5 py-0.2 text-[9px] font-semibold text-emerald-700 ring-1 ring-emerald-600/20">active</span>
            </div>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <form method="GET" action="{{ route('admin.permissions.index') }}" class="mb-3 flex flex-wrap items-center gap-2 rounded-lg border border-ink/10 bg-white p-2 shadow-2xs">
        <div class="relative min-w-[14rem] flex-1 sm:max-w-xs">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-muted">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
            </span>
            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                placeholder="Search capability or action…"
                class="field !py-1.5 pl-8 text-xs"
            >
        </div>

        {{-- Group Filter Dropdown --}}
        <div class="min-w-[12rem]">
            <select
                name="group"
                onchange="this.form.submit()"
                class="field !py-1.5 text-xs font-medium"
            >
                <option value="">All Module Groups ({{ count($groupCounts) }})</option>
                @foreach ($groupCounts as $grp => $cnt)
                    <option value="{{ $grp }}" @selected(request('group') === $grp)>
                        {{ $grp }} ({{ $cnt }})
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="admin-btn-secondary !py-1.5 text-xs">Filter</button>

        @if ($isFiltered)
            <a href="{{ route('admin.permissions.index') }}" class="admin-btn-secondary !py-1.5 text-xs text-accent">
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Clear
            </a>
        @endif

        <div class="ml-auto flex items-center gap-2">
            <span class="inline-flex items-center gap-1 rounded-full bg-mist px-2.5 py-0.5 text-[11px] font-semibold text-ink ring-1 ring-ink/10">
                <span class="tabular-nums font-bold">{{ number_format($total) }}</span>
                <span class="text-muted">{{ $isFiltered ? 'matching' : 'total' }}</span>
            </span>
        </div>
    </form>

    {{-- Permissions Table Card --}}
    <div class="admin-card overflow-hidden">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">#</th>
                        <th>Capability Key</th>
                        <th class="w-48">Module Group</th>
                        <th class="w-32">Action</th>
                        <th class="w-20 text-center">Guard</th>
                        <th class="w-24 text-center">Roles</th>
                        <th class="w-28 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink/5">
                    @forelse ($permissions as $index => $permission)
                        @php
                            $group = \App\Support\PermissionHelper::groupFor($permission);
                            $badge = \App\Support\PermissionHelper::groupBadge($group);
                            $actionLabel = \App\Support\PermissionHelper::actionFor($permission);
                            $roleNames = $permission->roles->pluck('name')->join(', ');
                            $rowNum = ($permissions->currentPage() - 1) * $permissions->perPage() + $loop->iteration;
                        @endphp
                        <tr class="transition-colors hover:bg-mist/35">
                            <td class="text-center font-mono text-[11px] text-muted">{{ $rowNum }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <code class="rounded border border-ink/10 bg-white px-2 py-0.5 font-mono text-[11.5px] font-semibold text-ink shadow-2xs">
                                        {{ $permission->name }}
                                    </code>
                                </div>
                            </td>
                            <td>
                                <a
                                    href="{{ route('admin.permissions.index', ['group' => $group]) }}"
                                    class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-semibold transition hover:opacity-80 {{ $badge['bg'] }} ring-1"
                                    title="Filter by {{ $group }}"
                                >
                                    <span>{{ $group }}</span>
                                </a>
                            </td>
                            <td>
                                <span class="inline-flex items-center rounded bg-ink/5 px-1.5 py-0.5 text-[10.5px] font-medium text-slate-700">
                                    {{ $actionLabel }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="font-mono text-[11px] text-muted">{{ $permission->guard_name }}</span>
                            </td>
                            <td class="text-center">
                                @if ($permission->roles_count > 0)
                                    <span
                                        class="inline-flex cursor-help items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-600/20"
                                        title="{{ $roleNames }}"
                                    >
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                        </svg>
                                        <span class="tabular-nums">{{ $permission->roles_count }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-mist px-2 py-0.5 text-[10.5px] font-medium text-muted">
                                        0
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <a
                                        href="{{ route('admin.permissions.edit', $permission) }}"
                                        class="inline-flex items-center gap-1 rounded px-1.5 py-1 text-xs font-medium text-sage hover:bg-sage/10 transition"
                                        title="Edit permission"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                        </svg>
                                        <span>Edit</span>
                                    </a>
                                    @if ($permission->roles_count === 0)
                                        <form method="POST" action="{{ route('admin.permissions.destroy', $permission) }}" class="inline" onsubmit="return confirm('Delete this permission?')">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-1 rounded px-1.5 py-1 text-xs font-medium text-accent hover:bg-accent/10 transition"
                                                title="Delete capability"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
                                                <span>Delete</span>
                                            </button>
                                        </form>
                                    @else
                                        <span
                                            class="cursor-not-allowed px-1.5 py-1 text-xs text-muted/50"
                                            title="Assigned to {{ $permission->roles_count }} role(s). Unassign before deleting."
                                        >
                                            Delete
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center">
                                <div class="mx-auto flex max-w-xs flex-col items-center justify-center text-center">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-mist text-muted">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                        </svg>
                                    </div>
                                    <p class="mt-2 text-sm font-semibold text-ink">No capabilities match your query</p>
                                    <p class="mt-1 text-xs text-muted">Try clearing search filters or choosing a different module group.</p>
                                    @if ($isFiltered)
                                        <a href="{{ route('admin.permissions.index') }}" class="admin-btn-secondary mt-3 !py-1 text-xs">
                                            Clear Filters
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($permissions->hasPages())
            <div class="border-t border-ink/10 bg-[#FAFAFA] px-3 py-2">
                {{ $permissions->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
