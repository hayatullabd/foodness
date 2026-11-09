@extends('layouts.admin')

@section('title', 'Delivery Methods')
@section('heading', 'Delivery Methods')
@section('subheading', 'Add multiple courier accounts (same type allowed). Active methods appear when sending orders.')

@section('actions')
    <a href="{{ route('admin.courier-panel.index') }}" class="admin-btn-secondary">
        Courier Dashboard
    </a>
    <a href="{{ route('admin.courier-apis.index', array_merge(request()->query(), ['create' => 1])) }}" class="admin-btn-primary" id="add-delivery-method-btn">
        + Add Delivery Method
    </a>
@endsection

@php
    $editing = $editing ?? null;
    $isEditing = $editing !== null;
    $formProvider = old('provider', $editing?->provider ?? 'steadfast');
    $sendProductNames = old('send_product_names', $editing ? $editing->metaValue('send_product_names', true) : true);
@endphp

@push('styles')
    <style>
        #delivery-method-dialog .admin-modal-panel--wide { width: min(58rem, 96vw); }
        #delivery-method-dialog .dm-section {
            margin-bottom: 0.75rem;
            border: 1px solid rgba(15, 23, 42, 0.1);
            border-radius: 0.625rem;
            background: #fff;
            padding: 0.85rem 0.9rem 0.95rem;
        }
        #delivery-method-dialog .dm-section h3 { margin-bottom: 0.7rem; }
    </style>
@endpush

