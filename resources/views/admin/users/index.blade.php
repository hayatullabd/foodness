@extends('layouts.admin')

@section('title', 'Users')
@section('heading', 'Users')
@section('subheading', 'Admin accounts and assigned roles')

@section('actions')
    <a href="{{ route('admin.users.create') }}" class="admin-btn-primary">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Add User
    </a>
@endsection

@section('content')
    <div class="admin-card">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Roles</th>
                        <th class="w-24">Status</th>
                        <th class="w-28 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td class="font-medium text-ink">{{ $user->name }}</td>
                            <td class="text-sm text-muted">{{ $user->email }}</td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($user->roles as $role)
                                        <span class="inline-flex rounded bg-mist px-1.5 py-0.5 text-[11px] font-medium text-sage">{{ $role->name }}</span>
                                    @empty
                                        <span class="text-xs text-muted">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                <span @class([
                                    'inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold',
                                    'bg-emerald-50 text-emerald-700' => $user->is_active,
                                    'bg-slate-100 text-slate-600' => ! $user->is_active,
                                ])>
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="text-xs font-medium text-sage hover:underline">Edit</a>
                                    @if ($user->id !== auth('admin')->id())
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline" onsubmit="return confirm('Delete this admin user?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-medium text-accent hover:underline">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-muted">No admin users yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="border-t border-ink/10 px-3 py-2">{{ $users->links() }}</div>
        @endif
    </div>
@endsection
