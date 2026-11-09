@extends('installer.layout', ['currentStep' => 1])

@section('title', 'System Requirements')

@section('content')
    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Step 1: Server Requirements</h2>
            <p class="text-sm text-slate-500 mt-0.5">Please make sure your hosting server meets the following requirements.</p>
        </div>

        {{-- PHP Extensions List --}}
        <div class="border border-slate-200 rounded-xl overflow-hidden divide-y divide-slate-100">
            @foreach ($requirements as $name => $passed)
                <div class="px-4 py-2.5 flex items-center justify-between text-sm bg-white">
                    <span class="font-medium text-slate-700">{{ $name }}</span>
                    @if ($passed)
                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            Passed
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                            Missing
                        </span>
                    @endif
                </div>
            @endforeach
        </div>

        <div>
            <h3 class="text-base font-bold text-slate-900">Directory Permissions</h3>
            <p class="text-xs text-slate-500">The following directories must be writable by the web server (chmod 775 or 755).</p>
        </div>

        <div class="border border-slate-200 rounded-xl overflow-hidden divide-y divide-slate-100">
            @foreach ($permissions as $dir => $writable)
                <div class="px-4 py-2.5 flex items-center justify-between text-sm bg-white">
                    <span class="font-mono text-xs text-slate-700">{{ $dir }}</span>
                    @if ($writable)
                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
                            Writable
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full">
                            Not Writable
                        </span>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
            <span class="text-xs text-slate-400">Step 1 of 4</span>

            @if ($allRequirementsMet)
                <a href="{{ route('install.step2') }}" class="inline-flex justify-center items-center py-2.5 px-6 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition">
                    Continue to Database &rarr;
                </a>
            @else
                <button type="button" disabled class="cursor-not-allowed inline-flex justify-center items-center py-2.5 px-6 rounded-xl text-sm font-semibold text-slate-400 bg-slate-100">
                    Fix Errors to Continue
                </button>
            @endif
        </div>
    </div>
@endsection
