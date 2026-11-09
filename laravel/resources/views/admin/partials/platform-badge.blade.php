@php
    $platform = strtolower(trim((string) ($platform ?? 'direct')));
    $looksLikeIp = (bool) preg_match('/^\d{1,3}([._-]\d{1,3}){3}$/', $platform)
        || (bool) filter_var(str_replace('_', '.', $platform), FILTER_VALIDATE_IP);
    $knownPlatforms = ['facebook', 'instagram', 'google', 'tiktok', 'youtube', 'referral', 'direct'];
@endphp
@if (! $looksLikeIp && (isset($customLabel) || in_array($platform, $knownPlatforms, true)))
    @php
        $badgeLabel = isset($customLabel) ? $customLabel : app(\App\Support\TrafficAttribution::class)->platformLabel($platform);
        $classes = match ($platform) {
            'facebook' => 'bg-sky-100 text-sky-800',
            'instagram' => 'bg-fuchsia-100 text-fuchsia-800',
            'google' => 'bg-emerald-100 text-emerald-800',
            'tiktok' => 'bg-zinc-200 text-zinc-800',
            'youtube' => 'bg-red-100 text-red-800',
            'referral' => 'bg-amber-100 text-amber-800',
            default => 'bg-mist text-sage',
        };
    @endphp
    <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-semibold leading-none {{ $classes }}">{{ $badgeLabel }}</span>
@endif
