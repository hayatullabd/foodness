@extends('layouts.admin')

@section('title', 'Settings')
@section('heading', 'Store settings')
@section('subheading', 'Identity, contact details, and storefront protection')

@section('content')
    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="max-w-3xl space-y-4">
        @csrf
        @method('PUT')

        <section class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h2 class="text-sm font-semibold text-ink">Store identity</h2>
                    <p class="text-xs text-muted mt-0.5">Name, contact, branding, and invoice format</p>
                </div>
            </div>
            <div class="admin-card-body space-y-4">
                <div>
                    <label class="admin-label" for="store_name">Store name</label>
                    <input id="store_name" name="store_name" type="text" value="{{ old('store_name', $settings['store_name'] ?? '') }}" required class="field">
                    @error('store_name') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="admin-label" for="store_phone">Phone</label>
                        <input id="store_phone" name="store_phone" type="text" value="{{ old('store_phone', $settings['store_phone'] ?? '') }}" class="field">
                    </div>
                    <div>
                        <label class="admin-label" for="store_email">Email</label>
                        <input id="store_email" name="store_email" type="email" value="{{ old('store_email', $settings['store_email'] ?? '') }}" class="field">
                    </div>
                </div>
                <div>
                    <label class="admin-label" for="store_address">Address</label>
                    <textarea id="store_address" name="store_address" rows="3" class="field">{{ old('store_address', $settings['store_address'] ?? '') }}</textarea>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="admin-label" for="currency">Currency</label>
                        <input id="currency" name="currency" type="text" value="{{ old('currency', $settings['currency'] ?? 'BDT') }}" required class="field">
                    </div>
                    <div>
                        <label class="admin-label" for="currency_symbol">Symbol</label>
                        <input id="currency_symbol" name="currency_symbol" type="text" value="{{ old('currency_symbol', $settings['currency_symbol'] ?? '৳') }}" required class="field">
                    </div>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="admin-label" for="invoice_prefix">Invoice Prefix</label>
                        <input id="invoice_prefix" name="invoice_prefix" type="text" value="{{ old('invoice_prefix', $settings['invoice_prefix'] ?? 'ORD') }}" class="field uppercase" placeholder="ORD" maxlength="20">
                        <p class="mt-1 text-xs text-muted">Format: <strong>{PREFIX}-{{ now()->format('ymd') }}-0001</strong></p>
                        @error('invoice_prefix') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    @include('admin.partials.dropzone', [
                        'name' => 'store_logo',
                        'id' => 'store_logo',
                        'label' => 'Store Logo',
                        'current' => !empty($settings['store_logo']) && file_exists(public_path('storage/'.$settings['store_logo'])) ? asset('storage/'.$settings['store_logo']) : null,
                        'hint' => 'Recommended: 300 × 80 px · PNG, SVG, WEBP · Max 4 MB',
                        'maxHeight' => 'h-28',
                    ])
                    @error('store_logo') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h2 class="text-sm font-semibold text-ink flex items-center gap-2">
                        <svg class="h-4 w-4 text-sage" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Code & content protection
                    </h2>
                    <p class="text-xs text-muted mt-0.5">Anti-inspect and anti-theft controls for the storefront</p>
                </div>
            </div>
            <div class="admin-card-body space-y-3">
                <div class="admin-toggle-row">
                    <div>
                        <div class="text-sm font-medium text-ink">Disable Inspect & View Source</div>
                        <div class="text-xs text-muted">
                            Blocks right-click, F12, Ctrl+U, and DevTools shortcuts with anti-debugging triggers.
                        </div>
                    </div>
                    <label class="relative inline-flex cursor-pointer items-center flex-shrink-0">
                        <input type="checkbox" name="anti_inspect_enabled" value="1" class="peer sr-only" @checked(old('anti_inspect_enabled', ($settings['anti_inspect_enabled'] ?? '0') === '1'))>
                        <span class="h-5 w-9 rounded-full bg-fog transition peer-checked:bg-sage"></span>
                        <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                    </label>
                </div>

                <div class="admin-toggle-row">
                    <div>
                        <div class="text-sm font-medium text-ink">Disable Text Copying & Image Dragging</div>
                        <div class="text-xs text-muted">
                            Prevents selecting/copying storefront text and dragging images (checkout inputs stay usable).
                        </div>
                    </div>
                    <label class="relative inline-flex cursor-pointer items-center flex-shrink-0">
                        <input type="checkbox" name="anti_copy_enabled" value="1" class="peer sr-only" @checked(old('anti_copy_enabled', ($settings['anti_copy_enabled'] ?? '0') === '1'))>
                        <span class="h-5 w-9 rounded-full bg-fog transition peer-checked:bg-sage"></span>
                        <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                    </label>
                </div>

                <p class="text-[11px] text-muted">
                    When logged into the Admin Panel, these restrictions are bypassed automatically.
                </p>
            </div>
        </section>

        <div class="admin-form-footer">
            <button type="submit" class="admin-btn-primary">Save settings</button>
        </div>
    </form>
@endsection
