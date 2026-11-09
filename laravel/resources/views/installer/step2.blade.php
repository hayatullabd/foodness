@extends('installer.layout', ['currentStep' => 2])

@section('title', 'Database Setup')

@section('content')
    <form method="POST" action="{{ route('install.post-step2') }}" class="space-y-5">
        @csrf

        <div>
            <h2 class="text-xl font-bold text-slate-900">Step 2: Database Configuration</h2>
            <p class="text-sm text-slate-500 mt-0.5">Enter your MySQL database credentials (create an empty database in cPanel first).</p>
        </div>

        <div class="grid grid-cols-3 gap-3">
            <div class="col-span-2">
                <label for="db_host" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Database Host</label>
                <input type="text" id="db_host" name="db_host" value="{{ old('db_host', $defaultHost) }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                @error('db_host') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="db_port" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Port</label>
                <input type="number" id="db_port" name="db_port" value="{{ old('db_port', $defaultPort) }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                @error('db_port') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="db_name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Database Name</label>
            <input type="text" id="db_name" name="db_name" value="{{ old('db_name', $defaultDatabase) }}" placeholder="e.g. cpaneluser_ecommerce" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
            @error('db_name') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="db_user" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Database Username</label>
            <input type="text" id="db_user" name="db_user" value="{{ old('db_user', $defaultUsername) }}" placeholder="e.g. cpaneluser_dbuser" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
            @error('db_user') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="db_password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Database Password</label>
            <input type="password" id="db_password" name="db_password" placeholder="Leave empty if no password (local)" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
            @error('db_password') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
            <a href="{{ route('install.step1') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-800 transition">
                &larr; Back
            </a>
            <button type="submit" class="inline-flex justify-center items-center py-2.5 px-6 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition">
                Test & Save Connection &rarr;
            </button>
        </div>
    </form>
@endsection
