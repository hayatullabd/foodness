@extends('layouts.admin')

@section('title', 'Social Login Settings')
@section('heading', 'Social Login Settings')
@section('subheading', 'Facebook and Google sign-in for customers, and for existing admin emails on the admin login page')

@section('content')
    @php
        $facebookEnabled = (bool) old('facebook_enabled', $integration->credential('facebook_enabled'));
        $googleEnabled = (bool) old('google_enabled', $integration->credential('google_enabled'));
    @endphp

    <form method="POST" action="{{ route('admin.api-integrations.social-login.update') }}" class="space-y-4">
        @csrf
        @method('PUT')

        {{-- Facebook Login --}}
        <section class="admin-card overflow-hidden">
            <div class="admin-card-header">
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-[#1877F2]/10 text-[#1877F2]" aria-hidden="true">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M22 12.07C22 6.48 17.52 2 11.93 2S1.86 6.48 1.86 12.07c0 5.02 3.66 9.18 8.44 9.93v-7.02H7.9v-2.91h2.4V9.84c0-2.37 1.4-3.69 3.56-3.69 1.03 0 2.11.19 2.11.19v2.32h-1.19c-1.17 0-1.54.73-1.54 1.48v1.78h2.62l-.42 2.91h-2.2V22c4.78-.75 8.44-4.91 8.44-9.93z"/></svg>
                    </span>
                    <div>
                        <h2 class="text-sm font-semibold text-ink">Facebook Login</h2>
                        <p class="text-xs text-muted">Facebook Developers app credentials</p>
                    </div>
                </div>
            </div>

            <div class="admin-card-body space-y-3.5">
                <div class="flex items-center justify-between rounded-md border border-ink/10 bg-mist/60 px-3 py-2.5">
                    <div class="text-sm text-ink">
                        <span class="font-medium">Facebook Login</span>
                        <p class="text-xs text-muted">Allow customers to sign in with Facebook</p>
                    </div>
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input type="checkbox" name="facebook_enabled" value="1" class="peer sr-only" @checked($facebookEnabled)>
                        <span class="h-5 w-9 rounded-full bg-fog transition peer-checked:bg-sage"></span>
                        <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                    </label>
                </div>

                <div class="rounded-md border border-sage/20 bg-sage/5 px-3 py-2.5">
                    <p class="text-xs font-semibold text-ink">Callback URL (Redirect URI):</p>
                    <div class="mt-1.5 flex flex-wrap items-center gap-2">
                        <code id="facebook-callback-url" class="block flex-1 break-all font-mono text-[12px] text-sage">{{ $facebookCallbackUrl }}</code>
                        <button
                            type="button"
                            class="admin-btn-secondary shrink-0"
                            data-copy-target="facebook-callback-url"
                        >Copy</button>
                    </div>
                    <p class="mt-1.5 text-xs text-muted">Paste this URL in the Facebook Developers Console as a Valid OAuth Redirect URI.</p>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="admin-label" for="facebook_client_id">App ID [Client ID]</label>
                        <input
                            id="facebook_client_id"
                            name="facebook_client_id"
                            type="text"
                            value="{{ old('facebook_client_id', $integration->credential('facebook_client_id')) }}"
                            class="field"
                            autocomplete="off"
                            placeholder="Your Facebook App ID"
                        >
                        <p class="mt-1 text-xs text-muted">From Facebook Developers → App → Settings → Basic</p>
                        @error('facebook_client_id') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label" for="facebook_client_secret">App Secret</label>
                        <input
                            id="facebook_client_secret"
                            name="facebook_client_secret"
                            type="password"
                            value=""
                            class="field"
                            autocomplete="off"
                            placeholder="{{ $maskedFacebookSecret ? 'Leave blank to keep current secret' : 'Paste App Secret' }}"
                        >
                        @if ($maskedFacebookSecret)
                            <p class="mt-1 text-xs text-muted">Current secret: <span class="font-mono tracking-wide">{{ $maskedFacebookSecret }}</span></p>
                        @endif
                        <p class="mt-1 text-xs text-muted">Leave blank to keep the saved secret</p>
                        @error('facebook_client_secret') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="admin-label" for="facebook_redirect_url">Redirect URL</label>
                    <input
                        id="facebook_redirect_url"
                        type="text"
                        value="{{ $facebookCallbackUrl }}"
                        class="field bg-mist/40"
                        readonly
                        tabindex="-1"
                    >
                    <p class="mt-1 text-xs text-muted">Auto-generated from APP_URL. Same as the callback URI above.</p>
                </div>
            </div>
        </section>

        {{-- Google Login --}}
        <section class="admin-card overflow-hidden">
            <div class="admin-card-header">
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-mist text-ink" aria-hidden="true">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-sm font-semibold text-ink">Google Login</h2>
                        <p class="text-xs text-muted">Google Cloud OAuth client credentials</p>
                    </div>
                </div>
            </div>

            <div class="admin-card-body space-y-3.5">
                <div class="flex items-center justify-between rounded-md border border-ink/10 bg-mist/60 px-3 py-2.5">
                    <div class="text-sm text-ink">
                        <span class="font-medium">Google Login</span>
                        <p class="text-xs text-muted">Allow customers to sign in with Google</p>
                    </div>
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input type="checkbox" name="google_enabled" value="1" class="peer sr-only" @checked($googleEnabled)>
                        <span class="h-5 w-9 rounded-full bg-fog transition peer-checked:bg-sage"></span>
                        <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                    </label>
                </div>

                <div class="rounded-md border border-amber-700/15 bg-amber-50 px-3 py-2.5">
                    <p class="text-xs font-semibold text-ink">Callback URL (Authorized Redirect URI):</p>
                    <div class="mt-1.5 flex flex-wrap items-center gap-2">
                        <code id="google-callback-url" class="block flex-1 break-all font-mono text-[12px] text-amber-900">{{ $googleCallbackUrl }}</code>
                        <button
                            type="button"
                            class="admin-btn-secondary shrink-0"
                            data-copy-target="google-callback-url"
                        >Copy</button>
                    </div>
                    <p class="mt-1.5 text-xs text-muted">Add this URI under Google Cloud Console → APIs &amp; Services → Credentials → OAuth client.</p>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="admin-label" for="google_client_id">Client ID</label>
                        <input
                            id="google_client_id"
                            name="google_client_id"
                            type="text"
                            value="{{ old('google_client_id', $integration->credential('google_client_id')) }}"
                            class="field"
                            autocomplete="off"
                            placeholder="xxxx.apps.googleusercontent.com"
                        >
                        <p class="mt-1 text-xs text-muted">OAuth 2.0 Client ID from Google Cloud Console</p>
                        @error('google_client_id') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label" for="google_client_secret">Client Secret</label>
                        <input
                            id="google_client_secret"
                            name="google_client_secret"
                            type="password"
                            value=""
                            class="field"
                            autocomplete="off"
                            placeholder="{{ $maskedGoogleSecret ? 'Leave blank to keep current secret' : 'Paste Client Secret' }}"
                        >
                        @if ($maskedGoogleSecret)
                            <p class="mt-1 text-xs text-muted">Current secret: <span class="font-mono tracking-wide">{{ $maskedGoogleSecret }}</span></p>
                        @endif
                        <p class="mt-1 text-xs text-muted">Leave blank to keep the saved secret</p>
                        @error('google_client_secret') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="admin-label" for="google_redirect_url">Redirect URL</label>
                    <input
                        id="google_redirect_url"
                        type="text"
                        value="{{ $googleCallbackUrl }}"
                        class="field bg-mist/40"
                        readonly
                        tabindex="-1"
                    >
                    <p class="mt-1 text-xs text-muted">Auto-generated from APP_URL. Same as the authorized redirect URI above.</p>
                </div>
            </div>
        </section>

        <div class="flex justify-end">
            <button type="submit" class="admin-btn-primary">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                </svg>
                Save Settings
            </button>
        </div>
    </form>

    <script>
        document.querySelectorAll('[data-copy-target]').forEach((button) => {
            button.addEventListener('click', async () => {
                const target = document.getElementById(button.getAttribute('data-copy-target'));
                if (!target) return;
                const text = target.textContent.trim();
                try {
                    await navigator.clipboard.writeText(text);
                    const previous = button.textContent;
                    button.textContent = 'Copied';
                    setTimeout(() => { button.textContent = previous; }, 1500);
                } catch (e) {
                    window.prompt('Copy this URL:', text);
                }
            });
        });
    </script>
@endsection
