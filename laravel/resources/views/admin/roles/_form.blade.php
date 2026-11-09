@php
    $role = $role ?? null;
    $selected = collect(old('permissions', $role?->permissions->pluck('name')->all() ?? []));
    $selectedCount = $selected->count();
    $totalPermissions = collect($permissionGroups)->flatten()->count();
    $isSuperAdmin = $role?->name === 'Super Admin';
@endphp

<div class="grid gap-4 lg:grid-cols-12" data-permission-matrix>
    {{-- Left Sidebar: Role Meta & Quick Controls --}}
    <div class="space-y-3 lg:col-span-4 xl:col-span-3">
        <div class="rounded-lg border border-ink/10 bg-white p-3.5 shadow-2xs">
            <label class="admin-label !mb-1 text-xs" for="name">Role Name <span class="text-accent">*</span></label>
            <input
                id="name"
                name="name"
                type="text"
                value="{{ old('name', $role?->name) }}"
                required
                @readonly($isSuperAdmin)
                class="field font-medium text-sm"
                placeholder="e.g. Operations Manager"
            >
            @error('name') <p class="mt-1 text-xs text-accent">{{ $message }}</p> @enderror
            @if ($isSuperAdmin)
                <p class="mt-1 text-[11px] text-muted">System role name cannot be modified.</p>
            @endif
        </div>

        {{-- Selection Summary & Progress --}}
        <div class="rounded-lg border border-ink/10 bg-white p-3.5 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-muted">Selected Capabilities</span>
                <span class="font-mono text-xs font-bold text-sage" data-selected-pct>
                    {{ $totalPermissions > 0 ? round(($selectedCount / $totalPermissions) * 100) : 0 }}%
                </span>
            </div>

            <div class="mt-2 flex items-baseline gap-1.5">
                <span class="font-display text-2xl font-bold text-ink tabular-nums" data-selected-count>{{ $selectedCount }}</span>
                <span class="text-xs text-muted">of <span class="tabular-nums font-semibold">{{ $totalPermissions }}</span> capabilities</span>
            </div>

            <div class="mt-2.5 h-1.5 w-full overflow-hidden rounded-full bg-mist">
                <div
                    class="h-full rounded-full bg-sage transition-all duration-300"
                    data-progress-bar
                    style="width: {{ $totalPermissions > 0 ? ($selectedCount / $totalPermissions) * 100 : 0 }}%"
                ></div>
            </div>

            <div class="mt-3.5 flex gap-2">
                <button type="button" class="admin-btn-secondary flex-1 !py-1 text-[11.5px]" data-perm-select-all>
                    Select All
                </button>
                <button type="button" class="admin-btn-secondary flex-1 !py-1 text-[11.5px]" data-perm-clear-all>
                    Clear All
                </button>
            </div>
        </div>

        {{-- Quick Module Jump List --}}
        <div class="hidden lg:block rounded-lg border border-ink/10 bg-white p-3 shadow-2xs">
            <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-muted">Jump to Module</p>
            <div class="max-h-80 overflow-y-auto space-y-0.5 no-scrollbar pr-1">
                @foreach ($permissionGroups as $group => $perms)
                    @php
                        $gNames = $perms->pluck('name');
                        $gSel = $gNames->intersect($selected)->count();
                        $gTot = $gNames->count();
                        $slug = \Illuminate\Support\Str::slug($group);
                    @endphp
                    <a
                        href="#module-{{ $slug }}"
                        class="flex items-center justify-between rounded px-2 py-1 text-xs text-muted hover:bg-mist hover:text-ink transition"
                    >
                        <span class="truncate max-w-[130px] font-medium">{{ $group }}</span>
                        <span class="tabular-nums font-mono text-[10.5px] text-muted" data-jump-count="{{ $slug }}">
                            {{ $gSel }}/{{ $gTot }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Right Area: Capability Matrix --}}
    <div class="space-y-2 lg:col-span-8 xl:col-span-9">
        {{-- Matrix Toolbar --}}
        <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-ink/10 bg-white p-2.5 shadow-2xs">
            <div class="relative min-w-[16rem] flex-1">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-muted">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                </span>
                <input
                    type="search"
                    placeholder="Filter permissions by keyword (e.g. view, create, order)…"
                    class="field !py-1 pl-8 text-xs font-normal"
                    data-perm-filter
                    autocomplete="off"
                >
            </div>
            <div class="flex items-center gap-1.5">
                <button type="button" class="admin-btn-secondary !px-2.5 !py-1 text-[11px]" data-expand-all>
                    Expand All
                </button>
                <button type="button" class="admin-btn-secondary !px-2.5 !py-1 text-[11px]" data-collapse-all>
                    Collapse All
                </button>
            </div>
        </div>

        {{-- Group Cards --}}
        <div class="space-y-2">
            @forelse ($permissionGroups as $group => $permissions)
                @php
                    $groupNames = $permissions->pluck('name');
                    $groupSelected = $groupNames->intersect($selected)->count();
                    $groupTotal = $groupNames->count();
                    $allChecked = $groupTotal > 0 && $groupSelected === $groupTotal;
                    $badge = \App\Support\PermissionHelper::groupBadge($group);
                    $slug = \Illuminate\Support\Str::slug($group);
                @endphp
                <details
                    id="module-{{ $slug }}"
                    class="group overflow-hidden rounded-lg border border-ink/10 bg-white shadow-2xs transition"
                    data-perm-group
                    data-group-slug="{{ $slug }}"
                    open
                >
                    <summary class="flex cursor-pointer list-none items-center gap-2.5 border-b border-ink/5 bg-slate-50/70 px-3.5 py-2 text-xs font-semibold text-ink transition hover:bg-slate-100/70 select-none [&::-webkit-details-marker]:hidden">
                        <svg class="h-3.5 w-3.5 shrink-0 text-muted transition group-open:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>

                        <span class="inline-flex items-center gap-1.5 font-bold text-slate-800">
                            {{ $group }}
                        </span>

                        <span class="inline-flex items-center rounded-full px-2 py-0.2 font-mono text-[10.5px] font-bold tabular-nums {{ $badge['bg'] }} ring-1" data-group-count>
                            {{ $groupSelected }}/{{ $groupTotal }}
                        </span>

                        <label class="ml-auto flex items-center gap-1.5 text-[11.5px] font-semibold text-slate-700 hover:text-ink cursor-pointer" onclick="event.stopPropagation()">
                            <input
                                type="checkbox"
                                class="h-3.5 w-3.5 rounded border-ink/30 text-sage focus:ring-sage"
                                data-group-toggle
                                @checked($allChecked)
                            >
                            <span>Select Group</span>
                        </label>
                    </summary>

                    <div class="grid gap-1.5 p-2.5 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($permissions as $permission)
                            @php
                                $action = \App\Support\PermissionHelper::actionFor($permission);
                                $isChecked = $selected->contains($permission->name);
                            @endphp
                            <label
                                class="flex cursor-pointer items-center justify-between gap-1.5 rounded-md border border-ink/5 bg-white px-2.5 py-1.5 text-xs transition hover:border-ink/20 hover:bg-mist/30 has-[:checked]:border-sage/40 has-[:checked]:bg-emerald-50/30"
                                data-perm-item
                                data-perm-name="{{ strtolower($permission->name) }}"
                            >
                                <div class="flex items-center gap-2 min-w-0">
                                    <input
                                        type="checkbox"
                                        name="permissions[]"
                                        value="{{ $permission->name }}"
                                        class="h-3.5 w-3.5 shrink-0 rounded border-ink/30 text-sage focus:ring-sage"
                                        data-perm-checkbox
                                        @checked($isChecked)
                                    >
                                    <span class="truncate font-mono text-[11px] font-medium text-slate-800" title="{{ $permission->name }}">
                                        {{ $permission->name }}
                                    </span>
                                </div>
                                <span class="shrink-0 rounded bg-ink/5 px-1.5 py-0.2 text-[9.5px] font-semibold text-muted">
                                    {{ $action }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </details>
            @empty
                <p class="rounded-lg border border-dashed border-ink/15 p-8 text-center text-sm text-muted">
                    No capabilities registered.
                </p>
            @endforelse
        </div>

        @error('permissions') <p class="text-xs text-accent mt-2">{{ $message }}</p> @enderror
        @error('permissions.*') <p class="text-xs text-accent mt-2">{{ $message }}</p> @enderror
    </div>
</div>

<script>
    (() => {
        const root = document.querySelector('[data-permission-matrix]');
        if (!root) return;

        const form = root.closest('form');
        const selectedCountEl = form?.querySelector('[data-selected-count]');
        const selectedPctEl = form?.querySelector('[data-selected-pct]');
        const progressBarEl = form?.querySelector('[data-progress-bar]');
        const filterInput = root.querySelector('[data-perm-filter]');
        const totalPermissions = {{ $totalPermissions }};

        const updateCounts = () => {
            const allChecked = form.querySelectorAll('[data-perm-checkbox]:checked').length;
            if (selectedCountEl) selectedCountEl.textContent = String(allChecked);

            const pct = totalPermissions > 0 ? Math.round((allChecked / totalPermissions) * 100) : 0;
            if (selectedPctEl) selectedPctEl.textContent = `${pct}%`;
            if (progressBarEl) progressBarEl.style.width = `${pct}%`;

            root.querySelectorAll('[data-perm-group]').forEach((group) => {
                const boxes = group.querySelectorAll('[data-perm-checkbox]');
                const on = group.querySelectorAll('[data-perm-checkbox]:checked').length;
                const count = group.querySelector('[data-group-count]');
                const toggle = group.querySelector('[data-group-toggle]');
                const slug = group.dataset.groupSlug;

                if (count) count.textContent = `${on}/${boxes.length}`;
                if (toggle) {
                    toggle.checked = boxes.length > 0 && on === boxes.length;
                    toggle.indeterminate = on > 0 && on < boxes.length;
                }

                if (slug) {
                    const jumpCount = form.querySelector(`[data-jump-count="${slug}"]`);
                    if (jumpCount) jumpCount.textContent = `${on}/${boxes.length}`;
                }
            });
        };

        // Group toggle
        root.querySelectorAll('[data-group-toggle]').forEach((toggle) => {
            toggle.addEventListener('change', () => {
                const group = toggle.closest('[data-perm-group]');
                group.querySelectorAll('[data-perm-checkbox]').forEach((box) => {
                    const item = box.closest('[data-perm-item]');
                    if (!item || !item.classList.contains('hidden')) {
                        box.checked = toggle.checked;
                    }
                });
                updateCounts();
            });
        });

        // Global Select All
        form?.querySelector('[data-perm-select-all]')?.addEventListener('click', () => {
            root.querySelectorAll('[data-perm-checkbox]').forEach((box) => {
                const item = box.closest('[data-perm-item]');
                if (!item || !item.classList.contains('hidden')) {
                    box.checked = true;
                }
            });
            updateCounts();
        });

        // Global Clear All
        form?.querySelector('[data-perm-clear-all]')?.addEventListener('click', () => {
            root.querySelectorAll('[data-perm-checkbox]').forEach((box) => { box.checked = false; });
            updateCounts();
        });

        // Expand / Collapse all
        root.querySelector('[data-expand-all]')?.addEventListener('click', () => {
            root.querySelectorAll('[data-perm-group]').forEach((group) => { group.open = true; });
        });

        root.querySelector('[data-collapse-all]')?.addEventListener('click', () => {
            root.querySelectorAll('[data-perm-group]').forEach((group) => { group.open = false; });
        });

        // Checkbox change listener
        root.addEventListener('change', (event) => {
            if (event.target.matches('[data-perm-checkbox]')) updateCounts();
        });

        // Live filter
        filterInput?.addEventListener('input', () => {
            const query = filterInput.value.trim().toLowerCase();
            root.querySelectorAll('[data-perm-group]').forEach((group) => {
                let visible = 0;
                group.querySelectorAll('[data-perm-item]').forEach((item) => {
                    const match = !query || (item.dataset.permName || '').includes(query);
                    item.classList.toggle('hidden', !match);
                    if (match) visible += 1;
                });
                group.classList.toggle('hidden', visible === 0);
                if (query && visible > 0) group.open = true;
            });
            updateCounts();
        });

        updateCounts();
    })();
</script>
