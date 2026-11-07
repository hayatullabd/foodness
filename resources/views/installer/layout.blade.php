<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Installation Wizard') - {{ config('version.name', 'Foodness eCommerce') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8 text-slate-800">
    <div class="sm:mx-auto sm:w-full sm:max-w-xl">
        <div class="text-center mb-6">
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">
                {{ config('version.name', 'Foodness eCommerce') }}
            </h1>
            <p class="mt-1 text-sm text-slate-500 font-medium">Software Installation & Setup Wizard (v{{ config('version.version', '1.0.0') }})</p>
        </div>

        {{-- Progress Bar --}}
        <div class="flex items-center justify-between mb-8 px-4">
            @php
                $step = $currentStep ?? 1;
            @endphp
            <div class="flex flex-col items-center">
                <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold {{ $step >= 1 ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'bg-slate-200 text-slate-500' }}">1</span>
                <span class="text-[11px] font-semibold mt-1 {{ $step >= 1 ? 'text-indigo-600' : 'text-slate-400' }}">Requirements</span>
            </div>
            <div class="flex-1 h-0.5 mx-2 {{ $step >= 2 ? 'bg-indigo-600' : 'bg-slate-200' }}"></div>
            <div class="flex flex-col items-center">
                <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold {{ $step >= 2 ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'bg-slate-200 text-slate-500' }}">2</span>
                <span class="text-[11px] font-semibold mt-1 {{ $step >= 2 ? 'text-indigo-600' : 'text-slate-400' }}">Database</span>
            </div>
            <div class="flex-1 h-0.5 mx-2 {{ $step >= 3 ? 'bg-indigo-600' : 'bg-slate-200' }}"></div>
            <div class="flex flex-col items-center">
                <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold {{ $step >= 3 ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'bg-slate-200 text-slate-500' }}">3</span>
                <span class="text-[11px] font-semibold mt-1 {{ $step >= 3 ? 'text-indigo-600' : 'text-slate-400' }}">Admin</span>
            </div>
            <div class="flex-1 h-0.5 mx-2 {{ $step >= 4 ? 'bg-indigo-600' : 'bg-slate-200' }}"></div>
            <div class="flex flex-col items-center">
                <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold {{ $step >= 4 ? 'bg-emerald-600 text-white shadow-md shadow-emerald-200' : 'bg-slate-200 text-slate-500' }}">4</span>
                <span class="text-[11px] font-semibold mt-1 {{ $step >= 4 ? 'text-emerald-600' : 'text-slate-400' }}">Complete</span>
            </div>
        </div>

        {{-- Main Card --}}
        <div class="bg-white py-8 px-6 shadow-xl shadow-slate-200/50 rounded-2xl sm:px-10 border border-slate-100">
            @if (session('error'))
                <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm font-medium">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </div>

        <p class="text-center text-xs text-slate-400 mt-6">
            &copy; {{ date('Y') }} {{ config('version.name', 'Foodness eCommerce') }}. All rights reserved.
        </p>
    </div>
    @include('partials.password-toggle')
</body>
</html>
