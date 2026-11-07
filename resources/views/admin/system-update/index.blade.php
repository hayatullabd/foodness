@extends('layouts.admin')

@section('title', 'System Update')
@section('heading', 'System Update')

@section('content')
    <div class="max-w-4xl space-y-6">
        @if (session('success'))
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 font-medium">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 font-medium">
                {{ session('error') }}
            </div>
        @endif

        {{-- Current System Version Status Card --}}
        <div class="admin-card admin-card-body space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border">
                <div>
                    <h3 class="text-lg font-bold text-foreground">Current Software Version</h3>
                    <p class="text-sm text-muted">Track installed version and system integrity.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 text-sm font-semibold rounded-full bg-primary/10 text-primary border border-primary/20">
                        v{{ $currentVersion }}
                    </span>
                    <span class="text-xs text-muted">Build: {{ $buildDate }}</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                <div class="p-4 rounded-xl bg-muted/20 border border-border">
                    <span class="text-xs font-semibold uppercase text-muted block mb-1">PHP Version</span>
                    <span class="text-base font-bold text-foreground">{{ $phpVersion }}</span>
                    <span class="block text-xs text-emerald-500 mt-1">✓ Compatible</span>
                </div>
                <div class="p-4 rounded-xl bg-muted/20 border border-border">
                    <span class="text-xs font-semibold uppercase text-muted block mb-1">Zip Extension</span>
                    <span class="text-base font-bold text-foreground">{{ $isZipLoaded ? 'Enabled' : 'Missing' }}</span>
                    <span class="block text-xs {{ $isZipLoaded ? 'text-emerald-500' : 'text-rose-500' }} mt-1">
                        {{ $isZipLoaded ? '✓ Ready for Updates' : '⚠ Required for Zip extraction' }}
                    </span>
                </div>
                <div class="p-4 rounded-xl bg-muted/20 border border-border">
                    <span class="text-xs font-semibold uppercase text-muted block mb-1">Release Source</span>
                    <span class="text-base font-bold text-foreground">
                        {{ filled($githubRepo) ? 'GitHub Releases' : 'Update Server' }}
                    </span>
                    <span class="block text-xs {{ filled($githubRepo) ? 'text-primary' : 'text-muted' }} mt-1 truncate">
                        {{ filled($githubRepo) ? $githubRepo : 'Manual / Self-hosted' }}
                    </span>
                </div>
            </div>

            @if ($autoUpdateEnabled)
                <div class="px-3.5 py-2 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-xs text-emerald-600 dark:text-emerald-400 flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Automatic background update is <strong>Enabled</strong> (Checks daily at 03:00 AM via cron)
                    </span>
                    <span class="font-mono text-[11px] opacity-75">cron: system:auto-update</span>
                </div>
            @endif
        </div>

        {{-- Online Update Check --}}
        @if ($updateInfo['has_update'])
            <div class="admin-card admin-card-body border-2 border-primary/30 space-y-4 bg-primary/5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="px-2.5 py-0.5 text-xs font-bold uppercase rounded bg-primary text-white">New Version Available</span>
                            @if (! empty($updateInfo['source']))
                                <span class="text-xs font-medium text-muted">via {{ $updateInfo['source'] }}</span>
                            @endif
                        </div>
                        <h4 class="text-xl font-bold text-foreground">v{{ $updateInfo['latest_version'] }} is ready to install</h4>
                        <p class="text-sm text-muted">Released on: {{ $updateInfo['release_date'] ?? 'Recently' }}</p>
                    </div>
                    @if (! empty($updateInfo['download_url']))
                        <form method="POST" action="{{ route('admin.system-update.apply-online') }}" onsubmit="return confirm('Do you want to apply this update now? A backup of existing files is recommended.');">
                            @csrf
                            <input type="hidden" name="download_url" value="{{ $updateInfo['download_url'] }}">
                            <button type="submit" class="btn-primary">
                                One-Click Update Now &rarr;
                            </button>
                        </form>
                    @endif
                </div>

                @if (! empty($updateInfo['changelog']))
                    <div class="mt-4 p-4 rounded-xl bg-background border border-border text-sm text-foreground">
                        <h5 class="font-bold mb-2">What's New:</h5>
                        <div class="prose prose-sm dark:prose-invert max-w-none">
                            {!! nl2br(e($updateInfo['changelog'])) !!}
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- Theme CSS publish (no npm needed) --}}
        <div class="admin-card admin-card-body space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-foreground">Publish Theme CSS</h3>
                    <p class="text-sm text-muted">
                        Rebuild storefront theme styles without Node/npm. Copies
                        <code class="text-xs font-mono bg-muted/30 px-1 py-0.5 rounded">resources/css/foodness-theme.css</code>
                        → <code class="text-xs font-mono bg-muted/30 px-1 py-0.5 rounded">public/css/</code>
                        (and shared-host document root when applicable).
                    </p>
                </div>
                <form method="POST" action="{{ route('admin.system-update.publish-theme-css') }}">
                    @csrf
                    <button type="submit" class="btn-primary whitespace-nowrap">
                        Publish Theme CSS
                    </button>
                </form>
            </div>
        </div>

        {{-- Manual Zip Upload Update --}}
        <div class="admin-card admin-card-body space-y-4">
            <div>
                <h3 class="text-lg font-bold text-foreground">Manual Update via ZIP Package</h3>
                <p class="text-sm text-muted">
                    If you downloaded an update package (<code class="text-xs font-mono bg-muted/30 px-1 py-0.5 rounded">update_vX.X.X.zip</code>), upload it here to upgrade your store safely.
                </p>
            </div>

            <form method="POST" action="{{ route('admin.system-update.apply-zip') }}" enctype="multipart/form-data" class="space-y-4" onsubmit="return confirm('Make sure you have tested or backed up before applying. Proceed?');">
                @csrf

                <div class="space-y-1.5">
                    <label class="admin-label !mb-0" for="update_zip">Select Update Package (.zip)</label>
                    <input
                        type="file"
                        name="update_zip"
                        id="update_zip"
                        accept=".zip,application/zip,application/x-zip-compressed"
                        required
                        class="block w-full text-sm text-foreground file:mr-4 file:rounded-lg file:border-0 file:bg-primary file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-white hover:file:opacity-90 cursor-pointer rounded-xl border border-border bg-background px-3 py-3"
                    >
                    <p class="text-xs text-muted">Choose the <code class="font-mono bg-muted/30 px-1 py-0.5 rounded">update_vX.X.X.zip</code> file from your computer, then click Upload &amp; Apply Update.</p>
                    @error('update_zip')
                        <p class="mt-1 text-sm text-accent">{{ $message }}</p>
                    @enderror
                </div>

                <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/20 text-xs text-amber-700 dark:text-amber-400 space-y-1">
                    <p class="font-semibold">Important Notes on Data Safety:</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        <li>Your <code class="font-mono">.env</code> database configuration will <strong>NEVER</strong> be overwritten.</li>
                        <li>Uploaded product images, banners, and logos inside <code class="font-mono">storage/</code> remain <strong>100% untouched</strong>.</li>
                        <li>Existing database orders, customers, and products are preserved; only new schema additions will be migrated.</li>
                    </ul>
                </div>

                <button type="submit" class="btn-primary">
                    Upload & Apply Update
                </button>
            </form>
        </div>
    </div>
@endsection
