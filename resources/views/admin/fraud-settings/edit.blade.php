@extends('layouts.admin')

@section('title', 'Manage Courier Ratio API')
@section('heading', 'Manage Courier Ratio API')
@section('subheading', 'Configure courier ratio check providers. Only one provider can be active at a time.')

@section('content')
    <div class="grid gap-6 lg:grid-cols-2">
        {{-- BD Courier Card --}}
        @php
            $isBdCourierActive = $fraudSetting->is_active && $fraudSetting->activeProvider() === \App\Models\FraudSetting::PROVIDER_BDCOURIER;
        @endphp
        <form
            method="POST"
            action="{{ route('admin.fraud-settings.update') }}"
            class="overflow-hidden border border-ink/10 bg-white shadow-sm flex flex-col justify-between"
        >
            @csrf
            @method('PUT')
            <input type="hidden" name="target_provider" value="bdcourier">

            <div>
                <div class="flex border-b border-ink/10">
                    <div class="w-1.5 shrink-0 {{ $isBdCourierActive ? 'bg-emerald-600' : 'bg-ink/40' }}" aria-hidden="true"></div>
                    <div class="flex flex-1 items-start justify-between gap-3 px-4 py-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-sm font-semibold text-ink">BD Courier Ratio Checker</h2>
                                @if ($isBdCourierActive)
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-800">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600">
                                        Inactive
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-muted">api.bdcourier.com</p>
                        </div>
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-mist text-[11px] font-bold tracking-tight text-ink">
                            BDC
                        </span>
                    </div>
                </div>

                <div class="space-y-4 px-4 py-4">
                    <div>
                        <label class="admin-label" for="bdcourier_api_key">API KEY (BEARER TOKEN)</label>
                        <input
                            id="bdcourier_api_key"
                            name="bdcourier_api_key"
                            type="password"
                            value=""
                            class="field"
                            autocomplete="off"
                            placeholder="{{ $maskedBdCourierKey ? 'Leave blank to keep current key' : 'Paste BD Courier API key' }}"
                        >
                        @if ($maskedBdCourierKey)
                            <p class="mt-1 text-xs text-muted">Current key: <span class="font-mono tracking-wide">{{ $maskedBdCourierKey }}</span></p>
                        @else
                            <p class="mt-1 text-xs text-muted">No API key saved yet.</p>
                        @endif
                        @error('bdcourier_api_key') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center justify-between rounded-md border border-ink/10 bg-mist/60 px-3 py-2.5">
                        <div class="flex items-center gap-2 text-sm text-ink">
                            <svg class="h-4 w-4 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1012.728 0M12 3v9" />
                            </svg>
                            <div>
                                <span class="font-medium">Active Status</span>
                                <p class="text-xs text-muted">Set as the active courier ratio checker</p>
                            </div>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                class="peer sr-only"
                                @checked($isBdCourierActive)
                            >
                            <span class="h-5 w-9 rounded-full bg-fog transition peer-checked:bg-emerald-600"></span>
                            <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                        </label>
                    </div>

                    <p class="text-xs text-muted">
                        Endpoint: <code>POST https://api.bdcourier.com/courier-check</code>
                    </p>
                </div>
            </div>

            <div class="border-t border-ink/10 px-4 py-3 bg-mist/30">
                <button type="submit" class="inline-flex w-full items-center justify-center gap-1.5 rounded-md bg-ink px-3 py-2.5 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-ink/90">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                    Save BD Courier Config
                </button>
            </div>
        </form>

        {{-- Hoorin Card --}}
        @php
            $isHoorinActive = $fraudSetting->is_active && $fraudSetting->activeProvider() === \App\Models\FraudSetting::PROVIDER_HOORIN;
        @endphp
        <form
            method="POST"
            action="{{ route('admin.fraud-settings.update') }}"
            class="overflow-hidden border border-ink/10 bg-white shadow-sm flex flex-col justify-between"
        >
            @csrf
            @method('PUT')
            <input type="hidden" name="target_provider" value="hoorin">

            <div>
                <div class="flex border-b border-ink/10">
                    <div class="w-1.5 shrink-0 {{ $isHoorinActive ? 'bg-emerald-600' : 'bg-ink/40' }}" aria-hidden="true"></div>
                    <div class="flex flex-1 items-start justify-between gap-3 px-4 py-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-sm font-semibold text-ink">Hoorin Courier Search</h2>
                                @if ($isHoorinActive)
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-800">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600">
                                        Inactive
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-muted">plugin.hoorin.com / dash.hoorin.com</p>
                        </div>
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-mist text-[11px] font-bold tracking-tight text-ink">
                            HRN
                        </span>
                    </div>
                </div>

                <div class="space-y-4 px-4 py-4">
                    <div>
                        <label class="admin-label" for="hoorin_api_key">HOORIN API KEY</label>
                        <input
                            id="hoorin_api_key"
                            name="hoorin_api_key"
                            type="password"
                            value=""
                            class="field"
                            autocomplete="off"
                            placeholder="{{ $maskedHoorinKey ? 'Leave blank to keep current key' : 'Paste Hoorin API key' }}"
                        >
                        @if ($maskedHoorinKey)
                            <p class="mt-1 text-xs text-muted">Current key: <span class="font-mono tracking-wide">{{ $maskedHoorinKey }}</span></p>
                        @else
                            <p class="mt-1 text-xs text-muted">No API key saved yet.</p>
                        @endif
                        @error('hoorin_api_key') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center justify-between rounded-md border border-ink/10 bg-mist/60 px-3 py-2.5">
                        <div class="flex items-center gap-2 text-sm text-ink">
                            <svg class="h-4 w-4 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1012.728 0M12 3v9" />
                            </svg>
                            <div>
                                <span class="font-medium">Active Status</span>
                                <p class="text-xs text-muted">Set as the active courier ratio checker</p>
                            </div>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                class="peer sr-only"
                                @checked($isHoorinActive)
                            >
                            <span class="h-5 w-9 rounded-full bg-fog transition peer-checked:bg-emerald-600"></span>
                            <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                        </label>
                    </div>

                    <p class="text-xs text-muted">
                        Endpoint: <code>GET https://plugin.hoorin.com/courier/api/v1/search</code>
                    </p>
                </div>
            </div>

            <div class="border-t border-ink/10 px-4 py-3 bg-mist/30">
                <button type="submit" class="inline-flex w-full items-center justify-center gap-1.5 rounded-md bg-ink px-3 py-2.5 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-ink/90">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                    Save Hoorin Config
                </button>
            </div>
        </form>
    </div>
@endsection
