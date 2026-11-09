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
    <div class="admin-card overflow-hidden">
        <div class="admin-card-header">
            <div>
                <h2 class="text-sm font-semibold text-ink">Admin users</h2>
                <p class="text-xs text-muted mt-0.5">{{ $users->total() }} account{{ $users->total() === 1 ? '' : 's' }}</p>
            </div>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Roles</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
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
                                        <span class="admin-badge admin-badge-sage">{{ $role->name }}</span>
                                    @empty
                                        <span class="text-xs text-muted">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                <span @class([
                                    'admin-badge-pill',
                                    'admin-badge-success' => $user->is_active,
                                    'admin-badge-neutral' => ! $user->is_active,
                                ])>
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="admin-table-actions justify-end">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="admin-btn-secondary">Edit</a>
                                    @if ($user->id !== auth('admin')->id())
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline" onsubmit="return confirm('Delete this admin user?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="admin-btn-danger">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="admin-empty-cell">
                                <div class="admin-empty">
                                    <p class="admin-empty-title">No admin users yet</p>
                                    <p class="admin-empty-hint">Add a teammate to help manage the store.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages() || $users->total())
            <div class="orders-footer">
                <div class="text-xs text-muted">
                    @if ($users->total())
                        Showing {{ $users->firstItem() }}–{{ $users->lastItem() }} of {{ $users->total() }}
                    @endif
                </div>
                <div>{{ $users->links() }}</div>
            </div>
        @endif
    </div>
@endsection
