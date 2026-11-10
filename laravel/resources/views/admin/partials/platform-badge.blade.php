@php
    $platform = strtolower(trim((string) ($platform ?? 'direct')));
    $looksLikeIp = (bool) preg_match('/^\d{1,3}([._-]\d{1,3}){3}$/', $platform)
        || (bool) filter_var(str_replace('_', '.', $platform), FILTER_VALIDATE_IP);
@endphp
@if (! $looksLikeIp && (isset($customLabel) || \App\Support\OrderSources::isKnown($platform)))
    @php
        $badgeLabel = isset($customLabel) ? $customLabel : \App\Support\OrderSources::label($platform);
        $classes = \App\Support\OrderSources::badgeClass($platform);
    @endphp
    <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-semibold leading-none {{ $classes }}">{{ $badgeLabel }}</span>
@endif
