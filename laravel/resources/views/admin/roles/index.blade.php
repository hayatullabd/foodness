@extends('layouts.admin')

@section('title', 'Roles')
@section('heading', 'Roles & Permissions')
@section('subheading', 'Define administrative roles and grant granular capabilities')

@section('actions')
    <div class="flex items-center gap-2">
        <a href="{{ route('admin.permissions.index') }}" class="admin-btn-secondary">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
            </svg>
            Permissions Matrix
        </a>
        <a href="{{ route('admin.roles.create') }}" class="admin-btn-primary">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Add Role
        </a>
    </div>
@endsection

@section('content')
    {{-- KPI Summary Row --}}
    <div class="mb-3 grid grid-cols-1 gap-2.5 sm:grid-cols-3">
        <div class="rounded-lg border border-ink/10 bg-white p-3 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-muted">Configured Roles</span>
                <span class="flex h-7 w-7 items-center justify-center rounded-md bg-sage/10 text-sage">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-1.5">
                <span class="font-display text-2xl font-bold text-ink">{{ $roles->total() }}</span>
                <span class="text-xs text-muted">roles active</span>
            </div>
        </div>

        <div class="rounded-lg border border-ink/10 bg-white p-3 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-muted">Total Capabilities</span>
                <span class="flex h-7 w-7 items-center justify-center rounded-md bg-blue-50 text-blue-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-1.5">
                <span class="font-display text-2xl font-bold text-ink">{{ $totalPermissions ?? 0 }}</span>
                <span class="text-xs text-muted">system permissions</span>
            </div>
        </div>

        <div class="rounded-lg border border-ink/10 bg-white p-3 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-muted">Assigned Admins</span>
                <span class="flex h-7 w-7 items-center justify-center rounded-md bg-purple-50 text-purple-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-1.5">
                <span class="font-display text-2xl font-bold text-ink">{{ $totalAdmins ?? 0 }}</span>
                <span class="text-xs text-muted">admin accounts</span>
            </div>
        </div>
    </div>

    {{-- Roles Card & Compact Table --}}
    <div class="admin-card overflow-hidden">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th class="w-64">Role Name</th>
                        <th class="w-64">Capability Coverage</th>
                        <th>Assigned Users</th>
                        <th class="w-20 text-center">Guard</th>
                        <th class="w-32 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink/5">
                    @forelse ($roles as $role)
                        @php
                            $isSuperAdmin = $role->name === 'Super Admin';
                            $permTotal = $totalPermissions ?: 1;
                            $pct = min(100, round(($role->permissions_count / $permTotal) * 100));
                        @endphp
                        <tr class="transition-colors hover:bg-mist/35">
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-ink/10 bg-white text-ink shadow-2xs">
                                        <svg class="h-4 w-4 {{ $isSuperAdmin ? 'text-amber-600' : 'text-sage' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-semibold text-ink text-sm">{{ $role->name }}</span>
                                            @if ($isSuperAdmin)
                                                <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.2 text-[10px] font-bold uppercase tracking-wider text-amber-700 ring-1 ring-amber-600/20">System</span>
                                            @endif
                                        </div>
                                        <p class="text-[11px] text-muted">
                                            {{ $isSuperAdmin ? 'Unrestricted access to all capabilities' : 'Custom assigned permission policy' }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="space-y-1">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="font-semibold tabular-nums text-ink">
                                            {{ $role->permissions_count }}
                                            <span class="font-normal text-muted">/ {{ $totalPermissions }}</span>
                                        </span>
                                        <span class="font-mono text-[11px] font-semibold text-muted">{{ $pct }}%</span>
                                    </div>
                                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-mist">
                                        <div
                                            class="h-full rounded-full transition-all duration-300 {{ $isSuperAdmin ? 'bg-amber-500' : 'bg-sage' }}"
                                            style="width: {{ $pct }}%"
                                        ></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    @if ($role->users_count > 0)
                                        @foreach ($role->users->take(3) as $user)
                                            <span class="inline-flex items-center gap-1 rounded-md border border-ink/10 bg-white px-2 py-0.5 text-xs font-medium text-slate-800 shadow-2xs" title="{{ $user->email }}">
                                                <span class="flex h-4 w-4 items-center justify-center rounded-full bg-mist text-[9px] font-bold text-slate-600">
                                                    {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                                                </span>
                                                <span class="truncate max-w-[100px]">{{ $user->name }}</span>
                                            </span>
                                        @endforeach
                                        @if ($role->users_count > 3)
                                            <span class="rounded-md bg-mist px-1.5 py-0.5 text-[11px] font-medium text-muted">
                                                +{{ $role->users_count - 3 }} more
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-xs text-muted italic">No users assigned</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center font-mono text-xs text-muted">
                                {{ $role->guard_name }}
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1.5">
                                    <a
                                        href="{{ route('admin.roles.edit', $role) }}"
                                        class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium text-sage hover:bg-sage/10 transition"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                        </svg>
                                        <span>Edit</span>
                                    </a>

                                    @if (! $isSuperAdmin)
                                        <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="inline" onsubmit="return confirm('Delete role \'{{ $role->name }}\'?')">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium text-accent hover:bg-accent/10 transition"
                                                title="Delete role"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
                                                <span>Delete</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-muted">
                                No roles defined yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($roles->hasPages())
            <div class="border-t border-ink/10 px-3 py-2 bg-[#FAFAFA]">
                {{ $roles->links() }}
            </div>
        @endif
    </div>
@endsection
