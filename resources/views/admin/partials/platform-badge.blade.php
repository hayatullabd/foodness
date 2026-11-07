@php
    $platform = strtolower((string) ($platform ?? 'direct'));
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
<span class="inline-flex items-center rounded px-2 py-0.5 text-xs font-medium {{ $classes }}">{{ $badgeLabel }}</span>