@section('content')
    <div class="mb-3 grid gap-2 sm:grid-cols-3">
        <div class="flex items-center justify-between rounded-md border border-ink/10 bg-white px-3 py-2.5 shadow-sm">
            <div>
                <p class="text-[11px] font-medium uppercase tracking-wide text-muted">Total Methods</p>
                <p class="text-xl font-semibold text-ink">{{ $stats['total'] }}</p>
            </div>
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-mist text-sage" aria-hidden="true">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/></svg>
            </span>
        </div>
        <div class="flex items-center justify-between rounded-md border border-ink/10 bg-white px-3 py-2.5 shadow-sm">
            <div>
                <p class="text-[11px] font-medium uppercase tracking-wide text-muted">Active</p>
                <p class="text-xl font-semibold text-sage">{{ $stats['active'] }}</p>
            </div>
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-sage/10 text-sage" aria-hidden="true">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 17l6-6 4 4 8-8"/></svg>
            </span>
        </div>
        <div class="flex items-center justify-between rounded-md border border-ink/10 bg-white px-3 py-2.5 shadow-sm">
            <div>
                <p class="text-[11px] font-medium uppercase tracking-wide text-muted">Inactive</p>
                <p class="text-xl font-semibold text-accent">{{ $stats['inactive'] }}</p>
            </div>
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-accent/10 text-accent" aria-hidden="true">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7l6 6 4-4 8 8"/></svg>
            </span>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.courier-apis.index') }}" class="mb-3 flex flex-wrap items-center gap-2 rounded-md border border-ink/10 bg-white p-2.5 shadow-sm">
        <div class="relative min-w-[14rem] flex-1">
            <span class="pointer-events-none absolute inset-y-0 left-2.5 flex items-center text-muted">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z"/></svg>
            </span>
            <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search delivery methods..." class="field pl-8">
        </div>
        <select name="type" class="field max-w-[9rem]">
            <option value="">All Types</option>
            @foreach (\App\Models\CourierApi::PROVIDERS as $provider)
                <option value="{{ $provider }}" @selected($filters['type'] === $provider)>{{ strtoupper($provider) }}</option>
            @endforeach
        </select>
        <select name="status" class="field max-w-[9rem]">
            <option value="">All Status</option>
            <option value="active" @selected($filters['status'] === 'active')>Active</option>
            <option value="inactive" @selected($filters['status'] === 'inactive')>Inactive</option>
        </select>
        <input type="hidden" name="view" value="{{ $filters['view'] }}">
        <button type="submit" class="rounded-md border border-ink/10 bg-mist px-3 py-2 text-xs font-semibold text-ink hover:bg-fog/70">Filter</button>
        <div class="ml-auto inline-flex overflow-hidden rounded-md border border-ink/10">
            <a href="{{ route('admin.courier-apis.index', array_merge(request()->query(), ['view' => 'list'])) }}"
               class="px-2.5 py-2 {{ $filters['view'] === 'list' ? 'bg-ink text-white' : 'bg-white text-muted hover:bg-mist' }}" title="List view">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16M4 12h16M4 17h16"/></svg>
            </a>
            <a href="{{ route('admin.courier-apis.index', array_merge(request()->query(), ['view' => 'grid'])) }}"
               class="px-2.5 py-2 {{ $filters['view'] === 'grid' ? 'bg-ink text-white' : 'bg-white text-muted hover:bg-mist' }}" title="Grid view">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 0h6v6h-6v-6z"/></svg>
            </a>
        </div>
    </form>

    @if ($filters['view'] === 'grid')
        <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
            @forelse ($methods as $method)
                <div class="rounded-md border border-ink/10 bg-white p-3 shadow-sm">
                    <div class="mb-2 flex items-start justify-between gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded border border-sage/30 bg-sage/10 px-1.5 py-0.5 text-[10px] font-bold tracking-wide text-sage">
                            <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-sage text-[9px] text-white">{{ strtoupper(substr($method->provider, 0, 1)) }}</span>
                            {{ $method->providerBadge() }}
                        </span>
                        @if ($method->is_active)
                            <span class="rounded bg-sage/15 px-1.5 py-0.5 text-[10px] font-semibold text-sage">Active</span>
                        @else
                            <span class="rounded bg-fog px-1.5 py-0.5 text-[10px] font-semibold text-muted">Inactive</span>
                        @endif
                    </div>
                    <p class="text-sm font-semibold text-ink">{{ $method->displayName() }}</p>
                    <p class="mt-1 text-xs text-muted">{{ $method->is_preferred ? 'Preferred for '.strtoupper($method->provider) : 'Not preferred' }}</p>
                    <div class="mt-3 flex gap-2">
                        <a href="{{ route('admin.courier-apis.index', array_merge(request()->query(), ['edit' => $method->id])) }}" class="rounded-md bg-ink px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-ink/90">Edit</a>
                        <form method="POST" action="{{ route('admin.courier-apis.destroy', $method) }}" onsubmit="return confirm('Delete this delivery method?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-md border border-accent/30 px-2.5 py-1.5 text-xs font-semibold text-accent hover:bg-accent/5">Delete</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="col-span-full rounded-md border border-dashed border-ink/15 bg-white px-4 py-10 text-center text-sm text-muted">
                    No delivery methods yet. Add your first courier account.
                </div>
            @endforelse
        </div>
    @else
        <div class="overflow-hidden rounded-md border border-ink/10 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-mist text-[11px] uppercase tracking-wide text-muted">
                        <tr>
                            <th class="px-3 py-2 font-medium">Courier Type</th>
                            <th class="px-3 py-2 font-medium">Name</th>
                            <th class="px-3 py-2 font-medium">Status</th>
                            <th class="px-3 py-2 font-medium">Preferred</th>
                            <th class="px-3 py-2 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($methods as $method)
                            <tr class="border-t border-ink/5">
                                <td class="px-3 py-2">
                                    <span class="inline-flex items-center gap-1.5 rounded border border-sage/30 bg-sage/10 px-1.5 py-0.5 text-[10px] font-bold tracking-wide text-sage">
                                        <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-sage text-[9px] text-white">{{ strtoupper(substr($method->provider, 0, 1)) }}</span>
                                        {{ $method->providerBadge() }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 font-medium text-ink">{{ $method->displayName() }}</td>
                                <td class="px-3 py-2">
                                    @if ($method->is_active)
                                        <span class="rounded bg-sage/15 px-1.5 py-0.5 text-[11px] font-semibold text-sage">Active</span>
                                    @else
                                        <span class="rounded bg-fog px-1.5 py-0.5 text-[11px] font-semibold text-muted">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    @if ($method->is_preferred)
                                        <span class="inline-flex items-center text-sage" title="Preferred">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center text-fog" title="Not preferred">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118L2.08 10.1c-.783-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.courier-apis.index', array_merge(request()->query(), ['edit' => $method->id])) }}" class="inline-flex items-center gap-1 rounded-md bg-ink px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-ink/90">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-.707.464l-3.12.78a.5.5 0 01-.606-.606l.78-3.12a2 2 0 01.464-.707z"/></svg>
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('admin.courier-apis.destroy', $method) }}" onsubmit="return confirm('Delete this delivery method?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-md border border-accent/30 px-2.5 py-1.5 text-xs font-semibold text-accent hover:bg-accent/5">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-sm text-muted">No delivery methods yet. Add your first courier account.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <dialog id="delivery-method-dialog" class="admin-modal-overlay">
        <div class="admin-modal-panel admin-modal-panel--wide bg-white shadow-xl">
        <div class="flex shrink-0 items-center justify-between border-b border-ink/10 bg-mist/60 px-4 py-3">
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wide text-muted">Courier account</p>
                <h2 class="text-sm font-semibold text-ink">{{ $isEditing ? 'Edit Delivery Method' : 'Create Delivery Method' }}</h2>
            </div>
            <button type="button" data-close-modal class="rounded p-1 text-muted hover:bg-mist hover:text-ink" aria-label="Close">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form
            method="POST"
            action="{{ $isEditing ? route('admin.courier-apis.update', $editing) : route('admin.courier-apis.store') }}"
            class="min-h-0 flex-1 overflow-y-auto bg-mist/30 px-4 py-3"
            id="delivery-method-form"
        >
            @csrf
            @if ($isEditing)
                @method('PUT')
            @endif

            <section class="dm-section">
                <h3 class="mb-2 flex items-center gap-1.5 border-b border-ink/10 pb-1 text-[11px] font-semibold uppercase tracking-wide text-ink">
                    <span class="h-1.5 w-1.5 rounded-full bg-sage"></span>
                    Basic Details
                </h3>
                <div class="grid gap-2.5 sm:grid-cols-2">
                    <div>
                        <label class="admin-label" for="name">Delivery Method Name</label>
                        <input id="name" name="name" type="text" required class="field" value="{{ old('name', $editing?->name) }}" placeholder="Courier Name...">
                        @error('name') <p class="mt-1 text-xs text-accent">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label" for="provider">Courier Type</label>
                        <select id="provider" name="provider" class="field" @disabled($isEditing) data-provider-select>
                            @foreach (\App\Models\CourierApi::PROVIDERS as $provider)
                                <option value="{{ $provider }}" @selected($formProvider === $provider)>{{ strtoupper($provider) }}</option>
                            @endforeach
                        </select>
                        @if ($isEditing)
                            <input type="hidden" name="provider" value="{{ $editing->provider }}">
                        @endif
                    </div>
                </div>
            </section>

            <section class="dm-section" data-provider-panel="steadfast">
                <h3 class="mb-2 flex items-center gap-1.5 border-b border-ink/10 pb-1 text-[11px] font-semibold uppercase tracking-wide text-ink">
                    <span class="h-1.5 w-1.5 rounded-full bg-sage"></span>
                    Steadfast Integration Settings
                </h3>
                <div class="grid gap-2.5 sm:grid-cols-2">
                    <div>
                        <label class="admin-label" for="sf_api_key">SteadFast ApiKey</label>
                        <input id="sf_api_key" name="api_key" type="text" class="field" value="" autocomplete="off"
                            placeholder="{{ $isEditing && $editing->provider === 'steadfast' && $editing->maskedAttribute('api_key') ? 'Leave blank to keep current' : 'API key' }}"
                            data-for-provider="steadfast">
                        @if ($isEditing && $editing->provider === 'steadfast' && $editing->maskedAttribute('api_key'))
                            <p class="mt-1 text-xs text-muted">Current: <span class="font-mono">{{ $editing->maskedAttribute('api_key') }}</span></p>
                        @endif
                    </div>
                    <div>
                        <label class="admin-label" for="sf_secret_key">SteadFast Api Secret</label>
                        <input id="sf_secret_key" name="secret_key" type="password" class="field" value="" autocomplete="off"
                            placeholder="{{ $isEditing && $editing->provider === 'steadfast' && $editing->maskedAttribute('secret_key') ? 'Leave blank to keep current' : 'Secret key' }}"
                            data-for-provider="steadfast">
                        @if ($isEditing && $editing->provider === 'steadfast' && $editing->maskedAttribute('secret_key'))
                            <p class="mt-1 text-xs text-muted">Current: <span class="font-mono">{{ $editing->maskedAttribute('secret_key') }}</span></p>
                        @endif
                    </div>
                    <div class="sm:col-span-2">
                        <label class="admin-label" for="sf_base_url">API Base URL (optional)</label>
                        <input id="sf_base_url" name="base_url" type="url" class="field" data-for-provider="steadfast"
                            value="{{ old('base_url', $editing?->provider === 'steadfast' ? ($editing->base_url ?: 'https://portal.packzy.com/api/v1') : 'https://portal.packzy.com/api/v1') }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="admin-label" for="sf_webhook_url">Webhook URL</label>
                        <div class="flex gap-2">
                            <input id="sf_webhook_url" type="text" readonly value="{{ $steadfastWebhookUrl }}" class="field bg-mist/50" data-copy-value>
                            <button type="button" class="shrink-0 rounded-md border border-ink/10 bg-white px-2.5 text-xs font-medium text-ink hover:bg-mist" data-copy-btn>Copy</button>
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="admin-label" for="sf_token">Steadfast Webhook Token (Optional)</label>
                        <input id="sf_token" name="token" type="text" class="field" value="" autocomplete="off" data-for-provider="steadfast"
                            placeholder="{{ $isEditing && $editing->provider === 'steadfast' && $editing->maskedAttribute('token') ? 'Leave blank to keep current' : 'Paste the token from your Steadfast panel.' }}">
                        <p class="mt-1 text-xs text-muted">Required. Unsigned Steadfast webhooks are rejected.</p>
                    </div>
                    <div>
                        <label class="admin-label" for="sf_account_email">Steadfast Account Email (Optional)</label>
                        <input id="sf_account_email" name="account_email" type="email" class="field" data-for-provider="steadfast"
                            value="{{ old('account_email', $editing?->provider === 'steadfast' ? $editing->metaValue('account_email') : '') }}"
                            placeholder="your-email@example.com">
                    </div>
                    <div>
                        <label class="admin-label" for="sf_account_password">Steadfast Account Password (Optional)</label>
                        <input id="sf_account_password" name="account_password" type="password" class="field" value="" autocomplete="off" data-for-provider="steadfast"
                            placeholder="{{ $isEditing && $editing?->provider === 'steadfast' && filled($editing->metaValue('account_password')) ? 'Leave blank to keep current' : 'Enter your Steadfast password' }}">
                    </div>
                </div>
            </section>

            <section class="dm-section hidden" data-provider-panel="pathao">
                <h3 class="mb-2 flex items-center gap-1.5 border-b border-ink/10 pb-1 text-[11px] font-semibold uppercase tracking-wide text-ink">
                    <span class="h-1.5 w-1.5 rounded-full bg-ink"></span>
                    Pathao Integration Settings
                </h3>
                <div class="grid gap-2.5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="admin-label" for="ph_base_url">API URL</label>
                        <input id="ph_base_url" name="base_url" type="url" class="field" data-for-provider="pathao"
                            value="{{ old('base_url', $editing?->provider === 'pathao' ? ($editing->base_url ?: 'https://api-hermes.pathao.com') : 'https://api-hermes.pathao.com') }}">
                    </div>
                    <div>
                        <label class="admin-label" for="ph_api_key">Client ID</label>
                        <input id="ph_api_key" name="api_key" type="text" class="field" value="" autocomplete="off" data-for-provider="pathao"
                            placeholder="{{ $isEditing && $editing->provider === 'pathao' && $editing->maskedAttribute('api_key') ? 'Leave blank to keep current' : 'Client ID' }}">
                    </div>
                    <div>
                        <label class="admin-label" for="ph_secret_key">Client Secret</label>
                        <input id="ph_secret_key" name="secret_key" type="password" class="field" value="" autocomplete="off" data-for-provider="pathao"
                            placeholder="{{ $isEditing && $editing->provider === 'pathao' && $editing->maskedAttribute('secret_key') ? 'Leave blank to keep current' : 'Client secret' }}">
                    </div>
                    <div>
                        <label class="admin-label" for="ph_username">Username / Email</label>
                        <input id="ph_username" name="username" type="text" class="field" data-for-provider="pathao"
                            value="{{ old('username', $editing?->provider === 'pathao' ? $editing->username : '') }}">
                    </div>
                    <div>
                        <label class="admin-label" for="ph_password">Password</label>
                        <input id="ph_password" name="password" type="password" class="field" value="" autocomplete="off" data-for-provider="pathao"
                            placeholder="{{ $isEditing && $editing->provider === 'pathao' && $editing->maskedAttribute('password') ? 'Leave blank to keep current' : 'Password' }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="admin-label" for="ph_token">Access Token</label>
                        <textarea id="ph_token" name="token" rows="2" class="field font-mono text-xs" data-for-provider="pathao"
                            placeholder="{{ $isEditing && $editing->provider === 'pathao' && $editing->maskedAttribute('token') ? 'Leave blank to keep current' : 'Generated or pasted access token' }}"></textarea>
                    </div>
                    <div>
                        <label class="admin-label" for="ph_store_id">Store ID (optional)</label>
                        <input id="ph_store_id" name="store_id" type="number" min="1" class="field" data-for-provider="pathao"
                            value="{{ old('store_id', $editing?->provider === 'pathao' ? $editing->metaValue('store_id') : '') }}">
                    </div>
                </div>
            </section>

            <section class="dm-section hidden" data-provider-panel="redx">
                <h3 class="mb-2 flex items-center gap-1.5 border-b border-ink/10 pb-1 text-[11px] font-semibold uppercase tracking-wide text-ink">
                    <span class="h-1.5 w-1.5 rounded-full bg-accent"></span>
                    RedX Integration Settings
                </h3>
                <div class="grid gap-2.5 sm:grid-cols-2">
                    <div>
                        <label class="admin-label" for="rx_environment">Environment</label>
                        <select id="rx_environment" name="environment" class="field" data-for-provider="redx">
                            <option value="production" @selected(old('environment', $editing?->provider === 'redx' ? $editing->redxEnvironment() : 'production') === 'production')>Production</option>
                            <option value="sandbox" @selected(old('environment', $editing?->provider === 'redx' ? $editing->redxEnvironment() : 'production') === 'sandbox')>Sandbox</option>
                        </select>
                    </div>
                    <div>
                        <label class="admin-label" for="rx_webhook_url">Webhook URL</label>
                        <div class="flex gap-2">
                            <input id="rx_webhook_url" type="text" readonly value="{{ $redxWebhookUrl }}" class="field bg-mist/50" data-copy-value>
                            <button type="button" class="shrink-0 rounded-md border border-ink/10 bg-white px-2.5 text-xs font-medium text-ink hover:bg-mist" data-copy-btn>Copy</button>
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="admin-label" for="rx_token">API Access Token</label>
                        <textarea id="rx_token" name="token" rows="2" class="field font-mono text-xs" data-for-provider="redx"
                            placeholder="{{ $isEditing && $editing->provider === 'redx' && $editing->maskedAttribute('token') ? 'Leave blank to keep current' : 'Paste RedX API access token' }}"></textarea>
                        <p class="mt-1 text-xs text-muted">Webhooks must send this token as a Bearer token or in X-Redx-Token. Empty tokens are rejected.</p>
                    </div>
                    <div>
                        <label class="admin-label" for="rx_default_area_id">Default area ID</label>
                        <input id="rx_default_area_id" name="default_area_id" type="number" min="1" class="field" data-for-provider="redx"
                            value="{{ old('default_area_id', $editing?->provider === 'redx' ? $editing->metaValue('default_area_id') : '') }}">
                    </div>
                    <div>
                        <label class="admin-label" for="rx_default_area_name">Default area name</label>
                        <input id="rx_default_area_name" name="default_area_name" type="text" class="field" data-for-provider="redx"
                            value="{{ old('default_area_name', $editing?->provider === 'redx' ? $editing->metaValue('default_area_name') : '') }}">
                    </div>
                    <div>
                        <label class="admin-label" for="rx_pickup_store_id">Pickup store ID</label>
                        <input id="rx_pickup_store_id" name="pickup_store_id" type="number" min="1" class="field" data-for-provider="redx"
                            value="{{ old('pickup_store_id', $editing?->provider === 'redx' ? $editing->metaValue('pickup_store_id') : '') }}">
                    </div>
                </div>
            </section>

            <section class="dm-section hidden" data-provider-panel="carrybee">
                <h3 class="mb-2 flex items-center gap-1.5 border-b border-ink/10 pb-1 text-[11px] font-semibold uppercase tracking-wide text-ink">
                    <span class="h-1.5 w-1.5 rounded-full bg-sage"></span>
                    Carrybee Integration Settings
                </h3>
                <div class="grid gap-2.5 sm:grid-cols-2">
                    <div>
                        <label class="admin-label" for="cb_environment">Environment</label>
                        <select id="cb_environment" name="environment" class="field" data-for-provider="carrybee">
                            <option value="production" @selected(old('environment', $editing?->provider === 'carrybee' ? $editing->carrybeeEnvironment() : 'production') === 'production')>Production — developers.carrybee.com</option>
                            <option value="sandbox" @selected(old('environment', $editing?->provider === 'carrybee' ? $editing->carrybeeEnvironment() : 'production') === 'sandbox')>Sandbox — sandbox.carrybee.com</option>
                        </select>
                    </div>
                    <div>
                        <label class="admin-label" for="cb_store_id">Store ID</label>
                        <input id="cb_store_id" name="store_id" type="text" class="field" data-for-provider="carrybee"
                            value="{{ old('store_id', $editing?->provider === 'carrybee' ? $editing->metaValue('store_id') : '') }}"
                            placeholder="Leave blank to use the default pickup store">
                    </div>
                    <div>
                        <label class="admin-label" for="cb_api_key">Client-ID</label>
                        <input id="cb_api_key" name="api_key" type="text" class="field" value="" autocomplete="off" data-for-provider="carrybee"
                            placeholder="{{ $isEditing && $editing->provider === 'carrybee' && $editing->maskedAttribute('api_key') ? 'Leave blank to keep current' : 'Client-ID' }}">
                        @if ($isEditing && $editing->provider === 'carrybee' && $editing->maskedAttribute('api_key'))
                            <p class="mt-1 text-xs text-muted">Current: <span class="font-mono">{{ $editing->maskedAttribute('api_key') }}</span></p>
                        @endif
                    </div>
                    <div>
                        <label class="admin-label" for="cb_secret_key">Client-Secret</label>
                        <input id="cb_secret_key" name="secret_key" type="password" class="field" value="" autocomplete="off" data-for-provider="carrybee"
                            placeholder="{{ $isEditing && $editing->provider === 'carrybee' && $editing->maskedAttribute('secret_key') ? 'Leave blank to keep current' : 'Client-Secret' }}">
                        @if ($isEditing && $editing->provider === 'carrybee' && $editing->maskedAttribute('secret_key'))
                            <p class="mt-1 text-xs text-muted">Current: <span class="font-mono">{{ $editing->maskedAttribute('secret_key') }}</span></p>
                        @endif
                    </div>
                    <div class="sm:col-span-2">
                        <label class="admin-label" for="cb_username">Client-Context</label>
                        <input id="cb_username" name="username" type="text" class="field" value="" autocomplete="off" data-for-provider="carrybee"
                            placeholder="{{ $isEditing && $editing->provider === 'carrybee' && $editing->maskedAttribute('username') ? 'Leave blank to keep current' : 'Client-Context' }}">
                        @if ($isEditing && $editing->provider === 'carrybee' && $editing->maskedAttribute('username'))
                            <p class="mt-1 text-xs text-muted">Current: <span class="font-mono">{{ $editing->maskedAttribute('username') }}</span></p>
                        @endif
                    </div>
                    <div class="sm:col-span-2">
                        <label class="admin-label" for="cb_webhook_url">Webhook URL</label>
                        <div class="flex gap-2">
                            <input id="cb_webhook_url" type="text" readonly value="{{ $carrybeeWebhookUrl }}" class="field bg-mist/50" data-copy-value>
                            <button type="button" class="shrink-0 rounded-md border border-ink/10 bg-white px-2.5 text-xs font-medium text-ink hover:bg-mist" data-copy-btn>Copy</button>
                        </div>
                        <p class="mt-1 text-xs text-muted">Carrybee ড্যাশবোর্ডের Webhook Integration-এ এই URL বসান।</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="admin-label" for="cb_token">Webhook secret</label>
                        <input id="cb_token" name="token" type="password" class="field" value="" autocomplete="off" data-for-provider="carrybee"
                            placeholder="{{ $isEditing && $editing->provider === 'carrybee' && $editing->maskedAttribute('token') ? 'Leave blank to keep current' : 'Secret from Carrybee Webhook Integration' }}">
                        <p class="mt-1 text-xs text-muted">হ্যাঁ, এটা দিতে হবে। Carrybee ওয়েবহুক সেটআপে যে secret দেয়, সেটাই এখানে পেস্ট করুন। ওরা সেটা <span class="font-mono">X-CB-Webhook-Integration-Header</span> হিসেবে পাঠায়। সিক্রেট না মিললে অর্ডার তৈরি হবে, কিন্তু ডেলিভারি স্ট্যাটাস আপডেট আসবে না।</p>
                    </div>
                    <div>
                        <label class="admin-label" for="cb_delivery_type">Delivery type</label>
                        <select id="cb_delivery_type" name="delivery_type" class="field" data-for-provider="carrybee">
                            <option value="1" @selected((string) old('delivery_type', $editing?->provider === 'carrybee' ? $editing->metaValue('delivery_type', 1) : 1) === '1')>Normal</option>
                            <option value="2" @selected((string) old('delivery_type', $editing?->provider === 'carrybee' ? $editing->metaValue('delivery_type', 1) : 1) === '2')>Express</option>
                        </select>
                    </div>
                    <div>
                        <label class="admin-label" for="cb_product_type">Product type</label>
                        <select id="cb_product_type" name="product_type" class="field" data-for-provider="carrybee">
                            <option value="1" @selected((string) old('product_type', $editing?->provider === 'carrybee' ? $editing->metaValue('product_type', 1) : 1) === '1')>Parcel</option>
                            <option value="2" @selected((string) old('product_type', $editing?->provider === 'carrybee' ? $editing->metaValue('product_type', 1) : 1) === '2')>Book</option>
                            <option value="3" @selected((string) old('product_type', $editing?->provider === 'carrybee' ? $editing->metaValue('product_type', 1) : 1) === '3')>Document</option>
                        </select>
                    </div>
                    <div>
                        <label class="admin-label" for="cb_item_weight">Default item weight (grams)</label>
                        <input id="cb_item_weight" name="item_weight" type="number" min="1" max="25000" class="field" data-for-provider="carrybee"
                            value="{{ old('item_weight', $editing?->provider === 'carrybee' ? $editing->metaValue('item_weight', 500) : 500) }}">
                    </div>
                </div>
            </section>

            <section class="dm-section">
                <h3 class="mb-2 flex items-center gap-1.5 border-b border-ink/10 pb-1 text-[11px] font-semibold uppercase tracking-wide text-ink">
                    <span class="h-1.5 w-1.5 rounded-full bg-sage"></span>
                    Preferences & Defaults
                </h3>
                <div class="mb-2.5">
                    <label class="admin-label" for="default_shipping_note">Default Shipping Note</label>
                    <textarea id="default_shipping_note" name="default_shipping_note" rows="2" class="field"
                        placeholder="Sent with every order when the order has no note">{{ old('default_shipping_note', $editing?->metaValue('default_shipping_note')) }}</textarea>
                </div>
                <div class="grid gap-2 sm:grid-cols-3">
                    <div class="flex items-center justify-between rounded-md border border-ink/10 bg-mist/50 px-3 py-2">
                        <span class="text-xs font-medium text-ink">Active</span>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" name="is_active" value="1" class="peer sr-only" @checked(old('is_active', $editing?->is_active ?? true))>
                            <span class="h-5 w-9 rounded-full bg-fog transition peer-checked:bg-sage"></span>
                            <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                        </label>
                    </div>
                    <div class="flex items-center justify-between rounded-md border border-ink/10 bg-mist/50 px-3 py-2">
                        <span class="text-xs font-medium text-ink">Send product names</span>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" name="send_product_names" value="1" class="peer sr-only" @checked($sendProductNames)>
                            <span class="h-5 w-9 rounded-full bg-fog transition peer-checked:bg-sage"></span>
                            <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                        </label>
                    </div>
                    <div class="flex items-center justify-between rounded-md border border-ink/10 bg-mist/50 px-3 py-2">
                        <span class="text-xs font-medium text-ink">Preferred</span>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" name="is_preferred" value="1" class="peer sr-only" @checked(old('is_preferred', $editing?->is_preferred ?? false))>
                            <span class="h-5 w-9 rounded-full bg-fog transition peer-checked:bg-sage"></span>
                            <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                        </label>
                    </div>
                </div>
                <p class="mt-1.5 text-xs text-muted">Preferred = default method when multiple accounts share the same courier type.</p>
            </section>
        </form>

        @if ($isEditing && $editing->provider === 'pathao')
            <form method="POST" action="{{ route('admin.courier-apis.pathao.generate-token', $editing) }}" class="shrink-0 border-t border-ink/10 px-4 py-2.5" id="pathao-token-form">
                @csrf
                <input type="hidden" name="base_url" id="pathao_token_base_url">
                <input type="hidden" name="api_key" id="pathao_token_api_key">
                <input type="hidden" name="secret_key" id="pathao_token_secret_key">
                <input type="hidden" name="username" id="pathao_token_username">
                <input type="hidden" name="password" id="pathao_token_password">
                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center rounded-md border border-ink/15 bg-mist px-3 py-2 text-xs font-semibold uppercase tracking-wide text-ink hover:bg-fog/70"
                    onclick="document.getElementById('pathao_token_base_url').value=document.getElementById('ph_base_url').value;document.getElementById('pathao_token_api_key').value=document.getElementById('ph_api_key').value;document.getElementById('pathao_token_secret_key').value=document.getElementById('ph_secret_key').value;document.getElementById('pathao_token_username').value=document.getElementById('ph_username').value;document.getElementById('pathao_token_password').value=document.getElementById('ph_password').value;"
                >
                    Generate Pathao Token
                </button>
            </form>
        @endif

        <div class="flex shrink-0 items-center justify-end gap-2 border-t border-ink/10 px-4 py-3">
            <button type="button" data-close-modal class="rounded-md border border-ink/10 bg-white px-3 py-2 text-xs font-semibold text-ink hover:bg-mist">Cancel</button>
            <button type="submit" form="delivery-method-form" class="rounded-md bg-ink px-3 py-2 text-xs font-semibold text-white hover:bg-ink/90">
                {{ $isEditing ? 'Update Delivery Method' : 'Create Delivery Method' }}
            </button>
        </div>
        </div>
    </dialog>

    <script>
        (function () {
            const dialog = document.getElementById('delivery-method-dialog');
            const providerSelect = document.querySelector('[data-provider-select]');
            const panels = document.querySelectorAll('[data-provider-panel]');
            const openForm = @json($openForm);

            function syncProviderFields(provider) {
                panels.forEach((panel) => {
                    const active = panel.getAttribute('data-provider-panel') === provider;
                    panel.classList.toggle('hidden', !active);
                    panel.querySelectorAll('[data-for-provider]').forEach((input) => {
                        input.disabled = !active;
                    });
                });
            }

            function clearModalQueryParams() {
                const url = new URL(window.location.href);
                url.searchParams.delete('create');
                url.searchParams.delete('edit');
                const next = url.pathname + url.search + url.hash;
                window.history.replaceState({}, '', next);
            }

            function closeDeliveryMethodModal() {
                if (!dialog) {
                    return;
                }

                if (typeof dialog.close === 'function' && dialog.open) {
                    dialog.close();
                    return;
                }

                dialog.removeAttribute('open');
                clearModalQueryParams();
            }

            if (providerSelect) {
                providerSelect.addEventListener('change', () => syncProviderFields(providerSelect.value));
                syncProviderFields(providerSelect.value || @json($formProvider));
            } else {
                syncProviderFields(@json($formProvider));
            }

            if (dialog) {
                dialog.querySelectorAll('[data-close-modal]').forEach((button) => {
                    button.addEventListener('click', (event) => {
                        event.preventDefault();
                        closeDeliveryMethodModal();
                    });
                });

                // Full-viewport dialog receives backdrop clicks on itself (not ::backdrop).
                dialog.addEventListener('click', (event) => {
                    if (event.target === dialog) {
                        closeDeliveryMethodModal();
                    }
                });

                dialog.addEventListener('close', () => {
                    clearModalQueryParams();
                });
            }

            if (openForm && dialog) {
                if (typeof dialog.showModal === 'function') {
                    dialog.showModal();
                } else {
                    dialog.setAttribute('open', 'open');
                }
            }

            document.addEventListener('admin:form-saved', (event) => {
                if (event.detail?.form?.id !== 'delivery-method-form') {
                    return;
                }

                const url = new URL(@json(route('admin.courier-apis.index')), window.location.origin);
                const current = new URL(window.location.href);
                ['q', 'type', 'status', 'view'].forEach((key) => {
                    if (current.searchParams.has(key)) {
                        url.searchParams.set(key, current.searchParams.get(key));
                    }
                });
                window.location.assign(url.toString());
            });

            document.querySelectorAll('[data-copy-btn]').forEach((button) => {
                button.addEventListener('click', async () => {
                    const input = button.parentElement?.querySelector('[data-copy-value]');
                    if (!input) return;
                    try {
                        await navigator.clipboard.writeText(input.value);
                        const previous = button.textContent;
                        button.textContent = 'Copied';
                        setTimeout(() => { button.textContent = previous; }, 1200);
                    } catch (_) {}
                });
            });
        })();
    </script>
@endsection
