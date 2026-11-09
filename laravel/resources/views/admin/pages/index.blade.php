@extends('layouts.admin')

@section('title', 'Pages & Policies')
@section('heading', 'Pages & Policies')
@section('subheading', 'Customize and publish legal policies, order guide, FAQ, live tracking and customer service pages.')

@section('content')
    <div class="space-y-6">
        {{-- Pages Table Card --}}
        <div class="admin-card overflow-hidden">
            <div class="admin-card-header border-b border-slate-100 px-4 py-3.5 flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-semibold text-slate-900 uppercase tracking-wider">Website Pages & Policies</h3>
                    <p class="text-[11px] text-slate-500">Edit titles, content, guides and SEO settings for all policy and service pages.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold text-slate-600">
                    {{ $pages->count() }} Pages Configured
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-xs">
                    <thead class="border-b border-slate-100 bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="px-4 py-3">Page Name</th>
                            <th class="px-4 py-3">Storefront URL</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Last Updated</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($pages as $page)
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
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-4 py-3">
                                    <span class="font-bold text-slate-900 block text-xs">{{ $page->title }}</span>
                                    @if ($page->subtitle)
                                        <span class="text-[11px] text-slate-500 line-clamp-1 max-w-md">{{ $page->subtitle }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ $storeRoute }}" target="_blank" class="font-mono text-[11px] text-slate-600 hover:text-emerald-700 flex items-center gap-1">
                                        <span>/{{ $page->slug }}</span>
                                        <svg class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($page->is_active)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-600/20">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Published
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500 ring-1 ring-slate-400/20">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span> Draft
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-500 text-[11px]">
                                    {{ $page->updated_at?->format('d M, Y — h:i A') }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ $storeRoute }}" target="_blank" class="admin-btn-secondary !px-2.5 !py-1 text-[11px]" title="View live page">
                                            Preview
                                        </a>
                                        <a href="{{ route('admin.pages.edit', $page) }}" class="admin-btn-primary !px-2.5 !py-1 text-[11px] font-semibold" title="Edit content & settings">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('admin.pages.toggle', $page) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="admin-btn-secondary !px-2 !py-1 text-[10px] text-slate-500" title="Toggle Publish / Draft">
                                                {{ $page->is_active ? 'Draft' : 'Publish' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Contact Inquiries Card --}}
        <div class="admin-card overflow-hidden">
            <div class="admin-card-header border-b border-slate-100 px-4 py-3.5 flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-semibold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                        <span>Customer Contact Inquiries</span>
                        @if ($unreadCount > 0)
                            <span class="rounded-full bg-amber-500 text-white px-2 py-0.2 text-[10px] font-bold">{{ $unreadCount }} new</span>
                        @endif
                    </h3>
                    <p class="text-[11px] text-slate-500">Messages and inquiries submitted by customers through the Contact Us page.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold text-slate-600">
                    {{ $messages->total() }} Total Messages
                </span>
            </div>

            @if ($messages->count() > 0)
                <div class="divide-y divide-slate-100">
                    @foreach ($messages as $msg)
                        <div class="p-4 hover:bg-slate-50/70 transition flex flex-col sm:flex-row sm:items-start justify-between gap-3 text-xs">
                            <div class="space-y-1 max-w-2xl">
                                <div class="flex items-center gap-2">
                                    <strong class="font-bold text-slate-900">{{ $msg->name }}</strong>
                                    <span class="text-slate-400">·</span>
                                    <a href="tel:{{ $msg->phone }}" class="font-mono font-semibold text-emerald-700 hover:underline">{{ $msg->phone }}</a>
                                    @if ($msg->email)
                                        <span class="text-slate-400">·</span>
                                        <a href="mailto:{{ $msg->email }}" class="text-slate-500 hover:underline">{{ $msg->email }}</a>
                                    @endif
                                </div>
                                @if ($msg->subject)
                                    <p class="font-semibold text-slate-800 text-[11px]">Subject: {{ $msg->subject }}</p>
                                @endif
                                <p class="text-slate-700 text-xs leading-relaxed bg-slate-50 rounded-lg p-2.5 border border-slate-100">
                                    {{ $msg->message }}
                                </p>
                                <span class="text-[10px] text-slate-400 block">Received: {{ $msg->created_at?->format('d M, Y — h:i A') }}</span>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <a href="tel:{{ $msg->phone }}" class="admin-btn-secondary !px-2.5 !py-1 text-[11px] text-emerald-700">
                                    Call Customer
                                </a>
                                <form method="POST" action="{{ route('admin.pages.messages.destroy', $msg) }}" onsubmit="return confirm('Are you sure you want to delete this message?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition" title="Delete inquiry">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($messages->hasPages())
                    <div class="p-3 border-t border-slate-100">
                        {{ $messages->links() }}
                    </div>
                @endif
            @else
                <div class="p-8 text-center text-xs text-slate-400">
                    No customer inquiry messages found yet.
                </div>
            @endif
        </div>
    </div>
@endsection
