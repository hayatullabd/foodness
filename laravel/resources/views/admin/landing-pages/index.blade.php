@extends('layouts.admin')

@section('title', 'Landing Pages & Funnels — Foodness')
@section('heading', 'Landing Pages & Funnels')
@section('subheading', 'Create and customize high-converting landing pages with custom HTML, CSS, JS and 1-click checkout.')

@section('actions')
    <a href="{{ route('admin.landing-pages.create') }}" class="btn-primary inline-flex items-center gap-1.5">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        <span>Add New Landing Page</span>
    </a>
@endsection

@section('content')
<div class="space-y-5">
    <form method="POST" action="{{ route('admin.landing-pages.url-prefix') }}" data-reload-on-success class="admin-card flex flex-col gap-3 p-4 sm:flex-row sm:items-end sm:justify-between">
        @csrf
        @method('PUT')
        <div class="min-w-0">
            <label for="landing_url_prefix" class="block text-xs font-bold text-slate-800">Landing URL prefix</label>
            <p class="mt-0.5 text-[11px] text-slate-500">This word replaces <span class="font-mono">page</span> in every landing page link.</p>
        </div>
        <div class="flex min-w-0 flex-1 flex-col gap-1 sm:max-w-xl">
            <div class="flex min-w-0 items-stretch overflow-hidden rounded-lg border border-slate-300 bg-white">
                <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-3 font-mono text-[11px] text-slate-500">{{ url('/') }}/</span>
                <input
                    id="landing_url_prefix"
                    name="landing_url_prefix"
                    type="text"
                    required
                    value="{{ old('landing_url_prefix', \App\Support\LandingUrl::prefix()) }}"
                    pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                    class="min-w-0 flex-1 border-0 px-3 py-2 font-mono text-sm text-slate-900 outline-none"
                    placeholder="page"
                >
                <span class="inline-flex items-center border-l border-slate-200 bg-slate-50 px-3 font-mono text-[11px] text-slate-500">/slug</span>
            </div>
            @error('landing_url_prefix')
                <p class="text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" class="admin-btn-primary !h-9 !px-3 text-xs">Save prefix</button>
    </form>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="rounded-xl border border-ink/10 bg-white p-4 shadow-xs">
            <span class="text-xs font-medium text-slate-500">Total Pages</span>
            <div class="mt-1 flex items-baseline gap-2">
                <span class="text-2xl font-black text-ink">{{ $stats['total'] }}</span>
                <span class="text-[11px] font-semibold text-slate-400">Funnels</span>
            </div>
        </div>

        <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/40 p-4 shadow-xs">
            <span class="text-xs font-semibold text-emerald-800">Active Pages</span>
            <div class="mt-1 flex items-baseline gap-2">
                <span class="text-2xl font-black text-emerald-700">{{ $stats['active'] }}</span>
                <span class="text-[11px] font-semibold text-emerald-600">Live</span>
            </div>
        </div>

        <div class="rounded-xl border border-sky-200/80 bg-sky-50/40 p-4 shadow-xs">
            <span class="text-xs font-semibold text-sky-800">Total Views</span>
            <div class="mt-1 flex items-baseline gap-2">
                <span class="text-2xl font-black text-sky-700">{{ number_format($stats['total_views']) }}</span>
                <span class="text-[11px] font-semibold text-sky-600">Visits</span>
            </div>
        </div>

        <div class="rounded-xl border border-purple-200/80 bg-purple-50/40 p-4 shadow-xs">
            <span class="text-xs font-semibold text-purple-800">Total Orders</span>
            <div class="mt-1 flex items-baseline gap-2">
                <span class="text-2xl font-black text-purple-700">{{ number_format($stats['total_orders']) }}</span>
                <span class="text-[11px] font-semibold text-purple-600">Sales</span>
            </div>
        </div>
    </div>

    {{-- Filter & Search Strip --}}
    <div class="admin-card p-3 sm:p-4">
        <form method="GET" action="{{ route('admin.landing-pages.index') }}" class="flex flex-col sm:flex-row gap-3 items-center justify-between">
            <div class="relative w-full sm:w-80">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search landing pages by title or slug..."
                    class="w-full pl-9 pr-3 py-2 text-xs sm:text-sm rounded-lg border border-slate-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-hidden"
                >
                <svg class="h-4 w-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <select name="status" onchange="this.form.submit()" class="text-xs sm:text-sm rounded-lg border border-slate-300 py-2 px-3 focus:border-emerald-500 outline-hidden bg-white">
                    <option value="">All Statuses</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active Only</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive Only</option>
                </select>

                @if (request()->hasAny(['search', 'status']))
                    <a href="{{ route('admin.landing-pages.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 px-2 py-2">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Landing Pages Table --}}
    <div class="admin-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-xs sm:text-sm">
                <thead class="bg-mist text-slate-600 border-b border-ink/10 uppercase text-[11px] tracking-wider font-semibold">
                    <tr>
                        <th class="px-4 py-3">Page & URL</th>
                        <th class="px-4 py-3">Target Product</th>
                        <th class="px-4 py-3 text-center">Analytics</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink/5 bg-white">
                    @forelse ($landingPages as $page)
                        @php
                            $publicUrl = route('landing.show', $page->slug);
                            $productImg = $page->product?->primaryImagePath();
                            $conversionRate = $page->views_count > 0 ? round(($page->orders_count / $page->views_count) * 100, 1) : 0;
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-4 py-3.5">
                                <div class="font-bold text-slate-900 text-sm">
                                    {{ $page->title }}
                                </div>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-xs text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded font-mono font-medium border border-emerald-200/60">
                                        /{{ \App\Support\LandingUrl::prefix() }}/{{ $page->slug }}
                                    </span>
                                    <button
                                        type="button"
                                        onclick="navigator.clipboard.writeText('{{ $publicUrl }}'); alert('Link copied to clipboard!');"
                                        title="Copy public URL"
                                        class="text-slate-400 hover:text-slate-700 transition"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                    <a href="{{ $publicUrl }}" target="_blank" title="Open live landing page" class="text-slate-400 hover:text-emerald-600 transition">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                </div>
                            </td>

                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <img
                                        src="{{ $productImg ? asset('storage/' . $productImg) : asset('images/placeholder.png') }}"
                                        alt="{{ $page->product?->name }}"
                                        class="w-10 h-10 object-cover rounded-lg border border-slate-200 shrink-0"
                                    >
                                    <div class="min-w-0">
                                        <div class="font-medium text-slate-800 text-xs truncate max-w-[180px]">
                                            {{ $page->product?->name ?? 'Deleted Product' }}
                                        </div>
                                        <div class="text-[11px] font-bold text-emerald-600">
                                            Offer: {{ \App\Support\Money::formatBn($page->effectivePrice()) }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3.5 text-center">
                                <div class="inline-flex items-center gap-3 bg-slate-50 px-2.5 py-1 rounded-lg border border-slate-200/60 text-xs">
                                    <div title="Views">
                                        <span class="text-slate-400 font-medium text-[10px] block">VIEWS</span>
                                        <span class="font-bold text-slate-800">{{ number_format($page->views_count) }}</span>
                                    </div>
                                    <div class="h-5 w-px bg-slate-200"></div>
                                    <div title="Orders">
                                        <span class="text-slate-400 font-medium text-[10px] block">ORDERS</span>
                                        <span class="font-bold text-emerald-700">{{ number_format($page->orders_count) }}</span>
                                    </div>
                                    <div class="h-5 w-px bg-slate-200"></div>
                                    <div title="Conversion Rate">
                                        <span class="text-slate-400 font-medium text-[10px] block">CVR</span>
                                        <span class="font-bold text-purple-700">{{ $conversionRate }}%</span>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3.5 text-center">
                                <form action="{{ route('admin.landing-pages.toggle', $page) }}" method="POST">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold transition {{ $page->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border border-slate-200 hover:bg-slate-200' }}"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full {{ $page->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                        <span>{{ $page->is_active ? 'Active' : 'Inactive' }}</span>
                                    </button>
                                </form>
                            </td>

                            <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <a
                                        href="{{ route('admin.landing-pages.edit', $page) }}"
                                        class="p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:text-emerald-700 hover:border-emerald-300 hover:bg-emerald-50 transition"
                                        title="Edit Design & Code"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>

                                    <form action="{{ route('admin.landing-pages.duplicate', $page) }}" method="POST" class="inline">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:text-blue-700 hover:border-blue-300 hover:bg-blue-50 transition"
                                            title="Duplicate / Clone Page"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                                            </svg>
                                        </button>
                                    </form>

                                    <form action="{{ route('admin.landing-pages.destroy', $page) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this landing page?');">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:text-red-700 hover:border-red-300 hover:bg-red-50 transition"
                                            title="Delete Page"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-slate-500">
                                <div class="max-w-sm mx-auto">
                                    <div class="w-12 h-12 mx-auto rounded-full bg-emerald-100/70 text-emerald-600 flex items-center justify-center text-xl mb-3">
                                        🚀
                                    </div>
                                    <h3 class="font-bold text-slate-800 text-base mb-1">No Landing Pages Yet</h3>
                                    <p class="text-xs text-slate-500 mb-4">Create your first custom landing page with HTML/CSS and 1-click checkout to boost sales.</p>
                                    <a href="{{ route('admin.landing-pages.create') }}" class="btn-primary inline-flex items-center gap-1.5">
                                        <span>Create First Landing Page</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($landingPages->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $landingPages->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
