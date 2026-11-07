@php($permission = $permission ?? null)

<div class="space-y-3">
    <div>
        <label class="admin-label text-xs font-semibold" for="name">
            Capability / Permission Key <span class="text-accent">*</span>
        </label>
        <div class="relative">
            <input
                id="name"
                name="name"
                type="text"
                value="{{ old('name', $permission?->name) }}"
                required
                class="field font-mono text-sm pr-20"
                placeholder="e.g. orders.view or story-create"
                autocomplete="off"
            >
            <span class="absolute inset-y-0 right-2 flex items-center">
                <span class="rounded bg-mist px-2 py-0.5 font-mono text-[10px] font-semibold text-muted">
                    admin guard
                </span>
            </span>
        </div>
        @error('name')
            <p class="mt-1 text-xs text-accent font-medium">{{ $message }}</p>
        @enderror
    </div>

    <div class="rounded-lg border border-ink/10 bg-slate-50/70 p-3 text-xs text-muted space-y-1.5">
        <p class="font-semibold text-slate-800 flex items-center gap-1.5">
            <svg class="h-4 w-4 text-sage" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
            </svg>
            Naming Convention Guidelines
        </p>
        <p class="text-[11.5px] leading-relaxed">
            Use lowercase with dot notation (e.g. <code class="rounded bg-white px-1 py-0.5 font-mono text-slate-800 ring-1 ring-ink/10">orders.view</code>, <code class="rounded bg-white px-1 py-0.5 font-mono text-slate-800 ring-1 ring-ink/10">products.manage</code>) or legacy hyphens (e.g. <code class="rounded bg-white px-1 py-0.5 font-mono text-slate-800 ring-1 ring-ink/10">story-create</code>).
        </p>
        <div class="flex flex-wrap gap-1.5 pt-1 text-[10.5px]">
            <span class="rounded bg-white px-1.5 py-0.5 font-mono text-muted ring-1 ring-ink/10">module.view</span>
            <span class="rounded bg-white px-1.5 py-0.5 font-mono text-muted ring-1 ring-ink/10">module.edit</span>
            <span class="rounded bg-white px-1.5 py-0.5 font-mono text-muted ring-1 ring-ink/10">module.manage</span>
            <span class="rounded bg-white px-1.5 py-0.5 font-mono text-muted ring-1 ring-ink/10">module-list</span>
            <span class="rounded bg-white px-1.5 py-0.5 font-mono text-muted ring-1 ring-ink/10">module-create</span>
        </div>
    </div>
</div>
