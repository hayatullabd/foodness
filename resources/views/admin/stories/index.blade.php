@extends('layouts.admin')

@section('title', 'Stories Management — Foodness')
@section('heading', 'Stories Management')
@section('subheading', 'Showcase and manage Instagram/Facebook style stories on the storefront hero section.')

@section('actions')
    <a href="{{ route('admin.stories.create') }}" class="btn-primary inline-flex items-center gap-1.5">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        <span>Add New Story</span>
    </a>
@endsection

@section('content')
<div class="space-y-5">
    {{-- Info Card / Counter Strip --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="rounded-xl border border-ink/10 bg-white p-4 shadow-xs">
            <span class="text-xs font-medium text-slate-500">Total Stories</span>
            <div class="mt-1 flex items-baseline gap-2">
                <span class="text-2xl font-black text-ink">{{ $stories->total() }}</span>
                <span class="text-[11px] font-semibold text-slate-400">Total</span>
            </div>
        </div>

        <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/40 p-4 shadow-xs">
            <span class="text-xs font-semibold text-emerald-800">Active Stories</span>
            <div class="mt-1 flex items-baseline gap-2">
                <span class="text-2xl font-black text-emerald-700">{{ $stories->where('is_active', true)->count() }}</span>
                <span class="text-[11px] font-semibold text-emerald-600">Live</span>
            </div>
        </div>

        <div class="rounded-xl border border-sky-200/80 bg-sky-50/40 p-4 shadow-xs">
            <span class="text-xs font-semibold text-sky-800">Video Stories</span>
            <div class="mt-1 flex items-baseline gap-2">
                <span class="text-2xl font-black text-sky-700">{{ $stories->where('media_type', 'video')->count() }}</span>
                <span class="text-[11px] font-semibold text-sky-600">Videos</span>
            </div>
        </div>

        <div class="rounded-xl border border-amber-200/80 bg-amber-50/40 p-4 shadow-xs">
            <span class="text-xs font-semibold text-amber-800">Image Stories</span>
            <div class="mt-1 flex items-baseline gap-2">
                <span class="text-2xl font-black text-amber-700">{{ $stories->where('media_type', 'image')->count() }}</span>
                <span class="text-[11px] font-semibold text-amber-600">Images</span>
            </div>
        </div>
    </div>

    {{-- Stories Table --}}
    <div class="admin-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-xs sm:text-sm">
                <thead class="bg-mist text-slate-600 border-b border-ink/10 uppercase text-[11px] tracking-wider font-semibold">
                    <tr>
                        <th class="px-4 py-3">Preview</th>
                        <th class="px-4 py-3">Title & Link</th>
                        <th class="px-4 py-3">Type & Duration</th>
                        <th class="px-4 py-3">Order</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink/5 bg-white">
                    @forelse ($stories as $story)
                        <tr class="hover:bg-slate-50/60 transition">
                            {{-- Circle Preview --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="relative p-0.5 rounded-full bg-gradient-to-tr from-amber-500 via-rose-500 to-[#0B733E] shadow-xs">
                                        <div class="h-12 w-12 rounded-full overflow-hidden bg-slate-900 border-2 border-white">
                                            @if ($story->thumbnail_path)
                                                <img src="{{ $story->thumbnailUrl() }}" alt="{{ $story->title }}" class="h-full w-full object-cover">
                                            @elseif ($story->isImage())
                                                <img src="{{ $story->mediaUrl() }}" alt="{{ $story->title }}" class="h-full w-full object-cover">
                                            @else
                                                <div class="h-full w-full bg-slate-800 flex items-center justify-center text-white">
                                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                        @if ($story->isVideo())
                                            <span class="absolute -bottom-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-rose-600 text-white shadow-xs">
                                                <svg class="h-2.5 w-2.5 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Title & Link --}}
                            <td class="px-4 py-3">
                                <span class="font-bold text-ink block line-clamp-1">{{ $story->title }}</span>
                                @if ($story->link_url)
                                    <a href="{{ $story->link_url }}" target="_blank" rel="noopener noreferrer" class="mt-0.5 inline-flex items-center gap-1 text-[11px] text-[#0B733E] hover:underline">
                                        <span>{{ $story->link_text ?: 'Link' }}: {{ Str::limit($story->link_url, 30) }}</span>
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                @else
                                    <span class="text-[11px] text-slate-400">No link</span>
                                @endif
                            </td>

                            {{-- Type & Duration --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    @if ($story->isVideo())
                                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-bold text-rose-700">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9A2.25 2.25 0 0013.5 5.25h-9A2.25 2.25 0 002.25 7.5v9A2.25 2.25 0 004.5 18.75z" />
                                            </svg>
                                            Video
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-800">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                            </svg>
                                            Image
                                        </span>
                                    @endif
                                    <span class="text-xs text-slate-500 font-medium">{{ $story->duration }}s</span>
                                </div>
                            </td>

                            {{-- Sort Order --}}
                            <td class="px-4 py-3">
                                <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-700">#{{ $story->sort_order }}</span>
                            </td>

                            {{-- Status Toggle --}}
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('admin.stories.toggle', $story) }}">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold transition {{ $story->is_active ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                                        title="Click to toggle status"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full {{ $story->is_active ? 'bg-emerald-600 animate-pulse' : 'bg-slate-400' }}"></span>
                                        <span>{{ $story->is_active ? 'Active' : 'Inactive' }}</span>
                                    </button>
                                </form>
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a
                                        href="{{ route('admin.stories.edit', $story) }}"
                                        class="rounded-lg border border-ink/15 bg-white px-2.5 py-1 text-xs font-semibold text-ink shadow-xs hover:bg-mist/40 transition"
                                    >
                                        Edit
                                    </a>
                                    <form
                                        method="POST"
                                        action="{{ route('admin.stories.destroy', $story) }}"
                                        class="inline"
                                        onsubmit="return confirm('Are you sure you want to delete this story? This action cannot be undone.')"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 shadow-xs hover:bg-rose-100 transition"
                                        >
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="mx-auto max-w-sm text-center">
                                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <h3 class="mt-3 text-sm font-bold text-ink">No stories found</h3>
                                    <p class="mt-1 text-xs text-muted">Add your first story to showcase interactive Facebook & Instagram style stories on the storefront hero section.</p>
                                    <div class="mt-4">
                                        <a href="{{ route('admin.stories.create') }}" class="btn-primary inline-flex items-center gap-1.5">
                                            + Add New Story
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($stories->hasPages())
            <div class="border-t border-ink/10 px-4 py-3">
                {{ $stories->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
