@extends('layouts.admin')

@php
    $storeRoute = match($page->slug) {
        'privacy-policy' => route('page.privacy'),
        'refund-return-policy' => route('page.refund-return'),
        'terms-and-conditions' => route('page.terms'),
        'shipping-policy' => route('page.shipping'),
        'payment-policy' => route('page.payment'),
        'how-to-order' => route('page.how-to-order'),
        'order-track' => route('order-track.index'),
        'faq' => route('faq.index'),
        'contact' => route('contact.index'),
        default => route('page.show', $page->slug),
    };
@endphp

@section('title', 'Edit Page — ' . $page->title)
@section('heading', 'Edit Page: ' . $page->title)
@section('subheading', 'Update content body, banner subtitle, and SEO metadata.')

@section('actions')
    <a href="{{ $storeRoute }}" target="_blank" class="admin-btn-secondary !px-3 !py-1.5 text-xs flex items-center gap-1.5">
        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
        </svg>
        <span>Preview Live Page</span>
    </a>
    <a href="{{ route('admin.pages.index') }}" class="admin-btn-secondary !px-3 !py-1.5 text-xs">
        &larr; Back to Pages
    </a>
@endsection

@section('content')
    <div class="max-w-4xl space-y-5">
        <form method="POST" action="{{ route('admin.pages.update', $page) }}" class="space-y-5">
            @csrf
            @method('PUT')

            {{-- Main Content Card --}}
            <div class="admin-card">
                <div class="admin-card-header border-b border-slate-100 px-4 py-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-semibold text-slate-900 uppercase tracking-wider">Page Content & Details</h3>
                        <p class="text-[11px] text-slate-500">Edit page heading, banner subtitle and main body copy.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $page->is_active) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                        <span class="ml-2 text-xs font-medium text-slate-700">Published</span>
                    </label>
                </div>

                <div class="p-4 sm:p-5 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="title" class="admin-label">Page Title *</label>
                            <input
                                id="title"
                                name="title"
                                type="text"
                                value="{{ old('title', $page->title) }}"
                                required
                                class="field text-sm"
                            >
                            @error('title') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="admin-label">Slug / URL Path</label>
                            <input
                                type="text"
                                value="/{{ $page->slug }}"
                                disabled
                                class="field text-sm bg-slate-50 text-slate-500 font-mono"
                            >
                        </div>
                    </div>

                    <div>
                        <label for="subtitle" class="admin-label">Banner Subtitle / Notice</label>
                        <textarea
                            id="subtitle"
                            name="subtitle"
                            rows="2"
                            class="field text-sm"
                            placeholder="Brief 1-2 sentence overview shown below the main page title"
                        >{{ old('subtitle', $page->subtitle) }}</textarea>
                        @error('subtitle') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="content" class="admin-label !mb-0">Page Body Content (HTML Allowed)</label>
                            <span class="text-[11px] text-slate-400">Supports &lt;h3&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;strong&gt;, etc.</span>
                        </div>
                        <textarea
                            id="content"
                            name="content"
                            rows="14"
                            class="field font-mono text-xs leading-relaxed"
                            placeholder="Enter formatted content here..."
                        >{{ old('content', $page->content) }}</textarea>
                        @error('content') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- SEO Metadata Card --}}
            <div class="admin-card">
                <div class="admin-card-header border-b border-slate-100 px-4 py-3">
                    <h3 class="text-xs font-semibold text-slate-900 uppercase tracking-wider">Search Engine Optimization (SEO)</h3>
                    <p class="text-[11px] text-slate-500">Customize how this page appears in Google search results and social media shares.</p>
                </div>

                <div class="p-4 sm:p-5 space-y-4">
                    <div>
                        <label for="meta_title" class="admin-label">Meta Title (Browser Tab & Google Headline)</label>
                        <input
                            id="meta_title"
                            name="meta_title"
                            type="text"
                            value="{{ old('meta_title', $page->meta_title) }}"
                            class="field text-sm"
                            placeholder="e.g. Privacy Policy | Store"
                        >
                        @error('meta_title') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="meta_description" class="admin-label">Meta Description</label>
                        <textarea
                            id="meta_description"
                            name="meta_description"
                            rows="2"
                            class="field text-sm"
                            placeholder="Brief summary for search engine snippets (up to 160 characters recommended)"
                        >{{ old('meta_description', $page->meta_description) }}</textarea>
                        @error('meta_description') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Submit Action Bar --}}
            <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-3.5 shadow-xs">
                <span class="text-xs text-slate-500">
                    Last modified: <strong>{{ $page->updated_at?->format('d M, Y — h:i A') }}</strong>
                </span>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.pages.index') }}" class="admin-btn-secondary !px-3 !py-1.5 text-xs">
                        Cancel
                    </a>
                    <button type="submit" class="admin-btn-primary !px-4 !py-1.5 text-xs font-semibold inline-flex items-center gap-1.5 shadow-xs">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Save Page Changes</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
