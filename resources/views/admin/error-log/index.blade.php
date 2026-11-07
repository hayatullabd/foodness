@extends('layouts.admin')

@section('title', 'Laravel Error Log')
@section('heading', 'Laravel Error Log')
@section('subheading', 'Inspect the latest application log output')

@section('actions')
    <form method="POST" action="{{ route('admin.error-log.write-test') }}" class="inline">
        @csrf
        <button type="submit" class="admin-btn-secondary">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
            </svg>
            Write test log
        </button>
    </form>
    <form method="POST" action="{{ route('admin.error-log.create') }}" class="inline">
        @csrf
        <button type="submit" class="admin-btn-secondary">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Create log file
        </button>
    </form>
    @if ($log['exists'])
        <form method="POST" action="{{ route('admin.error-log.clear') }}" class="inline" onsubmit="return confirm('Clear the entire laravel.log file?')">
            @csrf
            <button type="submit" class="admin-btn-secondary">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
                Clear log
            </button>
        </form>
    @endif
    <a href="{{ route('admin.error-log.index') }}" class="admin-btn-primary">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
        </svg>
        Refresh
    </a>
@endsection

@section('content')
    @php
        $sizeLabel = $log['size_bytes'] !== null
            ? number_format(((int) $log['size_bytes']) / 1024, 1).' KB'
            : '—';
    @endphp

    <div class="mb-4 overflow-hidden rounded-lg bg-gradient-to-r from-ink to-sage px-4 py-5 text-white shadow-sm sm:px-5">
        <div class="flex items-center gap-3">
            <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white text-sage shadow-sm">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9zm3.75 11.625a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
            </span>
            <div>
                <h2 class="font-display text-lg font-semibold tracking-tight sm:text-xl">Laravel Error Log</h2>
                <p class="mt-0.5 text-sm text-white/80">Live view of the latest {{ number_format($log['lines_limit']) }} lines from laravel.log.</p>
            </div>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-body space-y-3">
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <span class="font-medium text-ink">File:</span>
                <code class="rounded bg-mist px-2 py-1 font-mono text-xs text-ink">{{ $log['path'] }}</code>
                <span class="text-muted">(last {{ number_format($log['lines_limit']) }} lines)</span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span @class([
                    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1',
                    'bg-emerald-50 text-emerald-700 ring-emerald-200' => $log['writable'],
                    'bg-amber-50 text-amber-800 ring-amber-200' => ! $log['writable'],
                ])>
                    <span @class([
                        'h-1.5 w-1.5 rounded-full',
                        'bg-emerald-500' => $log['writable'],
                        'bg-amber-500' => ! $log['writable'],
                    ])></span>
                    {{ $log['writable'] ? 'Writable' : 'Not writable' }}
                </span>
                <span @class([
                    'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1',
                    'bg-sky-50 text-sky-800 ring-sky-200' => $log['exists'],
                    'bg-slate-100 text-slate-600 ring-slate-200' => ! $log['exists'],
                ])>
                    {{ $log['exists'] ? 'File exists' : 'File missing' }}
                </span>
                <span class="inline-flex items-center rounded-full bg-mist px-2.5 py-1 text-xs font-semibold text-ink ring-1 ring-ink/10">
                    Channel: {{ $log['channel'] }}
                </span>
                <span class="inline-flex items-center rounded-full bg-mist px-2.5 py-1 text-xs font-semibold text-ink ring-1 ring-ink/10">
                    Level: {{ $log['level'] }}
                </span>
                <span class="inline-flex items-center rounded-full bg-mist px-2.5 py-1 text-xs font-semibold text-ink ring-1 ring-ink/10">
                    Size: {{ $sizeLabel }}
                </span>
            </div>

            <div class="overflow-hidden rounded-md border border-ink/10 bg-[#0f172a]">
                <pre class="max-h-[70vh] overflow-auto p-4 font-mono text-[12px] leading-5 text-slate-100 whitespace-pre-wrap break-words">@if ($log['exists'] && filled($log['content']))
{{ $log['content'] }}
@elseif ($log['exists'])
Log file is empty. Click “Write test log” to add a sample entry.
@else
Log file not found. Click “Create log file” to create storage/logs/laravel.log.
@endif</pre>
            </div>
        </div>
    </div>
@endsection
