@extends('layouts.admin')

@section('title', 'My Profile & Account')
@section('heading', 'My Profile & Account')
@section('subheading', 'Manage your administrator account details, security credentials, and view system roles & permissions.')

@section('actions')
    <a href="{{ route('admin.dashboard') }}" class="admin-btn-secondary !px-3 !py-1.5 text-xs">
        &larr; Back to Dashboard
    </a>
@endsection

@section('content')
    <div class="space-y-5 max-w-5xl">
        {{-- Profile Hero / Overview Card --}}
        <div class="admin-card overflow-hidden">
            <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-5 sm:p-6 text-white">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-xl font-extrabold text-white ring-2 ring-white/20 shadow-inner backdrop-blur-sm">
                            {{ strtoupper(substr($admin->name, 0, 2)) }}
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-lg sm:text-xl font-bold tracking-tight text-white">{{ $admin->name }}</h2>
                                @if ($admin->is_active)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/20 px-2 py-0.5 text-[10px] font-semibold text-emerald-300 ring-1 ring-emerald-500/30">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-500/20 px-2 py-0.5 text-[10px] font-semibold text-rose-300 ring-1 ring-rose-500/30">
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-400"></span> Suspended
                                    </span>
                                @endif
                            </div>
                            <p class="mt-0.5 text-xs text-slate-300">{{ $admin->email }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                @forelse ($admin->roles as $role)
                                    <span @class([
                                        'rounded-md px-2 py-0.5 text-[10px] font-bold tracking-wide uppercase',
                                        'bg-purple-500/25 text-purple-200 border border-purple-400/30' => in_array(strtolower($role->name), ['super admin', 'super_admin']),
                                        'bg-emerald-500/25 text-emerald-200 border border-emerald-400/30' => in_array(strtolower($role->name), ['admin']),
                                        'bg-blue-500/25 text-blue-200 border border-blue-400/30' => in_array(strtolower($role->name), ['manager']),
                                        'bg-amber-500/25 text-amber-200 border border-amber-400/30' => in_array(strtolower($role->name), ['staff']),
                                        'bg-sky-500/25 text-sky-200 border border-sky-400/30' => in_array(strtolower($role->name), ['moderator']),
                                        'bg-slate-700 text-slate-200 border border-slate-600' => !in_array(strtolower($role->name), ['super admin', 'super_admin', 'admin', 'manager', 'staff', 'moderator']),
                                    ])>
                                        {{ $role->name }}
                                    </span>
                                @empty
                                    <span class="rounded-md bg-slate-700/60 px-2 py-0.5 text-[10px] text-slate-400 border border-slate-600/50">
                                        No Specific Role Assigned
                                    </span>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="sm:text-right text-xs text-slate-400 space-y-1">
                        <div>Account ID: <span class="font-mono text-slate-200">#{{ $admin->id }}</span></div>
                        <div>Member Since: <span class="font-medium text-slate-200">{{ $admin->created_at?->format('d M, Y') ?? 'N/A' }}</span></div>
                        <div>Guard: <span class="font-mono text-emerald-400">admin</span></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Form Cards Grid --}}
        <div class="grid gap-5 md:grid-cols-2">
            {{-- Personal Details Form --}}
            <div class="admin-card">
                <div class="admin-card-header border-b border-slate-100 px-4 py-3">
                    <h3 class="text-xs font-semibold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                        <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Personal Details
                    </h3>
                    <p class="text-[11px] text-slate-500">Update your administrator display name and official login email address.</p>
                </div>

                <form method="POST" action="{{ route('admin.profile.update') }}" class="p-4 sm:p-5 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="name" class="admin-label">Full Name *</label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            value="{{ old('name', $admin->name) }}"
                            required
                            class="field text-sm"
                            placeholder="e.g. Admin User"
                        >
                        @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="email" class="admin-label">Email Address *</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email', $admin->email) }}"
                            required
                            class="field text-sm"
                            placeholder="admin@example.com"
                        >
                        @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="admin-btn-primary !px-4 !py-2 text-xs font-semibold flex items-center gap-1.5 shadow-xs">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Save Profile Changes</span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Security & Password Update Form --}}
            <div class="admin-card">
                <div class="admin-card-header border-b border-slate-100 px-4 py-3">
                    <h3 class="text-xs font-semibold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                        <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Security & Password
                    </h3>
                    <p class="text-[11px] text-slate-500">Ensure your account is using a long, random password to stay secure.</p>
                </div>

                <form method="POST" action="{{ route('admin.profile.password.update') }}" class="p-4 sm:p-5 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="current_password" class="admin-label">Current Password *</label>
                        <input
                            id="current_password"
                            name="current_password"
                            type="password"
                            required
                            autocomplete="current-password"
                            class="field text-sm"
                            placeholder="••••••••"
                        >
                        @error('current_password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="password" class="admin-label">New Password *</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            autocomplete="new-password"
                            class="field text-sm"
                            placeholder="At least 8 characters"
                        >
                        @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="admin-label">Confirm New Password *</label>
                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            required
                            autocomplete="new-password"
                            class="field text-sm"
                            placeholder="••••••••"
                        >
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="admin-btn-primary !px-4 !py-2 text-xs font-semibold flex items-center gap-1.5 shadow-xs">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <span>Update Password</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Roles & Capabilities Inspector --}}
        <div class="admin-card">
            <div class="admin-card-header border-b border-slate-100 px-4 py-3 flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-semibold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                        <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                        Assigned Role & System Capabilities
                    </h3>
                    <p class="text-[11px] text-slate-500">Your role determines which features and administrative modules you are authorized to operate.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold text-slate-600">
                    {{ $admin->getAllPermissions()->count() }} Permissions Active
                </span>
            </div>

            <div class="p-4 sm:p-5 space-y-4">
                <div>
                    <h4 class="text-xs font-bold text-slate-800 mb-2">Assigned Roles</h4>
                    <div class="flex flex-wrap gap-2">
                        @forelse ($admin->roles as $role)
                            <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                <span class="text-xs font-bold text-slate-800">{{ $role->name }}</span>
                                <span class="text-[10px] text-slate-400">({{ $role->permissions->count() }} permissions)</span>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500">No roles assigned.</p>
                        @endforelse
                    </div>
                </div>

                @if ($admin->isSuperAdmin())
                    <div class="rounded-xl border border-purple-200 bg-purple-50/70 p-3.5 text-xs text-purple-900 flex items-start gap-2.5">
                        <svg class="h-4 w-4 text-purple-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                        </svg>
                        <div>
                            <strong class="font-bold">Super Admin Privileges:</strong> You have unlimited full operational and configuration control across the entire store platform, server settings, customizer, and team access rules.
                        </div>
                    </div>
                @else
                    <div>
                        <h4 class="text-xs font-bold text-slate-800 mb-2">Direct & Inherited Permissions</h4>
                        <div class="max-h-48 overflow-y-auto rounded-xl border border-slate-200 p-2.5 bg-slate-50 flex flex-wrap gap-1.5">
                            @forelse ($admin->getAllPermissions() as $permission)
                                <span class="rounded bg-white border border-slate-200 px-2 py-0.5 text-[10px] font-mono text-slate-700 shadow-2xs">
                                    {{ $permission->name }}
                                </span>
                            @empty
                                <p class="text-xs text-slate-500 p-2">No permissions currently assigned.</p>
                            @endforelse
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
