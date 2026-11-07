@extends('layouts.admin')

@section('title', 'Payment Gateway Settings')
@section('heading', 'Payment Gateway')
@section('subheading', 'Configure Cash on Delivery (COD), automated gateways, and manual payment methods')

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        {{-- 1. Cash on Delivery (COD) --}}
        <form
            method="POST"
            action="{{ route('admin.api-integrations.payment-gateway.update', 'cod') }}"
            class="flex flex-col overflow-hidden border border-ink/10 bg-white shadow-sm rounded-lg"
        >
            @csrf
            @method('PUT')

            <div class="flex border-b border-ink/10">
                <div class="w-1.5 shrink-0 bg-emerald-600" aria-hidden="true"></div>
                <div class="flex flex-1 items-start justify-between gap-3 px-4 py-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-semibold text-ink">Cash on Delivery</h2>
                            <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-bold {{ $cod->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                                {{ $cod->is_active ? 'ENABLED' : 'DISABLED' }}
                            </span>
                        </div>
                        <p class="text-xs text-muted">Pay in cash upon doorstep delivery</p>
                    </div>
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-emerald-50 text-[10px] font-bold tracking-tight text-emerald-700">COD</span>
                </div>
            </div>

            <div class="flex-1 space-y-3.5 px-4 py-4 flex flex-col justify-between">
                <div class="space-y-3.5">
                    <div>
                        <label class="admin-label" for="cod_title">Display Title</label>
                        <input
                            id="cod_title"
                            name="title"
                            type="text"
                            value="{{ old('title', $cod->credential('title', 'Cash on Delivery (COD)')) }}"
                            class="field"
                            placeholder="Cash on Delivery (COD)"
                        >
                        @error('title') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="admin-label" for="cod_instructions">Short Note / Subtitle</label>
                        <textarea
                            id="cod_instructions"
                            name="instructions"
                            rows="3"
                            class="field"
                            placeholder="Pay in cash when you receive the parcel at your doorstep."
                        >{{ old('instructions', $cod->credential('instructions', 'Pay in cash when you receive the parcel at your doorstep.')) }}</textarea>
                        @error('instructions') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center justify-between rounded-md border border-ink/10 bg-mist/60 px-3 py-2.5">
                        <div class="flex items-center gap-2 text-sm text-ink">
                            <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <span class="font-medium text-xs">Enable COD</span>
                                <p class="text-[11px] text-muted">Show COD option at checkout</p>
                            </div>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" name="is_active" value="1" class="peer sr-only" @checked(old('is_active', $cod->is_active))>
                            <span class="h-5 w-9 rounded-full bg-fog transition peer-checked:bg-emerald-600"></span>
                            <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                        </label>
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="inline-flex w-full items-center justify-center gap-1.5 rounded-md bg-ink px-3 py-2.5 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-ink/90">
                        Update COD Settings
                    </button>
                </div>
            </div>
        </form>

        {{-- 2. SSLCommerz (Online Payment) --}}
        <form
            method="POST"
            action="{{ route('admin.api-integrations.payment-gateway.update', 'sslcommerz') }}"
            class="flex flex-col overflow-hidden border border-ink/10 bg-white shadow-sm rounded-lg"
        >
            @csrf
            @method('PUT')

            <div class="flex border-b border-ink/10">
                <div class="w-1.5 shrink-0 bg-blue-600" aria-hidden="true"></div>
                <div class="flex flex-1 items-start justify-between gap-3 px-4 py-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-semibold text-ink">SSLCommerz</h2>
                            <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-bold {{ $sslcommerz->is_active ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-slate-100 text-slate-600' }}">
                                {{ $sslcommerz->is_active ? 'ENABLED' : 'DISABLED' }}
                            </span>
                        </div>
                        <p class="text-xs text-muted">Cards, Mobile Banking &amp; Internet Banking</p>
                    </div>
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-blue-50 text-[10px] font-bold tracking-tight text-blue-700">SC</span>
                </div>
            </div>

            <div class="flex-1 space-y-3.5 px-4 py-4 flex flex-col justify-between">
                <div class="space-y-3.5">
                    <div>
                        <label class="admin-label" for="sslcommerz_store_id">STORE ID</label>
                        <input
                            id="sslcommerz_store_id"
                            name="store_id"
                            type="text"
                            value="{{ old('store_id', $sslcommerz->credential('store_id')) }}"
                            class="field"
                            autocomplete="off"
                            placeholder="Your SSLCommerz Store ID"
                        >
                        @error('store_id') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="admin-label" for="sslcommerz_store_password">STORE PASSWORD</label>
                        <input
                            id="sslcommerz_store_password"
                            name="store_password"
                            type="password"
                            value=""
                            class="field"
                            autocomplete="off"
                            placeholder="{{ $sslcommerzMaskedStorePassword ? 'Leave blank to keep current password' : 'Enter store password' }}"
                        >
                        @if ($sslcommerzMaskedStorePassword)
                            <p class="mt-1 text-xs text-muted">Current password: <span class="font-mono tracking-wide">{{ $sslcommerzMaskedStorePassword }}</span></p>
                        @endif
                        @error('store_password') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center justify-between rounded-md border border-ink/10 bg-mist/60 px-3 py-2.5">
                        <div class="flex items-center gap-2 text-sm text-ink">
                            <svg class="h-4 w-4 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <span class="font-medium text-xs">Sandbox Mode</span>
                                <p class="text-[11px] text-muted">Use sandbox.sslcommerz.com</p>
                            </div>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" name="is_sandbox" value="1" class="peer sr-only" @checked(old('is_sandbox', $sslcommerz->credential('is_sandbox', true)))>
                            <span class="h-5 w-9 rounded-full bg-fog transition peer-checked:bg-blue-600"></span>
                            <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                        </label>
                    </div>

                    <div class="flex items-center justify-between rounded-md border border-ink/10 bg-mist/60 px-3 py-2.5">
                        <div class="flex items-center gap-2 text-sm text-ink">
                            <svg class="h-4 w-4 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1012.728 0M12 3v9" />
                            </svg>
                            <div>
                                <span class="font-medium text-xs">Gateway Status</span>
                                <p class="text-[11px] text-muted">Enable online payment</p>
                            </div>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" name="is_active" value="1" class="peer sr-only" @checked(old('is_active', $sslcommerz->is_active))>
                            <span class="h-5 w-9 rounded-full bg-fog transition peer-checked:bg-blue-600"></span>
                            <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                        </label>
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="inline-flex w-full items-center justify-center gap-1.5 rounded-md bg-ink px-3 py-2.5 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-ink/90">
                        Update SSLCommerz Config
                    </button>
                </div>
            </div>
        </form>

        {{-- 3. Manual Payment / Bank Transfer --}}
        <form
            method="POST"
            action="{{ route('admin.api-integrations.payment-gateway.update', 'manual_payment') }}"
            class="flex flex-col overflow-hidden border border-ink/10 bg-white shadow-sm rounded-lg"
        >
            @csrf
            @method('PUT')

            <div class="flex border-b border-ink/10">
                <div class="w-1.5 shrink-0 bg-purple-600" aria-hidden="true"></div>
                <div class="flex flex-1 items-start justify-between gap-3 px-4 py-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-semibold text-ink">Manual Payment</h2>
                            <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-bold {{ $manualPayment->is_active ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-slate-100 text-slate-600' }}">
                                {{ $manualPayment->is_active ? 'ENABLED' : 'DISABLED' }}
                            </span>
                        </div>
                        <p class="text-xs text-muted">bKash, Nagad personal or Bank details</p>
                    </div>
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-purple-50 text-[10px] font-bold tracking-tight text-purple-700">MANUAL</span>
                </div>
            </div>

            <div class="flex-1 space-y-3.5 px-4 py-4 flex flex-col justify-between">
                <div class="space-y-3.5">
                    <div>
                        <label class="admin-label" for="manual_title">Payment Title</label>
                        <input
                            id="manual_title"
                            name="title"
                            type="text"
                            value="{{ old('title', $manualPayment->credential('title')) }}"
                            class="field"
                            placeholder="bKash / Nagad / Bank Transfer"
                        >
                        @error('title') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="admin-label" for="manual_account_name">Account Name</label>
                            <input
                                id="manual_account_name"
                                name="account_name"
                                type="text"
                                value="{{ old('account_name', $manualPayment->credential('account_name')) }}"
                                class="field"
                                placeholder="Store / Owner name"
                            >
                            @error('account_name') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="admin-label" for="manual_account_number">Account / Number</label>
                            <input
                                id="manual_account_number"
                                name="account_number"
                                type="text"
                                value="{{ old('account_number', $manualPayment->credential('account_number')) }}"
                                class="field"
                                placeholder="017XXXXXXXX"
                            >
                            @error('account_number') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="admin-label" for="manual_bank_name">Bank / Wallet Provider</label>
                        <input
                            id="manual_bank_name"
                            name="bank_name"
                            type="text"
                            value="{{ old('bank_name', $manualPayment->credential('bank_name')) }}"
                            class="field"
                            placeholder="bKash Merchant / City Bank"
                        >
                        @error('bank_name') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="admin-label" for="manual_instructions">Instructions / TrxID Note</label>
                        <textarea
                            id="manual_instructions"
                            name="instructions"
                            rows="2"
                            class="field"
                            placeholder="Send money and write TrxID in notes"
                        >{{ old('instructions', $manualPayment->credential('instructions')) }}</textarea>
                        @error('instructions') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center justify-between rounded-md border border-ink/10 bg-mist/60 px-3 py-2.5">
                        <div class="flex items-center gap-2 text-sm text-ink">
                            <svg class="h-4 w-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1012.728 0M12 3v9" />
                            </svg>
                            <div>
                                <span class="font-medium text-xs">Manual Payment Status</span>
                                <p class="text-[11px] text-muted">Enable manual payment</p>
                            </div>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" name="is_active" value="1" class="peer sr-only" @checked(old('is_active', $manualPayment->is_active))>
                            <span class="h-5 w-9 rounded-full bg-fog transition peer-checked:bg-purple-600"></span>
                            <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                        </label>
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="inline-flex w-full items-center justify-center gap-1.5 rounded-md bg-ink px-3 py-2.5 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-ink/90">
                        Update Manual Payment
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
