@php
    $name = $name ?? null;
    $checked = (bool) ($checked ?? false);
    $label = $label ?? null;
    $hint = $hint ?? null;
    $id = $id ?? ($name ? 'toggle-'.$name : 'toggle-'.uniqid());
@endphp

<label for="{{ $id }}" class="flex cursor-pointer items-center justify-between gap-3">
    @if ($label)
        <span class="min-w-0">
            <span class="block text-sm font-medium text-ink">{{ $label }}</span>
            @if ($hint)
                <span class="mt-0.5 block text-[11px] text-muted">{{ $hint }}</span>
            @endif
        </span>
    @endif
    <span class="relative inline-flex shrink-0">
        <input
            id="{{ $id }}"
            type="checkbox"
            @if ($name) name="{{ $name }}" @endif
            value="1"
            class="peer sr-only"
            @checked($checked)
        >
        <span class="h-5 w-9 rounded-full bg-fog transition peer-checked:bg-sage peer-focus-visible:ring-2 peer-focus-visible:ring-sage/40"></span>
        <span class="pointer-events-none absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
    </span>
</label>
