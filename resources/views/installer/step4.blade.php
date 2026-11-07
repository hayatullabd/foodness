@extends('installer.layout', ['currentStep' => 4])

@section('title', 'Installation Complete')

@section('content')
    <div class="text-center space-y-6 py-4">
        <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 mx-auto flex items-center justify-center shadow-inner">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        </div>

        <div>
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Congratulations! 🎉</h2>
            <p class="text-sm text-slate-600 mt-1">
                {{ config('version.name', 'Foodness eCommerce') }} has been installed and configured successfully.
            </p>
        </div>

        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-left text-xs text-slate-600 space-y-1.5">
            <p class="font-semibold text-slate-800">Security & Maintenance Checklist:</p>
            <ul class="list-disc list-inside space-y-0.5">
                <li>The installer has now been locked permanently.</li>
                <li>You can access the admin dashboard to configure your store logo, payment gateways, and courier APIs.</li>
                <li>Whenever a new update is released, visit <strong>Settings &gt; System Update</strong> in your admin panel.</li>
            </ul>
        </div>

        <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ route('home') }}" target="_blank" class="inline-flex justify-center items-center py-2.5 px-6 rounded-xl text-sm font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                View Storefront &rarr;
            </a>
            <a href="{{ route('admin.login') }}" class="inline-flex justify-center items-center py-2.5 px-6 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-200 transition">
                Go to Admin Dashboard &rarr;
            </a>
        </div>
    </div>
@endsection
