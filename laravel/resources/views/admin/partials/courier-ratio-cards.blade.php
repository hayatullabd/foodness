@php
    use App\Support\CourierRatioCards;

    $fraudReport = is_array($fraudReport ?? null) ? $fraudReport : [];
    $ourRecord = is_array($ourRecord ?? null) ? $ourRecord : CourierRatioCards::ourRecordForPhone($phone ?? null);
    $courierCards = CourierRatioCards::courierCards($fraudReport);
    $showOurRecord = ($showOurRecord ?? true) === true;
    $scrollId = 'courier-ratio-scroll-'.($scrollKey ?? uniqid());
    $hasContent = $showOurRecord || $courierCards !== [];
@endphp

@if ($hasContent)
    <div class="courier-ratio-strip relative">
        <div
            id="{{ $scrollId }}"
            class="courier-ratio-scroll flex gap-2 overflow-x-auto pb-1.5 pr-8"
        >
            @if ($showOurRecord)
                <div class="courier-ratio-card shrink-0 overflow-hidden rounded-lg border border-ink/10 bg-white shadow-sm">
                    <div
                        class="flex items-center gap-1.5 px-2 py-1.5 text-[11px] font-semibold text-white"
                        style="background: {{ CourierRatioCards::headerColor('our_record') }}"
                    >
                        <span class="inline-flex h-4 w-4 items-center justify-center rounded bg-white/20 text-[9px] font-bold leading-none">O</span>
                        <span class="truncate">Our Record</span>
                    </div>
                    <div class="space-y-1.5 px-2 py-1.5">
                        <div class="flex flex-wrap items-center gap-1.5">
                            @if ($ourRecord['is_new_customer'] ?? true)
                                <span class="inline-flex rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600">New customer</span>
                            @else
                                <span class="inline-flex rounded-full bg-emerald-50 px-1.5 py-0.5 text-[10px] font-medium text-emerald-700">Returning</span>
                            @endif
                            <span class="text-[10px] text-muted">
                                @if (($ourRecord['total_orders'] ?? 0) < 1)
                                    No orders yet
                                @else
                                    {{ $ourRecord['total_orders'] }} {{ $ourRecord['total_orders'] === 1 ? 'order' : 'orders' }}
                                @endif
                            </span>
                        </div>
                        <div>
                            <div class="mb-0.5 text-[9px] font-semibold uppercase tracking-wide text-muted">Web orders</div>
                            @if (($ourRecord['status_counts'] ?? []) === [])
                                <p class="text-[10px] text-muted">—</p>
                            @else
                                <ul class="space-y-0.5">
                                    @foreach ($ourRecord['status_counts'] as $statusRow)
                                        <li class="flex items-center justify-between gap-2 text-[11px] leading-tight">
                                            <span class="inline-flex min-w-0 items-center gap-1.5 text-ink">
                                                <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background: {{ $statusRow['dot_color'] }}"></span>
                                                <span class="truncate">{{ $statusRow['label'] }}</span>
                                            </span>
                                            <span class="shrink-0 font-semibold tabular-nums text-ink">{{ $statusRow['count'] }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @foreach ($courierCards as $courier)
                <div class="courier-ratio-card shrink-0 overflow-hidden rounded-lg border border-ink/10 bg-white shadow-sm">
                    <div
                        class="flex items-center gap-1.5 px-2 py-1.5 text-[11px] font-semibold text-white"
                        style="background: {{ $courier['header_color'] }}"
                    >
                        @if (! empty($courier['logo']))
                            <img src="{{ $courier['logo'] }}" alt="" class="h-4 w-4 rounded-sm bg-white/90 object-contain p-px">
                        @elseif ($courier['key'] === 'overall')
                            <svg class="h-3.5 w-3.5 shrink-0 opacity-90" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 0 1-9.201 2.466l-.312-.311-1.414 1.414.312.311a7.5 7.5 0 0 0 11.712-1.536l.78-.78-1.415-1.414-.78.78Zm-10.624-2.85a5.5 5.5 0 0 1 9.201-2.466l.312.311 1.414-1.414-.312-.311A7.5 7.5 0 0 0 3.192 6.15l-.78.78 1.414 1.414.78-.78Z" clip-rule="evenodd"/></svg>
                        @endif
                        <span class="truncate">{{ $courier['name'] }}</span>
                    </div>
                    <div class="px-2 py-1.5">
                        <dl class="space-y-0.5 text-[11px] leading-tight">
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-muted">Success Rate</dt>
                                <dd class="font-semibold tabular-nums text-emerald-600">{{ $courier['success_rate'] ?? '—' }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-muted">Total</dt>
                                <dd class="font-semibold tabular-nums text-ink">{{ $courier['total'] }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-muted">Success</dt>
                                <dd class="font-semibold tabular-nums text-ink">{{ $courier['success'] }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-muted">Cancelled</dt>
                                <dd class="font-semibold tabular-nums text-ink">{{ $courier['cancelled'] }}</dd>
                            </div>
                        </dl>
                        <div class="mt-1.5 h-1 overflow-hidden rounded-full bg-slate-100">
                            <div
                                class="h-full rounded-full bg-emerald-500"
                                style="width: {{ min(100, max(0, $courier['success_rate_value'] ?? 0)) }}%"
                            ></div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <button
            type="button"
            class="courier-ratio-next absolute right-0 top-1/2 z-10 -translate-y-1/2 inline-flex h-7 w-7 items-center justify-center rounded-full border border-ink/10 bg-white text-ink shadow-sm hover:bg-mist"
            data-scroll-target="{{ $scrollId }}"
            aria-label="Scroll courier ratio cards"
        >
            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd"/></svg>
        </button>
    </div>

    @once
        <script>
            document.addEventListener('click', function (event) {
                const button = event.target.closest('.courier-ratio-next');
                if (!button) return;
                const target = document.getElementById(button.getAttribute('data-scroll-target'));
                if (!target) return;
                target.scrollBy({ left: 168, behavior: 'smooth' });
            });
        </script>
    @endonce
@endif
