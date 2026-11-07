@php
    $score = $score ?? null;
    $size = $size ?? 'sm';
    $showEmpty = $showEmpty ?? true;
    $textClass = $size === 'md' ? 'text-sm' : 'text-xs';
@endphp

@if ($score !== null)
    @php
        $ratio = (float) $score;
        $tone = match (true) {
            $ratio >= 70 => 'bg-emerald-100 text-emerald-800',
            $ratio >= 40 => 'bg-amber-100 text-amber-800',
            default => 'bg-red-100 text-red-800',
        };
    @endphp
    <span class="inline-flex rounded-full px-2 py-0.5 font-medium leading-tight {{ $textClass }} {{ $tone }}">
        {{ number_format($ratio, 2) }}%
    </span>
@elseif ($showEmpty)
    <span class="{{ $textClass }} text-muted">—</span>
@endif
