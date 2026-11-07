@php
    use App\Support\CourierRatioCards;

    $summary = CourierRatioCards::listSummary(
        is_array($fraudReport ?? null) ? $fraudReport : null,
        $score ?? null,
    );
    $ratio = $summary['success_rate'];
    $hasData = $summary['has_data'];
    $tone = match (true) {
        $ratio === null => '#94a3b8',
        $ratio >= 70 => '#16a34a',
        $ratio >= 40 => '#d97706',
        default => '#dc2626',
    };
    $textTone = match (true) {
        $ratio === null => 'text-muted',
        $ratio >= 70 => 'text-emerald-700',
        $ratio >= 40 => 'text-amber-700',
        default => 'text-red-600',
    };
    $radius = 18;
    $circumference = 2 * M_PI * $radius;
    $progress = $ratio !== null ? max(0, min(100, $ratio)) : 0;
    $dashOffset = $circumference - (($progress / 100) * $circumference);
@endphp

@if ($hasData)
    <div class="flex items-center gap-2.5">
        <div class="relative h-11 w-11 shrink-0">
            <svg class="h-11 w-11 -rotate-90" viewBox="0 0 44 44" aria-hidden="true">
                <circle cx="22" cy="22" r="{{ $radius }}" fill="none" stroke="#e2e8f0" stroke-width="4" />
                <circle
                    cx="22"
                    cy="22"
                    r="{{ $radius }}"
                    fill="none"
                    stroke="{{ $tone }}"
                    stroke-width="4"
                    stroke-linecap="round"
                    stroke-dasharray="{{ $circumference }}"
                    stroke-dashoffset="{{ $dashOffset }}"
                />
            </svg>
            <span class="absolute inset-0 flex items-center justify-center text-[10px] font-semibold tabular-nums {{ $textTone }}">
                {{ $ratio !== null ? number_format($ratio, 0) : '—' }}
            </span>
        </div>
        <div class="min-w-0 text-[11px] leading-4">
            <div>
                <span class="text-muted">Success:</span>
                <span class="font-semibold {{ $textTone }}">
                    {{ $ratio !== null ? number_format($ratio, 0).'%' : '—' }}
                </span>
            </div>
            <div>
                <span class="text-muted">Order:</span>
                <span class="font-medium text-ink">
                    @if ($summary['success'] !== null || $summary['total'] !== null)
                        {{ $summary['success'] ?? 0 }}/{{ $summary['total'] ?? 0 }}
                    @else
                        —
                    @endif
                </span>
            </div>
            <div>
                <span class="text-muted">Cancel:</span>
                <span class="font-medium text-ink">{{ $summary['cancelled'] ?? '—' }}</span>
            </div>
        </div>
    </div>
@else
    <span class="text-xs text-muted">Not checked</span>
@endif
