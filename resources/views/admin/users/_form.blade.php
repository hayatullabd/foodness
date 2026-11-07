@php($user = $user ?? null)

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="admin-label" for="name">Name</label>
        <input id="name" name="name" type="text" value="{{ old('name', $user?->name) }}" required class="field">
        @error('name') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="admin-label" for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $user?->email) }}" required class="field">
        @error('email') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="admin-label" for="password">Password{{ $user ? ' (leave blank to keep)' : '' }}</label>
        <input id="password" name="password" type="password" class="field" @unless($user) required @endunless autocomplete="new-password">
        @error('password') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="admin-label" for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" class="field" @unless($user) required @endunless autocomplete="new-password">
    </div>
</div>

<label class="mt-1 flex items-center gap-2 text-sm">
    <input type="checkbox" name="is_active" value="1" class="h-3.5 w-3.5 rounded border-ink/30 text-sage focus:ring-sage" @checked(old('is_active', $user?->is_active ?? true))>
    Active account
</label>

<div class="pt-1">
    <div class="mb-1.5 flex items-center justify-between gap-2">
        <p class="admin-label !mb-0">Roles</p>
        <span class="text-[11px] text-muted">{{ $roles->count() }} available</span>
    </div>
    <div class="grid gap-1.5 sm:grid-cols-2">
        @forelse ($roles as $role)
            <label class="flex items-center gap-2 rounded-md border border-ink/10 bg-mist/40 px-2.5 py-1.5 text-sm hover:bg-mist">
                <input
                    type="checkbox"
                    name="roles[]"
                    value="{{ $role->name }}"
                    class="h-3.5 w-3.5 rounded border-ink/30 text-sage focus:ring-sage"
                    @checked(in_array($role->name, old('roles', $user?->roles->pluck('name')->all() ?? []), true))
                >
                <span class="font-medium text-ink">{{ $role->name }}</span>
            </label>
        @empty
            <p class="text-sm text-muted sm:col-span-2">No roles available. Create roles first.</p>
        @endforelse
    </div>
    @error('roles') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
    @error('roles.*') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
</div>
