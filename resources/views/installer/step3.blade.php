@extends('installer.layout', ['currentStep' => 3])

@section('title', 'Admin Account & Migration')

@section('content')
    <form method="POST" action="{{ route('install.post-step3') }}" class="space-y-5">
        @csrf

        <div>
            <h2 class="text-xl font-bold text-slate-900">Step 3: Super Admin Account</h2>
            <p class="text-sm text-slate-500 mt-0.5">This step will migrate the database tables and create your primary administrator login.</p>
        </div>

        <div>
            <label for="admin_name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Admin Full Name</label>
            <input type="text" id="admin_name" name="admin_name" value="{{ old('admin_name', 'Store Administrator') }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
            @error('admin_name') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="admin_email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Admin Email Address</label>
            <input type="email" id="admin_email" name="admin_email" value="{{ old('admin_email', 'admin@foodness.com') }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
            @error('admin_email') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label for="admin_password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Password</label>
                <input type="password" id="admin_password" name="admin_password" placeholder="At least 8 characters" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                @error('admin_password') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="admin_password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Confirm Password</label>
                <input type="password" id="admin_password_confirmation" name="admin_password_confirmation" placeholder="Re-type password" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
            </div>
        </div>

        <div class="p-3.5 rounded-xl bg-indigo-50 border border-indigo-100 text-xs text-indigo-800 leading-relaxed">
            <strong>Note:</strong> Clicking "Run Installation" may take 15–30 seconds to create all tables, indexes, roles, and default statuses. Please do not refresh.
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
            <a href="{{ route('install.step2') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-800 transition">
                &larr; Back
            </a>
            <button type="submit" class="inline-flex justify-center items-center py-2.5 px-6 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition">
                Run Installation &rarr;
            </button>
        </div>
    </form>
@endsection
