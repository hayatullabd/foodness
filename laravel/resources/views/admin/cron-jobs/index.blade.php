@extends('layouts.admin')

@section('title', 'Cron Jobs')
@section('heading', 'Background Sync & Schedule')
@section('subheading', 'Enable or disable courier status updates, set intervals and limits. One server cron line is enough for the Laravel scheduler.')

@section('actions')
    <span class="inline-flex items-center gap-1.5 rounded-full border border-sage/25 bg-mist px-2.5 py-1 text-xs font-semibold text-sage">
        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
        </svg>
        {{ $activeCount }}/{{ $totalJobs }} Active
    </span>
@endsection

@section('content')
    <p class="mb-3 text-xs text-muted">Cron &amp; scheduled tasks</p>

    <div class="grid gap-4 lg:grid-cols-5">
        <div class="space-y-4 lg:col-span-3">
            <article class="admin-card overflow-hidden">
                <div class="admin-card-header">
                    <div class="flex items-center gap-2.5">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-sage text-white">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-sm font-semibold text-ink">Courier Status Sync</h2>
                            <p class="text-xs text-muted">Automatically updates order statuses from Pathao, Steadfast &amp; RedX (using stored Courier API credentials).</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.cron-jobs.toggle') }}">
                        @csrf
                        <button
                            type="submit"
                            role="switch"
                            aria-checked="{{ $job->is_enabled ? 'true' : 'false' }}"
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border transition {{ $job->is_enabled ? 'border-sage bg-sage' : 'border-ink/20 bg-fog' }}"
                            title="{{ $job->is_enabled ? 'Disable' : 'Enable' }}"
                        >
                            <span class="sr-only">Toggle Courier Status Sync</span>
                            <span @class([
                                'pointer-events-none inline-block h-5 w-5 translate-y-px rounded-full bg-white shadow transition',
                                'translate-x-[22px]' => $job->is_enabled,
                                'translate-x-0.5' => ! $job->is_enabled,
                            ])></span>
                        </button>
                    </form>
                </div>

                <div class="admin-card-body space-y-4">
                    <div class="grid grid-cols-3 gap-2">
                        <div class="admin-stat">
                            <div class="admin-stat-value text-sage">{{ $job->last_updated_count ?? 0 }}</div>
                            <div class="admin-stat-label">Updated</div>
                        </div>
                        <div class="admin-stat">
                            <div class="admin-stat-value text-accent">{{ $job->last_failed_count ?? 0 }}</div>
                            <div class="admin-stat-label">Failed</div>
                        </div>
                        <div class="admin-stat">
                            <div class="admin-stat-value">{{ $job->frequency_minutes }}</div>
                            <div class="admin-stat-label">Minute interval</div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-md border border-ink/10 bg-mist/60 px-3 py-2.5">
                        <div>
                            <p class="text-[11px] font-medium uppercase tracking-wide text-muted">Last run</p>
                            <p class="text-sm text-ink">
                                {{ $job->last_run_at ? $job->last_run_at->format('d M Y, h:i A') : 'Not run yet' }}
                            </p>
                        </div>
                        @php
                            $status = $job->last_run_status;
                            $badgeClass = match ($status) {
                                'success' => 'bg-sage/10 text-sage',
                                'failed' => 'bg-accent/10 text-accent',
                                'running' => 'bg-sky-100 text-sky-800',
                                default => 'bg-fog text-muted',
                            };
                            $badgeLabel = match ($status) {
                                'success' => 'Success',
                                'failed' => 'Failed',
                                'running' => 'Running',
                                default => 'Pending',
                            };
                        @endphp
                        <span class="admin-badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                    </div>

                    @if ($job->last_run_result)
                        <p class="rounded-md border border-ink/10 bg-white px-3 py-2 text-xs text-muted">{{ $job->last_run_result }}</p>
                    @endif

                    <form method="POST" action="{{ route('admin.cron-jobs.update') }}" class="space-y-3">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="is_enabled" value="{{ $job->is_enabled ? '1' : '0' }}">

                        <div class="grid gap-3 sm:grid-cols-[1fr_1fr_auto]">
                            <div>
                                <label class="admin-label" for="frequency_minutes">Frequency</label>
                                <select id="frequency_minutes" name="frequency_minutes" class="field">
                                    @foreach ($frequencyOptions as $minutes)
                                        <option value="{{ $minutes }}" @selected((int) old('frequency_minutes', $job->frequency_minutes) === $minutes)>
                                            {{ $minutes >= 60 ? ($minutes / 60).' Hour'.($minutes > 60 ? 's' : '') : $minutes.' Minutes' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('frequency_minutes') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="admin-label" for="order_limit">Order limit</label>
                                <select id="order_limit" name="order_limit" class="field">
                                    @foreach ($limitOptions as $limit)
                                        <option value="{{ $limit }}" @selected((int) old('order_limit', $job->order_limit) === $limit)>
                                            {{ $limit }} items
                                        </option>
                                    @endforeach
                                </select>
                                @error('order_limit') <p class="mt-1 text-sm text-accent">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex items-end">
                                <button type="submit" class="admin-btn-primary w-full sm:w-auto">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    Save Settings
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-ink/10 px-4 py-3">
                    <p class="text-xs text-muted">
                        Automatic:
                        @if ($job->is_enabled)
                            Every {{ $job->frequencyLabel() }}
                        @else
                            Disabled
                        @endif
                    </p>
                    <form method="POST" action="{{ route('admin.cron-jobs.run') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-md bg-sage px-3.5 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-sage/90">
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M8 5.14v13.72a1 1 0 001.5.86l11-6.86a1 1 0 000-1.72l-11-6.86A1 1 0 008 5.14z" />
                            </svg>
                            Run Now
                        </button>
                    </form>
                </div>
            </article>
        </div>

        <aside class="lg:col-span-2">
            <div class="overflow-hidden rounded-md border border-ink/20 bg-ink text-slate-200 shadow-sm">
                <div class="border-b border-white/10 px-4 py-3">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-fog" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.737 5.1a3.375 3.375 0 012.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 01.9 2.7m0 0a3 3 0 01-3 3m0 3h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008zm-3 6h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008z" />
                        </svg>
                        <h2 class="text-sm font-semibold text-white">Server Cron</h2>
                    </div>
                    <p class="mt-1 text-xs text-slate-400">Add a single line on the server (cPanel or VPS). The path below uses this install’s <code class="text-fog">base_path()</code>.</p>
                </div>

                <div class="space-y-4 px-4 py-4">
                    <div class="relative">
                        <pre id="cron-command" class="overflow-x-auto rounded-md bg-black/40 px-3 py-3 pr-16 font-mono text-[11px] leading-relaxed text-mist">{{ $cronCommand }}</pre>
                        <button
                            type="button"
                            class="absolute right-2 top-2 rounded border border-white/15 bg-white/5 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-fog hover:bg-white/10"
                            onclick="navigator.clipboard.writeText(document.getElementById('cron-command').innerText).then(() => { this.textContent = 'Copied'; setTimeout(() => this.textContent = 'Copy', 1500); })"
                        >
                            Copy
                        </button>
                    </div>

                    <p class="text-xs text-slate-400">One cron line runs all Laravel scheduled intervals.</p>

                    <div>
                        <h3 class="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-fog">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
                            </svg>
                            Cron summary
                        </h3>
                        <ul class="list-disc space-y-1.5 pl-4 text-xs leading-relaxed text-slate-400">
                            <li>Orders in <strong class="text-mist">In Courier</strong>, <strong class="text-mist">On The Way</strong>, or <strong class="text-mist">Processing</strong> with courier + tracking are polled via API.</li>
                            <li>Delivered → <strong class="text-mist">Completed</strong>; in transit → <strong class="text-mist">On The Way</strong>; cancelled/returned → <strong class="text-mist">Cancelled</strong>.</li>
                            <li>Steadfast &amp; RedX also update instantly via webhooks; Pathao relies on this poll (enabled by default every 10 minutes).</li>
                            <li>Use <strong class="text-mist">Run Now</strong> for a manual sync anytime. Server cron must call <code class="text-mist">php artisan schedule:run</code>.</li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-fog">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0021 18V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v12a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                            Terminal
                        </h3>
                        <div class="space-y-2">
                            <pre class="overflow-x-auto rounded-md bg-black/40 px-3 py-2 font-mono text-[11px] text-mist">php artisan courier:check-status --limit=50</pre>
                            <pre class="overflow-x-auto rounded-md bg-black/40 px-3 py-2 font-mono text-[11px] text-mist">php artisan courier:check-status --force</pre>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
@endsection
